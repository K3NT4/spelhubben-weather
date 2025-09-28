<?php
// admin/admin-page.php
// Admin settings (UI + info)
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Enqueue admin assets only on our page
 */
if ( ! function_exists( 'sv_vader_admin_enqueue' ) ) {
    function sv_vader_admin_enqueue( $hook ) {
        // Our page: "toplevel_page_sv-vader" (slug kept for backward compatibility)
        if ( $hook !== 'toplevel_page_sv-vader' ) return;

        wp_enqueue_style(
            'sv-vader-admin',
            SV_VADER_URL . 'admin/admin.css',
            array(),
            SV_VADER_VER
        );

        wp_enqueue_script(
            'sv-vader-admin',
            SV_VADER_URL . 'admin/admin.js',
            array(),
            SV_VADER_VER,
            true
        );

        // Strings for copy buttons (not HTML, so no esc_* here)
        wp_localize_script( 'sv-vader-admin', 'SVV_ADMIN_I18N', array(
            'copied' => __( 'Copied!', 'spelhubben-weather' ),
            'copy'   => __( 'Copy', 'spelhubben-weather' ),
        ) );
    }
    add_action( 'admin_enqueue_scripts', 'sv_vader_admin_enqueue' );
}

/**
 * Menu
 */
if ( ! function_exists( 'sv_vader_register_admin_menu' ) ) {
    function sv_vader_register_admin_menu() {
        add_menu_page(
            __( 'Spelhubben Weather', 'spelhubben-weather' ),
            __( 'Spelhubben Weather', 'spelhubben-weather' ),
            'manage_options',
            'sv-vader', // slug kept for compatibility
            'sv_vader_render_settings_page',
            'dashicons-cloud',
            65
        );
    }
    add_action( 'admin_menu', 'sv_vader_register_admin_menu' );
}

/**
 * Register settings
 */
if ( ! function_exists( 'sv_vader_register_settings' ) ) {
    function sv_vader_register_settings() {
        register_setting( 'sv_vader_group', 'sv_vader_options', array(
            'type'              => 'array',
            'sanitize_callback' => 'sv_vader_sanitize_options',
            'default'           => sv_vader_default_options(),
            'show_in_rest'      => false,
        ) );

        add_settings_section( 'sv_vader_main', __( 'Default settings', 'spelhubben-weather' ), '__return_false', 'sv_vader' );

        // Default place
        add_settings_field( 'default_ort', __( 'Default place', 'spelhubben-weather' ), function() {
            $o  = sv_vader_get_options();
            /* translators: Placeholder shows an example city name. */
            $ph = __( 'e.g. Stockholm', 'spelhubben-weather' );
            printf(
                '<input type="text" name="sv_vader_options[default_ort]" value="%s" class="regular-text" placeholder="%s" />',
                esc_attr( $o['default_ort'] ?? '' ),
                esc_attr( $ph )
            );
        }, 'sv_vader', 'sv_vader_main' );

        // Cache time
        add_settings_field( 'cache_minutes', __( 'Cache TTL (minutes)', 'spelhubben-weather' ), function() {
            $o = sv_vader_get_options();
            printf(
                '<input type="number" min="1" name="sv_vader_options[cache_minutes]" value="%d" class="small-text" />',
                intval( $o['cache_minutes'] ?? 30 )
            );
            echo '<p class="description">' . esc_html__( 'How long weather data is cached (transients).', 'spelhubben-weather' ) . '</p>';
        }, 'sv_vader', 'sv_vader_main' );

        // Default fields (show)
        add_settings_field( 'default_show', __( 'Default fields', 'spelhubben-weather' ), function() {
            $o = sv_vader_get_options();
            printf(
                '<input type="text" name="sv_vader_options[default_show]" value="%s" class="regular-text" />',
                esc_attr( $o['default_show'] ?? 'temp,wind,icon' )
            );
            echo '<p class="description">' . esc_html__( 'Comma-separated: temp,wind,icon', 'spelhubben-weather' ) . '</p>';
        }, 'sv_vader', 'sv_vader_main' );

        // Default layout
        add_settings_field( 'default_layout', __( 'Default layout', 'spelhubben-weather' ), function() {
            $o = sv_vader_get_options();
            $layouts = array(
                'inline'   => _x( 'Inline', 'layout label', 'spelhubben-weather' ),
                'compact'  => _x( 'Compact', 'layout label', 'spelhubben-weather' ),
                'card'     => _x( 'Card', 'layout label', 'spelhubben-weather' ),
                'detailed' => _x( 'Detailed', 'layout label', 'spelhubben-weather' ),
            );
            echo '<select name="sv_vader_options[default_layout]">';
            foreach ( $layouts as $val => $label ) {
                printf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr( $val ),
                    selected( $o['default_layout'] ?? 'inline', $val, false ),
                    esc_html( $label )
                );
            }
            echo '</select>';
        }, 'sv_vader', 'sv_vader_main' );

        // Map default
        add_settings_field( 'map_default', __( 'Show map by default', 'spelhubben-weather' ), function() {
            $o = sv_vader_get_options();
            printf(
                '<label><input type="checkbox" name="sv_vader_options[map_default]" value="1" %s/> %s</label>',
                checked( 1, intval( $o['map_default'] ?? 0 ), false ),
                esc_html__( 'Enable map as default.', 'spelhubben-weather' )
            );
        }, 'sv_vader', 'sv_vader_main' );

        // Map height
        add_settings_field( 'map_height', __( 'Map height (px)', 'spelhubben-weather' ), function() {
            $o = sv_vader_get_options();
            printf(
                '<input type="number" min="120" name="sv_vader_options[map_height]" value="%d" class="small-text" />',
                intval( $o['map_height'] ?? 240 )
            );
        }, 'sv_vader', 'sv_vader_main' );

        // Providers (labels must be escaped inline so sniffs see it)
        add_settings_field( 'providers', __( 'Data providers', 'spelhubben-weather' ), function() {
            $o = sv_vader_get_options();

            printf(
                '<label><input type="checkbox" name="sv_vader_options[prov_openmeteo]" value="1" %s/> %s</label><br>',
                checked( 1, ! empty( $o['prov_openmeteo'] ), false ),
                esc_html__( 'Open-Meteo', 'spelhubben-weather' )
            );
            printf(
                '<label><input type="checkbox" name="sv_vader_options[prov_smhi]" value="1" %s/> %s</label><br>',
                checked( 1, ! empty( $o['prov_smhi'] ), false ),
                esc_html__( 'SMHI', 'spelhubben-weather' )
            );
            printf(
                '<label><input type="checkbox" name="sv_vader_options[prov_yr]" value="1" %s/> %s</label><br>',
                checked( 1, ! empty( $o['prov_yr'] ), false ),
                esc_html__( 'Yr (MET Norway)', 'spelhubben-weather' )
            );
            printf(
                '<label><input type="checkbox" name="sv_vader_options[prov_metno_nowcast]" value="1" %s/> %s</label>',
                checked( 1, ! empty( $o['prov_metno_nowcast'] ), false ),
                esc_html__( 'MET Norway Nowcast', 'spelhubben-weather' )
            );
        }, 'sv_vader', 'sv_vader_main' );

        // Yr contact
        add_settings_field( 'yr_contact', __( 'Yr contact/UA', 'spelhubben-weather' ), function() {
            $o = sv_vader_get_options();
            printf(
                '<input type="text" name="sv_vader_options[yr_contact]" value="%s" class="regular-text" />',
                esc_attr( $o['yr_contact'] ?? '' )
            );
            echo '<p class="description">' . esc_html__( 'Recommended by MET Norway: include an email or URL in your User-Agent.', 'spelhubben-weather' ) . '</p>';
        }, 'sv_vader', 'sv_vader_main' );
    }
    add_action( 'admin_init', 'sv_vader_register_settings' );
}

/**
 * Render admin page with helpful info
 */
if ( ! function_exists( 'sv_vader_render_settings_page' ) ) {
    function sv_vader_render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        // New (preferred) shortcode examples
        $nx1 = '[spelhubben_weather]';
        $nx2 = '[spelhubben_weather place="Gothenburg" layout="compact" map="1" animate="1"]';
        $nx3 = '[spelhubben_weather lat="57.7089" lon="11.9746" place="Gothenburg" layout="inline" map="0" show="temp,icon"]';
        $nx4 = '[spelhubben_weather place="Umeå" layout="detailed" forecast="daily" days="5" providers="smhi,yr,openmeteo"]';
        $nx5 = '[spelhubben_weather place="Malmö" show="temp,wind" map="0"]';

        // Legacy shortcode examples (kept for reference)
        $lx1 = '[sv_vader]';
        $lx2 = '[sv_vader ort="Göteborg" layout="compact" map="1" animate="1"]';
        $lx3 = '[sv_vader lat="57.7089" lon="11.9746" ort="Göteborg" layout="inline" map="0" show="temp,icon"]';
        $lx4 = '[sv_vader ort="Umeå" layout="detailed" forecast="daily" days="5" providers="smhi,yr,openmeteo"]';
        $lx5 = '[sv_vader ort="Malmö" show="temp,wind" map="0"]';
        ?>
        <div class="wrap svv-admin-wrap">
            <h1 class="svv-page-title">
                <?php esc_html_e( 'Spelhubben Weather – Settings', 'spelhubben-weather' ); ?>
            </h1>

            <div class="svv-grid">
                <div class="svv-col">
                    <div class="svv-card">
                        <h2 class="svv-card-title"><?php esc_html_e( 'General', 'spelhubben-weather' ); ?></h2>
                        <form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" class="svv-form">
                            <?php
                            settings_fields( 'sv_vader_group' );
                            do_settings_sections( 'sv_vader' );
                            submit_button();
                            ?>
                        </form>
                    </div>

                    <div class="svv-card">
                        <h2 class="svv-card-title"><?php esc_html_e( 'Attribution', 'spelhubben-weather' ); ?></h2>
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
                        <h2 class="svv-card-title"><?php esc_html_e( 'Quick start – shortcodes', 'spelhubben-weather' ); ?></h2>

                        <?php
                        // Preferred (English) shortcode examples
                        $new_examples = array(
                            array( 'label' => __( 'Basic example', 'spelhubben-weather' ), 'code' => $nx1 ),
                            array( 'label' => __( 'Compact with map & animation', 'spelhubben-weather' ), 'code' => $nx2 ),
                            array( 'label' => __( 'Inline without map', 'spelhubben-weather' ), 'code' => $nx3 ),
                            array( 'label' => __( 'Detailed with daily forecast & all providers', 'spelhubben-weather' ), 'code' => $nx4 ),
                            array( 'label' => __( 'Only temperature + wind, no map', 'spelhubben-weather' ), 'code' => $nx5 ),
                        );
                        foreach ( $new_examples as $ex ) :
                        ?>
                            <div class="svv-codeblock">
                                <div class="svv-codeblock-head">
                                    <span><?php echo esc_html( $ex['label'] ); ?></span>
                                    <button type="button" class="button button-secondary svv-copy-btn" data-copy="<?php echo esc_attr( $ex['code'] ); ?>">
                                        <?php esc_html_e( 'Copy', 'spelhubben-weather' ); ?>
                                    </button>
                                </div>
                                <pre class="svv-pre"><code><?php echo esc_html( $ex['code'] ); ?></code></pre>
                            </div>
                        <?php endforeach; ?>

                        <details class="svv-details" style="margin-top:12px;">
                            <summary><?php esc_html_e( 'Legacy shortcode examples (kept for compatibility)', 'spelhubben-weather' ); ?></summary>
                            <?php
                            $legacy_examples = array(
                                array( 'label' => __( 'Basic example (legacy)', 'spelhubben-weather' ), 'code' => $lx1 ),
                                array( 'label' => __( 'Compact with map & animation (legacy)', 'spelhubben-weather' ), 'code' => $lx2 ),
                                array( 'label' => __( 'Inline without map (legacy)', 'spelhubben-weather' ), 'code' => $lx3 ),
                                array( 'label' => __( 'Detailed with daily forecast & all providers (legacy)', 'spelhubben-weather' ), 'code' => $lx4 ),
                                array( 'label' => __( 'Only temperature + wind, no map (legacy)', 'spelhubben-weather' ), 'code' => $lx5 ),
                            );
                            foreach ( $legacy_examples as $ex ) :
                            ?>
                                <div class="svv-codeblock">
                                    <div class="svv-codeblock-head">
                                        <span><?php echo esc_html( $ex['label'] ); ?></span>
                                        <button type="button" class="button button-secondary svv-copy-btn" data-copy="<?php echo esc_attr( $ex['code'] ); ?>">
                                            <?php esc_html_e( 'Copy', 'spelhubben-weather' ); ?>
                                        </button>
                                    </div>
                                    <pre class="svv-pre"><code><?php echo esc_html( $ex['code'] ); ?></code></pre>
                                </div>
                            <?php endforeach; ?>
                        </details>
                    </div>

                    <div class="svv-card">
                        <h2 class="svv-card-title"><?php esc_html_e( 'Attributes – overview', 'spelhubben-weather' ); ?></h2>
                        <div class="svv-table-wrap">
                            <table class="svv-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Attribute', 'spelhubben-weather' ); ?></th>
                                        <th><?php esc_html_e( 'Description', 'spelhubben-weather' ); ?></th>
                                        <th><?php esc_html_e( 'Example', 'spelhubben-weather' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>place</code></td>
                                        <td><?php esc_html_e( 'Place name to geocode (used if lat/lon are missing).', 'spelhubben-weather' ); ?></td>
                                        <td><code><?php echo esc_html( 'place="Umeå"' ); ?></code></td>
                                    </tr>
                                    <tr>
                                        <td><code>lat</code>, <code>lon</code></td>
                                        <td><?php esc_html_e( 'Coordinates take precedence over place.', 'spelhubben-weather' ); ?></td>
                                        <td><code><?php echo esc_html( 'lat="57.7" lon="11.97"' ); ?></code></td>
                                    </tr>
                                    <tr>
                                        <td><code>show</code></td>
                                        <td><?php esc_html_e( 'Fields to display: temp,wind,icon', 'spelhubben-weather' ); ?></td>
                                        <td><code><?php echo esc_html( 'show="temp,wind"' ); ?></code></td>
                                    </tr>
                                    <tr>
                                        <td><code>layout</code></td>
                                        <td><?php esc_html_e( 'inline | compact | card | detailed', 'spelhubben-weather' ); ?></td>
                                        <td><code><?php echo esc_html( 'layout="compact"' ); ?></code></td>
                                    </tr>
                                    <tr>
                                        <td><code>map</code></td>
                                        <td><?php esc_html_e( '1/0 to show/hide map', 'spelhubben-weather' ); ?></td>
                                        <td><code><?php echo esc_html( 'map="1"' ); ?></code></td>
                                    </tr>
                                    <tr>
                                        <td><code>map_height</code></td>
                                        <td><?php esc_html_e( 'Map height in px (min 120).', 'spelhubben-weather' ); ?></td>
                                        <td><code><?php echo esc_html( 'map_height="240"' ); ?></code></td>
                                    </tr>
                                    <tr>
                                        <td><code>providers</code></td>
                                        <td><?php esc_html_e( 'openmeteo,smhi,yr,metno_nowcast (comma-separated)', 'spelhubben-weather' ); ?></td>
                                        <td><code><?php echo esc_html( 'providers="smhi,yr,metno_nowcast"' ); ?></code></td>
                                    </tr>
                                    <tr>
                                        <td><code>animate</code></td>
                                        <td><?php esc_html_e( '1/0 – subtle animations', 'spelhubben-weather' ); ?></td>
                                        <td><code><?php echo esc_html( 'animate="1"' ); ?></code></td>
                                    </tr>
                                    <tr>
                                        <td><code>forecast</code></td>
                                        <td><?php esc_html_e( 'none | daily', 'spelhubben-weather' ); ?></td>
                                        <td><code><?php echo esc_html( 'forecast="daily"' ); ?></code></td>
                                    </tr>
                                    <tr>
                                        <td><code>days</code></td>
                                        <td><?php esc_html_e( 'Number of days in the forecast (3–10)', 'spelhubben-weather' ); ?></td>
                                        <td><code><?php echo esc_html( 'days="5"' ); ?></code></td>
                                    </tr>
                                    <tr>
                                        <td><code>class</code></td>
                                        <td><?php esc_html_e( 'Custom CSS class on the wrapper', 'spelhubben-weather' ); ?></td>
                                        <td><code><?php echo esc_html( 'class="my-widget"' ); ?></code></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="svv-badges">
                            <span class="svv-badge"><?php esc_html_e( 'Shortcode', 'spelhubben-weather' ); ?></span>
                            <span class="svv-badge"><?php esc_html_e( 'Gutenberg block', 'spelhubben-weather' ); ?></span>
                            <span class="svv-badge"><?php esc_html_e( 'Widget', 'spelhubben-weather' ); ?></span>
                            <span class="svv-badge"><?php esc_html_e( 'Leaflet map', 'spelhubben-weather' ); ?></span>
                            <span class="svv-badge"><?php esc_html_e( 'Animation', 'spelhubben-weather' ); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
