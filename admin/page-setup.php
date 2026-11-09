<?php
// admin/page-setup.php - "Getting started & requirements" checklist and activation notice.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'sv_vader_setup_checks' ) ) {
	/**
	 * Evaluate what the plugin needs in order to work on this site.
	 *
	 * Each check: [ 'status' => ok|warn|error, 'label' => string, 'detail' => string ].
	 * Pure (no output) so it can be unit tested.
	 *
	 * @param array|null $opts Plugin options (defaults to saved options).
	 * @return array[]
	 */
	function sv_vader_setup_checks( $opts = null ) {
		if ( $opts === null ) {
			$opts = function_exists( 'sv_vader_get_options' ) ? sv_vader_get_options() : [];
		}
		$checks = [];

		// PHP / WordPress versions.
		$php_ok = version_compare( PHP_VERSION, '7.4', '>=' );
		$checks[] = [
			'status' => $php_ok ? 'ok' : 'error',
			/* translators: %s: PHP version number. */
			'label'  => sprintf( __( 'PHP %s', 'spelhubben-weather' ), PHP_VERSION ),
			'detail' => $php_ok ? __( 'Requires PHP 7.4 or newer.', 'spelhubben-weather' ) : __( 'The plugin requires PHP 7.4 or newer. Ask your host to upgrade PHP.', 'spelhubben-weather' ),
		];

		$wp_version = function_exists( 'get_bloginfo' ) ? (string) get_bloginfo( 'version' ) : '';
		$wp_ok = $wp_version === '' || version_compare( $wp_version, '6.8', '>=' );
		$checks[] = [
			'status' => $wp_ok ? 'ok' : 'error',
			/* translators: %s: WordPress version number. */
			'label'  => sprintf( __( 'WordPress %s', 'spelhubben-weather' ), $wp_version ),
			'detail' => $wp_ok ? __( 'Requires WordPress 6.8 or newer.', 'spelhubben-weather' ) : __( 'The plugin requires WordPress 6.8 or newer.', 'spelhubben-weather' ),
		];

		// Outbound requests are how all weather data is fetched.
		$blocked = defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL;
		$checks[] = [
			'status' => $blocked ? 'error' : 'ok',
			'label'  => __( 'Server can reach weather services', 'spelhubben-weather' ),
			'detail' => $blocked
				? __( 'WP_HTTP_BLOCK_EXTERNAL is enabled, which blocks all weather requests. Allow the provider hosts via WP_ACCESSIBLE_HOSTS or disable the block.', 'spelhubben-weather' )
				: __( 'Weather data is fetched from your server, so it needs outgoing HTTPS access (firewalls may block it).', 'spelhubben-weather' ),
		];

		// Providers.
		$enabled = function_exists( 'sv_vader_enabled_provider_ids' ) ? sv_vader_enabled_provider_ids( $opts ) : [];
		$checks[] = [
			'status' => empty( $enabled ) ? 'error' : 'ok',
			'label'  => __( 'At least one weather provider is enabled', 'spelhubben-weather' ),
			'detail' => empty( $enabled )
				? __( 'Enable a provider under Providers below. Open-Meteo, SMHI and Yr need no API key.', 'spelhubben-weather' )
				: __( 'Open-Meteo, SMHI, Yr, MET Nowcast and FMI need no API key.', 'spelhubben-weather' ),
		];

		if ( function_exists( 'sv_vader_provider_registry' ) ) {
			foreach ( sv_vader_provider_registry() as $id => $provider ) {
				if ( empty( $provider['requires_key'] ) || ! in_array( $id, $enabled, true ) ) {
					continue;
				}
				$key = trim( (string) ( $opts[ $provider['key_option'] ] ?? '' ) );
				if ( $key === '' ) {
					$checks[] = [
						'status' => 'warn',
						/* translators: %s: weather provider name. */
						'label'  => sprintf( __( '%s needs an API key', 'spelhubben-weather' ), $provider['label'] ),
						'detail' => __( 'The provider is enabled but no API key is saved, so it is skipped. Add the key or disable the provider.', 'spelhubben-weather' ),
					];
				}
			}
		}

		// MET Norway asks for contact details in the User-Agent.
		if ( ( in_array( 'yr', $enabled, true ) || in_array( 'metno_nowcast', $enabled, true ) ) && trim( (string) ( $opts['yr_contact'] ?? '' ) ) === '' ) {
			$checks[] = [
				'status' => 'warn',
				'label'  => __( 'Contact for Yr / MET Norway is empty', 'spelhubben-weather' ),
				'detail' => __( 'MET Norway asks API users to identify themselves. Enter an email or website URL in "Yr contact" to avoid being rate limited.', 'spelhubben-weather' ),
			];
		}

		// Default place.
		if ( trim( (string) ( $opts['default_ort'] ?? '' ) ) === '' ) {
			$checks[] = [
				'status' => 'warn',
				'label'  => __( 'No default place set', 'spelhubben-weather' ),
				'detail' => __( 'Set a default place, or always give place or lat/lon in the block, shortcode or widget.', 'spelhubben-weather' ),
			];
		}

		return $checks;
	}
}

if ( ! function_exists( 'sv_vader_render_setup_card' ) ) {
	/**
	 * Render the "Getting started" card on the settings page.
	 */
	function sv_vader_render_setup_card() {
		$checks   = sv_vader_setup_checks();
		$problems = 0;
		foreach ( $checks as $c ) {
			if ( $c['status'] !== 'ok' ) $problems++;
		}
		$icons  = [ 'ok' => 'yes-alt', 'warn' => 'warning', 'error' => 'dismiss' ];
		$colors = [ 'ok' => '#00a32a', 'warn' => '#dba617', 'error' => '#d63638' ];
		?>
		<div class="svv-card svv-setup">
			<h2 class="svv-card-title">
				<span class="dashicons dashicons-clipboard"></span>
				<?php esc_html_e( 'Getting started & requirements', 'spelhubben-weather' ); ?>
			</h2>
			<p class="description">
				<?php
				echo $problems === 0
					? esc_html__( 'Everything the plugin needs is in place.', 'spelhubben-weather' )
					: esc_html__( 'Fix the items marked below so the weather can be shown.', 'spelhubben-weather' );
				?>
			</p>
			<ul class="svv-setup-checks" style="margin:12px 0; padding:0; list-style:none;">
				<?php foreach ( $checks as $c ) : ?>
					<li style="display:flex; gap:8px; margin:0 0 8px;">
						<span class="dashicons dashicons-<?php echo esc_attr( $icons[ $c['status'] ] ); ?>" style="color:<?php echo esc_attr( $colors[ $c['status'] ] ); ?>; flex:none;" aria-hidden="true"></span>
						<span>
							<strong><?php echo esc_html( $c['label'] ); ?></strong><br />
							<span class="description"><?php echo esc_html( $c['detail'] ); ?></span>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>

			<h3 style="margin:16px 0 6px;"><?php esc_html_e( 'Show the weather on your site', 'spelhubben-weather' ); ?></h3>
			<ol style="margin:0 0 12px 18px;">
				<li><?php esc_html_e( 'Block: edit a page, click Add block and search for "Spelhubben Weather".', 'spelhubben-weather' ); ?></li>
				<li><?php echo wp_kses( __( 'Shortcode: paste <code>[spelhubben_weather place="Stockholm"]</code> in a page, post or text widget.', 'spelhubben-weather' ), [ 'code' => [] ] ); ?></li>
				<li><?php esc_html_e( 'Widget: go to Appearance → Widgets and add "Spelhubben Weather" to a sidebar.', 'spelhubben-weather' ); ?></li>
			</ol>
			<p class="description">
				<?php esc_html_e( 'Good to know: maps need no API key, but visitors load map tiles from OpenStreetMap. If you use a caching or optimization plugin, clear its cache after changing settings. Your theme must call wp_footer() for scripts to load.', 'spelhubben-weather' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=sv-vader-shortcodes' ) ); ?>"><?php esc_html_e( 'Open Shortcodes', 'spelhubben-weather' ); ?></a>
			</p>
		</div>
		<?php
	}
}

/**
 * One-time notice after activation.
 */
if ( ! function_exists( 'sv_vader_activation_notice' ) ) {
	function sv_vader_activation_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! get_transient( 'sv_vader_show_welcome' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$on_our_page = isset( $_GET['page'] ) && sanitize_key( wp_unslash( $_GET['page'] ) ) === 'sv-vader';
		if ( $on_our_page ) {
			delete_transient( 'sv_vader_show_welcome' );
			return;
		}
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Spelhubben Weather is installed.', 'spelhubben-weather' ); ?></strong>
				<?php esc_html_e( 'Open the settings to check the requirements, pick your place and providers, and see how to add the weather to your site.', 'spelhubben-weather' ); ?>
				<a class="button button-primary" style="margin-left:8px;" href="<?php echo esc_url( admin_url( 'admin.php?page=sv-vader' ) ); ?>"><?php esc_html_e( 'Get started', 'spelhubben-weather' ); ?></a>
			</p>
		</div>
		<?php
	}
	add_action( 'admin_notices', 'sv_vader_activation_notice' );
}
