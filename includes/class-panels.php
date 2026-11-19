<?php
// Optional panels share one renderer across shortcode, block and widget.
if (!defined('ABSPATH')) exit;

class SV_Vader_Panels {
	public static function astro(array $data): string {
		$timezone = new DateTimeZone($data['timezone']);
		$format = sv_vader_hour_time_format();
		$time = static function ($timestamp) use ($format, $timezone) {
			return $timestamp === null ? __('Unavailable', 'spelhubben-weather') : wp_date('j M ' . $format, $timestamp, $timezone);
		};
		$moon = $data['moon'];
		$next = $data['next_phase'];
		ob_start();
		?>
		<section class="svv-astro" aria-label="<?php esc_attr_e('Sun and moon', 'spelhubben-weather'); ?>">
			<h3 class="svv-panel-title"><?php esc_html_e('Sun and moon', 'spelhubben-weather'); ?></h3>
			<?php if ($data['sun_status'] === 'polar_day' || $data['sun_status'] === 'polar_night'): ?>
				<p><?php echo esc_html($data['sun_status'] === 'polar_day' ? __('Midnight sun: the sun does not set on this date.', 'spelhubben-weather') : __('Polar night: the sun does not rise on this date.', 'spelhubben-weather')); ?></p>
			<?php elseif ($data['sun_status'] === 'unavailable'): ?>
				<p><?php esc_html_e('Sun data is unavailable for this location.', 'spelhubben-weather'); ?></p>
			<?php endif; ?>
			<dl class="svv-astro-grid">
				<div><dt><?php esc_html_e('Sunrise', 'spelhubben-weather'); ?></dt><dd><?php echo esc_html($time($data['sunrise'])); ?></dd></div>
				<div><dt><?php esc_html_e('Sunset', 'spelhubben-weather'); ?></dt><dd><?php echo esc_html($time($data['sunset'])); ?></dd></div>
				<div><dt><?php esc_html_e('Day length', 'spelhubben-weather'); ?></dt><dd><?php
					/* translators: 1: hours, 2: minutes. */
					echo esc_html($data['day_length'] === null ? __('Unavailable', 'spelhubben-weather') : sprintf(__('%1$d h %2$d min', 'spelhubben-weather'), intdiv($data['day_length'], 3600), intdiv($data['day_length'] % 3600, 60)));
				?></dd></div>
				<div><dt><?php esc_html_e('Moon phase', 'spelhubben-weather'); ?></dt><dd><?php echo esc_html(sv_vader_moon_icon($moon['phase_index']) . ' ' . $moon['phase'] . ' (' . sv_vader_num($moon['illum'], 0) . '%)'); ?></dd></div>
				<div><dt><?php esc_html_e('Next major moon phase (approx.)', 'spelhubben-weather'); ?></dt><dd><?php echo esc_html($next['name'] . ' · ' . wp_date(get_option('date_format', 'Y-m-d') . ' ' . $format, $next['timestamp'], $timezone)); ?></dd></div>
			</dl>
			<p class="svv-panel-note"><?php
				/* translators: %s: site timezone name. */
				echo esc_html(sprintf(__('Times use the site timezone: %s. Moon phase dates are estimates.', 'spelhubben-weather'), $data['timezone']));
			?></p>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function insights(array $data, array $units): string {
		$format = 'j M ' . sv_vader_hour_time_format();
		$time = static function ($timestamp) use ($format) { return wp_date($format, $timestamp); };
		ob_start();
		?>
		<section class="svv-insights" aria-label="<?php esc_attr_e('Forecast insights', 'spelhubben-weather'); ?>">
			<h3 class="svv-panel-title"><?php esc_html_e('Forecast insights', 'spelhubben-weather'); ?></h3>
			<?php if (empty($data['available']) || (empty($data['dry_period']) && empty($data['wind_peak']) && empty($data['rain_later']))): ?>
				<p><?php esc_html_e('Not enough reliable hourly data to calculate an insight.', 'spelhubben-weather'); ?></p>
			<?php else: ?>
				<ul>
					<?php if (!empty($data['dry_period'])): $period = $data['dry_period']; ?>
						<li><?php /* translators: 1: start time, 2: end time. */ echo esc_html(sprintf(__('Longest mostly dry period in the forecast: %1$s–%2$s.', 'spelhubben-weather'), $time($period['start']), $time($period['end']))); ?></li>
					<?php endif; ?>
					<?php if (!empty($data['wind_peak'])): $peak = $data['wind_peak']; list($wind, $unit) = sv_vader_wind($peak['wind'], $units['wind'], 1); ?>
						<li><?php /* translators: 1: start time, 2: wind speed and unit. */ echo esc_html(sprintf(__('Strongest wind in the forecast: %1$s, about %2$s.', 'spelhubben-weather'), $time($peak['start']), sv_vader_num($wind, 1) . ' ' . $unit)); ?></li>
					<?php endif; ?>
					<?php if (!empty($data['rain_later'])): ?>
						<li><?php /* translators: %s: forecast time. */ echo esc_html(sprintf(__('Rain is forecast later, from %s.', 'spelhubben-weather'), $time($data['rain_later']['start']))); ?></li>
					<?php endif; ?>
				</ul>
				<p class="svv-panel-note"><?php esc_html_e('Based on the next available forecast hours, not a guarantee. Mostly dry means less than 0.1 mm per hour.', 'spelhubben-weather'); ?></p>
			<?php endif; ?>
		</section>
		<?php
		return ob_get_clean();
	}
}
