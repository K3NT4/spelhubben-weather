// Runtime behavior with a minimal DOM, without network access or WordPress.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

class Element {
  constructor(attrs = {}) {
    this.attrs = attrs;
    this.dataset = {};
    this.children = [];
    this.nodeType = 1;
  }
  getAttribute(key) { return this.attrs[key] ?? null; }
  setAttribute(key, value) { this.attrs[key] = value; }
  appendChild(child) { this.children.push(child); }
  set innerHTML(value) { this.children = []; }
  querySelectorAll() { return []; }
  getBoundingClientRect() { return { width: 300, height: 240, top: 0, bottom: 240 }; }
}
function run(engine, lat = '59.3', lon = '18.1', libraries = {}, file = 'assets/map.js') {
  const el = new Element({ 'data-engine': engine, 'data-lat': lat, 'data-lon': lon, 'data-name': 'Stockholm' });
  const listeners = {};
  const document = {
    readyState: 'complete', documentElement: {},
    createElement: () => new Element(),
    querySelectorAll: (selector) => selector === '.svv-map' ? [el] : [],
    addEventListener: (name, fn) => { listeners[name] = fn; },
    dispatchEvent: (event) => { if (listeners[event.type]) listeners[event.type](); }
  };
  const window = { getComputedStyle: () => ({ height: '240px' }), ...libraries };
  vm.runInNewContext(fs.readFileSync(file, 'utf8'), {
    window, document, console, setTimeout: () => 1, clearTimeout() {},
    MutationObserver: class { observe() {} }, CustomEvent: class { constructor(type) { this.type = type; } }
  });
  return { el, window };
}
const ol = { SVVOpenLayers: { createMap: () => ({ setTarget() {} }) } };
const leaflet = { L: { map: () => ({ setView() {}, invalidateSize() {}, remove() {} }), tileLayer: () => ({ addTo() {} }) } };
for (const file of ['assets/map.js', 'assets/map.min.js']) {
  for (const engine of ['auto', 'openlayers', 'leaflet', 'static']) {
    const { el } = run(engine, '0', '0', { ...ol, ...leaflet }, file);
    assert.equal(el.dataset.activeEngine, engine === 'auto' ? 'openlayers' : engine);
    assert.equal(el.dataset.mapStatus, engine === 'static' ? 'SVV_MAP_STATIC_MODE' : 'SVV_MAP_STARTED');
  }
  assert.equal(run('auto', '59', '18', leaflet, file).el.dataset.activeEngine, 'leaflet');
  const broken = { SVVOpenLayers: { createMap() { throw Error('private URL must not be recorded'); } }, ...leaflet };
  const recovered = run('auto', '59', '18', broken, file);
  assert.equal(recovered.el.dataset.activeEngine, 'leaflet');
  assert(!JSON.stringify(recovered.window.SVV_MAP_DIAGNOSTICS).includes('private URL'));
  for (const engine of ['auto', 'openlayers', 'leaflet']) {
    const { el } = run(engine, '59', '18', {}, file);
    assert.equal(el.dataset.mapStatus, 'SVV_MAP_ENGINE_UNAVAILABLE');
    assert.equal(el.children[0].children.at(-1).href.includes('openstreetmap.org'), true);
  }
  for (const lat of ['', null, 'abc', '59junk', '91', 'Infinity', '0x10']) {
    const { el } = run('auto', lat, '18', ol, file);
    assert.equal(el.dataset.mapStatus, 'SVV_MAP_INVALID_COORDS', String(lat));
    assert.equal(el.children[0].children.length, 2, 'invalid coordinates have no coordinate text or link');
  }
  assert.equal(run('auto', '59', '181', ol, file).el.dataset.mapStatus, 'SVV_MAP_INVALID_COORDS');
}
console.log('Map runtime checks passed (source and minified).');

async function checkReporter() {
  const el = new Element({ 'data-engine': 'auto' });
  el.dataset = { mapStatus: 'pending', activeEngine: 'static' };
  const listeners = {};
  const calls = [];
  let tick;
  const fetch = (url, options) => { calls.push({ url, options }); return Promise.resolve({ ok: true }); };
  const document = {
    readyState: 'complete', querySelectorAll: () => [el],
    addEventListener: (name, fn) => { listeners[name] = fn; }
  };
  vm.runInNewContext(fs.readFileSync('assets/map-status.js', 'utf8'), {
    window: { SVV_MAP_STATUS: { url: '/wp-admin/admin-ajax.php', nonce: 'valid' }, fetch, innerHeight: 800 },
    document, fetch, URLSearchParams, Promise, setInterval: (fn) => { tick = fn; }
  });
  assert.equal(calls.length, 0, 'pending map is not reported immediately');
  tick(); tick();
  await new Promise(setImmediate);
  assert.equal(calls.length, 1, 'blocked runtime produces one bounded observation');
  assert.equal(calls[0].options.body.get('code'), 'SVV_MAP_NOT_STARTED');
  assert.equal(calls[0].options.referrerPolicy, 'no-referrer');
  assert.deepEqual([...calls[0].options.body.keys()], ['action', 'nonce', 'code', 'engine', 'requested']);
  el.dataset = { mapStatus: 'SVV_MAP_STARTED', activeEngine: 'leaflet' };
  listeners['svv-map-status']();
  await new Promise(setImmediate);
  assert.equal(calls.length, 2, 'a late startup is still reported');
  assert.equal(calls[1].options.body.get('engine'), 'leaflet');
  tick();
  await new Promise(setImmediate);
  assert.equal(calls.length, 2, 'duplicate status is not sent again');
  console.log('Independent map reporter checks passed.');
}
checkReporter().catch((error) => { console.error(error); process.exitCode = 1; });
