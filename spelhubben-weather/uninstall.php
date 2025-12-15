<?php
/**
 * Uninstall cleanup (backward-compatible).
 *
 * Removes plugin options, transients, widget options, and scheduled events
 * for both the legacy “SV Väder” naming and the new “Spelhubben Weather”.
 *
 * @package spelhubben-weather
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * 1) Explicit option keys (single & network)
 *    Prefer API deletes to avoid raw SQL.
 */
$sv_vader_option_keys = array(
    // Legacy
    'sv_vader_options',
    'widget_sv_vader_widget',

    // New
    'spelhubben_weather_options',
    'widget_spelhubben_weather_widget',
);

foreach ( $sv_vader_option_keys as $sv_vader_key ) {
    delete_option( $sv_vader_key );
    delete_site_option( $sv_vader_key );
}

/**
 * 2) Known transients (if you use specific names, add them here)
 *    These are safe API calls.
 */
$sv_vader_transient_keys = array(
    'sv_vader_forecast_cache',
    'spelhubben_weather_forecast_cache',
);
foreach ( $sv_vader_transient_keys as $sv_vader_t ) {
    delete_transient( $sv_vader_t );
    delete_site_transient( $sv_vader_t );
}

/**
 * 3) Best-effort wildcard cleanup.
 *    WordPress has no native wildcard delete for options/transients.
 *    We therefore:
 *      - collect matching option names
 *      - delete them with delete_option()
 *
 *    The SELECT statements are read-only, use $wpdb->prepare(),
 *    and are limited to uninstall context. We document and
 *    phpcs-ignore the "DirectDatabaseQuery/NoCaching" warnings here.
 */
global $wpdb;

// Raw prefixes (without %). We'll escape with esc_like() and add '%'.
$sv_vader_option_prefixes = array(
    // Legacy
    'sv_vader_',
    'sv-vader-',
    // New
    'spelhubben_weather_',
    'spelhubben-weather_',
    // Transients (single-site)
    '_transient_sv_vader_',
    '_transient_timeout_sv_vader_',
    '_transient_spelhubben_weather_',
    '_transient_timeout_spelhubben_weather_',
);

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
foreach ( $sv_vader_option_prefixes as $sv_vader_raw_prefix ) {
    $sv_vader_like = $wpdb->esc_like( $sv_vader_raw_prefix ) . '%';

    // Allowed table-property interpolation ($wpdb->options) + prepared LIKE.
    $names = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
            $sv_vader_like
        )
    );

    if ( $names ) {
        foreach ( $names as $name ) {
            delete_option( $name );
        }
    }
}

// Multisite: sitemeta transients/prefixes + option-like keys
if ( is_multisite() && ! empty( $wpdb->sitemeta ) ) {
    $sv_vader_site_prefixes = array(
        '_site_transient_sv_vader_',
        '_site_transient_timeout_sv_vader_',
        '_site_transient_spelhubben_weather_',
        '_site_transient_timeout_spelhubben_weather_',
        // In case any network options followed these prefixes:
        'sv_vader_',
        'sv-vader-',
        'spelhubben_weather_',
        'spelhubben-weather_',
    );

    foreach ( $sv_vader_site_prefixes as $sv_vader_raw_prefix ) {
        $sv_vader_like = $wpdb->esc_like( $sv_vader_raw_prefix ) . '%';

        // Allowed table-property interpolation ($wpdb->sitemeta) + prepared LIKE.
        $sv_vader_meta_keys = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT meta_key FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s",
                $sv_vader_like
            )
        );

        if ( $sv_vader_meta_keys ) {
            foreach ( $sv_vader_meta_keys as $sv_vader_meta_key ) {
                // Works for *_site_transient_* and site options keys.
                delete_site_option( $sv_vader_meta_key );
            }
        }
    }
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

/**
 * 4) Clear scheduled cron events with our prefixes (best-effort)
 */
if ( function_exists( '_get_cron_array' ) ) {
    $sv_vader_prefixes = array(
        'sv_vader_',
        'sv-vader-',
        'spelhubben_weather_',
        'spelhubben-weather-',
    );

    $sv_vader_crons = _get_cron_array();
    if ( is_array( $sv_vader_crons ) ) {
        foreach ( $sv_vader_crons as $sv_vader_timestamp => $sv_vader_hooks ) {
            if ( ! is_array( $sv_vader_hooks ) ) {
                continue;
            }
            foreach ( $sv_vader_hooks as $sv_vader_hook => $sv_vader_events ) {
                foreach ( $sv_vader_prefixes as $sv_vader_pfx ) {
                    if ( strpos( $sv_vader_hook, $sv_vader_pfx ) === 0 ) {
                        while ( wp_next_scheduled( $sv_vader_hook ) ) {
                            wp_clear_scheduled_hook( $sv_vader_hook );
                        }
                        break;
                    }
                }
            }
        }
    }
}
