<?php
// includes/options.php
if (!defined('ABSPATH')) exit;

/**
 * Default plugin options (keys kept in Swedish for backward compatibility).
 */
if (!function_exists('sv_vader_default_options')) {
    function sv_vader_default_options() : array {
        return [
            'default_ort'    => 'Stockholm',   // default place (string)
            'cache_minutes'  => 10,            // transient cache TTL in minutes
            'default_show'   => 'temp,wind,icon', // visible fields: temp, wind, icon
            'default_layout' => 'card',        // inline|compact|card|detailed
            'map_default'    => 1,             // 1 to show map by default
            'map_height'     => 240,           // map height in px (min 120)

            // Data providers
            'prov_openmeteo'     => 1,         // Open-Meteo (default)
            'prov_smhi'          => 1,         // SMHI
            'prov_yr'            => 1,         // Yr (MET Norway)
            'prov_metno_nowcast' => 1,         // MET Norway Nowcast (no key)
            'yr_contact'         => 'kontakt@example.com', // contact email per MET policy
        ];
    }
}

/**
 * Get merged options (saved + defaults).
 */
if (!function_exists('sv_vader_get_options')) {
    function sv_vader_get_options() : array {
        $o = get_option('sv_vader_options', []);
        return wp_parse_args($o, sv_vader_default_options());
    }
}

/**
 * Sanitize options payload from settings form.
 */
if (!function_exists('sv_vader_sanitize_options')) {
    function sv_vader_sanitize_options($in) : array {
        $def = sv_vader_default_options();
        $out = [];

        // Place name (free text)
        $out['default_ort']    = sanitize_text_field($in['default_ort'] ?? $def['default_ort']);

        // Cache TTL (minutes, min 1)
        $out['cache_minutes']  = max(1, intval($in['cache_minutes'] ?? $def['cache_minutes']));

        // Visible fields whitelist
        $allowed_show = ['temp','wind','icon'];
        $show_in = strtolower((string)($in['default_show'] ?? $def['default_show']));
        $show_in = array_filter(array_map('trim', explode(',', $show_in)));
        $show_in = array_values(array_unique(array_intersect($show_in, $allowed_show)));
        $out['default_show'] = implode(',', $show_in ?: ['temp','wind','icon']);

        // Layout whitelist
        $allowed_layouts = ['inline','compact','card','detailed'];
        $layout_in = strtolower((string)($in['default_layout'] ?? $def['default_layout']));
        $out['default_layout'] = in_array($layout_in, $allowed_layouts, true) ? $layout_in : 'card';

        // Map toggle + height (min 120px)
        $out['map_default'] = !empty($in['map_default']) ? 1 : 0;
        $out['map_height']  = max(120, intval($in['map_height'] ?? $def['map_height']));

        // Providers toggles
        $out['prov_openmeteo']     = !empty($in['prov_openmeteo']) ? 1 : 0;
        $out['prov_smhi']          = !empty($in['prov_smhi']) ? 1 : 0;
        $out['prov_yr']            = !empty($in['prov_yr']) ? 1 : 0;
        $out['prov_metno_nowcast'] = !empty($in['prov_metno_nowcast']) ? 1 : 0;

        // Contact email for MET Norway usage policies (free text)
        $out['yr_contact'] = sanitize_text_field($in['yr_contact'] ?? $def['yr_contact']);

        return $out;
    }
}
