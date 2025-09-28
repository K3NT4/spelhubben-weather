// admin/admin.js
(function () {
  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    // Fallback: create a temporary textarea and use execCommand('copy')
    return new Promise(function (resolve, reject) {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'absolute';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();
      try {
        document.execCommand('copy');
        resolve();
      } catch (e) {
        reject(e);
      } finally {
        document.body.removeChild(ta);
      }
    });
  }

  function setBtnCopied(btn, copied) {
    var tCopy = (window.SVV_ADMIN_I18N && SVV_ADMIN_I18N.copy) || 'Copy';
    var tCopied = (window.SVV_ADMIN_I18N && SVV_ADMIN_I18N.copied) || 'Copied!';
    if (copied) {
      btn.classList.add('is-copied');
      btn.textContent = tCopied;
      setTimeout(function () {
        btn.classList.remove('is-copied');
        btn.textContent = tCopy;
      }, 1400);
    } else {
      btn.classList.remove('is-copied');
      btn.textContent = tCopy;
    }
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.svv-copy-btn');
    if (!btn) return;
    var text = btn.getAttribute('data-copy') || '';
    if (!text) return;
    copyText(text)
      .then(function () {
        setBtnCopied(btn, true);
      })
      .catch(function () {
        setBtnCopied(btn, false);
      });
  });
})();
