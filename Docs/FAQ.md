# FAQ — Spelhubben Weather

Here are answers to common questions about the Spelhubben Weather plugin.

- **Feedback & bug reports:** Please post issues or feature requests here: https://github.com/K3NT4/spelhubben-weather/issues

- **Installation:** Upload and activate the plugin via the WordPress admin (Plugins → Add New → Upload). Go to Settings → Spelhubben Weather to configure defaults.

- **How do I use it?**
  - Block (Gutenberg): Add the “Spelhubben Weather” block.
  - Shortcode: Use `[spelhubben_weather]`. Example: `[spelhubben_weather place="Stockholm" layout="compact" map="1"]`.
  - Widget: Go to Appearance → Widgets and add the “Spelhubben Weather” widget.

- **Shortcode examples:**
  - Simple: `[spelhubben_weather]`
  - Compact with map: `[spelhubben_weather place="Gothenburg" layout="compact" map="1"]`
  - Daily forecast (5 days): `[spelhubben_weather forecast="daily" days="5"]`
  - Hourly forecast (24 hours): `[spelhubben_weather hourly="1" hours="24"]`
  - OpenLayers map: `[spelhubben_weather map="1" map_engine="openlayers"]`

- **Common issues & troubleshooting:**
  - Nothing appears: Make sure at least one provider is enabled in Settings.
  - Wrong location: Provide `lat` and `lon` in the shortcode or use a more precise `place` string.
  - Map is not visible: Ensure `map="1"` and set `map_height="240"` (or larger) if the container is small.
  - Rate limiting / empty responses: Increase the cache TTL in Settings or reduce update frequency.

- **Do I need API keys?** No for Open-Meteo, SMHI, Yr/MET Norway, MET Norway Nowcast and FMI. OpenWeatherMap and WeatherAPI.com require keys stored server-side in Settings.

- **Languages & translations:** Includes Swedish (`sv_SE`) and Norwegian Bokmål (`nb_NO`). See the `/languages` folder for translation files.

- **Privacy:** The plugin does not set cookies by itself. If you enable the map, OpenStreetMap tiles are requested client-side from local OpenLayers/Leaflet code — mention OSM in your privacy policy if required.

- **Support:** For bug reports, issues or feature requests use GitHub issues: https://github.com/K3NT4/spelhubben-weather/issues

## Tips & advanced usage

- **Moon phase fields:** Use the new `phase` and `illumination` fields to show moon information. Example shortcode: `[spelhubben_weather show="temp,icon,phase,illumination"]` — available in Block inspector and Widget options as well.
- **Control which fields show:** Use `show="temp,wind,wind_dir,icon,phase,illumination"` to tailor the display. Fields are comma-separated.
- **Mix providers & compare:** Request specific providers with `providers="smhi,yr,openmeteo,fmi"`. Use `comparison="1"` to show side-by-side provider outputs for debugging.
- **Wind unit overrides:** Force wind units per instance with `wind_unit="ms|kmh|mph|knt"` (e.g. `wind_unit="knt"`). The Block/Widget inspector exposes this option.
- **Map tips:** Enable the map with `map="1"` and set height with `map_height="240"` (px). Use `map_engine="auto|openlayers|leaflet|static"` to control the smart map engine. If interactive scripts are blocked, the plugin shows a static fallback instead of an empty map.
- **Caching & troubleshooting:** Increase `Cache TTL` in Settings to reduce external calls. Clear plugin cache on Settings → Performance if data looks stale or providers return errors.
- **Animate & theme:** `animate` accepts `1`, `true`, `yes`, or `on`. Force theme per-instance with `theme="auto|light|dark"`.
- **MET Norway contact info:** For Yr/MET Norway, set contact email/URL in Settings so your User-Agent meets their API guidelines — this prevents rate-limiting in some cases.
- **When nothing shows:** Ensure at least one provider is enabled in Settings and that the place or `lat,lon` is valid. Use `comparison="1"` to see which providers return data for your location.
- **Shortcode Quick Builder & Block inspector:** Use the admin Shortcodes page Quick Builder or Block sidebar to preview and copy instance-specific shortcodes with chosen options.

If you’d like, I can also add a link to this FAQ in `readme.txt` or `README.md`.
