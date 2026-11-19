<?php
if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/../');
/** Standalone checks: php tests/insights_test.php */
require_once __DIR__ . '/../includes/class-insights.php';

$checks = 0;
function check_insight($condition, $label) {
	global $checks;
	$checks++;
	if (!$condition) {
		fwrite(STDERR, "FAIL: $label\n");
		exit(1);
	}
}
function forecast_rows($start, $count = 8) {
	$rows = [];
	for ($i = 0; $i < $count; $i++) {
		$rows[] = ['time' => $start + $i * 3600, 'wind' => 0, 'precip' => 0];
	}
	return $rows;
}
$now = 1800000000;
$base = forecast_rows($now);
$fallback = ['available' => false, 'dry_period' => null, 'wind_peak' => null, 'rain_later' => null];
foreach ([[], array_slice($base, 0, 5), forecast_rows($now - 864000), forecast_rows($now + 7200)] as $rows) {
	check_insight(SV_Vader_Insights::calculate($rows, $now) === $fallback, 'insufficient/stale/distant data fallback');
}
$result = SV_Vader_Insights::calculate($base, $now);
check_insight($result['available'] && $result['dry_period'] === ['start' => $now, 'end' => $now + 28800], 'zero values are valid and dry');
check_insight($result['wind_peak'] === null && $result['rain_later'] === null, 'zero wind/rain has no peak or rain');
check_insight(SV_Vader_Insights::calculate(array_reverse($base), $now) === $result, 'sort input');
foreach ([null, NAN, INF, -1, '', 'invalid', false, []] as $bad) {
	foreach (['wind', 'precip'] as $key) {
		$rows = $base;
		$rows[3][$key] = $bad;
		check_insight(SV_Vader_Insights::calculate($rows, $now) === $fallback, 'reject invalid ' . $key);
	}
}
$rows = $base;
unset($rows[3]['precip']);
check_insight(SV_Vader_Insights::calculate($rows, $now) === $fallback, 'missing measurement');
$rows = $base;
unset($rows[3]);
check_insight(SV_Vader_Insights::calculate($rows, $now) === $fallback, 'gap');
$rows = $base;
$rows[] = $base[2];
check_insight(SV_Vader_Insights::calculate($rows, $now) === $fallback, 'duplicate');
foreach (['2027-02-30T12:00:00Z', '2027-01-01T25:00:00Z', '2027-01-01T12:00:00', 'tomorrow', null, 1.5, '1800000000', '2027-01-01T12:00:00+99:00'] as $bad) {
	$rows = $base;
	$rows[0]['time'] = $bad;
	check_insight(SV_Vader_Insights::calculate($rows, $now) === $fallback, 'invalid/ambiguous timestamp');
}
foreach (['UTC', 'Europe/Stockholm', 'America/New_York'] as $zone) {
	$rows = $base;
	foreach ($rows as &$row) {
		$row['time'] = (new DateTimeImmutable('@' . $row['time']))->setTimezone(new DateTimeZone($zone))->format('Y-m-d\TH:i:sP');
	}
	unset($row);
	date_default_timezone_set('Pacific/Honolulu');
	check_insight(SV_Vader_Insights::calculate($rows, $now) === $result, 'timezone-independent instants ' . $zone);
}
$rows = $base;
foreach ($rows as &$row) $row['time'] = gmdate('Y-m-d\TH:i:s\Z', $row['time']);
unset($row);
check_insight(SV_Vader_Insights::calculate($rows, $now) === $result, 'UTC Z format');
$minute_now = intdiv($now, 60) * 60;
$rows = forecast_rows($minute_now);
foreach ($rows as &$row) $row['time'] = gmdate('Y-m-d\TH:i\Z', $row['time']);
unset($row);
check_insight(SV_Vader_Insights::calculate($rows, $minute_now)['available'], 'ISO without seconds');
$rows = array_merge(forecast_rows($now - 7200, 2), $base);
check_insight(SV_Vader_Insights::calculate($rows, $now) === $result, 'ignore historical starts');
check_insight(SV_Vader_Insights::calculate(forecast_rows($now - 1800), $now)['dry_period']['start'] === $now + 1800, 'only future hours');
$rows = forecast_rows($now, 30);
$rows[25]['wind'] = null;
check_insight(SV_Vader_Insights::calculate($rows, $now)['dry_period']['end'] === $now + 86400, '24 hour horizon');
$rows = $base;
$rows[2]['precip'] = 0.2;
$rows[3]['precip'] = 0.1;
$rows[4]['wind'] = 8;
$rows[5]['wind'] = 8;
$rows[7]['wind'] = 8;
$result = SV_Vader_Insights::calculate($rows, $now);
check_insight($result['rain_later'] === ['start' => $now + 7200, 'precip' => 0.2], 'rain later threshold');
check_insight($result['dry_period'] === ['start' => $now + 14400, 'end' => $now + 28800], 'longest dry run');
check_insight($result['wind_peak'] === ['start' => $now + 14400, 'end' => $now + 21600, 'wind' => 8.0], 'wind peak plateau earliest tie');
$rows[0]['precip'] = 0.1;
check_insight(SV_Vader_Insights::calculate($rows, $now)['rain_later'] === null, 'rain later requires initially dry hours');
$rows = forecast_rows($now, 6);
foreach ($rows as &$row) { $row['precip'] = '0.1'; $row['wind'] = '2.5'; }
unset($row);
$result = SV_Vader_Insights::calculate($rows, $now);
check_insight($result['available'] && $result['dry_period'] === null && $result['rain_later'] === null, 'numeric strings and dry boundary');
$rows = forecast_rows($now, 6);
$rows[1]['precip'] = 1;
$rows[4]['precip'] = 1;
check_insight(SV_Vader_Insights::calculate($rows, $now)['dry_period'] === ['start' => $now + 7200, 'end' => $now + 14400], 'single dry hours excluded');
fwrite(STDOUT, "OK: $checks insights checks\n");
