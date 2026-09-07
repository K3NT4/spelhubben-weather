<?php
/**
 * Translation integration checks using WordPress's real translation reader.
 * Usage: php tests/i18n_test.php /path/to/wordpress/wp-includes/l10n
 * The harness supplies locale/loading functions; no database is required.
 */
$core_dir = $argv[1] ?? '';
foreach (['class-wp-translation-file', 'class-wp-translation-file-php', 'class-wp-translation-file-mo', 'class-wp-translation-controller'] as $class) {
    $path = $core_dir . '/' . $class . '.php';
    if (!is_file($path)) {
        fwrite(STDERR, "Pass the path to WordPress wp-includes/l10n.\n");
        exit(1);
    }
    require_once $path;
}
define('ABSPATH', __DIR__ . '/../');
$fixture_dir = sys_get_temp_dir() . '/svv-i18n-' . bin2hex(random_bytes(8));
mkdir($fixture_dir . '/languages', 0777, true);
define('SV_VADER_DIR', $fixture_dir . '/');
require_once ABSPATH . 'includes/i18n.php';
require_once ABSPATH . 'includes/class-sv-vader.php';
foreach (glob(ABSPATH . 'languages/*') as $source) {
    copy($source, SV_VADER_DIR . 'languages/' . basename($source));
}

function apply_filters($hook, $value) {
    return $GLOBALS['language_override'] ?? $value;
}
function sv_vader_cache_get($key) {
    $GLOBALS['cache_keys'][] = $key;
    return []; // Stop before any network access; inspect actual API cache lookups.
}
function sv_vader_stats_hit() {}

function determine_locale() {
    return WP_Translation_Controller::get_instance()->get_locale();
}
function __($text, $domain = 'default') {
    return WP_Translation_Controller::get_instance()->translate($text, '', $domain) ?: $text;
}
function wp_json_encode($data) { return json_encode($data); }
function get_translations_for_domain($domain) {
    if (determine_locale() === 'sv_SE' && !empty($GLOBALS['installed_pack'])) {
        WP_Translation_Controller::get_instance()->load_file($GLOBALS['installed_pack'], $domain, 'sv_SE');
    }
}
function load_textdomain($domain, $mofile, $locale = null) {
    $file = $GLOBALS['translation_format'] === 'php' ? substr($mofile, 0, -3) . '.l10n.php' : $mofile;
    return WP_Translation_Controller::get_instance()->load_file($file, $domain, $locale);
}
function check_translation($source, $expected, $context = '') {
    $actual = WP_Translation_Controller::get_instance()->translate($source, $context, 'spelhubben-weather');
    if ($actual !== $expected) {
        throw new RuntimeException($source . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

$controller = WP_Translation_Controller::get_instance();
$pack = tempnam(sys_get_temp_dir(), 'svv-language-');
file_put_contents($pack, '<?php return ' . var_export([
    'language' => 'sv_SE',
    'messages' => ['Overcast' => 'Egen svensk översättning'],
], true) . ';');
// The reader detects PHP catalogs by extension.
rename($pack, $pack . '.php');
$pack .= '.php';
try {
    foreach (['de_DE'=>'Einstellungen', 'de_DE_formal'=>'Formelle Einstellungen', 'ar'=>'إعدادات'] as $locale => $translation) {
        $file = SV_VADER_DIR . 'languages/spelhubben-weather-' . $locale;
        file_put_contents($file . '.l10n.php', '<?php return ' . var_export([
            'language'=>$locale, 'messages'=>['Settings'=>$translation],
        ], true) . ';');
        file_put_contents($file . '.mo', WP_Translation_File::transform($file . '.l10n.php', 'mo'));
    }
    foreach (['php', 'mo'] as $format) {
        $GLOBALS['translation_format'] = $format;
        $GLOBALS['installed_pack'] = $pack;
        $controller->unload_textdomain('spelhubben-weather');
        $controller->set_locale('sv_SE');
        get_translations_for_domain('spelhubben-weather');
        check_translation('Wind: %1$s %2$s', false); // Reproduce an incomplete pack.
        sv_vader_load_bundled_translations();
        check_translation('Wind: %1$s %2$s', 'Vind: %1$s %2$s');
        check_translation('Moon: %1$s (%2$s%%)', 'Månen: %1$s (%2$s%%)');
        check_translation('Waning Crescent', 'Avtagande halvmåne');
        check_translation('Overcast', 'Egen svensk översättning');
        check_translation('Compact', 'Kompakt', 'layout label');

        $controller->set_locale('nb_NO');
        sv_vader_load_bundled_translations('nb_NO');
        check_translation('Settings', 'Innstillinger');
        $controller->set_locale('en_US');
        sv_vader_load_bundled_translations('en_US');
        check_translation('Settings', false);
        $controller->set_locale('sv_SE');
        sv_vader_load_bundled_translations('sv_SE');
        check_translation('Overcast', 'Egen svensk översättning');

        $controller->unload_textdomain('spelhubben-weather');
        unset($GLOBALS['installed_pack']);
        sv_vader_load_bundled_translations();
        check_translation('Settings', 'Inställningar');
        check_translation('Overcast', 'Mulet');

        foreach (['de_DE'=>'Einstellungen', 'de_DE_formal'=>'Formelle Einstellungen', 'ar'=>'إعدادات'] as $locale => $translation) {
            $controller->set_locale($locale);
            sv_vader_load_bundled_translations($locale);
            check_translation('Settings', $translation);
        }
        $controller->set_locale('fr_CA');
        sv_vader_load_bundled_translations();
        check_translation('Settings', false); // No bundled translation is fine.
    }

    // New locales are discovered even if only a PHP catalog is supplied.
    unlink(SV_VADER_DIR . 'languages/spelhubben-weather-ar.mo');
    $GLOBALS['translation_format'] = 'php';
    $controller->unload_textdomain('spelhubben-weather');
    $controller->set_locale('ar');
    sv_vader_load_bundled_translations();
    check_translation('Settings', 'إعدادات');
    sv_vader_load_bundled_translations('../sv_SE');
    check_translation('Settings', 'إعدادات');

    foreach (['de_DE'=>'de', 'fr_CA'=>'fr', 'ar'=>'ar', 'de_DE_formal'=>'de', 'pt_BR'=>'pt', 'zh_TW'=>'zh'] as $locale => $expected) {
        $controller->set_locale($locale);
        if (sv_vader_api_lang() !== $expected) throw new RuntimeException('Wrong API language: ' . $locale);
    }
    foreach ([['de_DE','de','de','de'], ['nb_NO','nb','no','en'], ['cs_CZ','cs','cz','cs'], ['ko_KR','ko','kr','ko'], ['zh_TW','zh','zh_tw','zh_tw'], ['pt_BR','pt','pt_br','pt'], ['fi','fi','fi','fi'], ['xx','xx','en','en']] as $case) {
        [$locale, $lang, $owm, $weatherapi] = $case;
        $controller->set_locale($locale);
        if (sv_vader_provider_lang('openweathermap', $lang) !== $owm || sv_vader_provider_lang('weatherapi', $lang) !== $weatherapi) {
            throw new RuntimeException('Wrong provider mapping: ' . $locale);
        }
    }
    $GLOBALS['language_override'] = 'fr';
    if (sv_vader_api_lang() !== 'fr') throw new RuntimeException('API language filter ignored');
    unset($GLOBALS['language_override']);

    $api = new SV_Vader_API();
    $keys = [];
    foreach (['en_US', 'en_GB', 'de_DE', 'de_DE_formal'] as $locale) {
        $controller->set_locale($locale);
        $GLOBALS['cache_keys'] = [];
        $api->get_current_weather('Test', '57', '11');
        $api->get_provider_details('Test', '57', '11');
        $api->get_daily_forecast('Test', '57', '11');
        $api->get_hourly_forecast('Test', '57', '11');
        $keys = array_merge($keys, $GLOBALS['cache_keys']);
    }
    if (count(array_unique($keys)) !== 16) throw new RuntimeException('Weather cache mixes locales');

    $json_name = 'spelhubben-weather-sv_SE-' . md5('blocks/spelhubben-weather/index.js') . '.json';
    $bundled_json = SV_VADER_DIR . 'languages/' . $json_name;
    $installed_json = SV_VADER_DIR . $json_name;
    if (sv_vader_script_translation_file($installed_json, 'block', 'spelhubben-weather') !== $bundled_json) {
        throw new RuntimeException('Missing JSON pack did not fall back to bundled catalog');
    }
    $selected = ['locale_data'=>['messages'=>[''=>['lang'=>'sv_SE'], 'Location'=>['Egen plats']]]];
    file_put_contents($installed_json, json_encode($selected));
    if (sv_vader_script_translation_file($installed_json, 'block', 'spelhubben-weather') !== $installed_json) {
        throw new RuntimeException('Installed JSON pack lost precedence');
    }
    $merged = json_decode(sv_vader_script_translations(json_encode($selected), $installed_json, 'block', 'spelhubben-weather'), true);
    if ($merged['locale_data']['messages']['Location'] !== ['Egen plats'] || $merged['locale_data']['messages']['Temperature'] !== ['Temperatur']) {
        throw new RuntimeException('JSON fallback did not preserve custom messages and fill gaps');
    }
    if (sv_vader_script_translations('invalid', $installed_json, 'block', 'spelhubben-weather') !== 'invalid' ||
        sv_vader_script_translations('{}', $installed_json, 'block', 'another-plugin') !== '{}') {
        throw new RuntimeException('JSON filter changed unrelated or malformed data');
    }
    echo "All translation checks passed (PHP and MO).\n";
} finally {
    unlink($pack);
    if (isset($installed_json) && is_file($installed_json)) unlink($installed_json);
    foreach (glob(SV_VADER_DIR . 'languages/*') as $file) unlink($file);
    rmdir(SV_VADER_DIR . 'languages');
    rmdir($fixture_dir);
}
