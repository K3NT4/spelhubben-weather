<?php
// includes/providers.php - Weather data providers and normalization functions
if (!defined('ABSPATH')) exit;

/**
 * Helper: Check if remote response is valid
 * Returns true if response is OK and status code matches expected
 */
if (!function_exists('sv_vader_check_remote_response')) {
    function sv_vader_check_remote_response($res, $expected_code = 200) {
        if (is_wp_error($res)) return false;
        return wp_remote_retrieve_response_code($res) === $expected_code;
    }
}

if (!function_exists('sv_vader_remote_error_status')) {
    function sv_vader_remote_error_status($res, $expected_code = 200) {
        if (is_wp_error($res)) return 'request_failed';
        return wp_remote_retrieve_response_code($res) === $expected_code ? '' : 'request_failed';
    }
}

if (!function_exists('sv_vader_provider_registry')) {
    /**
     * Canonical provider registry used by settings, renderer, diagnostics and docs.
     */
    function sv_vader_provider_registry() {
        return [
            'openmeteo' => [
                'id' => 'openmeteo',
                'label' => __('Open-Meteo', 'spelhubben-weather'),
                'option_key' => 'prov_openmeteo',
                'requires_key' => false,
                'key_option' => '',
                'current' => true,
                'hourly' => true,
                'region' => __('Global', 'spelhubben-weather'),
            ],
            'smhi' => [
                'id' => 'smhi',
                'label' => __('SMHI', 'spelhubben-weather'),
                'option_key' => 'prov_smhi',
                'requires_key' => false,
                'key_option' => '',
                'current' => true,
                'hourly' => false,
                'region' => __('Sweden and nearby regions', 'spelhubben-weather'),
            ],
            'yr' => [
                'id' => 'yr',
                'label' => __('Yr (MET Norway)', 'spelhubben-weather'),
                'option_key' => 'prov_yr',
                'requires_key' => false,
                'key_option' => '',
                'current' => true,
                'hourly' => false,
                'region' => __('Global', 'spelhubben-weather'),
            ],
            'metno_nowcast' => [
                'id' => 'metno_nowcast',
                'label' => __('MET Norway Nowcast', 'spelhubben-weather'),
                'option_key' => 'prov_metno_nowcast',
                'requires_key' => false,
                'key_option' => '',
                'current' => true,
                'hourly' => false,
                'region' => __('Nordic radar coverage', 'spelhubben-weather'),
            ],
            'fmi' => [
                'id' => 'fmi',
                'label' => __('FMI (Finland, Open Data)', 'spelhubben-weather'),
                'option_key' => 'prov_fmi',
                'requires_key' => false,
                'key_option' => '',
                'current' => true,
                'hourly' => false,
                'region' => __('Finland and nearby regions', 'spelhubben-weather'),
            ],
            'openweathermap' => [
                'id' => 'openweathermap',
                'label' => __('OpenWeatherMap', 'spelhubben-weather'),
                'option_key' => 'prov_openweathermap',
                'requires_key' => true,
                'key_option' => 'owm_api_key',
                'current' => true,
                'hourly' => false,
                'region' => __('Global', 'spelhubben-weather'),
            ],
            'weatherapi' => [
                'id' => 'weatherapi',
                'label' => __('WeatherAPI.com', 'spelhubben-weather'),
                'option_key' => 'prov_weatherapi',
                'requires_key' => true,
                'key_option' => 'weatherapi_api_key',
                'current' => true,
                'hourly' => false,
                'region' => __('Global', 'spelhubben-weather'),
            ],
        ];
    }
}

if (!function_exists('sv_vader_provider_ids')) {
    function sv_vader_provider_ids() {
        return array_keys(sv_vader_provider_registry());
    }
}

if (!function_exists('sv_vader_enabled_provider_ids')) {
    function sv_vader_enabled_provider_ids($opts = null) {
        if ($opts === null && function_exists('sv_vader_get_options')) {
            $opts = sv_vader_get_options();
        }
        $opts = is_array($opts) ? $opts : [];
        $ids = [];
        foreach (sv_vader_provider_registry() as $id => $provider) {
            $option_key = $provider['option_key'];
            if (!empty($opts[$option_key])) {
                $ids[] = $id;
            }
        }
        return $ids;
    }
}

if (!function_exists('sv_vader_provider_key_missing')) {
    function sv_vader_provider_key_missing($provider_id, array $opts) {
        $registry = sv_vader_provider_registry();
        if (empty($registry[$provider_id]['requires_key'])) {
            return false;
        }

        $key_option = $registry[$provider_id]['key_option'] ?? '';
        return $key_option === '' || trim((string)($opts[$key_option] ?? '')) === '';
    }
}

if (!function_exists('sv_vader_openmeteo_current')) {
    function sv_vader_openmeteo_current($lat, $lon, $locale = 'en') {
        $url = add_query_arg([
            'latitude'  => $lat,
            'longitude' => $lon,
            'current'   => 'temperature_2m,wind_speed_10m,wind_direction_10m,weather_code,precipitation,cloud_cover',
            'timezone'  => 'Europe/Stockholm',
            'lang'      => $locale
        ], 'https://api.open-meteo.com/v1/forecast');

        $res = wp_remote_get($url, ['timeout' => 10]);
        $remote_status = sv_vader_remote_error_status($res, 200);
        if ($remote_status !== '') return ['_status' => $remote_status];
        $j = json_decode(wp_remote_retrieve_body($res), true);
        if (empty($j['current'])) return ['_status' => 'no_data'];

        $c = $j['current'];
        return [
            'temp'     => isset($c['temperature_2m']) ? floatval($c['temperature_2m']) : null,
            'wind'     => isset($c['wind_speed_10m']) ? floatval($c['wind_speed_10m']) : null,
            'wind_dir' => isset($c['wind_direction_10m']) ? floatval($c['wind_direction_10m']) : null,
            'precip'   => isset($c['precipitation']) ? floatval($c['precipitation']) : null,
            'cloud'    => isset($c['cloud_cover']) ? intval($c['cloud_cover']) : null,
            'code'     => isset($c['weather_code']) ? intval($c['weather_code']) : null,
            'desc'     => null,
        ];
    }
}

if (!function_exists('sv_vader_smhi_current')) {
    function sv_vader_smhi_current($lat, $lon) {
        $url = sprintf(
            'https://opendata.smhi.se/meteorological/forecast/api/category/pmp3g/version/2/geotype/point/lon/%s/lat/%s/data.json',
            rawurlencode($lon), rawurlencode($lat)
        );
        $res = wp_remote_get($url, [
            'timeout'    => 12,
            'user-agent' => 'Spelhubben-Weather/1.0'
        ]);
        $remote_status = sv_vader_remote_error_status($res, 200);
        if ($remote_status !== '') return ['_status' => $remote_status];
        $j = json_decode(wp_remote_retrieve_body($res), true);
        if (empty($j['timeSeries'][0])) return ['_status' => 'no_data'];

        $now = current_time('timestamp', true);
        $nearest = null; $mindiff = PHP_INT_MAX;
        foreach ($j['timeSeries'] as $ts) {
            $t = strtotime($ts['validTime']);
            $diff = abs($t - $now);
            if ($diff < $mindiff) { $mindiff = $diff; $nearest = $ts; }
        }
        if (!$nearest || empty($nearest['parameters'])) return ['_status' => 'no_data'];

        $map = [];
        foreach ($nearest['parameters'] as $p) {
            $map[$p['name']] = $p['values'][0];
        }

        // SMHI total cloud cover (oktas 0..8) → percent
        $cloud_pct = isset($map['tcc']) ? intval(round(($map['tcc'] / 8) * 100)) : null;

        return [
            'temp'     => isset($map['t']) ? floatval($map['t']) : null,
            'wind'     => isset($map['ws']) ? floatval($map['ws']) : null,
            'wind_dir' => isset($map['wd']) ? floatval($map['wd']) : null,
            'precip'   => isset($map['pmean']) ? floatval($map['pmean']) : null,
            'cloud'    => $cloud_pct,
            'code'     => null,
            'desc'     => null,
        ];
    }
}

if (!function_exists('sv_vader_yr_current')) {
    function sv_vader_yr_current($lat, $lon, $contactUA = '') {
        $ua = 'Spelhubben-Weather/1.0';
        if ($contactUA) $ua .= ' (' . $contactUA . ')';

        $url = add_query_arg(['lat' => $lat, 'lon' => $lon], 'https://api.met.no/weatherapi/locationforecast/2.0/compact');

        $res = wp_remote_get($url, [
            'timeout' => 12,
            'headers' => ['User-Agent' => $ua]
        ]);
        $remote_status = sv_vader_remote_error_status($res, 200);
        if ($remote_status !== '') return ['_status' => $remote_status];
        $j = json_decode(wp_remote_retrieve_body($res), true);
        if (empty($j['properties']['timeseries'][0])) return ['_status' => 'no_data'];

        $now = current_time('timestamp', true);
        $nearest = null; $mindiff = PHP_INT_MAX;
        foreach ($j['properties']['timeseries'] as $ts) {
            $t = strtotime($ts['time']);
            $diff = abs($t - $now);
            if ($diff < $mindiff) { $mindiff = $diff; $nearest = $ts; }
        }
        if (!$nearest) return ['_status' => 'no_data'];

        $inst   = $nearest['data']['instant']['details'] ?? [];
        $next1h = $nearest['data']['next_1_hours']['details'] ?? [];

        return [
            'temp'     => isset($inst['air_temperature']) ? floatval($inst['air_temperature']) : null,
            'wind'     => isset($inst['wind_speed']) ? floatval($inst['wind_speed']) : null,
            'wind_dir' => isset($inst['wind_from_direction']) ? floatval($inst['wind_from_direction']) : null,
            'precip'   => isset($next1h['precipitation_amount']) ? floatval($next1h['precipitation_amount']) : null,
            'cloud'    => isset($inst['cloud_area_fraction']) ? intval(round($inst['cloud_area_fraction'])) : null,
            'code'     => null,
            'desc'     => null,
        ];
    }
}

if (!function_exists('sv_vader_metno_nowcast_current')) {
    /**
     * MET Norway Nowcast 2.0. Best short-term forecast in Nordic radar coverage.
     */
    function sv_vader_metno_nowcast_current($lat, $lon, $contactUA = '') {
        $ua = 'Spelhubben-Weather/2.1';
        if ($contactUA) $ua .= ' (' . $contactUA . ')';

        $url = add_query_arg([
            'lat' => $lat,
            'lon' => $lon,
        ], 'https://api.met.no/weatherapi/nowcast/2.0/complete');

        $res = wp_remote_get($url, [
            'timeout' => 12,
            'headers' => [
                'User-Agent' => $ua,
                'Accept' => 'application/json',
            ],
        ]);

        if (is_wp_error($res)) {
            return ['_status' => 'request_failed'];
        }

        $code = wp_remote_retrieve_response_code($res);
        if ($code === 404 || $code === 422) {
            return ['_status' => 'no_coverage'];
        }
        if ($code !== 200) {
            return ['_status' => 'request_failed'];
        }

        $j = json_decode(wp_remote_retrieve_body($res), true);
        if (empty($j['properties']['timeseries'][0])) {
            return ['_status' => 'no_data'];
        }

        $meta = $j['properties']['meta'] ?? [];
        $coverage = strtolower((string)($meta['radar_coverage'] ?? ($meta['radarCoverage'] ?? '')));
        $status = 'ok';
        if (strpos($coverage, 'no coverage') !== false) {
            $status = 'no_coverage';
        } elseif (strpos($coverage, 'temporarily') !== false) {
            $status = 'request_failed';
        }

        $now = current_time('timestamp', true);
        $nearest = null; $mindiff = PHP_INT_MAX;
        foreach ($j['properties']['timeseries'] as $ts) {
            $t = strtotime($ts['time'] ?? '');
            if (!$t) continue;
            $diff = abs($t - $now);
            if ($diff < $mindiff) { $mindiff = $diff; $nearest = $ts; }
        }
        if (!$nearest) return ['_status' => 'no_data'];

        $inst = $nearest['data']['instant']['details'] ?? [];
        $next1h = $nearest['data']['next_1_hours']['details'] ?? [];
        $summary = $nearest['data']['next_1_hours']['summary'] ?? [];
        $symbol = isset($summary['symbol_code']) ? sanitize_text_field($summary['symbol_code']) : '';

        $out = [
            'temp'     => isset($inst['air_temperature']) ? floatval($inst['air_temperature']) : null,
            'wind'     => isset($inst['wind_speed']) ? floatval($inst['wind_speed']) : null,
            'wind_dir' => isset($inst['wind_from_direction']) ? floatval($inst['wind_from_direction']) : null,
            'precip'   => isset($next1h['precipitation_amount']) ? floatval($next1h['precipitation_amount']) : null,
            'cloud'    => isset($inst['cloud_area_fraction']) ? intval(round($inst['cloud_area_fraction'])) : null,
            'code'     => function_exists('sv_vader_symbol_code_to_wmo') ? sv_vader_symbol_code_to_wmo($symbol) : null,
            'desc'     => $symbol ? str_replace('_', ' ', $symbol) : null,
            '_status'  => $status,
        ];

        return ($out['temp']===null && $out['wind']===null && $out['wind_dir']===null && $out['precip']===null && $out['cloud']===null)
            ? ['_status' => $status === 'ok' ? 'no_data' : $status]
            : $out;
    }
}

if (!function_exists('sv_vader_symbol_code_to_wmo')) {
    function sv_vader_symbol_code_to_wmo($symbol) {
        $symbol = strtolower((string)$symbol);
        if ($symbol === '') return null;
        if (strpos($symbol, 'thunder') !== false) return 95;
        if (strpos($symbol, 'heavysnow') !== false) return 75;
        if (strpos($symbol, 'snow') !== false) return 71;
        if (strpos($symbol, 'sleet') !== false) return 66;
        if (strpos($symbol, 'heavyrain') !== false) return 65;
        if (strpos($symbol, 'rain') !== false) return 61;
        if (strpos($symbol, 'fog') !== false) return 45;
        if (strpos($symbol, 'partlycloudy') !== false) return 2;
        if (strpos($symbol, 'cloudy') !== false) return 3;
        if (strpos($symbol, 'fair') !== false) return 1;
        if (strpos($symbol, 'clearsky') !== false) return 0;
        return null;
    }
}

/**
 * NEW: FMI (Finnish Meteorological Institute) via WFS timevaluepair
 * Uses bbox around point to pick nearest station.
 * Parameters:
 *  - t2m (°C), ws_10min (m/s), r_1h (mm), n_man (cloud oktas 0..8)
 */
if (!function_exists('sv_vader_fmi_current')) {
    function sv_vader_fmi_current($lat, $lon) {
        $lat = floatval($lat); $lon = floatval($lon);
        if (!$lat && !$lon) return ['_status' => 'no_data'];

        $d = 0.06; // ~ ca 6–7 km
        $bbox = ($lon - $d) . ',' . ($lat - $d) . ',' . ($lon + $d) . ',' . ($lat + $d) . ',epsg:4326';

        $url = add_query_arg([
            'service'        => 'WFS',
            'version'        => '2.0.0',
            'request'        => 'getFeature',
            'storedquery_id' => 'fmi::observations::weather::timevaluepair',
            'parameters'     => 't2m,ws_10min,wd_10min,r_1h,n_man',
            'bbox'           => $bbox,
        ], 'https://opendata.fmi.fi/wfs');

        $res = wp_remote_get($url, ['timeout'=>14,'user-agent'=>'Spelhubben-Weather/1.0 (FMI WFS)']);
        $remote_status = sv_vader_remote_error_status($res, 200);
        if ($remote_status !== '') return ['_status' => $remote_status];

        $xml = wp_remote_retrieve_body($res);
        if (!is_string($xml) || $xml==='') return ['_status' => 'no_data'];

        // Safely load XML with LIBXML_NOCDATA to avoid entity expansion attacks
        $old_errors = libxml_use_internal_errors(true);
        $sx = simplexml_load_string($xml, null, LIBXML_NOCDATA);
        libxml_use_internal_errors($old_errors);
        
        if (!$sx) return ['_status' => 'no_data'];
        $sx->registerXPathNamespace('wml2','http://www.opengis.net/waterml/2.0');
        $sx->registerXPathNamespace('gml', 'http://www.opengis.net/gml/3.2');

        $out = ['temp'=>null,'wind'=>null,'wind_dir'=>null,'precip'=>null,'cloud'=>null,'code'=>null,'desc'=>null];
        $series = $sx->xpath('//wml2:MeasurementTimeseries');
        if (is_array($series)) {
            foreach ($series as $ts) {
                $attrs = $ts->attributes('gml', true);
                $gid   = isset($attrs['id']) ? strtolower((string)$attrs['id']) : '';
                $vals  = $ts->xpath('.//wml2:point/wml2:MeasurementTVP/wml2:value');
                if (!$vals || !count($vals)) continue;
                $val = (string)$vals[count($vals)-1];

                if (strpos($gid,'t2m')!==false)          $out['temp']     = is_numeric($val)?(float)$val:null;
                elseif (strpos($gid,'ws_10min')!==false) $out['wind']     = is_numeric($val)?(float)$val:null;
                elseif (strpos($gid,'wd_10min')!==false) $out['wind_dir'] = is_numeric($val)?(float)$val:null;
                elseif (strpos($gid,'r_1h')!==false)     $out['precip']   = is_numeric($val)?(float)$val:null;
                elseif (strpos($gid,'n_man')!==false) {
                    $oktas = is_numeric($val)?(float)$val:null;
                    $out['cloud'] = ($oktas!==null) ? (int)round(($oktas/8)*100) : null;
                }
            }
        }
        return ($out['temp']===null && $out['wind']===null && $out['wind_dir']===null && $out['precip']===null && $out['cloud']===null) ? ['_status' => 'no_data'] : $out;
    }
}

if (!function_exists('sv_vader_openweathermap_current')) {
    function sv_vader_openweathermap_current($lat, $lon, $locale = 'en', $api_key = '') {
        // OpenWeatherMap requires an API key configured in plugin settings.
        $api_key = trim((string)$api_key);
        if ($api_key === '') return ['_status' => 'missing_key'];

        $url = add_query_arg([
            'lat'   => $lat,
            'lon'   => $lon,
            'units' => 'metric',
            'lang'  => $locale,
            'appid' => $api_key,
        ], 'https://api.openweathermap.org/data/2.5/weather');

        $res = wp_remote_get($url, ['timeout' => 10]);
        $remote_status = sv_vader_remote_error_status($res, 200);
        if ($remote_status !== '') return ['_status' => $remote_status];
        $j = json_decode(wp_remote_retrieve_body($res), true);
        if (empty($j['main'])) return ['_status' => 'no_data'];

        $main = $j['main'];
        $wind = !empty($j['wind']) ? $j['wind'] : [];
        $clouds = !empty($j['clouds']) ? $j['clouds'] : [];
        $rain = !empty($j['rain']) ? $j['rain'] : [];

        return [
            'temp'     => isset($main['temp']) ? floatval($main['temp']) : null,
            'wind'     => isset($wind['speed']) ? floatval($wind['speed']) : null,
            'wind_dir' => isset($wind['deg']) ? floatval($wind['deg']) : null,
            'precip'   => isset($rain['1h']) ? floatval($rain['1h']) : null,
            'cloud'    => isset($clouds['all']) ? intval($clouds['all']) : null,
            'code'     => null,
            'desc'     => !empty($j['weather'][0]['main']) ? sanitize_text_field($j['weather'][0]['main']) : null,
        ];
    }
}

if (!function_exists('sv_vader_weatherapi_current')) {
    function sv_vader_weatherapi_current($lat, $lon, $locale = 'en', $api_key = '') {
        // WeatherAPI requires an API key configured in plugin settings.
        $api_key = trim((string)$api_key);
        if ($api_key === '') return ['_status' => 'missing_key'];

        $lang_map = [
            'sv' => 'sv',
            'nb' => 'no',
            'en' => 'en',
            'de' => 'de',
            'fr' => 'fr',
            'es' => 'es',
        ];
        $api_lang = $lang_map[substr($locale, 0, 2)] ?? 'en';

        $url = add_query_arg([
            'key'   => $api_key,
            'q'     => "$lat,$lon",
            'lang'  => $api_lang,
            'aqi'   => 'no',
        ], 'https://api.weatherapi.com/v1/current.json');

        $res = wp_remote_get($url, ['timeout' => 10]);
        $remote_status = sv_vader_remote_error_status($res, 200);
        if ($remote_status !== '') return ['_status' => $remote_status];
        $j = json_decode(wp_remote_retrieve_body($res), true);
        if (empty($j['current'])) return ['_status' => 'no_data'];

        $current = $j['current'];
        return [
            'temp'     => isset($current['temp_c']) ? floatval($current['temp_c']) : null,
            'wind'     => isset($current['wind_kph']) ? floatval($current['wind_kph'] / 3.6) : null, // Convert km/h to m/s
            'wind_dir' => isset($current['wind_degree']) ? floatval($current['wind_degree']) : null,
            'precip'   => isset($current['precip_mm']) ? floatval($current['precip_mm']) : null,
            'cloud'    => isset($current['cloud']) ? intval($current['cloud']) : null,
            'code'     => null,
            'desc'     => !empty($current['condition']['text']) ? sanitize_text_field($current['condition']['text']) : null,
        ];
    }
}

/**
 * WMO code → English text (base language). Wrapped in i18n for translation.
 */
if (!function_exists('sv_vader_wmo_text')) {
    function sv_vader_wmo_text($code) {
        // Translators: weather description from WMO code.
        $map = [
            0  => __('Clear sky', 'spelhubben-weather'),
            1  => __('Mostly clear', 'spelhubben-weather'),
            2  => __('Partly cloudy', 'spelhubben-weather'),
            3  => __('Overcast', 'spelhubben-weather'),
            45 => __('Fog', 'spelhubben-weather'),
            48 => __('Depositing rime fog', 'spelhubben-weather'),
            51 => __('Drizzle: light', 'spelhubben-weather'),
            53 => __('Drizzle: moderate', 'spelhubben-weather'),
            55 => __('Drizzle: dense', 'spelhubben-weather'),
            61 => __('Rain: light', 'spelhubben-weather'),
            63 => __('Rain: moderate', 'spelhubben-weather'),
            65 => __('Rain: heavy', 'spelhubben-weather'),
            66 => __('Freezing rain: light', 'spelhubben-weather'),
            67 => __('Freezing rain: heavy', 'spelhubben-weather'),
            71 => __('Snowfall: light', 'spelhubben-weather'),
            73 => __('Snowfall: moderate', 'spelhubben-weather'),
            75 => __('Snowfall: heavy', 'spelhubben-weather'),
            77 => __('Snow grains', 'spelhubben-weather'),
            80 => __('Rain showers: slight', 'spelhubben-weather'),
            81 => __('Rain showers: moderate', 'spelhubben-weather'),
            82 => __('Rain showers: violent', 'spelhubben-weather'),
            85 => __('Snow showers: slight', 'spelhubben-weather'),
            86 => __('Snow showers: heavy', 'spelhubben-weather'),
            95 => __('Thunderstorm', 'spelhubben-weather'),
            96 => __('Thunderstorm with slight hail', 'spelhubben-weather'),
            99 => __('Thunderstorm with heavy hail', 'spelhubben-weather'),
        ];
        return $map[$code] ?? '';
    }
}

// Back-compat: old Swedish helper (now defers to English/i18n version)
if (!function_exists('sv_vader_wmo_text_sv')) {
    function sv_vader_wmo_text_sv($code) {
        return sv_vader_wmo_text($code);
    }
}

if (!function_exists('sv_vader_consensus')) {
    function sv_vader_consensus(array $samples) {
        $nums = ['temp','wind','wind_dir','precip','cloud'];
        $out = [];
        foreach ($nums as $k) {
            $vals = array_values(array_filter(array_map(function($s) use ($k){
                return $s[$k] ?? null;
            }, $samples), function($v){ return $v !== null; }));
            if ($vals) {
                sort($vals, SORT_NUMERIC);
                $mid = (int) floor((count($vals) - 1) / 2);
                $out[$k] = $vals[$mid]; // median
            } else {
                $out[$k] = null;
            }
        }

        // Description/icon: prefer Open-Meteo WMO code when available
        $om = null;
        foreach ($samples as $s) {
            if (isset($s['code']) && $s['code'] !== null) { $om = $s['code']; break; }
        }
        if ($om !== null) {
            $out['code'] = $om;
            $out['desc'] = sv_vader_wmo_text($om);
        } else {
            $cloud = $out['cloud'];
            $prec  = $out['precip'];
            if ($prec !== null && $prec >= 0.1) {
                $out['desc'] = __('Precipitation', 'spelhubben-weather');
            } elseif ($cloud !== null) {
                if ($cloud <= 20) {
                    $out['desc'] = __('Clear sky', 'spelhubben-weather');
                } elseif ($cloud <= 60) {
                    $out['desc'] = __('Partly cloudy', 'spelhubben-weather');
                } else {
                    $out['desc'] = __('Overcast', 'spelhubben-weather');
                }
            } else {
                $out['desc'] = '';
            }
            $out['code'] = null;
        }
        return $out;
    }
}

if (!function_exists('sv_vader_openmeteo_daily')) {
    /**
     * Fetch daily forecast (max/min, WMO code) for N days (3..10).
     * Returns: [ ['date'=>'YYYY-MM-DD','tmax'=>..,'tmin'=>..,'code'=>int|null,'desc'=>string], ... ]
     */
    function sv_vader_openmeteo_daily($lat, $lon, $days = 5, $locale = 'en') {
        $days = max(3, min(10, intval($days)));
        $url = add_query_arg([
            'latitude'      => $lat,
            'longitude'     => $lon,
            'daily'         => 'weather_code,temperature_2m_max,temperature_2m_min',
            'timezone'      => 'Europe/Stockholm',
            'forecast_days' => $days,
            'lang'          => $locale
        ], 'https://api.open-meteo.com/v1/forecast');

        $res = wp_remote_get($url, ['timeout' => 10]);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) return [];

        $j = json_decode(wp_remote_retrieve_body($res), true);
        $out = [];
        if (!empty($j['daily']['time'])) {
            $times = $j['daily']['time'];
            $tmax  = $j['daily']['temperature_2m_max'] ?? [];
            $tmin  = $j['daily']['temperature_2m_min'] ?? [];
            $wcode = $j['daily']['weather_code'] ?? [];
            foreach ($times as $i => $iso) {
                $code = isset($wcode[$i]) ? intval($wcode[$i]) : null;
                $out[] = [
                    'date' => $iso,
                    'tmax' => isset($tmax[$i]) ? round(floatval($tmax[$i])) : null,
                    'tmin' => isset($tmin[$i]) ? round(floatval($tmin[$i])) : null,
                    'code' => $code,
                    'desc' => ($code !== null) ? sv_vader_wmo_text($code) : ''
                ];
            }
        }
        return $out;
    }
}

if (!function_exists('sv_vader_openmeteo_hourly')) {
    /**
     * Fetch compact hourly forecast for the next N hours.
     * Returns: [ ['time'=>'YYYY-MM-DDTHH:MM','temp'=>..,'wind'=>..,'wind_dir'=>..,'precip'=>..,'code'=>int|null,'desc'=>string], ... ]
     */
    function sv_vader_openmeteo_hourly($lat, $lon, $hours = 24, $locale = 'en') {
        $hours = max(3, min(24, intval($hours)));
        $url = add_query_arg([
            'latitude'       => $lat,
            'longitude'      => $lon,
            'hourly'         => 'temperature_2m,wind_speed_10m,wind_direction_10m,weather_code,precipitation',
            'timezone'       => 'Europe/Stockholm',
            'forecast_hours' => $hours,
            'lang'           => $locale,
        ], 'https://api.open-meteo.com/v1/forecast');

        $res = wp_remote_get($url, ['timeout' => 10]);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) return [];

        $j = json_decode(wp_remote_retrieve_body($res), true);
        if (empty($j['hourly']['time']) || !is_array($j['hourly']['time'])) return [];

        $times = $j['hourly']['time'];
        $temps = $j['hourly']['temperature_2m'] ?? [];
        $winds = $j['hourly']['wind_speed_10m'] ?? [];
        $wind_dirs = $j['hourly']['wind_direction_10m'] ?? [];
        $codes = $j['hourly']['weather_code'] ?? [];
        $precips = $j['hourly']['precipitation'] ?? [];
        $out = [];

        foreach (array_slice($times, 0, $hours) as $i => $time) {
            $code = isset($codes[$i]) ? intval($codes[$i]) : null;
            $out[] = [
                'time'     => $time,
                'temp'     => isset($temps[$i]) ? floatval($temps[$i]) : null,
                'wind'     => isset($winds[$i]) ? floatval($winds[$i]) : null,
                'wind_dir' => isset($wind_dirs[$i]) ? floatval($wind_dirs[$i]) : null,
                'precip'   => isset($precips[$i]) ? floatval($precips[$i]) : null,
                'code'     => $code,
                'desc'     => ($code !== null) ? sv_vader_wmo_text($code) : '',
            ];
        }

        return $out;
    }
}

if (!function_exists('sv_vader_provider_status_label')) {
    function sv_vader_provider_status_label($status) {
        $labels = [
            'ok' => __('OK', 'spelhubben-weather'),
            'missing_key' => __('Missing API key', 'spelhubben-weather'),
            'no_coverage' => __('No coverage', 'spelhubben-weather'),
            'request_failed' => __('Request failed', 'spelhubben-weather'),
            'no_data' => __('No data', 'spelhubben-weather'),
        ];
        return $labels[$status] ?? __('Unknown', 'spelhubben-weather');
    }
}


    /**
     * Tide fetching helpers
     * - Supports WorldTides (API key) and a generic custom endpoint.
     * - Returns array: ['tz'=>string,'events'=> [ ['time'=>'ISO','type'=>'high'|'low','height'=>float|null], ... ] ]
     */
    if (!function_exists('sv_vader_fetch_tides')) {
        function sv_vader_fetch_tides($lat, $lon) {
            $lat = floatval($lat); $lon = floatval($lon);
            if (!$lat && !$lon) return null;

            $opts = sv_vader_get_options();
            if (empty($opts['tides_enabled'])) return null;

            $prov = $opts['tide_provider'] ?? 'custom';
            $cache_key = 'sv_vader_tides_' . md5($lat . '|' . $lon . '|' . $prov . '|' . sv_vader_cache_salt());
            $cached = sv_vader_cache_get($cache_key);
            if ($cached !== false) return $cached;

            $res = null;
            if ($prov === 'worldtides' && !empty($opts['tide_api_key'])) {
                $res = sv_vader_tide_worldtides($lat, $lon, $opts['tide_api_key']);
            } elseif ($prov === 'noaa') {
                // NOAA is US-only and format varies; attempt a simple query to tides API via NOAA CO-OPS (v1 REST)
                $res = sv_vader_tide_noaa($lat, $lon);
            } else {
                // custom endpoint: append lat/lon and api_key as query params if provided
                $endpoint = rtrim((string)($opts['tide_custom_endpoint'] ?? ''), '/');
                if ($endpoint !== '') {
                    $res = sv_vader_tide_custom($endpoint, $lat, $lon, $opts['tide_api_key'] ?? '');
                }
            }

            if ($res !== null) {
                $ttl = max(5, intval($opts['tide_cache_minutes'] ?? 60));
                sv_vader_cache_set($cache_key, $res, MINUTE_IN_SECONDS * $ttl);
            }
            return $res;
        }
    }

    if (!function_exists('sv_vader_tide_worldtides')) {
        function sv_vader_tide_worldtides($lat, $lon, $key) {
            $url = add_query_arg([
                'extremes' => 'true',
                'lat' => $lat,
                'lon' => $lon,
                'key' => $key,
            ], 'https://www.worldtides.info/api/v3');

            $res = wp_remote_get($url, ['timeout' => 12]);
            if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) return null;
            $j = json_decode(wp_remote_retrieve_body($res), true);
            if (!$j) return null;

            $tz = $j['timezone'] ?? null;
            $events = [];
            if (!empty($j['extremes']) && is_array($j['extremes'])) {
                foreach ($j['extremes'] as $e) {
                    $dt = !empty($e['dt']) ? gmdate('c', intval($e['dt'])) : ( !empty($e['date']) ? $e['date'] : null );
                    $type = !empty($e['type']) ? strtolower($e['type']) : null; // High/Low
                    $h = isset($e['height']) ? floatval($e['height']) : (isset($e['value']) ? floatval($e['value']) : null);
                    if ($dt && $type) {
                        $events[] = ['time' => $dt, 'type' => ($type === 'high' ? 'high' : 'low'), 'height' => $h];
                    }
                }
            }
            // Fallback: heights array
            if (empty($events) && !empty($j['heights']) && is_array($j['heights'])) {
                foreach ($j['heights'] as $h) {
                    $dt = !empty($h['dt']) ? gmdate('c', intval($h['dt'])) : null;
                    $val = isset($h['height']) ? floatval($h['height']) : (isset($h['value']) ? floatval($h['value']) : null);
                    if ($dt) $events[] = ['time' => $dt, 'type' => 'height', 'height' => $val];
                }
            }

            return ['tz' => $tz, 'events' => $events];
        }
    }

    if (!function_exists('sv_vader_tide_custom')) {
        function sv_vader_tide_custom($endpoint, $lat, $lon, $key = '') {
            $url = add_query_arg(['lat' => $lat, 'lon' => $lon], $endpoint);
            if ($key !== '') $url = add_query_arg(['api_key' => $key], $url);

            $res = wp_remote_get($url, ['timeout' => 12]);
            if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) return null;
            $j = json_decode(wp_remote_retrieve_body($res), true);
            if (!$j || !is_array($j)) return null;

            // Try to normalise common shapes: extremes | events | data
            $events = [];
            if (!empty($j['extremes']) && is_array($j['extremes'])) {
                foreach ($j['extremes'] as $e) {
                    $time = $e['time'] ?? ($e['date'] ?? null);
                    $type = strtolower($e['type'] ?? ($e['kind'] ?? ''));
                    $h = isset($e['height']) ? floatval($e['height']) : null;
                    if ($time) $events[] = ['time' => $time, 'type' => $type ?: 'event', 'height' => $h];
                }
            } elseif (!empty($j['events']) && is_array($j['events'])) {
                foreach ($j['events'] as $e) {
                    $time = $e['time'] ?? $e['date'] ?? null;
                    $type = strtolower($e['type'] ?? 'event');
                    $h = isset($e['height']) ? floatval($e['height']) : null;
                    if ($time) $events[] = ['time' => $time, 'type' => $type, 'height' => $h];
                }
            } elseif (!empty($j['data']) && is_array($j['data'])) {
                foreach ($j['data'] as $e) {
                    $time = $e['time'] ?? $e['date'] ?? null;
                    $type = strtolower($e['type'] ?? 'event');
                    $h = isset($e['height']) ? floatval($e['height']) : null;
                    if ($time) $events[] = ['time' => $time, 'type' => $type, 'height' => $h];
                }
            }

            $tz = $j['timezone'] ?? null;
            return ['tz' => $tz, 'events' => $events];
        }
    }

    if (!function_exists('sv_vader_tide_noaa')) {
        function sv_vader_tide_noaa($lat, $lon) {
            $lat = floatval($lat); $lon = floatval($lon);
            if (!$lat && !$lon) return null;

            // Try to find nearby NOAA stations using MDAPI (bounding box).
            $d = 0.5; // ~50 km box
            $minlon = $lon - $d; $minlat = $lat - $d; $maxlon = $lon + $d; $maxlat = $lat + $d;
            $mdapi = add_query_arg([
                'bbox' => sprintf('%s,%s,%s,%s', $minlon, $minlat, $maxlon, $maxlat),
            ], 'https://api.tidesandcurrents.noaa.gov/mdapi/prod/webapi/stations.json');

            $res = wp_remote_get($mdapi, ['timeout' => 12]);
            if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) return null;
            $j = json_decode(wp_remote_retrieve_body($res), true);
            if (empty($j['stations']) || !is_array($j['stations'])) return null;

            // Find nearest station that supports tides (type contains 'TIDE' or has id)
            $best = null; $bestd = PHP_INT_MAX;
            foreach ($j['stations'] as $s) {
                if (empty($s['id']) || empty($s['lat']) || empty($s['lng'])) continue;
                // prefer tidal stations
                $stype = strtoupper($s['type'] ?? '');
                $slat = floatval($s['lat']); $slon = floatval($s['lng']);
                $dist = pow($slat - $lat, 2) + pow($slon - $lon, 2);
                if ($dist < $bestd) { $bestd = $dist; $best = $s; }
            }
            if (!$best || empty($best['id'])) return null;

            $station = $best['id'];
            // Request high/low predictions for today..+2 days
            $start = gmdate('Ymd', time());
            $end = gmdate('Ymd', time() + 2 * DAY_IN_SECONDS);
            $pred_url = add_query_arg([
                'product' => 'predictions',
                'application' => 'spelhubben-weather',
                'begin_date' => $start,
                'end_date' => $end,
                'station' => $station,
                'time_zone' => 'gmt',
                'units' => 'metric',
                'format' => 'json',
                'interval' => 'hilo',
            ], 'https://api.tidesandcurrents.noaa.gov/api/prod/datagetter');

            $r2 = wp_remote_get($pred_url, ['timeout' => 12]);
            if (is_wp_error($r2) || wp_remote_retrieve_response_code($r2) !== 200) return null;
            $pj = json_decode(wp_remote_retrieve_body($r2), true);
            if (empty($pj['predictions']) || !is_array($pj['predictions'])) return null;

            $events = [];
            foreach ($pj['predictions'] as $p) {
                $t = $p['t'] ?? ($p['time'] ?? null);
                $type = isset($p['type']) ? (strtoupper($p['type']) === 'H' ? 'high' : 'low') : null;
                $val = isset($p['v']) ? floatval($p['v']) : (isset($p['value']) ? floatval($p['value']) : null);
                if ($t) {
                    $iso = date('c', strtotime($t));
                    $events[] = ['time' => $iso, 'type' => $type ?? 'event', 'height' => $val];
                }
            }

            return ['tz' => 'GMT', 'events' => $events];
        }

    }
