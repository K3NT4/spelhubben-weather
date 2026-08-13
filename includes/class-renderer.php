<?php
// includes/class-renderer.php
if (!defined('ABSPATH')) exit;

if (!class_exists('SV_Vader_Renderer')) {

class SV_Vader_Renderer {

	public function render_shortcode($atts = []) {
		$opts = sv_vader_get_options();

		$a = shortcode_atts([
			'ort'        => $opts['default_ort'],
			'lat'        => '',
			'lon'        => '',
			'show'       => $opts['default_show'],
			'layout'     => $opts['default_layout'],
			'class'      => '',
			'map'        => $opts['map_default'] ? '1' : '0',
			'map_height' => (string) $opts['map_height'],
			'providers'  => function_exists('sv_vader_enabled_provider_ids')
				? implode(',', sv_vader_enabled_provider_ids($opts))
				: implode(',', array_keys(array_filter([
					'openmeteo'      => $opts['prov_openmeteo'],
					'smhi'           => $opts['prov_smhi'],
					'yr'             => $opts['prov_yr'],
					'metno_nowcast'  => $opts['prov_metno_nowcast'] ?? 0,
					'fmi'            => $opts['prov_fmi'] ?? 0,
					'openweathermap' => $opts['prov_openweathermap'] ?? 0,
					'weatherapi'     => $opts['prov_weatherapi'] ?? 0,
				]))),
			'animate'    => '1',
			'forecast'   => 'none',
			'days'       => '5',
			'hourly'     => '0',
			'hours'      => '24',
			'comparison' => '0', // NEW: Show individual provider data
			'extras'     => '',
			'tides'      => '0',
			'show_moon'  => '0',
			'show_moon_daily' => '0',
			'show_alerts' => (string) $opts['show_alerts'],

			// Units & formatting (overrides)
			'units'        => $opts['units'],
			'temp_unit'    => $opts['temp_unit'],
			'wind_unit'    => $opts['wind_unit'],
			'precip_unit'  => $opts['precip_unit'],
			'date_format'  => $opts['date_format'],
			'theme'        => 'auto',
			'preset'       => '',
			'map_engine'   => $opts['map_engine'] ?? 'auto',
		], $atts, 'sv_vader');



		$layout = strtolower(trim($a['layout']));
		$allowed_layouts = ['inline','compact','card','detailed'];
		if (!in_array($layout, $allowed_layouts, true)) $layout = 'card';
		$map_height = max(120, intval($a['map_height']));
		$hours = max(3, min(24, intval($a['hours'])));

		$provider_list = array_filter(array_map('trim', explode(',', strtolower($a['providers']))));
		$allowed = function_exists('sv_vader_provider_ids') ? sv_vader_provider_ids() : ['openmeteo','smhi','yr','metno_nowcast','fmi','openweathermap','weatherapi'];
		$provider_list = array_values(array_intersect($provider_list, $allowed));
		if (empty($provider_list)) $provider_list = ['openmeteo'];

		$show = array_map('trim', explode(',', strtolower($a['show'])));

		// Extras handling: extras="moon" or show_moon="1"
		$extras = array_map('trim', explode(',', strtolower((string)($a['extras'] ?? ''))));
		$show_moon = in_array('moon', $extras, true) || (string)($a['show_moon'] ?? '') === '1';
		$show_tides = in_array('tides', $extras, true) || (string)($a['tides'] ?? '') === '1';
		// Daily forecast moon: enable per-day moon icons when extras contains 'moon_daily' or flag set
		$show_moon_daily = in_array('moon_daily', $extras, true) || (string)($a['show_moon_daily'] ?? '') === '1';
		$show_hourly = in_array(strtolower((string)($a['hourly'] ?? '')), ['1','true','yes','on'], true);

		// Units
		$units = sv_vader_resolve_units([
			'units'        => $a['units'],
			'temp_unit'    => $a['temp_unit'],
			'wind_unit'    => $a['wind_unit'],
			'precip_unit'  => $a['precip_unit'],
			'date_format'  => $a['date_format'],
		]);

		// Safety: if shortcode explicitly sets wind_unit, ensure it is applied.
		// Accept both 'knt' and 'kn' and canonicalize to 'knt'.
		$allowed_winds = ['ms','kmh','mph','knt','kn'];
		$wu_attr = strtolower(trim((string)($a['wind_unit'] ?? '')));
		if ($wu_attr !== '' && in_array($wu_attr, $allowed_winds, true)) {
			if ($wu_attr === 'kn') $wu_attr = 'knt';
			$units['wind'] = $wu_attr;
		}

		$api = new SV_Vader_API(intval($opts['cache_minutes']));
		
		// Check if comparison mode is enabled
		if ($a['comparison'] === '1') {
			$res = $api->get_provider_details($a['ort'], $a['lat'], $a['lon'], $provider_list, $opts['yr_contact']);
			if (is_wp_error($res)) return '<em>' . esc_html($res->get_error_message()) . '</em>';
			return $this->render_comparison_view($res, $a['ort'], $units, $api);
		}
		
		$res = $api->get_current_weather($a['ort'], $a['lat'], $a['lon'], $provider_list, $opts['yr_contact']);
		if (is_wp_error($res)) return '<em>' . esc_html($res->get_error_message()) . '</em>';

        // Convert values according to selected units
		list($t_val, $t_sym) = sv_vader_temp($res['temp'] ?? null, $units['temp'], 0);
		list($w_val, $w_u)   = sv_vader_wind($res['wind'] ?? null, $units['wind'], 0);
		$w_dir               = $res['wind_dir'] ?? null;
		list($p_val, $p_u)   = sv_vader_precip($res['precip'] ?? null, $units['precip'], 1);
		$cloud               = isset($res['cloud']) ? intval($res['cloud']) : null;

		// Moon info (optional)
		$moon = null;
		if ($show_moon) {
			if (function_exists('sv_vader_moon')) {
				$moon = sv_vader_moon();
			}
		}

		// Pre-build moon HTML (single place to render)
		$moon_html = '';
		if ($show_moon) {
			if ($moon) {
				$icon = function_exists('sv_vader_moon_icon') ? sv_vader_moon_icon(intval($moon['phase_index'] ?? 0)) : '';
				/* translators: 1: moon phase name, 2: illumination percentage. */
				$moon_text = sprintf( __( 'Moon: %1$s (%2$s%%)', 'spelhubben-weather' ), $moon['phase'], sv_vader_num($moon['illum'], 0) );
				$moon_html = '<span class="svv-moon"><span class="svv-moon-icon">' . esc_html($icon) . '</span>' . esc_html($moon_text) . '</span>';
			} else {
				$moon_html = '<span class="svv-moon">' . esc_html( __( 'Moon data unavailable', 'spelhubben-weather' ) ) . '</span>';
			}
		}

		$desc     = $res['desc'] ?? '';
		$icon_url = $api->map_icon_url($res['code'] ?? null);
		$name     = $res['name'];
		$lat      = $res['lat'];
		$lon      = $res['lon'];

		$alerts   = ( $a['show_alerts'] === '1' ) ? sv_vader_get_alerts($res) : [];

		// Tide data (optional)
		$tide_data = null;
		if ($show_tides) {
			$opts = sv_vader_get_options();
			if (!empty($opts['tides_enabled'])) {
				$tide_data = $api->get_tides($a['ort'], $a['lat'], $a['lon']);
			}
		}

		$forecast = [];
		if ($a['forecast'] === 'daily') {
			$forecast = (new SV_Vader_API(intval($opts['cache_minutes'])))->get_daily_forecast($a['ort'], $a['lat'], $a['lon'], intval($a['days']));
		}

		$hourly = [];
		if ($show_hourly && method_exists($api, 'get_hourly_forecast')) {
			$hourly = $api->get_hourly_forecast($a['ort'], $a['lat'], $a['lon'], $hours);
		}

		$is_anim = in_array(strtolower((string)($a['animate'] ?? '')), ['1','true','yes','on'], true);
		$classes = 'sv-vader spelhubben-weather ' . $a['class'] . ' ' . ($is_anim ? 'svv-anim' : '') . ' svv-layout-' . $layout;

		// Theme handling: auto|light|dark (fallback to auto)
		$theme = strtolower(trim((string) ($a['theme'] ?? 'auto')));
		if (!in_array($theme, ['auto','light','dark'], true)) {
			$theme = 'auto';
		}

		if ($theme !== 'auto') {
			// Add both theme class and a force-class to override OS/browser prefers-color-scheme
			$classes .= ' svv-theme-' . $theme . ' svv-force-' . $theme;
		}

		$preset = strtolower(trim((string)($a['preset'] ?? '')));
		$allowed_presets = ['mini','hero','sidebar','dashboard','forecast-strip'];
		if ($preset !== '' && in_array($preset, $allowed_presets, true)) {
			$classes .= ' svv-preset-' . $preset;
		}

		$map_engine = strtolower(trim((string)($a['map_engine'] ?? 'auto')));
		if (!in_array($map_engine, ['auto','openlayers','leaflet','static'], true)) {
			$map_engine = 'auto';
		}

		ob_start(); ?>
		<div class="<?php echo esc_attr($classes); ?>" data-svv-ro="1" data-svv-theme="<?php echo esc_attr($theme); ?>" data-svv-wind-unit="<?php echo esc_attr($units['wind']); ?>">
		<?php if ($show_moon): ?>
			<?php if ($moon): ?>
				<?php echo '<!-- SVV-MOON: phase_index=' . intval($moon['phase_index'] ?? -1) . ' phase=' . esc_html($moon['phase']) . ' illum=' . esc_html($moon['illum']) . ' -->'; ?>
			<?php else: ?>
				<?php echo '<!-- SVV-MOON: requested but unavailable -->'; ?>
			<?php endif; ?>
		<?php else: ?>
			<?php echo '<!-- SVV-MOON: not requested -->'; ?>
		<?php endif; ?>
			<?php if (!empty($name) && $layout !== 'inline'): ?>
				<div class="svv-ort"><?php echo esc_html($name); ?></div>
			<?php endif; ?>

			<?php if (!empty($alerts) && $layout !== 'inline'): ?>
				<div class="svv-frontend-alerts">
					<?php foreach ($alerts as $alert): ?>
						<div class="svv-alert-mini is-<?php echo esc_attr($alert['level']); ?>" title="<?php echo esc_attr($alert['msg']); ?>">
							<span class="svv-alert-text"><?php echo esc_html($alert['title']); ?>: <?php echo esc_html($alert['msg']); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php switch ($layout) {
				case 'inline': ?>
					<div class="svv-row svv-row-inline">
						<?php if (in_array('icon', $show, true) && $icon_url): ?>
							<img class="svv-icon" src="<?php echo esc_url($icon_url); ?>" alt="" width="40" height="40" loading="eager" fetchpriority="high">
						<?php endif; ?>
						<?php if (in_array('temp', $show, true) && $t_val !== null): ?>
						<div class="svv-temp"><?php echo esc_html( sv_vader_num($t_val) ); ?><?php echo esc_html($t_sym); ?></div>
						<?php endif; ?>
						<?php echo wp_kses_post( $moon_html ); ?>
					</div>
				<?php break;

				case 'compact': ?>
					<div class="svv-row svv-row-compact">
						<?php if (in_array('icon', $show, true) && $icon_url): ?>
							<img class="svv-icon" src="<?php echo esc_url($icon_url); ?>" alt="" width="40" height="40" loading="eager" fetchpriority="high">
						<?php endif; ?>
							<?php if (in_array('temp', $show, true) && $t_val !== null): ?>
								<div class="svv-temp"><?php echo esc_html( sv_vader_num($t_val) ); ?><?php echo esc_html($t_sym); ?></div>
                            
							<?php endif; ?>
						<?php if (in_array('wind', $show, true) && $w_val !== null): ?>
							<?php
							/* translators: 1: wind value, 2: wind unit (e.g. 5, km/h) */
							$wind_compact = sprintf( __( 'Wind %1$s %2$s', 'spelhubben-weather' ), sv_vader_num($w_val), $w_u );
							?>
							<span class="svv-wind svv-badge">
								<?php echo esc_html( $wind_compact ); ?>
							<?php if (in_array('wind_dir', $show, true) && $w_dir !== null) echo wp_kses_post( sv_vader_wind_dir_icon($w_dir) ); ?>
							</span>
						<?php elseif (in_array('wind_dir', $show, true) && $w_dir !== null): ?>
							<span class="svv-wind svv-badge">
								<?php echo wp_kses_post( sv_vader_wind_dir_icon($w_dir) ); ?>
							</span>
						<?php endif; ?>
						<?php if (!empty($desc)): ?>
							<span class="svv-desc svv-badge"><?php echo esc_html($desc); ?></span>
						<?php endif; ?>
						<?php echo wp_kses_post( $moon_html ); ?>
					</div>
				<?php break;

				case 'detailed': ?>
					<div class="svv-row svv-row-detailed">
						<?php if (in_array('icon', $show, true) && $icon_url): ?>
							<img class="svv-icon" src="<?php echo esc_url($icon_url); ?>" alt="" width="44" height="44" loading="eager" fetchpriority="high">
						<?php endif; ?>
						<div class="svv-col">
							<?php if (in_array('temp', $show, true) && $t_val !== null): ?>
								<div class="svv-temp"><?php echo esc_html( sv_vader_num($t_val) ); ?><?php echo esc_html($t_sym); ?></div>
							<?php endif; ?>
							<div class="svv-meta">
								<?php if (in_array('wind', $show, true) && $w_val !== null): ?>
									<?php
									/* translators: 1: wind value, 2: wind unit (e.g. 5, km/h) */
									$wind_detailed = sprintf( __( 'Wind: %1$s %2$s', 'spelhubben-weather' ), sv_vader_num($w_val), $w_u );
									?>
									<span class="svv-wind">
										<?php echo esc_html( $wind_detailed ); ?>
									<?php if (in_array('wind_dir', $show, true) && $w_dir !== null) echo wp_kses_post( sv_vader_wind_dir_icon($w_dir) ); ?>
									</span>
								<?php endif; ?>
								<?php if (!(in_array('wind', $show, true) && $w_val !== null) && in_array('wind_dir', $show, true) && $w_dir !== null): ?>
									<span class="svv-wind">
										<?php echo wp_kses_post( sv_vader_wind_dir_icon($w_dir) ); ?>
									</span>
								<?php endif; ?>
								<?php if (!empty($desc)): ?>
									<span class="svv-desc"><?php echo esc_html($desc); ?></span>
								<?php endif; ?>
								<?php echo wp_kses_post( $moon_html ); ?>
							</div>
							<div class="svv-extra">
								<?php if ($p_val !== null): ?>
									<?php
									/* translators: 1: precipitation value, 2: precipitation unit (e.g. 1.2, mm) */
									$precip_str = sprintf( __( 'Precipitation: %1$s %2$s', 'spelhubben-weather' ), sv_vader_num($p_val, 1), $p_u );
									?>
									<span class="svv-precip"><?php echo esc_html( $precip_str ); ?></span>
								<?php endif; ?>
								<?php if ($cloud !== null): ?>
									<?php
									/* translators: %s: cloud cover percent (0–100) */
									$cloud_str = sprintf( __( 'Cloud cover: %s%%', 'spelhubben-weather' ), sv_vader_num($cloud) );
									?>
									<span class="svv-cloud"><?php echo esc_html( $cloud_str ); ?></span>
								<?php endif; ?>
								<?php // moon shown above in meta; skip duplicate output here ?>
							</div>
						</div>
					</div>
				<?php break;

				case 'card':
				default: ?>
					<div class="svv-row">
						<?php if (in_array('icon', $show, true) && $icon_url): ?>
							<img class="svv-icon" src="<?php echo esc_url($icon_url); ?>" alt="" width="40" height="40" loading="eager" fetchpriority="high">
						<?php endif; ?>
						<?php if (in_array('temp', $show, true) && $t_val !== null): ?>
							<div class="svv-temp"><?php echo esc_html( sv_vader_num($t_val) ); ?><?php echo esc_html($t_sym); ?></div>
						<?php endif; ?>
					</div>

					<div class="svv-meta">
						<?php if (in_array('wind', $show, true) && $w_val !== null): ?>
							<?php
							/* translators: 1: wind value, 2: wind unit (e.g. 5, km/h) */
							$wind_card = sprintf( __( 'Wind: %1$s %2$s', 'spelhubben-weather' ), sv_vader_num($w_val), $w_u );
							?>
							<span class="svv-wind">
								<?php echo esc_html( $wind_card ); ?>
							<?php if (in_array('wind_dir', $show, true) && $w_dir !== null) echo wp_kses_post( sv_vader_wind_dir_icon($w_dir) ); ?>
							</span>
						<?php endif; ?>
						<?php if (!(in_array('wind', $show, true) && $w_val !== null) && in_array('wind_dir', $show, true) && $w_dir !== null): ?>
							<span class="svv-wind">
								<?php echo wp_kses_post( sv_vader_wind_dir_icon($w_dir) ); ?>
							</span>
						<?php endif; ?>
						<?php if (!empty($desc)): ?>
							<span class="svv-desc"><?php echo esc_html($desc); ?></span>
						<?php endif; ?>
						<?php echo wp_kses_post( $moon_html ); ?>
					</div>
				<?php break; } ?>

			<?php if ($a['map'] === '1' && $layout !== 'inline'): ?>
				<div class="svv-map"
					 data-lat="<?php echo esc_attr($lat); ?>"
					 data-lon="<?php echo esc_attr($lon); ?>"
					 data-name="<?php echo esc_attr($name); ?>"
					 data-engine="<?php echo esc_attr($map_engine); ?>"
					 style="height: <?php echo esc_attr($map_height); ?>px;"></div>

				<div class="svv-map-attrib" role="note" aria-label="<?php echo esc_attr_x('Map data attribution', 'aria label', 'spelhubben-weather'); ?>"><?php echo wp_kses_post(SV_VADER_ATTRIB_HTML); ?></div>

				<div class="svv-map-link">
					<a href="<?php echo esc_url('https://www.openstreetmap.org/?mlat=' . rawurlencode($lat) . '&mlon=' . rawurlencode($lon) . '#map=12/' . rawurlencode($lat) . '/' . rawurlencode($lon)); ?>"
					   target="_blank" rel="noopener noreferrer">
						<?php esc_html_e('View on OpenStreetMap', 'spelhubben-weather'); ?>
					</a>
				</div>
			<?php endif; ?>

			<?php if (!empty($tide_data) && $layout !== 'inline'): ?>
				<div class="svv-tides">
					<strong><?php echo esc_html__( 'Tides', 'spelhubben-weather' ); ?></strong>
					<ul class="svv-tide-list">
						<?php foreach (array_slice($tide_data['events'] ?? [], 0, 5) as $ev):
							$ts = strtotime($ev['time']);
							$lbl = $ts ? date_i18n(get_option('time_format') . ' ' . get_option('date_format'), $ts) : ($ev['time'] ?? '');
						?>
							<li><?php echo esc_html( ucfirst($ev['type'] ?? 'event') . ': ' . $lbl . (isset($ev['height']) ? (' — ' . sv_vader_num($ev['height'], 2) . ' m') : '') ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if (!empty($hourly) && $layout !== 'inline') : ?>
				<div class="svv-hourly" data-svv-hours="<?php echo esc_attr($hours); ?>">
					<?php foreach (array_slice($hourly, 0, $hours) as $h):
						$icon = $api->map_icon_url($h['code'] ?? null);
						$ts = strtotime($h['time'] ?? '');
						$lbl = $ts ? sv_vader_format_hour_time($ts) : ($h['time'] ?? '');
						list($h_temp, $h_temp_sym) = sv_vader_temp($h['temp'] ?? null, $units['temp'], 0);
						list($h_wind, $h_wind_unit) = sv_vader_wind($h['wind'] ?? null, $units['wind'], 0);
						list($h_precip, $h_precip_unit) = sv_vader_precip($h['precip'] ?? null, $units['precip'], 1);
					?>
						<div class="svv-hour">
							<div class="svv-hour-time"><?php echo esc_html($lbl); ?></div>
							<?php if ($icon): ?><img class="svv-hour-icon" src="<?php echo esc_url($icon); ?>" alt="" width="28" height="28"><?php endif; ?>
							<?php if ($h_temp !== null): ?><div class="svv-hour-temp"><?php echo esc_html(sv_vader_num($h_temp)); ?><?php echo esc_html($h_temp_sym); ?></div><?php endif; ?>
							<div class="svv-hour-meta">
								<?php if ($h_precip !== null): ?><span><?php echo esc_html(sv_vader_num($h_precip, 1) . ' ' . $h_precip_unit); ?></span><?php endif; ?>
								<?php if ($h_wind !== null): ?><span><?php echo esc_html(sv_vader_num($h_wind) . ' ' . $h_wind_unit); ?></span><?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if (!empty($forecast) && $layout !== 'inline') : ?>
				<div class="svv-forecast <?php echo ($is_anim ? 'svv-anim' : ''); ?>">
					<?php foreach ($forecast as $d):
						$icon = $api->map_icon_url($d['code']);
						$ts   = strtotime($d['date']);
						$lbl  = date_i18n($units['date_format'], $ts);
					?>
					<div class="svv-daycard">
						<div class="svv-daylabel"><?php echo esc_html($lbl); ?></div>
						<?php if ($icon): ?><img class="svv-dayicon" src="<?php echo esc_url($icon); ?>" alt="" width="36" height="36"><?php endif; ?>
						<?php if (!empty($show_moon_daily)): ?>
						<?php
						// Moon info for this forecast day
						$day_moon = null;
						if (function_exists('sv_vader_moon')) {
							$day_moon = sv_vader_moon($ts);
						}
						?>
						<?php if ($day_moon): ?>
							<?php /* translators: 1: moon phase name, 2: illumination percentage. */ ?>
							<span class="svv-daymoon" title="<?php echo esc_attr(sprintf(__('Moon: %1$s — %2$s%%', 'spelhubben-weather'), $day_moon['phase'], sv_vader_num($day_moon['illum'], 0))); ?>">
								<?php echo esc_html( sv_vader_moon_icon(intval($day_moon['phase_index'] ?? 0)) ); ?> <?php echo esc_html( sv_vader_num($day_moon['illum'], 0) ); ?>%
							</span>
						<?php endif; ?>
						<?php endif; ?>
						<div class="svv-daytemps">
							<?php
							list($fmax,) = sv_vader_temp($d['tmax'], $units['temp'], 0);
							list($fmin,) = sv_vader_temp($d['tmin'], $units['temp'], 0);
							?>
							<span class="svv-tmax"><?php echo esc_html( sv_vader_num($fmax) ); ?>°</span>
							<span class="svv-tmin"><?php echo esc_html( sv_vader_num($fmin) ); ?>°</span>
						</div>
						<?php if (!empty($d['desc'])): ?>
							<div class="svv-daydesc"><?php echo esc_html($d['desc']); ?></div>
						<?php endif; ?>
					</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render comparison view showing all providers' data side-by-side
	 */
	private function render_comparison_view($res, $place, $units, $api) {
		$name = $res['name'] ?? $place;
		$lat = $res['lat'];
		$lon = $res['lon'];
		$providers = $res['providers'] ?? [];
		$statuses = $res['provider_statuses'] ?? [];
		$registry = function_exists('sv_vader_provider_registry') ? sv_vader_provider_registry() : [];

		ob_start(); ?>
		<div class="sv-vader spelhubben-weather svv-comparison">
			<div class="svv-ort"><?php echo esc_html($name); ?></div>
			<p class="svv-comparison-subtitle" style="text-align:center; margin:10px 0; font-size:13px; color:#666;">
				<?php esc_html_e('Provider Comparison', 'spelhubben-weather'); ?> (<?php echo count($providers); ?> <?php esc_html_e('sources', 'spelhubben-weather'); ?>)
			</p>

			<div class="svv-comparison-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:12px; margin:12px 0;">
				<?php foreach ($providers as $provider_key => $data): ?>
					<?php
					$display_name = $registry[$provider_key]['label'] ?? ucfirst($provider_key);
					$status = $statuses[$provider_key] ?? ($data['_status'] ?? 'ok');
					
					// Convert values
					list($t_val, $t_sym) = sv_vader_temp($data['temp'] ?? null, $units['temp'], 0);
					list($w_val, $w_u)   = sv_vader_wind($data['wind'] ?? null, $units['wind'], 0);
					$w_dir               = $data['wind_dir'] ?? null;
					list($p_val, $p_u)   = sv_vader_precip($data['precip'] ?? null, $units['precip'], 1);
					$cloud = isset($data['cloud']) ? intval($data['cloud']) : null;
					$desc = $data['desc'] ?? '—';
					?>
					<div class="svv-provider-card" style="border:1px solid #ddd; border-radius:6px; padding:12px; background:#fafafa;">
						<div style="font-weight:600; margin-bottom:8px; color:#333;"><?php echo esc_html($display_name); ?></div>
						<div class="svv-provider-status is-<?php echo esc_attr($status); ?>" style="font-size:11px; margin-bottom:8px; color:#666;">
							<?php echo esc_html(function_exists('sv_vader_provider_status_label') ? sv_vader_provider_status_label($status) : $status); ?>
						</div>
						
						<?php if ($status === 'ok'): ?>
							<div style="font-size:18px; font-weight:bold; color:#2c3e50; margin-bottom:6px;">
								<?php if ($t_val !== null): ?>
								<?php echo esc_html(sv_vader_num($t_val)); ?><?php echo esc_html($t_sym); ?>
							<?php else: ?>
								<span style="color:#999;">—</span>
							<?php endif; ?>
							</div>

							<div style="font-size:12px; color:#666; margin-bottom:8px;">
							<?php if ($w_val !== null): ?>
								<div>
									<?php esc_html_e('Wind:', 'spelhubben-weather'); ?> <?php echo esc_html(sv_vader_num($w_val)); ?> <?php echo esc_html($w_u); ?>
									<?php if ($w_dir !== null) {
										echo wp_kses_post( sv_vader_wind_dir_icon($w_dir) );
									} ?>
								</div>
							<?php endif; ?>
							<?php if ($p_val !== null): ?>
								<div><?php esc_html_e('Precip:', 'spelhubben-weather'); ?> <?php echo esc_html(sv_vader_num($p_val, 1)); ?> <?php echo esc_html($p_u); ?></div>
								<?php endif; ?>
								<?php if ($cloud !== null): ?>
									<div><?php esc_html_e('Cloud:', 'spelhubben-weather'); ?> <?php echo esc_html($cloud); ?>%</div>
								<?php endif; ?>
							</div>

							<?php if (!empty($desc) && $desc !== '—'): ?>
								<div style="font-size:12px; font-style:italic; color:#555; padding-top:6px; border-top:1px solid #e0e0e0;">
									<?php echo esc_html($desc); ?>
								</div>
							<?php endif; ?>
						<?php else: ?>
							<div style="font-size:12px; color:#777;">
								<?php esc_html_e('This provider did not return usable data for the current request.', 'spelhubben-weather'); ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<div style="margin-top:16px; padding:8px; background:#f0f0f0; border-radius:4px; font-size:12px; color:#666;">
				<strong><?php esc_html_e('Note:', 'spelhubben-weather'); ?></strong> 
				<?php esc_html_e('Each provider may report different values due to different measuring stations or calculation methods. Use this view to compare accuracy and availability.', 'spelhubben-weather'); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
}
