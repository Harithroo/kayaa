/* Thank-you behaviour (production code: stays after the Blade conversion).
   Copy button for the order number, and "Refresh status" for the pending state. */
(function () {
  'use strict';

  var page = document.querySelector('[data-thanks-page]');
  if (!page) return;

  var copyBtn = page.querySelector('[data-copy-ref]');
  var refEl = page.querySelector('[data-order-ref]');
  var status = page.querySelector('[data-copy-status]');

  function announce(msg) {
    status.textContent = '';
    setTimeout(function () { status.textContent = msg; }, 30);   // re-trigger the live region
  }

  if (copyBtn && refEl) {
    copyBtn.addEventListener('click', function () {
      var text = refEl.textContent.trim();
      var done = function (ok) {
        var label = copyBtn.querySelector('[data-copy-label]');
        if (label) label.textContent = ok ? 'Copied' : 'Copy failed';
        announce(ok ? 'Order number copied' : 'Could not copy. Select the order number and copy it manually.');
        setTimeout(function () { if (label) label.textContent = 'Copy'; }, 2000);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function () { done(true); }, function () { done(false); });
      } else {
        var range = document.createRange();
        range.selectNodeContents(refEl);
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        sel.removeAllRanges();
        done(ok);
      }
    });
  }

  var refresh = page.querySelector('[data-refresh-status]');
  if (refresh) refresh.addEventListener('click', function () { location.reload(); });
})();
