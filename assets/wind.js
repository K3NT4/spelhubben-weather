/* assets/wind.js
 * Small helper to rotate wind direction arrows when inline styles
 * are stripped by WP KSES (wp_kses_post). Looks for elements with
 * class 'svv-wind-dir' and a 'data-deg' attribute containing the
 * wind degree (0..360). Rotates arrow to point TO the bearing.
 */
(function(){
  function applyWindRotations(root){
    var els = (root || document).querySelectorAll && (root || document).querySelectorAll('.svv-wind-dir[data-deg]');
    if (!els || !els.length) return;
    els.forEach(function(el){
      var v = el.getAttribute('data-deg');
      if (!v) return;
      var deg = parseFloat(v);
      if (isNaN(deg)) return;
      // Rotate so arrow points TO the bearing: deg + 90 (mirror of many glyphs)
      var rot = (deg + 90) % 360;
      el.style.transform = 'rotate(' + rot + 'deg)';
      // store computed rotation as data for debugging
      el.setAttribute('data-rot', String(rot));
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){ applyWindRotations(document); });
  } else {
    applyWindRotations(document);
  }

  // Observe future additions (e.g., dynamic content)
  if (window.MutationObserver) {
    var mo = new MutationObserver(function(records){
      records.forEach(function(r){
        if (r.addedNodes && r.addedNodes.length) applyWindRotations(r.target || document);
      });
    });
    mo.observe(document.documentElement || document.body, { childList: true, subtree: true });
  }
})();
