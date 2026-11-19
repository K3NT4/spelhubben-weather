#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

tmp_dir="$(mktemp -d)"
cleanup() {
  rm -rf "$tmp_dir"
}
trap cleanup EXIT

php tests/run.php
node tests/map_runtime_test.js

for asset in assets/map.min.js assets/openlayers.js assets/openlayers.css assets/style.min.css licenses/THIRDPARTY.txt licenses/leaflet-BSD-2-Clause.txt licenses/openlayers-BSD-2-Clause.txt; do
  [[ -f "$asset" ]] || { echo "Missing $asset"; exit 1; }
done
grep -q 'npm run build:assets' README.md || { echo "README.md is missing build guidance"; exit 1; }

# Build in a disposable directory: never replace or remove the checkout's
# node_modules (some existing checkouts version that directory).
cp package.json package-lock.json "$tmp_dir/"
cp -R assets "$tmp_dir/assets"
(
  cd "$tmp_dir"
  npm ci
  npm run build:assets
)
for asset in map.min.js openlayers.js openlayers.css style.min.css; do
  cmp -s "assets/$asset" "$tmp_dir/assets/$asset" || { echo "assets/$asset is out of sync"; exit 1; }
done

echo "Release checks passed."
