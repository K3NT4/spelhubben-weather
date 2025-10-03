=== Spelhubben Weather ===
Contributors: spelhubben
Tags: weather, forecast, widget, shortcode, blocks
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.6.2
Donate link: https://www.paypal.com/donate/?hosted_button_id=CV74CEXY5XEAU
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Weather widget & block with optional map and daily forecast. Can combine Open-Meteo, SMHI and Yr/MET data.

== Description ==
This plugin displays current weather and an optional forecast. It can aggregate data from northern-Europe friendly providers (Open-Meteo, SMHI, and Yr/MET Norway) and compute a simple consensus.

**Features**
- Gutenberg block and shortcode
- Optional Leaflet map (OpenStreetMap)
- Daily forecast and multiple layouts (inline, compact, card, detailed)
- Caching with transients
- Fully translatable

*Not affiliated with Open-Meteo, SMHI, Yr/MET Norway, Leaflet, or OpenStreetMap. Names are used for descriptive purposes only. Map data © OpenStreetMap contributors (ODbL).*

== Installation ==
1. Upload/activate the plugin.
2. Go to **Settings → Spelhubben Weather** and set defaults (place, shown fields, layout, providers, cache time).
3. Add weather to your site in any of these ways:

= Block (Gutenberg) =
- Edit a page/post → click **Add block** → search for **“Spelhubben Weather”**.
- Optional: override defaults in the block sidebar (place/lat,lon, layout, map, forecast).

= Shortcode =
- Insert `[spelhubben_weather]` anywhere shortcodes are supported.
- Examples:
  - Basic: `[spelhubben_weather]`
  - Compact with map & animation: `[spelhubben_weather place="Gothenburg" layout="compact" map="1" animate="1"]`
  - Inline no map: `[spelhubben_weather lat="57.7089" lon="11.9746" layout="inline" map="0" show="temp,icon"]`
  - Detailed + daily forecast (5 days) + provider mix: `[spelhubben_weather place="Umeå" layout="detailed" forecast="daily" days="5" providers="smhi,yr,openmeteo"]`

= Classic Widget =
- Go to **Appearance → Widgets** → add **Spelhubben Weather**.
- Configure per-widget options (title, place or lat/lon, fields, layout, map, forecast, days, CSS class).

== Frequently Asked Questions ==

= Where does the data come from? =
From public APIs such as Open-Meteo, SMHI and Yr/MET Norway. You choose providers under **Settings → Spelhubben Weather** or per block/shortcode/widget via the `providers` attribute.

= Do I need an API key? =
No. For Yr/MET Norway it’s recommended to include contact info (email/URL) in **Settings → Spelhubben Weather → Yr contact/UA** so your User-Agent is compliant.

= Block, shortcode or widget — what’s the difference? =
All three render the same UI. Use the **block** in the block editor, the **shortcode** in classic content areas, and the **widget** in sidebars (Appearance → Widgets). Each lets you override global defaults.

= How do place and coordinates work? =
If `lat` and `lon` are provided they take precedence. Otherwise the plugin geocodes the `place` string (e.g. `place="Umeå"`). Set a global default place in settings.

= What fields can I show/hide? =
Use `show="temp,wind,icon"` (comma separated). Defaults are set in settings.

= How do layouts work? =
Choose `layout="inline|compact|card|detailed"`. “Detailed” supports the multi-day forecast row.

= How do I enable the map and set its size? =
`map="1"` shows a Leaflet map (OpenStreetMap). Control height with `map_height="240"` (px). Global defaults exist in settings.

= How do I enable animations? =
`animate="1"` adds subtle UI animation. Global default is in settings.

= How do I get a daily forecast? =
Set `forecast="daily"` and `days="3–10"`. Example: `forecast="daily" days="5"`.

= Can I mix providers and get a consensus? =
Yes. Set `providers="smhi,yr,openmeteo"` (order doesn’t matter). The plugin calculates a simple consensus across available providers for the displayed fields.

= Caching — how long is data stored? =
Responses are cached with WordPress transients. Change TTL (minutes) in settings. Clear by saving settings or changing attributes (which creates a new cache key).

= Does it work without JavaScript? =
Yes, rendering is server-side. The map (Leaflet) requires JS.

= Translations? =
The plugin is fully translatable. Strings are available on translate.wordpress.org. Ship `.pot/.po/.mo` in `/languages`.

= GDPR / privacy? =
The plugin does not set cookies by itself. If you enable the map, Leaflet/OpenStreetMap tiles are requested client-side. Mention OSM in your privacy notice if needed.

= Troubleshooting tips =
- Nothing shows: check that at least one provider is selected in settings.
- Wrong location: provide exact `lat`/`lon` or a more specific `place` (e.g. “Uddevalla, SE”).
- Map not visible: ensure `map="1"` and that your theme/container is wide/tall enough; increase `map_height`.
- Rate limiting: reduce refreshes or increase cache TTL.

== Screenshots ==
1. Frontend examples: inline, compact, card, detailed, with optional map.
2. Settings page with quick shortcode examples and attribute reference.

== Upgrade Notice ==
= 1.6.1 =
Initial public release. No breaking changes.
* Security hardening: consistent output escaping.
* Block + shortcode parity, sensible defaults.
* Improved uninstall cleanup and minor UI tweaks.
* WordPress.org packaging (.distignore, assets, readme).

Donate link: hhttps://www.paypal.com/donate/?hosted_button_id=CV74CEXY5XEAU
