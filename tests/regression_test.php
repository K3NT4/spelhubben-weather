<?php
/**
 * tests/regression_test.php
 *
 * Lightweight regression checks for renderer/provider behavior without requiring
 * a full WordPress test suite.
 *
 * Usage:
 *   php tests/regression_test.php
 */

define('ABSPATH', __DIR__ . '/../');

if (!function_exists('__')) {
	function __($text, $domain = null) {
		return $text;
	}
}

if (!function_exists('esc_html__')) {
	function esc_html__($text, $domain = null) {
		return $text;
	}
}

if (!function_exists('esc_attr_x')) {
	function esc_attr_x($text, $context, $domain = null) {
		return $text;
	}
}

if (!function_exists('esc_html_e')) {
	function esc_html_e($text, $domain = null) {
		echo $text;
	}
}

if (!function_exists('shortcode_atts')) {
	function shortcode_atts($pairs, $atts, $shortcode = '') {
		return array_merge($pairs, is_array($atts) ? $atts : []);
	}
}

if (!function_exists('sanitize_text_field')) {
	function sanitize_text_field($text) {
		return trim((string) $text);
	}
}

if (!function_exists('esc_url_raw')) {
	function esc_url_raw($url) {
		return trim((string) $url);
	}
}

if (!function_exists('esc_textarea')) {
	function esc_textarea($text) {
		return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('get_option')) {
	function get_option($name, $default = false) {
		if ($name === 'sv_vader_options' && isset($GLOBALS['svv_test_options']) && is_array($GLOBALS['svv_test_options'])) {
			return $GLOBALS['svv_test_options'];
		}
		if (isset($GLOBALS['svv_test_wp_options']) && is_array($GLOBALS['svv_test_wp_options']) && array_key_exists($name, $GLOBALS['svv_test_wp_options'])) {
			return $GLOBALS['svv_test_wp_options'][$name];
		}
		return $default;
	}
}

if (!function_exists('determine_locale')) {
	function determine_locale() {
		return $GLOBALS['svv_test_locale'] ?? 'en_US';
	}
}

if (!function_exists('wp_parse_args')) {
	function wp_parse_args($args, $defaults = []) {
		return array_merge($defaults, is_array($args) ? $args : []);
	}
}

if (!function_exists('add_query_arg')) {
	function add_query_arg($args, $url) {
		return $url . (strpos($url, '?') === false ? '?' : '&') . http_build_query($args);
	}
}

if (!function_exists('wp_remote_get')) {
	function wp_remote_get($url, $args = []) {
		if (!empty($GLOBALS['svv_remote_responses'])) {
			return array_shift($GLOBALS['svv_remote_responses']);
		}
		return ['response' => ['code' => 200], 'body' => ''];
	}
}

if (!function_exists('wp_remote_retrieve_response_code')) {
	function wp_remote_retrieve_response_code($response) {
		return (int) ($response['response']['code'] ?? 0);
	}
}

if (!function_exists('wp_remote_retrieve_body')) {
	function wp_remote_retrieve_body($response) {
		return (string) ($response['body'] ?? '');
	}
}

if (!function_exists('current_time')) {
	function current_time($type, $gmt = false) {
		return time();
	}
}

if (!function_exists('esc_attr')) {
	function esc_attr($text) {
		return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('esc_html')) {
	function esc_html($text) {
		return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('esc_url')) {
	function esc_url($url) {
		return (string) $url;
	}
}

if (!function_exists('wp_kses_post')) {
	function wp_kses_post($html) {
		return (string) $html;
	}
}

if (!function_exists('date_i18n')) {
	function date_i18n($format, $timestamp) {
		return date($format, $timestamp);
	}
}

if (!function_exists('number_format_i18n')) {
	function number_format_i18n($number, $decimals = 0) {
		return number_format((float) $number, $decimals, '.', '');
	}
}

if (!defined('SV_VADER_ATTRIB_HTML')) {
	define('SV_VADER_ATTRIB_HTML', 'Map data © OpenStreetMap contributors');
}

if (!class_exists('WP_Error')) {
	class WP_Error {
	}
}

if (!class_exists('WP_Post')) {
	class WP_Post {
		public $post_content = '';
	}
}

if (!function_exists('is_wp_error')) {
	function is_wp_error($thing) {
		return $thing instanceof WP_Error;
	}
}

if (!class_exists('SV_Vader_API')) {
	class SV_Vader_API {
		public function __construct($cache_minutes = 10) {
		}

		public function get_current_weather($ort = '', $lat = '', $lon = '', $providers = [], $yr_contact = '') {
			return [
				'name' => $ort !== '' ? $ort : 'Stockholm',
				'lat' => $lat !== '' ? $lat : '59.3293',
				'lon' => $lon !== '' ? $lon : '18.0686',
				'temp' => 6.4,
				'wind' => 3.2,
				'wind_dir' => 180,
				'precip' => 0.5,
				'cloud' => 25,
				'desc' => 'Clear sky',
				'code' => 0,
			];
		}

		public function get_provider_details($ort = '', $lat = '', $lon = '', $providers = [], $yr_contact = '') {
			return [
				'name' => $ort !== '' ? $ort : 'Stockholm',
				'lat' => $lat !== '' ? $lat : '59.3293',
				'lon' => $lon !== '' ? $lon : '18.0686',
				'providers' => [],
			];
		}

		public function get_daily_forecast($ort = '', $lat = '', $lon = '', $days = 5) {
			return [];
		}

		public function get_hourly_forecast($ort = '', $lat = '', $lon = '', $hours = 24) {
			return [
				[
					'time' => '2026-05-03T12:00',
					'temp' => 8.2,
					'wind' => 4.1,
					'precip' => 0.3,
					'code' => 61,
					'desc' => 'Rain: light',
				],
			];
		}

		public function map_icon_url($code = null) {
			return 'https://example.test/icon.svg';
		}
	}
}

if (!function_exists('sv_vader_api_lang')) {
	function sv_vader_api_lang() {
		return 'en';
	}
}

if (!function_exists('sv_vader_cache_get')) {
	function sv_vader_cache_get($key) {
		return false;
	}
}

if (!function_exists('sv_vader_cache_set')) {
	function sv_vader_cache_set($key, $value, $ttl) {
		return true;
	}
}

if (!function_exists('sv_vader_stats_hit')) {
	function sv_vader_stats_hit() {
	}
}

if (!function_exists('sv_vader_stats_miss')) {
	function sv_vader_stats_miss($providers_count = 0, $place = '', $lat = '', $lon = '') {
	}
}

require_once __DIR__ . '/../includes/options.php';
require_once __DIR__ . '/../includes/format.php';
require_once __DIR__ . '/../includes/providers.php';
require_once __DIR__ . '/../includes/class-renderer.php';
require_once __DIR__ . '/../includes/class-assets.php';
require_once __DIR__ . '/../includes/class-block.php';

function assert_true($condition, $message) {
	if (!$condition) {
		fwrite(STDERR, "FAIL: {$message}\n");
		exit(1);
	}

	echo "PASS: {$message}\n";
}

$renderer = new SV_Vader_Renderer();

$html_min_height = $renderer->render_shortcode([
	'ort' => 'Stockholm',
	'map' => '1',
	'map_height' => '20',
	'layout' => 'card',
	'show' => 'temp,wind,icon',
	'forecast' => 'none',
]);

assert_true(strpos($html_min_height, 'style="height: 120px;"') !== false, 'renderer clamps map height to 120px minimum');

$html_custom_height = $renderer->render_shortcode([
	'ort' => 'Stockholm',
	'map' => '1',
	'map_height' => '180',
	'layout' => 'card',
	'show' => 'temp,wind,icon',
	'forecast' => 'none',
]);

assert_true(strpos($html_custom_height, 'style="height: 180px;"') !== false, 'renderer preserves valid custom map height');

$plugin_root = realpath(__DIR__ . '/..');
$normalize_script = <<<PHP
<?php
define('ABSPATH', '{$plugin_root}/');
if (!class_exists('WP_Error')) {
	class WP_Error {}
}
if (!function_exists('__')) {
	function __(\$text, \$domain = null) { return \$text; }
}
require_once '{$plugin_root}/includes/class-sv-vader.php';
require_once '{$plugin_root}/includes/providers.php';
\$api = new SV_Vader_API(10);
\$method = new ReflectionMethod(\$api, 'normalize_providers');
\$method->setAccessible(true);
echo json_encode([
	'basic' => \$method->invoke(\$api, [' Yr ', 'smhi', 'yr', '', 'openmeteo', 'SMHI']),
	'registry_order' => \$method->invoke(\$api, ['weatherapi', 'metno_nowcast', 'openmeteo']),
]);
PHP;

$normalize_tmp = tempnam(sys_get_temp_dir(), 'svv-regression-');
file_put_contents($normalize_tmp, $normalize_script);
$normalized_json = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($normalize_tmp));
@unlink($normalize_tmp);
$normalized = json_decode((string) $normalized_json, true);

assert_true(
	($normalized['basic'] ?? null) === ['openmeteo', 'smhi', 'yr'],
	'provider normalization trims, deduplicates, lowercases and sorts provider lists'
);
assert_true(
	($normalized['registry_order'] ?? null) === ['openmeteo', 'metno_nowcast', 'weatherapi'],
	'provider normalization follows registry priority order'
);

assert_true(function_exists('sv_vader_provider_registry'), 'provider registry function exists');
$registry = sv_vader_provider_registry();
assert_true(isset($registry['metno_nowcast']), 'provider registry contains MET Norway Nowcast');
assert_true(!empty($registry['openweathermap']['requires_key']), 'provider registry marks OpenWeatherMap as key-required');
assert_true(!empty($registry['weatherapi']['requires_key']), 'provider registry marks WeatherAPI as key-required');
assert_true(empty(sv_vader_default_options()['prov_openweathermap']), 'OpenWeatherMap disabled by default until API key is configured');
assert_true(empty(sv_vader_default_options()['prov_weatherapi']), 'WeatherAPI disabled by default until API key is configured');
assert_true(sv_vader_symbol_code_to_wmo('partlycloudy_day') === 2, 'MET partlycloudy symbol maps to WMO partly cloudy');

$GLOBALS['svv_remote_responses'] = [
	['response' => ['code' => 500], 'body' => ''],
];
$failed_current = sv_vader_openmeteo_current('59.3293', '18.0686');
assert_true(($failed_current['_status'] ?? '') === 'request_failed', 'Open-Meteo current reports request_failed for HTTP failures');

$GLOBALS['svv_remote_responses'] = [
	['response' => ['code' => 200], 'body' => '{}'],
];
$empty_current = sv_vader_openmeteo_current('59.3293', '18.0686');
assert_true(($empty_current['_status'] ?? '') === 'no_data', 'Open-Meteo current reports no_data for empty payloads');

$sanitized = sv_vader_sanitize_options([
	'map_height' => '20',
	'default_layout' => 'weird',
	'prov_openweathermap' => '1',
	'prov_weatherapi' => '1',
	'owm_api_key' => ' test-owm ',
	'weatherapi_api_key' => ' test-wa ',
]);

assert_true($sanitized['map_height'] === 120, 'options sanitizer clamps map height to minimum');
assert_true($sanitized['default_layout'] === 'card', 'options sanitizer falls back to card layout for invalid layout');
assert_true($sanitized['owm_api_key'] === 'test-owm' && $sanitized['weatherapi_api_key'] === 'test-wa', 'options sanitizer keeps API keys trimmed');

$html_hourly = $renderer->render_shortcode([
	'ort' => 'Stockholm',
	'layout' => 'card',
	'map' => '0',
	'hourly' => '1',
	'hours' => '24',
	'show' => 'temp,wind,icon',
]);

assert_true(strpos($html_hourly, 'svv-hourly') !== false, 'renderer outputs hourly forecast container when hourly=1');
assert_true(strpos($html_hourly, 'data-svv-hours="24"') !== false, 'renderer records clamped hourly hours count');
assert_true(sv_vader_hour_time_format('sv_SE') === 'H:i', 'hourly time format uses 24-hour clock for Swedish');
assert_true(sv_vader_hour_time_format('nb_NO') === 'H:i', 'hourly time format uses 24-hour clock for Norwegian Bokmal');
assert_true(sv_vader_hour_time_format('en_US') === 'g:i A', 'hourly time format keeps 12-hour clock for US English');

$GLOBALS['svv_test_locale'] = 'sv_SE';
$GLOBALS['svv_test_wp_options']['time_format'] = 'g:i a';
$html_hourly_sv = $renderer->render_shortcode([
	'ort' => 'Stockholm',
	'layout' => 'card',
	'map' => '0',
	'hourly' => '1',
	'hours' => '24',
	'show' => 'temp,wind,icon',
]);
assert_true(strpos($html_hourly_sv, '<div class="svv-hour-time">12:00</div>') !== false, 'hourly forecast uses locale-appropriate 24-hour time for Swedish');
assert_true(strpos($html_hourly_sv, '12:00 pm') === false && strpos($html_hourly_sv, '12:00 f m') === false, 'hourly forecast avoids 12-hour meridiem for Swedish');
unset($GLOBALS['svv_test_locale'], $GLOBALS['svv_test_wp_options']);

$html_moon_compact = $renderer->render_shortcode([
	'ort' => 'Stockholm',
	'layout' => 'compact',
	'map' => '0',
	'extras' => 'moon',
	'show' => 'temp,wind,icon',
]);
assert_true(strpos($html_moon_compact, 'class="svv-moon"') !== false, 'compact layout renders moon extras');

$html_moon_detailed = $renderer->render_shortcode([
	'ort' => 'Stockholm',
	'layout' => 'detailed',
	'map' => '0',
	'extras' => 'moon',
	'show' => 'temp,wind,icon',
]);
assert_true(substr_count($html_moon_detailed, 'class="svv-moon"') === 1, 'detailed layout renders moon extras once');

$map_js = file_get_contents(__DIR__ . '/../assets/map.js');
assert_true(strpos($map_js, 'unpkg.com') === false, 'map runtime does not contain external Leaflet CDN fallback');
$map_min_js = file_get_contents(__DIR__ . '/../assets/map.min.js');
assert_true(strpos($map_min_js, 'unpkg.com') === false, 'minified map runtime does not contain external Leaflet CDN fallback');

$block_json = json_decode((string) file_get_contents(__DIR__ . '/../blocks/spelhubben-weather/block.json'), true);
assert_true(isset($block_json['attributes']['hourly']), 'block declares hourly attribute');
assert_true(isset($block_json['attributes']['hours']), 'block declares hours attribute');
assert_true(isset($block_json['attributes']['tides']), 'block declares tides attribute');
assert_true(isset($block_json['attributes']['theme']), 'block declares theme attribute');
assert_true(isset($block_json['attributes']['mapEngine']), 'block declares mapEngine attribute');
assert_true(isset($block_json['attributes']['preset']), 'block declares preset attribute');

if (!defined('SV_VADER_URL')) {
	define('SV_VADER_URL', 'https://example.test/wp-content/plugins/spelhubben-weather/');
}
if (!defined('SV_VADER_DIR')) {
	define('SV_VADER_DIR', dirname(__DIR__) . '/');
}
if (!defined('SV_VADER_VER')) {
	define('SV_VADER_VER', '2.1.0-test');
}

if (!function_exists('trailingslashit')) {
	function trailingslashit($value) {
		return rtrim((string) $value, '/') . '/';
	}
}

if (!function_exists('wp_register_style')) {
	function wp_register_style($handle, $src = '', $deps = [], $ver = false) {
		$GLOBALS['svv_registered_styles'][] = $handle;
	}
}

if (!function_exists('wp_register_script')) {
	function wp_register_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false) {
		$GLOBALS['svv_registered_scripts'][] = $handle;
	}
}

if (!function_exists('wp_enqueue_style')) {
	function wp_enqueue_style($handle) {
		$GLOBALS['svv_enqueued_styles'][] = $handle;
	}
}

if (!function_exists('wp_enqueue_script')) {
	function wp_enqueue_script($handle) {
		$GLOBALS['svv_enqueued_scripts'][] = $handle;
	}
}

if (!function_exists('wp_add_inline_script')) {
	function wp_add_inline_script($handle, $data, $position = 'after') {
		$GLOBALS['svv_inline_scripts'][] = $handle;
	}
}

if (!function_exists('wp_localize_script')) {
	function wp_localize_script($handle, $object_name, $l10n) {
		$GLOBALS['svv_localized_scripts'][$handle] = [$object_name, $l10n];
	}
}

if (!function_exists('wp_set_script_translations')) {
	function wp_set_script_translations($handle, $domain = 'default', $path = null) {
		$GLOBALS['svv_translated_scripts'][] = $handle;
	}
}

if (!function_exists('has_shortcode')) {
	function has_shortcode($content, $tag) {
		return strpos((string) $content, '[' . $tag) !== false;
	}
}

if (!function_exists('has_block')) {
	function has_block($block_name, $post = null) {
		return false;
	}
}

if (!function_exists('get_queried_object')) {
	function get_queried_object() {
		return null;
	}
}

if (!function_exists('is_active_widget')) {
	function is_active_widget($callback = false, $widget_id = false, $id_base = false, $skip_inactive = true) {
		return false;
	}
}

$GLOBALS['svv_test_options'] = ['map_engine' => 'static'];
$GLOBALS['svv_enqueued_styles'] = [];
$GLOBALS['svv_enqueued_scripts'] = [];
$GLOBALS['post'] = new WP_Post();
$GLOBALS['post']->post_content = '[spelhubben_weather map="1" map_engine="openlayers"]';

$assets = new SV_Vader_Assets();
$assets->enqueue_public_assets();

assert_true(in_array('svv-openlayers-css', $GLOBALS['svv_enqueued_styles'], true), 'OpenLayers CSS enqueued even when global map engine is static and instance requests OpenLayers');
assert_true(in_array('svv-openlayers-js', $GLOBALS['svv_enqueued_scripts'], true), 'OpenLayers JS enqueued even when global map engine is static and instance requests OpenLayers');
assert_true(in_array('sv-vader-map', $GLOBALS['svv_enqueued_scripts'], true), 'map runtime enqueued for per-instance map engine');

$widget_code = file_get_contents(__DIR__ . '/../includes/Widget/class-widget.php');
assert_true(strpos($widget_code, "'tides'") !== false, 'widget exposes tides setting');
assert_true(strpos($widget_code, "'tides'       =>") !== false || strpos($widget_code, "'tides'      =>") !== false, 'widget forwards tides to renderer');

$shortcodes_page = file_get_contents(__DIR__ . '/../admin/page-shortcodes.php');
assert_true(strpos($shortcodes_page, 'svv-b-hours') !== false, 'Shortcodes Quick Builder exposes hours control');
assert_true(strpos($shortcodes_page, 'svv-b-tides') !== false, 'Shortcodes Quick Builder exposes tides control');

$admin_js = file_get_contents(__DIR__ . '/../admin/admin.js');
assert_true(strpos($admin_js, 'svv-b-hours') !== false, 'Quick Builder reads hours control');
assert_true(strpos($admin_js, 'svv-b-tides') !== false, 'Quick Builder reads tides control');

$settings_page = file_get_contents(__DIR__ . '/../admin/page-settings.php');
$performance_page = file_get_contents(__DIR__ . '/../admin/page-performance.php');
$admin_css = file_get_contents(__DIR__ . '/../admin/admin.css');
$main_plugin = file_get_contents(__DIR__ . '/../spelhubben-weather.php');
$admin_php = file_get_contents(__DIR__ . '/../admin/admin.php');
$assets_php = file_get_contents(__DIR__ . '/../includes/class-assets.php');
$block_php = file_get_contents(__DIR__ . '/../includes/class-block.php');
assert_true(strpos($settings_page, 'svv-grid--settings') !== false, 'settings page uses wide settings grid');
assert_true(strpos($shortcodes_page, 'svv-grid--shortcodes') !== false, 'shortcodes page uses shortcodes grid');
assert_true(strpos($performance_page, 'svv-grid--performance') !== false, 'performance page uses performance grid');
assert_true(strpos($admin_css, '.svv-grid--settings') !== false && strpos($admin_css, 'minmax(620px, 1fr)') !== false, 'admin CSS keeps settings form column wide enough');
assert_true(strpos($admin_css, '.svv-form .form-table') !== false && strpos($admin_css, 'table-layout:fixed') !== false, 'admin CSS constrains settings form table layout');
assert_true(strpos($main_plugin, 'load_plugin_textdomain') !== false && strpos($main_plugin, "dirname( plugin_basename( __FILE__ ) ) . '/languages'") !== false, 'plugin loads bundled text domain');
assert_true(strpos($admin_php, "wp_set_script_translations( 'sv-vader-admin', 'spelhubben-weather', SV_VADER_DIR . 'languages' )") !== false, 'admin script translations use bundled language path');
assert_true(strpos($assets_php, "wp_set_script_translations( 'sv-vader-map', 'spelhubben-weather', SV_VADER_DIR . 'languages' )") !== false, 'map script translations use bundled language path');
assert_true(strpos($block_php, 'spelhubben-weather-spelhubben-weather-editor-script') !== false && strpos($block_php, "dirname( __DIR__ ) . '/languages'") !== false, 'block editor translations use bundled language path');

assert_true(file_exists(__DIR__ . '/../blocks/spelhubben-weather/index.asset.php'), 'block editor script declares WordPress dependencies');

if (!function_exists('register_block_type')) {
	function register_block_type($block_type, $args = []) {
		$GLOBALS['svv_registered_blocks'][$block_type] = $args;
	}
}

$capturing_renderer = new class {
	public $atts = [];
	public function render_shortcode($atts) {
		$this->atts = $atts;
		return '';
	}
};
$block = new SV_Vader_Block($capturing_renderer);
$block->register_block();
$main_block_key = realpath(__DIR__ . '/../blocks/spelhubben-weather');
$callback = $GLOBALS['svv_registered_blocks'][$main_block_key]['render_callback'] ?? null;
assert_true(is_callable($callback), 'main block render callback registered');
$callback(['ort' => 'Stockholm', 'place' => 'Gothenburg']);
assert_true(($capturing_renderer->atts['ort'] ?? '') === 'Gothenburg', 'block render prefers non-empty place over default ort');

echo "All regression checks passed.\n";
