<?php
// admin/page-performance.php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'sv_vader_render_performance_page' ) ) {
	function sv_vader_render_performance_page() {
		if ( ! current_user_can( 'manage_options' ) ) return;

		// Clear cache button
		if ( isset( $_POST['svv_clear_cache'] ) && check_admin_referer( 'svv_clear_cache_action', 'svv_clear_cache_nonce' ) ) {
			if ( function_exists( 'sv_vader_cache_invalidate_all' ) ) {
				sv_vader_cache_invalidate_all();
			} else {
				// Fallback: bump salt directly
				$o = sv_vader_get_options();
				$o['cache_salt'] = (string) time();
				update_option( 'sv_vader_options', $o );
			}
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Cache cleared.', 'spelhubben-weather' ) . '</p></div>';
		}

		// Clear stats button
		if ( isset( $_POST['svv_clear_stats'] ) && check_admin_referer( 'svv_clear_stats_action', 'svv_clear_stats_nonce' ) ) {
			sv_vader_stats_reset();
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Statistics cleared.', 'spelhubben-weather' ) . '</p></div>';
		}

		$stats = sv_vader_stats_get();
		$opts  = sv_vader_get_options();
		$total = max( 0, (int) $stats['hits'] + (int) $stats['misses'] );
		$hit_rate = $total > 0 ? round( ( (int) $stats['hits'] / $total ) * 100, 1 ) : 0;

		$today = gmdate( 'Y-m-d' );
		$today_stats = $stats['per_day'][ $today ] ?? [ 'hits' => 0, 'misses' => 0, 'api_calls' => 0 ];

		$providers = [];
		foreach ( [
			'openmeteo'      => 'Open-Meteo',
			'smhi'           => 'SMHI',
			'yr'             => 'Yr/MET Norway',
			'metno_nowcast'  => 'MET Norway Nowcast',
			'fmi'            => 'FMI',
			'openweathermap' => 'OpenWeatherMap',
			'weatherapi'     => 'WeatherAPI',
		] as $key => $label ) {
			$flag = $opts[ 'prov_' . $key ] ?? 0;
			$providers[] = [ 'label' => $label, 'active' => (bool) $flag ];
		}

		$recent = is_array( $stats['recent'] ) ? $stats['recent'] : [];
		?>
		<div class="wrap svv-admin-wrap">
			<h1 class="svv-page-title"><?php esc_html_e( 'Spelhubben Weather – Performance', 'spelhubben-weather' ); ?></h1>
			<p class="svv-subheader"><?php esc_html_e( 'Cache efficiency, API usage and recent updates.', 'spelhubben-weather' ); ?></p>

			<div class="svv-toolbar">
				<form method="post" style="margin:0; display:inline-block;">
					<?php wp_nonce_field( 'svv_clear_cache_action', 'svv_clear_cache_nonce' ); ?>
					<button class="button button-secondary" name="svv_clear_cache" value="1">
						<span class="dashicons dashicons-update" style="vertical-align:middle"></span>
						<?php esc_html_e( 'Clear cache (transients)', 'spelhubben-weather' ); ?>
					</button>
				</form>
				<form method="post" style="margin:0; display:inline-block; margin-left: 8px;">
					<?php wp_nonce_field( 'svv_clear_stats_action', 'svv_clear_stats_nonce' ); ?>
					<button class="button button-link-delete" name="svv_clear_stats" value="1" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all statistics?', 'spelhubben-weather' ); ?>');">
						<span class="dashicons dashicons-trash" style="vertical-align:middle"></span>
						<?php esc_html_e( 'Clear statistics', 'spelhubben-weather' ); ?>
					</button>
				</form>
			</div>

			<div class="svv-grid">
				<div class="svv-col">
					<div class="svv-card">
						<h2 class="svv-card-title"><span class="dashicons dashicons-dashboard"></span><?php esc_html_e( 'Cache & API', 'spelhubben-weather' ); ?></h2>
						<ul class="svv-kv-list">
							<li><span><?php esc_html_e( 'Cache hit rate', 'spelhubben-weather' ); ?></span><strong><?php echo esc_html( $hit_rate ); ?>%</strong></li>
							<li><span><?php esc_html_e( 'Total hits', 'spelhubben-weather' ); ?></span><strong><?php echo esc_html( (int) $stats['hits'] ); ?></strong></li>
							<li><span><?php esc_html_e( 'Total misses', 'spelhubben-weather' ); ?></span><strong><?php echo esc_html( (int) $stats['misses'] ); ?></strong></li>
							<li><span><?php esc_html_e( 'API calls today', 'spelhubben-weather' ); ?></span><strong><?php echo esc_html( (int) $today_stats['api_calls'] ); ?></strong></li>
						</ul>
					</div>

					<div class="svv-card">
						<h2 class="svv-card-title"><span class="dashicons dashicons-admin-site-alt3"></span><?php esc_html_e( 'Active providers', 'spelhubben-weather' ); ?></h2>
						<div class="svv-badges">
							<?php foreach ( $providers as $p ) : ?>
								<span class="svv-badge <?php echo esc_attr( $p['active'] ? 'is-active' : 'is-inactive' ); ?>">
									<?php echo $p['active'] ? '●' : '○'; ?> <?php echo esc_html( $p['label'] ); ?>
								</span>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<div class="svv-col">
					<div class="svv-card">
						<h2 class="svv-card-title"><span class="dashicons dashicons-clock"></span><?php esc_html_e( 'Recent updates', 'spelhubben-weather' ); ?></h2>
						<?php if ( empty( $recent ) ) : ?>
							<p class="description"><?php esc_html_e( 'No recent misses recorded yet.', 'spelhubben-weather' ); ?></p>
						<?php else : ?>
							<ul class="svv-recent">
								<?php foreach ( $recent as $row ) : ?>
									<li>
										<strong><?php echo esc_html( $row['place'] ?: __( 'Unknown', 'spelhubben-weather' ) ); ?></strong>
										<small><?php echo esc_html( $row['lat'] . ', ' . $row['lon'] ); ?></small>
										<?php /* translators: %s: human-readable time difference, e.g. "2 hours" */ ?>
										<span><?php echo esc_html( sprintf( __( '%s ago', 'spelhubben-weather' ), human_time_diff( (int) $row['time'], current_time( 'timestamp' ) ) ) ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
