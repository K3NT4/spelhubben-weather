# 2.2.5 release verification

Validation record for the 2.2.5 maintenance release (GitHub issue #32). WordPress.org publication is triggered by an explicitly pushed release tag, which is created only after the GitHub checks for the release commit have passed.

## Scope

- WordPress 7.2 compatibility review against the 7.2 field guide (media, editor components, Abilities API, REST API, admin SVG icons, Interactivity API, jQuery Migrate 3.6, miscellaneous editor changes) and PHP 8.6.
- Full plugin bug review. Fixes: SMHI API migration (`pmp3g` → `snow1g`), dropped shortcode unit/format attributes, daily forecast timezone, widget wind unit fallback, cache clear/settings save option handling, tide `WP_Error` guard, admin dark-mode readability, block editor list keys, uninstall prefix scope, batched statistics writes and geocoding negative cache.
- `fflate` development dependency 0.8.2 → 0.8.3 (`npm audit`: 0 vulnerabilities).

## Verified

Local WordPress 7.2 RC3 (upgraded from 7.1.5 with database and core backups), PHP 8.5.13 and PHP 8.6.0 (official `php:8.6-cli` image).

- `php tests/run.php` on PHP 8.5 and 8.6, including the new `tests/maintenance_test.php` (SMHI snow1g parsing/status mapping, daily timezone, geocoding negative cache, batched statistics, cache invalidation on older stored options).
- `node tests/map_runtime_test.js` and an isolated asset rebuild: distributed assets are byte-identical after the dependency update.
- WordPress 7.2 bootstrap on PHP 8.5 and 8.6 with all notices and `_doing_it_wrong`/deprecation hooks captured: shortcode, block, widget, patterns and all four admin pages render with no notices from the plugin.
- Headless Chromium against WordPress 7.2 RC3: block insertion in the iframed editor, ServerSideRender preview, all inspector controls (text, select, toggle, range) update attributes and re-render, all blocks valid, six patterns available, no plugin console warnings; admin menu icon renders as SVG and page Dashicons still load; front-end maps initialise lazily (8/8 drawn).
- Admin pages with an emulated dark OS colour scheme: headings and labels use the light wp-admin palette.
- WordPress Plugin Check 2.2.0, all categories, on the distribution file set: no errors or warnings.
- Live provider checks: Open-Meteo, SMHI (snow1g), Yr, MET Norway Nowcast return data for Stockholm; FMI returns no data outside Finland as expected; key-based providers report a missing key.
- Live shortcode checks: `units="imperial"` renders °F/mph, `wind_unit="kmh"` renders km/h, an unknown place shows the localized "place not found" message.

## Limits

- PHP 8.6 was verified through the CLI test suites and a WordPress bootstrap, not through a browser session (the local site runs PHP 8.5).
- Blocks keep using the block's own attribute defaults (`units`, `mapEngine`, `layout`) rather than the global settings; changing that would alter existing content and is out of scope for a maintenance release.
- Plugin Check is static and does not replace testing with other themes or plugins.
