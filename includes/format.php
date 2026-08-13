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
		// Normalize degrees to [0,360)
		$deg = fmod(floatval($deg) + 360.0, 360.0);
		$cardinals = [
			__('N', 'spelhubben-weather'),
			__('NE', 'spelhubben-weather'),
			__('E', 'spelhubben-weather'),
			__('SE', 'spelhubben-weather'),
			__('S', 'spelhubben-weather'),
			__('SW', 'spelhubben-weather'),
			__('W', 'spelhubben-weather'),
			__('NW', 'spelhubben-weather'),
		];

		// Use standard sector calculation: each cardinal spans 45°, centered on multiples of 45°.
		// Adding 22.5° before flooring ensures correct rounding at boundaries.
		$index = (int) floor(($deg + 22.5) / 45.0) % 8;
		return $cardinals[$index];
	}
}

if (!function_exists('sv_vader_wind_dir_icon')) {
	function sv_vader_wind_dir_icon(?float $deg): string {
		if ($deg === null) return '';
		// Output a data attribute instead of inline styles (wp_kses_post may strip style attrs).
		$deg_val = floatval($deg);
		return sprintf(
				'<span class="svv-wind-dir" data-deg="%s" title="%s">➤</span>',
			esc_attr((string)$deg_val),
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

if (!function_exists('sv_vader_hour_time_format')) {
	/**
	 * Pick a compact hour format from the active WordPress locale.
	 *
	 * Hourly cards are small, so Swedish/Nordic and most non-US locales use
	 * 24-hour time even if the site option is set to a 12-hour format.
	 */
	function sv_vader_hour_time_format(?string $locale = null): string {
		if ($locale === null || $locale === '') {
			if (function_exists('determine_locale')) {
				$locale = determine_locale();
			} elseif (function_exists('get_locale')) {
				$locale = get_locale();
			} else {
				$locale = 'en_US';
			}
		}

		$locale = strtolower(str_replace('-', '_', (string) $locale));
		$twelve_hour_locales = ['en', 'en_us', 'en_ca', 'en_au', 'en_nz', 'en_ph'];
		$format = in_array($locale, $twelve_hour_locales, true) ? 'g:i A' : 'H:i';

		return function_exists('apply_filters')
			? (string) apply_filters('sv_vader_hour_time_format', $format, $locale)
			: $format;
	}
}

if (!function_exists('sv_vader_format_hour_time')) {
	function sv_vader_format_hour_time(int $timestamp): string {
		if ($timestamp <= 0) {
			return '';
		}

		$format = sv_vader_hour_time_format();
		if (function_exists('wp_date')) {
			return (string) wp_date($format, $timestamp);
		}
		return (string) date_i18n($format, $timestamp);
	}
}

if (!function_exists('sv_vader_moon')) {
		/**
		 * Compute simple moon phase information for a given timestamp.
		 * Returns age (days), phase name and illumination percent.
		 * Uses a low-cost algorithm based on Julian date and synodic month.
		 */
		function sv_vader_moon(?int $timestamp = null): array {
			if ($timestamp === null) $timestamp = time();

			// Julian date for given timestamp (UTC)
			$t = gmdate('U', $timestamp);
			$jd = ($t / 86400.0) + 2440587.5;

			// Known new moon reference: 2000 Jan 6. (JD 2451550.1)
			$synodic_month = 29.530588853; // average length
			$d = $jd - 2451550.1;
			$age = fmod($d, $synodic_month);
			if ($age < 0) $age += $synodic_month;

			$phase_fraction = $age / $synodic_month; // 0..1

			// Illumination fraction (approx)
			$illum = (1.0 - cos(2.0 * M_PI * $phase_fraction)) / 2.0;

			// Map to common phase names (8 classical phases)
			$phase_index = (int) floor($phase_fraction * 8 + 0.5) % 8;
			$phase_names = [
				__('New Moon', 'spelhubben-weather'),
				__('Waxing Crescent', 'spelhubben-weather'),
				__('First Quarter', 'spelhubben-weather'),
				__('Waxing Gibbous', 'spelhubben-weather'),
				__('Full Moon', 'spelhubben-weather'),
				__('Waning Gibbous', 'spelhubben-weather'),
				__('Last Quarter', 'spelhubben-weather'),
				__('Waning Crescent', 'spelhubben-weather'),
			];

			$phase = $phase_names[$phase_index] ?? $phase_names[0];

			return [
				'age' => $age,
				'phase' => $phase,
				'phase_index' => $phase_index,
				'illum' => round($illum * 100, 0), // percent
				'fraction' => $phase_fraction,
			];
		}
	}

if (!function_exists('sv_vader_moon_icon')) {
	/**
	 * Return a small unicode moon glyph for a given phase index (0..7).
	 */
	function sv_vader_moon_icon(int $phase_index): string {
		// Unicode moon phase characters
		$icons = [
			"🌑", // New Moon U+1F311
			"🌒", // Waxing Crescent U+1F312
			"🌓", // First Quarter U+1F313
			"🌔", // Waxing Gibbous U+1F314
			"🌕", // Full Moon U+1F315
			"🌖", // Waning Gibbous U+1F316
			"🌗", // Last Quarter U+1F317
			"🌘", // Waning Crescent U+1F318
		];
		$i = $phase_index % 8;
		return $icons[$i] ?? '';
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

		if ($precip !== null && $precip > $th_precip_heavy) {
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
