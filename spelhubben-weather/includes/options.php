<?php
// includes/options.php
if (!defined('ABSPATH')) exit;

/**
 * Default plugin options (keys kept in Swedish for backward compatibility).
 */
if (!function_exists('sv_vader_default_options')) {
	function sv_vader_default_options() : array {
		return [
			'default_ort'    => 'Stockholm',
			'cache_minutes'  => 10,
			'default_show'   => 'temp,wind,icon',
			'default_layout' => 'card',
			'map_default'    => 1,
			'map_height'     => 240,

			// Data providers
			'prov_openmeteo'     => 1,
			'prov_smhi'          => 1,
			'prov_yr'            => 1,
			'prov_metno_nowcast' => 1,
			'yr_contact'         => 'kontakt@example.com',

			// NEW: Units & formatting
			'units'        => 'metric', // metric | metric_kmh | imperial
			'temp_unit'    => '',       // optional override: C|F
			'wind_unit'    => '',       // optional override: ms|kmh|mph
			'precip_unit'  => '',       // optional override: mm|in
			'date_format'  => 'D j/n',  // used in forecast labels

			// NEW: cache salt (rotates when user clears cache)
			'cache_salt'   => '1',
		];
	}
}

/** Get merged options */
if (!function_exists('sv_vader_get_options')) {
	function sv_vader_get_options() : array {
		$o = get_option('sv_vader_options', []);
		return wp_parse_args($o, sv_vader_default_options());
	}
}

/** Sanitize options payload */
if (!function_exists('sv_vader_sanitize_options')) {
	function sv_vader_sanitize_options($in) : array {
		$def = sv_vader_default_options();
		$out = [];

		$out['default_ort']    = sanitize_text_field($in['default_ort'] ?? $def['default_ort']);
		$out['cache_minutes']  = max(1, intval($in['cache_minutes'] ?? $def['cache_minutes']));

		$allowed_show = ['temp','wind','icon'];
		$show_in = strtolower((string)($in['default_show'] ?? $def['default_show']));
		$show_in = array_filter(array_map('trim', explode(',', $show_in)));
		$show_in = array_values(array_unique(array_intersect($show_in, $allowed_show)));
		$out['default_show'] = implode(',', $show_in ?: ['temp','wind','icon']);

		$allowed_layouts = ['inline','compact','card','detailed'];
		$layout_in = strtolower((string)($in['default_layout'] ?? $def['default_layout']));
		$out['default_layout'] = in_array($layout_in, $allowed_layouts, true) ? $layout_in : 'card';

		$out['map_default'] = !empty($in['map_default']) ? 1 : 0;
		$out['map_height']  = max(120, intval($in['map_height'] ?? $def['map_height']));

		$out['prov_openmeteo']     = !empty($in['prov_openmeteo']) ? 1 : 0;
		$out['prov_smhi']          = !empty($in['prov_smhi']) ? 1 : 0;
		$out['prov_yr']            = !empty($in['prov_yr']) ? 1 : 0;
		$out['prov_metno_nowcast'] = !empty($in['prov_metno_nowcast']) ? 1 : 0;

		$out['yr_contact'] = sanitize_text_field($in['yr_contact'] ?? $def['yr_contact']);

		// NEW: Units & format
		$units_allowed = ['metric','metric_kmh','imperial'];
		$units_in = strtolower((string)($in['units'] ?? $def['units']));
		$out['units'] = in_array($units_in, $units_allowed, true) ? $units_in : 'metric';

		$tu = strtoupper((string)($in['temp_unit'] ?? ''));
		$wu = strtolower((string)($in['wind_unit'] ?? ''));
		$pu = strtolower((string)($in['precip_unit'] ?? ''));
		$out['temp_unit']   = in_array($tu, ['C','F'], true) ? $tu : '';
		$out['wind_unit']   = in_array($wu, ['ms','kmh','mph'], true) ? $wu : '';
		$out['precip_unit'] = in_array($pu, ['mm','in'], true) ? $pu : '';
		$out['date_format'] = sanitize_text_field($in['date_format'] ?? $def['date_format']);

		// Preserve/initialize cache salt
		$out['cache_salt'] = sanitize_text_field($in['cache_salt'] ?? $def['cache_salt']);

		return $out;
	}
}

/** Helper: current cache salt */
if (!function_exists('sv_vader_cache_salt')) {
	function sv_vader_cache_salt(): string {
		$o = sv_vader_get_options();
		$val = (string) ($o['cache_salt'] ?? '1');
		return $val !== '' ? $val : '1';
	}
}
