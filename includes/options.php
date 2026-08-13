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
			'default_show'   => 'temp,wind,wind_dir,icon',
			'default_layout' => 'card',
			'map_default'    => 1,
			'map_height'     => 240,
			'map_engine'     => 'auto', // auto|openlayers|leaflet|static
			'icon_style'     => 'classic',  // classic | modern-flat | modern-gradient

			// Data providers
			'prov_openmeteo'     => 1,
			'prov_smhi'          => 1,
			'prov_yr'            => 1,
			'prov_metno_nowcast' => 1,
        'prov_fmi'           => 1,
		'prov_openweathermap' => 0,
		'prov_weatherapi'     => 0,
			'owm_api_key'        => '',
			'weatherapi_api_key' => '',
			'temp_unit'    => '',       // optional override: C|F
			'wind_unit'    => '',       // optional override: ms|kmh|mph|knt
			'precip_unit'  => '',       // optional override: mm|in
			'date_format'  => 'D j/n',  // used in forecast labels

			// Contact + units (for backward compatibility when upgrading from older installs)
			'yr_contact'   => '',
			'units'        => 'metric',

			// NEW: cache salt (rotates when user clears cache)
			'cache_salt'   => '1',

			// Tides
			'tides_enabled'      => 0,
			'tide_provider'      => 'custom', // worldtides|noaa|custom
			'tide_api_key'       => '',
			'tide_custom_endpoint' => '',
			'tide_cache_minutes' => 60,
			// Show tide notices/examples in admin UI (separate from enabling tide fetching)
			// Default to 0 (hidden) because these are just examples/notices for testing
			'tides_admin_visible' => 0,

			// Alert Thresholds
			'alert_cold_extreme'  => -15,
			'alert_cold_freezing' => 0,
			'alert_heat_extreme'  => 30,
			'alert_heat_warm'     => 25,
			'alert_wind_storm'    => 24.5,
			'alert_wind_strong'   => 15,
			'alert_precip_heavy'  => 5,
			'show_alerts'         => 1, // Default to show alerts
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
		$in  = is_array($in) ? $in : [];
		$current = get_option('sv_vader_options', []);
		$current = wp_parse_args(is_array($current) ? $current : [], $def);

		$out['default_ort']    = sanitize_text_field($in['default_ort'] ?? $def['default_ort']);
		$out['cache_minutes']  = max(1, intval($in['cache_minutes'] ?? $def['cache_minutes']));

		$allowed_show = ['temp','wind','wind_dir','icon'];
		$show_in = strtolower((string)($in['default_show'] ?? $def['default_show']));
		$show_in = array_filter(array_map('trim', explode(',', $show_in)));
		$show_in = array_values(array_unique(array_intersect($show_in, $allowed_show)));
		$out['default_show'] = implode(',', $show_in ?: ['temp','wind','wind_dir','icon']);

		$allowed_layouts = ['inline','compact','card','detailed'];
		$layout_in = strtolower((string)($in['default_layout'] ?? $def['default_layout']));
		$out['default_layout'] = in_array($layout_in, $allowed_layouts, true) ? $layout_in : 'card';

		$out['map_default'] = !empty($in['map_default']) ? 1 : 0;
		$out['map_height']  = max(120, intval($in['map_height'] ?? $def['map_height']));
		$map_engine = strtolower(trim((string)($in['map_engine'] ?? $def['map_engine'])));
		$out['map_engine'] = in_array($map_engine, ['auto','openlayers','leaflet','static'], true) ? $map_engine : 'auto';

		$out['prov_openmeteo']     = !empty($in['prov_openmeteo']) ? 1 : 0;
		$out['prov_smhi']          = !empty($in['prov_smhi']) ? 1 : 0;
		$out['prov_yr']            = !empty($in['prov_yr']) ? 1 : 0;
		$out['prov_metno_nowcast'] = !empty($in['prov_metno_nowcast']) ? 1 : 0;
        $out['prov_fmi']           = !empty($in['prov_fmi']) ? 1 : 0;
		$out['prov_openweathermap'] = !empty($in['prov_openweathermap']) ? 1 : 0;
		$out['prov_weatherapi']     = !empty($in['prov_weatherapi']) ? 1 : 0;
		$out['owm_api_key']        = sanitize_text_field($in['owm_api_key'] ?? '');
		$out['weatherapi_api_key'] = sanitize_text_field($in['weatherapi_api_key'] ?? '');

		// Icon style preference
		$allowed_icon_styles = ['classic','modern-flat','modern-gradient','modern-2026','modern-3d'];
		$icon_style_in = strtolower((string)($in['icon_style'] ?? $def['icon_style'] ?? 'classic'));
		$out['icon_style'] = in_array($icon_style_in, $allowed_icon_styles, true) ? $icon_style_in : 'classic';

		$out['yr_contact'] = sanitize_text_field($in['yr_contact'] ?? $def['yr_contact']);

		// NEW: Units & format
		$units_allowed = ['metric','metric_kmh','metric_knt','imperial'];
		$units_in = strtolower((string)($in['units'] ?? $def['units']));
		$out['units'] = in_array($units_in, $units_allowed, true) ? $units_in : 'metric';

		$tu = strtoupper((string)($in['temp_unit'] ?? ''));
		$wu = strtolower((string)($in['wind_unit'] ?? ''));
		$pu = strtolower((string)($in['precip_unit'] ?? ''));
		$out['temp_unit']   = in_array($tu, ['C','F'], true) ? $tu : '';
		// Accept both 'knt' and 'kn' as valid aliases for knots.
		$out['wind_unit']   = in_array($wu, ['ms','kmh','mph','knt','kn'], true) ? $wu : '';
		$out['precip_unit'] = in_array($pu, ['mm','in'], true) ? $pu : '';
		$out['date_format'] = sanitize_text_field($in['date_format'] ?? $def['date_format']);

		// Preserve/initialize cache salt
		$out['cache_salt'] = sanitize_text_field($in['cache_salt'] ?? $def['cache_salt']);

		// Alert Thresholds
		// Alert settings are edited on a separate admin page. Preserve them when
		// the main settings form, which does not contain these fields, is saved.
		$out['alert_cold_extreme']  = floatval($in['alert_cold_extreme']  ?? $current['alert_cold_extreme']);
		$out['alert_cold_freezing'] = floatval($in['alert_cold_freezing'] ?? $current['alert_cold_freezing']);
		$out['alert_heat_extreme']  = floatval($in['alert_heat_extreme']  ?? $current['alert_heat_extreme']);
		$out['alert_heat_warm']     = floatval($in['alert_heat_warm']     ?? $current['alert_heat_warm']);
		$out['alert_wind_storm']    = floatval($in['alert_wind_storm']    ?? $current['alert_wind_storm']);
		$out['alert_wind_strong']   = floatval($in['alert_wind_strong']   ?? $current['alert_wind_strong']);
		$out['alert_precip_heavy']  = floatval($in['alert_precip_heavy']  ?? $current['alert_precip_heavy']);
		$out['show_alerts']         = !empty($in['show_alerts'] ?? $current['show_alerts']) ? 1 : 0;

		// Tides
		$out['tides_enabled'] = !empty($in['tides_enabled']) ? 1 : 0;
		$prov = strtolower(trim((string)($in['tide_provider'] ?? $current['tide_provider'] ?? 'custom')));
		$out['tide_provider'] = in_array($prov, ['worldtides','noaa','custom'], true) ? $prov : 'custom';
		$out['tide_api_key'] = sanitize_text_field($in['tide_api_key'] ?? $current['tide_api_key']);
		$tide_endpoint = esc_url_raw(
			trim((string)($in['tide_custom_endpoint'] ?? $current['tide_custom_endpoint'])),
			['https']
		);
		$out['tide_custom_endpoint'] = 'https' === wp_parse_url($tide_endpoint, PHP_URL_SCHEME)
			? $tide_endpoint
			: '';
		$out['tide_cache_minutes'] = max(5, intval($in['tide_cache_minutes'] ?? $current['tide_cache_minutes'] ?? 60));
		// Admin visibility toggle for tide UI (helps disable admin notices/examples during rollout)
		$out['tides_admin_visible'] = !empty($in['tides_admin_visible']) ? 1 : 0;

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
