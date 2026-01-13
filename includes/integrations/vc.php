<?php
// includes/integrations/vc.php
if ( ! defined( 'ABSPATH' ) ) exit;

// Register WPBakery (Visual Composer) element if VC is present
if ( function_exists( 'vc_map' ) ) {
    add_action( 'vc_before_init', function() {
        vc_map( array(
            'name' => __( 'Spelhubben Weather', 'spelhubben-weather' ),
            'base' => 'spelhubben_weather',
            'category' => __( 'Content', 'spelhubben-weather' ),
            'icon' => 'icon-wpb-vc_icon',
            'params' => array(
                array(
                    'type' => 'textfield',
                    'heading' => __( 'Place (name)', 'spelhubben-weather' ),
                    'param_name' => 'ort',
                    'description' => '',
                ),
                array(
                    'type' => 'textfield',
                    'heading' => 'Lat',
                    'param_name' => 'lat',
                ),
                array(
                    'type' => 'textfield',
                    'heading' => 'Lon',
                    'param_name' => 'lon',
                ),
                array(
                    'type' => 'dropdown',
                    'heading' => __( 'Layout', 'spelhubben-weather' ),
                    'param_name' => 'layout',
                    'value' => array(
                        __( 'Inline', 'spelhubben-weather' ) => 'inline',
                        __( 'Compact', 'spelhubben-weather' ) => 'compact',
                        __( 'Card', 'spelhubben-weather' ) => 'card',
                        __( 'Detailed', 'spelhubben-weather' ) => 'detailed',
                    ),
                    'std' => 'card',
                ),
                array(
                    'type' => 'textfield',
                    'heading' => __( 'Show fields (comma separated)', 'spelhubben-weather' ),
                    'param_name' => 'show',
                    'description' => __( 'e.g. temp,wind,wind_dir,icon', 'spelhubben-weather' ),
                ),
                array(
                    'type' => 'dropdown',
                    'heading' => __( 'Units preset', 'spelhubben-weather' ),
                    'param_name' => 'units',
                    'value' => array(
                        __( 'Metric (°C, m/s, mm)', 'spelhubben-weather' ) => 'metric',
                        __( 'Metric (°C, km/h, mm)', 'spelhubben-weather' ) => 'metric_kmh',
                        __( 'Metric (°C, knt, mm)', 'spelhubben-weather' ) => 'metric_knt',
                        __( 'Imperial (°F, mph, in)', 'spelhubben-weather' ) => 'imperial',
                    ),
                ),
                array(
                    'type' => 'dropdown',
                    'heading' => __( 'Wind unit override', 'spelhubben-weather' ),
                    'param_name' => 'wind_unit',
                    'value' => array(
                        __( '(use preset)', 'spelhubben-weather' ) => '',
                        'm/s' => 'ms',
                        'km/h' => 'kmh',
                        'mph' => 'mph',
                        'knt (knots)' => 'knt',
                    ),
                ),
                array(
                    'type' => 'checkbox',
                    'heading' => __( 'Show map', 'spelhubben-weather' ),
                    'param_name' => 'map',
                    'value' => array( __( 'Show map', 'spelhubben-weather' ) => '1' ),
                ),
                array(
                    'type' => 'textfield',
                    'heading' => __( 'Forecast', 'spelhubben-weather' ),
                    'param_name' => 'forecast',
                    'description' => __( 'Set to "daily" to show daily forecast.', 'spelhubben-weather' ),
                ),
            ),
        ) );
    } );
}

?>
