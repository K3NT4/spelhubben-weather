<?php
// admin/page-shortcodes.php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Kortkods-sida (render)
 */
if ( ! function_exists( 'sv_vader_render_shortcodes_page' ) ) {
	function sv_vader_render_shortcodes_page() {
		if ( ! current_user_can( 'manage_options' ) ) return;

		// Exempel (nya alias)
		$nx1 = '[spelhubben_weather]';
		$nx2 = '[spelhubben_weather place="Gothenburg" layout="compact" map="1" animate="1"]';
		$nx3 = '[spelhubben_weather lat="57.7089" lon="11.9746" place="Gothenburg" layout="inline" map="0" show="temp,icon"]';
		$nx4 = '[spelhubben_weather place="Umeå" layout="detailed" forecast="daily" days="5" providers="smhi,yr,openmeteo,fmi" units="metric_kmh"]';
		$nx5 = '[spelhubben_weather place="Malmö" show="temp,wind" map="0" units="imperial"]';

		// Legacy (behålls för referens)
		$lx1 = '[sv_vader]';
		$lx2 = '[sv_vader ort="Göteborg" layout="compact" map="1" animate="1"]';
		$lx3 = '[sv_vader lat="57.7089" lon="11.9746" ort="Göteborg" layout="inline" map="0" show="temp,icon"]';
		$lx4 = '[sv_vader ort="Umeå" layout="detailed" forecast="daily" days="5" providers="smhi,yr,openmeteo"]';
		$lx5 = '[sv_vader ort="Malmö" show="temp,wind" map="0"]';

		$new_examples = array(
			array( 'label' => __( 'Basic example', 'spelhubben-weather' ), 'code' => $nx1 ),
			array( 'label' => __( 'Compact with map & animation', 'spelhubben-weather' ), 'code' => $nx2 ),
			array( 'label' => __( 'Inline without map', 'spelhubben-weather' ), 'code' => $nx3 ),
			array( 'label' => __( 'Detailed with daily forecast & km/h', 'spelhubben-weather' ), 'code' => $nx4 ),
			array( 'label' => __( 'Only temperature + wind, imperial', 'spelhubben-weather' ), 'code' => $nx5 ),
		);

		$legacy_examples = array(
			array( 'label' => __( 'Basic example (legacy)', 'spelhubben-weather' ), 'code' => $lx1 ),
			array( 'label' => __( 'Compact with map & animation (legacy)', 'spelhubben-weather' ), 'code' => $lx2 ),
			array( 'label' => __( 'Inline without map (legacy)', 'spelhubben-weather' ), 'code' => $lx3 ),
			array( 'label' => __( 'Detailed with daily forecast & all providers (legacy)', 'spelhubben-weather' ), 'code' => $lx4 ),
			array( 'label' => __( 'Only temperature + wind, no map (legacy)', 'spelhubben-weather' ), 'code' => $lx5 ),
		);
		?>
		<div class="wrap svv-admin-wrap">
			<h1 class="svv-page-title"><?php esc_html_e( 'Spelhubben Weather – Shortcodes', 'spelhubben-weather' ); ?></h1>
			<p class="svv-subheader"><?php esc_html_e( 'Copy & paste ready-made snippets. Click “Copy” to put a shortcode on your clipboard.', 'spelhubben-weather' ); ?></p>

			<!-- Toolbar: back + filter + copy all -->
			<div class="svv-toolbar">
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sv-vader' ) ); ?>">
					<span class="dashicons dashicons-arrow-left-alt2" style="vertical-align:middle"></span>
					<?php esc_html_e( 'Back to Settings', 'spelhubben-weather' ); ?>
				</a>

				<label class="svv-search">
					<span class="dashicons dashicons-search"></span>
					<input type="search" class="svv-sc-search" placeholder="<?php echo esc_attr__( 'Search examples… e.g. inline, map, imperial', 'spelhubben-weather' ); ?>" />
				</label>

				<button type="button" class="button button-secondary svv-copy-batch"
						data-batch-selector=".svv-codeblock[data-svv-visible='1'] .svv-pre code">
					<span class="dashicons dashicons-clipboard"></span>
					<?php esc_html_e( 'Copy all (visible)', 'spelhubben-weather' ); ?>
				</button>
			</div>

			<div class="svv-grid">
				<!-- LEFT: Examples -->
				<div class="svv-col">
					<div class="svv-card">
						<h2 class="svv-card-title">
							<span class="dashicons dashicons-editor-code"></span>
							<?php esc_html_e( 'Quick start – shortcodes', 'spelhubben-weather' ); ?>
						</h2>

						<div class="svv-codegrid">
							<?php foreach ( $new_examples as $ex ) : ?>
								<div class="svv-codeblock svv-codeblock--light" data-svv-visible="1"
									 data-label="<?php echo esc_attr( $ex['label'] ); ?>"
									 data-code="<?php echo esc_attr( $ex['code'] ); ?>">
									<div class="svv-codeblock-head">
										<span><?php echo esc_html( $ex['label'] ); ?></span>
										<div class="svv-chiprow">
											<span class="svv-chip"><?php esc_html_e( 'new', 'spelhubben-weather' ); ?></span>
											<button type="button" class="button button-secondary svv-copy-btn"
													data-copy="<?php echo esc_attr( $ex['code'] ); ?>">
												<?php esc_html_e( 'Copy', 'spelhubben-weather' ); ?>
											</button>
										</div>
									</div>
									<pre class="svv-pre"><code tabindex="0"><?php echo esc_html( $ex['code'] ); ?></code></pre>
                                </div>
							<?php endforeach; ?>
						</div>

						<details class="svv-details" style="margin-top:14px;">
							<summary><?php esc_html_e( 'Legacy shortcode examples (deprecated – will be removed soon)', 'spelhubben-weather' ); ?></summary>

							<div class="svv-codegrid">
								<?php foreach ( $legacy_examples as $ex ) : ?>
									<div class="svv-codeblock svv-codeblock--light" data-svv-visible="1"
										 data-label="<?php echo esc_attr( $ex['label'] ); ?>"
										 data-code="<?php echo esc_attr( $ex['code'] ); ?>">
										<div class="svv-codeblock-head">
											<span><?php echo esc_html( $ex['label'] ); ?></span>
											<div class="svv-chiprow">
												<span class="svv-chip svv-chip-muted"><?php esc_html_e( 'legacy', 'spelhubben-weather' ); ?></span>
												<button type="button" class="button button-secondary svv-copy-btn"
														data-copy="<?php echo esc_attr( $ex['code'] ); ?>">
													<?php esc_html_e( 'Copy', 'spelhubben-weather' ); ?>
												</button>
											</div>
										</div>
										<pre class="svv-pre"><code tabindex="0"><?php echo esc_html( $ex['code'] ); ?></code></pre>
									</div>
								<?php endforeach; ?>
							</div>
						</details>
					</div>
				</div>

				<!-- RIGHT: Preview + Attributes -->
				<div class="svv-col">
					<!-- PREVIEW PANEL (ljus, som settings) -->
					<div class="svv-card">
						<h2 class="svv-card-title">
							<span class="dashicons dashicons-visibility"></span>
							<?php esc_html_e( 'Preview', 'spelhubben-weather' ); ?>
						</h2>
						<p class="description" style="margin-top:-4px">
							<?php esc_html_e( 'Click a snippet to send it here. You can edit, copy or expand the box.', 'spelhubben-weather' ); ?>
						</p>

						<div class="svv-preview-actions">
							<button type="button" class="button button-secondary svv-preview-copy">
								<span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'spelhubben-weather' ); ?>
							</button>
							<button type="button" class="button svv-preview-clear">
								<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Clear', 'spelhubben-weather' ); ?>
							</button>
							<button type="button" class="button button-primary svv-preview-toggle">
								<span class="dashicons dashicons-editor-expand"></span> <?php esc_html_e( 'Expand', 'spelhubben-weather' ); ?>
							</button>
						</div>

						<textarea class="svv-sc-preview" rows="8" spellcheck="false" aria-label="<?php echo esc_attr__( 'Shortcode preview', 'spelhubben-weather' ); ?>" placeholder="[spelhubben_weather place=&quot;…&quot;]"></textarea>
						<div class="svv-live-preview" hidden>
  							<div class="svv-live-bar">
    							<span class="dashicons dashicons-visibility"></span>
    							<strong><?php esc_html_e( 'Live preview', 'spelhubben-weather' ); ?></strong>
    							<em class="svv-live-status" aria-live="polite"></em>
  							</div>
  							<iframe class="svv-live-frame"
          							title="<?php echo esc_attr__( 'Shortcode live preview', 'spelhubben-weather' ); ?>"></iframe>
						</div>

					<!-- ATTRIBUTES -->
					<div class="svv-card">
						<h2 class="svv-card-title">
							<span class="dashicons dashicons-list-view"></span>
							<?php esc_html_e( 'Attributes – overview', 'spelhubben-weather' ); ?>
						</h2>

						<div class="svv-attr-legend">
							<span class="svv-badge"><span class="dashicons dashicons-location-alt"></span><?php esc_html_e( 'Location', 'spelhubben-weather' ); ?></span>
							<span class="svv-badge"><span class="dashicons dashicons-admin-appearance"></span><?php esc_html_e( 'Display', 'spelhubben-weather' ); ?></span>
							<span class="svv-badge"><span class="dashicons dashicons-schedule"></span><?php esc_html_e( 'Forecast', 'spelhubben-weather' ); ?></span>
							<span class="svv-badge"><span class="dashicons dashicons-admin-tools"></span><?php esc_html_e( 'Units & format', 'spelhubben-weather' ); ?></span>
						</div>

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
									<tr data-group="loc"><td><code>place</code></td><td><?php esc_html_e( 'Place name to geocode (used if lat/lon are missing).', 'spelhubben-weather' ); ?></td><td><code>place="Umeå"</code></td></tr>
									<tr data-group="loc"><td><code>lat</code>, <code>lon</code></td><td><?php esc_html_e( 'Coordinates take precedence over place.', 'spelhubben-weather' ); ?></td><td><code>lat="57.7" lon="11.97"</code></td></tr>

									<tr data-group="disp"><td><code>show</code></td><td><?php esc_html_e( 'Fields to display: temp,wind,icon', 'spelhubben-weather' ); ?></td><td><code>show="temp,wind"</code></td></tr>
									<tr data-group="disp"><td><code>layout</code></td><td><?php esc_html_e( 'inline | compact | card | detailed', 'spelhubben-weather' ); ?></td><td><code>layout="compact"</code></td></tr>
									<tr data-group="disp"><td><code>map</code></td><td><?php esc_html_e( '1/0 to show/hide map', 'spelhubben-weather' ); ?></td><td><code>map="1"</code></td></tr>
									<tr data-group="disp"><td><code>map_height</code></td><td><?php esc_html_e( 'Map height in px (min 120).', 'spelhubben-weather' ); ?></td><td><code>map_height="240"</code></td></tr>
									<tr data-group="disp"><td><code>providers</code></td><td><?php esc_html_e( 'openmeteo,smhi,yr,metno_nowcast (comma-separated)', 'spelhubben-weather' ); ?></td><td><code>providers="smhi,yr,metno_nowcast"</code></td></tr>
									<tr data-group="disp"><td><code>animate</code></td><td><?php esc_html_e( '1/0 – subtle animations', 'spelhubben-weather' ); ?></td><td><code>animate="1"</code></td></tr>

									<tr data-group="fc"><td><code>forecast</code></td><td><?php esc_html_e( 'none | daily', 'spelhubben-weather' ); ?></td><td><code>forecast="daily"</code></td></tr>
									<tr data-group="fc"><td><code>days</code></td><td><?php esc_html_e( 'Number of days in the forecast (3–10)', 'spelhubben-weather' ); ?></td><td><code>days="5"</code></td></tr>

									<tr data-group="uf"><td><code>units</code></td><td><?php esc_html_e( 'Preset: metric | metric_kmh | imperial', 'spelhubben-weather' ); ?></td><td><code>units="metric_kmh"</code></td></tr>
									<tr data-group="uf"><td><code>temp_unit</code></td><td><?php esc_html_e( 'Override temperature unit', 'spelhubben-weather' ); ?></td><td><code>temp_unit="F"</code></td></tr>
									<tr data-group="uf"><td><code>wind_unit</code></td><td><?php esc_html_e( 'Override wind unit', 'spelhubben-weather' ); ?></td><td><code>wind_unit="kmh"</code></td></tr>
									<tr data-group="uf"><td><code>precip_unit</code></td><td><?php esc_html_e( 'Override precipitation unit', 'spelhubben-weather' ); ?></td><td><code>precip_unit="in"</code></td></tr>
									<tr data-group="uf"><td><code>date_format</code></td><td><?php esc_html_e( 'Forecast date label (PHP date)', 'spelhubben-weather' ); ?></td><td><code>date_format="D j/n"</code></td></tr>
								</tbody>
							</table>
						</div>

						<div style="margin-top:14px;">
							<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sv-vader' ) ); ?>">
								<span class="dashicons dashicons-admin-generic" style="vertical-align:middle"></span>
								<?php esc_html_e( 'Back to Settings', 'spelhubben-weather' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div><!-- /.svv-grid -->
		</div><!-- /.wrap -->
		<?php
	}
}
