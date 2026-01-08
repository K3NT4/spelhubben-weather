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

- **Common issues & troubleshooting:**
  - Nothing appears: Make sure at least one provider is enabled in Settings.
  - Wrong location: Provide `lat` and `lon` in the shortcode or use a more precise `place` string.
  - Map is not visible: Ensure `map="1"` and set `map_height="240"` (or larger) if the container is small.
  - Rate limiting / empty responses: Increase the cache TTL in Settings or reduce update frequency.

- **Do I need API keys?** No for Open-Meteo, SMHI and FMI. Some providers may require a key — check Settings.

- **Languages & translations:** Includes Swedish (`sv_SE`) and Norwegian Bokmål (`nb_NO`). See the `/languages` folder for translation files.

- **Privacy:** The plugin does not set cookies by itself. If you enable the map, OpenStreetMap tiles are requested client-side — mention OSM in your privacy policy if required.

- **Support:** For bug reports, issues or feature requests use GitHub issues: https://github.com/K3NT4/spelhubben-weather/issues

If you’d like, I can also add a link to this FAQ in `readme.txt` or `README.md`.