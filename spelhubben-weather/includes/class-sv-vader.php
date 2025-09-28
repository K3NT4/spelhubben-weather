<?php
// includes/class-sv-vader.php
if (!defined('ABSPATH')) exit;
if (!class_exists('SV_Vader_API')) {

require_once __DIR__ . '/providers.php'; // remove if not used

class SV_Vader_API {
    /** @var int */
    private $cache_minutes;

    public function __construct($cache_minutes = 10) {
        $this->cache_minutes = max(1, intval($cache_minutes));
    }

    /**
     * @param string $ort
     * @param string $lat
     * @param string $lon
     * @param array  $providers ex: ['openmeteo','smhi','yr']
     * @param string $yr_contact
     * @return array|WP_Error
     */
    public function get_current_weather($ort = '', $lat = '', $lon = '', $providers = [], $yr_contact = '') {
        $ort = trim((string)$ort);
        $lat = trim((string)$lat);
        $lon = trim((string)$lon);

        $cache_key = 'sv_vader_cons_' . md5(json_encode([$ort,$lat,$lon,$providers]));
        $cached = get_transient($cache_key);
        if ($cached !== false) return $cached;

        if ($lat === '' || $lon === '') {
            $coords = $this->geocode($ort);
            if (is_wp_error($coords)) return $coords;
            $lat  = $coords['lat'];
            $lon  = $coords['lon'];
            $name = $coords['name'];
        } else {
            $name = $ort;
        }

        $samples = [];
        $api_lang = sv_vader_api_lang();

        if (in_array('openmeteo', $providers, true)) {
            $om = svp_openmeteo_current($lat, $lon, $api_lang);
            if ($om) $samples[] = $om;
        }
        if (in_array('smhi', $providers, true)) {
            $sm = svp_smhi_current($lat, $lon);
            if ($sm) $samples[] = $sm;
        }
        if (in_array('yr', $providers, true)) {
            $yr = svp_yr_current($lat, $lon, $yr_contact);
            if ($yr) $samples[] = $yr;
        }

        if (empty($samples)) {
            return new WP_Error('sv_vader_no_sources', __('Could not fetch weather data from the selected providers.', 'spelhubben-weather'));
        }

        $cons = svp_consensus($samples);

        $out = array_merge([
            'name' => $name ?: $ort,
            'lat'  => $lat,
            'lon'  => $lon,
        ], $cons);

        set_transient($cache_key, $out, MINUTE_IN_SECONDS * $this->cache_minutes);
        return $out;
    }

    /**
     * Fetches daily forecast (3..10 days) via Open-Meteo.
     */
    public function get_daily_forecast($ort = '', $lat = '', $lon = '', $days = 5) {
        $ort  = trim((string)$ort);
        $lat  = trim((string)$lat);
        $lon  = trim((string)$lon);
        $days = max(3, min(10, intval($days)));

        $cache_key = 'sv_vader_daily_' . md5(json_encode([$ort,$lat,$lon,$days]));
        $cached = get_transient($cache_key);
        if ($cached !== false) return $cached;

        if ($lat === '' || $lon === '') {
            $coords = $this->geocode($ort);
            if (is_wp_error($coords)) return [];
            $lat = $coords['lat'];
            $lon = $coords['lon'];
        }

        $list = svp_openmeteo_daily($lat, $lon, $days, sv_vader_api_lang());
        set_transient($cache_key, $list, MINUTE_IN_SECONDS * $this->cache_minutes);
        return $list;
    }

    /**
     * Geocoding via Open-Meteo.
     */
    private function geocode($q) {
        $url = add_query_arg([
            'name'     => $q,
            'count'    => 1,
            'language' => sv_vader_api_lang(),
            'format'   => 'json'
        ], 'https://geocoding-api.open-meteo.com/v1/search');

        $res = wp_remote_get($url, ['timeout'=>10]);
        if (is_wp_error($res)) return $res;

        $data = json_decode(wp_remote_retrieve_body($res), true);
        if (empty($data['results'][0])) {
            return new WP_Error('sv_vader_geocode', __('Could not find the place.', 'spelhubben-weather'));
        }

        $r = $data['results'][0];
        return [
            'lat'  => (string)$r['latitude'],
            'lon'  => (string)$r['longitude'],
            'name' => trim(($r['name'] ?? '') . (isset($r['country_code']) ? ', ' . $r['country_code'] : ''))
        ];
    }

    /**
     * Icon URL for WMO code (local SVGs or inline fallback).
     */
    public function map_icon_url($code) {
        if ($code === null) return '';
        $type = 'cloud';
        if (in_array($code, [0,1], true)) { $type = 'sun';
        } elseif (in_array($code, [2,3,45,48], true)) { $type = 'cloud';
        } elseif (in_array($code, [51,53,55,61,63,65,80,81,82,66,67], true)) { $type = 'rain';
        } elseif (in_array($code, [71,73,75,77,85,86], true)) { $type = 'snow';
        } elseif (in_array($code, [95,96,99], true)) { $type = 'storm'; }

        $rel  = 'assets/icons/' . $type . '.svg';
        $path = trailingslashit(SV_VADER_DIR) . $rel;
        $url  = trailingslashit(SV_VADER_URL) . $rel;

        if (file_exists($path)) return $url;
        return $this->svg_data_uri($type);
    }

    private function svg_data_uri($type) {
        $svg = '';
        if ($type === 'sun') {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><g fill="#111"><circle cx="32" cy="32" r="12"/><g opacity=".9"><rect x="31" y="2" width="2" height="10"/><rect x="31" y="52" width="2" height="10"/><rect x="2" y="31" width="10" height="2"/><rect x="52" y="31" width="10" height="2"/><rect x="10.3" y="10.3" width="2" height="10" transform="rotate(-45 11.3 15.3)"/><rect x="51.7" y="43.7" width="2" height="10" transform="rotate(-45 52.7 48.7)"/><rect x="43.7" y="10.3" width="10" height="2" transform="rotate(45 48.7 11.3)"/><rect x="10.3" y="51.7" width="10" height="2" transform="rotate(45 15.3 52.7)"/></g></g></svg>';
        } elseif ($type === 'cloud') {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path fill="#111" d="M22 48h24a10 10 0 0 0 0-20 14 14 0 0 0-27.3-3.8A12 12 0 0 0 22 48z"/></svg>';
        } elseif ($type === 'rain') {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path fill="#111" d="M22 40h24a10 10 0 0 0 0-20 14 14 0 0 0-27.3-3.8A12 12 0 0 0 22 40z"/><g fill="#111" opacity=".9"><path d="M22 46l-2 6"/><path d="M30 46l-2 6"/><path d="M38 46l-2 6"/><path d="M46 46l-2 6"/></g></svg>';
        } elseif ($type === 'snow') {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path fill="#111" d="M22 40h24a10 10 0 0 0 0-20 14 14 0 0 0-27.3-3.8A12 12 0 0 0 22 40z"/><g fill="#111" opacity=".9"><circle cx="24" cy="48" r="2"/><circle cx="32" cy="48" r="2"/><circle cx="40" cy="48" r="2"/></g></svg>';
        } else { // storm
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path fill="#111" d="M22 40h24a10 10 0 0 0 0-20 14 14 0 0 0-27.3-3.8A12 12 0 0 0 22 40z"/><path fill="#111" d="M32 42l-6 12h6l-2 8 8-14h-6l2-6z"/></svg>';
        }
        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }
}

/**
 * Provider helpers + consensus + forecast.
 * All user-visible text goes through i18n (sv_vader_wmo_text()).
 */

if (!function_exists('svp_openmeteo_current')) {
    function svp_openmeteo_current($lat, $lon, $locale = 'sv') {
        $url = add_query_arg([
            'latitude'  => $lat,
            'longitude' => $lon,
            'current'   => 'temperature_2m,wind_speed_10m,weather_code,precipitation,cloud_cover',
            'timezone'  => wp_timezone_string() ?: 'Europe/Stockholm',
            'lang'      => $locale
        ], 'https://api.open-meteo.com/v1/forecast');

        $res = wp_remote_get($url, ['timeout'=>10]);
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
        $res = wp_remote_get($url, ['timeout'=>12, 'user-agent'=>'Spelhubben-Weather/1.0']);
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
        foreach ($nearest['parameters'] as $p) { $map[$p['name']] = $p['values'][0]; }

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

        $url = add_query_arg(['lat'=>$lat, 'lon'=>$lon], 'https://api.met.no/weatherapi/locationforecast/2.0/compact');

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

if (!function_exists('svp_consensus')) {
    function svp_consensus(array $samples) {
        $nums = ['temp','wind','precip','cloud'];
        $out = [];
        foreach ($nums as $k) {
            $vals = array_values(array_filter(array_map(function($s) use ($k){ return $s[$k] ?? null; }, $samples), function($v){ return $v !== null; }));
            if ($vals) {
                sort($vals, SORT_NUMERIC);
                $mid = (int) floor((count($vals)-1)/2);
                $out[$k] = $vals[$mid];
            } else {
                $out[$k] = null;
            }
        }

        // Description/icon: prioritize Open-Meteo WMO code
        $om = null;
        foreach ($samples as $s) { if (isset($s['code']) && $s['code'] !== null) { $om = $s['code']; break; } }
        if ($om !== null) {
            $out['code'] = $om;
            $out['desc'] = sv_vader_wmo_text($om);
        } else {
            $cloud = $out['cloud'];
            $prec  = $out['precip'];
            if ($prec !== null && $prec >= 0.1) {
                $out['desc'] = __('Precipitation', 'spelhubben-weather');
            } elseif ($cloud !== null) {
                if ($cloud <= 20)      $out['desc'] = __('Clear', 'spelhubben-weather');
                elseif ($cloud <= 60)  $out['desc'] = __('Partly cloudy', 'spelhubben-weather');
                else                   $out['desc'] = __('Overcast', 'spelhubben-weather');
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
     * Returns list:
     * [ ['date'=>'YYYY-MM-DD','tmax'=>int|null,'tmin'=>int|null,'code'=>int|null,'desc'=>string], ... ]
     */
    function svp_openmeteo_daily($lat, $lon, $days = 5, $locale = 'sv') {
        $days = max(3, min(10, intval($days)));
        $url = add_query_arg([
            'latitude'      => $lat,
            'longitude'     => $lon,
            'daily'         => 'weather_code,temperature_2m_max,temperature_2m_min',
            'timezone'      => wp_timezone_string() ?: 'Europe/Stockholm',
            'forecast_days' => $days,
            'lang'          => $locale
        ], 'https://api.open-meteo.com/v1/forecast');

        $res = wp_remote_get($url, ['timeout'=>10]);
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
}
