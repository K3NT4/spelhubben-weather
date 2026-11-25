<?php
/**
 * tests/maintenance_test.php
 *
 * Network-free checks for the real API/service layer (class-sv-vader.php,
 * providers.php, cache.php, options.php): SMHI snow1g, daily timezone,
 * geocoding negative cache, batched statistics and cache invalidation.
 */

define('ABSPATH', __DIR__ . '/../');
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);
define('DAY_IN_SECONDS', 86400);
define('SV_VADER_VER', 'test');

$GLOBALS['svv_db'] = [];            // option storage
$GLOBALS['svv_transients'] = [];    // transient storage
$GLOBALS['svv_http'] = [];          // queued HTTP responses
$GLOBALS['svv_http_log'] = [];      // requested URLs
$GLOBALS['svv_option_writes'] = 0;
$GLOBALS['svv_actions'] = [];

function __($text, $domain = null) { return $text; }
function apply_filters($hook, $value, ...$args) { return $value; }
function determine_locale() { return 'sv_SE'; }
function sanitize_text_field($text) { return trim((string) $text); }
function esc_url_raw($url, $protocols = null) { return trim((string) $url); }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function wp_parse_args($args, $defaults = []) { return array_merge($defaults, is_array($args) ? $args : []); }
function add_query_arg($args, $url) { return $url . (strpos($url, '?') === false ? '?' : '&') . http_build_query($args); }
function current_time($type, $gmt = false) { return $GLOBALS['svv_now'] ?? time(); }
function add_action($hook, $cb) { $GLOBALS['svv_actions'][$hook][] = $cb; }

function get_option($name, $default = false) { return array_key_exists($name, $GLOBALS['svv_db']) ? $GLOBALS['svv_db'][$name] : $default; }
function update_option($name, $value, $autoload = null) {
	// Mirror WordPress: the registered sanitize callback runs on every update.
	if ($name === 'sv_vader_options') $value = sv_vader_sanitize_options($value);
	$GLOBALS['svv_option_writes']++;
	$GLOBALS['svv_db'][$name] = $value;
	return true;
}
function delete_option($name) { unset($GLOBALS['svv_db'][$name]); return true; }
function get_transient($k) { return $GLOBALS['svv_transients'][$k] ?? false; }
function set_transient($k, $v, $ttl = 0) { $GLOBALS['svv_transients'][$k] = $v; return true; }
function delete_transient($k) { unset($GLOBALS['svv_transients'][$k]); return true; }

class WP_Error {
	private $code; private $message;
	public function __construct($code = '', $message = '') { $this->code = $code; $this->message = $message; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error($thing) { return $thing instanceof WP_Error; }
function wp_remote_get($url, $args = []) {
	$GLOBALS['svv_http_log'][] = $url;
	return $GLOBALS['svv_http'] ? array_shift($GLOBALS['svv_http']) : ['response' => ['code' => 200], 'body' => ''];
}
function wp_safe_remote_get($url, $args = []) { return wp_remote_get($url, $args); }
function wp_remote_retrieve_response_code($r) { return is_array($r) ? (int) ($r['response']['code'] ?? 0) : 0; }
function wp_remote_retrieve_body($r) { return is_array($r) ? (string) ($r['body'] ?? '') : ''; }

require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/options.php';
require_once __DIR__ . '/../includes/cache.php';
require_once __DIR__ . '/../includes/class-sv-vader.php';

$failures = 0;
function check($cond, $msg) {
	global $failures;
	echo ($cond ? 'PASS: ' : 'FAIL: ') . $msg . "\n";
	if (!$cond) $failures++;
}
function http_json($data, $code = 200) { return ['response' => ['code' => $code], 'body' => json_encode($data)]; }

// --- SMHI snow1g -----------------------------------------------------------
$GLOBALS['svv_now'] = strtotime('2026-11-25T21:10:00Z');
$GLOBALS['svv_http'] = [http_json(['timeSeries' => [
	['time' => '2026-11-25T20:00:00Z', 'data' => ['air_temperature' => 5]],
	['time' => '2026-11-25T21:00:00Z', 'data' => [
		'air_temperature' => -0.4, 'wind_speed' => 3, 'wind_from_direction' => 321,
		'precipitation_amount_mean' => 0.2, 'cloud_area_fraction' => 4, 'symbol_code' => 6,
	]],
	['time' => '2026-11-25T22:00:00Z', 'data' => ['air_temperature' => 9]],
]])];
$smhi = sv_vader_smhi_current('59.3300', '18.0600');
$url = end($GLOBALS['svv_http_log']);
check(strpos($url, '/api/category/snow1g/version/1/geotype/point/lon/18.06/lat/59.33/data.json') !== false, 'SMHI uses the snow1g/version/1 point endpoint with plain coordinates');
check(strpos($url, 'pmp3g') === false, 'SMHI no longer calls the retired pmp3g API');
check($smhi === ['temp' => -0.4, 'wind' => 3.0, 'wind_dir' => 321.0, 'precip' => 0.2, 'cloud' => 50, 'code' => null, 'desc' => null], 'SMHI snow1g picks the nearest step and converts oktas to percent');
$GLOBALS['svv_http'] = [http_json(['error' => 'outside grid'], 404)];
check(sv_vader_smhi_current('48.85', '2.35') === ['_status' => 'no_coverage'], 'SMHI 404 outside its grid reports no_coverage');
$GLOBALS['svv_http'] = [http_json([], 503)];
check(sv_vader_smhi_current('59.33', '18.06') === ['_status' => 'request_failed'], 'SMHI server errors report request_failed');
$GLOBALS['svv_http'] = [http_json(['timeSeries' => [['time' => '2026-11-25T21:00:00Z', 'data' => ['air_temperature' => null]]]])];
check(sv_vader_smhi_current('59.33', '18.06') === ['_status' => 'no_data'], 'SMHI step without measurements reports no_data');
check(sv_vader_coord_string(1.0E-7) === '0' && sv_vader_coord_string('-12.3456789') === '-12.345679', 'coordinates in URL paths never use exponent notation');

// --- Daily forecast timezone ------------------------------------------------
$GLOBALS['svv_http'] = [http_json(['daily' => ['time' => ['2026-11-25'], 'temperature_2m_max' => [3], 'temperature_2m_min' => [-1], 'weather_code' => [3]]])];
sv_vader_openmeteo_daily('40.71', '-74.0', 3, 'en');
check(strpos(end($GLOBALS['svv_http_log']), 'timezone=auto') !== false, 'daily forecast uses the location timezone, not Europe/Stockholm');

// --- Geocoding negative cache ----------------------------------------------
$api = new SV_Vader_API(10);
$GLOBALS['svv_http_log'] = [];
$GLOBALS['svv_http'] = [http_json(['generationtime_ms' => 0.2])]; // No results.
$first = $api->get_daily_forecast('Nowhereville', '', '', 5);
$second = $api->get_daily_forecast('Nowhereville', '', '', 5);
check($first === [] && $second === [], 'unknown place returns an empty daily forecast');
check(count($GLOBALS['svv_http_log']) === 1, 'an unknown place is geocoded once, then served from the negative cache');
$err = $api->get_current_weather('Nowhereville', '', '', ['openmeteo']);
check(is_wp_error($err) && $err->get_error_code() === 'sv_vader_geocode' && count($GLOBALS['svv_http_log']) === 1, 'cached geocode miss still reports "place not found" without a new request');

// --- Batched statistics -----------------------------------------------------
$GLOBALS['svv_option_writes'] = 0;
sv_vader_stats_hit();
sv_vader_stats_hit();
sv_vader_stats_miss(3, 'Lund', '55.7', '13.2');
check($GLOBALS['svv_option_writes'] === 0, 'cache hits and misses do not write the stats option during rendering');
check(count($GLOBALS['svv_actions']['shutdown'] ?? []) === 1, 'stats flush is scheduled once per request on shutdown');
sv_vader_stats_flush();
$stats = get_option('sv_vader_stats');
$today = gmdate('Y-m-d');
check($GLOBALS['svv_option_writes'] === 1, 'buffered stats are written with a single option update');
check($stats['hits'] === 2 && $stats['misses'] === 1 && $stats['api_calls'] === 3, 'flushed totals include every buffered hit, miss and API call');
check($stats['per_day'][$today] === ['hits' => 2, 'misses' => 1, 'api_calls' => 3], 'flushed per-day counters are correct');
check(($stats['recent'][0]['place'] ?? '') === 'Lund', 'recent lookups survive batching');
sv_vader_stats_flush();
check($GLOBALS['svv_option_writes'] === 1, 'an empty buffer does not write the option again');

// --- Cache invalidation keeps settings on older installs --------------------
// Stored option from an install that predates FMI, Nowcast and map_default.
$GLOBALS['svv_db']['sv_vader_options'] = ['default_ort' => 'Kiruna', 'prov_openmeteo' => 1, 'prov_smhi' => 1, 'prov_yr' => 1, 'cache_salt' => '1'];
sv_vader_cache_invalidate_all();
$after = get_option('sv_vader_options');
check($after['cache_salt'] !== '1', 'clearing the cache rotates the salt');
check($after['default_ort'] === 'Kiruna' && $after['prov_fmi'] === 1 && $after['prov_metno_nowcast'] === 1 && $after['map_default'] === 1, 'clearing the cache does not switch off providers or the map default missing from an older stored option');

if ($failures) {
	echo "$failures maintenance check(s) failed.\n";
	exit(1);
}
echo "Maintenance checks passed.\n";
