<?php
// includes/class-block.php
if (!defined('ABSPATH')) exit;

class SV_Vader_Block {

    private $renderer;

    public function __construct($renderer) {
        $this->renderer = $renderer;
    }

    public function register_block() {
        // Registrera blocket från block.json och koppla vår PHP-render
        $block_dir = dirname(__DIR__) . '/blocks/spelhubben-weather';

        register_block_type(
            $block_dir,
            [
                'render_callback' => function($attrs) {
                    $opts = sv_vader_get_options();

                    // place -> ort fallback
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
                    ];
                    return $this->renderer->render_shortcode($atts);
                },
            ]
        );

        // (Valfritt) Behåll legacy block-namnet för bakåtkomp om du redan använder det i innehåll:
        register_block_type(
            'sv/vader',
            [
                'api_version'     => 2,
                'render_callback' => function($attrs) {
                    // samma mappning men utan "place"
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
                    ];
                    return $this->renderer->render_shortcode($atts);
                },
                'title'       => __('Spelhubben Weather (legacy)', 'spelhubben-weather'),
                'category'    => 'widgets',
                'icon'        => 'cloud',
                'style'       => 'sv-vader-style',
                'attributes'  => [
                    'ort'       => ['type' => 'string', 'default' => 'Stockholm'],
                    'lat'       => ['type' => 'string', 'default' => ''],
                    'lon'       => ['type' => 'string', 'default' => ''],
                    'show'      => ['type' => 'string', 'default' => 'temp,wind,icon'],
                    'layout'    => ['type' => 'string', 'default' => 'card'],
                    'map'       => ['type' => 'boolean', 'default' => false],
                    'mapHeight' => ['type' => 'number',  'default' => 240],
                    'animate'   => ['type' => 'boolean', 'default' => true],
                    'forecast'  => ['type' => 'string',  'default' => 'none'],
                    'days'      => ['type' => 'number',  'default' => 5],
                ],
            ]
        );
    }
}
