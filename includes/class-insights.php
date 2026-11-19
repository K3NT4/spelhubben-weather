<?php
/** Deterministic, conservative insights from hourly forecasts. */
if (!defined('ABSPATH')) exit;
class SV_Vader_Insights {
	/**
	 * Input rows: time (Unix integer or ISO8601 with Z/explicit offset), wind
	 * (m/s), precip (mm per hour). Numeric strings are accepted; null, booleans,
	 * negative and non-finite measurements are not. Naive local times are rejected.
	 *
	 * Only starts at/after $timestamp and before $timestamp + 24 hours are used.
	 * The first start must be within one hour; at least six consecutive hourly
	 * rows are required. Duplicate starts, gaps or invalid rows cause fallback.
	 * Historical rows are ignored, but unparseable times cause fallback.
	 * Each row represents [time, time + 3600); ends in the result are exclusive.
	 *
	 * Returns ['available'=>bool, 'dry_period'=>null|['start'=>int,'end'=>int],
	 * 'wind_peak'=>null|['start'=>int,'end'=>int,'wind'=>float],
	 * 'rain_later'=>null|['start'=>int,'precip'=>float]]. All times are Unix UTC.
	 * Dry: longest run >=2 hours, every hour <0.1 mm; earliest wins ties.
	 * Wind: strongest hour in this forecast (not a warning); earliest wins ties,
	 * adjacent equal maxima are combined. All-zero wind has no peak insight.
	 * Rain later: first hour >=0.2 mm at least two hours after reference time,
	 * provided the first two forecast hours are both dry (<0.1 mm).
	 * Rain precip is that hour's amount, never a probability or a period total.
	 * available means sufficient data, even if individual insights are null.
	 * No confidence/probability is inferred, and no day-boundary assumption is made.
	 */
	public static function calculate(array $hourly, ?int $timestamp = null): array {
		$result = ['available' => false, 'dry_period' => null, 'wind_peak' => null, 'rain_later' => null];
		$now = $timestamp ?? time();
		$rows = [];
		foreach ($hourly as $row) {
			if (!is_array($row)) return $result;
			$start = self::parse_time($row['time'] ?? null);
			if ($start === null) return $result;
			if ($start < $now || $start >= $now + 86400) continue;
			foreach (['wind', 'precip'] as $key) {
				$value = $row[$key] ?? null;
				if ((!is_int($value) && !is_float($value) && !is_string($value)) || !is_numeric($value)
					|| !is_finite((float) $value) || (float) $value < 0) return $result;
			}
			$rows[] = ['time' => $start, 'wind' => (float) $row['wind'], 'precip' => (float) $row['precip']];
		}
		usort($rows, static function ($a, $b) { return $a['time'] <=> $b['time']; });
		$count = count($rows);
		if ($count < 6 || $rows[0]['time'] > $now + 3600) return $result;
		for ($i = 1; $i < $count; $i++) {
			if ($rows[$i]['time'] - $rows[$i - 1]['time'] !== 3600) return $result;
		}
		$result['available'] = true;
		$dry_start = null;
		$best_length = 0;
		$peak = null;
		$initial_dry = $rows[0]['precip'] < 0.1 && $rows[1]['precip'] < 0.1;
		foreach ($rows as $i => $row) {
			if ($row['precip'] < 0.1) {
				if ($dry_start === null) $dry_start = $row['time'];
				$length = $row['time'] + 3600 - $dry_start;
				if ($length >= 7200 && $length > $best_length) {
					$best_length = $length;
					$result['dry_period'] = ['start' => $dry_start, 'end' => $row['time'] + 3600];
				}
			} else {
				$dry_start = null;
			}
			if ($row['wind'] > 0 && ($peak === null || $row['wind'] > $peak['wind'])) {
				$peak = ['start' => $row['time'], 'end' => $row['time'] + 3600, 'wind' => $row['wind']];
			} elseif ($peak !== null && $row['wind'] === $peak['wind'] && $row['time'] === $peak['end']) {
				$peak['end'] += 3600;
			}
			if ($initial_dry && $result['rain_later'] === null && $row['time'] >= $now + 7200 && $row['precip'] >= 0.2) {
				$result['rain_later'] = ['start' => $row['time'], 'precip' => $row['precip']];
			}
		}
		$result['wind_peak'] = $peak;
		return $result;
	}

	private static function parse_time($value): ?int {
		if (is_int($value)) return $value;
		if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?(?:Z|[+-](?:[01]\d|2[0-3]):[0-5]\d)$/D', $value)) return null;
		$format = substr($value, 16, 1) === ':'
			? '!Y-m-d\TH:i:sP' : '!Y-m-d\TH:iP';
		$date = DateTimeImmutable::createFromFormat($format, $value, new DateTimeZone('UTC'));
		$errors = DateTimeImmutable::getLastErrors();
		if ($date === false || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))) return null;
		return $date->getTimestamp();
	}
}
