<?php
/** Local, network-free solar events and approximate lunar phases. */
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/format.php';

class SV_Vader_Astro {
	/**
	 * Calculate for the calendar date containing $timestamp in $timezone.
	 * Coordinates accept finite numbers or numeric strings within geographic bounds.
	 *
	 * @return array sunrise/sunset: Unix seconds from the same solar cycle, or null;
	 * day_length: seconds of daylight in the solar cycle whose transit is on that
	 * date (86400/0 for polar day/night), or null; sun_status: normal,
	 * polar_day, polar_night or unavailable; moon: unchanged sv_vader_moon result;
	 * next_phase: translated name, future Unix timestamp, approximate=true;
	 * timezone: timezone identifier. Lunar timing uses a mean synodic month and
	 * is an estimate, not a precision astronomical prediction. An unusual timezone
	 * relative to longitude can put a cycle's rise or set outside the local date;
	 * the panel includes dates so those adjacent-day events remain unambiguous.
	 */
	public static function calculate($lat, $lon, ?int $timestamp = null, ?DateTimeZone $timezone = null): array {
		$timestamp = $timestamp ?? time();
		$timezone = $timezone ?? (function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('UTC'));
		$moon = sv_vader_moon($timestamp);
		$quarter = (int) floor($moon['fraction'] * 4) + 1;
		$seconds = ($quarter / 4 - $moon['fraction']) * 29.530588853 * 86400;
		$names = [
			__('New Moon', 'spelhubben-weather'),
			__('First Quarter', 'spelhubben-weather'),
			__('Full Moon', 'spelhubben-weather'),
			__('Last Quarter', 'spelhubben-weather'),
		];
		$result = [
			'sunrise' => null,
			'sunset' => null,
			'day_length' => null,
			'sun_status' => 'unavailable',
			'moon' => $moon,
			'next_phase' => [
				'name' => $names[$quarter % 4],
				'timestamp' => $timestamp + max(1, (int) ceil($seconds)),
				'approximate' => true,
			],
			'timezone' => $timezone->getName(),
		];
		if (!self::valid_coordinate($lat, 90) || !self::valid_coordinate($lon, 180)) {
			return $result;
		}

		$local = (new DateTimeImmutable('@' . $timestamp))->setTimezone($timezone);
		$date = $local->format('Y-m-d');
		// PHP selects the UTC calendar date. Search adjacent UTC dates so the
		// chosen events belong to the requested local day, including UTC +/-14.
		$anchor = new DateTimeImmutable($date . ' 12:00:00', new DateTimeZone('UTC'));
		$cycle = null;
		for ($offset = -2; $offset <= 2; $offset++) {
			$info = date_sun_info($anchor->getTimestamp() + $offset * 86400, (float) $lat, (float) $lon);
			if ($info === false) continue;
			if (is_int($info['transit']) && self::local_date($info['transit'], $timezone) === $date) {
				$cycle = $info;
			}
		}
		if ($cycle !== null) {
			if ($cycle['sunrise'] === true && $cycle['sunset'] === true) {
				$result['sun_status'] = 'polar_day';
				$result['day_length'] = 86400;
			} elseif ($cycle['sunrise'] === false && $cycle['sunset'] === false) {
				$result['sun_status'] = 'polar_night';
				$result['day_length'] = 0;
			} elseif (is_int($cycle['sunrise']) && is_int($cycle['sunset'])) {
				$result['sunrise'] = $cycle['sunrise'];
				$result['sunset'] = $cycle['sunset'];
				$result['sun_status'] = 'normal';
				$result['day_length'] = $cycle['sunset'] - $cycle['sunrise'];
			}
		}
		return $result;
	}

	private static function valid_coordinate($value, int $limit): bool {
		return (is_int($value) || is_float($value) || is_string($value))
			&& is_numeric($value) && is_finite((float) $value)
			&& (float) $value >= -$limit && (float) $value <= $limit;
	}

	private static function local_date(int $timestamp, DateTimeZone $timezone): string {
		return (new DateTimeImmutable('@' . $timestamp))->setTimezone($timezone)->format('Y-m-d');
	}
}
