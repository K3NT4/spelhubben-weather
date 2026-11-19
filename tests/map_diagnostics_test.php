<?php
// AJAX contract checks without WordPress or network access.
define('ABSPATH', __DIR__ . '/../');
define('DAY_IN_SECONDS', 86400);
function __($text, $domain = null) { return $text; }
function add_action($hook, $callback) { $GLOBALS['hooks'][$hook] = $callback; }
function current_user_can($cap) { return $cap === 'manage_options' && $GLOBALS['authorized']; }
function check_ajax_referer($action, $key) {
	if ($action !== 'svv_map_status' || ($_POST[$key] ?? '') !== 'valid') throw new RuntimeException('nonce');
}
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function wp_unslash($value) { return stripslashes($value); }
function get_transient($key) { return $GLOBALS['cache'][$key] ?? false; }
function set_transient($key, $value, $ttl) { $GLOBALS['cache'][$key] = $value; $GLOBALS['writes']++; }
function wp_send_json_error($data, $status) { $GLOBALS['response'] = $status; }
function wp_send_json_success($data = null) { $GLOBALS['response'] = $data ?? 200; }
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
require __DIR__ . '/../includes/i18n.php';
require __DIR__ . '/../includes/map-diagnostics.php';
$GLOBALS['cache'] = [];
$GLOBALS['writes'] = 0;
$GLOBALS['authorized'] = false;
$_POST = [];
sv_vader_ajax_map_status();
check($GLOBALS['response'] === 403 && $GLOBALS['writes'] === 0, 'Guests denied before nonce validation');
check(!isset($GLOBALS['hooks']['wp_ajax_nopriv_svv_map_status']), 'No public write endpoint');
$GLOBALS['authorized'] = true;
try { sv_vader_ajax_map_status(); throw new RuntimeException('Nonce was not checked'); }
catch (RuntimeException $e) { check($e->getMessage() === 'nonce', 'Invalid nonce rejected'); }
$valid = ['nonce' => 'valid', 'code' => 'SVV_MAP_STARTED', 'engine' => 'openlayers', 'requested' => 'auto'];
foreach ([['code' => 'arbitrary error'], ['engine' => 'unknown'], ['requested' => []], ['engine' => 'static']] as $invalid) {
	$_POST = array_merge($valid, $invalid);
	sv_vader_ajax_map_status();
	check($GLOBALS['response'] === 400 && $GLOBALS['writes'] === 0, 'Invalid enum/shape rejected');
}
$_POST = $valid + ['lat' => '59.12345', 'url' => 'https://private.example/path', 'message' => 'private exception'];
sv_vader_ajax_map_status();
$report = sv_vader_map_get_report('sv_vader_map_last_status');
check(array_keys($report) === ['code','engine','requested','time'], 'Only minimal fixed-schema data stored');
$writes = $GLOBALS['writes'];
sv_vader_ajax_map_status();
check($GLOBALS['writes'] === $writes && $GLOBALS['response']['limited'], 'Repeated report throttled');
$_POST = array_merge($valid, ['code' => 'SVV_MAP_OPENLAYERS_INIT_FAIL', 'engine' => 'static']);
sv_vader_ajax_map_status();
check(sv_vader_map_get_report('sv_vader_map_last_error')['code'] === 'SVV_MAP_OPENLAYERS_INIT_FAIL', 'Latest error retained');
$_POST = array_merge($valid, ['engine' => 'leaflet']);
sv_vader_ajax_map_status();
check(sv_vader_map_get_report('sv_vader_map_last_status')['engine'] === 'leaflet', 'Actual fallback engine recorded');
check(sv_vader_map_get_report('sv_vader_map_last_error')['code'] === 'SVV_MAP_OPENLAYERS_INIT_FAIL', 'Successful startup does not erase last error');
$GLOBALS['cache']['sv_vader_map_last_status'] = ['code' => []];
check(sv_vader_map_get_report('sv_vader_map_last_status') === false, 'Malformed stored data ignored');
echo "Map diagnostics checks passed.\n";
