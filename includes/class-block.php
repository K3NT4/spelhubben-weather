<?php
// includes/class-block.php
if ( ! defined( 'ABSPATH' ) ) exit;

class SV_Vader_Block {

	private $renderer;

	public function __construct( $renderer ) {
		$this->renderer = $renderer;
	}

	public function register_block() {
		$block_dir = dirname( __DIR__ ) . '/blocks/spelhubben-weather';

		register_block_type(
			$block_dir,
			[
				'render_callback' => function( $attrs ) {
					$opts = sv_vader_get_options();
					$place = trim( (string) ( $attrs['place'] ?? '' ) );
					$ort   = trim( (string) ( $attrs['ort'] ?? '' ) );
					$atts = [
						'ort'        => $place !== '' ? $place : ( $ort !== '' ? $ort : $opts['default_ort'] ),
						'lat'        => $attrs['lat'] ?? '',
						'lon'        => $attrs['lon'] ?? '',
						'show'       => $attrs['show'] ?? $opts['default_show'],
						'layout'     => $attrs['layout'] ?? $opts['default_layout'],
						'class'      => 'is-block',
						'map'        => ! empty( $attrs['map'] ) ? '1' : ( $opts['map_default'] ? '1' : '0' ),
						'map_height' => isset( $attrs['mapHeight'] ) ? (string) intval( $attrs['mapHeight'] ) : (string) $opts['map_height'],
						'map_engine' => $attrs['mapEngine'] ?? ( $opts['map_engine'] ?? 'auto' ),
						'animate'    => ! empty( $attrs['animate'] ) ? '1' : '0',
						'forecast'   => isset( $attrs['forecast'] ) ? $attrs['forecast'] : 'none',
						'hourly'     => ! empty( $attrs['hourly'] ) ? '1' : '0',
						'hours'      => isset( $attrs['hours'] ) ? (string) intval( $attrs['hours'] ) : '24',
						'theme'      => $attrs['theme'] ?? 'auto',
						'preset'     => $attrs['preset'] ?? '',
						'days'       => isset( $attrs['days'] ) ? (string) intval( $attrs['days'] ) : '5',

						'units'       => $attrs['units']       ?? $opts['units'],
						'temp_unit'   => $attrs['temp_unit']   ?? $opts['temp_unit'],
						'wind_unit'   => $attrs['wind_unit']   ?? $opts['wind_unit'],
						'precip_unit' => $attrs['precip_unit'] ?? $opts['precip_unit'],
						'date_format' => $attrs['date_format'] ?? $opts['date_format'],
						'show_alerts' => isset( $attrs['showAlerts'] ) ? ( $attrs['showAlerts'] ? '1' : '0' ) : (string) $opts['show_alerts'],
						'tides'      => ! empty( $attrs['tides'] ) ? '1' : '0',
					];
					return $this->renderer->render_shortcode( $atts );
				},
			]
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations(
				'spelhubben-weather-spelhubben-weather-editor-script',
				'spelhubben-weather',
				dirname( __DIR__ ) . '/languages'
			);
		}

		register_block_type(
			'sv/vader',
			[
				'api_version'     => 2,
				'render_callback' => function( $attrs ) {
					$opts = sv_vader_get_options();
					$place = trim( (string) ( $attrs['place'] ?? '' ) );
					$ort   = trim( (string) ( $attrs['ort'] ?? '' ) );
					$atts = [
						'ort'         => $place !== '' ? $place : ( $ort !== '' ? $ort : $opts['default_ort'] ),
						'lat'         => $attrs['lat'] ?? '',
						'lon'         => $attrs['lon'] ?? '',
						'show'        => $attrs['show'] ?? $opts['default_show'],
						'layout'      => $attrs['layout'] ?? $opts['default_layout'],
						'class'       => 'is-block',
						'map'         => ! empty( $attrs['map'] ) ? '1' : ( $opts['map_default'] ? '1' : '0' ),
						'map_height'  => isset( $attrs['mapHeight'] ) ? (string) intval( $attrs['mapHeight'] ) : (string) $opts['map_height'],
						'map_engine'  => $attrs['mapEngine'] ?? ( $opts['map_engine'] ?? 'auto' ),
						'animate'     => ! empty( $attrs['animate'] ) ? '1' : '0',
						'forecast'    => isset( $attrs['forecast'] ) ? $attrs['forecast'] : 'none',
						'hourly'      => ! empty( $attrs['hourly'] ) ? '1' : '0',
						'hours'       => isset( $attrs['hours'] ) ? (string) intval( $attrs['hours'] ) : '24',
						'theme'       => $attrs['theme'] ?? 'auto',
						'preset'      => $attrs['preset'] ?? '',
						'days'        => isset( $attrs['days'] ) ? (string) intval( $attrs['days'] ) : '5',
						'show_alerts' => isset( $attrs['showAlerts'] ) ? ( $attrs['showAlerts'] ? '1' : '0' ) : (string) $opts['show_alerts'],
						'tides'       => ! empty( $attrs['tides'] ) ? '1' : '0',
						'units'       => $opts['units'],
						'temp_unit'   => $opts['temp_unit'],
						'wind_unit'   => $opts['wind_unit'],
						'precip_unit' => $opts['precip_unit'],
						'date_format' => $opts['date_format'],
					];
					return $this->renderer->render_shortcode( $atts );
				},
				'title'       => __( 'Spelhubben Weather (legacy)', 'spelhubben-weather' ),
				'category'    => 'widgets',
				'icon'        => 'cloud',
				'style'       => 'sv-vader-style',
			]
		);
	}

	public function register_patterns() {
		if ( ! is_admin() ) {
			return; // only needed in editor
		}

		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		if ( function_exists( 'register_block_pattern_category' ) ) {
			register_block_pattern_category( 'spelhubben-weather', [ 'label' => __( 'Spelhubben Weather', 'spelhubben-weather' ) ] );
		}

		$block_name = 'spelhubben-weather/spelhubben-weather';

		$registry = 
			class_exists( 'WP_Block_Patterns_Registry' )
				? 
					WP_Block_Patterns_Registry::get_instance()
				: null;

		$patterns = [
			'spelhubben-weather/weather-map' => [
				'title'       => __( 'Weather + Map', 'spelhubben-weather' ),
				'categories'  => [ 'spelhubben-weather', 'widgets' ],
				'description' => __( 'Card layout with map and 5-day forecast.', 'spelhubben-weather' ),
				'content'     => sprintf( '<!-- wp:%1$s {"place":"Stockholm","layout":"card","map":true,"mapHeight":280,"forecast":"daily","days":5,"show":"temp,wind,icon","animate":true} /-->', $block_name ),
			],
			'spelhubben-weather/compact-weather' => [
				'title'       => __( 'Compact Weather', 'spelhubben-weather' ),
				'categories'  => [ 'spelhubben-weather', 'widgets' ],
				'description' => __( 'Compact layout without map.', 'spelhubben-weather' ),
				'content'     => sprintf( '<!-- wp:%1$s {"place":"Stockholm","layout":"compact","map":false,"forecast":"none","days":5,"show":"temp,wind,icon","animate":true} /-->', $block_name ),
			],
			'spelhubben-weather/detailed-forecast' => [
				'title'       => __( 'Detailed Forecast', 'spelhubben-weather' ),
				'categories'  => [ 'spelhubben-weather', 'widgets' ],
				'description' => __( 'Detailed layout with 7-day daily forecast.', 'spelhubben-weather' ),
				'content'     => sprintf( '<!-- wp:%1$s {"place":"Stockholm","layout":"detailed","map":false,"forecast":"daily","days":7,"show":"temp,wind,icon","animate":true} /-->', $block_name ),
			],
		];

		foreach ( $patterns as $slug => $args ) {
			if ( $registry && method_exists( $registry, 'is_registered' ) && $registry->is_registered( $slug ) ) {
				continue;
			}
			$args['blockTypes'] = [ $block_name ];
			register_block_pattern( $slug, $args );
		}
	}
}
