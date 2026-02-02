<?php
// includes/class-assets.php
if (!defined('ABSPATH')) exit;

class SV_Vader_Assets {

    /**
     * Enqueue public-facing assets (CSS/JS) for the frontend.
     * Only loads core stylesheet; Leaflet/map assets are loaded conditionally.
     */
    public function enqueue_public_assets() {

        // Core plugin stylesheet - register, enqueue only when used on the page
        // Prefer minified file when present and WP_DEBUG is not enabled
        $style_file = 'assets/style.css';
        $base_dir = defined('SV_VADER_PATH')
            ? rtrim(SV_VADER_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            : dirname(__DIR__) . DIRECTORY_SEPARATOR;

        if ( ! ( defined('WP_DEBUG') && WP_DEBUG ) ) {
            if ( file_exists( $base_dir . 'assets' . DIRECTORY_SEPARATOR . 'style.min.css' ) ) {
                $style_file = 'assets/style.min.css';
            }
        }

        wp_register_style('sv-vader-style', SV_VADER_URL . $style_file, [], SV_VADER_VER);

        /**
         * IMPORTANT:
         * Use unique handles for Leaflet to avoid collisions with themes/other plugins.
         * Live sites often already register "leaflet-js" which can break our dependency chain.
         */
        wp_register_style('svv-leaflet-css', SV_VADER_URL . 'assets/vendor/leaflet/leaflet.css', [], '1.9.4');
        wp_register_script('svv-leaflet-js', SV_VADER_URL . 'assets/vendor/leaflet/leaflet.js', [], '1.9.4', true);

        // Prefer minified map script when available
        $map_file = 'assets/map.js';
        if ( ! ( defined('WP_DEBUG') && WP_DEBUG ) && file_exists( $base_dir . 'assets' . DIRECTORY_SEPARATOR . 'map.min.js' ) ) {
            $map_file = 'assets/map.min.js';
        }

        // Map depends on *our* Leaflet handle (svv-leaflet-js)
        wp_register_script('sv-vader-map', SV_VADER_URL . $map_file, ['svv-leaflet-js'], SV_VADER_VER, true);

        // Small helper to rotate wind direction arrows when inline styles are stripped
        wp_register_script('sv-vader-wind', SV_VADER_URL . 'assets/wind.js', [], SV_VADER_VER, true);

        // Load core style only when plugin output is present on the page
        if ( $this->should_load_assets() ) {
            wp_enqueue_style('sv-vader-style');
            wp_enqueue_script('sv-vader-wind');
        }

        // Load Leaflet assets only if shortcode is present or Gutenberg block is used
        if ( $this->should_load_leaflet() ) {
            wp_enqueue_style('svv-leaflet-css');
            wp_enqueue_script('svv-leaflet-js');
            wp_enqueue_script('sv-vader-map');

            // Localize only when map script is actually enqueued
            wp_localize_script('sv-vader-map', 'SVV', [
                'iconBase' => trailingslashit(SV_VADER_URL . 'assets/vendor/leaflet/images'),
            ]);

            /**
             * NOTE:
             * Do NOT force "defer" here. Defer/Delay/Async is best left to caching/optimization plugins,
             * otherwise we risk Leaflet loading after our map script on some live setups.
             */
        }
    }

    /**
     * Check if Leaflet assets should be loaded on this page.
     */
    private function should_load_leaflet() {
        global $post;

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

    /**
     * Should we load any frontend assets (styles/scripts) for the plugin on this page?
     */
    private function should_load_assets() {
        global $post;

        // If the post contains the shortcode or block, load assets
        if ( isset( $post->post_content ) ) {
            if ( has_shortcode( $post->post_content, 'spelhubben_weather' ) ) {
                return true;
            }
            if ( has_block( 'spelhubben-weather/spelhubben-weather', $post ) ) {
                return true;
            }
        }

        // If widget is active anywhere, load assets
        if ( function_exists( 'is_active_widget' ) && is_active_widget( false, false, 'sv_vader_widget' ) ) {
            return true;
        }

        return false;
    }
}
