<?php
/** Run isolated, network-free PHP test suites on every supported PHP version. */
$suites = ['regression_test.php', 'map_diagnostics_test.php', 'astro_test.php', 'insights_test.php', 'presets_test.php', 'reviews_test.php', 'maintenance_test.php'];
foreach ($suites as $suite) {
	passthru(escapeshellarg(PHP_BINARY) . ' -d error_reporting=-1 -d display_errors=1 ' . escapeshellarg(__DIR__ . '/' . $suite), $status);
	if ($status !== 0) exit($status);
}
