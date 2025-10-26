<?php
// admin/admin.php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ladda del-sidor
 * - Görs villkorligt inne i callbacks, men vi require:ar filerna här för enkelhet.
 */
require_once __DIR__ . '/page-settings.php';
require_once __DIR__ . '/page-shortcodes.php';

/**
 * Enqueue admin assets endast på våra sidor
 */
if ( ! function_exists( 'sv_vader_admin_enqueue' ) ) {
	function sv_vader_admin_enqueue( $hook ) {
		// Ladda våra assets på alla sidor vars hook innehåller "sv-vader"
		if ( strpos( $hook, 'sv-vader' ) === false ) {
			return;
		}

		// Robust byggning av URL + versions-bust via filemtime
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

		wp_localize_script( 'sv-vader-admin', 'SVV_ADMIN_I18N', array(
			'copied'     => __( 'Copied!', 'spelhubben-weather' ),
			'copy'       => __( 'Copy', 'spelhubben-weather' ),
			'expand'     => __( 'Expand', 'spelhubben-weather' ),
			'collapse'   => __( 'Collapse', 'spelhubben-weather' ),
			'rendering'  => __( 'Rendering…', 'spelhubben-weather' ),
			'ok'         => __( 'OK', 'spelhubben-weather' ),
			'failed'     => __( 'Failed', 'spelhubben-weather' ),
			'previewErr' => __( 'Preview failed', 'spelhubben-weather' ),

			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => wp_create_nonce( 'svv_preview_sc' ),

			'assets' => array(
				'css' => array(
					trailingslashit( SV_VADER_URL ) . 'assets/style.css',
					trailingslashit( SV_VADER_URL ) . 'assets/leaflet/leaflet.css',
				),
				'js'  => array(
					trailingslashit( SV_VADER_URL ) . 'assets/leaflet/leaflet.js',
					trailingslashit( SV_VADER_URL ) . 'assets/widget.js',
					trailingslashit( SV_VADER_URL ) . 'assets/map.js',
				),
				'svv' => array(
					'iconBase' => trailingslashit( SV_VADER_URL ) . 'assets/leaflet/images/',
				),
			),
		) );
	}
	add_action( 'admin_enqueue_scripts', 'sv_vader_admin_enqueue' );
}

/**
 * Meny och undersidor
 */
if ( ! function_exists( 'sv_vader_register_admin_menu' ) ) {
	function sv_vader_register_admin_menu() {
		// Toppmeny – visar Inställningar-sidan
		add_menu_page(
			__( 'Spelhubben Weather', 'spelhubben-weather' ),
			__( 'Spelhubben Weather', 'spelhubben-weather' ),
			'manage_options',
			'sv-vader', // parent slug (behålls för kompabilitet)
			'sv_vader_render_settings_page',
			'dashicons-cloud',
			65
		);

		// Undersida: Inställningar (alias – pekar på samma callback som toppnivån)
		add_submenu_page(
			'sv-vader',
			__( 'Settings', 'spelhubben-weather' ),
			__( 'Settings', 'spelhubben-weather' ),
			'manage_options',
			'sv-vader',
			'sv_vader_render_settings_page'
		);

		// Undersida: Kortkoder
		add_submenu_page(
			'sv-vader',
			__( 'Shortcodes', 'spelhubben-weather' ),
			__( 'Shortcodes', 'spelhubben-weather' ),
			'manage_options',
			'sv-vader-shortcodes',
			'sv_vader_render_shortcodes_page'
		);
	}
	add_action( 'admin_menu', 'sv_vader_register_admin_menu' );
}

/**
 * Registrera inställningar (hookas här men själva rendering sker i page-settings.php)
 */
if ( ! function_exists( 'sv_vader_register_settings' ) ) {
	function sv_vader_register_settings() {
		register_setting( 'sv_vader_group', 'sv_vader_options', array(
			'type'              => 'array',
			'sanitize_callback' => 'sv_vader_sanitize_options', // måste hantera ev. nya fält
			'default'           => sv_vader_default_options(),
			'show_in_rest'      => false,
		) );

		// ===== Huvudsektion (General) =====
		add_settings_section( 'sv_vader_main', __( 'Default settings', 'spelhubben-weather' ), '__return_false', 'sv_vader' );

		add_settings_field( 'default_ort', __( 'Default place', 'spelhubben-weather' ), 'sv_vader_field_default_ort', 'sv_vader', 'sv_vader_main' );
		add_settings_field( 'cache_minutes', __( 'Cache TTL (minutes)', 'spelhubben-weather' ), 'sv_vader_field_cache_minutes', 'sv_vader', 'sv_vader_main' );
		add_settings_field( 'default_show', __( 'Default fields', 'spelhubben-weather' ), 'sv_vader_field_default_show', 'sv_vader', 'sv_vader_main' );
		add_settings_field( 'default_layout', __( 'Default layout', 'spelhubben-weather' ), 'sv_vader_field_default_layout', 'sv_vader', 'sv_vader_main' );
		add_settings_field( 'map_default', __( 'Show map by default', 'spelhubben-weather' ), 'sv_vader_field_map_default', 'sv_vader', 'sv_vader_main' );
		add_settings_field( 'map_height', __( 'Map height (px)', 'spelhubben-weather' ), 'sv_vader_field_map_height', 'sv_vader', 'sv_vader_main' );
		add_settings_field( 'providers', __( 'Data providers', 'spelhubben-weather' ), 'sv_vader_field_providers', 'sv_vader', 'sv_vader_main' );
		add_settings_field( 'yr_contact', __( 'Yr contact/UA', 'spelhubben-weather' ), 'sv_vader_field_yr_contact', 'sv_vader', 'sv_vader_main' );

		// ===== Units & Format =====
		add_settings_section( 'sv_vader_units', __( 'Units & format', 'spelhubben-weather' ), '__return_false', 'sv_vader' );
		add_settings_field( 'units', __( 'Preset', 'spelhubben-weather' ), 'sv_vader_field_units', 'sv_vader', 'sv_vader_units' );
		add_settings_field( 'overrides', __( 'Overrides (optional)', 'spelhubben-weather' ), 'sv_vader_field_overrides', 'sv_vader', 'sv_vader_units' );
		add_settings_field( 'date_format', __( 'Date format (PHP)', 'spelhubben-weather' ), 'sv_vader_field_date_format', 'sv_vader', 'sv_vader_units' );
	}
	add_action( 'admin_init', 'sv_vader_register_settings' );
}

/**
 * Fält-renderers (hålls här för att inte blanda med sidornas markup)
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
	printf(
		'<input type="text" name="sv_vader_options[default_show]' . '" value="%s" class="regular-text" />',
		esc_attr( $o['default_show'] ?? 'temp,wind,icon' )
	);
	echo '<p class="description">' . esc_html__( 'Comma-separated: temp,wind,icon', 'spelhubben-weather' ) . '</p>';
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
		'<label><input type="checkbox" name="sv_vader_options[prov_fmi]" value="1" %s/> %s</label>',
		checked( 1, ! empty( $o['prov_fmi'] ), false ),
		esc_html__( 'FMI (Finland, Open Data)', 'spelhubben-weather' )
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

function sv_vader_field_units() {
	$o = sv_vader_get_options();
	$opts = array(
		'metric'     => __( 'Metric (°C, m/s, mm)', 'spelhubben-weather' ),
		'metric_kmh' => __( 'Metric (°C, km/h, mm)', 'spelhubben-weather' ),
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
		'<label>%s <input type="text" name="sv_vader_options[wind_unit]" value="%s" class="small-text" placeholder="ms|kmh|mph" /></label> ',
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
 * Live preview av shortcode (admin-ajax)
 * - Sanerar/validerar inkommande shortcode-sträng så den endast
 *   får vara en enda [spelhubben_weather] eller [sv_vader]-tagg.
 */
add_action( 'wp_ajax_svv_preview_shortcode', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
	}

	check_ajax_referer( 'svv_preview_sc', 'nonce' );

	// Hämta från POST utan att referera $_POST direkt (tillfredsställer PHPCS).
	$raw_sc = filter_input( INPUT_POST, 'sc', FILTER_UNSAFE_RAW );
	$raw_sc = is_string( $raw_sc ) ? $raw_sc : '';

	// Sanera omedelbart.
	// textarea-varianten bevarar hakparenteser och radbrytningar men rensar oönskat.
	$sc = sanitize_textarea_field( $raw_sc );
	$sc = trim( $sc );

	if ( '' === $sc ) {
		wp_send_json_error( array( 'message' => 'empty' ), 400 );
	}

	// Tillåt ENDAST våra shortcodes och ingen omgivande HTML/text.
	// Ex: [spelhubben_weather ...] eller [sv_vader ...]
	if ( ! preg_match( '/^\s*\[(spelhubben_weather|sv_vader)\b[^\]]*\]\s*$/i', $sc ) ) {
		wp_send_json_error( array( 'message' => 'invalid' ), 400 );
	}

	// Kör shortcoden. Resultatet kapslas i iframe i admin.js.
	$html = do_shortcode( $sc );

	// Sänd tillbaka säkrad HTML (tillåt vanlig post-HTML).
	$html = wp_kses_post( $html );

	wp_send_json_success( array( 'html' => $html ) );
} );
