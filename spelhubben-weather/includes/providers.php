<?php
// includes/providers.php - Weather data providers and normalization functions
if (!defined('ABSPATH')) exit;

if (!function_exists('svp_openmeteo_current')) {
    function svp_openmeteo_current($lat, $lon, $locale = 'en') {
        $url = add_query_arg([
            'latitude'  => $lat,
            'longitude' => $lon,
            'current'   => 'temperature_2m,wind_speed_10m,weather_code,precipitation,cloud_cover',
            'timezone'  => 'Europe/Stockholm',
            'lang'      => $locale
        ], 'https://api.open-meteo.com/v1/forecast');

        $res = wp_remote_get($url, ['timeout' => 10]);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) return null;
        $j = json_decode(wp_remote_retrieve_body($res), true);
        if (empty($j['current'])) return null;

        $c = $j['current'];
        return [
            'temp'   => isset($c['temperature_2m']) ? floatval($c['temperature_2m']) : null,
            'wind'   => isset($c['wind_speed_10m']) ? floatval($c['wind_speed_10m']) : null,
            'precip' => isset($c['precipitation']) ? floatval($c['precipitation']) : null,
            'cloud'  => isset($c['cloud_cover']) ? intval($c['cloud_cover']) : null,
            'code'   => isset($c['weather_code']) ? intval($c['weather_code']) : null,
            'desc'   => null,
        ];
    }
}

if (!function_exists('svp_smhi_current')) {
    function svp_smhi_current($lat, $lon) {
        $url = sprintf(
            'https://opendata.smhi.se/meteorological/forecast/api/category/pmp3g/version/2/geotype/point/lon/%s/lat/%s/data.json',
            rawurlencode($lon), rawurlencode($lat)
        );
        $res = wp_remote_get($url, [
            'timeout'    => 12,
            'user-agent' => 'Spelhubben-Weather/1.0'
        ]);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) return null;
        $j = json_decode(wp_remote_retrieve_body($res), true);
        if (empty($j['timeSeries'][0])) return null;

        $now = current_time('timestamp', true);
        $nearest = null; $mindiff = PHP_INT_MAX;
        foreach ($j['timeSeries'] as $ts) {
            $t = strtotime($ts['validTime']);
            $diff = abs($t - $now);
            if ($diff < $mindiff) { $mindiff = $diff; $nearest = $ts; }
        }
        if (!$nearest || empty($nearest['parameters'])) return null;

        $map = [];
        foreach ($nearest['parameters'] as $p) {
            $map[$p['name']] = $p['values'][0];
        }

        // SMHI total cloud cover (oktas 0..8) → percent
        $cloud_pct = isset($map['tcc']) ? intval(round(($map['tcc'] / 8) * 100)) : null;

        return [
            'temp'   => isset($map['t']) ? floatval($map['t']) : null,
            'wind'   => isset($map['ws']) ? floatval($map['ws']) : null,
            'precip' => isset($map['pmean']) ? floatval($map['pmean']) : null,
            'cloud'  => $cloud_pct,
            'code'   => null,
            'desc'   => null,
        ];
    }
}

if (!function_exists('svp_yr_current')) {
    function svp_yr_current($lat, $lon, $contactUA = '') {
        $ua = 'Spelhubben-Weather/1.0';
        if ($contactUA) $ua .= ' (' . $contactUA . ')';

        $url = add_query_arg(['lat' => $lat, 'lon' => $lon], 'https://api.met.no/weatherapi/locationforecast/2.0/compact');

        $res = wp_remote_get($url, [
            'timeout' => 12,
            'headers' => ['User-Agent' => $ua]
        ]);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) return null;
        $j = json_decode(wp_remote_retrieve_body($res), true);
        if (empty($j['properties']['timeseries'][0])) return null;

        $now = current_time('timestamp', true);
        $nearest = null; $mindiff = PHP_INT_MAX;
        foreach ($j['properties']['timeseries'] as $ts) {
            $t = strtotime($ts['time']);
            $diff = abs($t - $now);
            if ($diff < $mindiff) { $mindiff = $diff; $nearest = $ts; }
        }
        if (!$nearest) return null;

        $inst   = $nearest['data']['instant']['details'] ?? [];
        $next1h = $nearest['data']['next_1_hours']['details'] ?? [];

        return [
            'temp'   => isset($inst['air_temperature']) ? floatval($inst['air_temperature']) : null,
            'wind'   => isset($inst['wind_speed']) ? floatval($inst['wind_speed']) : null,
            'precip' => isset($next1h['precipitation_amount']) ? floatval($next1h['precipitation_amount']) : null,
            'cloud'  => isset($inst['cloud_area_fraction']) ? intval(round($inst['cloud_area_fraction'])) : null,
            'code'   => null,
            'desc'   => null,
        ];
    }
}

/**
 * NEW: FMI (Finnish Meteorological Institute) via WFS timevaluepair
 * Uses bbox around point to pick nearest station.
 * Parameters:
 *  - t2m (°C), ws_10min (m/s), r_1h (mm), n_man (cloud oktas 0..8)
 */
if (!function_exists('svp_fmi_current')) {
    function svp_fmi_current($lat, $lon) {
        $lat = floatval($lat); $lon = floatval($lon);
        if (!$lat && !$lon) return null;

        $d = 0.06; // ~ ca 6–7 km
        $bbox = ($lon - $d) . ',' . ($lat - $d) . ',' . ($lon + $d) . ',' . ($lat + $d) . ',epsg:4326';

        $url = add_query_arg([
            'service'        => 'WFS',
            'version'        => '2.0.0',
            'request'        => 'getFeature',
            'storedquery_id' => 'fmi::observations::weather::timevaluepair',
            'parameters'     => 't2m,ws_10min,r_1h,n_man',
            'bbox'           => $bbox,
        ], 'https://opendata.fmi.fi/wfs');

        $res = wp_remote_get($url, ['timeout'=>14,'user-agent'=>'Spelhubben-Weather/1.0 (FMI WFS)']);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) return null;

        $xml = wp_remote_retrieve_body($res);
        if (!is_string($xml) || $xml==='') return null;

        $sx = @simplexml_load_string($xml);
        if (!$sx) return null;
        $sx->registerXPathNamespace('wml2','http://www.opengis.net/waterml/2.0');
        $sx->registerXPathNamespace('gml', 'http://www.opengis.net/gml/3.2');

        $out = ['temp'=>null,'wind'=>null,'precip'=>null,'cloud'=>null,'code'=>null,'desc'=>null];
        $series = $sx->xpath('//wml2:MeasurementTimeseries');
        if (is_array($series)) {
            foreach ($series as $ts) {
                $attrs = $ts->attributes('gml', true);
                $gid   = isset($attrs['id']) ? strtolower((string)$attrs['id']) : '';
                $vals  = $ts->xpath('.//wml2:point/wml2:MeasurementTVP/wml2:value');
                if (!$vals || !count($vals)) continue;
                $val = (string)$vals[count($vals)-1];

                if (strpos($gid,'t2m')!==false)          $out['temp']   = is_numeric($val)?(float)$val:null;
                elseif (strpos($gid,'ws_10min')!==false) $out['wind']   = is_numeric($val)?(float)$val:null;
                elseif (strpos($gid,'r_1h')!==false)     $out['precip'] = is_numeric($val)?(float)$val:null;
                elseif (strpos($gid,'n_man')!==false) {
                    $oktas = is_numeric($val)?(float)$val:null;
                    $out['cloud'] = ($oktas!==null) ? (int)round(($oktas/8)*100) : null;
                }
            }
        }
        return ($out['temp']===null && $out['wind']===null && $out['precip']===null && $out['cloud']===null) ? null : $out;
    }
}

/**
 * WMO code → English text (base language). Wrapped in i18n for translation.
 */
if (!function_exists('svp_wmo_text')) {
    function svp_wmo_text($code) {
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
if (!function_exists('svp_wmo_text_sv')) {
    function svp_wmo_text_sv($code) {
        return svp_wmo_text($code);
    }
}

if (!function_exists('svp_consensus')) {
    function svp_consensus(array $samples) {
        $nums = ['temp','wind','precip','cloud'];
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
            $out['desc'] = svp_wmo_text($om);
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

if (!function_exists('svp_openmeteo_daily')) {
    /**
     * Fetch daily forecast (max/min, WMO code) for N days (3..10).
     * Returns: [ ['date'=>'YYYY-MM-DD','tmax'=>..,'tmin'=>..,'code'=>int|null,'desc'=>string], ... ]
     */
    function svp_openmeteo_daily($lat, $lon, $days = 5, $locale = 'en') {
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
                    'desc' => ($code !== null) ? svp_wmo_text($code) : ''
                ];
            }
        }
        return $out;
    }
}
