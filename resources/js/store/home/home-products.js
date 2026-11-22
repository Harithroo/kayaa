/* Kayaa Home: New this week / Bestsellers. The two lists are the real product cards (components.css .product-card, quick-add sheet in app.js),
   written into the page by tools/sync-shell.mjs; this script only adds the tab list with its spring pill, a staggered entrance and a pointer tilt.
   Ported from design/home-v2-concept.html: pill spring .4s, entrance .6s with 70ms stagger, tilt up to 8deg (fine pointers only).
   The entrance class is removed on animationend so the card's hover lift and tilt work again. Reduced motion: no entrance, no tilt. */
(function () {
  'use strict';
  var K = window.KayaaHome;
  if (!K) return;
  var $ = K.$, $$ = K.$$, RM = K.RM;
  var tablist = $('#prodTabs'), pill = $('#tabPill'), title = $('#prod-title');
  var tabs = $$('.tab', tablist || document);
  if (!tablist || !tabs.length) return;
  var panels = tabs.map(function (t) { return document.getElementById(t.getAttribute('aria-controls')); });
  var section = $('#products');

  /* ---------- the staggered entrance ---------- */
  var gridShown = !!RM || !('IntersectionObserver' in window);
  function enter(panel, paused) {
    if (RM || !panel) return;
    $$('.product-card', panel).forEach(function (c, i) {
      c.style.setProperty('--i', i);
      c.classList.remove('enter'); void c.offsetWidth;   // restart the animation after a tab switch
      c.style.animationPlayState = paused ? 'paused' : 'running';
      c.classList.add('enter');
    });
  }
  document.addEventListener('animationend', function (e) {
    if (e.animationName === 'cardin' && e.target.classList) { e.target.classList.remove('enter'); e.target.style.animationPlayState = ''; }
  });
  if (!RM) {
    enter(panels[0], !gridShown);
    if (!gridShown) {
      var io = new IntersectionObserver(function (es) {
        if (es[0].isIntersecting) { gridShown = true; $$('.product-card.enter', section).forEach(function (c) { c.style.animationPlayState = 'running'; }); io.disconnect(); }
      }, { threshold: 0.15 });
      io.observe(panels[0]);
    }
  }

  /* ---------- the pointer tilt on fine pointers (the card's own hover lift stays) ---------- */
  if (!RM && !K.COARSE) {
    section.addEventListener('pointermove', function (e) {
      var c = e.target.closest ? e.target.closest('.product-card') : null; if (!c || c.classList.contains('product-card--oos')) return;
      var r = c.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - 0.5, y = (e.clientY - r.top) / r.height - 0.5;
      c.style.setProperty('--ry', (x * 8).toFixed(2) + 'deg'); c.style.setProperty('--rx', (-y * 8).toFixed(2) + 'deg');
    });
    section.addEventListener('pointerout', function (e) {
      var c = e.target.closest ? e.target.closest('.product-card') : null;
      if (c && !c.contains(e.relatedTarget)) { c.style.setProperty('--ry', '0deg'); c.style.setProperty('--rx', '0deg'); }
    });
  }

  /* ---------- the tabs: a pill that slides with a spring; arrow keys, Home and End ---------- */
  function movePill(t) { pill.style.width = t.offsetWidth + 'px'; pill.style.transform = 'translateX(' + (t.offsetLeft - 4) + 'px)'; }
  function selected() { return tabs.filter(function (t) { return t.getAttribute('aria-selected') === 'true'; })[0] || tabs[0]; }
  function select(t, focus) {
    var i = tabs.indexOf(t), changed = t !== selected();
    tabs.forEach(function (x) { var on = x === t; x.setAttribute('aria-selected', String(on)); x.tabIndex = on ? 0 : -1; });
    if (focus) t.focus();
    movePill(t);
    if (!changed) return;
    panels.forEach(function (p, k) { if (p) p.hidden = k !== i; });
    if (title && t.dataset.title) title.textContent = t.dataset.title;
    enter(panels[i], false);
  }
  tabs.forEach(function (t, i) {
    t.addEventListener('click', function () { select(t); });
    t.addEventListener('keydown', function (e) {
      var n = tabs.length, k = e.key;
      if (k === 'ArrowRight') { e.preventDefault(); select(tabs[(i + 1) % n], true); }
      else if (k === 'ArrowLeft') { e.preventDefault(); select(tabs[(i + n - 1) % n], true); }
      else if (k === 'Home') { e.preventDefault(); select(tabs[0], true); }
      else if (k === 'End') { e.preventDefault(); select(tabs[n - 1], true); }
    });
  });
  movePill(tabs[0]);
  window.addEventListener('resize', function () { movePill(selected()); });
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { movePill(selected()); });
}());
