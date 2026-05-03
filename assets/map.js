// assets/map.js
(function () {
  'use strict';

  function debounce(fn, ms) {
    var timeoutId = null;
    return function () {
      var args = arguments;
      if (timeoutId) clearTimeout(timeoutId);
      timeoutId = setTimeout(function () { fn.apply(null, args); }, ms);
    };
  }

  function recordMapEvent(el, code, message) {
    window.SVV_MAP_DIAGNOSTICS = window.SVV_MAP_DIAGNOSTICS || [];
    window.SVV_MAP_DIAGNOSTICS.push({
      code: code,
      message: message,
      name: el.getAttribute('data-name') || '',
      lat: el.getAttribute('data-lat') || '',
      lon: el.getAttribute('data-lon') || '',
      time: new Date().toISOString()
    });
    if (window.console && console.warn) {
      console.warn(code + ': ' + message, el);
    }
  }

  function osmUrl(lat, lon) {
    return 'https://www.openstreetmap.org/?mlat=' + encodeURIComponent(lat) +
      '&mlon=' + encodeURIComponent(lon) + '#map=12/' +
      encodeURIComponent(lat) + '/' + encodeURIComponent(lon);
  }

  function showFallback(el, code, message) {
    var lat = el.getAttribute('data-lat') || '';
    var lon = el.getAttribute('data-lon') || '';
    var name = el.getAttribute('data-name') || '';

    el.dataset.svvMapFailed = '1';
    el.dataset.inited = '1';
    el.innerHTML = '';

    var box = document.createElement('div');
    box.className = 'svv-map-fallback';
    box.setAttribute('role', 'status');

    var title = document.createElement('strong');
    title.textContent = name || 'Map location';
    box.appendChild(title);

    var text = document.createElement('span');
    text.textContent = message;
    box.appendChild(text);

    if (lat && lon) {
      var coords = document.createElement('small');
      coords.textContent = lat + ', ' + lon;
      box.appendChild(coords);

      var link = document.createElement('a');
      link.href = osmUrl(lat, lon);
      link.target = '_blank';
      link.rel = 'noopener noreferrer';
      link.textContent = 'OpenStreetMap';
      box.appendChild(link);
    }

    el.appendChild(box);
    recordMapEvent(el, code, message);
  }

  function hasUsableHeight(el) {
    var computedHeight = window.getComputedStyle(el).height;
    return computedHeight && computedHeight !== '0px' && computedHeight !== 'auto';
  }

  function initOpenLayers(el, lat, lon) {
    if (!window.SVVOpenLayers || typeof window.SVVOpenLayers.createMap !== 'function') {
      return false;
    }

    var map = window.SVVOpenLayers.createMap(el, { lat: lat, lon: lon, zoom: 12 });
    el._svvMap = map;
    el._svvMapEngine = 'openlayers';
    return true;
  }

  function initLeaflet(el, lat, lon) {
    if (typeof window.L === 'undefined' || !window.L.map) {
      return false;
    }

    if (window.SVV && window.SVV.iconBase && window.L.Icon && window.L.Icon.Default) {
      window.L.Icon.Default.mergeOptions({
        iconUrl: window.SVV.iconBase + 'marker-icon.png',
        iconRetinaUrl: window.SVV.iconBase + 'marker-icon-2x.png',
        shadowUrl: window.SVV.iconBase + 'marker-shadow.png'
      });
    }

    var map = window.L.map(el, { scrollWheelZoom: false, attributionControl: false });
    el._svvMap = map;
    el._svvMapEngine = 'leaflet';
    map.setView([lat, lon], 12);
    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
    setTimeout(function () { map.invalidateSize(); }, 200);
    return true;
  }

  function initMap(el) {
    if (el.dataset.inited) return;
    el.dataset.inited = '1';

    var lat = parseFloat(el.getAttribute('data-lat'));
    var lon = parseFloat(el.getAttribute('data-lon'));
    if (isNaN(lat) || isNaN(lon)) {
      showFallback(el, 'SVV_MAP_INVALID_COORDS', 'Map coordinates are missing or invalid.');
      return;
    }

    if (!hasUsableHeight(el)) {
      delete el.dataset.inited;
      setTimeout(function () { initMap(el); }, 300);
      return;
    }

    var requested = (el.getAttribute('data-engine') || (window.SVV && window.SVV.mapEngine) || 'auto').toLowerCase();
    if (['auto', 'openlayers', 'leaflet', 'static'].indexOf(requested) === -1) {
      requested = 'auto';
    }

    if (requested === 'static') {
      showFallback(el, 'SVV_MAP_STATIC_MODE', 'Interactive map is disabled for this weather block.');
      return;
    }

    try {
      if ((requested === 'auto' || requested === 'openlayers') && initOpenLayers(el, lat, lon)) {
        return;
      }
    } catch (e) {
      recordMapEvent(el, 'SVV_MAP_OPENLAYERS_INIT_FAIL', e && e.message ? e.message : 'OpenLayers failed to initialize.');
      if (requested === 'openlayers') {
        showFallback(el, 'SVV_MAP_OPENLAYERS_INIT_FAIL', 'OpenLayers could not initialize on this page.');
        return;
      }
    }

    try {
      if ((requested === 'auto' || requested === 'leaflet') && initLeaflet(el, lat, lon)) {
        return;
      }
    } catch (e2) {
      recordMapEvent(el, 'SVV_MAP_LEAFLET_INIT_FAIL', e2 && e2.message ? e2.message : 'Leaflet failed to initialize.');
      if (requested === 'leaflet') {
        showFallback(el, 'SVV_MAP_LEAFLET_INIT_FAIL', 'Leaflet could not initialize on this page.');
        return;
      }
    }

    showFallback(el, 'SVV_MAP_ENGINE_UNAVAILABLE', 'No local interactive map engine was available.');
  }

  function scanMapsLazy(root) {
    var scope = root && root.querySelectorAll ? root : document;
    var maps = Array.prototype.slice.call(scope.querySelectorAll('.svv-map'));
    if (root && root.matches && root.matches('.svv-map')) maps.unshift(root);
    if (!maps.length) return;

    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            initMap(entry.target);
            io.unobserve(entry.target);
          }
        });
      }, { root: null, rootMargin: '200px', threshold: 0.01 });

      maps.forEach(function (m) {
        if (!m.dataset.inited) io.observe(m);
      });
      return;
    }

    maps.forEach(initMap);
  }

  function computeScale(w) {
    var minW = 160, maxW = 520;
    var scale = (w - minW) / (maxW - minW);
    return Math.max(0.8, Math.min(1.3, scale));
  }

  var attachRO = debounce(function () {
    if (!('ResizeObserver' in window)) return;

    document.querySelectorAll('.sv-vader[data-svv-ro="1"], .spelhubben-weather[data-svv-ro="1"]').forEach(function (card) {
      if (card._svvObserved) return;
      card._svvObserved = true;

      var applyScale = function () {
        var w = (card.getBoundingClientRect().width || card.clientWidth || 0);
        if (!w) return;
        card.style.setProperty('--svv-scale', computeScale(w).toFixed(3));

        if (card._svvLastW && Math.abs(w - card._svvLastW) > 2) {
          var m = card.querySelector('.svv-map');
          if (m && m._svvMap) {
            if (m._svvMapEngine === 'leaflet' && m._svvMap.invalidateSize) m._svvMap.invalidateSize();
            if (m._svvMapEngine === 'openlayers' && m._svvMap.updateSize) m._svvMap.updateSize();
          }
        }
        card._svvLastW = w;
      };

      applyScale();
      var ro = new ResizeObserver(debounce(applyScale, 60));
      ro.observe(card);
      card._svvResizeObserver = ro;
    });
  }, 50);

  function cleanupNode(node) {
    if (!node || node.nodeType !== 1) return;
    var cards = node.querySelectorAll ? node.querySelectorAll('.sv-vader[data-svv-ro="1"], .spelhubben-weather[data-svv-ro="1"]') : [];
    Array.prototype.forEach.call(cards, function (card) {
      if (card._svvResizeObserver) {
        card._svvResizeObserver.disconnect();
        delete card._svvResizeObserver;
      }
      var mapEl = card.querySelector('.svv-map');
      if (mapEl && mapEl._svvMap) {
        if (mapEl._svvMapEngine === 'leaflet' && mapEl._svvMap.remove) mapEl._svvMap.remove();
        if (mapEl._svvMapEngine === 'openlayers' && mapEl._svvMap.setTarget) mapEl._svvMap.setTarget(null);
        delete mapEl._svvMap;
      }
      delete card._svvObserved;
    });
  }

  function boot() {
    scanMapsLazy(document);
    attachRO();

    new MutationObserver(function (mutations) {
      var shouldAttach = false;
      mutations.forEach(function (mutation) {
        Array.prototype.forEach.call(mutation.addedNodes, function (node) {
          if (node.nodeType === 1) {
            scanMapsLazy(node);
            shouldAttach = true;
          }
        });
        Array.prototype.forEach.call(mutation.removedNodes, cleanupNode);
      });
      if (shouldAttach) attachRO();
    }).observe(document.documentElement, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
