<?php
/**
 * Plugin Configuration Constants
 * 
 * Central location for all magic numbers and configuration values.
 * Updated in v1.8.3 for improved maintainability.
 * 
 * @package Spelhubben_Weather
 * @since 1.8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── API Timeouts (seconds)
if ( ! defined( 'SV_VADER_API_TIMEOUT_DEFAULT' ) ) {
	define( 'SV_VADER_API_TIMEOUT_DEFAULT', 10 );  // Open-Meteo, Geocoding
}
if ( ! defined( 'SV_VADER_API_TIMEOUT_SMHI' ) ) {
	define( 'SV_VADER_API_TIMEOUT_SMHI', 12 );     // SMHI API
}
if ( ! defined( 'SV_VADER_API_TIMEOUT_YR' ) ) {
	define( 'SV_VADER_API_TIMEOUT_YR', 12 );       // MET Norway API
}
if ( ! defined( 'SV_VADER_API_TIMEOUT_FMI' ) ) {
	define( 'SV_VADER_API_TIMEOUT_FMI', 12 );      // FMI API
}
if ( ! defined( 'SV_VADER_API_TIMEOUT_OWM' ) ) {
	define( 'SV_VADER_API_TIMEOUT_OWM', 10 );      // OpenWeatherMap API
}
if ( ! defined( 'SV_VADER_API_TIMEOUT_WEATHERAPI' ) ) {
	define( 'SV_VADER_API_TIMEOUT_WEATHERAPI', 10 );  // WeatherAPI.com
}
if ( ! defined( 'SV_VADER_API_TIMEOUT_WPORG' ) ) {
	define( 'SV_VADER_API_TIMEOUT_WPORG', 8 );     // WordPress.org plugin API
}

// ── Cache Values (minutes)
if ( ! defined( 'SV_VADER_CACHE_MINUTES_DEFAULT' ) ) {
	define( 'SV_VADER_CACHE_MINUTES_DEFAULT', 10 );  // Weather data (user configurable)
}
if ( ! defined( 'SV_VADER_CACHE_MINUTES_GEOCODE' ) ) {
	define( 'SV_VADER_CACHE_MINUTES_GEOCODE', 7 * 24 * 60 );  // Geocoding: 7 days
}
if ( ! defined( 'SV_VADER_CACHE_MINUTES_PLUGINS' ) ) {
	define( 'SV_VADER_CACHE_MINUTES_PLUGINS', 24 * 60 );  // WP.org plugins: 24 hours
}

// ── Map Configuration
if ( ! defined( 'SV_VADER_MAP_HEIGHT_DEFAULT' ) ) {
	define( 'SV_VADER_MAP_HEIGHT_DEFAULT', 240 );  // pixels
}
if ( ! defined( 'SV_VADER_MAP_HEIGHT_MIN' ) ) {
	define( 'SV_VADER_MAP_HEIGHT_MIN', 150 );      // pixels
}
if ( ! defined( 'SV_VADER_MAP_HEIGHT_MAX' ) ) {
	define( 'SV_VADER_MAP_HEIGHT_MAX', 600 );      // pixels
}

// ── Frontend Display Defaults
if ( ! defined( 'SV_VADER_FORECAST_DAYS_DEFAULT' ) ) {
	define( 'SV_VADER_FORECAST_DAYS_DEFAULT', 5 );
}
if ( ! defined( 'SV_VADER_FORECAST_DAYS_MAX' ) ) {
	define( 'SV_VADER_FORECAST_DAYS_MAX', 10 );
}

// ── Plugin Showcase Configuration
if ( ! defined( 'SV_VADER_PLUGINS_LIMIT' ) ) {
	define( 'SV_VADER_PLUGINS_LIMIT', 12 );  // Number of plugins to fetch from WP.org
}
if ( ! defined( 'SV_VADER_PLUGINS_DISPLAY_LIMIT' ) ) {
	define( 'SV_VADER_PLUGINS_DISPLAY_LIMIT', 6 );  // Number to display initially
}

// ── Weather Data Consensus
if ( ! defined( 'SV_VADER_CONSENSUS_MIN_SOURCES' ) ) {
	define( 'SV_VADER_CONSENSUS_MIN_SOURCES', 1 );  // Minimum providers needed for consensus
}

// ── Admin JavaScript Debounce (milliseconds)
if ( ! defined( 'SV_VADER_DEBOUNCE_LIVE_PREVIEW' ) ) {
	define( 'SV_VADER_DEBOUNCE_LIVE_PREVIEW', 600 );  // 600ms for shortcode preview
}

// ── AJAX Response Codes
if ( ! defined( 'SV_VADER_HTTP_OK' ) ) {
	define( 'SV_VADER_HTTP_OK', 200 );
}
if ( ! defined( 'SV_VADER_HTTP_FORBIDDEN' ) ) {
	define( 'SV_VADER_HTTP_FORBIDDEN', 403 );
}
if ( ! defined( 'SV_VADER_HTTP_ERROR' ) ) {
	define( 'SV_VADER_HTTP_ERROR', 500 );
}

// ── Geocoding API Configuration
if ( ! defined( 'SV_VADER_GEOCODE_API_URL' ) ) {
	define( 'SV_VADER_GEOCODE_API_URL', 'https://geocoding-api.open-meteo.com/v1/search' );
}
if ( ! defined( 'SV_VADER_GEOCODE_LIMIT' ) ) {
	define( 'SV_VADER_GEOCODE_LIMIT', 1 );  // Results per query
}

// ── Icon Configuration
if ( ! defined( 'SV_VADER_ICON_STYLES' ) ) {
	define( 'SV_VADER_ICON_STYLES', 'classic,modern-flat,modern-gradient' );
}

// ── Wind Unit Conversions
if ( ! defined( 'SV_VADER_WIND_KMPH_FACTOR' ) ) {
	define( 'SV_VADER_WIND_KMPH_FACTOR', 3.6 );  // m/s to km/h
}
if ( ! defined( 'SV_VADER_WIND_MPH_FACTOR' ) ) {
	define( 'SV_VADER_WIND_MPH_FACTOR', 2.237 );  // m/s to mph
}

// ── Transient Key Versioning
if ( ! defined( 'SV_VADER_TRANSIENT_VERSION' ) ) {
	define( 'SV_VADER_TRANSIENT_VERSION', 'v2' );
}
if ( ! defined( 'SV_VADER_PLUGINS_TRANSIENT_KEY' ) ) {
	define( 'SV_VADER_PLUGINS_TRANSIENT_KEY', 'spelhubben_plugins_list_' . SV_VADER_TRANSIENT_VERSION );
}

// ── Default Location
if ( ! defined( 'SV_VADER_DEFAULT_LOCATION' ) ) {
	define( 'SV_VADER_DEFAULT_LOCATION', 'Stockholm' );
}

// ── Plugin Metadata
if ( ! defined( 'SV_VADER_AUTHOR' ) ) {
	define( 'SV_VADER_AUTHOR', 'Spelhubben' );
}
if ( ! defined( 'SV_VADER_PLUGIN_NAME' ) ) {
	define( 'SV_VADER_PLUGIN_NAME', 'Spelhubben Weather' );
}
