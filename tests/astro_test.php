<?php
/** Deterministic standalone checks: php tests/astro_test.php */
define('ABSPATH', __DIR__ . '/../');
function __($text, $domain = null) { return 'translated:' . $text; }
require_once __DIR__ . '/../includes/class-astro.php';

function check($condition, string $message): void {
	if (!$condition) throw new RuntimeException($message);
}
function astro($lat, $lon, string $date, string $zone = 'Europe/Stockholm'): array {
	$tz = new DateTimeZone($zone);
	$timestamp = (new DateTimeImmutable($date, $tz))->getTimestamp();
	$result = SV_Vader_Astro::calculate($lat, $lon, $timestamp, $tz);
	check($result['moon'] === sv_vader_moon($timestamp), 'Existing moon result preserved');
	check($result['next_phase']['timestamp'] > $timestamp, 'Next phase strictly in future');
	check($result['next_phase']['timestamp'] - $timestamp <= 638000, 'Next phase within a quarter month');
	check($result['next_phase']['approximate'] === true, 'Approximation explicit');
	check(strpos($result['next_phase']['name'], 'translated:') === 0, 'Phase name translated');
	check($result['timezone'] === $zone, 'Timezone preserved');
	foreach (['sunrise', 'sunset'] as $key) {
		if ($result[$key] !== null) {
			check(is_int($result[$key]), 'Event is an integer');
			$event = (new DateTimeImmutable('@' . $result[$key]))->setTimezone($tz);
			check(abs($event->getTimestamp() - (new DateTimeImmutable($date, $tz))->getTimestamp()) < 129600, 'Event is in the adjacent solar cycle');
		}
	}
	return $result;
}

$summer = astro(59.3293, 18.0686, '2026-06-21 12:00');
$winter = astro(59.3293, 18.0686, '2026-12-21 12:00');
check($summer['sun_status'] === 'normal' && $winter['sun_status'] === 'normal', 'Stockholm normal');
check($summer['day_length'] > 18 * 3600 && $summer['day_length'] < 19 * 3600, 'Stockholm summer daylight');
check($winter['day_length'] > 6 * 3600 && $winter['day_length'] < 7 * 3600, 'Stockholm winter daylight');
foreach (['2026-03-29', '2026-10-25'] as $date) {
	$a = astro(59.3293, 18.0686, $date . ' 00:01');
	$b = astro(59.3293, 18.0686, $date . ' 23:59');
	check($a['sunrise'] === $b['sunrise'] && $a['sunset'] === $b['sunset'], 'DST date stable');
}
foreach (['2026-06-21' => 'polar_day', '2026-12-21' => 'polar_night'] as $date => $status) {
	$r = astro(78.2232, 15.6469, $date . ' 12:00');
	check($r['sun_status'] === $status, 'Polar status');
	check($r['sunrise'] === null && $r['sunset'] === null, 'Polar booleans never timestamps');
	check($r['day_length'] === ($status === 'polar_day' ? 86400 : 0), 'Polar daylight');
}
check(astro(0, 0, '2026-03-20 12:00')['sun_status'] === 'normal', 'Zero coordinates valid');
check(astro('0', '0', '2026-03-20 12:00')['sun_status'] === 'normal', 'Numeric strings valid');
foreach ([[null, 0], [false, 0], ['', 0], ['12x', 0], [91, 0], [0, -181], [NAN, 0], [0, INF], [[], 0], [new stdClass(), 0]] as $pair) {
	$r = astro($pair[0], $pair[1], '2026-06-21 12:00');
	check($r['sun_status'] === 'unavailable' && $r['day_length'] === null && $r['sunrise'] === null && $r['sunset'] === null, 'Invalid coordinates rejected');
}
foreach ([['Pacific/Kiritimati', 1.87, -157.43], ['Etc/GMT+12', 0, -179]] as $case) {
	$a = astro($case[1], $case[2], '2026-06-21 00:01', $case[0]);
	$b = astro($case[1], $case[2], '2026-06-21 23:59', $case[0]);
	check($a['sun_status'] === 'normal' && $a['sunrise'] !== null && $a['sunset'] !== null, 'Date boundary events available');
	check($a['sunrise'] === $b['sunrise'] && $a['sunset'] === $b['sunset'], 'Boundary date stable');
}
// A distant place uses one coherent solar cycle even in the site's timezone.
$tokyo = astro(35.6762, 139.6503, '2026-06-21 12:00', 'Europe/Stockholm');
check($tokyo['sunrise'] < $tokyo['sunset'], 'Tokyo sunrise precedes sunset');
check($tokyo['sunset'] - $tokyo['sunrise'] === $tokyo['day_length'], 'Displayed events match daylight duration');
// The estimate must reach the selected main phase using the existing algorithm.
foreach ([0, 946684800, 1782043200, -86400] as $timestamp) {
	$r = SV_Vader_Astro::calculate(0, 0, $timestamp);
	$fraction = sv_vader_moon($r['next_phase']['timestamp'])['fraction'] * 4;
	check(abs($fraction - round($fraction)) < 0.00001, 'Next timestamp matches a main phase');
	check($r['timezone'] === 'UTC', 'UTC fallback');
	check($r['next_phase']['timestamp'] > $timestamp, 'Historic next phase future');
}
// Declare dynamically to exercise the WP fallback only after UTC checks.
eval('function wp_timezone() { return new DateTimeZone("Europe/Stockholm"); }');
check(SV_Vader_Astro::calculate(0, 0, 0)['timezone'] === 'Europe/Stockholm', 'WordPress timezone fallback');
check(SV_Vader_Astro::calculate(0, 0, 0, new DateTimeZone('UTC'))['timezone'] === 'UTC', 'Explicit timezone overrides WordPress');
echo "Astro tests passed.\n";
