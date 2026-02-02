// assets/map.js
(function () {
  'use strict';

  // (Optional) Point Leaflet default icons to local files.
  // Harmless to keep even when we don't place markers.
  if (window.SVV && SVV.iconBase && window.L && L.Icon && L.Icon.Default) {
    L.Icon.Default.mergeOptions({
      iconUrl:       SVV.iconBase + 'marker-icon.png',
      iconRetinaUrl: SVV.iconBase + 'marker-icon-2x.png',
      shadowUrl:     SVV.iconBase + 'marker-shadow.png'
    });
  }

  // Helpers
  function debounce(fn, ms){
    let timeoutId = null;
    return function(...args) {
      if (timeoutId) clearTimeout(timeoutId);
      timeoutId = setTimeout(() => fn.apply(null, args), ms);
    };
  }

  // Compute a scale factor based on actual card width
  function computeScale(w){
    const minW = 160, maxW = 520;
    const scale = (w - minW) / (maxW - minW);
    return Math.max(0.8, Math.min(1.3, scale));
  }

  // --- Initialize maps WITHOUT a marker ---
  function initMap(el){
    if (el.dataset.inited) return;

    // Retry guard (prevents infinite console spam if Leaflet never loads)
    const tries = parseInt(el.dataset.svvLeafletTries || '0', 10);
    if (tries > 20) { // ~10s total with 500ms interval
      console.error('Leaflet failed to load after multiple retries. Giving up for element:', el);
      el.dataset.svvLeafletFailed = '1';
      el.dataset.inited = '1'; // lock
      return;
    }

    el.dataset.inited = '1';

    // Check if Leaflet is loaded
    if (typeof L === 'undefined' || !L.map) {
      console.warn('Leaflet not loaded yet, retrying...', el);
      setTimeout(() => {
        el.dataset.svvLeafletTries = String(tries + 1);
        delete el.dataset.inited;
        initMap(el);
      }, 500);
      return;
    }

    const lat  = parseFloat(el.getAttribute('data-lat'));
    const lon  = parseFloat(el.getAttribute('data-lon'));
    if (isNaN(lat) || isNaN(lon)) {
      console.warn('Missing or invalid lat/lon for map', el);
      return;
    }

    // Ensure element has a computed height before initializing
    const computedHeight = window.getComputedStyle(el).height;
    if (!computedHeight || computedHeight === '0px' || computedHeight === 'auto') {
      console.warn('Map element has no height, retrying...', el);
      setTimeout(() => {
        delete el.dataset.inited;
        initMap(el);
      }, 500);
      return;
    }

    try {
      const map = L.map(el, { scrollWheelZoom:false, attributionControl:false });
      el._svvMap = map;

      map.setView([lat, lon], 12);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom:19 }).addTo(map);

      setTimeout(()=>map.invalidateSize(), 200);
    } catch (e) {
      console.error('Error initializing map:', e);
      delete el.dataset.inited;
    }
  }

  // Lazy-initialize maps when they enter the viewport using IntersectionObserver.
  function scanMapsLazy(){
    const maps = Array.from(document.querySelectorAll('.svv-map'));
    if (!maps.length) return;

    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver(function(entries){
        entries.forEach(function(entry){
          if (entry.isIntersecting) {
            initMap(entry.target);
            io.unobserve(entry.target);
          }
        });
      }, { root: null, rootMargin: '200px', threshold: 0.01 });

      maps.forEach(function(m){
        if (m.dataset.inited) return;
        io.observe(m);
      });
      return;
    }

    maps.forEach(initMap);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scanMapsLazy);
  } else { scanMapsLazy(); }

  new MutationObserver(scanMapsLazy).observe(document.documentElement, { childList:true, subtree:true });

  // --- Responsive scaling for the card ---
  const attachRO = debounce(function(){
    if (!('ResizeObserver' in window)) return;

    document.querySelectorAll('.sv-vader[data-svv-ro="1"], .spelhubben-weather[data-svv-ro="1"]').forEach(function(card){
      if (card._svvObserved) return;
      card._svvObserved = true;

      const applyScale = ()=>{
        const w = (card.getBoundingClientRect().width || card.clientWidth || 0);
        if (!w) return;
        card.style.setProperty('--svv-scale', computeScale(w).toFixed(3));

        if (card._svvLastW && Math.abs(w - card._svvLastW) > 2) {
          const m = card.querySelector('.svv-map');
          if (m && m._svvMap) m._svvMap.invalidateSize();
        }
        card._svvLastW = w;
      };

      applyScale();
      const ro = new ResizeObserver(debounce(applyScale, 60));
      ro.observe(card);

      card._svvResizeObserver = ro;
    });
  }, 50);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachRO);
  } else { attachRO(); }

  const mutationObserver = new MutationObserver(function(mutations){
    mutations.forEach(function(m){
      if (m.removedNodes.length) {
        m.removedNodes.forEach(function(node){
          if (node.nodeType === 1) {
            const cards = node.querySelectorAll ? node.querySelectorAll('.sv-vader[data-svv-ro="1"], .spelhubben-weather[data-svv-ro="1"]') : [];
            cards.forEach(function(card){
              if (card._svvResizeObserver) {
                card._svvResizeObserver.disconnect();
                delete card._svvResizeObserver;
              }
              if (card._svvMap) {
                card._svvMap.remove();
                delete card._svvMap;
              }
              delete card._svvObserved;
            });
          }
        });
      }
    });
    attachRO();
  });

  mutationObserver.observe(document.documentElement, { childList:true, subtree: true });
})();
