<?php
// includes/i18n.php
if (!defined('ABSPATH')) exit;

/**
 * Returns a two-letter API language code based on the current WP locale.
 * Examples: sv_SE → sv, nb_NO → nb, da_DK → da, fi_FI → fi, is_IS → is, otherwise en.
 *
 * Filter: 'sv_vader_api_lang' to force a specific language.
 */
if (!function_exists('sv_vader_api_lang')) {
    function sv_vader_api_lang(): string {
        // Follow the correct context (admin/frontend)
        $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
        $lang   = strtolower(substr((string) $locale, 0, 2));

        $supported = array('sv','nb','nn','da','fi','is','en');
        if (!in_array($lang, $supported, true)) {
            $lang = 'en';
        }

        /**
         * Allow forcing an API language.
         *
         * @param string $lang Two-letter language code passed to external APIs.
         */
        return (string) apply_filters('sv_vader_api_lang', $lang);
    }
}

/**
 * Localized text for WMO codes.
 * Uses __() so strings are extracted into the .pot and can be translated.
 *
 * Filter: 'sv_vader_wmo_text' to override the label per code.
 */
if (!function_exists('sv_vader_wmo_text')) {
    /**
     * @param int|string $code WMO weather code
     * @return string          Localized description
     */
    function sv_vader_wmo_text($code): string {
        $c = (int) $code;

        // Common WMO codes → translatable labels
        $map = [
            0  => __('Clear', 'spelhubben-weather'),
            1  => __('Mostly clear', 'spelhubben-weather'),
            2  => __('Partly cloudy', 'spelhubben-weather'),
            3  => __('Overcast', 'spelhubben-weather'),
            45 => __('Fog', 'spelhubben-weather'),
            48 => __('Freezing fog', 'spelhubben-weather'),
            51 => __('Light drizzle', 'spelhubben-weather'),
            53 => __('Moderate drizzle', 'spelhubben-weather'),
            55 => __('Dense drizzle', 'spelhubben-weather'),
            61 => __('Light rain', 'spelhubben-weather'),
            63 => __('Moderate rain', 'spelhubben-weather'),
            65 => __('Heavy rain', 'spelhubben-weather'),
            66 => __('Light freezing rain', 'spelhubben-weather'),
            67 => __('Heavy freezing rain', 'spelhubben-weather'),
            71 => __('Light snowfall', 'spelhubben-weather'),
            73 => __('Moderate snowfall', 'spelhubben-weather'),
            75 => __('Heavy snowfall', 'spelhubben-weather'),
            77 => __('Snow grains', 'spelhubben-weather'),
            80 => __('Light rain showers', 'spelhubben-weather'),
            81 => __('Moderate rain showers', 'spelhubben-weather'),
            82 => __('Violent rain showers', 'spelhubben-weather'),
            85 => __('Light snow showers', 'spelhubben-weather'),
            86 => __('Heavy snow showers', 'spelhubben-weather'),
            95 => __('Thunderstorm', 'spelhubben-weather'),
            96 => __('Thunderstorm (slight hail)', 'spelhubben-weather'),
            99 => __('Thunderstorm (heavy hail)', 'spelhubben-weather'),
        ];

        $text = $map[$c] ?? '';

        /**
         * Allow overriding the text per WMO code.
         *
         * @param string $text Localized description (may be empty if code unknown).
         * @param int    $c    WMO code (cast to int).
         */
        return (string) apply_filters('sv_vader_wmo_text', $text, $c);
    }
}
