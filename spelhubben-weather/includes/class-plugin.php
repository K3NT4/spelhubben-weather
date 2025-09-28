<?php
// includes/class-plugin.php
if (!defined('ABSPATH')) exit;

class SV_Vader_Plugin {

    private $assets;
    public  $renderer;
    private $block;

    public function __construct() {
        $this->assets   = new SV_Vader_Assets();
        $this->renderer = new SV_Vader_Renderer();
        $this->block    = new SV_Vader_Block($this->renderer);

        add_action('init',               [$this, 'register_shortcodes']);
        add_action('init',               [$this->block, 'register_block']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets_wrapper']);

        if (is_admin()) {
            if (function_exists('sv_vader_register_admin_menu')) {
                add_action('admin_menu', 'sv_vader_register_admin_menu');
            }
            if (function_exists('sv_vader_register_settings')) {
                add_action('admin_init', 'sv_vader_register_settings');
            }
        }

        add_action('widgets_init', [$this, 'register_widget']);

        // Update the plugin basename to the new main file
        add_filter('plugin_action_links_' . plugin_basename(dirname(__DIR__) . '/spelhubben-weather.php'), [$this, 'plugin_action_links']);
    }

    /**
     * Register legacy shortcode and the new alias.
     */
    public function register_shortcodes() {
        // Legacy shortcode (kept for compatibility)
        add_shortcode('sv_vader', [$this, 'render_shortcode_proxy']);

        // New shortcode alias with attribute adapter (English -> legacy Swedish keys)
        add_shortcode('spelhubben_weather', [$this, 'render_shortcode_alias']);
    }

    /**
     * Proxy to renderer method for legacy shortcode.
     */
    public function render_shortcode_proxy($atts = [], $content = null, $tag = '') {
        return $this->renderer->render_shortcode($atts, $content, $tag);
    }

    /**
     * New shortcode alias handler: maps English attributes to legacy keys,
     * then forwards to the same renderer.
     *
     * Supported (English) attributes:
     *  place, lat, lon, show, layout, class, map, map_height, providers,
     *  animate, forecast, days
     */
    public function render_shortcode_alias($atts = [], $content = null, $tag = 'spelhubben_weather') {
        $norm = [];
        foreach ((array) $atts as $k => $v) {
            $norm[strtolower($k)] = $v;
        }

        // Map to legacy keys expected by render_shortcode()
        $legacy = [];

        // place -> ort (fallback if ort not explicitly set)
        if (isset($norm['ort'])) {
            $legacy['ort'] = $norm['ort'];
        } elseif (isset($norm['place'])) {
            $legacy['ort'] = $norm['place'];
        }

        // Pass-through of same-name attributes
        foreach (['lat','lon','show','layout','class','providers','forecast','days','map_height'] as $k) {
            if (isset($norm[$k])) {
                $legacy[$k] = $norm[$k];
            }
        }

        // Booleans that should be "1"/"0"
        foreach (['map','animate'] as $k) {
            if (isset($norm[$k])) {
                $val = $norm[$k];
                $truthy = in_array(strtolower((string)$val), ['1','true','yes','y','on'], true);
                $legacy[$k] = $truthy ? '1' : '0';
            }
        }

        // Defaults: let renderer fill from options if missing
        return $this->renderer->render_shortcode($legacy, $content, $tag);
    }

    /**
     * Enqueue public assets via the existing assets class.
     */
    public function enqueue_public_assets_wrapper() {
        if (method_exists($this->assets, 'enqueue_public_assets')) {
            $this->assets->enqueue_public_assets();
        }
    }

    public function register_widget() {
        if (class_exists('SV_Vader_Widget')) {
            register_widget('SV_Vader_Widget');
        }
    }

    public function plugin_action_links($links) {
        $url = admin_url('admin.php?page=sv-vader'); // keeping existing slug
        $links[] = '<a href="' . esc_url($url) . '">' . esc_html__('Settings', 'spelhubben-weather') . '</a>';
        return $links;
    }
}
