<?php
// includes/class-block.php
if (!defined('ABSPATH')) exit;

class SV_Vader_Block {

	private $renderer;

	public function __construct($renderer) {
		$this->renderer = $renderer;
	}

	public function register_block() {
		$block_dir = dirname(__DIR__) . '/blocks/spelhubben-weather';

		register_block_type(
			$block_dir,
			[
				'render_callback' => function($attrs) {
					$opts = sv_vader_get_options();
					$atts = [
						'ort'        => $attrs['ort'] ?? ($attrs['place'] ?? $opts['default_ort']),
						'lat'        => $attrs['lat'] ?? '',
						'lon'        => $attrs['lon'] ?? '',
						'show'       => $attrs['show'] ?? $opts['default_show'],
						'layout'     => $attrs['layout'] ?? $opts['default_layout'],
						'class'      => 'is-block',
						'map'        => !empty($attrs['map']) ? '1' : ($opts['map_default'] ? '1' : '0'),
						'map_height' => isset($attrs['mapHeight']) ? (string)intval($attrs['mapHeight']) : (string)$opts['map_height'],
						'animate'    => !empty($attrs['animate']) ? '1' : '0',
						'forecast'   => isset($attrs['forecast']) ? $attrs['forecast'] : 'none',
						'days'       => isset($attrs['days']) ? (string)intval($attrs['days']) : '5',

						// NEW: Units & format
						'units'       => $attrs['units']       ?? $opts['units'],
						'temp_unit'   => $attrs['temp_unit']   ?? $opts['temp_unit'],
						'wind_unit'   => $attrs['wind_unit']   ?? $opts['wind_unit'],
						'precip_unit' => $attrs['precip_unit'] ?? $opts['precip_unit'],
						'date_format' => $attrs['date_format'] ?? $opts['date_format'],
					];
					return $this->renderer->render_shortcode($atts);
				},
			]
		);

		// Legacy block kept (unchanged except it inherits global options at render time)
		register_block_type(
			'sv/vader',
			[
				'api_version'     => 2,
				'render_callback' => function($attrs) {
					$opts = sv_vader_get_options();
					$atts = [
						'ort'        => $attrs['ort'] ?? $opts['default_ort'],
						'lat'        => $attrs['lat'] ?? '',
						'lon'        => $attrs['lon'] ?? '',
						'show'       => $attrs['show'] ?? $opts['default_show'],
						'layout'     => $attrs['layout'] ?? $opts['default_layout'],
						'class'      => 'is-block',
						'map'        => !empty($attrs['map']) ? '1' : ($opts['map_default'] ? '1' : '0'),
						'map_height' => isset($attrs['mapHeight']) ? (string)intval($attrs['mapHeight']) : (string)$opts['map_height'],
						'animate'    => !empty($attrs['animate']) ? '1' : '0',
						'forecast'   => isset($attrs['forecast']) ? $attrs['forecast'] : 'none',
						'days'       => isset($attrs['days']) ? (string)intval($attrs['days']) : '5',
						'units'       => $opts['units'],
						'temp_unit'   => $opts['temp_unit'],
						'wind_unit'   => $opts['wind_unit'],
						'precip_unit' => $opts['precip_unit'],
						'date_format' => $opts['date_format'],
					];
					return $this->renderer->render_shortcode($atts);
				},
				'title'       => __('Spelhubben Weather (legacy)', 'spelhubben-weather'),
				'category'    => 'widgets',
				'icon'        => 'cloud',
				'style'       => 'sv-vader-style',
			]
		);
	}
}
