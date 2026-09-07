<?php
// includes/i18n.php
if (!defined('ABSPATH')) exit;

/**
 * Fill gaps in installed language packs with the bundled translations.
 * WordPress selects one directory for just-in-time loading, so registering
 * /languages alone does not supplement an incomplete WordPress.org pack.
 */
function sv_vader_load_bundled_translations($locale = null): void {
    $locale = $locale ?? determine_locale();
    // Accept WordPress locales (including variants), never filesystem paths.
    if (!is_string($locale) || !preg_match('/^[a-zA-Z0-9_@-]+$/D', $locale)) {
        return;
    }

    $domain = 'spelhubben-weather';
    $base = SV_VADER_DIR . 'languages/' . $domain . '-' . $locale;
    if (!is_readable($base . '.mo') && !is_readable($base . '.l10n.php')) {
        return;
    }
    // Load the site's selected translation first to preserve its precedence.
    get_translations_for_domain($domain);
    load_textdomain($domain, $base . '.mo', $locale);
}

/** Fall back to bundled JSON only when WordPress finds no installed file. */
function sv_vader_script_translation_file($file, $handle, $domain) {
    if ($domain !== 'spelhubben-weather' || !$file || is_readable($file)) {
        return $file;
    }
    $bundled = SV_VADER_DIR . 'languages/' . basename($file);
    return is_readable($bundled) ? $bundled : $file;
}

/** Fill missing JavaScript messages while preserving the selected JSON catalog. */
function sv_vader_script_translations($translations, $file, $handle, $domain) {
    if ($domain !== 'spelhubben-weather') {
        return $translations;
    }
    $bundled = SV_VADER_DIR . 'languages/' . basename($file);
    if (!is_readable($bundled) || realpath($bundled) === realpath($file)) {
        return $translations;
    }
    $selected = json_decode($translations, true);
    $fallback = json_decode(file_get_contents($bundled), true);
    if (!is_array($selected) || !is_array($fallback)) {
        return $translations;
    }
    $key = isset($selected['locale_data'][$domain]) ? $domain : 'messages';
    $fallback_key = isset($fallback['locale_data'][$domain]) ? $domain : 'messages';
    if (!isset($selected['locale_data'][$key], $fallback['locale_data'][$fallback_key]) ||
        !is_array($selected['locale_data'][$key]) || !is_array($fallback['locale_data'][$fallback_key])) {
        return $translations;
    }
    $selected['locale_data'][$key] += $fallback['locale_data'][$fallback_key];
    return wp_json_encode($selected);
}

/**
 * Returns an ISO language code based on the current WP locale.
 * Examples: sv_SE → sv, de_DE → de, fr_CA → fr.
 *
 * Filter: 'sv_vader_api_lang' to force a specific language.
 */
if (!function_exists('sv_vader_api_lang')) {
    function sv_vader_api_lang(): string {
        // Follow the correct context (admin/frontend)
        $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
        $parts = preg_split('/[_@-]/', strtolower((string) $locale));
        $lang = preg_match('/^[a-z]{2,3}$/D', $parts[0]) ? $parts[0] : 'en';

        /**
         * Allow forcing an API language.
         *
         * @param string $lang Language code passed to external APIs.
         */
        return (string) apply_filters('sv_vader_api_lang', $lang);
    }
}

/** Shared map messages for the frontend, block editor and admin preview. */
function sv_vader_map_translations(): array {
    return [
        'location' => __('Map location', 'spelhubben-weather'),
        'invalidCoords' => __('Map coordinates are missing or invalid.', 'spelhubben-weather'),
        'staticMode' => __('Interactive map is disabled for this weather block.', 'spelhubben-weather'),
        'openlayersFailed' => __('OpenLayers could not initialize on this page.', 'spelhubben-weather'),
        'leafletFailed' => __('Leaflet could not initialize on this page.', 'spelhubben-weather'),
        'unavailable' => __('No local interactive map engine was available.', 'spelhubben-weather'),
    ];
}

/** Map WordPress/ISO language codes to each provider's supported codes. */
function sv_vader_provider_lang(string $provider, string $lang): string {
    $lang = strtolower(str_replace('-', '_', $lang));
    $locale = strtolower(str_replace('-', '_', determine_locale()));
    // Preserve regional variants unless the API-language filter chose another language.
    if ($lang === 'zh') {
        $lang = preg_match('/^zh_(tw|hk|mo|hant)(_|$)/', $locale) ? 'zh_tw' : 'zh_cn';
    } elseif ($lang === 'pt' && $locale === 'pt_br') {
        $lang = 'pt_br';
    }
    if ($provider === 'openweathermap') {
        $aliases = ['nb'=>'no', 'nn'=>'no', 'cs'=>'cz', 'ko'=>'kr', 'lv'=>'la'];
        $supported = 'sq af ar az eu be bg ca zh_cn zh_tw hr cz da nl en fi fr gl de el he hi hu is id it ja kr ku la lt mk no fa pl pt pt_br ro ru sr sk sl es sv th tr uk vi zu';
    } elseif ($provider === 'weatherapi') {
        $aliases = ['zh_cn'=>'zh', 'pt_br'=>'pt', 'cmn'=>'zh_cmn', 'wuu'=>'zh_wuu', 'hsn'=>'zh_hsn', 'yue'=>'zh_yue'];
        $supported = 'en ar bn bg zh zh_tw cs da nl fi fr de el hi hu it ja jv ko zh_cmn mr pl pt pa ro ru sr si sk es sv ta te tr uk ur vi zh_wuu zh_hsn zh_yue zu';
    } else {
        return $lang;
    }
    $lang = $aliases[$lang] ?? $lang;
    return in_array($lang, explode(' ', $supported), true) ? $lang : 'en';
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
