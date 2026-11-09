<?php
// includes/class-assets.php
if (!defined('ABSPATH')) exit;

class SV_Vader_Assets {

    private $registered = false;
    private $localized = false;
    private $footer_hooked = false;
    /** @var array<string,bool> Map libraries required on this request. */
    private $map_libs = [];

    public function __construct() {
        add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_content_assets' ] );
        add_action( 'sv_vader_render_assets', [ $this, 'enqueue_on_render' ], 10, 2 );
    }

    /**
     * Enqueue assets at render time. Covers output placed outside post content
     * (theme sidebars, block widget areas, templates) that the page-content
     * detection cannot see. Late-enqueued styles/scripts print in the footer.
     *
     * @param bool   $with_map Whether this instance renders a map.
     * @param string $engine   Requested map engine for this instance.
     */
    public function enqueue_on_render( $with_map = false, $engine = 'auto' ) {
        if ( function_exists( 'is_admin' ) && is_admin() ) {
            return;
        }

        $this->register_assets();
        wp_enqueue_style('sv-vader-style');
        wp_enqueue_script('sv-vader-wind');

        if ( $with_map ) {
            // Collect what every instance needs; the footer hook enqueues the
            // union once all instances have rendered.
            $this->request_map_engine( $this->normalize_engine( $engine ) );
            if ( ! $this->footer_hooked ) {
                $this->footer_hooked = true;
                add_action( 'wp_footer', [ $this, 'apply_map_assets' ], 5 );
            }
        }
    }

    /**
     * Enqueue public-facing assets (CSS/JS) for the frontend.
     * Only loads the core stylesheet and wind helper; map assets load at render time.
     */
    public function enqueue_public_assets() {
        $this->register_assets();

        // Load core style only when plugin output is present on the page
        if ( $this->should_load_assets() ) {
            wp_enqueue_style('sv-vader-style');
            wp_enqueue_script('sv-vader-wind');
        }

        // Map engines are NOT enqueued here. Each rendered instance knows whether
        // it shows a map and which engine it wants, so enqueue_on_render() loads
        // exactly what is needed (and nothing on pages with map="0").
        //
        // NOTE: Do NOT force "defer" on these scripts. Defer/Delay/Async is best
        // left to caching/optimization plugins, otherwise Leaflet may load after
        // the map script on some live setups.
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

        // Dependencies on the engine libraries are attached in apply_map_assets()
        // so only the engine(s) actually needed are downloaded.
        wp_register_script('sv-vader-map', SV_VADER_URL . $map_file, [], SV_VADER_VER, true);

        // Enable translations for front-end scripts (if present)
        if ( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations( 'sv-vader-map', 'spelhubben-weather', WP_LANG_DIR . '/plugins' );
        }
        // Small helper to rotate wind direction arrows when inline styles are stripped
        wp_register_script('sv-vader-wind', SV_VADER_URL . 'assets/wind.js', [], SV_VADER_VER, true);

        $this->registered = true;
    }

    /**
     * Whitelist a map engine value.
     */
    private function normalize_engine( $engine ) {
        $engine = strtolower( (string) $engine );
        return in_array( $engine, ['auto','openlayers','leaflet','static'], true ) ? $engine : 'auto';
    }

    /**
     * Record which map libraries an engine needs.
     * "auto" tries OpenLayers first and falls back to Leaflet, so it needs both;
     * "static" needs neither.
     */
    private function request_map_engine( $engine ) {
        if ( $engine === 'static' ) {
            return;
        }
        if ( $engine === 'openlayers' || $engine === 'auto' ) {
            $this->map_libs['openlayers'] = true;
        }
        if ( $engine === 'leaflet' || $engine === 'auto' ) {
            $this->map_libs['leaflet'] = true;
        }
    }

    /**
     * Enqueue the map libraries required for the given engine right away.
     */
    private function enqueue_map_assets( $engine = 'auto' ) {
        $engine = $this->normalize_engine( $engine );
        $this->request_map_engine( $engine );
        $this->apply_map_assets( $engine );
    }

    /**
     * Enqueue the union of map libraries requested so far, plus the map script.
     * Also hooked to wp_footer (priority 5) for render-time requests.
     */
    public function apply_map_assets( $engine = null ) {
        if ( empty( $this->map_libs ) ) {
            return;
        }
        $this->register_assets();

        $deps = [];
        if ( ! empty( $this->map_libs['leaflet'] ) ) {
            wp_enqueue_style('svv-leaflet-css');
            wp_enqueue_script('svv-leaflet-js');
            $deps[] = 'svv-leaflet-js';
        }
        if ( ! empty( $this->map_libs['openlayers'] ) ) {
            wp_enqueue_style('svv-openlayers-css');
            wp_enqueue_script('svv-openlayers-js');
            $deps[] = 'svv-openlayers-js';
        }

        // Attach the needed libraries as dependencies so they print before map.js.
        $scripts = function_exists( 'wp_scripts' ) ? wp_scripts() : null;
        if ( $scripts && isset( $scripts->registered['sv-vader-map'] ) ) {
            $scripts->registered['sv-vader-map']->deps = $deps;
        }
        wp_enqueue_script('sv-vader-map');

        // Localize only once, even if enqueued from several code paths.
        if ( $this->localized ) {
            return;
        }
        $this->localized = true;
        if ( $engine === null ) {
            $opts = function_exists('sv_vader_get_options') ? sv_vader_get_options() : [];
            $engine = $this->normalize_engine( $opts['map_engine'] ?? 'auto' );
        }
        wp_localize_script('sv-vader-map', 'SVV', [
            'iconBase' => trailingslashit(SV_VADER_URL . 'assets/vendor/leaflet/images'),
            'mapEngine' => $engine,
            'i18n' => sv_vader_map_translations(),
        ]);
    }

    /**
     * Does the main page content, or any post in the main query, contain
     * plugin output (shortcode, block, or legacy names)?
     */
    private function content_has_plugin_output() {
        $shortcodes = ['sv-vader', 'spelhubben_weather'];
        $blocks     = ['spelhubben-weather/spelhubben-weather', 'sv/vader'];

        $check = function ( $p ) use ( $shortcodes, $blocks ) {
            if ( ! is_object( $p ) || ! isset( $p->post_content ) ) {
                return false;
            }
            foreach ( $shortcodes as $sc ) {
                if ( has_shortcode( $p->post_content, $sc ) ) return true;
            }
            foreach ( $blocks as $b ) {
                if ( has_block( $b, $p ) ) return true;
            }
            return false;
        };

        global $post, $wp_query;
        $current = ( isset( $post ) && is_a( $post, 'WP_Post' ) ) ? $post : get_queried_object();
        if ( $check( $current ) ) {
            return true;
        }

        if ( isset( $wp_query ) && ! empty( $wp_query->posts ) && is_array( $wp_query->posts ) ) {
            foreach ( $wp_query->posts as $qpost ) {
                if ( $check( $qpost ) ) return true;
            }
        }

        return function_exists( 'is_active_widget' ) && is_active_widget( false, false, 'sv_vader_widget' );
    }

    /** Should we load any frontend assets (styles/scripts) for the plugin on this page? */
    private function should_load_assets() {
        return $this->content_has_plugin_output();
    }
}
