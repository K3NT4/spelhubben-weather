<?php
// includes/class-assets.php
if (!defined('ABSPATH')) exit;

class SV_Vader_Assets {

    /**
     * Enqueue public-facing assets (CSS/JS) for the frontend.
     */
    public function enqueue_public_assets() {
        // Core plugin stylesheet
        wp_enqueue_style('sv-vader-style', SV_VADER_URL . 'assets/style.css', [], SV_VADER_VER);

        // Leaflet (bundled locally)
        wp_register_style('leaflet-css', SV_VADER_URL . 'assets/vendor/leaflet/leaflet.css', [], '1.9.4');
        wp_enqueue_style('leaflet-css');

        wp_register_script('leaflet-js', SV_VADER_URL . 'assets/vendor/leaflet/leaflet.js', [], '1.9.4', true);
        wp_enqueue_script('leaflet-js');

        // Map logic (depends on Leaflet)
        wp_register_script('sv-vader-map', SV_VADER_URL . 'assets/map.js', ['leaflet-js'], SV_VADER_VER, true);

        // Localized data for JS
        wp_localize_script('sv-vader-map', 'SVV', [
            'iconBase' => trailingslashit(SV_VADER_URL . 'assets/vendor/leaflet/images'),
        ]);

        wp_enqueue_script('sv-vader-map');
    }
}
