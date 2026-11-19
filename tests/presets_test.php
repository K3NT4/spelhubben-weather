<?php
/**
 * tests/presets_test.php
 *
 * Deterministic checks for the style/layout preset registries and the style
 * preset normalizer. No WordPress install or network access is required.
 *
 * Usage:
 *   php tests/presets_test.php
 */

if (!defined('ABSPATH')) {
	define('ABSPATH', dirname(__DIR__) . '/');
}

if (!function_exists('__')) {
	function __($text, $domain = null) {
		$translations = [
			'Default'        => 'Standard',
			'Light'          => 'Ljus',
			'Dark'           => 'Mörk',
			'Neutral'        => 'Neutral',
			'Spelhubben'     => 'Spelhubben',
			'Compact'        => 'Kompakt',
			'Mini'           => 'Mini',
			'Hero'           => 'Hero',
			'Sidebar'        => 'Sidofält',
			'Dashboard'      => 'Instrumentpanel',
			'Forecast strip' => 'Prognosremsa',
		];
		return $translations[$text] ?? $text;
	}
}

require_once ABSPATH . 'includes/presets.php';

function svv_presets_assert_true($condition, $message) {
	if (!$condition) {
		fwrite(STDERR, "FAIL: " . $message . "\n");
		exit(1);
	}
}

$expected_style_keys = ['', 'light', 'dark', 'neutral', 'spelhubben', 'compact'];
$styles = sv_vader_style_presets();

svv_presets_assert_true(is_array($styles), 'style preset registry is an array');
svv_presets_assert_true(array_keys($styles) === $expected_style_keys, 'style preset registry exposes the documented keys in order');

$expected_style_labels = [
	''           => 'Standard',
	'light'      => 'Ljus',
	'dark'       => 'Mörk',
	'neutral'    => 'Neutral',
	'spelhubben' => 'Spelhubben',
	'compact'    => 'Kompakt',
];
foreach ($expected_style_labels as $key => $label) {
	svv_presets_assert_true(isset($styles[$key]) && is_string($styles[$key]), 'style preset "' . $key . '" has a string label');
	svv_presets_assert_true($styles[$key] === $label, 'style preset "' . $key . '" uses its translated label');
}

foreach ($expected_style_keys as $key) {
	svv_presets_assert_true(sv_vader_normalize_style_preset($key) === $key, 'normalizer keeps valid style key "' . $key . '"');
	svv_presets_assert_true(sv_vader_normalize_style_preset(' ' . $key . ' ') === $key, 'normalizer trims style key "' . $key . '"');
}

svv_presets_assert_true(sv_vader_normalize_style_preset('LIGHT') === 'light', 'normalizer lowercases style keys');
svv_presets_assert_true(sv_vader_normalize_style_preset("\tSpelhubben\n") === 'spelhubben', 'normalizer trims and lowercases mixed-case style keys');
svv_presets_assert_true(sv_vader_normalize_style_preset('   ') === '', 'normalizer maps whitespace to the default style');
svv_presets_assert_true(sv_vader_normalize_style_preset('blue') === '', 'normalizer rejects unknown style names');
svv_presets_assert_true(sv_vader_normalize_style_preset('light-dark') === '', 'normalizer rejects compound style names');
svv_presets_assert_true(sv_vader_normalize_style_preset('0') === '', 'normalizer rejects numeric string values');
svv_presets_assert_true(sv_vader_normalize_style_preset(0) === '', 'normalizer rejects integer values');
svv_presets_assert_true(sv_vader_normalize_style_preset(1.5) === '', 'normalizer rejects float values');
svv_presets_assert_true(sv_vader_normalize_style_preset(true) === '', 'normalizer rejects boolean true');
svv_presets_assert_true(sv_vader_normalize_style_preset(false) === '', 'normalizer treats boolean false as the default style');
svv_presets_assert_true(sv_vader_normalize_style_preset(null) === '', 'normalizer rejects null');
svv_presets_assert_true(sv_vader_normalize_style_preset(['light']) === '', 'normalizer rejects arrays');
svv_presets_assert_true(sv_vader_normalize_style_preset(new stdClass()) === '', 'normalizer rejects objects');
svv_presets_assert_true(sv_vader_normalize_style_preset(new class { public function __toString() { return 'light'; } }) === '', 'normalizer does not accept stringable objects');

$expected_layout_keys = ['mini', 'hero', 'sidebar', 'dashboard', 'forecast-strip'];
$layouts = sv_vader_layout_presets();

svv_presets_assert_true(is_array($layouts), 'layout preset registry is an array');
svv_presets_assert_true(array_keys($layouts) === $expected_layout_keys, 'layout preset registry keeps the existing premium layout names');
$expected_layout_labels = [
	'mini'           => 'Mini',
	'hero'           => 'Hero',
	'sidebar'        => 'Sidofält',
	'dashboard'      => 'Instrumentpanel',
	'forecast-strip' => 'Prognosremsa',
];
foreach ($expected_layout_labels as $key => $label) {
	svv_presets_assert_true(isset($layouts[$key]) && $layouts[$key] === $label, 'layout preset "' . $key . '" uses its translated label');
}

$css = file_get_contents(ABSPATH . 'assets/style.css');
svv_presets_assert_true(is_string($css) && $css !== '', 'frontend stylesheet is readable');
foreach (['light', 'dark', 'neutral', 'spelhubben', 'compact'] as $key) {
	$pattern = '/\.sv-vader\.svv-style-' . preg_quote($key, '/') . '\b/';
	svv_presets_assert_true(preg_match($pattern, $css) === 1, 'stylesheet scopes style "' . $key . '" under .sv-vader');
}

echo "All preset checks passed.\n";
