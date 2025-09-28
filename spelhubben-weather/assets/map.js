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
  function debounce(fn, ms){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn.apply(null,a), ms); }; }

  // Compute a scale factor based on actual card width
  function computeScale(w){
    const minW = 160, maxW = 520;
    const scale = (w - minW) / (maxW - minW);
    return Math.max(0.8, Math.min(1.3, scale));
  }

  // --- Initialize maps WITHOUT a marker ---
  function initMap(el){
    if (el.dataset.inited) return;
    el.dataset.inited = '1';

    const lat  = parseFloat(el.getAttribute('data-lat'));
    const lon  = parseFloat(el.getAttribute('data-lon'));
    if (isNaN(lat) || isNaN(lon)) return;

    const map = L.map(el, { scrollWheelZoom:false, attributionControl:false });
    el._svvMap = map;

    map.setView([lat, lon], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom:19 }).addTo(map);

    // NOTE: No L.marker here — the pin is never placed.
    setTimeout(()=>map.invalidateSize(), 200);
  }

  function scanMaps(){ document.querySelectorAll('.svv-map').forEach(initMap); }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scanMaps);
  } else { scanMaps(); }

  new MutationObserver(scanMaps).observe(document.documentElement, { childList:true, subtree:true });

  // --- Responsive scaling for the card ---
  const attachRO = debounce(function(){
    if (!('ResizeObserver' in window)) return;

    // Support both old (.sv-vader) and new (.spelhubben-weather) container classes
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
    });
  }, 50);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachRO);
  } else { attachRO(); }

  new MutationObserver(attachRO).observe(document.documentElement, { childList:true, subtree: true });
})();
