<?php
// admin/page-settings.php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Inställnings-sida (render)
 */
if ( ! function_exists( 'sv_vader_render_settings_page' ) ) {
	function sv_vader_render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) return;

		// Clear cache-knapp
		if ( isset( $_POST['svv_clear_cache'] ) && check_admin_referer( 'svv_clear_cache_action', 'svv_clear_cache_nonce' ) ) {
			$o = sv_vader_get_options();
			$o['cache_salt'] = (string) time(); // bump salt – invalidates transients
			update_option( 'sv_vader_options', $o );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Cache cleared.', 'spelhubben-weather' ) . '</p></div>';
		}
		?>
		<div class="wrap svv-admin-wrap">
			<h1 class="svv-page-title"><?php esc_html_e( 'Spelhubben Weather – Settings', 'spelhubben-weather' ); ?></h1>
			<p class="svv-subheader"><?php esc_html_e( 'Tune defaults, providers and formatting. Shortcodes now live on their own page.', 'spelhubben-weather' ); ?></p>

			<div class="svv-toolbar">
				<form method="post" style="margin:0;">
					<?php wp_nonce_field( 'svv_clear_cache_action', 'svv_clear_cache_nonce' ); ?>
					<button class="button button-secondary" name="svv_clear_cache" value="1">
						<span class="dashicons dashicons-update" style="vertical-align:middle"></span>
						<?php esc_html_e( 'Clear cache (transients)', 'spelhubben-weather' ); ?>
					</button>
				</form>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=sv-vader-shortcodes' ) ); ?>">
					<span class="dashicons dashicons-editor-code" style="vertical-align:middle"></span>
					<?php esc_html_e( 'Open Shortcodes', 'spelhubben-weather' ); ?>
				</a>
			</div>

			<div class="svv-grid">
				<div class="svv-col">
					<div class="svv-card">
						<h2 class="svv-card-title">
							<span class="dashicons dashicons-admin-generic"></span>
							<?php esc_html_e( 'General', 'spelhubben-weather' ); ?>
							<span class="svv-tag"><?php esc_html_e( 'Default settings', 'spelhubben-weather' ); ?></span>
						</h2>

						<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" class="svv-form">
							<?php
							settings_fields( 'sv_vader_group' );
							do_settings_sections( 'sv_vader' );
							submit_button();
							?>
						</form>
					</div>

					<div class="svv-card">
						<h2 class="svv-card-title">
							<span class="dashicons dashicons-shield-alt"></span>
							<?php esc_html_e( 'Attribution', 'spelhubben-weather' ); ?>
						</h2>
						<div class="svv-kv">
							<div class="svv-kv-key"><?php esc_html_e( 'License requirements', 'spelhubben-weather' ); ?></div>
							<div class="svv-kv-val"><?php echo wp_kses_post( SV_VADER_ATTRIB_HTML ); ?></div>
						</div>
						<p class="description">
							<?php esc_html_e( 'The attribution is locked to comply with OpenStreetMap/ODbL requirements.', 'spelhubben-weather' ); ?>
						</p>
						<details class="svv-details">
							<summary><?php esc_html_e( 'Why locked?', 'spelhubben-weather' ); ?></summary>
							<p><?php esc_html_e( 'To ensure proper crediting of data sources per ODbL and respective API policies.', 'spelhubben-weather' ); ?></p>
						</details>
					</div>
				</div>

				<div class="svv-col">
					<div class="svv-card">
						<h2 class="svv-card-title">
							<span class="dashicons dashicons-lightbulb"></span>
							<?php esc_html_e( 'Tips', 'spelhubben-weather' ); ?>
						</h2>
						<p class="description">
							<?php esc_html_e( 'Use the Shortcodes page to quickly copy examples and see supported attributes.', 'spelhubben-weather' ); ?>
						</p>
						<p>
							<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sv-vader-shortcodes' ) ); ?>">
								<?php esc_html_e( 'Open Shortcodes', 'spelhubben-weather' ); ?>
							</a>
						</p>
						<div class="svv-badges">
							<span class="svv-badge"><span class="dashicons dashicons-shortcode"></span><?php esc_html_e( 'Shortcode', 'spelhubben-weather' ); ?></span>
							<span class="svv-badge"><span class="dashicons dashicons-schedule"></span><?php esc_html_e( 'Forecast', 'spelhubben-weather' ); ?></span>
							<span class="svv-badge"><span class="dashicons dashicons-location-alt"></span><?php esc_html_e( 'Leaflet map', 'spelhubben-weather' ); ?></span>
						</div>
					</div>
				</div>
			</div><!-- /.svv-grid -->

			<!-- More plugins by Spelhubben -->
			<div style="margin-top: 30px; margin-bottom: 20px;">
				<?php
				if ( class_exists( 'SV_Vader_WPOrg_Plugins' ) ) {
					$wporg = new SV_Vader_WPOrg_Plugins();
					echo wp_kses_post( $wporg->render() );
				}
				?>
			</div>
		</div><!-- /.wrap -->
		<?php
	}
}
