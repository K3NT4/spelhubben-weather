<?php
/**
 * tests/tide_test.php
 *
 * Simple CLI test to validate tide fetching and caching.
 * Usage (from plugin folder or WP root):
 *  php tests/tide_test.php [WP_ROOT] [lat] [lon]
 *
 * If WP_ROOT is omitted, the script will try to locate wp-load.php in common relative paths.
 */

$argv = $_SERVER['argv'];
$script = array_shift($argv);
$wp_root = $argv[0] ?? null;
$lat = $argv[1] ?? '57.7089'; // Gothenburg
$lon = $argv[2] ?? '11.9667';

function find_wp_load($explicit = null) {
    if ($explicit) {
        $p = rtrim($explicit, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'wp-load.php';
        if (file_exists($p)) return $p;
    }
    $cands = [
        __DIR__ . '/../../../wp-load.php',
        __DIR__ . '/../../../../wp-load.php',
        __DIR__ . '/../../../../../wp-load.php',
        __DIR__ . '/../wp-load.php',
        __DIR__ . '/../../wp-load.php',
    ];
    foreach ($cands as $c) { if (file_exists($c)) return $c; }
    return false;
}

$wp_load = find_wp_load($wp_root);
if (!$wp_load) {
    fwrite(STDERR, "Could not find wp-load.php. Run this script from a WordPress installation or pass the WP root path as first arg.\n");
    exit(2);
}

require_once $wp_load;

// Try to include the plugin main file (relative to tests dir)
$plugin_main = __DIR__ . '/../spelhubben-weather.php';
if (file_exists($plugin_main)) {
    require_once $plugin_main;
} else {
    fwrite(STDERR, "Plugin file not found at " . $plugin_main . "\n");
    // continue — functions may be available if plugin is active
}

if (!function_exists('sv_vader_get_options')) {
    fwrite(STDERR, "Plugin functions not available. Ensure the plugin is loaded or active.\n");
    exit(3);
}

$opts = sv_vader_get_options();
printf("Tide feature enabled: %s\n", !empty($opts['tides_enabled']) ? 'yes' : 'no');
printf("Tide provider: %s\n", $opts['tide_provider'] ?? '(not set)');
printf("Tide API key present: %s\n", (!empty($opts['tide_api_key']) ? 'yes' : 'no'));

$prov = $opts['tide_provider'] ?? 'custom';
$salt = sv_vader_cache_salt();
$cache_key = 'sv_vader_tides_' . md5($lat . '|' . $lon . '|' . $prov . '|' . $salt);

// Clear any existing cache for a clean test
if (function_exists('sv_vader_cache_delete')) {
    sv_vader_cache_delete($cache_key);
    echo "Cleared existing tide cache (if any).\n";
}

$api = new SV_Vader_API(intval($opts['cache_minutes'] ?? 10));

$start = microtime(true);
$tide = $api->get_tides('', $lat, $lon);
$dur = microtime(true) - $start;

echo "First fetch duration: " . round($dur, 3) . "s\n";
if ($tide === null) {
    echo "Tide fetch returned null (no data). Check provider settings and network.\n";
    exit(0);
}

echo "Tide result (first 5 events):\n";
$events = $tide['events'] ?? [];
foreach (array_slice($events, 0, 5) as $e) {
    printf(" - %s: %s %s\n", ucfirst($e['type'] ?? 'event'), $e['time'] ?? '', isset($e['height']) ? (float)$e['height'] . ' m' : '');
}

$cached = sv_vader_cache_get($cache_key);
printf("Cache present after fetch: %s\n", $cached !== false ? 'yes' : 'no');

// Second fetch (should be served from cache)
$start2 = microtime(true);
$tide2 = $api->get_tides('', $lat, $lon);
$dur2 = microtime(true) - $start2;

echo "Second fetch duration: " . round($dur2, 3) . "s\n";

if ($tide2 === null) {
    echo "Second fetch returned null — unexpected if cached.\n";
} else {
    echo "Second fetch returned " . count($tide2['events'] ?? []) . " events.\n";
}

if ($dur2 < $dur) {
    echo "Second call faster (likely from cache).\n";
} else {
    echo "Second call not faster — cache may not be used.\n";
}

echo "Test complete.\n";
