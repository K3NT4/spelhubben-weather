<?php
// includes/format.php
if (!defined('ABSPATH')) exit;

/**
 * Resolve canonical units based on a top-level "units" preset and optional overrides.
 * Presets:
 *  - metric      => C,  ms,  mm
 *  - metric_kmh  => C,  kmh, mm
 *  - imperial    => F,  mph, in
 */
if (!function_exists('sv_vader_resolve_units')) {
	function sv_vader_resolve_units(array $in): array {
		$preset = strtolower($in['units'] ?? '');
		$map = [
			'metric'     => ['temp'=>'C',  'wind'=>'ms',  'precip'=>'mm'],
			'metric_knt' => ['temp'=>'C',  'wind'=>'knt', 'precip'=>'mm'],
			'metric_kmh' => ['temp'=>'C',  'wind'=>'kmh', 'precip'=>'mm'],
			'imperial'   => ['temp'=>'F',  'wind'=>'mph', 'precip'=>'in'],
		];
		$u = $map[$preset] ?? $map['metric'];

		// Optional explicit overrides
		$tu = strtoupper($in['temp_unit']   ?? '');
		$wu = strtolower($in['wind_unit']   ?? '');
		$pu = strtolower($in['precip_unit'] ?? '');

		if (in_array($tu, ['C','F'], true))     $u['temp']   = $tu;
		// Accept both 'knt' and 'kn' as aliases for knots.
		if (in_array($wu, ['ms','kmh','mph','knt','kn'], true)) $u['wind']   = $wu;
		if (in_array($pu, ['mm','in'], true))   $u['precip'] = $pu;

		$u['date_format'] = $in['date_format'] ?? 'D j/n';
		return $u;
	}
}

if (!function_exists('sv_vader_temp')) {
	function sv_vader_temp(?float $celsius, string $unit, int $dec = 0): array {
		if ($celsius === null) return [null, $unit === 'F' ? '°F' : '°C'];
		if ($unit === 'F') {
			$val = $celsius * 9/5 + 32;
			return [ (float) round($val, $dec), '°F' ];
		}
		return [ (float) round($celsius, $dec), '°C' ];
	}
}

if (!function_exists('sv_vader_wind')) {
	function sv_vader_wind(?float $ms, string $unit, int $dec = 0): array {
		if ($ms === null) return [null, $unit];
		switch ($unit) {
			case 'kmh': $val = $ms * 3.6; break;
			case 'knt':
			case 'kn': $val = $ms * 1.94384449; $unit = 'kn'; break;
			case 'mph': $val = $ms * 2.23693629; break;
			default:    $val = $ms; $unit = 'm/s';
		}
		return [ (float) round($val, $dec), $unit ];
	}
}

if (!function_exists('sv_vader_wind_dir')) {
	function sv_vader_wind_dir(?float $deg): string {
		if ($deg === null) return '';
		$cardinals = [
			__('N', 'spelhubben-weather'),
			__('NE', 'spelhubben-weather'),
			__('E', 'spelhubben-weather'),
			__('SE', 'spelhubben-weather'),
			__('S', 'spelhubben-weather'),
			__('SW', 'spelhubben-weather'),
			__('W', 'spelhubben-weather'),
			__('NW', 'spelhubben-weather'),
			__('N', 'spelhubben-weather'),
		];
		return $cardinals[round($deg / 45)];
	}
}

if (!function_exists('sv_vader_wind_dir_icon')) {
	function sv_vader_wind_dir_icon(?float $deg): string {
		if ($deg === null) return '';
		return sprintf(
				'<span class="svv-wind-dir" style="display:inline-block;transform:rotate(%ddeg);line-height:1;font-style:normal;vertical-align:middle;" title="%s">➤</span>',
				intval($deg) - 90,
			esc_attr(sv_vader_wind_dir($deg))
		);
	}
}

if (!function_exists('sv_vader_precip')) {
	function sv_vader_precip(?float $mm, string $unit, int $dec = 1): array {
		if ($mm === null) return [null, $unit];
		if ($unit === 'in') {
			$val = $mm / 25.4;
			return [ (float) round($val, $dec), 'in' ];
		}
		return [ (float) round($mm, $dec), 'mm' ];
	}
}

if (!function_exists('sv_vader_num')) {
	function sv_vader_num($v, int $decimals = 0) {
		if ($v === null || $v === '') return '';
		return number_format_i18n($v, $decimals);
	}
}

/**
 * Get weather alerts based on current conditions
 */
if (!function_exists('sv_vader_get_alerts')) {
	function sv_vader_get_alerts(array $weather): array {
		$alerts = [];
		$opts   = sv_vader_get_options();

		// Resolve units based on settings
		$units = sv_vader_resolve_units($opts);

		// Convert raw weather data (metric) to user-selected units for comparison
		list($temp)   = sv_vader_temp($weather['temp'] ?? null, $units['temp'], 1);
		list($wind)   = sv_vader_wind($weather['wind'] ?? null, $units['wind'], 1);
		list($precip) = sv_vader_precip($weather['precip'] ?? null, $units['precip'], 1);

		// Convert configured alert thresholds (stored in plugin options, metric base)
		// into the resolved units so comparisons are meaningful regardless of unit prefs.
		$th_cold_extreme = sv_vader_temp(floatval($opts['alert_cold_extreme'] ?? 0), $units['temp'], 1)[0];
		$th_cold_freezing = sv_vader_temp(floatval($opts['alert_cold_freezing'] ?? 0), $units['temp'], 1)[0];
		$th_heat_extreme = sv_vader_temp(floatval($opts['alert_heat_extreme'] ?? 0), $units['temp'], 1)[0];
		$th_heat_warm = sv_vader_temp(floatval($opts['alert_heat_warm'] ?? 0), $units['temp'], 1)[0];

		$th_wind_storm = sv_vader_wind(floatval($opts['alert_wind_storm'] ?? 0), $units['wind'], 1)[0];
		$th_wind_strong = sv_vader_wind(floatval($opts['alert_wind_strong'] ?? 0), $units['wind'], 1)[0];

		$th_precip_heavy = sv_vader_precip(floatval($opts['alert_precip_heavy'] ?? 0), $units['precip'], 1)[0];

		if ($temp !== null) {
			if ($temp < $th_cold_extreme) {
				$alerts[] = [
					'level' => 'danger',
					'title' => __('Extreme Cold', 'spelhubben-weather'),
					'msg'   => __('It is freezing cold outside! Dress warmly, preferably in layers, or you might turn into an icicle.', 'spelhubben-weather'),
					'icon'  => 'thermometer-minus'
				];
			} elseif ($temp < $th_cold_freezing) {
				$alerts[] = [
					'level' => 'warning',
					'title' => __('Freezing Temperatures', 'spelhubben-weather'),
					'msg'   => __('It is below freezing outside. Don\'t forget your gloves and hat!', 'spelhubben-weather'),
					'icon'  => 'snow'
				];
			} elseif ($temp > $th_heat_extreme) {
				$alerts[] = [
					'level' => 'danger',
					'title' => __('Extreme Heat', 'spelhubben-weather'),
					'msg'   => __('Phew! It is really hot. Drink plenty of water and stay in the shade if you can.', 'spelhubben-weather'),
					'icon'  => 'thermometer-plus'
				];
			} elseif ($temp > $th_heat_warm) {
				$alerts[] = [
					'level' => 'info',
					'title' => __('Lovely Weather', 'spelhubben-weather'),
					'msg'   => __('It is warm and pleasant outside. Don\'t forget the sunscreen!', 'spelhubben-weather'),
					'icon'  => 'lightbulb'
				];
			}
		}

		if ($wind !== null) {
			if ($wind > $th_wind_storm) {
				$alerts[] = [
					'level' => 'danger',
					'title' => __('Storm Warning', 'spelhubben-weather'),
					'msg'   => __('Storm force winds detected! Stay indoors if possible and secure loose objects.', 'spelhubben-weather'),
					'icon'  => 'wind'
				];
			} elseif ($wind > $th_wind_strong) {
				$alerts[] = [
					'level' => 'warning',
					'title' => __('Strong Wind', 'spelhubben-weather'),
					'msg'   => __('It is very windy outside. Hold on to your hat!', 'spelhubben-weather'),
					'icon'  => 'wind'
				];
			}
		}

		if ($precip !== null && $precip > $opts['alert_precip_heavy']) {
			$alerts[] = [
				'level' => 'info',
				'title' => __('Heavy Precipitation', 'spelhubben-weather'),
				'msg'   => __('It looks like it will rain or snow quite a bit. Don\'t forget your umbrella!', 'spelhubben-weather'),
				'icon'  => 'rain'
			];
		}

		return $alerts;
	}
}
