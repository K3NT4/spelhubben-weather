# Spelhubben Weather

WordPress weather plugin. Shows current conditions and an optional daily forecast using a simple consensus of **Open-Meteo**, **SMHI**, and **Yr (MET Norway)**. Includes a Gutenberg block, classic widget, shortcode, optional Leaflet map, responsive layouts, and local SVG icons.

> This `README.md` is for GitHub. For WordPress.org metadata, use `/readme.txt`.

## Features
- Shortcode `[spelhubben_weather]`, Gutenberg block, and classic widget
- Providers: Open-Meteo, SMHI, Yr — enable one or combine
- Multiple layouts: `inline`, `compact`, `card`, `detailed`
- Daily forecast (3–10 days)
- Leaflet map with OSM tiles and locked attribution (ODbL)
- Local SVG icons (no CDN), responsive scaling, transient cache
- Translation-ready (English base strings)

## Local Leaflet (required for WordPress.org)
WordPress.org disallows loading CSS/JS from third-party CDNs. Bundle Leaflet **locally** in the plugin.

**Folder structure**
```
assets/
  vendor/
    leaflet/
      leaflet.css
      leaflet.js
      images/
        marker-icon.png
        marker-icon-2x.png
        marker-shadow.png
```

**PHP enqueue (excerpt)**  
*(main file: `spelhubben-weather.php` — handles renamed for clarity; constants remain backward-compatible)*

```php
// In spelhubben-weather.php -> enqueue_public_assets()
wp_enqueue_style('spelhubben-weather-style', SV_VADER_URL . 'assets/style.css', [], SV_VADER_VER);

wp_register_style('leaflet-css', SV_VADER_URL . 'assets/vendor/leaflet/leaflet.css', [], '1.9.4');
wp_enqueue_style('leaflet-css');

wp_register_script('leaflet-js', SV_VADER_URL . 'assets/vendor/leaflet/leaflet.js', [], '1.9.4', true);
wp_enqueue_script('leaflet-js');

wp_register_script('spelhubben-weather-map', SV_VADER_URL . 'assets/map.js', ['leaflet-js'], SV_VADER_VER, true);
wp_localize_script('spelhubben-weather-map', 'SVV', [
  'iconBase' => trailingslashit(SV_VADER_URL . 'assets/vendor/leaflet/images'),
]);
wp_enqueue_script('spelhubben-weather-map');
```

## Shortcode examples
```text
[spelhubben_weather]
[spelhubben_weather place="Gothenburg" layout="compact" map="1" animate="1"]
[spelhubben_weather lat="57.7089" lon="11.9746" place="Gothenburg" layout="inline" map="0" show="temp,icon"]
[spelhubben_weather place="Umeå" layout="detailed" forecast="daily" days="5" providers="smhi,yr,openmeteo"]
```

## Attributes (shortcode)
- `place` — place name to geocode (used if `lat`/`lon` are absent)  
- `lat`, `lon` — coordinates; take precedence over `place`  
- `show` — comma list of fields to display (e.g., `temp,wind,icon`)  
- `layout` — `inline | compact | card | detailed`  
- `map` — `1`/`0` to show/hide map  
- `map_height` — map height in px (min 120)  
- `providers` — `openmeteo,smhi,yr` (comma-separated)  
- `animate` — `1`/`0` for subtle animations  
- `forecast` — `none | daily`  
- `days` — number of days (3–10) when `forecast="daily"`  
- `class` — extra CSS class on the wrapper  

## Development
- PHP 7.4+; WordPress 6.0+ (tested 6.8)
- Run the **Plugin Check** plugin before release
- Keep `/readme.txt` “Stable tag” in sync with the main file’s `Version` header
- Generate POT after string changes:  
  `wp i18n make-pot . languages/spelhubben-weather.pot --slug=spelhubben-weather`

## Migration (from “SV Väder”)
- Main file renamed to `spelhubben-weather.php`
- Text domain changed to `spelhubben-weather`
- New shortcode `[spelhubben_weather]` (old aliases may be removed in a future major)
- Readme and UI strings use English as the base language

## Privacy
- No personal data collected. API responses cached in transients for a short time
- External requests: Open-Meteo, SMHI, MET Norway (Yr), and OSM tile servers

## License
- Code: GPLv2 or later
- Leaflet (bundled): BSD-2-Clause
- Icons: local SVG created for this plugin

## Trademarks (no affiliation)
Open-Meteo, SMHI, Yr, MET Norway, Leaflet, and OpenStreetMap are trademarks or project names of their respective owners. This plugin is not affiliated with or endorsed by them.
