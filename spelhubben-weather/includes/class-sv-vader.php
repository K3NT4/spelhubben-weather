<?php
// includes/class-sv-vader.php
if (!defined('ABSPATH')) exit;
if (!class_exists('SV_Vader_API')) {

require_once __DIR__ . '/providers.php';

class SV_Vader_API {
	private $cache_minutes;

	public function __construct($cache_minutes = 10) {
		$this->cache_minutes = max(1, intval($cache_minutes));
	}

	public function get_current_weather($ort = '', $lat = '', $lon = '', $providers = [], $yr_contact = '') {
		$ort = trim((string)$ort);
		$lat = trim((string)$lat);
		$lon = trim((string)$lon);

		$api_lang = sv_vader_api_lang();
		$salt     = sv_vader_cache_salt();

		$cache_key = 'sv_vader_cons_' . md5(json_encode([$ort,$lat,$lon,$providers,$api_lang,$salt]));
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
        // NEW: FMI
        if (in_array('fmi', $providers, true)) {
            $fmi = svp_fmi_current($lat, $lon);
            if ($fmi) $samples[] = $fmi;
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

	public function get_daily_forecast($ort = '', $lat = '', $lon = '', $days = 5) {
		$ort  = trim((string)$ort);
		$lat  = trim((string)$lat);
		$lon  = trim((string)$lon);
		$days = max(3, min(10, intval($days)));

		$api_lang = sv_vader_api_lang();
		$salt     = sv_vader_cache_salt();

		$cache_key = 'sv_vader_daily_' . md5(json_encode([$ort,$lat,$lon,$days,$api_lang,$salt]));
		$cached = get_transient($cache_key);
		if ($cached !== false) return $cached;

		if ($lat === '' || $lon === '') {
			$coords = $this->geocode($ort);
			if (is_wp_error($coords)) return [];
			$lat = $coords['lat'];
			$lon = $coords['lon'];
		}

		$list = svp_openmeteo_daily($lat, $lon, $days, $api_lang);
		set_transient($cache_key, $list, MINUTE_IN_SECONDS * $this->cache_minutes);
		return $list;
	}

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
		// (unchanged inline SVGs)
		$svg = '';
        if ($type === 'sun') {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><g fill="#111"><circle cx="32" cy="32" r="12"/><g opacity=".9"><rect x="31" y="2" width="2" height="10"/><rect x="31" y="52" width="2" height="10"/><rect x="2" y="31" width="10" height="2"/><rect x="52" y="31" width="10" height="2"/><rect x="10.3" y="10.3" width="2" height="10" transform="rotate(-45 11.3 15.3)"/><rect x="51.7" y="43.7" width="2" height="10" transform="rotate(-45 52.7 48.7)"/><rect x="43.7" y="10.3" width="10" height="2" transform="rotate(45 48.7 11.3)"/><rect x="10.3" y="51.7" width="10" height="2" transform="rotate(45 15.3 52.7)"/></g></g></svg>';
        } elseif ($type === 'cloud') {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path fill="#111" d="M22 48h24a10 10 0 0 0 0-20 14 14 0 0 0-27.3-3.8A12 12 0 0 0 22 48z"/></svg>';
        } elseif ($type === 'rain') {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path fill="#111" d="M22 40h24a10 10 0 0 0 0-20 14 14 0 0 0-27.3-3.8A12 12 0 0 0 22 40z"/><g fill="#111" opacity=".9"><path d="M22 46l-2 6"/><path d="M30 46l-2 6"/><path d="M38 46l-2 6"/><path d="M46 46l-2 6"/></g></svg>';
        } elseif ($type === 'snow') {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path fill="#111" d="M22 40h24a10 10 0 0 0 0-20 14 14 0 0 0-27.3-3.8A12 12 0 0 0 22 40z"/><g fill="#111" opacity=".9"><circle cx="24" cy="48" r="2"/><circle cx="32" cy="48" r="2"/><circle cx="40" cy="48" r="2"/></g></svg>';
        } else {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path fill="#111" d="M22 40h24a10 10 0 0 0 0-20 14 14 0 0 0-27.3-3.8A12 12 0 0 0 22 40z"/><path fill="#111" d="M32 42l-6 12h6l-2 8 8-14h-6l2-6z"/></svg>';
        }
		return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
	}
}
}
