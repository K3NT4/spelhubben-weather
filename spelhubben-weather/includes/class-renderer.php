<?php
// includes/class-renderer.php
if (!defined('ABSPATH')) exit;

if (!class_exists('SV_Vader_Renderer')) {

class SV_Vader_Renderer {

    public function render_shortcode($atts = []) {
        $opts = sv_vader_get_options();

        $a = shortcode_atts([
            'ort'        => $opts['default_ort'],
            'lat'        => '',
            'lon'        => '',
            'show'       => $opts['default_show'],
            'layout'     => $opts['default_layout'],
            'class'      => '',
            'map'        => $opts['map_default'] ? '1' : '0',
            'map_height' => (string) $opts['map_height'],
            'providers'  => implode(',', array_keys(array_filter([
                'openmeteo'      => $opts['prov_openmeteo'],
                'smhi'           => $opts['prov_smhi'],
                'yr'             => $opts['prov_yr'],
                'metno_nowcast'  => $opts['prov_metno_nowcast'] ?? 0,
            ]))),
            'animate'    => '1',
            'forecast'   => 'none',
            'days'       => '5',
        ], $atts, 'sv_vader');

        $layout = strtolower(trim($a['layout']));
        $allowed_layouts = ['inline','compact','card','detailed'];
        if (!in_array($layout, $allowed_layouts, true)) $layout = 'card';

        $provider_list = array_filter(array_map('trim', explode(',', strtolower($a['providers']))));
        $allowed = ['openmeteo','smhi','yr','metno_nowcast'];
        $provider_list = array_values(array_intersect($provider_list, $allowed));
        if (empty($provider_list)) $provider_list = ['openmeteo'];

        $show = array_map('trim', explode(',', strtolower($a['show'])));

        // API object
        $api = new SV_Vader_API(intval($opts['cache_minutes']));
        $res = $api->get_current_weather($a['ort'], $a['lat'], $a['lon'], $provider_list, $opts['yr_contact']);
        if (is_wp_error($res)) return '<em>' . esc_html($res->get_error_message()) . '</em>';

        $temp     = isset($res['temp']) ? round($res['temp']) : null;
        $wind     = isset($res['wind']) ? round($res['wind']) : null;
        $precip   = isset($res['precip']) ? $res['precip'] : null;
        $cloud    = isset($res['cloud']) ? $res['cloud'] : null;
        $desc     = $res['desc'] ?? '';
        $icon_url = $api->map_icon_url($res['code'] ?? null);
        $name     = $res['name'];
        $lat      = $res['lat'];
        $lon      = $res['lon'];

        $forecast = [];
        if ($a['forecast'] === 'daily') {
            $forecast = $api->get_daily_forecast($a['ort'], $a['lat'], $a['lon'], intval($a['days']));
        }

        // Add new container class 'spelhubben-weather' but keep 'sv-vader' for backward compatibility
        $classes = 'sv-vader spelhubben-weather ' . $a['class'] . ' ' . ($a['animate']==='1' ? 'svv-anim' : '') . ' svv-layout-' . $layout;

        ob_start(); ?>
        <div class="<?php echo esc_attr($classes); ?>" data-svv-ro="1">
            <?php if (!empty($name) && $layout !== 'inline'): ?>
                <div class="svv-ort"><?php echo esc_html($name); ?></div>
            <?php endif; ?>

            <?php switch ($layout) {
                case 'inline': ?>
                    <div class="svv-row svv-row-inline">
                        <?php if (in_array('icon', $show, true) && $icon_url): ?>
                            <img class="svv-icon" src="<?php echo esc_url($icon_url); ?>" alt="" loading="lazy">
                        <?php endif; ?>
                        <?php if (in_array('temp', $show, true) && $temp !== null): ?>
                            <div class="svv-temp"><?php echo esc_html($temp); ?>°C</div>
                        <?php endif; ?>
                    </div>
                <?php break;

                case 'compact': ?>
                    <div class="svv-row svv-row-compact">
                        <?php if (in_array('icon', $show, true) && $icon_url): ?>
                            <img class="svv-icon" src="<?php echo esc_url($icon_url); ?>" alt="" loading="lazy">
                        <?php endif; ?>
                        <?php if (in_array('temp', $show, true) && $temp !== null): ?>
                            <div class="svv-temp"><?php echo esc_html($temp); ?>°C</div>
                        <?php endif; ?>
                        <?php if (in_array('wind', $show, true) && $wind !== null): ?>
                            <?php /* translators: %s: wind speed in m/s */ ?>
                            <span class="svv-wind svv-badge"><?php echo esc_html( sprintf( __( 'Wind %s m/s', 'spelhubben-weather' ), $wind ) ); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($desc)): ?>
                            <span class="svv-desc svv-badge"><?php echo esc_html($desc); ?></span>
                        <?php endif; ?>
                    </div>
                <?php break;

                case 'detailed': ?>
                    <div class="svv-row svv-row-detailed">
                        <?php if (in_array('icon', $show, true) && $icon_url): ?>
                            <img class="svv-icon" src="<?php echo esc_url($icon_url); ?>" alt="" loading="lazy">
                        <?php endif; ?>
                        <div class="svv-col">
                            <?php if (in_array('temp', $show, true) && $temp !== null): ?>
                                <div class="svv-temp"><?php echo esc_html($temp); ?>°C</div>
                            <?php endif; ?>
                            <div class="svv-meta">
                                <?php if (in_array('wind', $show, true) && $wind !== null): ?>
                                    <?php /* translators: %s: wind speed in m/s */ ?>
                                    <span class="svv-wind"><?php echo esc_html( sprintf( __( 'Wind: %s m/s', 'spelhubben-weather' ), $wind ) ); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($desc)): ?>
                                    <span class="svv-desc"><?php echo esc_html($desc); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="svv-extra">
                                <?php if ($precip !== null): ?>
                                    <?php /* translators: %s: precipitation in mm */ ?>
                                    <span class="svv-precip"><?php echo esc_html( sprintf( __( 'Precipitation: %s mm', 'spelhubben-weather' ), round($precip, 1) ) ); ?></span>
                                <?php endif; ?>
                                <?php if ($cloud !== null): ?>
                                    <?php /* translators: %s: cloud cover in percent */ ?>
                                    <span class="svv-cloud"><?php echo esc_html( sprintf( __( 'Cloud cover: %s%%', 'spelhubben-weather' ), $cloud ) ); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php break;

                case 'card':
                default: ?>
                    <div class="svv-row">
                        <?php if (in_array('icon', $show, true) && $icon_url): ?>
                            <img class="svv-icon" src="<?php echo esc_url($icon_url); ?>" alt="" loading="lazy">
                        <?php endif; ?>
                        <?php if (in_array('temp', $show, true) && $temp !== null): ?>
                            <div class="svv-temp"><?php echo esc_html($temp); ?>°C</div>
                        <?php endif; ?>
                    </div>

                    <div class="svv-meta">
                        <?php if (in_array('wind', $show, true) && $wind !== null): ?>
                            <?php /* translators: %s: wind speed in m/s */ ?>
                            <span class="svv-wind"><?php echo esc_html( sprintf( __( 'Wind: %s m/s', 'spelhubben-weather' ), $wind ) ); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($desc)): ?>
                            <span class="svv-desc"><?php echo esc_html($desc); ?></span>
                        <?php endif; ?>
                    </div>
                <?php break; } ?>

            <?php if ($a['map'] === '1' && $layout !== 'inline'): ?>
                <div class="svv-map"
                     data-lat="<?php echo esc_attr($lat); ?>"
                     data-lon="<?php echo esc_attr($lon); ?>"
                     data-name="<?php echo esc_attr($name); ?>"
                     style="height: <?php echo intval($a['map_height']); ?>px;"></div>

                <div class="svv-map-attrib"><?php echo wp_kses_post(SV_VADER_ATTRIB_HTML); ?></div>

                <div class="svv-map-link">
                    <a href="<?php echo esc_url('https://www.openstreetmap.org/?mlat=' . rawurlencode($lat) . '&mlon=' . rawurlencode($lon) . '#map=12/' . rawurlencode($lat) . '/' . rawurlencode($lon)); ?>"
                       target="_blank" rel="noopener"><?php esc_html_e('View on OpenStreetMap', 'spelhubben-weather'); ?></a>
                </div>
            <?php endif; ?>

            <?php if (!empty($forecast) && $layout !== 'inline') : ?>
                <div class="svv-forecast <?php echo ($a['animate']==='1' ? 'svv-anim' : ''); ?>">
                    <?php foreach ($forecast as $d):
                        $icon = $api->map_icon_url($d['code']);
                        $ts   = strtotime($d['date']);
                        $lbl  = date_i18n('D j/n', $ts);
                    ?>
                    <div class="svv-daycard">
                        <div class="svv-daylabel"><?php echo esc_html($lbl); ?></div>
                        <?php if ($icon): ?><img class="svv-dayicon" src="<?php echo esc_url($icon); ?>" alt=""><?php endif; ?>
                        <div class="svv-daytemps">
                            <span class="svv-tmax"><?php echo esc_html($d['tmax']); ?>°</span>
                            <span class="svv-tmin"><?php echo esc_html($d['tmin']); ?>°</span>
                        </div>
                        <?php if (!empty($d['desc'])): ?>
                            <div class="svv-daydesc"><?php echo esc_html($d['desc']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

} // class_exists
