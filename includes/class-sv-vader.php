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
		$providers = $this->normalize_providers($providers);

		$api_lang = sv_vader_api_lang();
		$cache_key = 'sv_vader_cons_' . md5(json_encode([$ort,$lat,$lon,$providers,$api_lang]));
		$cached = sv_vader_cache_get($cache_key);
		if ($cached !== false) {
			sv_vader_stats_hit();
			return $cached;
		}

		if ($lat === '' || $lon === '') {
			$coords = $this->geocode($ort);
			if (is_wp_error($coords)) return $coords;
			$lat  = $coords['lat'];
			$lon  = $coords['lon'];
			$name = $coords['name'];
		} else {
			$name = $ort;
		}

		$opts = sv_vader_get_options();
		$samples = [];
		$attempted = 0;
		foreach ($providers as $provider) {
			$fetched = $this->fetch_provider_current($provider, $lat, $lon, $api_lang, $yr_contact, $opts);
			if (($fetched['status'] ?? '') !== 'missing_key') {
				$attempted++;
			}
			if (($fetched['status'] ?? '') === 'ok' && !empty($fetched['data'])) {
				$samples[] = $fetched['data'];
			}
		}

		if (empty($samples)) {
			return new WP_Error('sv_vader_no_sources', __('Could not fetch weather data from the selected providers.', 'spelhubben-weather'));
		}

		$cons = sv_vader_consensus($samples);

		$out = array_merge([
			'name' => $name ?: $ort,
			'lat'  => $lat,
			'lon'  => $lon,
		], $cons);

		sv_vader_cache_set($cache_key, $out, MINUTE_IN_SECONDS * $this->cache_minutes);
		sv_vader_stats_miss($attempted, $out['name'] ?? $ort, $lat, $lon);
		return $out;
	}

	/**
	 * Get individual provider data (for comparison/debugging)
	 */
	public function get_provider_details($ort = '', $lat = '', $lon = '', $providers = [], $yr_contact = '') {
		$ort = trim((string)$ort);
		$lat = trim((string)$lat);
		$lon = trim((string)$lon);
		$providers = $this->normalize_providers($providers);

		$api_lang = sv_vader_api_lang();
		$cache_key = 'sv_vader_details_' . md5(json_encode([$ort,$lat,$lon,$providers,$api_lang]));
		$cached = sv_vader_cache_get($cache_key);
		if ($cached !== false) {
			sv_vader_stats_hit();
			return $cached;
		}

		if ($lat === '' || $lon === '') {
			$coords = $this->geocode($ort);
			if (is_wp_error($coords)) return $coords;
			$lat  = $coords['lat'];
			$lon  = $coords['lon'];
			$name = $coords['name'];
		} else {
			$name = $ort;
		}

		$opts = sv_vader_get_options();
		$details = [];
		$statuses = [];
		$attempted = 0;
		foreach ($providers as $provider) {
			$fetched = $this->fetch_provider_current($provider, $lat, $lon, $api_lang, $yr_contact, $opts);
			$statuses[$provider] = $fetched['status'];
			if (($fetched['status'] ?? '') !== 'missing_key') {
				$attempted++;
			}
			$details[$provider] = !empty($fetched['data']) ? $fetched['data'] : ['_status' => $fetched['status']];
		}

		$out = [
			'name' => $name ?: $ort,
			'lat'  => $lat,
			'lon'  => $lon,
			'providers' => $details,
			'provider_statuses' => $statuses,
		];

		sv_vader_cache_set($cache_key, $out, MINUTE_IN_SECONDS * $this->cache_minutes);
		sv_vader_stats_miss($attempted, $name ?? $ort, $lat, $lon);
		return $out;
	}

	public function get_daily_forecast($ort = '', $lat = '', $lon = '', $days = 5) {
		$ort  = trim((string)$ort);
		$lat  = trim((string)$lat);
		$lon  = trim((string)$lon);
		$days = max(3, min(10, intval($days)));

		$api_lang = sv_vader_api_lang();
		$cache_key = 'sv_vader_daily_' . md5(json_encode([$ort,$lat,$lon,$days,$api_lang]));
		$cached = sv_vader_cache_get($cache_key);
		if ($cached !== false) {
			sv_vader_stats_hit();
			return $cached;
		}

		if ($lat === '' || $lon === '') {
			$coords = $this->geocode($ort);
			if (is_wp_error($coords)) return [];
			$lat  = $coords['lat'];
			$lon  = $coords['lon'];
			$name = $coords['name'];
		} else {
			$name = $ort;
		}

		$list = sv_vader_openmeteo_daily($lat, $lon, $days, $api_lang);
		sv_vader_cache_set($cache_key, $list, MINUTE_IN_SECONDS * $this->cache_minutes);
		sv_vader_stats_miss(1, $name, $lat, $lon);
		return $list;
	}

	public function get_hourly_forecast($ort = '', $lat = '', $lon = '', $hours = 24) {
		$ort = trim((string)$ort);
		$lat = trim((string)$lat);
		$lon = trim((string)$lon);
		$hours = max(3, min(24, intval($hours)));

		$api_lang = sv_vader_api_lang();
		$cache_key = 'sv_vader_hourly_' . md5(json_encode([$ort,$lat,$lon,$hours,$api_lang]));
		$cached = sv_vader_cache_get($cache_key);
		if ($cached !== false) {
			sv_vader_stats_hit();
			return $cached;
		}

		if ($lat === '' || $lon === '') {
			$coords = $this->geocode($ort);
			if (is_wp_error($coords)) return [];
			$lat = $coords['lat'];
			$lon = $coords['lon'];
			$name = $coords['name'];
		} else {
			$name = $ort;
		}

		$list = sv_vader_openmeteo_hourly($lat, $lon, $hours, $api_lang);
		sv_vader_cache_set($cache_key, $list, MINUTE_IN_SECONDS * $this->cache_minutes);
		sv_vader_stats_miss(1, $name, $lat, $lon);
		return $list;
	}

	/**
	 * Return tide events for a location (uses providers helper)
	 * Returns null on failure or an array with 'tz' and 'events'
	 */
	public function get_tides($ort = '', $lat = '', $lon = '') {
		$ort = trim((string)$ort);
		$lat = trim((string)$lat);
		$lon = trim((string)$lon);

		$cache_key = 'sv_vader_tides_res_' . md5(json_encode([$ort,$lat,$lon]));
		$cached = sv_vader_cache_get($cache_key);
		if ($cached !== false) {
			sv_vader_stats_hit();
			return $cached;
		}

		if ($lat === '' || $lon === '') {
			$coords = $this->geocode($ort);
			if (is_wp_error($coords)) return $coords;
			$lat  = $coords['lat'];
			$lon  = $coords['lon'];
			$name = $coords['name'];
		} else {
			$name = $ort;
		}

		$tides = sv_vader_fetch_tides($lat, $lon);
		if ($tides === null) return null;

		sv_vader_cache_set($cache_key, $tides, MINUTE_IN_SECONDS * max(5, intval(sv_vader_get_options()['tide_cache_minutes'] ?? 60)));
		sv_vader_stats_miss(1, $name, $lat, $lon);
		return $tides;
	}

	private function normalize_providers($providers) {
		if (!is_array($providers)) {
			$providers = (array) $providers;
		}

		$providers = array_filter(array_map(static function ($provider) {
			return trim(strtolower((string) $provider));
		}, $providers), static function ($provider) {
			return $provider !== '';
		});

		$providers = array_values(array_unique($providers));
		$order = function_exists('sv_vader_provider_ids')
			? sv_vader_provider_ids()
			: ['openmeteo','smhi','yr','metno_nowcast','fmi','openweathermap','weatherapi'];
		$rank = array_flip($order);
		usort($providers, static function ($a, $b) use ($rank) {
			$rank_a = $rank[$a] ?? PHP_INT_MAX;
			$rank_b = $rank[$b] ?? PHP_INT_MAX;
			if ($rank_a === $rank_b) {
				return strcmp($a, $b);
			}
			return $rank_a <=> $rank_b;
		});

		return $providers;
	}

	private function fetch_provider_current($provider, $lat, $lon, $api_lang, $yr_contact, array $opts) {
		if (function_exists('sv_vader_provider_key_missing') && sv_vader_provider_key_missing($provider, $opts)) {
			return ['status' => 'missing_key', 'data' => null];
		}

		$data = null;
		if ($provider === 'openmeteo') {
			$data = sv_vader_openmeteo_current($lat, $lon, $api_lang);
		} elseif ($provider === 'smhi') {
			$data = sv_vader_smhi_current($lat, $lon);
		} elseif ($provider === 'yr') {
			$data = sv_vader_yr_current($lat, $lon, $yr_contact);
		} elseif ($provider === 'metno_nowcast') {
			$data = sv_vader_metno_nowcast_current($lat, $lon, $yr_contact);
		} elseif ($provider === 'fmi') {
			$data = sv_vader_fmi_current($lat, $lon);
		} elseif ($provider === 'openweathermap') {
			$data = sv_vader_openweathermap_current($lat, $lon, $api_lang, trim((string)($opts['owm_api_key'] ?? '')));
		} elseif ($provider === 'weatherapi') {
			$data = sv_vader_weatherapi_current($lat, $lon, $api_lang, trim((string)($opts['weatherapi_api_key'] ?? '')));
		}

		if (is_array($data) && !empty($data['_status']) && $data['_status'] !== 'ok') {
			return ['status' => $data['_status'], 'data' => null];
		}

		return $data ? ['status' => 'ok', 'data' => $data] : ['status' => 'no_data', 'data' => null];
	}

	private function geocode($q) {
		$api_lang = sv_vader_api_lang();
		// Include language in cache key to avoid stale translations
		$geocode_cache_key = 'sv_vader_geocode_' . md5($q . $api_lang);

		// Check cache first
		$cached = sv_vader_cache_get($geocode_cache_key);
		if ($cached !== false) return $cached;

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
		$result = [
			'lat'  => (string)$r['latitude'],
			'lon'  => (string)$r['longitude'],
			'name' => trim(($r['name'] ?? '') . (isset($r['country_code']) ? ', ' . $r['country_code'] : ''))
		];
		
		// Cache geocoding result for 7 days
		sv_vader_cache_set($geocode_cache_key, $result, DAY_IN_SECONDS * 7);
		return $result;
	}

	public function map_icon_url($code) {
		if ($code === null) return '';
		$type = 'cloud';
		// Clear sky
		if (in_array($code, [0,1], true)) { $type = 'sun';
		// Partly cloudy
		} elseif (in_array($code, [2], true)) { $type = 'partly-cloudy';
		// Fog/Mist (no drizzle/rain, just fog)
		} elseif (in_array($code, [45,48], true)) { $type = 'fog';
		// Overcast
		} elseif (in_array($code, [3], true)) { $type = 'cloud';
		// Drizzle (light rain)
		} elseif (in_array($code, [51,53,55], true)) { $type = 'rain';
		// Rain
		} elseif (in_array($code, [61,63,65,80,81,82], true)) { $type = 'rain';
		// Sleet (rain+snow mix)
		} elseif (in_array($code, [66,67], true)) { $type = 'sleet';
		// Snow
		} elseif (in_array($code, [71,73,75,77,85,86], true)) { $type = 'snow';
		// Thunderstorm
		} elseif (in_array($code, [95,96,99], true)) { $type = 'storm'; }

		return $this->build_icon_url($type);
	}

	private function build_icon_url($type) {
		// Get icon style from options (cached at renderer level, but safe here too)
		static $style = null;
		if ($style === null) {
			$opts = sv_vader_get_options();
			$style = $opts['icon_style'] ?? 'classic';
		}

		// Try modern style first if selected
		if ($style !== 'classic') {
			$modern_rel  = 'assets/icons/' . $style . '-' . $type . '.svg';
			$modern_path = trailingslashit(SV_VADER_DIR) . $modern_rel;
			$modern_url  = trailingslashit(SV_VADER_URL) . $modern_rel;
			if (file_exists($modern_path)) return $modern_url;
		}

		// Fallback to classic style
		$rel  = 'assets/icons/' . $type . '.svg';
		$path = trailingslashit(SV_VADER_DIR) . $rel;
		$url  = trailingslashit(SV_VADER_URL) . $rel;

		if (file_exists($path)) return $url;
		return $this->svg_data_uri($type);
	}

	private function svg_data_uri($type) {
		// Fallback inline SVG data URIs for icon types
		$svg = '';
        if ($type === 'sun') {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><g fill="#111"><circle cx="32" cy="32" r="12"/><g opacity=".9"><rect x="31" y="2" width="2" height="10"/><rect x="31" y="52" width="2" height="10"/><rect x="2" y="31" width="10" height="2"/><rect x="52" y="31" width="10" height="2"/><rect x="10.3" y="10.3" width="2" height="10" transform="rotate(-45 11.3 15.3)"/><rect x="51.7" y="43.7" width="2" height="10" transform="rotate(-45 52.7 48.7)"/><rect x="43.7" y="10.3" width="10" height="2" transform="rotate(45 48.7 11.3)"/><rect x="10.3" y="51.7" width="10" height="2" transform="rotate(45 15.3 52.7)"/></g></g></svg>';
        } elseif ($type === 'partly-cloudy') {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><g fill="#111"><circle cx="20" cy="24" r="6"/><g opacity=".7"><rect x="19" y="8" width="2" height="8"/><rect x="19" y="40" width="2" height="8"/><rect x="4" y="23" width="8" height="2"/><rect x="32" y="23" width="8" height="2"/><rect x="9.2" y="13.2" width="2" height="8" transform="rotate(-45 10.2 17.2)"/><rect x="27.8" y="31.8" width="2" height="8" transform="rotate(-45 28.8 35.8)"/><rect x="27.8" y="13.2" width="8" height="2" transform="rotate(45 31.8 14.2)"/><rect x="9.2" y="31.8" width="8" height="2" transform="rotate(45 13.2 32.8)"/></g></g><path fill="#111" d="M30 44h20a8 8 0 0 0 0-16 11 11 0 0 0-21.6-3A10 10 0 0 0 30 44z" opacity=".8"/></svg>';
        } elseif ($type === 'cloud') {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path fill="#111" d="M22 48h24a10 10 0 0 0 0-20 14 14 0 0 0-27.3-3.8A12 12 0 0 0 22 48z"/></svg>';
        } elseif ($type === 'fog') {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path fill="#111" d="M22 40h24a10 10 0 0 0 0-20 14 14 0 0 0-27.3-3.8A12 12 0 0 0 22 40z"/><g stroke="#111" stroke-width="1.5" stroke-linecap="round" opacity=".6"><line x1="20" y1="44" x2="40" y2="44"/><line x1="18" y1="48" x2="42" y2="48"/><line x1="20" y1="52" x2="40" y2="52"/></g></svg>';
        } elseif ($type === 'rain') {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path fill="#111" d="M22 40h24a10 10 0 0 0 0-20 14 14 0 0 0-27.3-3.8A12 12 0 0 0 22 40z"/><g fill="#111" opacity=".9"><path d="M22 46l-2 6"/><path d="M30 46l-2 6"/><path d="M38 46l-2 6"/><path d="M46 46l-2 6"/></g></svg>';
        } elseif ($type === 'sleet') {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path fill="#111" d="M22 40h24a10 10 0 0 0 0-20 14 14 0 0 0-27.3-3.8A12 12 0 0 0 22 40z"/><g opacity=".8"><circle cx="26" cy="50" r="1.5" fill="#111"/><path d="M34 46l-2 6" stroke="#111" stroke-width="1.5" fill="none" stroke-linecap="round"/><circle cx="42" cy="50" r="1.5" fill="#111"/><path d="M46 48l-2 6" stroke="#111" stroke-width="1.5" fill="none" stroke-linecap="round"/></g></svg>';
        } elseif ($type === 'snow') {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path fill="#111" d="M22 40h24a10 10 0 0 0 0-20 14 14 0 0 0-27.3-3.8A12 12 0 0 0 22 40z"/><g fill="#111" opacity=".9"><circle cx="24" cy="48" r="2"/><circle cx="32" cy="48" r="2"/><circle cx="40" cy="48" r="2"/></g></svg>';
        } elseif ($type === 'hail') {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path fill="#111" d="M22 40h24a10 10 0 0 0 0-20 14 14 0 0 0-27.3-3.8A12 12 0 0 0 22 40z"/><g fill="#111" opacity=".8"><circle cx="26" cy="48" r="1.8"/><circle cx="34" cy="50" r="1.8"/><circle cx="42" cy="48" r="1.8"/><circle cx="50" cy="50" r="1.8"/><circle cx="30" cy="54" r="1.5"/><circle cx="46" cy="54" r="1.5"/></g></svg>';
        } else {
            // Storm (thunderbolt)
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path fill="#111" d="M22 40h24a10 10 0 0 0 0-20 14 14 0 0 0-27.3-3.8A12 12 0 0 0 22 40z"/><path fill="#111" d="M32 42l-6 12h6l-2 8 8-14h-6l2-6z"/></svg>';
        }
		return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
	}
}

// --- Stats helpers for Performance Dashboard ---
if ( ! function_exists( 'sv_vader_stats_get' ) ) {
	function sv_vader_stats_get() : array {
		$raw = get_option( 'sv_vader_stats', [] );
		$defaults = [
			'hits'   => 0,
			'misses' => 0,
			'api_calls' => 0,
			'per_day' => [], // key: Y-m-d => ['hits'=>int,'misses'=>int,'api_calls'=>int]
			'recent' => [],  // list of ['place','lat','lon','time']
		];
		return wp_parse_args( $raw, $defaults );
	}
}

if ( ! function_exists( 'sv_vader_stats_save' ) ) {
	function sv_vader_stats_save( array $data ) : void {
		update_option( 'sv_vader_stats', $data, false );
	}
}

if ( ! function_exists( 'sv_vader_stats_prune_days' ) ) {
	function sv_vader_stats_prune_days( array $per_day, int $days = 14 ) : array {
		ksort( $per_day );
		$keys = array_keys( $per_day );
		if ( count( $keys ) <= $days ) return $per_day;
		$excess = array_slice( $keys, 0, count( $keys ) - $days );
		foreach ( $excess as $k ) {
			unset( $per_day[ $k ] );
		}
		return $per_day;
	}
}

if ( ! function_exists( 'sv_vader_stats_hit' ) ) {
	function sv_vader_stats_hit() : void {
		$stats = sv_vader_stats_get();
		$today = gmdate( 'Y-m-d' );
		$stats['hits']++;
		if ( ! isset( $stats['per_day'][ $today ] ) ) {
			$stats['per_day'][ $today ] = [ 'hits' => 0, 'misses' => 0, 'api_calls' => 0 ];
		}
		$stats['per_day'][ $today ]['hits']++;
		$stats['per_day'] = sv_vader_stats_prune_days( $stats['per_day'] );
		sv_vader_stats_save( $stats );
	}
}

if ( ! function_exists( 'sv_vader_stats_miss' ) ) {
	function sv_vader_stats_miss( int $providers_count = 0, string $place = '', string $lat = '', string $lon = '' ) : void {
		$stats = sv_vader_stats_get();
		$today = gmdate( 'Y-m-d' );
		$stats['misses']++;
		$stats['api_calls'] += max( 1, $providers_count );
		if ( ! isset( $stats['per_day'][ $today ] ) ) {
			$stats['per_day'][ $today ] = [ 'hits' => 0, 'misses' => 0, 'api_calls' => 0 ];
		}
		$stats['per_day'][ $today ]['misses']++;
		$stats['per_day'][ $today ]['api_calls'] += max( 1, $providers_count );
		$stats['per_day'] = sv_vader_stats_prune_days( $stats['per_day'] );

		$stats['recent'] = is_array( $stats['recent'] ) ? $stats['recent'] : [];
		array_unshift( $stats['recent'], [
			'place' => $place,
			'lat'   => $lat,
			'lon'   => $lon,
			'time'  => time(),
		] );
		$stats['recent'] = array_slice( $stats['recent'], 0, 5 );

		sv_vader_stats_save( $stats );
	}
}

if ( ! function_exists( 'sv_vader_stats_reset' ) ) {
	function sv_vader_stats_reset() : void {
		delete_option( 'sv_vader_stats' );
	}
}
}
