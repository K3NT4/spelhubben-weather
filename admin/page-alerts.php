<?php
// admin/page-alerts.php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'sv_vader_render_alerts_page' ) ) {
	function sv_vader_render_alerts_page() {
		if ( ! current_user_can( 'manage_options' ) ) return;

		// Save settings if submitted
		if ( isset( $_POST['svv_save_alert_settings'] ) && check_admin_referer( 'svv_save_alerts_action', 'svv_save_alerts_nonce' ) ) {
			$o = sv_vader_get_options();
			$o['alert_cold_extreme']  = isset( $_POST['alert_cold_extreme'] ) ? floatval( sanitize_text_field( wp_unslash( $_POST['alert_cold_extreme'] ) ) ) : $o['alert_cold_extreme'];
			$o['alert_cold_freezing'] = isset( $_POST['alert_cold_freezing'] ) ? floatval( sanitize_text_field( wp_unslash( $_POST['alert_cold_freezing'] ) ) ) : $o['alert_cold_freezing'];
			$o['alert_heat_extreme']  = isset( $_POST['alert_heat_extreme'] ) ? floatval( sanitize_text_field( wp_unslash( $_POST['alert_heat_extreme'] ) ) ) : $o['alert_heat_extreme'];
			$o['alert_heat_warm']     = isset( $_POST['alert_heat_warm'] ) ? floatval( sanitize_text_field( wp_unslash( $_POST['alert_heat_warm'] ) ) ) : $o['alert_heat_warm'];
			$o['alert_wind_storm']    = isset( $_POST['alert_wind_storm'] ) ? floatval( sanitize_text_field( wp_unslash( $_POST['alert_wind_storm'] ) ) ) : $o['alert_wind_storm'];
			$o['alert_wind_strong']   = isset( $_POST['alert_wind_strong'] ) ? floatval( sanitize_text_field( wp_unslash( $_POST['alert_wind_strong'] ) ) ) : $o['alert_wind_strong'];
			$o['alert_precip_heavy']  = isset( $_POST['alert_precip_heavy'] ) ? floatval( sanitize_text_field( wp_unslash( $_POST['alert_precip_heavy'] ) ) ) : $o['alert_precip_heavy'];
			
			update_option( 'sv_vader_options', $o );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Alert settings saved.', 'spelhubben-weather' ) . '</p></div>';
		}

		$opts = sv_vader_get_options();
		$api  = new SV_Vader_API( (int) $opts['cache_minutes'] );
		
		// Resolve units for labels
		$units = sv_vader_resolve_units($opts);
		$t_unit = $units['temp'] === 'F' ? '°F' : '°C';
		$w_unit = $units['wind'];
		$p_unit = $units['precip'];

		// Get current weather for default location to show active alerts
		$weather = $api->get_current_weather( $opts['default_ort'] );
		$alerts  = is_wp_error( $weather ) ? [] : sv_vader_get_alerts( $weather );
		?>
		<div class="wrap svv-admin-wrap">
			<h1 class="svv-page-title"><?php esc_html_e( 'Spelhubben Weather – Alerts', 'spelhubben-weather' ); ?></h1>
			<p class="svv-subheader"><?php esc_html_e( 'Extreme weather warnings and smart recommendations.', 'spelhubben-weather' ); ?></p>

			<div class="svv-grid">
				<div class="svv-col">
					<div class="svv-card">
						<h2 class="svv-card-title">
							<span class="dashicons dashicons-warning"></span>
							<?php
							/* translators: %s: place name, e.g. "Stockholm" */
							printf( esc_html__( 'Current Alerts for %s', 'spelhubben-weather' ), esc_html( $opts['default_ort'] ) );
							?>
						</h2>
						
						<?php if ( empty( $alerts ) ) : ?>
							<div class="svv-alert-empty">
								<span class="dashicons dashicons-smiley"></span>
								<p><?php esc_html_e( 'No extreme weather detected right now. Everything looks normal!', 'spelhubben-weather' ); ?></p>
							</div>
						<?php else : ?>
							<div class="svv-alerts-list">
								<?php foreach ( $alerts as $alert ) : ?>
									<div class="svv-alert-item is-<?php echo esc_attr( $alert['level'] ); ?>">
										<div class="svv-alert-icon">
											<span class="dashicons dashicons-<?php echo esc_attr( $alert['icon'] ); ?>"></span>
										</div>
										<div class="svv-alert-content">
											<h3><?php echo esc_html( $alert['title'] ); ?></h3>
											<p><?php echo esc_html( $alert['msg'] ); ?></p>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="svv-card">
						<h2 class="svv-card-title">
							<span class="dashicons dashicons-info"></span>
							<?php esc_html_e( 'How it works', 'spelhubben-weather' ); ?>
						</h2>
						<p><?php esc_html_e( 'The system monitors temperature, wind speed, and precipitation. If any value exceeds safety thresholds, an alert is triggered with a helpful recommendation.', 'spelhubben-weather' ); ?></p>
                            <ul class="ul-disc" style="margin-left:20px;">
                            	<li><strong><?php esc_html_e( 'Extreme Cold:', 'spelhubben-weather' ); ?></strong> <?php /* translators: %s: temperature with unit, e.g. "-20°C" */ printf( esc_html__( 'Below %s', 'spelhubben-weather' ), esc_html( $opts['alert_cold_extreme'] . $t_unit ) ); ?></li>
                            	<li><strong><?php esc_html_e( 'Freezing:', 'spelhubben-weather' ); ?></strong> <?php /* translators: %s: temperature with unit, e.g. "0°C" */ printf( esc_html__( 'Below %s', 'spelhubben-weather' ), esc_html( $opts['alert_cold_freezing'] . $t_unit ) ); ?></li>
                            	<li><strong><?php esc_html_e( 'Extreme Heat:', 'spelhubben-weather' ); ?></strong> <?php /* translators: %s: temperature with unit, e.g. "35°C" */ printf( esc_html__( 'Above %s', 'spelhubben-weather' ), esc_html( $opts['alert_heat_extreme'] . $t_unit ) ); ?></li>
                            	<li><strong><?php esc_html_e( 'Storm:', 'spelhubben-weather' ); ?></strong> <?php /* translators: %s: wind speed with unit, e.g. "25 m/s" */ printf( esc_html__( 'Above %s', 'spelhubben-weather' ), esc_html( $opts['alert_wind_storm'] . ' ' . $w_unit ) ); ?></li>
                            	<li><strong><?php esc_html_e( 'Strong Wind:', 'spelhubben-weather' ); ?></strong> <?php /* translators: %s: wind speed with unit, e.g. "15 m/s" */ printf( esc_html__( 'Above %s', 'spelhubben-weather' ), esc_html( $opts['alert_wind_strong'] . ' ' . $w_unit ) ); ?></li>
                            	<li><strong><?php esc_html_e( 'Heavy Rain:', 'spelhubben-weather' ); ?></strong> <?php /* translators: %s: precipitation with unit, e.g. "10 mm" */ printf( esc_html__( 'Above %s', 'spelhubben-weather' ), esc_html( $opts['alert_precip_heavy'] . ' ' . $p_unit ) ); ?></li>
                            </ul>
					</div>
				</div>

				<div class="svv-col">
					<div class="svv-card">
						<h2 class="svv-card-title">
							<span class="dashicons dashicons-admin-settings"></span>
							<?php esc_html_e( 'Alert Thresholds', 'spelhubben-weather' ); ?>
						</h2>
						<form method="post" class="svv-form">
							<?php wp_nonce_field( 'svv_save_alerts_action', 'svv_save_alerts_nonce' ); ?>
							
							<div class="svv-settings-group">
								<?php /* translators: %s: temperature unit symbol, e.g. "°C" or "°F" */ ?>
								<h3><?php printf( esc_html__( 'Temperature (%s)', 'spelhubben-weather' ), esc_html( $t_unit ) ); ?></h3>
								<div class="svv-field-row">
									<label><?php esc_html_e( 'Extreme Cold (Danger)', 'spelhubben-weather' ); ?></label>
									<input type="number" step="0.1" name="alert_cold_extreme" value="<?php echo esc_attr( $opts['alert_cold_extreme'] ); ?>" />
								</div>
								<div class="svv-field-row">
									<label><?php esc_html_e( 'Freezing (Warning)', 'spelhubben-weather' ); ?></label>
									<input type="number" step="0.1" name="alert_cold_freezing" value="<?php echo esc_attr( $opts['alert_cold_freezing'] ); ?>" />
								</div>
								<div class="svv-field-row">
									<label><?php esc_html_e( 'Extreme Heat (Danger)', 'spelhubben-weather' ); ?></label>
									<input type="number" step="0.1" name="alert_heat_extreme" value="<?php echo esc_attr( $opts['alert_heat_extreme'] ); ?>" />
								</div>
								<div class="svv-field-row">
									<label><?php esc_html_e( 'Warm/Nice (Info)', 'spelhubben-weather' ); ?></label>
									<input type="number" step="0.1" name="alert_heat_warm" value="<?php echo esc_attr( $opts['alert_heat_warm'] ); ?>" />
								</div>
							</div>

							<div class="svv-settings-group" style="margin-top:20px;">
								<h3><?php esc_html_e( 'Wind & Rain', 'spelhubben-weather' ); ?></h3>
								<div class="svv-field-row">
									<?php /* translators: %s: wind unit, e.g. "m/s" or "km/h" */ ?>
									<label><?php printf( esc_html__( 'Storm Wind (%s)', 'spelhubben-weather' ), esc_html( $w_unit ) ); ?></label>
									<input type="number" step="0.1" name="alert_wind_storm" value="<?php echo esc_attr( $opts['alert_wind_storm'] ); ?>" />
								</div>
								<div class="svv-field-row">
									<?php /* translators: %s: wind unit, e.g. "m/s" or "km/h" */ ?>
									<label><?php printf( esc_html__( 'Strong Wind (%s)', 'spelhubben-weather' ), esc_html( $w_unit ) ); ?></label>
									<input type="number" step="0.1" name="alert_wind_strong" value="<?php echo esc_attr( $opts['alert_wind_strong'] ); ?>" />
								</div>
								<div class="svv-field-row">
									<?php /* translators: %s: precipitation unit, e.g. "mm" or "in" */ ?>
									<label><?php printf( esc_html__( 'Heavy Rain (%s)', 'spelhubben-weather' ), esc_html( $p_unit ) ); ?></label>
									<input type="number" step="0.1" name="alert_precip_heavy" value="<?php echo esc_attr( $opts['alert_precip_heavy'] ); ?>" />
								</div>
							</div>

							<div style="margin-top:20px;">
								<button type="submit" name="svv_save_alert_settings" class="button button-primary">
									<?php esc_html_e( 'Save Alert Settings', 'spelhubben-weather' ); ?>
								</button>
							</div>
						</form>
					</div>

					<div class="svv-card">
						<h2 class="svv-card-title">
							<span class="dashicons dashicons-admin-appearance"></span>
							<?php esc_html_e( 'Preview Messages', 'spelhubben-weather' ); ?>
						</h2>
						<p class="description"><?php esc_html_e( 'Here are some examples of messages that can appear:', 'spelhubben-weather' ); ?></p>
						
						<div class="svv-alert-preview">
							<div class="svv-alert-item is-danger" style="margin-bottom:10px;">
								<div class="svv-alert-content">
									<strong><?php esc_html_e( 'Storm Warning', 'spelhubben-weather' ); ?></strong>
									<p style="font-size:12px; margin:4px 0;"><?php esc_html_e( 'Storm force winds detected! Stay indoors if possible and secure loose objects.', 'spelhubben-weather' ); ?></p>
								</div>
							</div>
							<div class="svv-alert-item is-danger" style="margin-bottom:10px;">
								<div class="svv-alert-content">
									<strong><?php esc_html_e( 'Extreme Cold', 'spelhubben-weather' ); ?></strong>
									<p style="font-size:12px; margin:4px 0;"><?php esc_html_e( 'It is freezing cold outside! Dress warmly, preferably in layers, or you might turn into an icicle.', 'spelhubben-weather' ); ?></p>
								</div>
							</div>
							<div class="svv-alert-item is-warning" style="margin-bottom:10px;">
								<div class="svv-alert-content">
									<strong><?php esc_html_e( 'Strong Wind', 'spelhubben-weather' ); ?></strong>
									<p style="font-size:12px; margin:4px 0;"><?php esc_html_e( 'It is very windy outside. Hold on to your hat!', 'spelhubben-weather' ); ?></p>
								</div>
							</div>
							<div class="svv-alert-item is-danger" style="margin-bottom:10px;">
								<div class="svv-alert-content">
									<strong><?php esc_html_e( 'Heavy Rain', 'spelhubben-weather' ); ?></strong>
									<p style="font-size:12px; margin:4px 0;"><?php esc_html_e( 'It is pouring down! Don\'t forget your umbrella or stay inside with a cup of coffee.', 'spelhubben-weather' ); ?></p>
								</div>
							</div>
							<div class="svv-alert-item is-warning" style="margin-bottom:10px;">
								<div class="svv-alert-content">
									<strong><?php esc_html_e( 'Icy Conditions', 'spelhubben-weather' ); ?></strong>
									<p style="font-size:12px; margin:4px 0;"><?php esc_html_e( 'The temperature has dropped below freezing. Watch out for icy patches!', 'spelhubben-weather' ); ?></p>
								</div>
							</div>
							<div class="svv-alert-item is-info">
								<div class="svv-alert-content">
									<strong><?php esc_html_e( 'Lovely Weather', 'spelhubben-weather' ); ?></strong>
									<p style="font-size:12px; margin:4px 0;"><?php esc_html_e( 'It is warm and pleasant outside. Don\'t forget the sunscreen!', 'spelhubben-weather' ); ?></p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
