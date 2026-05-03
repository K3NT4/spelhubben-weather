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
		$registry = function_exists('sv_vader_provider_registry') ? sv_vader_provider_registry() : [];
		foreach ( $registry as $provider ) {
			$flag = $opts[ $provider['option_key'] ] ?? 0;
			$providers[] = [
				'label' => $provider['label'],
				'active' => (bool) $flag,
				'key_required' => ! empty( $provider['requires_key'] ),
				'key_missing' => function_exists('sv_vader_provider_key_missing') ? sv_vader_provider_key_missing($provider['id'], $opts) : false,
			];
		}

		$recent = is_array( $stats['recent'] ) ? $stats['recent'] : [];
		$map_engine = $opts['map_engine'] ?? 'auto';
		$openlayers_ok = file_exists( SV_VADER_DIR . 'assets/openlayers.js' ) && file_exists( SV_VADER_DIR . 'assets/openlayers.css' );
		$leaflet_ok = file_exists( SV_VADER_DIR . 'assets/vendor/leaflet/leaflet.js' ) && file_exists( SV_VADER_DIR . 'assets/vendor/leaflet/leaflet.css' );
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

			<div class="svv-grid svv-grid--performance">
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
									<?php if ( $p['active'] && $p['key_missing'] ) : ?>
										<?php echo esc_html__( '(missing key)', 'spelhubben-weather' ); ?>
									<?php endif; ?>
								</span>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="svv-card">
						<h2 class="svv-card-title"><span class="dashicons dashicons-location-alt"></span><?php esc_html_e( 'Map diagnostics', 'spelhubben-weather' ); ?></h2>
						<ul class="svv-kv-list">
							<li><span><?php esc_html_e( 'Configured engine', 'spelhubben-weather' ); ?></span><strong><?php echo esc_html( $map_engine ); ?></strong></li>
							<li><span><?php esc_html_e( 'OpenLayers assets', 'spelhubben-weather' ); ?></span><strong><?php echo esc_html( $openlayers_ok ? __( 'OK', 'spelhubben-weather' ) : __( 'Missing', 'spelhubben-weather' ) ); ?></strong></li>
							<li><span><?php esc_html_e( 'Leaflet legacy assets', 'spelhubben-weather' ); ?></span><strong><?php echo esc_html( $leaflet_ok ? __( 'OK', 'spelhubben-weather' ) : __( 'Missing', 'spelhubben-weather' ) ); ?></strong></li>
						</ul>
						<p class="description"><?php esc_html_e( 'If a cache or optimization plugin delays scripts, set the map engine to Static fallback temporarily or exclude Spelhubben Weather map assets from delay/defer rules.', 'spelhubben-weather' ); ?></p>
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
