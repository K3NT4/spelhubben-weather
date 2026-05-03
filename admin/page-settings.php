<?php
// admin/page-settings.php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Settings page (render)
 */
if ( ! function_exists( 'sv_vader_render_settings_page' ) ) {
	function sv_vader_render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) return;

		// Import status notices
		if ( isset( $_GET['svv_import_status'] ) ) {
			$status = sanitize_text_field( wp_unslash( $_GET['svv_import_status'] ) );
			if ( $status === 'ok' ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings imported.', 'spelhubben-weather' ) . '</p></div>';
			} elseif ( $status === 'fail' ) {
				$msg_raw = isset( $_GET['svv_import_msg'] ) ? rawurldecode( wp_unslash( $_GET['svv_import_msg'] ) ) : '';
				$msg = $msg_raw !== '' ? sanitize_text_field( $msg_raw ) : __( 'Import failed.', 'spelhubben-weather' );
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
			}

				// Reset notice
				if ( isset( $_GET['svv_reset'] ) ) {
					$reset = sanitize_text_field( wp_unslash( $_GET['svv_reset'] ) );
					if ( $reset === 'ok' ) {
						echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings reset to defaults.', 'spelhubben-weather' ) . '</p></div>';
					}
				}
		}
		?>
		<div class="wrap svv-admin-wrap">
			<h1 class="svv-page-title"><?php esc_html_e( 'Spelhubben Weather – Settings', 'spelhubben-weather' ); ?></h1>
			<p class="svv-subheader"><?php esc_html_e( 'Tune defaults, providers and formatting. Shortcodes now live on their own page.', 'spelhubben-weather' ); ?></p>

			<div class="svv-toolbar">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0; display:inline-block;">
					<input type="hidden" name="action" value="svv_export_settings" />
					<?php wp_nonce_field( 'svv_export_settings_action', 'svv_export_settings_nonce' ); ?>
					<button class="button button-secondary" name="svv_export_settings" value="1">
						<span class="dashicons dashicons-download" style="vertical-align:middle"></span>
						<?php esc_html_e( 'Export Settings', 'spelhubben-weather' ); ?>
					</button>
				</form>
                
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="svv-import-form" style="margin:0 0 0 8px; display:inline-flex; gap:8px; align-items:center;">
					<input type="hidden" name="action" value="svv_import_settings" />
					<?php wp_nonce_field( 'svv_import_settings_action', 'svv_import_settings_nonce' ); ?>
					<label class="svv-file-btn">
						<span class="dashicons dashicons-upload" aria-hidden="true"></span>
						<span class="svv-file-label-text"><?php esc_html_e( 'Choose File', 'spelhubben-weather' ); ?></span>
						<input type="file" name="svv_import_file" accept="application/json,.json" class="svv-file-input" />
					</label>
					<span class="svv-file-name" data-default="<?php echo esc_attr__( 'No file chosen', 'spelhubben-weather' ); ?>"><?php esc_html_e( 'No file chosen', 'spelhubben-weather' ); ?></span>
					<button class="button" name="svv_import_settings" value="1">
						<span class="dashicons dashicons-download" style="transform: rotate(180deg); vertical-align:middle"></span>
						<?php esc_html_e( 'Import Settings', 'spelhubben-weather' ); ?>
					</button>
				</form>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=sv-vader-shortcodes' ) ); ?>">
					<span class="dashicons dashicons-editor-code" style="vertical-align:middle"></span>
					<?php esc_html_e( 'Open Shortcodes', 'spelhubben-weather' ); ?>
				</a>
			</div>

			<div class="svv-grid svv-grid--settings">
				<div class="svv-col">
					<div class="svv-card">
						<h2 class="svv-card-title" style="display:flex; align-items:center; gap:8px;">
							<span class="dashicons dashicons-admin-generic"></span>
							<?php esc_html_e( 'General', 'spelhubben-weather' ); ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-left:auto;">
								<?php wp_nonce_field( 'svv_reset_defaults', 'svv_reset_nonce' ); ?>
								<input type="hidden" name="action" value="svv_reset_defaults" />
								<button type="submit" class="svv-tag button" style="cursor:pointer"><?php esc_html_e( 'Reset to defaults', 'spelhubben-weather' ); ?></button>
							</form>
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
							<div class="svv-kv-val">
								<div class="svv-attr-box">
									<div class="svv-attr-legend">
										<span class="svv-chip svv-chip-muted"><?php esc_html_e( 'Locked', 'spelhubben-weather' ); ?></span>
										<span class="svv-chip"><?php esc_html_e( 'OSM / ODbL', 'spelhubben-weather' ); ?></span>
									</div>
									<div class="svv-attr-preview" aria-hidden="false">
										<span class="dashicons dashicons-location-alt" aria-hidden="true" style="margin-right:8px; color:var(--svv-accent);"></span>
										<span class="svv-attr-preview-inner"><?php echo wp_kses_post( SV_VADER_ATTRIB_HTML ); ?></span>
									</div>
									<textarea readonly class="svv-attr-textarea svv-hidden" aria-readonly="true" aria-label="<?php echo esc_attr_x( 'Attribution (HTML)', 'aria label', 'spelhubben-weather' ); ?>"><?php echo esc_textarea( SV_VADER_ATTRIB_HTML ); ?></textarea>
									<div class="svv-attr-actions" style="display:flex; gap:8px; margin-top:8px; align-items:center;">
										<button type="button" class="button svv-copy-btn" data-copy="<?php echo esc_attr( SV_VADER_ATTRIB_HTML ); ?>"><?php esc_html_e( 'Copy attribution (HTML)', 'spelhubben-weather' ); ?></button>
										<button type="button" class="button svv-copy-btn" data-copy="<?php echo esc_attr( wp_strip_all_tags( SV_VADER_ATTRIB_HTML ) ); ?>"><?php esc_html_e( 'Copy plain text', 'spelhubben-weather' ); ?></button>
										<button type="button" class="button svv-attr-toggle" aria-expanded="false"><?php esc_html_e( 'Show HTML', 'spelhubben-weather' ); ?></button>
										<button type="button" class="button svv-attr-check-btn" style="margin-left:8px;">
											<span class="dashicons dashicons-search" aria-hidden="true"></span>
											<?php esc_html_e( 'Check attribution', 'spelhubben-weather' ); ?>
										</button>
										<span class="svv-attr-check-status" aria-live="polite" style="margin-left:auto; color:var(--svv-muted); font-size:13px; display:flex; align-items:center; gap:8px;">
											<!-- status inserted by JS -->
										</span>
									</div>
								</div>
							</div>
						</div>
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
						<p class="description"><span id="svv-tip-text" aria-live="polite"><?php esc_html_e( 'Use the Shortcodes page to quickly copy examples and see supported attributes.', 'spelhubben-weather' ); ?></span></p>
						<div class="svv-tip-footer">
							<div class="svv-tip-actions">
								<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=sv-vader-shortcodes' ) ); ?>"><?php esc_html_e( 'Shortcodes', 'spelhubben-weather' ); ?></a>
								<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=sv-vader-alerts' ) ); ?>"><?php esc_html_e( 'Alerts', 'spelhubben-weather' ); ?></a>
								<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=sv-vader-performance' ) ); ?>"><?php esc_html_e( 'Performance', 'spelhubben-weather' ); ?></a>
							</div>
							<div class="svv-tip-badges">
								<span class="svv-badge"><span class="dashicons dashicons-shortcode"></span><?php esc_html_e( 'Shortcode', 'spelhubben-weather' ); ?></span>
								<span class="svv-badge"><span class="dashicons dashicons-schedule"></span><?php esc_html_e( 'Forecast', 'spelhubben-weather' ); ?></span>
								<span class="svv-badge"><span class="dashicons dashicons-location-alt"></span><?php esc_html_e( 'Smart map', 'spelhubben-weather' ); ?></span>
							</div>
						</div>
						<script>
						(function(){
							const svvTips = <?php echo wp_json_encode( array(
								__( 'Use the Shortcodes page to quickly copy examples and see supported attributes.', 'spelhubben-weather' ),
								__( 'Change icon style in Settings → Spelhubben Weather → Icon style to try different themes.', 'spelhubben-weather' ),
								__( 'Enable the smart map to use OpenLayers first, Leaflet legacy when selected, or a static fallback if scripts are blocked.', 'spelhubben-weather' ),
								__( 'Shortcodes support a `layout` attribute to switch between compact and detailed views.', 'spelhubben-weather' )
							) ); ?>;

							const el = document.getElementById('svv-tip-text');
							if (!el || !Array.isArray(svvTips) || svvTips.length === 0) return;

							let idx = Math.floor(Math.random() * svvTips.length);
							function showTip(i){ el.textContent = svvTips[i]; }
							showTip(idx);

							// Rotate tips every 15 seconds (give user time to read)
							setInterval(function(){ idx = (idx + 1) % svvTips.length; showTip(idx); }, 15000);
						})();
						</script>
                        
					</div>
				</div>
			</div><!-- /.svv-grid -->

			<!-- More plugins by Spelhubben - lazy loaded -->
			<div id="svv-plugin-showcase" style="margin-top: 30px; margin-bottom: 20px;">
				<p style="color: #666; font-style: italic;">
					<?php esc_html_e( 'Loading other Spelhubben plugins…', 'spelhubben-weather' ); ?>
				</p>
			</div>
			<script>
				document.addEventListener('DOMContentLoaded', function() {
					fetch(ajaxurl || '/wp-admin/admin-ajax.php', {
						method: 'POST',
						credentials: 'same-origin',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: 'action=svv_load_wporg_showcase&nonce=' + encodeURIComponent((window.SVV_ADMIN_I18N && SVV_ADMIN_I18N.ajax_nonce) || '')
					})
					.then(r => r.json())
					.then(data => {
						const el = document.getElementById('svv-plugin-showcase');
						if (el && data.success && data.data) {
							el.innerHTML = data.data;
						}
					})
					.catch(() => {
						const el = document.getElementById('svv-plugin-showcase');
						if (el) {
							el.innerHTML = '<p style="color: #999;"><?php echo esc_html__( 'Could not load plugin showcase.', 'spelhubben-weather' ); ?></p>';
						}
					});
				});
			</script>
		</div><!-- /.wrap -->
		<?php
	}
}
