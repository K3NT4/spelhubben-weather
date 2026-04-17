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

if (!function_exists('get_option')) {
	function get_option($name, $default = false) {
		return $default;
	}
}

if (!function_exists('wp_parse_args')) {
	function wp_parse_args($args, $defaults = []) {
		return array_merge($defaults, is_array($args) ? $args : []);
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
require_once __DIR__ . '/../includes/class-renderer.php';

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
require_once '{$plugin_root}/includes/class-sv-vader.php';
\$api = new SV_Vader_API(10);
\$method = new ReflectionMethod(\$api, 'normalize_providers');
\$method->setAccessible(true);
echo json_encode(\$method->invoke(\$api, [' Yr ', 'smhi', 'yr', '', 'openmeteo', 'SMHI']));
PHP;

$normalize_tmp = tempnam(sys_get_temp_dir(), 'svv-regression-');
file_put_contents($normalize_tmp, $normalize_script);
$normalized_json = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($normalize_tmp));
@unlink($normalize_tmp);
$normalized = json_decode((string) $normalized_json, true);

assert_true(
	$normalized === ['openmeteo', 'smhi', 'yr'],
	'provider normalization trims, deduplicates, lowercases and sorts provider lists'
);

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

echo "All regression checks passed.\n";
