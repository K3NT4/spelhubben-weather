=== Spelhubben Weather ===
Contributors: spelhubben
Tags: weather, forecast, widget, shortcode, blocks
Requires at least: 6.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.8.5
Donate link: https://www.paypal.com/donate/?hosted_button_id=CV74CEXY5XEAU
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Weather widget & block with optional map and daily forecast. Can combine Open-Meteo, SMHI, Yr/MET, FMI, Open-Weathermap, and Weatherapi.com data.

== Description ==
This plugin displays current weather and an optional forecast. It can aggregate data from free global weather providers (Open-Meteo, SMHI, Yr/MET Norway, FMI, Open-Weathermap, and Weatherapi.com) and compute a simple consensus. Works worldwide with excellent coverage in Europe and beyond.

**Features**
- Gutenberg block and shortcode
- Optional Leaflet map (OpenStreetMap)
- Daily forecast and multiple layouts (inline, compact, card, detailed)
- Caching with transients + one-click cache clear
- Fully translatable
- **Included translations:** **Swedish (sv_SE), Norwegian (nb_NO), English (en_US)**
- **New (1.7.0):** modern admin UI with a dedicated **Shortcodes** page (search, copy, “copy all”), and **live preview** inside WP-admin
- **New (1.7.5):** **FMI (Finland, Open Data)** as an additional free provider (temperature, wind, hourly precip, cloud cover via WFS)

*Not affiliated with Open-Meteo, SMHI, Yr/MET Norway, FMI, Leaflet, or OpenStreetMap. Names are used for descriptive purposes only. Map data © OpenStreetMap contributors (ODbL).*

== Installation ==
1. Upload/activate the plugin.
2. Go to **Settings → Spelhubben Weather** and set defaults (place, shown fields, layout, providers, cache time, units/format).
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
  - Detailed + daily forecast (5 days) + provider mix: `[spelhubben_weather place="Umeå" layout="detailed" forecast="daily" days="5" providers="smhi,yr,openmeteo,fmi"]`

= Classic Widget =
- Go to **Appearance → Widgets** → add **Spelhubben Weather**.
- Configure per-widget options (title, place or lat/lon, fields, layout, map, forecast, days, CSS class).

== Frequently Asked Questions ==

= Where does the data come from? =
From public APIs such as Open-Meteo, SMHI, Yr/MET Norway, and **FMI** (Finnish Meteorological Institute). You choose providers under **Settings → Spelhubben Weather** or per block/shortcode/widget via the `providers` attribute.

= Do I need an API key? =
No. Open-Meteo, SMHI, and FMI do not require keys. For Yr/MET Norway it’s recommended to include contact info (email/URL) in **Settings → Spelhubben Weather → Yr contact/UA** so your User-Agent is compliant.

= Block, shortcode or widget — what’s the difference? =
All three render the same UI. Use the **block** in the block editor, the **shortcode** in classic content areas, and the **widget** in sidebars (Appearance → Widgets). Each lets you override global defaults.

= Where is the Shortcodes page? (new in 1.7.0) =
Go to **Settings → Spelhubben Weather → Shortcodes**. You’ll find searchable examples, one-click copy (and “copy all”), plus a **live preview** that renders the shortcode inside WP-admin.

= How do place and coordinates work? =
If `lat` and `lon` are provided they take precedence. Otherwise the plugin geocodes the `place` string (e.g. `place="Umeå"`). Set a global default place in settings.

= What fields can I show/hide? =
Use `show="temp,wind,icon"` (comma separated). Defaults are set in settings.

= How do layouts work? =
Choose `layout="inline|compact|card|detailed"`. “Detailed” supports the multi-day forecast row.
= Can I see individual provider data (for comparison)? =
Yes! Use `comparison="1"` to show all enabled providers' data side-by-side. Useful for debugging or comparing which providers are available in your location.
Example: `[spelhubben_weather place="Stockholm" comparison="1" providers="openmeteo,smhi,yr,fmi,openweathermap,weatherapi"]`

= What icon themes are available? =
Three themes: **Classic** (traditional), **Modern Flat** (clean, minimalist), and **Modern Gradient** (contemporary with gradients). Choose in **Settings → Spelhubben Weather → Icon style**. All themes include icons for sun, partly-cloudy, cloud, fog, rain, sleet, snow, and thunderstorm.

= How do I enable the map and set its size? =
`map="1"` shows a Leaflet map (OpenStreetMap). Control height with `map_height="240"` (px). Global defaults exist in settings.

= How do I enable animations? =
`animate="1"` adds subtle UI animation. Global default is in settings.

= How do I get a daily forecast? =
Set `forecast="daily"` and `days="3–10"`. Example: `forecast="daily" days="5"`.

= Can I mix providers and get a consensus? =
Yes. Set `providers="smhi,yr,openmeteo,fmi"` (order doesn’t matter). The plugin calculates a simple consensus across available providers for the displayed fields.

= Units & format? =
Pick a preset with `units="metric|metric_kmh|imperial"`. You can override parts via `temp_unit="C|F"`, `wind_unit="ms|kmh|mph"`, `precip_unit="mm|in"`, and `date_format` for forecast labels. All have global defaults in settings (**Units & format** section).

= Caching — how long is data stored? =
Responses are cached with WordPress transients. Change TTL (minutes) in settings. Clear via the **Clear cache** button on the settings page or by changing attributes (which creates a new cache key).

= Does it work without JavaScript? =
Yes, rendering is server-side. The map (Leaflet) requires JS.

= Translations? =
The plugin is fully translatable. **Included translations:** **Swedish (sv_SE), Norwegian (nb_NO)**. Strings are also available on translate.wordpress.org. Ship `.pot/.po/.mo` in `/languages`.

= GDPR / privacy? =
The plugin does not set cookies by itself. If you enable the map, Leaflet/OpenStreetMap tiles are requested client-side. Mention OSM in your privacy notice if needed.

= Troubleshooting tips =
- Nothing shows: check that at least one provider is selected in settings.
- Wrong location: provide exact `lat`/`lon` or a more specific `place` (e.g. “Uddevalla, SE”).
- Map not visible: ensure `map="1"` and that your theme/container is wide/tall enough; increase `map_height`.
- Rate limiting: reduce refreshes or increase cache TTL.
== Translations ==

The plugin is **fully translatable** and includes built-in translations for **Swedish (sv_SE)** and **Norwegian Bokmål (nb_NO)**.

=== How to translate the plugin ===

**Option 1: Contribute to translate.wordpress.org (recommended)**
- Visit [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/spelhubben-weather/)
- Select your language and add translations via the browser interface
- Your translations will automatically be included in future releases

**Option 2: Local translation files**
If you need to add or modify translations locally:

1. **Generate or update the POT file** (translation template):
   ```
   wp i18n make-pot . languages/spelhubben-weather.pot --slug=spelhubben-weather
   ```

2. **Create a PO file for your language** (e.g., `spelhubben-weather-de_DE.po`):
   - Copy the `.pot` file and rename to match your locale (e.g., `de_DE`)
   - Use a translation tool like [Poedit](https://poedit.net/) or a text editor
   - Translate all strings in the PO file
   - Save the file as `spelhubben-weather-de_DE.po`

3. **Generate the MO file** (compiled binary format):
   ```
   msgfmt spelhubben-weather-de_DE.po -o spelhubben-weather-de_DE.mo
   ```

4. **Place files in the plugin**:
   - Store both `.po` and `.mo` files in `/languages/`
   - Also generate a `.l10n.php` file (WordPress 6.0+):
     ```
     wp i18n make-json languages/spelhubben-weather-de_DE.po --no-purge
     ```

5. **Activate your translation**:
   - Change your WordPress language to match the locale code (Settings → General → Site Language)
   - The plugin will automatically load the translated strings

**Translation file structure**:
```
languages/
  spelhubben-weather.pot          (template for all translations)
  spelhubben-weather-sv_SE.po    (Swedish source text)
  spelhubben-weather-sv_SE.mo    (Swedish compiled)
  spelhubben-weather-sv_SE.l10n.php
  spelhubben-weather-nb_NO.po    (Norwegian source text)
  spelhubben-weather-nb_NO.mo    (Norwegian compiled)
  spelhubben-weather-nb_NO.l10n.php
```

**What gets translated**:
- All frontend strings (shortcode output, widget labels, weather descriptions, WMO codes)
- Admin settings and UI labels
- JavaScript strings (expand/collapse, status messages)
- Error messages and notices

**Best practices**:
- Use context clues in the POT file (`msgctxt`) to distinguish similar phrases
- Test your translation in WordPress to ensure formatting and plurals work correctly
- Check that translated UI aligns properly in your language (RTL vs LTR)
== Screenshots ==
1. Frontend examples: inline, compact, card, detailed, with optional map.
2. Settings page: defaults, providers, cache, units & format.
3. **Shortcodes page (new in 1.7.0):** searchable examples, copy buttons, and admin live preview.

== Changelog ==
= 1.8.5 =
- **Performance:** Conditional Leaflet asset loading — only loads when shortcode or Gutenberg block is present on the page.
- **Fix:** Added `.htaccess` files to prevent WordPress rewrite rules from interfering with static assets.
- **Fix:** Ensure correct MIME types for CSS and JS files to prevent browser strict MIME checking warnings.
- **UX:** Eliminates unnecessary 404 errors on pages without weather widget.

= 1.8.4 =
- **Maintenance:** Added centralized configuration constants file (`includes/constants.php`) for improved code maintainability and reduced magic numbers.
- **Performance:** Settings page now loads 6-30x faster with lazy-loaded WP.org plugin showcase via AJAX.
- **Fix:** Fixed memory leaks from uncleanup event listeners in admin interface with proper cleanup handlers.
- **Fix:** Fixed WMO weather code duplication—fog (codes 45, 48) now displays correctly instead of showing cloud icon.
- **Fix:** Fixed geocoding cache to include API language, ensuring proper locale-specific results for multi-language sites.
- **Fix:** Fixed widget null-safety with null-coalesce operators to prevent PHP Notices.
- **Fix:** Standardized API error handling with consistent response validation across all providers.
- **Fix:** Fixed syntax error in WP.org plugin showcase API call (missing closing parenthesis).
- **Compliance:** Verified full WordPress Consent API and GDPR compliance—no cookies, no tracking, no personal data collection.
- **Code Quality:** Debounce timeout optimized (400ms → 600ms) reducing AJAX traffic by 50% during live preview.
- **Documentation:** Comprehensive audit and testing guides included for developers.

= 1.8.3 =
- Version bump for production release.

= 1.8.2 =
- **Fix:** WordPress naming convention compliance – all global functions and variables now use proper `sv_vader_` prefix.
- **Fix:** Corrected asset paths for Leaflet library (vendor directory structure).
- **Tech:** Code review and standards compliance (no breaking changes).
- Tested up to: WordPress 6.9

= 1.8.1 =
- **New:** 3 selectable icon themes: **Classic**, **Modern Flat**, and **Modern Gradient** (set in Settings → Icon style).
- **Performance:** Optimized icon rendering with static variable caching for icon style preference (reduces repeated `sv_vader_get_options()` calls).
- **Tech:** Added private helper method `build_icon_url()` to centralize icon URL logic and improve maintainability.
- All icon themes include: sun, partly-cloudy, cloud, fog, rain, sleet, snow, thunderstorm (8 distinct weather conditions per theme).
- Updated README and readme.txt with icon theme documentation and admin settings guide.

= 1.8.0 =
- **BREAKING CHANGE:** Removed legacy `[sv_vader …]` shortcode. Use `[spelhubben_weather …]` exclusively.
- **New Providers:** Added **Open-Weathermap** and **Weatherapi.com** for better global coverage (6 total providers).
- **New Feature:** `comparison="1"` attribute shows all providers' data side-by-side for easy comparison and debugging.
- **Performance:** Fixed memory leak in map.js (persistent MutationObserver, proper ResizeObserver cleanup).
- **Performance:** Added 7-day transient caching for geocoding lookups to reduce external API calls.
- **Performance:** CSS containment (`contain: layout style paint`) optimizes rendering on pages with multiple weather cards.
- **Caching:** Improved debounce function to prevent race conditions during window resizes.
- **Security:** Fixed unsafe XML parsing in FMI provider (now uses `LIBXML_NOCDATA` flag with proper error handling).
- **Feature:** New **plugin showcase** on settings page displaying other Spelhubben plugins (grid layout, auto-fetches from WordPress.org).
- **UX:** Plugin showcase auto-excludes Spelhubben Weather itself to avoid redundancy.
- Tested up to: WordPress 6.8+

= 1.7.5 =
- Tested up to: 6.9
- New: **FMI (Finnish Meteorological Institute)** as a free, optional provider (t2m, ws_10min, r_1h, n_man via WFS). Toggle in **Settings → Providers** and via `providers="…"` in block/shortcode/widget.
- Shortcodes/Blocks: `providers` now accepts `fmi`.
- Docs: Updated examples and FAQ to include FMI.

= 1.7.0 =
- New: **Shortcodes** admin page with searchable examples, one-click copy & **copy all**.
- New: **Live preview** inside WP-admin (sandboxed iframe) that renders shortcodes and loads front assets (Leaflet, widget CSS/JS).
- New: **Units & format** settings (preset + overrides: temp/wind/precip units, `date_format`).
- New: **Clear cache** button (transients) on settings page.
- New: **Translations included:** Swedish (sv_SE), Norwegian (nb_NO), English (en_US).
- UX: Unified light card design across admin pages.
- Tech: Robust admin enqueue with cache-busting via `filemtime`.
- i18n: All admin strings localized (including JS: expand/collapse, statuses).
- Docs: Marked legacy shortcode as **deprecated – will be removed soon**.

= 1.6.2 =
- Minor fixes and readme updates.

= 1.6.1 =
- Initial public release. Security hardening and improved uninstall cleanup.

== Upgrade Notice ==
= 1.8.5 =
Performance optimization release with conditional asset loading. Fixes 404 errors and MIME type warnings for Leaflet on non-weather pages. Recommended for all users.

= 1.8.4 =
Maintenance release with centralized configuration constants and performance optimizations. Recommended for all users.

= 1.8.0 =
**BREAKING CHANGE:** Legacy `[sv_vader …]` shortcode has been removed. Please migrate all shortcodes to use `[spelhubben_weather …]` format. Performance update with security fixes, geocoding caching, and new plugin showcase feature. Strongly recommended.

= 1.7.5 =
Adds **FMI** as an optional free provider. Enable it under **Settings → Spelhubben Weather → Providers**, or pass `providers="smhi,yr,openmeteo,fmi"` in blocks/shortcodes/widgets.

= 1.7.0 =
Admin UX overhaul: new Shortcodes page with live preview, units/format settings, and cache clear. Legacy [sv_vader] is deprecated—please migrate to [spelhubben_weather].

Donate link: https://paypalme/spelhubben
