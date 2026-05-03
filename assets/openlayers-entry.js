import Map from 'ol/Map.js';
import View from 'ol/View.js';
import TileLayer from 'ol/layer/Tile.js';
import OSM from 'ol/source/OSM.js';
import { fromLonLat } from 'ol/proj.js';
import 'ol/ol.css';

export function createMap(target, options) {
  const lat = Number(options.lat);
  const lon = Number(options.lon);
  if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
    throw new Error('SVV_MAP_INVALID_COORDS');
  }

  const map = new Map({
    target,
    layers: [
      new TileLayer({
        source: new OSM({
          attributions: [],
        }),
      }),
    ],
    view: new View({
      center: fromLonLat([lon, lat]),
      zoom: options.zoom || 12,
    }),
    controls: [],
  });

  setTimeout(() => map.updateSize(), 200);
  return map;
}
