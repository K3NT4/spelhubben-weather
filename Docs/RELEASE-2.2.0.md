# 2.2.0 release verification

Validation record for 2.2.0. GitHub issue and project-board state are unchanged. WordPress.org plugin publication is triggered by an explicitly pushed release tag; readme/assets updates run separately on eligible main-branch changes.

## Scope

- Smart maps retained; HTML fallback and administrator diagnostics completed.
- Optional sun/moon panel, responsive hourly timeline and conservative forecast insights.
- Existing layout presets retained and styled independently through `style_preset`.
- Gutenberg patterns, shortcode builder/preview and classic widget support.
- English source text with Swedish and Norwegian Bokmål catalogs.
- Optional review link and a reminder after 14 days, permanently dismissible per site.
- FAQ, README and WordPress.org readme updated.

## Verified

Local WordPress 7.1.5, PHP 8.5, and the Paseo browser were used.

- `php tests/run.php`: renderer/provider regressions, map diagnostic authorization/validation/rate limits, deterministic solar/lunar calculations, 48 forecast-insight checks, presets, reminder timing/scope/authorization/persistence.
- `node tests/map_runtime_test.js`: source/minified map engines, fallback, invalid coordinates, independent reporter, late startup and bounded requests.
- `php tests/i18n_test.php /path/to/wordpress/wp-includes/l10n`: English fallback and locale changes, Swedish/Norwegian PHP/MO and editor JSON, including review messages and corrected older translations.
- `bash scripts/release-check.sh`: isolated rebuild and byte comparison of distributed assets; checkout `node_modules` remains unchanged.
- PHP syntax check on all 40 PHP files; `git diff --check`.
- WordPress Plugin Check static checks: no findings after output escaping and readme upgrade-notice fixes. Other theme/addon code was skipped for the clean check.
- Live shortcode, Gutenberg rendering, classic widget, administrator shortcode preview, and the Gutenberg patterns REST endpoint.
- Live Open-Meteo hourly data, explicit wind units, UTC timestamps and normalized upcoming precipitation intervals.
- Browser checks of all five layouts/styles, 24-hour scrolling at 320 px, forced light inside a dark wrapper, dark fallback, and the retained map modes.
- Authenticated HTTP reminder tests: hidden before 14 days, visible only on plugin pages, invalid nonce rejected, valid dismissal persists. Temporary reminder options and test sessions were restored/removed.

## Limits and follow-up

- Local checks used PHP 8.5. GitHub Actions also passed lint and regressions on PHP 7.4, 8.1, 8.2, 8.3, 8.4 and 8.5, plus translation compatibility on WordPress 6.8/latest and the asset/release checks: [verified run](https://github.com/K3NT4/spelhubben-weather/actions/runs/44490813522).
- Plugin Check was static; it does not prove absence of all defects or replace testing with other themes/plugins.
- Moon dates are approximate. Insights require sufficient complete future data and provide summaries, not weather guarantees or probabilities.
- The dependency audit still reports the pre-existing moderate advisory in the indirect development dependency `fflate`; dependencies were not upgraded in this feature work.
- WordPress.org screenshots 1–6 are retained and described as earlier interfaces; they do not depict the new 2.2 panels.
- New features are opt-in; existing stored settings were not migrated or reset.

## Contributions

Codex handled integration and validation; delegated Codex `gpt-6.1-sol` agents implemented astro/insight helpers and documentation. OpenCode `deepseek-v4.1-flash-free` implemented style/preset foundations. Claude `claude-opus-5-5` independently reviewed runtime logic and the reminder. An attempted OpenCode documentation run failed before contributing and was replaced by Codex.
