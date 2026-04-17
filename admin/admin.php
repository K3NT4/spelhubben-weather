<?php
/**
 * Admin bootstrap for Spelhubben Weather
 *
 * Copyright (C) 2026 Spelhubben
 * Licensed under the GNU General Public License v3 (or later)
 * https://www.gnu.org/licenses/gpl-3.0.html
 */
// admin/admin.php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Load sub-pages
 * - Done conditionally inside callbacks, but we require files here for simplicity.
 */
require_once __DIR__ . '/page-settings.php';
require_once __DIR__ . '/page-shortcodes.php';
require_once __DIR__ . '/page-performance.php';
require_once __DIR__ . '/page-alerts.php';

/**
 * Enqueue admin assets only on our pages
 */
if ( ! function_exists( 'sv_vader_admin_enqueue' ) ) {
	function sv_vader_admin_enqueue( $hook ) {
		// Load our assets on all pages where hook contains "sv-vader"
		if ( strpos( $hook, 'sv-vader' ) === false ) {
			return;
		}

		// Enqueue WP.org plugin showcase
		if ( class_exists( 'SV_Vader_WPOrg_Plugins' ) ) {
			$wporg = new SV_Vader_WPOrg_Plugins();
			$wporg->enqueue_assets( $hook );
		}

		// Robust building of URL + version-bust via filemtime
		$plugin_file = defined( 'SV_VADER_FILE' ) ? SV_VADER_FILE : __DIR__ . '/../spelhubben-weather.php';
		$base_url    = plugins_url( '', $plugin_file );
		$base_path   = plugin_dir_path( $plugin_file );

		$css_rel = 'admin/admin.css';
		$js_rel  = 'admin/admin.js';

		$css_ver = file_exists( $base_path . $css_rel ) ? filemtime( $base_path . $css_rel ) : ( defined( 'SV_VADER_VER' ) ? SV_VADER_VER : time() );
		$js_ver  = file_exists( $base_path . $js_rel )  ? filemtime( $base_path . $js_rel )  : ( defined( 'SV_VADER_VER' ) ? SV_VADER_VER : time() );

		wp_enqueue_style(
			'sv-vader-admin',
			$base_url . '/' . $css_rel,
			array(),
			$css_ver
		);

		wp_enqueue_script(
			'sv-vader-admin',
			$base_url . '/' . $js_rel,
			array(),
			$js_ver,
			true
		);

		// Enable JS translations for admin script when available
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'sv-vader-admin', 'spelhubben-weather' );
		}
		wp_localize_script( 'sv-vader-admin', 'SVV_ADMIN_I18N', array(
			'copied'     => __( 'Copied!', 'spelhubben-weather' ),
			'copy'       => __( 'Copy', 'spelhubben-weather' ),
			'expand'     => __( 'Expand', 'spelhubben-weather' ),
			'collapse'   => __( 'Collapse', 'spelhubben-weather' ),
			'rendering'  => __( 'Rendering…', 'spelhubben-weather' ),
			'ok'         => __( 'OK', 'spelhubben-weather' ),
			'failed'     => __( 'Failed', 'spelhubben-weather' ),
			'previewErr' => __( 'Preview failed', 'spelhubben-weather' ),

			// Admin UI toggles
			'show_html'      => __( 'Show HTML', 'spelhubben-weather' ),
			'show_html_hide' => __( 'Hide HTML', 'spelhubben-weather' ),

			// Attribution checker
			'check_attrib'     => __( 'Check attribution', 'spelhubben-weather' ),
			'checking'         => __( 'Checking…', 'spelhubben-weather' ),
			/* translators: %s: URL where attribution was found */
			'attrib_found'     => __( 'Attribution found on %s', 'spelhubben-weather' ),
			'attrib_not_found' => __( 'Attribution not found on recent pages', 'spelhubben-weather' ),
			'attrib_check_error'=> __( 'Check failed', 'spelhubben-weather' ),

			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => wp_create_nonce( 'svv_preview_sc' ),

			'assets' => array(
				'css' => array(
					trailingslashit( SV_VADER_URL ) . 'assets/style.css',
					trailingslashit( SV_VADER_URL ) . 'assets/vendor/leaflet/leaflet.css',
				),
				'js'  => array(
					trailingslashit( SV_VADER_URL ) . 'assets/vendor/leaflet/leaflet.js',
					trailingslashit( SV_VADER_URL ) . 'assets/map.js',
				),
				'svv' => array(
					'iconBase' => trailingslashit( SV_VADER_URL ) . 'assets/vendor/leaflet/images/',
				),
			),
		) );
	}
	add_action( 'admin_enqueue_scripts', 'sv_vader_admin_enqueue' );
}

// Export settings handler (runs before page render to avoid header already sent)
if ( ! function_exists( 'sv_vader_handle_export_settings' ) ) {
	function sv_vader_handle_export_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'spelhubben-weather' ) );
		}

		check_admin_referer( 'svv_export_settings_action', 'svv_export_settings_nonce' );

		$options  = sv_vader_get_options();
		$json     = wp_json_encode( $options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		$filename = 'spelhubben-weather-settings-' . gmdate( 'Ymd-His' ) . '.json';

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		// Intentionally output raw JSON for download.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $json;
		exit;
	}

	add_action( 'admin_post_svv_export_settings', 'sv_vader_handle_export_settings' );

    
}

// Import settings handler
if ( ! function_exists( 'sv_vader_handle_import_settings' ) ) {
	function sv_vader_handle_import_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'spelhubben-weather' ) );
		}

		check_admin_referer( 'svv_import_settings_action', 'svv_import_settings_nonce' );

		$redirect = admin_url( 'admin.php?page=sv-vader' );

		if ( empty( $_FILES['svv_import_file'] ) || ! isset( $_FILES['svv_import_file']['tmp_name'] ) ) {
			wp_safe_redirect( add_query_arg( array( 'svv_import_status' => 'fail', 'svv_import_msg' => rawurlencode( __( 'No file uploaded.', 'spelhubben-weather' ) ) ), $redirect ) );
			exit;
		}

		$file = $_FILES['svv_import_file'];

		// Basic structure validation
		if ( ! is_array( $file ) || empty( $file['tmp_name'] ) ) {
			wp_safe_redirect( add_query_arg( array( 'svv_import_status' => 'fail', 'svv_import_msg' => rawurlencode( __( 'No file uploaded.', 'spelhubben-weather' ) ) ), $redirect ) );
			exit;
		}

		// Ensure this really was uploaded via HTTP POST
		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			wp_safe_redirect( add_query_arg( array( 'svv_import_status' => 'fail', 'svv_import_msg' => rawurlencode( __( 'Invalid upload.', 'spelhubben-weather' ) ) ), $redirect ) );
			exit;
		}

		// Sanitize and restrict file types to JSON
		$safe_name = isset( $file['name'] ) ? sanitize_file_name( $file['name'] ) : '';
		$ext = pathinfo( $safe_name, PATHINFO_EXTENSION );
		if ( $ext && ! in_array( strtolower( $ext ), array( 'json' ), true ) ) {
			wp_safe_redirect( add_query_arg( array( 'svv_import_status' => 'fail', 'svv_import_msg' => rawurlencode( __( 'Invalid file type.', 'spelhubben-weather' ) ) ), $redirect ) );
			exit;
		}

		if ( ! empty( $file['error'] ) ) {
			wp_safe_redirect( add_query_arg( array( 'svv_import_status' => 'fail', 'svv_import_msg' => rawurlencode( __( 'Upload failed.', 'spelhubben-weather' ) ) ), $redirect ) );
			exit;
		}

		$size = isset( $file['size'] ) ? intval( $file['size'] ) : 0;
		if ( $size <= 0 || $size > 262144 ) { // 256 KB limit
			wp_safe_redirect( add_query_arg( array( 'svv_import_status' => 'fail', 'svv_import_msg' => rawurlencode( __( 'Invalid file size.', 'spelhubben-weather' ) ) ), $redirect ) );
			exit;
		}

		$payload = file_get_contents( $file['tmp_name'] );
		if ( $payload === false ) {
			wp_safe_redirect( add_query_arg( array( 'svv_import_status' => 'fail', 'svv_import_msg' => rawurlencode( __( 'Could not read file.', 'spelhubben-weather' ) ) ), $redirect ) );
			exit;
		}

		$data = json_decode( $payload, true );
		if ( ! is_array( $data ) ) {
			wp_safe_redirect( add_query_arg( array( 'svv_import_status' => 'fail', 'svv_import_msg' => rawurlencode( __( 'Invalid JSON.', 'spelhubben-weather' ) ) ), $redirect ) );
			exit;
		}

		$sanitized = sv_vader_sanitize_options( $data );
		update_option( 'sv_vader_options', $sanitized );

		wp_safe_redirect( add_query_arg( array( 'svv_import_status' => 'ok' ), $redirect ) );
		exit;
	}

	add_action( 'admin_post_svv_import_settings', 'sv_vader_handle_import_settings' );
}

	// AJAX: Check if attribution HTML appears on recent site pages
	if ( ! function_exists( 'sv_vader_ajax_check_attrib' ) ) {
		function sv_vader_ajax_check_attrib() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'insufficient_permissions' );
			}

			// Verify AJAX nonce created in admin UI (SVV_ADMIN_I18N.ajax_nonce)
			if ( ! check_ajax_referer( 'svv_preview_sc', 'nonce', false ) ) {
				wp_send_json_error( array( 'message' => 'invalid_nonce' ), 403 );
			}

			// Search candidate URLs: recent posts + front page
			$candidates = array();
			$home = home_url('/');
			$candidates[] = $home;

			$query = new WP_Query( array( 'post_type' => array('post','page'), 'posts_per_page' => 30, 'post_status' => 'publish', 'no_found_rows' => true ) );
			if ( $query->have_posts() ) {
				foreach ( $query->posts as $p ) {
					$candidates[] = get_permalink( $p );
				}
			}

			$attrib_html = SV_VADER_ATTRIB_HTML;
			$attrib_text = wp_strip_all_tags( $attrib_html );

			foreach ( array_unique( $candidates ) as $url ) {
				if ( empty( $url ) ) continue;
				$res = wp_remote_get( $url, array( 'timeout' => 5, 'redirection' => 5 ) );
				if ( is_wp_error( $res ) ) continue;
				$body = wp_remote_retrieve_body( $res );
				if ( ! $body ) continue;

				// 1) Preferentially search inside any <footer>...</footer> blocks
				$found_in_footer = false;
				if ( preg_match_all( '#<footer\b[^>]*>(.*?)</footer>#is', $body, $matches ) ) {
					foreach ( $matches[1] as $footer_html ) {
						if ( strpos( $footer_html, $attrib_html ) !== false || strpos( $footer_html, $attrib_text ) !== false ) {
							wp_send_json_success( array( 'found' => true, 'url' => esc_url_raw( $url ), 'context' => 'footer' ) );
						}
					}
				}

				// 2) Fallback: search entire body
				if ( strpos( $body, $attrib_html ) !== false || strpos( $body, $attrib_text ) !== false ) {
					wp_send_json_success( array( 'found' => true, 'url' => esc_url_raw( $url ), 'context' => 'body' ) );
				}
			}

			wp_send_json_success( array( 'found' => false ) );
		}
		add_action( 'wp_ajax_svv_check_attrib', 'sv_vader_ajax_check_attrib' );
	}

/**
 * Menu and sub-pages
 */
if ( ! function_exists( 'sv_vader_register_admin_menu' ) ) {
	function sv_vader_register_admin_menu() {
		// Top menu – shows Settings page
		add_menu_page(
			__( 'Spelhubben Weather', 'spelhubben-weather' ),
			__( 'Spelhubben Weather', 'spelhubben-weather' ),
			'manage_options',
			'sv-vader', // parent slug (kept for compatibility)
			'sv_vader_render_settings_page',
			'dashicons-cloud',
			65
		);

		// Sub-page: Settings (alias – points to same callback as top level)
		add_submenu_page(
			'sv-vader',
			__( 'Settings', 'spelhubben-weather' ),
			__( 'Settings', 'spelhubben-weather' ),
			'manage_options',
			'sv-vader',
			'sv_vader_render_settings_page'
		);

		// Sub-page: Weather Alerts
		add_submenu_page(
			'sv-vader',
			__( 'Alerts', 'spelhubben-weather' ),
			__( 'Alerts', 'spelhubben-weather' ),
			'manage_options',
			'sv-vader-alerts',
			'sv_vader_render_alerts_page'
		);

		// Sub-page: Shortcodes
		add_submenu_page(
			'sv-vader',
			__( 'Shortcodes', 'spelhubben-weather' ),
			__( 'Shortcodes', 'spelhubben-weather' ),
			'manage_options',
			'sv-vader-shortcodes',
			'sv_vader_render_shortcodes_page'
		);

		// Sub-page: Performance Dashboard
		add_submenu_page(
			'sv-vader',
			__( 'Performance', 'spelhubben-weather' ),
			__( 'Performance', 'spelhubben-weather' ),
			'manage_options',
			'sv-vader-performance',
			'sv_vader_render_performance_page'
		);
	}
	add_action( 'admin_menu', 'sv_vader_register_admin_menu' );
}

/**
 * Register settings (hooked here but rendering happens in page-settings.php)
 */
if ( ! function_exists( 'sv_vader_register_settings' ) ) {
	function sv_vader_register_settings() {
		register_setting( 'sv_vader_group', 'sv_vader_options', array(
			'type'              => 'array',
			'sanitize_callback' => 'sv_vader_sanitize_options', // must handle any new fields
			'default'           => sv_vader_default_options(),
			'show_in_rest'      => false,
		) );

		// ===== Main Section (General) =====
		add_settings_section( 'sv_vader_main', __( 'Default settings', 'spelhubben-weather' ), '__return_false', 'sv_vader' );

		add_settings_field( 'default_ort', __( 'Default place', 'spelhubben-weather' ), 'sv_vader_field_default_ort', 'sv_vader', 'sv_vader_main' );
		add_settings_field( 'cache_minutes', __( 'Cache TTL (minutes)', 'spelhubben-weather' ), 'sv_vader_field_cache_minutes', 'sv_vader', 'sv_vader_main' );
		add_settings_field( 'default_show', __( 'Default fields', 'spelhubben-weather' ), 'sv_vader_field_default_show', 'sv_vader', 'sv_vader_main' );
		add_settings_field( 'default_layout', __( 'Default layout', 'spelhubben-weather' ), 'sv_vader_field_default_layout', 'sv_vader', 'sv_vader_main' );
		add_settings_field( 'map_default', __( 'Show map by default', 'spelhubben-weather' ), 'sv_vader_field_map_default', 'sv_vader', 'sv_vader_main' );
		add_settings_field( 'map_height', __( 'Map height (px)', 'spelhubben-weather' ), 'sv_vader_field_map_height', 'sv_vader', 'sv_vader_main' );
		add_settings_field( 'icon_style', __( 'Icon style', 'spelhubben-weather' ), 'sv_vader_field_icon_style', 'sv_vader', 'sv_vader_main' );
		add_settings_field( 'providers', __( 'Data providers', 'spelhubben-weather' ), 'sv_vader_field_providers', 'sv_vader', 'sv_vader_main' );
		add_settings_field( 'yr_contact', __( 'Yr contact/UA', 'spelhubben-weather' ), 'sv_vader_field_yr_contact', 'sv_vader', 'sv_vader_main' );
		// Tides
		add_settings_field( 'tides', __( 'Tides (tidvatten)', 'spelhubben-weather' ), 'sv_vader_field_tides', 'sv_vader', 'sv_vader_main' );

		// ===== Units & Format =====
		add_settings_section( 'sv_vader_units', __( 'Units & format', 'spelhubben-weather' ), '__return_false', 'sv_vader' );
		add_settings_field( 'units', __( 'Preset', 'spelhubben-weather' ), 'sv_vader_field_units', 'sv_vader', 'sv_vader_units' );
		add_settings_field( 'overrides', __( 'Overrides (optional)', 'spelhubben-weather' ), 'sv_vader_field_overrides', 'sv_vader', 'sv_vader_units' );
		add_settings_field( 'date_format', __( 'Date format (PHP)', 'spelhubben-weather' ), 'sv_vader_field_date_format', 'sv_vader', 'sv_vader_units' );
		add_settings_field( 'tide_settings', __( 'Tide provider', 'spelhubben-weather' ), 'sv_vader_field_tide_settings', 'sv_vader', 'sv_vader_units' );
		// Admin visibility control for experimental tide UI
		add_settings_field( 'tide_admin_visibility', __( 'Tide admin UI', 'spelhubben-weather' ), 'sv_vader_field_tide_admin_visibility', 'sv_vader', 'sv_vader_units' );
	}
	add_action( 'admin_init', 'sv_vader_register_settings' );
}

	// Handle reset defaults request
	if ( ! function_exists( 'sv_vader_handle_reset_defaults' ) ) {
		function sv_vader_handle_reset_defaults() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Insufficient permissions.', 'spelhubben-weather' ) );
			}

			check_admin_referer( 'svv_reset_defaults', 'svv_reset_nonce' );

			// Reset options to defaults
			update_option( 'sv_vader_options', sv_vader_default_options() );

			// Redirect back with a success flag
			$redirect = add_query_arg( array( 'svv_reset' => 'ok' ), admin_url( 'admin.php?page=sv-vader' ) );
			wp_safe_redirect( $redirect );
			exit;
		}
		add_action( 'admin_post_svv_reset_defaults', 'sv_vader_handle_reset_defaults' );
	}

/**
 * Field renderers (kept here to not mix with page markup)
 */
function sv_vader_field_default_ort() {
	$o  = sv_vader_get_options();
	$ph = __( 'e.g. Stockholm', 'spelhubben-weather' );
	printf(
		'<input type="text" name="sv_vader_options[default_ort]" value="%s" class="regular-text" placeholder="%s" />',
		esc_attr( $o['default_ort'] ?? '' ),
		esc_attr( $ph )
	);
}

function sv_vader_field_cache_minutes() {
	$o = sv_vader_get_options();
	printf(
		'<input type="number" min="1" name="sv_vader_options[cache_minutes]" value="%d" class="small-text" />',
		intval( $o['cache_minutes'] ?? 30 )
	);
	echo '<p class="description">' . esc_html__( 'How long weather data is cached (transients).', 'spelhubben-weather' ) . '</p>';
}

function sv_vader_field_default_show() {
	$o = sv_vader_get_options();
	$current = array_filter(array_map('trim', explode(',', $o['default_show'] ?? 'temp,wind,wind_dir,icon')));
	$fields = [
		'temp'     => __('Temperature', 'spelhubben-weather'),
		'wind'     => __('Wind speed', 'spelhubben-weather'),
		'wind_dir' => __('Wind direction', 'spelhubben-weather'),
		'icon'     => __('Weather icon', 'spelhubben-weather'),
	];

	foreach ($fields as $key => $label) {
		printf(
			'<label style="margin-right:15px;"><input type="checkbox" name="sv_vader_show_tmp[]" value="%s" %s onchange="document.getElementById(\'svv_default_show_hidden\').value = Array.from(document.querySelectorAll(\'input[name=\\\'sv_vader_show_tmp[]\\\']:checked\')).map(i=>i.value).join(\',\')"> %s</label>',
			esc_attr($key),
			checked( in_array( $key, $current, true ), true, false ),
			esc_html($label)
		);
	}
	printf(
		'<input type="hidden" id="svv_default_show_hidden" name="sv_vader_options[default_show]" value="%s" />',
		esc_attr(implode(',', $current))
	);
	echo '<p class="description">' . esc_html__( 'Choose which fields to show by default.', 'spelhubben-weather' ) . '</p>';
}

function sv_vader_field_default_layout() {
	$o = sv_vader_get_options();
	$layouts = array(
		'inline'   => _x( 'Inline', 'layout label', 'spelhubben-weather' ),
		'compact'  => _x( 'Compact', 'layout label', 'spelhubben-weather' ),
		'card'     => _x( 'Card', 'layout label', 'spelhubben-weather' ),
		'detailed' => _x( 'Detailed', 'layout label', 'spelhubben-weather' ),
	);
	echo '<select name="sv_vader_options[default_layout]">';
	foreach ( $layouts as $val => $label ) {
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( $val ),
			selected( $o['default_layout'] ?? 'inline', $val, false ),
			esc_html( $label )
		);
	}
	echo '</select>';
}

function sv_vader_field_map_default() {
	$o = sv_vader_get_options();
	printf(
		'<label><input type="checkbox" name="sv_vader_options[map_default]" value="1" %s/> %s</label>',
		checked( 1, intval( $o['map_default'] ?? 0 ), false ),
		esc_html__( 'Enable map as default.', 'spelhubben-weather' )
	);
}

function sv_vader_field_map_height() {
	$o = sv_vader_get_options();
	printf(
		'<input type="number" min="120" name="sv_vader_options[map_height]" value="%d" class="small-text" />',
		intval( $o['map_height'] ?? 240 )
	);
}

function sv_vader_field_icon_style() {
	$o = sv_vader_get_options();
	$styles = array(
		'classic'          => __( 'Classic', 'spelhubben-weather' ),
		'modern-flat'      => __( 'Modern Flat', 'spelhubben-weather' ),
		'modern-gradient'  => __( 'Modern Gradient', 'spelhubben-weather' ),
		'modern-2026'      => __( 'Modern 2026', 'spelhubben-weather' ),
		'modern-3d'        => __( 'Modern 3D', 'spelhubben-weather' ),
	);
	echo '<select name="sv_vader_options[icon_style]">';
	foreach ( $styles as $val => $label ) {
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( $val ),
			selected( $o['icon_style'] ?? 'classic', $val, false ),
			esc_html( $label )
		);
	}
	echo '</select>';
	echo '<p class="description">' . esc_html__( 'Choose your preferred weather icon theme.', 'spelhubben-weather' ) . '</p>';
}

function sv_vader_field_providers() {
	$o = sv_vader_get_options();
	printf(
		'<label><input type="checkbox" name="sv_vader_options[prov_openmeteo]" value="1" %s/> %s</label><br>',
		checked( 1, ! empty( $o['prov_openmeteo'] ), false ),
		esc_html__( 'Open-Meteo', 'spelhubben-weather' )
	);
	printf(
		'<label><input type="checkbox" name="sv_vader_options[prov_smhi]" value="1" %s/> %s</label><br>',
		checked( 1, ! empty( $o['prov_smhi'] ), false ),
		esc_html__( 'SMHI', 'spelhubben-weather' )
	);
	printf(
		'<label><input type="checkbox" name="sv_vader_options[prov_yr]" value="1" %s/> %s</label><br>',
		checked( 1, ! empty( $o['prov_yr'] ), false ),
		esc_html__( 'Yr (MET Norway)', 'spelhubben-weather' )
	);
	printf(
		'<label><input type="checkbox" name="sv_vader_options[prov_metno_nowcast]" value="1" %s/> %s</label><br>',
		checked( 1, ! empty( $o['prov_metno_nowcast'] ), false ),
		esc_html__( 'MET Norway Nowcast', 'spelhubben-weather' )
	);
    // NEW: FMI
	printf(
		'<label><input type="checkbox" name="sv_vader_options[prov_fmi]" value="1" %s/> %s</label><br>',
		checked( 1, ! empty( $o['prov_fmi'] ), false ),
		esc_html__( 'FMI (Finland, Open Data)', 'spelhubben-weather' )
	);
	printf(
		'<label><input type="checkbox" name="sv_vader_options[prov_openweathermap]" value="1" %s/> %s</label><br>',
		checked( 1, ! empty( $o['prov_openweathermap'] ), false ),
		esc_html__( 'Open-Weathermap', 'spelhubben-weather' )
	);
	printf(
		'<label><input type="checkbox" name="sv_vader_options[prov_weatherapi]" value="1" %s/> %s</label>',
		checked( 1, ! empty( $o['prov_weatherapi'] ), false ),
		esc_html__( 'Weatherapi.com', 'spelhubben-weather' )
	);
	echo '<p class="description" style="margin:8px 0 6px;">' . esc_html__( 'OpenWeatherMap and WeatherAPI require API keys. Keys are stored server-side and never exposed to frontend visitors.', 'spelhubben-weather' ) . '</p>';
	printf(
		'<label>%s <input type="text" name="sv_vader_options[owm_api_key]" value="%s" class="regular-text" autocomplete="off" /></label><br>',
		esc_html__( 'OpenWeatherMap API key', 'spelhubben-weather' ),
		esc_attr( $o['owm_api_key'] ?? '' )
	);
	printf(
		'<label>%s <input type="text" name="sv_vader_options[weatherapi_api_key]" value="%s" class="regular-text" autocomplete="off" /></label>',
		esc_html__( 'WeatherAPI key', 'spelhubben-weather' ),
		esc_attr( $o['weatherapi_api_key'] ?? '' )
	);
}

function sv_vader_field_yr_contact() {
	$o = sv_vader_get_options();
	printf(
		'<input type="text" name="sv_vader_options[yr_contact]" value="%s" class="regular-text" />',
		esc_attr( $o['yr_contact'] ?? '' )
	);
	echo '<p class="description">' . esc_html__( 'Recommended by MET Norway: include an email or URL in your User-Agent.', 'spelhubben-weather' ) . '</p>';
}

/** Render tides settings field (main) */
function sv_vader_field_tides() {
	$o = sv_vader_get_options();
	printf(
		'<label><input type="checkbox" name="sv_vader_options[tides_enabled]" value="1" %s/> %s</label>',
		checked( 1, ! empty( $o['tides_enabled'] ), false ),
		esc_html__( 'Enable tide data (experimental).', 'spelhubben-weather' )
	);
	echo '<p class="description">' . esc_html__( 'Tide data requires a provider or a custom API endpoint. Some providers may require an API key and/or paid plan.', 'spelhubben-weather' ) . '</p>';
}

/** Render tide provider settings (detailed) */
function sv_vader_field_tide_settings() {
	$o = sv_vader_get_options();

	// Only show detailed tide provider settings when Tide admin UI is visible
	if ( empty( $o['tides_admin_visible'] ) ) {
		echo '<p class="description">' . esc_html__( 'Tide provider settings are hidden. Enable "Tide admin UI" to configure.', 'spelhubben-weather' ) . '</p>';
		return;
	}
	$prov = $o['tide_provider'] ?? 'custom';
	?>
	<select name="sv_vader_options[tide_provider]">
		<option value="worldtides" <?php selected( $prov, 'worldtides' ); ?>><?php esc_html_e( 'WorldTides (global, often paid)', 'spelhubben-weather' ); ?></option>
		<option value="noaa" <?php selected( $prov, 'noaa' ); ?>><?php esc_html_e( 'NOAA Tides (US only)', 'spelhubben-weather' ); ?></option>
		<option value="custom" <?php selected( $prov, 'custom' ); ?>><?php esc_html_e( 'Custom endpoint', 'spelhubben-weather' ); ?></option>
	</select>
	<p class="description"><?php esc_html_e( 'Choose a tide provider or use a custom API endpoint.', 'spelhubben-weather' ); ?></p>
	<p style="margin-top:8px;">
		<label><?php esc_html_e( 'API key (if required)', 'spelhubben-weather' ); ?> <input type="text" name="sv_vader_options[tide_api_key]" value="<?php echo esc_attr( $o['tide_api_key'] ?? '' ); ?>" class="regular-text" /></label>
	</p>
	<p>
		<label><?php esc_html_e( 'Custom endpoint', 'spelhubben-weather' ); ?> <input type="text" name="sv_vader_options[tide_custom_endpoint]" value="<?php echo esc_attr( $o['tide_custom_endpoint'] ?? '' ); ?>" class="regular-text" placeholder="https://example.com/tides" /></label>
	</p>
	<p>
		<label><?php esc_html_e( 'Cache TTL (minutes)', 'spelhubben-weather' ); ?> <input type="number" min="5" name="sv_vader_options[tide_cache_minutes]" value="<?php echo intval( $o['tide_cache_minutes'] ?? 60 ); ?>" class="small-text" /></label>
	</p>
	<p class="description" style="margin-top:8px;">
		<?php echo sprintf( wp_kses_post( __('Need an API key? See <a href="%s" target="_blank" rel="noopener noreferrer">WorldTides</a> (commercial) or NOAA docs for US stations: <a href="%s" target="_blank" rel="noopener noreferrer">NOAA Tides & Currents API</a>.', 'spelhubben-weather') ), esc_url('https://www.worldtides.info'), esc_url('https://api.tidesandcurrents.noaa.gov') ); ?>
	</p>
	<?php
}

/** Render tide admin visibility checkbox */
function sv_vader_field_tide_admin_visibility() {
	$o = sv_vader_get_options();
	printf(
		'<label><input type="checkbox" name="sv_vader_options[tides_admin_visible]" value="1" %s/> %s</label>',
		checked( 1, ! empty( $o['tides_admin_visible'] ), false ),
		esc_html__( 'Show tide examples & notices in admin (for testing)', 'spelhubben-weather' )
	);
	echo '<p class="description">' . esc_html__( 'Toggle visibility of experimental tide UI elements in the admin pages. Useful to hide notices/examples while testing with selected users.', 'spelhubben-weather' ) . '</p>';
}

function sv_vader_field_units() {
	$o = sv_vader_get_options();
	$opts = array(
		'metric'     => __( 'Metric (°C, m/s, mm)', 'spelhubben-weather' ),
		'metric_kmh' => __( 'Metric (°C, km/h, mm)', 'spelhubben-weather' ),
		'metric_knt' => __( 'Metric (°C, knt, mm)', 'spelhubben-weather' ),
		'imperial'   => __( 'Imperial (°F, mph, in)', 'spelhubben-weather' ),
	);
	echo '<select name="sv_vader_options[units]">';
	foreach ( $opts as $val => $label ) {
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( $val ),
			selected( $o['units'] ?? 'metric', $val, false ),
			esc_html( $label )
		);
	}
	echo '</select>';
}

function sv_vader_field_overrides() {
	$o = sv_vader_get_options();
	printf(
		'<label>%s <input type="text" name="sv_vader_options[temp_unit]" value="%s" class="small-text" placeholder="C|F" /></label> ',
		esc_html__( 'Temp unit', 'spelhubben-weather' ),
		esc_attr( $o['temp_unit'] ?? '' )
	);
	printf(
		'<label>%s <input type="text" name="sv_vader_options[wind_unit]" value="%s" class="small-text" placeholder="ms|kmh|mph|knt" /></label> ',
		esc_html__( 'Wind unit', 'spelhubben-weather' ),
		esc_attr( $o['wind_unit'] ?? '' )
	);
	printf(
		'<label>%s <input type="text" name="sv_vader_options[precip_unit]" value="%s" class="small-text" placeholder="mm|in" /></label> ',
		esc_html__( 'Precip unit', 'spelhubben-weather' ),
		esc_attr( $o['precip_unit'] ?? '' )
	);
}

function sv_vader_field_date_format() {
	$o = sv_vader_get_options();
	printf(
		'<input type="text" name="sv_vader_options[date_format]" value="%s" class="regular-text" placeholder="D j/n" />',
		esc_attr( $o['date_format'] ?? 'D j/n' )
	);
	echo '<p class="description">' . esc_html__( 'Used for forecast day labels.', 'spelhubben-weather' ) . '</p>';
}

/**
 * Live preview of shortcode (admin-ajax)
 * - Sanitizes/validates incoming shortcode string so it only
 *   allows a single [spelhubben_weather] or [sv_vader] tag.
 */
add_action( 'wp_ajax_svv_preview_shortcode', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
	}

	check_ajax_referer( 'svv_preview_sc', 'nonce' );

	// Get from POST without referring to $_POST directly (satisfies PHPCS).
	$raw_sc = filter_input( INPUT_POST, 'sc', FILTER_UNSAFE_RAW );
	$raw_sc = is_string( $raw_sc ) ? $raw_sc : '';

	// Sanitize immediately.
	// textarea variant preserves brackets and line breaks but cleans unwanted content.
	$sc = sanitize_textarea_field( $raw_sc );
	$sc = trim( $sc );

	if ( '' === $sc ) {
		wp_send_json_error( array( 'message' => 'empty' ), 400 );
	}

	// Allow ONLY our shortcodes and no surrounding HTML/text.
	// Ex: [spelhubben_weather ...] or [sv_vader ...]
	if ( ! preg_match( '/^\s*\[(spelhubben_weather|sv_vader)\b[^\]]*\]\s*$/i', $sc ) ) {
		wp_send_json_error( array( 'message' => 'invalid' ), 400 );
	}

	// Run the shortcode. Result is encapsulated in iframe in admin.js.
	$html = do_shortcode( $sc );

	// Send back secured HTML (allow normal post-HTML).
	$html = wp_kses_post( $html );

	wp_send_json_success( array( 'html' => $html ) );
} );

/**
 * Load WP.org plugin showcase via AJAX (lazy load for performance)
 */
add_action( 'wp_ajax_svv_load_wporg_showcase', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
	}

	// Verify nonce for AJAX requests from admin UI
	if ( ! check_ajax_referer( 'svv_preview_sc', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'invalid_nonce' ), 403 );
	}

	if ( class_exists( 'SV_Vader_WPOrg_Plugins' ) ) {
		$wporg = new SV_Vader_WPOrg_Plugins();
		$html = $wporg->render();
		wp_send_json_success( wp_kses_post( $html ) );
	} else {
		wp_send_json_error( array( 'message' => 'class_not_found' ), 500 );
	}
} );
