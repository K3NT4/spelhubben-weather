# FAQ — Spelhubben Weather

## Installation and requirements

Upload the plugin ZIP through Plugins → Add New → Upload Plugin, activate it, then open Spelhubben Weather → Settings. WordPress 6.8+ and PHP 7.4+ are required. Set a default place, select providers and check the getting-started checklist. The server needs outgoing HTTPS access; themes should call `wp_footer()`. Clear page/optimization caches after updating.

## Block, shortcode or widget?

Add the Spelhubben Weather block, insert `[spelhubben_weather]` in a shortcode block, or add the classic widget under Appearance → Widgets. Per-instance controls override global defaults. On the admin Shortcodes page, use Quick Builder and live preview, then copy the generated shortcode.

```text
[spelhubben_weather place="Stockholm" layout="compact" map="1"]
[spelhubben_weather lat="59.3293" lon="18.0686" hourly="1" hours="24"]
[spelhubben_weather forecast="daily" days="5" extras="moon,moon_daily"]
[spelhubben_weather astro="1" insights="1" style_preset="neutral"]
[spelhubben_weather preset="hero" style_preset="spelhubben"]
```

Coordinates take precedence over `place`. Use a precise city/country if geocoding finds the wrong location.

## Providers and API keys

Open-Meteo, SMHI, Yr/MET Norway, MET Norway Nowcast and FMI need no API keys. OpenWeatherMap and WeatherAPI.com are disabled by default until keys are configured server-side. Coverage varies by provider. Set Yr contact email/URL to identify API requests. Use `providers="smhi,yr,openmeteo,fmi"` to select providers and `comparison="1"` to inspect individual results. Hourly forecasts use Open-Meteo.

## Fields, layouts and styles

Use `show="temp,wind,wind_dir,icon"` for weather fields. Moon information uses extras, not `show`. Layouts are `inline`, `compact`, `card`, `detailed`; layout presets are `mini`, `hero`, `sidebar`, `dashboard`, `forecast-strip`. Separate `style_preset` values are `light`, `dark`, `neutral`, `spelhubben`, `compact`. Empty/invalid style retains the default appearance. Explicit `theme="light"` or `theme="dark"` wins over a conflicting style. New panels and styles are opt-in; existing defaults remain unchanged. Inline omits larger forecasts and optional panels; mini hides map/forecast surfaces.

Use `hourly="1" hours="24"` for hourly cards (3–24 hours) or `forecast="daily" days="5"` for daily forecasts (3–10 days). The block and widget expose these options, including astro, insights and style. Editor patterns include `weather-card`, `forecast-row`, `weather-map`, `astro-panel` and existing compact/detailed/forecast patterns under Spelhubben Weather.

## Sun and moon

The sun/moon panel uses the WordPress site timezone, explicitly shown in the panel, rather than automatically selecting the location’s timezone. Sunrise, sunset and day length belong to the same solar cycle; event dates clarify adjacent-day events. Polar day/night are handled explicitly. Moon phases and the next main phase use an approximation, not precision astronomical predictions. Existing `extras="moon"` and `extras="moon_daily"` remain supported; `phase` and `illumination` are not `show` fields.

Enable with `astro="1"` or `extras="astro"`. Daily moon extras require `forecast="daily"`.

## Forecast insights

Enable with `insights="1"` or `extras="insights"`.

Insights use complete future hourly forecasts within the next 24 hours, requiring at least six consecutive complete hours with wind in m/s and precipitation in mm. Missing, invalid, gapped or stale data produces an explanatory fallback. A mostly dry period requires less than 0.1 mm per hour for at least two consecutive hours; rain later requires at least 0.2 mm in an hour at least two hours ahead after two initially dry forecast hours. Wind peaks are relative to the available forecast. These summaries provide no probabilities or guarantees and are not weather warnings. Enabling insights alone does not display the hourly timeline.

## Units, icons and animation

Use `units="metric|metric_kmh|metric_knt|imperial"` with individual `temp_unit`, `wind_unit`, `precip_unit` overrides; for example `wind_unit="knt"`. Icon themes are Classic, Modern Flat, Modern Gradient, Modern 2026 and Modern 3D. `animate="1"` enables subtle animation; `true`, `yes` and `on` also work.

## Maps, privacy and diagnostics

Enable `map="1"` and optionally `map_height="240"` (minimum 120 px). `map_engine="auto"` tries local OpenLayers, then local Leaflet, then static fallback; force `openlayers`, `leaflet` or `static` when needed.

The static map fallback is included in the server-rendered HTML, even when JavaScript is blocked or never loads. With valid coordinates it includes an OpenStreetMap link. Performance shows administrator-only, rate-limited map diagnostics: the latest status and a separate latest error, retained for up to seven days. Reports store fixed status codes, engine names and timestamps, not visitor identities or raw browser errors. Guests do not report. This is not live monitoring and cannot identify the exact plugin blocking a script.

The plugin itself sets no cookies or tracking. Weather requests run server-side. Interactive maps request OpenStreetMap tiles from the browser; account for these third-party requests in your privacy information.

## Troubleshooting and cache

- No weather: check provider selection, API keys where needed, valid location and outgoing HTTPS access.
- Wrong location: supply exact `lat`/`lon` or a city with country.
- Map does not start: inspect fallback/status, try static mode, and exclude map scripts from delayed-JavaScript optimization where necessary.
- Stale data or rate limits: clear plugin cache on Performance, clear page caches and increase cache TTL in Settings if needed.
- Missing insights: enough complete future hours must be available; a fallback message is expected when data is insufficient.

## Languages

English (`en_US`) is the source language. Swedish (`sv_SE`) and Norwegian Bokmål (`nb_NO`) UI translations include PHP/MO and block-editor JSON catalogs. Frontend follows the active site locale; admin follows the user’s WordPress language. Provider-supplied descriptions depend on provider language support. See README translation instructions for custom locales.

## Support and voluntary reviews

Support and bug reports: https://github.com/K3NT4/spelhubben-weather/issues. Reviews are voluntary: https://wordpress.org/plugins/spelhubben-weather/#reviews. A discreet settings link and a reminder 14 days after the first administrator visit after installation (or upgrading from a version without the reminder) appear only on the plugin’s own admin pages. Choose “No thanks” to permanently dismiss the reminder for the whole site, including after updates; the optional settings link remains. There is no frontend popup, tracking or background request for reviews; reviewing or dismissing has no effect on plugin functionality.

On multisite, each site has its own reminder clock and dismissal.
