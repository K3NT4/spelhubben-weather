<?php
// includes/class-assets.php
if (!defined('ABSPATH')) exit;

class SV_Vader_Assets {

    private $registered = false;

    public function __construct() {
        add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_content_assets' ] );
    }

    /**
     * Enqueue public-facing assets (CSS/JS) for the frontend.
     * Only loads core stylesheet; map engine assets are loaded conditionally.
     */
    public function enqueue_public_assets() {
        $this->register_assets();

        // Load core style only when plugin output is present on the page
        if ( $this->should_load_assets() ) {
            wp_enqueue_style('sv-vader-style');
            wp_enqueue_script('sv-vader-wind');
        }

        // Load map assets only if shortcode is present or Gutenberg block is used.
        if ( $this->should_load_map() ) {
            $opts = function_exists('sv_vader_get_options') ? sv_vader_get_options() : [];
            $engine = strtolower((string)($opts['map_engine'] ?? 'auto'));
            if ( ! in_array($engine, ['auto','openlayers','leaflet','static'], true) ) {
                $engine = 'auto';
            }

            $this->enqueue_map_assets($engine);

            /**
             * NOTE:
             * Do NOT force "defer" here. Defer/Delay/Async is best left to caching/optimization plugins,
             * otherwise we risk Leaflet loading after our map script on some live setups.
             */
        }
    }

    /**
     * Enqueue block content assets inside the block editor iframe in WordPress 7.0+.
     */
    public function enqueue_block_content_assets() {
        if ( function_exists( 'is_admin' ) && ! is_admin() ) {
            return;
        }

        $this->register_assets();

        wp_enqueue_style('sv-vader-style');
        wp_enqueue_script('sv-vader-wind');

        $opts = function_exists('sv_vader_get_options') ? sv_vader_get_options() : [];
        $engine = strtolower((string)($opts['map_engine'] ?? 'auto'));
        if ( ! in_array($engine, ['auto','openlayers','leaflet','static'], true) ) {
            $engine = 'auto';
        }

        $this->enqueue_map_assets($engine);
    }

    /**
     * Register shared asset handles once so frontend and editor enqueue paths agree.
     */
    private function register_assets() {
        if ( $this->registered ) {
            return;
        }

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
        // Register Leaflet to load in the footer to improve ordering when
        // optimization plugins move or defer head scripts.
        wp_register_script('svv-leaflet-js', SV_VADER_URL . 'assets/vendor/leaflet/leaflet.js', [], '1.9.4', true);

        wp_register_style('svv-openlayers-css', SV_VADER_URL . 'assets/openlayers.css', [], SV_VADER_VER);
        wp_register_script('svv-openlayers-js', SV_VADER_URL . 'assets/openlayers.js', [], SV_VADER_VER, true);

        // Small inline flag executed immediately after Leaflet to aid debugging and
        // to allow other scripts to detect that Leaflet has been output.
        wp_add_inline_script('svv-leaflet-js', "window._svv_leaflet_registered = true;");

        // Prefer minified map script when available
        $map_file = 'assets/map.js';
        if ( ! ( defined('WP_DEBUG') && WP_DEBUG ) && file_exists( $base_dir . 'assets' . DIRECTORY_SEPARATOR . 'map.min.js' ) ) {
            $map_file = 'assets/map.min.js';
        }

        wp_register_script('sv-vader-map', SV_VADER_URL . $map_file, ['svv-openlayers-js', 'svv-leaflet-js'], SV_VADER_VER, true);

        // Enable translations for front-end scripts (if present)
        if ( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations( 'sv-vader-map', 'spelhubben-weather', SV_VADER_DIR . 'languages' );
        }
        // Small helper to rotate wind direction arrows when inline styles are stripped
        wp_register_script('sv-vader-wind', SV_VADER_URL . 'assets/wind.js', [], SV_VADER_VER, true);

        $this->registered = true;
    }

    /**
     * Enqueue all local map engines because each rendered instance can override engine.
     */
    private function enqueue_map_assets($engine = 'auto') {
        // Enqueue both local engines when a map is present. The effective
        // engine can be overridden per shortcode/block/widget instance, and
        // this enqueue phase cannot reliably know every rendered instance.
        wp_enqueue_style('svv-leaflet-css');
        wp_enqueue_script('svv-leaflet-js');
        wp_enqueue_style('svv-openlayers-css');
        wp_enqueue_script('svv-openlayers-js');
        wp_enqueue_script('sv-vader-map');

        // Localize only when map script is actually enqueued.
        wp_localize_script('sv-vader-map', 'SVV', [
            'iconBase' => trailingslashit(SV_VADER_URL . 'assets/vendor/leaflet/images'),
            'mapEngine' => $engine,
        ]);
    }

    /**
     * Check if map assets should be loaded on this page.
     */
    private function should_load_map() {
        global $post;
        // Fallback: get queried object if $post is not set or not a WP_Post
        if ( ! isset( $post ) || ! is_a( $post, 'WP_Post' ) ) {
            $post = get_queried_object();
        }

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

        // If we're on an archive/home/paged view, scan the main query posts
        global $wp_query;
        if ( isset( $wp_query ) && ! empty( $wp_query->posts ) && is_array( $wp_query->posts ) ) {
            foreach ( $wp_query->posts as $qpost ) {
                if ( isset( $qpost->post_content ) ) {
                    if ( has_shortcode( $qpost->post_content, 'sv-vader' ) || has_shortcode( $qpost->post_content, 'spelhubben_weather' ) ) {
                        return true;
                    }
                    if ( has_block( 'spelhubben-weather/spelhubben-weather', $qpost ) || has_block( 'sv/vader', $qpost ) ) {
                        return true;
                    }
                }
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
        // Fallback: get queried object if $post is not set or not en WP_Post
        if ( ! isset( $post ) || ! is_a( $post, 'WP_Post' ) ) {
            $post = get_queried_object();
        }

        // If the post contains the shortcode or block, load assets
        if ( isset( $post->post_content ) ) {
            if ( has_shortcode( $post->post_content, 'spelhubben_weather' ) ) {
                return true;
            }
            if ( has_block( 'spelhubben-weather/spelhubben-weather', $post ) ) {
                return true;
            }
        }

        // Also scan posts in the main query for archives/paged views
        global $wp_query;
        if ( isset( $wp_query ) && ! empty( $wp_query->posts ) && is_array( $wp_query->posts ) ) {
            foreach ( $wp_query->posts as $qpost ) {
                if ( isset( $qpost->post_content ) ) {
                    if ( has_shortcode( $qpost->post_content, 'spelhubben_weather' ) ) {
                        return true;
                    }
                    if ( has_block( 'spelhubben-weather/spelhubben-weather', $qpost ) ) {
                        return true;
                    }
                }
            }
        }

        // If widget is active anywhere, load assets
        if ( function_exists( 'is_active_widget' ) && is_active_widget( false, false, 'sv_vader_widget' ) ) {
            return true;
        }

        return false;
    }
}
