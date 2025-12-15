# Spelhubben Weather

WordPress weather plugin. Shows current conditions and an optional daily forecast using a simple consensus of **Open-Meteo**, **SMHI**, **Yr (MET Norway)**, **FMI (Finland)**, **Open-Weathermap**, and **Weatherapi.com**. Includes a Gutenberg block, classic widget, shortcode, optional Leaflet map, responsive layouts, multiple icon themes, and local SVG icons.

> This `README.md` is for GitHub. For WordPress.org metadata, use `/readme.txt`.

## Features
- Shortcode `[spelhubben_weather]`, Gutenberg block, and classic widget
- **Providers:** Open-Meteo, SMHI, Yr (MET Norway), FMI, Open-Weathermap, Weatherapi.com — enable one or combine
- **Icon themes:** Classic, Modern Flat, Modern Gradient (selectable in admin settings)
- Multiple layouts: `inline`, `compact`, `card`, `detailed`
- Daily forecast (3–10 days)
- Provider comparison mode — see side-by-side data from all enabled providers
- Leaflet map with OSM tiles and locked attribution (ODbL)
- Local SVG icons (no CDN), responsive scaling, transient cache
- Performance: Optimized icon rendering with static caching, 10-minute weather cache
- Translation-ready (English base strings)
- **v1.8.0:** Performance optimizations, plugin showcase, improved caching, 2 new providers, 3 icon themes

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
[spelhubben_weather place="Umeå" layout="detailed" forecast="daily" days="5" providers="smhi,yr,openmeteo,fmi"]
[spelhubben_weather place="Stockholm" comparison="1" providers="openmeteo,smhi,yr,fmi,openweathermap,weatherapi"]
```

## Icon Themes
The plugin includes **3 icon themes** selectable in Settings:
1. **Classic** — Traditional, timeless design
2. **Modern Flat** — Clean, minimalist aesthetic
3. **Modern Gradient** — Contemporary with subtle gradients and shadows

All themes include icons for: sun, partly-cloudy, cloud, fog, rain, sleet, snow, and thunderstorm.

## Shortcode examples
- `place` — place name to geocode (used if `lat`/`lon` are absent)  
- `lat`, `lon` — coordinates; take precedence over `place`  
- `show` — comma list of fields to display (e.g., `temp,wind,icon`)  
- `layout` — `inline | compact | card | detailed`
- `comparison` — `1` to show side-by-side provider comparison (ignores `layout`)  
- `map` — `1`/`0` to show/hide map  
- `map_height` — map height in px (min 120)  
- `providers` — `openmeteo,smhi,yr` (comma-separated)  
- `animate` — `1`/`0` for subtle animations  
- `forecast` — `none | daily`  
- `days` — number of days (3–10) when `forecast="daily"`  
- `class` — extra CSS class on the wrapper  

## Admin Settings
- **Default place** — fallback location (e.g., Stockholm)
- **Cache TTL** — transient lifetime in minutes (default: 10)
- **Default layout** — inline, compact, card, or detailed
- **Icon style** — Classic, Modern Flat, or Modern Gradient
- **Data providers** — checkboxes for each available source
- **Units** — metric, metric_kmh, or imperial presets with optional overrides
- **Date format** — PHP strtotime format for forecast labels

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
