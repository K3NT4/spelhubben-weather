<?php
// includes/map-diagnostics.php
if ( ! defined( 'ABSPATH' ) ) exit;

/** Fixed messages only: browser exception text and location data are never stored. */
function sv_vader_map_status_messages() {
	$messages = sv_vader_map_translations();
	return [
		'SVV_MAP_STARTED' => __( 'Interactive map started.', 'spelhubben-weather' ),
		'SVV_MAP_STATIC_MODE' => $messages['staticMode'],
		'SVV_MAP_INVALID_COORDS' => $messages['invalidCoords'],
		'SVV_MAP_OPENLAYERS_INIT_FAIL' => $messages['openlayersFailed'],
		'SVV_MAP_LEAFLET_INIT_FAIL' => $messages['leafletFailed'],
		'SVV_MAP_ENGINE_UNAVAILABLE' => $messages['unavailable'],
		'SVV_MAP_NOT_STARTED' => __( 'The visible map has not reported startup. Scripts may be delayed, unavailable or blocked.', 'spelhubben-weather' ),
	];
}

/** Reject stale/malformed cache entries before displaying them in admin. */
function sv_vader_map_get_report($key) {
	$report = get_transient($key);
	$messages = sv_vader_map_status_messages();
	if ( ! is_array($report) || ! isset($report['code'], $report['engine'], $report['requested'], $report['time'])
		|| ! is_string($report['code']) || ! isset($messages[$report['code']])
		|| ! in_array($report['engine'], ['openlayers','leaflet','static'], true)
		|| ! in_array($report['requested'], ['auto','openlayers','leaflet','static'], true)
		|| ! is_int($report['time']) ) {
		return false;
	}
	return $report;
}

function sv_vader_ajax_map_status() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		return;
	}
	check_ajax_referer( 'svv_map_status', 'nonce' );

	$code = isset($_POST['code']) && is_string($_POST['code']) ? sanitize_text_field(wp_unslash($_POST['code'])) : '';
	$engine = isset($_POST['engine']) && is_string($_POST['engine']) ? sanitize_text_field(wp_unslash($_POST['engine'])) : '';
	$requested = isset($_POST['requested']) && is_string($_POST['requested']) ? sanitize_text_field(wp_unslash($_POST['requested'])) : '';
	$messages = sv_vader_map_status_messages();
	if ( ! isset($messages[$code]) || ! in_array($engine, ['openlayers','leaflet','static'], true)
		|| ! in_array($requested, ['auto','openlayers','leaflet','static'], true)
		|| (($code === 'SVV_MAP_STARTED') === ($engine === 'static')) ) {
		wp_send_json_error( [ 'message' => 'invalid' ], 400 );
		return;
	}

	// Bound writes across administrators/tabs, without identifying the visitor.
	$key = 'sv_vader_map_rate_' . md5($code . ':' . $engine . ':' . $requested);
	if ( get_transient($key) ) {
		wp_send_json_success( [ 'limited' => true ] );
		return;
	}
	set_transient($key, 1, 60);
	$report = [ 'code' => $code, 'engine' => $engine, 'requested' => $requested, 'time' => time() ];
	set_transient('sv_vader_map_last_status', $report, 7 * DAY_IN_SECONDS);
	if ( ! in_array($code, ['SVV_MAP_STARTED','SVV_MAP_STATIC_MODE'], true) ) {
		set_transient('sv_vader_map_last_error', $report, 7 * DAY_IN_SECONDS);
	}
	wp_send_json_success();
}
add_action('wp_ajax_svv_map_status', 'sv_vader_ajax_map_status');
