// Independent of map.js and its engine dependencies; loaded for administrators only.
(function () {
  'use strict';
  if (!window.SVV_MAP_STATUS || !window.fetch) return;
  var sent = Object.create(null);
  var count = 0;
  var queue = Promise.resolve();

  function report(el, code) {
    var engine = el.dataset.activeEngine || 'static';
    var requested = el.getAttribute('data-engine') || 'auto';
    var key = code + ':' + engine + ':' + requested;
    if (sent[key] || count >= 12) return;
    sent[key] = true;
    count++;
    var body = new URLSearchParams({
      action: 'svv_map_status', nonce: window.SVV_MAP_STATUS.nonce,
      code: code, engine: engine, requested: requested
    });
    queue = queue.then(function () { return fetch(window.SVV_MAP_STATUS.url, {
      method: 'POST', credentials: 'same-origin', referrerPolicy: 'no-referrer', body: body
    }); }).catch(function () { /* Diagnostics must never interfere with the map. */ });
  }

  function scan(checkPending) {
    document.querySelectorAll('.svv-map').forEach(function (el) {
      var code = el.dataset.mapStatus;
      if (code && code !== 'pending') report(el, code);
      else if (checkPending) {
        var rect = el.getBoundingClientRect();
        if (rect.width && rect.height && rect.top < window.innerHeight && rect.bottom > 0) {
          report(el, 'SVV_MAP_NOT_STARTED');
        }
      }
    });
  }

  document.addEventListener('svv-map-status', function () { scan(false); });
  function boot() {
    scan(false);
    // A delayed script may still start later; this is an observation, not a blocker diagnosis.
    setInterval(function () { scan(true); }, 15000);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
