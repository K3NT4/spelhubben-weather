<?php
// includes/Widget/class-widget.php
if (!defined('ABSPATH')) exit;

class SV_Vader_Widget extends WP_Widget {

    private array $show_allowed = ['temp','wind','icon'];

    public function __construct() {
        $widget_ops = [
            'classname'                   => 'sv_vader_widget',
            'description'                 => __('Display current weather with an optional forecast.', 'spelhubben-weather'),
            'customize_selective_refresh' => true,
            'show_instance_in_rest'       => true,
        ];

        parent::__construct(
            'sv_vader_widget',
            __('Spelhubben Weather', 'spelhubben-weather'),
            $widget_ops
        );
    }

    public function widget($args, $instance) {
        $defaults = [
            'title'     => '',
            'ort'       => 'Stockholm',
            'lat'       => '',
            'lon'       => '',
            'show'      => ['temp','wind','icon'],
            'layout'    => 'card',
            'map'       => 0,
            'mapHeight' => 240,
            'animate'   => 1,
            'forecast'  => 'none',
            'days'      => 5,
            'class'     => 'is-widget',
        ];
        $instance = wp_parse_args((array) $instance, $defaults);

        $title     = isset($instance['title']) ? $instance['title'] : '';
        $ort       = sanitize_text_field($instance['ort'] ?? '');
        $lat       = sanitize_text_field($instance['lat'] ?? '');
        $lon       = sanitize_text_field($instance['lon'] ?? '');

        $show_selected = $instance['show'];
        if (is_string($show_selected)) {
            $show_selected = array_filter(array_map('trim', explode(',', $show_selected)));
        }
        if (!is_array($show_selected)) $show_selected = [];
        $show_selected = array_values(array_intersect(
            array_map('sanitize_text_field', $show_selected),
            $this->show_allowed
        ));
        $show_csv = implode(',', $show_selected);

        $layout    = sanitize_text_field($instance['layout']);
        $map       = !empty($instance['map']) ? 1 : 0;
        $mapHeight = (int) $instance['mapHeight'];
        $animate   = !empty($instance['animate']) ? 1 : 0;
        $forecast  = in_array($instance['forecast'], ['none','daily'], true) ? $instance['forecast'] : 'none';
        $days      = max(1, min(14, (int) $instance['days']));
        $extra_cls = isset($instance['class']) ? sanitize_html_class($instance['class'], '') : '';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core wrapper
        echo $args['before_widget'];

        if (!empty($title)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core wrapper
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }

        if (class_exists('SV_Vader_Renderer')) {
            $renderer = new SV_Vader_Renderer();
            $html = $renderer->render_shortcode([
                'ort'        => $ort,
                'lat'        => $lat,
                'lon'        => $lon,
                'show'       => $show_csv,
                'layout'     => $layout,
                'class'      => trim('is-widget ' . $extra_cls),
                'map'        => $map ? '1' : '0',
                'map_height' => (string) $mapHeight,
                'animate'    => $animate ? '1' : '0',
                'forecast'   => $forecast,
                'days'       => (string) $days,
            ]);

            echo wp_kses_post($html);
        } else {
            /* translators: %s: missing class name. */
            echo '<em>' . esc_html(sprintf(__('Could not load %s.', 'spelhubben-weather'), 'SV_Vader_Renderer')) . '</em>';
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core wrapper
        echo $args['after_widget'];
    }

    public function form($instance) {
        $defaults = [
            'title'     => '',
            'ort'       => 'Stockholm',
            'lat'       => '',
            'lon'       => '',
            'show'      => ['temp','wind','icon'],
            'layout'    => 'card',
            'map'       => 0,
            'mapHeight' => 240,
            'animate'   => 1,
            'forecast'  => 'none',
            'days'      => 5,
            'class'     => '',
        ];
        $instance   = wp_parse_args((array) $instance, $defaults);

        $show_selected = $instance['show'];
        if (is_string($show_selected)) $show_selected = array_filter(array_map('trim', explode(',', $show_selected)));
        if (!is_array($show_selected)) $show_selected = [];
        $show_selected = array_values(array_intersect($show_selected, $this->show_allowed));
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'spelhubben-weather'); ?></label>
            <input class="widefat"
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>"
                   type="text" value="<?php echo esc_attr($instance['title']); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('ort')); ?>"><?php esc_html_e('Place (name):', 'spelhubben-weather'); ?></label>
            <input class="widefat"
                   id="<?php echo esc_attr($this->get_field_id('ort')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('ort')); ?>"
                   type="text" value="<?php echo esc_attr($instance['ort']); ?>">
        </p>
        <p>
            <label><?php esc_html_e('Coordinates (optional):', 'spelhubben-weather'); ?></label><br>
            <label for="<?php echo esc_attr($this->get_field_id('lat')); ?>">Lat</label>
            <input style="width:48%"
                   id="<?php echo esc_attr($this->get_field_id('lat')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('lat')); ?>"
                   type="text" value="<?php echo esc_attr($instance['lat']); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('lon')); ?>">Lon</label>
            <input style="width:48%"
                   id="<?php echo esc_attr($this->get_field_id('lon')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('lon')); ?>"
                   type="text" value="<?php echo esc_attr($instance['lon']); ?>">
        </p>

        <fieldset>
            <legend><?php esc_html_e('Show:', 'spelhubben-weather'); ?></legend>
            <?php foreach ($this->show_allowed as $key): ?>
                <?php
                $cb_id   = $this->get_field_id('show_' . $key);
                $checked = in_array($key, $show_selected, true);
                ?>
                <p style="margin:4px 0;">
                    <input type="checkbox"
                           id="<?php echo esc_attr($cb_id); ?>"
                           name="<?php echo esc_attr($this->get_field_name('show')); ?>[]"
                           value="<?php echo esc_attr($key); ?>"
                           <?php checked($checked); ?>>
                    <label for="<?php echo esc_attr($cb_id); ?>"><?php echo esc_html($key); ?></label>
                </p>
            <?php endforeach; ?>
            <p class="description" style="margin-top:6px;">
                <?php esc_html_e('Choose which parts to display in the widget.', 'spelhubben-weather'); ?>
            </p>
        </fieldset>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('layout')); ?>"><?php esc_html_e('Layout:', 'spelhubben-weather'); ?></label>
            <select id="<?php echo esc_attr($this->get_field_id('layout')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('layout')); ?>"
                    class="widefat">
                <?php
                $layouts = ['inline','compact','card','detailed'];
                foreach ($layouts as $lay) {
                    printf(
                        '<option value="%1$s"%2$s>%1$s</option>',
                        esc_attr($lay),
                        selected($instance['layout'], $lay, false)
                    );
                }
                ?>
            </select>
        </p>
        <p>
            <input id="<?php echo esc_attr($this->get_field_id('map')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('map')); ?>"
                   type="checkbox" value="1" <?php checked($instance['map'], 1); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('map')); ?>"><?php esc_html_e('Show map', 'spelhubben-weather'); ?></label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('mapHeight')); ?>"><?php esc_html_e('Map height (px):', 'spelhubben-weather'); ?></label>
            <input id="<?php echo esc_attr($this->get_field_id('mapHeight')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('mapHeight')); ?>"
                   type="number" min="120" max="800" step="10"
                   value="<?php echo esc_attr((string) $instance['mapHeight']); ?>">
        </p>
        <p>
            <input id="<?php echo esc_attr($this->get_field_id('animate')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('animate')); ?>"
                   type="checkbox" value="1" <?php checked($instance['animate'], 1); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('animate')); ?>"><?php esc_html_e('Animations', 'spelhubben-weather'); ?></label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('forecast')); ?>"><?php esc_html_e('Forecast:', 'spelhubben-weather'); ?></label>
            <select id="<?php echo esc_attr($this->get_field_id('forecast')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('forecast')); ?>"
                    class="widefat">
                <option value="none"  <?php selected($instance['forecast'], 'none');  ?>><?php esc_html_e('None', 'spelhubben-weather'); ?></option>
                <option value="daily" <?php selected($instance['forecast'], 'daily'); ?>><?php esc_html_e('Daily', 'spelhubben-weather'); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('days')); ?>"><?php esc_html_e('Number of days (1–14):', 'spelhubben-weather'); ?></label>
            <input id="<?php echo esc_attr($this->get_field_id('days')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('days')); ?>"
                   type="number" min="1" max="14" step="1"
                   value="<?php echo esc_attr((string) $instance['days']); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('class')); ?>"><?php esc_html_e('Extra CSS class:', 'spelhubben-weather'); ?></label>
            <input class="widefat"
                   id="<?php echo esc_attr($this->get_field_id('class')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('class')); ?>"
                   type="text" value="<?php echo esc_attr($instance['class']); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title']     = sanitize_text_field($new_instance['title'] ?? '');
        $instance['ort']       = sanitize_text_field($new_instance['ort'] ?? 'Stockholm');
        $instance['lat']       = sanitize_text_field($new_instance['lat'] ?? '');
        $instance['lon']       = sanitize_text_field($new_instance['lon'] ?? '');

        if (isset($new_instance['show']) && is_array($new_instance['show'])) {
            $clean = array_map('sanitize_text_field', $new_instance['show']);
            $instance['show'] = array_values(array_intersect($clean, $this->show_allowed));
        } elseif (isset($new_instance['show']) && is_string($new_instance['show'])) {
            $clean = array_filter(array_map('trim', explode(',', $new_instance['show'])));
            $instance['show'] = array_values(array_intersect($clean, $this->show_allowed));
        } else {
            $instance['show'] = [];
        }

        $instance['layout']    = sanitize_text_field($new_instance['layout'] ?? 'card');
        $instance['map']       = !empty($new_instance['map']) ? 1 : 0;
        $instance['mapHeight'] = max(120, min(800, (int) ($new_instance['mapHeight'] ?? 240)));
        $instance['animate']   = !empty($new_instance['animate']) ? 1 : 0;
        $instance['forecast']  = in_array($new_instance['forecast'] ?? 'none', ['none','daily'], true) ? $new_instance['forecast'] : 'none';
        $instance['days']      = max(1, min(14, (int) ($new_instance['days'] ?? 5)));
        $instance['class']     = sanitize_html_class($new_instance['class'] ?? '', '');

        return $instance;
    }
}
