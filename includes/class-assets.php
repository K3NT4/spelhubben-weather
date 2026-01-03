<?php
// includes/class-assets.php
if (!defined('ABSPATH')) exit;

class SV_Vader_Assets {

    /**
     * Enqueue public-facing assets (CSS/JS) for the frontend.
     * Only loads core stylesheet; Leaflet/map assets are loaded conditionally via filters.
     */
    public function enqueue_public_assets() {
        // Core plugin stylesheet - always load
        wp_enqueue_style('sv-vader-style', SV_VADER_URL . 'assets/style.css', [], SV_VADER_VER);

        // Register Leaflet and map assets but don't auto-enqueue
        // They will be enqueued conditionally via has_shortcode() or block detection
        wp_register_style('leaflet-css', SV_VADER_URL . 'assets/vendor/leaflet/leaflet.css', [], '1.9.4');
        wp_register_script('leaflet-js', SV_VADER_URL . 'assets/vendor/leaflet/leaflet.js', [], '1.9.4', true);
        wp_register_script('sv-vader-map', SV_VADER_URL . 'assets/map.js', ['leaflet-js'], SV_VADER_VER, true);

        // Localized data for JS
        wp_localize_script('sv-vader-map', 'SVV', [
            'iconBase' => trailingslashit(SV_VADER_URL . 'assets/vendor/leaflet/images'),
        ]);

        // Load Leaflet assets only if shortcode is present or Gutenberg block is used
        if ( $this->should_load_leaflet() ) {
            wp_enqueue_style('leaflet-css');
            wp_enqueue_script('leaflet-js');
            wp_enqueue_script('sv-vader-map');
        }
    }

    /**
     * Check if Leaflet assets should be loaded on this page.
     */
    private function should_load_leaflet() {
        global $post, $wp_registered_sidebars;

        // Check for shortcodes in post content
        if ( isset( $post->post_content ) ) {
            // Check for old shortcode (legacy)
            if ( has_shortcode( $post->post_content, 'sv-vader' ) ) {
                return true;
            }
            // Check for new shortcode
            if ( has_shortcode( $post->post_content, 'spelhubben_weather' ) ) {
                return true;
            }
            // Check for Gutenberg blocks
            if ( has_block( 'spelhubben-weather/spelhubben-weather', $post ) ) {
                return true;
            }
            if ( has_block( 'sv/vader', $post ) ) {
                return true;
            }
        }

        // Check if the sv_vader_widget is active in any sidebar
        if ( function_exists( 'is_active_widget' ) ) {
            if ( is_active_widget( false, false, 'sv_vader_widget' ) ) {
                return true;
            }
        }

        return false;
    }
}
