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
if (!function_exists('svv_resolve_units')) {
	function svv_resolve_units(array $in): array {
		$preset = strtolower($in['units'] ?? '');
		$map = [
			'metric'     => ['temp'=>'C',  'wind'=>'ms',  'precip'=>'mm'],
			'metric_kmh' => ['temp'=>'C',  'wind'=>'kmh', 'precip'=>'mm'],
			'imperial'   => ['temp'=>'F',  'wind'=>'mph', 'precip'=>'in'],
		];
		$u = $map[$preset] ?? $map['metric'];

		// Optional explicit overrides
		$tu = strtoupper($in['temp_unit']   ?? '');
		$wu = strtolower($in['wind_unit']   ?? '');
		$pu = strtolower($in['precip_unit'] ?? '');

		if (in_array($tu, ['C','F'], true))     $u['temp']   = $tu;
		if (in_array($wu, ['ms','kmh','mph'], true)) $u['wind']   = $wu;
		if (in_array($pu, ['mm','in'], true))   $u['precip'] = $pu;

		$u['date_format'] = $in['date_format'] ?? 'D j/n';
		return $u;
	}
}

if (!function_exists('svv_temp')) {
	function svv_temp(?float $celsius, string $unit, int $dec = 0): array {
		if ($celsius === null) return [null, $unit === 'F' ? '°F' : '°C'];
		if ($unit === 'F') {
			$val = $celsius * 9/5 + 32;
			return [ (float) round($val, $dec), '°F' ];
		}
		return [ (float) round($celsius, $dec), '°C' ];
	}
}

if (!function_exists('svv_wind')) {
	function svv_wind(?float $ms, string $unit, int $dec = 0): array {
		if ($ms === null) return [null, $unit];
		switch ($unit) {
			case 'kmh': $val = $ms * 3.6; break;
			case 'mph': $val = $ms * 2.23693629; break;
			default:    $val = $ms; $unit = 'm/s';
		}
		return [ (float) round($val, $dec), $unit ];
	}
}

if (!function_exists('svv_precip')) {
	function svv_precip(?float $mm, string $unit, int $dec = 1): array {
		if ($mm === null) return [null, $unit];
		if ($unit === 'in') {
			$val = $mm / 25.4;
			return [ (float) round($val, $dec), 'in' ];
		}
		return [ (float) round($mm, $dec), 'mm' ];
	}
}

if (!function_exists('svv_num')) {
	function svv_num($v, int $decimals = 0) {
		if ($v === null || $v === '') return '';
		return number_format_i18n($v, $decimals);
	}
}
