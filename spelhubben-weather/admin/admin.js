(function () {
  // ===== Utilities =====
  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    return new Promise(function (resolve, reject) {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'absolute';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy'); resolve(); }
      catch (e) { reject(e); }
      finally { document.body.removeChild(ta); }
    });
  }

  function t(key, fallback) {
    return (window.SVV_ADMIN_I18N && SVV_ADMIN_I18N[key]) || fallback;
  }

  function setBtnCopied(btn, ok) {
    var tCopy   = t('copy', 'Copy');
    var tCopied = t('copied', 'Copied!');
    if (ok) {
      btn.classList.add('is-copied');
      btn.textContent = tCopied;
      setTimeout(function(){ btn.classList.remove('is-copied'); btn.textContent = tCopy; }, 1400);
    } else {
      btn.classList.remove('is-copied');
      btn.textContent = tCopy;
    }
  }

  function debounce(fn, ms){ var t; return function(){ clearTimeout(t); var a=arguments; t=setTimeout(function(){ fn.apply(null,a); }, ms); }; }

  // ===== Copy (single) with cleanup =====
  (function(){
    var handler = function (e) {
      var btn = e.target.closest('.svv-copy-btn');
      if (!btn) return;
      var text = btn.getAttribute('data-copy') || '';
      if (!text) return;
      copyText(text).then(function(){ setBtnCopied(btn, true); }).catch(function(){ setBtnCopied(btn, false); });
    };
    document.addEventListener('click', handler);
    window.addEventListener('beforeunload', function(){
      document.removeEventListener('click', handler);
    });
  })();

  // ===== Live filter with cleanup =====
  (function(){
    var handler = function(e){
      var el = e.target.closest('.svv-sc-search');
      if (!el) return;
      var q = (el.value || '').toLowerCase();
      document.querySelectorAll('.svv-codeblock').forEach(function(b){
        var txt = ((b.getAttribute('data-label')||'') + ' ' + (b.getAttribute('data-code')||'')).toLowerCase();
        var match = !q || txt.indexOf(q) >= 0;
        b.style.display = match ? '' : 'none';
        b.setAttribute('data-svv-visible', match ? '1' : '0');
      });
    };
    document.addEventListener('input', handler);
    window.addEventListener('beforeunload', function(){
      document.removeEventListener('input', handler);
    });
  })();

  // ===== Copy all visible with cleanup =====
  (function(){
    var handler = function (e) {
      var btn = e.target.closest('.svv-copy-batch');
      if (!btn) return;
      var sel = btn.getAttribute('data-batch-selector');
      if (!sel) return;
      var lines = Array.prototype.map.call(document.querySelectorAll(sel), function(n){
        return (n.textContent || '').trim();
      }).filter(Boolean);
      var text = lines.join('\n\n');
      if (!text) return;
      copyText(text).then(function(){ setBtnCopied(btn, true); }).catch(function(){ setBtnCopied(btn, false); });
    };
    document.addEventListener('click', handler);
    window.addEventListener('beforeunload', function(){
      document.removeEventListener('click', handler);
    });
  })();

  // ===== Preview textarea + actions with cleanup =====
  (function(){
    var preview = document.querySelector('.svv-sc-preview');
    if (!preview) return;

    // Klick på kod → fyll preview
    var codeClickHandler = function (e) {
      var code = e.target.closest('.svv-pre code');
      if (!code || !preview) return;
      var r = document.createRange(); r.selectNodeContents(code);
      var s = window.getSelection(); s.removeAllRanges(); s.addRange(r);
      preview.value = code.textContent.trim(); preview.focus();
      preview.dispatchEvent(new Event('input', { bubbles:true })); // trigga live-preview
    };
    document.addEventListener('click', codeClickHandler);

    var prevCopy   = document.querySelector('.svv-preview-copy');
    var prevClear  = document.querySelector('.svv-preview-clear');
    var prevToggle = document.querySelector('.svv-preview-toggle');

    if (prevCopy && preview) {
      var copyHandler = function(){
        if (!preview.value) return;
        copyText(preview.value).then(function(){ setBtnCopied(prevCopy, true); }).catch(function(){ setBtnCopied(prevCopy, false); });
      };
      prevCopy.addEventListener('click', copyHandler);
    }
    if (prevClear && preview) {
      var clearHandler = function(){
        preview.value=''; preview.focus();
        preview.dispatchEvent(new Event('input', { bubbles:true }));
      };
      prevClear.addEventListener('click', clearHandler);
    }
    if (prevToggle && preview) {
      var toggleHandler = function(){
        preview.classList.toggle('is-expanded');
        var isExp = preview.classList.contains('is-expanded');
        prevToggle.innerHTML = (isExp
          ? '<span class="dashicons dashicons-editor-contract"></span> ' + t('collapse','Collapse')
          : '<span class="dashicons dashicons-editor-expand"></span> ' + t('expand','Expand'));
      };
      prevToggle.addEventListener('click', toggleHandler);
    }

    // Cleanup handlers
    window.addEventListener('beforeunload', function(){
      document.removeEventListener('click', codeClickHandler);
      if (prevCopy) prevCopy.removeEventListener('click', copyHandler);
      if (prevClear) prevClear.removeEventListener('click', clearHandler);
      if (prevToggle) prevToggle.removeEventListener('click', toggleHandler);
    });
  })();

  // ===== Live shortcode preview (iframe) with optimized event handling =====
  (function(){
    var ta = document.querySelector('.svv-sc-preview');
    var box = document.querySelector('.svv-live-preview');
    var frame = document.querySelector('.svv-live-frame');
    var statusEl = document.querySelector('.svv-live-status');
    if (!ta || !box || !frame) return;

    var ajaxUrl  = t('ajax_url',  '');
    var nonce    = t('ajax_nonce','');
    var assets   = (window.SVV_ADMIN_I18N && SVV_ADMIN_I18N.assets) || { css:[], js:[], svv:{} };
    var inputHandler, clickHandler; // Store refs for cleanup

    function setStatus(txt){ if(statusEl){ statusEl.textContent = txt || ''; } }

    function renderToFrame(html){
      var head = '<meta charset="utf-8">';
      // CSS (front + leaflet etc.)
      (assets.css || []).forEach(function(href){
        head += '<link rel="stylesheet" href="'+ href +'">';
      });
      // SVV-global innan map.js körs
      var bootSVV = '<script>window.SVV='+ JSON.stringify(assets.svv || {}) +';<\/script>';
      // JS (leaflet, widget, map)
      var scripts = (assets.js || []).map(function(src){
        return '<script src="'+ src +'"><\/script>';
      }).join('');
      var doc = '<!doctype html><html><head>'+ head +'</head><body>'+ html + bootSVV + scripts +'</body></html>';
      frame.srcdoc = doc;
    }

    // Optimized debounce with longer timeout to reduce AJAX calls
    var run = debounce(function(){
      var val = (ta.value || '').trim();
      if (!val || val.indexOf('[') === -1) {
        box.hidden = true; setStatus('');
        return;
      }
      box.hidden = false; setStatus(t('rendering','Rendering…'));

      var fd = new FormData();
      fd.append('action','svv_preview_shortcode');
      fd.append('nonce', nonce);
      fd.append('sc', val);

      fetch(ajaxUrl, { method:'POST', credentials:'same-origin', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(data){
          if (!data || !data.success) throw new Error((data && data.data && data.data.message) || 'error');
          renderToFrame(data.data.html || '');
          setStatus(t('ok','OK'));
        })
        .catch(function(){
          renderToFrame('<div style="padding:12px;color:#b91c1c;font-family:system-ui">'+ t('previewErr','Preview failed') +'</div>');
          setStatus(t('failed','Failed'));
        });
    }, 600); // Increased from 400ms to 600ms to reduce excessive AJAX calls

    // Bind event listeners with reference for cleanup
    inputHandler = function(){ run(); };
    ta.addEventListener('input', inputHandler);
    
    clickHandler = function(e){
      if (e.target.closest('.svv-pre code')) run();
    };
    document.addEventListener('click', clickHandler);

    if (ta.value.trim()) run();

    // Cleanup on page unload to prevent memory leaks
    var cleanup = function(){
      ta.removeEventListener('input', inputHandler);
      document.removeEventListener('click', clickHandler);
    };
    window.addEventListener('beforeunload', cleanup);
  })();

})();
