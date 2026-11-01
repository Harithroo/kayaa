/* Content pages (production code: stays after the Blade conversion).
   - "On this page" table of contents: a <details> block under the intro below 1100px, a sticky sidebar from 1100px
     (where it is always open and the summary does not toggle). Markup ships open so it works without JS.
   - Print: every accordion is opened before printing and restored afterwards. */
(function () {
  'use strict';

  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var wide = window.matchMedia ? window.matchMedia('(min-width: 1100px)') : null;

  $$('[data-toc]').forEach(function (toc) {
    var summary = toc.querySelector('summary');
    function sync() {
      if (wide && wide.matches) toc.open = true;
      else if (!toc.hasAttribute('data-touched')) toc.open = false;
    }
    if (summary) {
      summary.addEventListener('click', function (e) {
        if (wide && wide.matches) { e.preventDefault(); return; }   // always open on desktop
        toc.setAttribute('data-touched', '');
      });
    }
    if (wide && wide.addEventListener) wide.addEventListener('change', sync);
    sync();
  });

  /* Delivery page: fill the estimate cells from the same { min, max } data as the checkout ETA, worded by Kayaa.formatEta.
     data-eta-placeholder on the JSON block keeps the placeholder brackets until the real data arrives. */
  (function () {
    var cells = $$('[data-eta]');
    var src = document.getElementById('district-eta');
    if (!cells.length || !src || !window.Kayaa) return;
    var data = {};
    try { data = JSON.parse(src.textContent); } catch (e) { return; }
    var bracket = src.hasAttribute('data-eta-placeholder');
    cells.forEach(function (cell) {
      var d = data[cell.getAttribute('data-eta')];
      if (!d) return;
      var text = window.Kayaa.formatEta(d.min, d.max);
      cell.textContent = bracket ? '[' + text + ']' : text;
    });
  })();

  var reopen = [];
  window.addEventListener('beforeprint', function () {
    reopen = $$('main details:not([open])');
    reopen.forEach(function (d) { d.open = true; });
  });
  window.addEventListener('afterprint', function () {
    reopen.forEach(function (d) { d.open = false; });
    reopen = [];
  });
})();
