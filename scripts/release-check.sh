#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

tmp_dir="$(mktemp -d)"
cleanup() {
  if [[ -f "$tmp_dir/map.min.js" ]]; then
    cp "$tmp_dir/map.min.js" assets/map.min.js
  fi
  if [[ -f "$tmp_dir/openlayers.js" ]]; then
    cp "$tmp_dir/openlayers.js" assets/openlayers.js
  fi
  if [[ -f "$tmp_dir/openlayers.css" ]]; then
    cp "$tmp_dir/openlayers.css" assets/openlayers.css
  fi
  if [[ -f "$tmp_dir/style.min.css" ]]; then
    cp "$tmp_dir/style.min.css" assets/style.min.css
  fi
  rm -rf "$ROOT_DIR/node_modules" "$tmp_dir"
}
trap cleanup EXIT

echo "==> Running PHP regression checks"
php tests/regression_test.php

echo "==> Verifying required release files"
[[ -f assets/map.min.js ]] || { echo "Missing assets/map.min.js"; exit 1; }
[[ -f assets/openlayers.js ]] || { echo "Missing assets/openlayers.js"; exit 1; }
[[ -f assets/openlayers.css ]] || { echo "Missing assets/openlayers.css"; exit 1; }
[[ -f assets/style.min.css ]] || { echo "Missing assets/style.min.css"; exit 1; }
[[ -f licenses/THIRDPARTY.txt ]] || { echo "Missing licenses/THIRDPARTY.txt"; exit 1; }
[[ -f licenses/leaflet-BSD-2-Clause.txt ]] || { echo "Missing licenses/leaflet-BSD-2-Clause.txt"; exit 1; }
[[ -f licenses/openlayers-BSD-2-Clause.txt ]] || { echo "Missing licenses/openlayers-BSD-2-Clause.txt"; exit 1; }
grep -q 'npm run build:assets' README.md || { echo "README.md is missing build:assets release guidance"; exit 1; }

cp assets/map.min.js "$tmp_dir/map.min.js"
cp assets/openlayers.js "$tmp_dir/openlayers.js"
cp assets/openlayers.css "$tmp_dir/openlayers.css"
cp assets/style.min.css "$tmp_dir/style.min.css"

echo "==> Rebuilding minified assets"
npm ci
npm run build:assets >/dev/null

echo "==> Comparing rebuilt assets against committed files"
cmp -s assets/map.min.js "$tmp_dir/map.min.js" || { echo "assets/map.min.js is out of sync with assets/map.js"; exit 1; }
cmp -s assets/openlayers.js "$tmp_dir/openlayers.js" || { echo "assets/openlayers.js is out of sync with assets/openlayers-entry.js"; exit 1; }
cmp -s assets/openlayers.css "$tmp_dir/openlayers.css" || { echo "assets/openlayers.css is out of sync with assets/openlayers-entry.js"; exit 1; }
cmp -s assets/style.min.css "$tmp_dir/style.min.css" || { echo "assets/style.min.css is out of sync with assets/style.css"; exit 1; }

echo "Release checks passed."
