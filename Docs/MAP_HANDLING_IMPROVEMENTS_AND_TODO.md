# Map Handling Improvements and TODO

Date: 2026-05-03
Scope: Spelhubben Weather map handling (OpenLayers, Leaflet legacy, static fallback, rendering, performance, compliance, tests)

## Goal

Improve map reliability, performance, compliance clarity, and maintainability without breaking current shortcode/block behavior.

## What Should Be Improved

### 1) Compliance and privacy clarity for Leaflet fallback

Current situation:
- The runtime no longer falls back to external CDNs for Leaflet JS/CSS.
- OpenLayers and Leaflet assets are bundled locally, and the map runtime shows a static fallback if an interactive engine cannot initialize.

Why this matters:
- External fallback adds additional third-party requests that may need clearer disclosure.
- Documentation mismatch can create legal/compliance risk and support confusion.

Improve by:
- Keeping the explicit policy: strictly self-hosted map runtime assets only.
- Updating consent/compliance docs whenever map runtime behavior changes.
- Keeping admin diagnostics aligned with the local asset policy.

Relevant files:
- assets/map.js
- Docs/CONSENT_API_AUDIT.md
- admin/page-settings.php (optional notice)

---

### 2) Consistent map height validation in renderer

Current situation:
- Options sanitization clamps map height to a minimum (120).
- Renderer, shortcode, block and widget paths now clamp map height to the documented minimum.

Why this matters:
- Invalid/small heights can lead to poor UX and map init issues.
- Inconsistency between settings, docs, and runtime behavior.

Improve by:
- Keeping docs and block/widget controls aligned to the same bounds as future UI changes land.

Relevant files:
- includes/class-renderer.php
- includes/options.php
- admin/page-shortcodes.php

---

### 3) Reduce expensive global map rescans

Current situation:
- Map initialization now scans added nodes instead of blindly rescanning the full document on every DOM update.
- A ResizeObserver remains for card scaling and map resize handling.

Why this matters:
- Can be expensive on dynamic pages (page builders, widgets, infinite scroll).
- Unnecessary repeated scans can hurt performance.

Improve by:
- Keep future map changes scoped to added nodes or plugin containers.
- Avoid reintroducing full document scans when mutation target has no map candidates.
- Optionally batching init attempts via requestAnimationFrame.

Relevant files:
- assets/map.js

---

### 4) CSS validity and maintainability around forecast card/moon rules

Current situation:
- Moon-related selectors are placed inside the daycard block in a way that risks invalid CSS structure.

Why this matters:
- Some rules may be ignored by browser parser.
- Harder to maintain and reason about visual behavior.

Improve by:
- Moving moon selectors to proper top-level CSS blocks.
- Running lint/quick visual regression check after fix.

Relevant files:
- assets/style.css

---

### 5) Add map-focused test coverage

Current situation:
- `tests/regression_test.php` covers map height clamping, local-only map runtime policy, minified asset drift, and per-instance map engine asset loading.
- Browser-level map lifecycle and fallback behavior still need fuller coverage.

Why this matters:
- Regressions in map loading are easy to miss.
- Hard to safely refactor map lifecycle/performance logic.

Improve by:
- Adding browser-level scenario tests for:
  - Leaflet present vs missing
  - Invalid lat/lon
  - Dynamic DOM insertion/removal
  - Fallback policy behavior (enabled/disabled)

Relevant files:
- tests/ (new map test files)
- assets/map.js

---

### 6) Better observability for map failures

Current situation:
- Error messages are partly visible in console and inline fallback text.

Why this matters:
- Support/debugging in production is harder without consistent signals.

Improve by:
- Standardizing error codes/messages (e.g. SVV_MAP_LEAFLET_LOAD_FAIL).
- Emitting one structured console warning per failed map element.
- Optionally exposing a debug mode flag in admin for verbose map logs.

Relevant files:
- assets/map.js
- admin settings (optional)

---

## Prioritized TODO List

Use this as the implementation checklist.

## P0 (Do first)

- [x] Decide fallback policy for Leaflet/OpenLayers runtime assets (self-hosted only).
- [x] Align docs with actual behavior in consent/compliance documentation.
- [x] Enforce map_height min clamp in renderer shortcode path.

## P1 (Next)

- [x] Refactor MutationObserver logic to reduce global rescans.
- [x] Add debounce/batching for map scan callback.
- [ ] Fix CSS block structure for daycard/moon selectors.

## P2 (Then)

- [ ] Add map smoke test(s) in tests folder.
- [ ] Add scenario tests for map lifecycle and fallback cases.
- [x] Improve map error observability with structured map diagnostics and frontend fallback events.

## Suggested Implementation Order

1. Policy + documentation update (compliance first).
2. Renderer validation fix (quick safety win).
3. Observer/performance refactor.
4. CSS cleanup and visual sanity pass.
5. Tests and observability hardening.

## Definition of Done

- Map behavior matches documented behavior for all environments.
- map_height is consistently bounded across settings, shortcode, and block usage.
- Dynamic pages do not cause unnecessary map rescans.
- CSS validates and renders consistently.
- Basic automated map regression checks exist.
- Clear error signals exist for failed map initialization.

## Notes

Keep backward compatibility for shortcode attributes and existing block content. Any policy change to CDN fallback should be explicitly mentioned in changelog/release notes.

---

## Additional Code Improvements Worth Adding

These items are outside pure map lifecycle handling, but they impact reliability and support quality.

### A1) Provider API key flow is inconsistent (OpenWeatherMap/WeatherAPI)

Current situation:
- Provider functions for OpenWeatherMap and WeatherAPI are called without API key parameters.
- Comments in provider code indicate no API key is required, while README text says keys are required.
- Admin currently exposes provider toggles but no dedicated API key settings for these two providers.

Why this matters:
- Provider calls may fail silently or always return null for many sites.
- Mixed documentation causes support overhead and wrong expectations.

Improve by:
- Deciding and implementing one clear strategy:
  - add API key fields and pass key in provider requests, or
  - remove providers from UI/docs until fully supported.
- Updating README/admin strings to exactly match runtime behavior.
- Emitting explicit provider error reason in debug/performance view.

Relevant files:
- includes/providers.php
- admin/admin.php
- includes/options.php
- README.md

---

### A2) Documentation drift for supported providers and shortcode attributes

Current situation:
- Some docs/pages list old provider subsets while code supports more providers.
- README contains duplicated shortcode sections and overlapping attribute rows.

Why this matters:
- Users copy outdated shortcode examples.
- Harder to maintain consistency across admin UI and README.

Improve by:
- Creating one canonical provider list and shortcode attribute table.
- Reusing that source in admin Shortcodes page + README during releases.
- Removing duplicated README shortcode sections.

Relevant files:
- admin/page-shortcodes.php
- README.md

---

### A3) Minified asset workflow should be automated

Current situation:
- map.min.js is maintained as a synced copy and has previously drifted.
- No explicit build/lint step is documented for JS/CSS minified assets.

Why this matters:
- Manual sync errors can break production unexpectedly.
- Release quality depends on human memory.

Improve by:
- Adding a reproducible build command for map/style minification.
- Running the build in release checklist/CI.
- Treating source files as canonical and generated minified files as build artifacts.

Relevant files:
- assets/map.js
- assets/map.min.js
- assets/style.css
- README.md (release/build notes)

---

### A4) Improve cache key hit rate by normalizing provider order

Current situation:
- Cache keys include provider arrays as-is.
- Different provider order can produce different cache keys for same logical request.

Why this matters:
- Lower cache hit ratio and unnecessary remote API calls.

Improve by:
- Sorting/normalizing provider list before building cache key.
- Applying same normalization in current weather and provider-details paths.

Relevant files:
- includes/class-sv-vader.php

---

### A5) Add broader automated regression tests and release checks

Current situation:
- tests folder currently focuses on tide CLI helper.
- No automated checks covering provider integrations, shortcode rendering permutations, or docs consistency.

Why this matters:
- Breakages are discovered late (after release/testing on live sites).

Improve by:
- Adding lightweight integration tests for key render/provider flows.
- Adding release checklist items for doc consistency and minified asset sync.

Relevant files:
- tests/
- README.md
- Docs/

## TODO Additions (Cross-Code)

Add these to implementation planning alongside the map-specific tasks.

## P0 Additions

- [x] Resolve OpenWeatherMap/WeatherAPI API key strategy (implement fully or hide/remove until ready).
- [x] Align provider docs/comments/admin texts with actual runtime behavior.
- [x] Clamp `map_height` in the renderer so shortcode/block/widget overrides honor the documented 120px minimum.
- [x] Document Leaflet/OSM consent-banner fallback guidance without gating the rest of the weather widget.

## P1 Additions

- [ ] Normalize provider list ordering before cache key generation.
- [ ] Remove shortcode/docs duplication and unify provider list across admin + README.

## P2 Additions

- [x] Add automated build/minify workflow for map/style assets.
- [x] Add regression tests for provider + renderer combinations and release checklist validation.
