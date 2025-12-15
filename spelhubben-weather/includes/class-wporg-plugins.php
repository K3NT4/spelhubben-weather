<?php
// includes/class-wporg-plugins.php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles fetching and rendering "Other plugins by Spelhubben" cards from WP.org.
 */
if ( ! class_exists( 'SV_Vader_WPOrg_Plugins' ) ) {

	class SV_Vader_WPOrg_Plugins {

		/**
		 * Transient key version. Bump to invalidate cache.
		 */
		private $transient_key = 'spelhubben_plugins_list_v2';

		/**
		 * Enqueue required assets (plugin cards + thickbox) and scoped CSS.
		 */
		public function enqueue_assets( $hook ) {
			// Only needed on Spelhubben Weather admin pages
			if ( strpos( $hook, 'sv-vader' ) === false ) {
				return;
			}

			// Force refresh via query arg (admin only)
			if ( current_user_can( 'manage_options' ) && isset( $_GET['svv_wporg_refresh'], $_GET['svv_wporg_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['svv_wporg_nonce'] ) ), 'svv_wporg_refresh_nonce' ) && $_GET['svv_wporg_refresh'] === '1' ) {
				delete_transient( $this->transient_key );
			}

			// WP core assets for plugin cards + modal
			wp_enqueue_style( 'plugin-install' );
			wp_enqueue_script( 'plugin-install' );
			wp_enqueue_script( 'updates' );
			add_thickbox();

			$css = '
				.svv-plugin-cards { max-width: 100%; box-sizing: border-box; }
				.svv-plugin-cards * { box-sizing: border-box; }

				.svv-plugin-cards.plugin-install {
					background: transparent !important;
					border: 0 !important;
					padding: 0 !important;
					margin: 0 !important;
					display: grid !important;
					grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)) !important;
					gap: 16px !important;
				}

				.svv-plugin-cards .plugin-card {
					border-radius: 8px;
					overflow: hidden;
					border: 1px solid #ddd;
					box-shadow: 0 1px 3px rgba(0,0,0,0.1);
					margin-bottom: 0 !important;
					display: flex;
					flex-direction: column;
				}

				.svv-plugin-cards .plugin-card-top,
				.svv-plugin-cards .plugin-card-bottom {
					float: none !important;
					clear: both !important;
					width: 100% !important;
				}

				.svv-plugin-cards .plugin-card-top {
					display: flex;
					flex-direction: column;
					gap: 14px;
					padding: 16px;
					border-bottom: 1px solid #f0f0f0;
					flex-grow: 1;
				}

				.svv-plugin-cards .plugin-card-top .name,
				.svv-plugin-cards .plugin-card-top .action-links,
				.svv-plugin-cards .plugin-card-top .desc {
					float: none !important;
					width: auto !important;
					margin: 0 !important;
				}

				.svv-plugin-cards .plugin-card-top .name {
					display: flex;
					align-items: flex-start;
					gap: 10px;
				}

				.svv-plugin-cards .plugin-card-top .name h3 {
					margin: 0 !important;
					font-size: 15px;
					line-height: 1.5;
					flex: 1;
				}

				.svv-plugin-cards .plugin-card-top .name h3 a {
					display: flex;
					align-items: center;
					gap: 8px;
					text-decoration: none;
				}

				.svv-plugin-cards .plugin-card-top .plugin-icon {
					float: none !important;
					margin: 0 !important;
					width: 44px !important;
					height: 44px !important;
					border-radius: 5px;
					object-fit: cover;
					flex-shrink: 0;
				}

				.svv-plugin-cards .plugin-card-top .action-links {
					align-self: flex-start;
					margin-top: auto !important;
				}

				.svv-plugin-cards .plugin-card-top .plugin-action-buttons {
					display: flex;
					gap: 8px;
					flex-wrap: wrap;
					margin: 0 !important;
				}

				.svv-plugin-cards .plugin-card-top .plugin-action-buttons li {
					margin: 0 !important;
				}

				.svv-plugin-cards .plugin-card-top .desc {
					font-size: 14px;
					color: #666;
					line-height: 1.6;
				}

				.svv-plugin-cards .plugin-card-bottom {
					display: flex;
					flex-wrap: wrap;
					gap: 14px;
					align-items: center;
					padding: 12px 16px;
					font-size: 13px;
					border-top: 1px solid #f0f0f0;
					margin-top: auto;
				}

				.svv-plugin-cards .plugin-card-bottom .vers,
				.svv-plugin-cards .plugin-card-bottom .column-updated,
				.svv-plugin-cards .plugin-card-bottom .column-downloaded {
					float: none !important;
					width: auto !important;
					margin: 0 !important;
				}

				.svv-plugin-cards .plugin-card-bottom .vers strong,
				.svv-plugin-cards .plugin-card-bottom .column-updated strong,
				.svv-plugin-cards .plugin-card-bottom .column-downloaded strong {
					display: block;
					font-size: 11px;
					color: #999;
					margin-bottom: 2px;
				}

				@media (max-width: 900px) {
					.svv-plugin-cards.plugin-install {
						grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)) !important;
					}
				}

				@media (max-width: 600px) {
					.svv-plugin-cards.plugin-install {
						grid-template-columns: 1fr !important;
					}
				}
			';
			wp_add_inline_style( 'plugin-install', $css );
		}

		/**
		 * Render HTML block for other plugins section.
		 */
		public function render() {
			$plugins = $this->get_plugins();

			if ( is_wp_error( $plugins ) ) {
				$msg = esc_html__( 'Could not fetch plugin list right now.', 'spelhubben-weather' );
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					$msg .= ' ' . esc_html( $plugins->get_error_message() );
				}
				$refresh = current_user_can( 'manage_options' )
					? ' <a href="' . esc_url( wp_nonce_url( add_query_arg( 'svv_wporg_refresh', '1' ), 'svv_wporg_refresh_nonce', 'svv_wporg_nonce' ) ) . '">' . esc_html__( 'Retry', 'spelhubben-weather' ) . '</a>'
					: '';
				return '<p>' . $msg . $refresh . '</p>';
			}

			if ( empty( $plugins ) || ! is_array( $plugins ) ) {
				return '<p>' . esc_html__( 'No other plugins found.', 'spelhubben-weather' ) . '</p>';
			}

			// Get current plugin slug to exclude it
			$current_plugin_slug = $this->get_current_plugin_slug();

			// Pre-filter renderable items (exclude current plugin)
			$render_list = array();
			foreach ( $plugins as $p ) {
				list($slug, $wporg_url) = $this->extract_slug_and_url( $p );
				if ( $slug === '' || $slug === $current_plugin_slug ) {
					continue;
				}
				$render_list[] = array( $p, $slug, $wporg_url );
			}

			// Return empty if only the current plugin was found
			if ( empty( $render_list ) ) {
				return '';
			}

			ob_start();

			echo '<p style="margin:0 0 20px 0; font-weight:600; font-size:16px;">' . esc_html__( 'Other plugins by Spelhubben:', 'spelhubben-weather' ) . '</p>';

			echo '<div class="svv-plugin-cards plugin-install">';

			foreach ( $render_list as $row ) {
				list($p, $slug, $wporg_url) = $row;

				$name            = (string) $this->pl_get( $p, 'name', $slug );
				$desc            = (string) $this->pl_get( $p, 'short_description', '' );
				$author          = (string) $this->pl_get( $p, 'author', '' );
				$rating          = (int) $this->pl_get( $p, 'rating', 0 );
				$num_ratings     = (int) $this->pl_get( $p, 'num_ratings', 0 );
				$active_installs = (int) $this->pl_get( $p, 'active_installs', 0 );
				$tested          = (string) $this->pl_get( $p, 'tested', '' );

				$icons = $this->pl_get( $p, 'icons', array() );
				if ( is_object( $icons ) ) {
					$icons = (array) $icons;
				}
				if ( ! is_array( $icons ) ) {
					$icons = array();
				}

				$icon = '';
				if ( ! empty( $icons['1x'] ) ) {
					$icon = $icons['1x'];
				} elseif ( ! empty( $icons['default'] ) ) {
					$icon = $icons['default'];
				} else {
					$legacy_icon = (string) $this->pl_get( $p, 'icon', '' );
					if ( $legacy_icon !== '' ) {
						$icon = $legacy_icon;
					}
				}

				$details_url = admin_url(
					'plugin-install.php?tab=plugin-information&plugin=' . rawurlencode( $slug ) . '&TB_iframe=true&width=772&height=600'
				);

				echo '<div class="plugin-card plugin-card-' . esc_attr( $slug ) . '">';

					echo '<div class="plugin-card-top">';

						echo '<div class="name column-name">';
							echo '<h3>';
								echo '<a class="thickbox open-plugin-details-modal" href="' . esc_url( $details_url ) . '">';
									if ( $icon ) {
										echo '<img class="plugin-icon" src="' . esc_url( $icon ) . '" alt="" />';
									} else {
										echo '<span class="plugin-icon dashicons dashicons-admin-plugins" aria-hidden="true" style="font-size:32px; width:40px; height:40px; line-height:40px;"></span>';
									}
									echo esc_html( $name );
								echo '</a>';
							echo '</h3>';
						echo '</div>';

						echo '<div class="action-links">';
							echo '<ul class="plugin-action-buttons">';
								echo '<li><a class="button button-small thickbox open-plugin-details-modal" href="' . esc_url( $details_url ) . '">' . esc_html__( 'Details', 'spelhubben-weather' ) . '</a></li>';
								echo '<li><a class="button button-small" href="' . esc_url( $wporg_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'WP.org', 'spelhubben-weather' ) . '</a></li>';
							echo '</ul>';
						echo '</div>';

						echo '<div class="desc column-description">';
							if ( $desc !== '' ) {
								echo '<p>' . esc_html( $desc ) . '</p>';
							}
						echo '</div>';

					echo '</div>';

					echo '<div class="plugin-card-bottom">';

						echo '<div class="vers column-rating">';
							if ( function_exists( 'wp_star_rating' ) ) {
								echo wp_kses_post( wp_star_rating( array(
									'rating' => $rating,
									'type'   => 'percent',
									'number' => $num_ratings,
									'echo'   => false,
								) ) );
							}
							echo '<span class="num-ratings">(' . esc_html( number_format_i18n( $num_ratings ) ) . ')</span>';
						echo '</div>';

						echo '<div class="column-updated">';
							echo '<strong>' . esc_html__( 'Active:', 'spelhubben-weather' ) . '</strong> ';
							echo esc_html( $this->format_installs( $active_installs ) );
						echo '</div>';

						echo '<div class="column-downloaded">';
							echo '<strong>' . esc_html__( 'Tested:', 'spelhubben-weather' ) . '</strong> ';
							echo $tested ? esc_html( $tested ) : esc_html__( 'Unknown', 'spelhubben-weather' );
						echo '</div>';

					echo '</div>';

				echo '</div>';
			}

			echo '</div>';

			return ob_get_clean();
		}

		/**
		 * Fetch plugin list from WP.org with caching.
		 */
		public function get_plugins() {
			// Purge older cache keys
			delete_transient( 'spelhubben_plugins_list_v1' );

			$cached = get_transient( $this->transient_key );

			// Return cached list if available
			if ( is_array( $cached ) && ! empty( $cached ) ) {
				return $cached;
			}

			$user = 'spelhubben';

			$fields = array(
				'short_description' => true,
				'icons'             => true,
				'rating'            => true,
				'num_ratings'       => true,
				'active_installs'   => true,
				'tested'            => true,
				'author'            => true,
				'homepage'          => true,
			);

			$plugins = $this->fetch_plugins_by_author_api( $user, 12, $fields );
			if ( is_wp_error( $plugins ) ) {
				return $plugins;
			}

			if ( empty( $plugins ) ) {
				return array();
			}

			set_transient( $this->transient_key, $plugins, DAY_IN_SECONDS );
			return $plugins;
		}

		/**
		 * Fetch plugins via WP.org author query API.
		 */
		private function fetch_plugins_by_author_api( $username, $limit, $fields ) {
			$limit = max( 1, (int) $limit );
			$url   = add_query_arg(
				array(
					'action'                => 'query_plugins',
					'request[author]'       => $username,
					'request[page]'         => 1,
					'request[per_page]'     => $limit,
				),
				'https://api.wordpress.org/plugins/info/1.2/'
			);

			foreach ( (array) $fields as $k => $v ) {
				$url = add_query_arg( 'request[fields][' . $k . ']', $v ? '1' : '0', $url );
			}

			$res = wp_remote_get(
				$url,
				array(
					'timeout'     => 15,
					'redirection' => 3,
					'user-agent'  => 'Spelhubben-Weather/1.8.0; ' . home_url( '/' ),
				)
			);

			if ( is_wp_error( $res ) ) {
				return $res;
			}

			$code = (int) wp_remote_retrieve_response_code( $res );
			$body = (string) wp_remote_retrieve_body( $res );

			if ( $code < 200 || $code >= 300 || $body === '' ) {
				return array();
			}

			$data = json_decode( $body );
			if ( ! is_object( $data ) || empty( $data->plugins ) || ! is_array( $data->plugins ) ) {
				return array();
			}

			return $this->normalize_plugins_list( $data->plugins );
		}

		/**
		 * Normalize plugin list from API response.
		 */
		private function normalize_plugins_list( $list ) {
			if ( ! is_array( $list ) ) {
				$list = (array) $list;
			}

			$plugins = array();

			foreach ( $list as $plugin ) {
				if ( is_array( $plugin ) ) {
					$plugin = (object) $plugin;
				}
				if ( ! is_object( $plugin ) ) {
					continue;
				}

				$slug = isset( $plugin->slug ) ? (string) $plugin->slug : '';
				$plugins[] = array(
					'name'              => isset( $plugin->name ) ? (string) $plugin->name : '',
					'slug'              => $slug,
					'url'               => $slug ? ( 'https://wordpress.org/plugins/' . $slug . '/' ) : ( isset( $plugin->homepage ) ? (string) $plugin->homepage : '' ),
					'short_description' => isset( $plugin->short_description ) ? (string) $plugin->short_description : '',
					'icons'             => isset( $plugin->icons ) ? (array) $plugin->icons : array(),
					'rating'            => isset( $plugin->rating ) ? (int) $plugin->rating : 0,
					'num_ratings'       => isset( $plugin->num_ratings ) ? (int) $plugin->num_ratings : 0,
					'active_installs'   => isset( $plugin->active_installs ) ? (int) $plugin->active_installs : 0,
					'tested'            => isset( $plugin->tested ) ? (string) $plugin->tested : '',
					'author'            => isset( $plugin->author ) ? (string) $plugin->author : '',
					'homepage'          => isset( $plugin->homepage ) ? (string) $plugin->homepage : '',
				);
			}

			return $plugins;
		}

		/**
		 * Helper: get value from array/object with default.
		 */
		private function pl_get( $item, $key, $default = '' ) {
			if ( is_array( $item ) && isset( $item[ $key ] ) ) {
				return $item[ $key ];
			}
			if ( is_object( $item ) && isset( $item->{$key} ) ) {
				return $item->{$key};
			}
			return $default;
		}

		/**
		 * Extract slug and URL from plugin item.
		 */
		private function extract_slug_and_url( $item ) {
			$slug = '';
			$url  = '';

			if ( is_string( $item ) ) {
				$candidate = trim( $item );
				if ( $candidate !== '' ) {
					if ( preg_match( '~wordpress\.org/plugins/([^/]+)/~', $candidate, $m ) ) {
						$slug = sanitize_key( $m[1] );
						$url  = 'https://wordpress.org/plugins/' . $slug . '/';
					} else {
						$slug = sanitize_key( $candidate );
						$url  = 'https://wordpress.org/plugins/' . $slug . '/';
					}
				}
				return array( $slug, $url );
			}

			$slug = (string) $this->pl_get( $item, 'slug', '' );
			$url  = (string) $this->pl_get( $item, 'url', '' );

			if ( $url === '' ) {
				$url = (string) $this->pl_get( $item, 'plugin_url', '' );
			}
			if ( $url === '' ) {
				$url = (string) $this->pl_get( $item, 'homepage', '' );
			}

			if ( $slug === '' && $url !== '' ) {
				if ( preg_match( '~wordpress\.org/plugins/([^/]+)/~', $url, $m ) ) {
					$slug = sanitize_key( $m[1] );
				}
			}

			if ( $slug !== '' && $url === '' ) {
				$url = 'https://wordpress.org/plugins/' . $slug . '/';
			}

			return array( $slug, $url );
		}

		/**
		 * Format large numbers for display.
		 */
		private function format_installs( $num ) {
			$num = (int) $num;
			if ( $num >= 1000000 ) {
				return round( $num / 1000000, 1 ) . 'M+';
			} elseif ( $num >= 1000 ) {
				return round( $num / 1000, 1 ) . 'K+';
			}
			return number_format_i18n( $num );
		}

		/**
		 * Get current plugin slug to exclude from showcase.
		 */
		private function get_current_plugin_slug() {
			// Get the current plugin's file path (spelhubben-weather.php)
			$plugin_file = defined( 'SV_VADER_FILE' ) ? SV_VADER_FILE : __FILE__;
			
			// Extract directory name as plugin slug
			$plugin_dir = basename( dirname( dirname( $plugin_file ) ) );
			
			return $plugin_dir;
		}
	}
}
