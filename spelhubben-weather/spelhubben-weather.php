<?php
/**
 * Plugin Name: Spelhubben Weather
 * Description: Displays current weather and an optional forecast with a simple consensus across providers (Open-Meteo, SMHI, Yr/MET Norway). Supports shortcode + Gutenberg block + classic widget. Optional Leaflet map, subtle animations, daily forecast, and multiple layouts.
 * Version: 1.7.0
 * Author: Spelhubben
 * Text Domain: spelhubben-weather
 * Domain Path: /languages
 * Requires at least: 6.8
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Constants (kept for backward compatibility).
if ( ! defined( 'SV_VADER_VER' ) ) {
	define( 'SV_VADER_VER', '1.7.0' );
}
if ( ! defined( 'SV_VADER_DIR' ) ) {
	define( 'SV_VADER_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'SV_VADER_URL' ) ) {
	define( 'SV_VADER_URL', plugin_dir_url( __FILE__ ) );
}

// Locked attribution – required by ODbL (do not translate the license text/link).
if ( ! defined( 'SV_VADER_ATTRIB_HTML' ) ) {
	define(
		'SV_VADER_ATTRIB_HTML',
		'© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'
	);
}

// ── PSR-0 style autoloader for SV_Vader_* classes under includes/.
spl_autoload_register(
	function ( $class ) {
		if ( strpos( $class, 'SV_Vader_' ) !== 0 ) return;

		$slug = strtolower( str_replace( 'SV_Vader_', '', $class ) );
		$slug = str_replace( '_', '-', $slug );

		$paths = array(
			SV_VADER_DIR . 'includes/class-' . $slug . '.php',
			SV_VADER_DIR . 'includes/Widget/class-' . $slug . '.php',
		);

		foreach ( $paths as $file ) {
			if ( file_exists( $file ) ) { require_once $file; return; }
		}
	}
);

// ── Base includes (non-autoloaded files).
require_once SV_VADER_DIR . 'includes/i18n.php';           // Language helpers.
require_once SV_VADER_DIR . 'includes/options.php';
require_once SV_VADER_DIR . 'includes/format.php';         // NEW: Units & formatting helpers
require_once SV_VADER_DIR . 'includes/class-sv-vader.php'; // API/service layer.

if ( is_admin() ) {
	$admin = SV_VADER_DIR . 'admin/admin.php';
	if ( file_exists( $admin ) ) {
		require_once $admin;
	}
}

// ── Widget (namespaced or classic).
$widget_file = SV_VADER_DIR . 'includes/Widget/class-widget.php';
if ( file_exists( $widget_file ) ) {
	require_once $widget_file;
}

// ── Register widget (namespaced variant preferred).
add_action('widgets_init', static function () {
	if ( class_exists('\SV_Vader\Widget\Widget') ) {
		register_widget('\SV_Vader\Widget\Widget');
	} elseif ( class_exists('SV_Vader_Widget') ) {
		register_widget('SV_Vader_Widget');
	}
});

// ── Bootstrap plugin (main plugin class lives in includes/class-plugin.php).
add_action('plugins_loaded', static function () {
	new SV_Vader_Plugin();
});
