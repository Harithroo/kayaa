/* Kayaa Home: shared helpers, the preloader, the word-rise headings and the scroll reveal. Classic script (no modules), loaded before
   home-hero.js, home-age.js and home-rail.js. Ported from design/home-v2-concept.html (tuning unchanged).
   Motion rules: html.rm (reduced motion) is decided in the head script of index.html; every animation has a finished state under it;
   only elements BELOW the fold at load may start hidden (nothing in the first viewport waits for an observer); loops pause off-screen. */
(function () {
  'use strict';
  var H = window.KayaaHome = window.KayaaHome || {};
  var RM = !!window.KAYAA_RM;
  var COARSE = !!(window.matchMedia && matchMedia('(hover: none), (pointer: coarse)').matches);
  function $(s, r) { return (r || document).querySelector(s); }
  function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
  function clamp(v, a, b) { return v < a ? a : v > b ? b : v; }

  /* damped pendulum: p = {a (angle), va (angular velocity), G, L, c (damping), max}; hookAccel is the hook's own acceleration, torque an extra push */
  function stepPendulum(p, dt, hookAccel, torque) {
    var aa = -(p.G / p.L) * Math.sin(p.a) - p.c * p.va - (hookAccel / p.L) * Math.cos(p.a) + (torque || 0);
    p.va += aa * dt; p.a += p.va * dt;
    if (p.a > p.max) { p.a = p.max; p.va *= -0.3; }
    if (p.a < -p.max) { p.a = -p.max; p.va *= -0.3; }
  }
  /* calls cb(true) while el is on screen, cb(false) when it is not (loops sleep off-screen) */
  function visibleWatcher(el, cb) {
    if (!('IntersectionObserver' in window)) { cb(true); return; }
    new IntersectionObserver(function (es) { cb(es[0].isIntersecting); }, { threshold: 0 }).observe(el);
  }
  H.RM = RM; H.COARSE = COARSE; H.$ = $; H.$$ = $$; H.clamp = clamp; H.stepPendulum = stepPendulum; H.visibleWatcher = visibleWatcher;

  var G = window.KayaaGarments, COL = G.COL;

  /* ---------- the preloader (first visit per session, never under reduced motion; the head script adds html.intro-on) ---------- */
  (function () {
    var root = document.documentElement, el = $('#intro');
    if (!el || !root.classList.contains('intro-on')) return;
    var gs = [['bodysuit', COL.peri, 150], ['sleepsuit', COL.lilac, 280], ['newborn', COL.white, 410]];
    $('#introGs').innerHTML = gs.map(function (g, i) {
      var y = 30 + (1 - Math.pow((g[2] - 280) / 270, 2)) * 20;
      return '<g transform="translate(' + (g[2] - 44) + ' ' + (y - 14) + ') scale(.62)"><g class="i-g g' + (i + 1) + '">' + G.garment(g[0], g[1]) + '</g></g>';
    }).join('');
    /* the overlay is hidden with a class once it has slid away; html.intro-on stays (the hero delays are offset by it) */
    function done() {
      if (el.classList.contains('out')) return;
      el.classList.add('out');
      try { sessionStorage.setItem('kayaa-intro', '1'); } catch (e) { /* storage blocked: the intro simply shows again next visit */ }
      setTimeout(function () { el.classList.add('gone'); }, 850);
    }
    el.addEventListener('click', done);
    setTimeout(done, 1550);
  }());

  /* ---------- section headings: words rise in turn ---------- */
  function splitWords(el) {
    var idx = 0;
    function walk(node) {
      Array.prototype.slice.call(node.childNodes).forEach(function (n) {
        if (n.nodeType === 3) {
          var parts = n.textContent.split(/(\s+)/), frag = document.createDocumentFragment();
          parts.forEach(function (p) {
            if (!p) return;
            if (/^\s+$/.test(p)) { frag.appendChild(document.createTextNode(p)); return; }
            var w = document.createElement('span'); w.className = 'w'; var s = document.createElement('span'); s.textContent = p; s.style.setProperty('--wi', idx++); w.appendChild(s); frag.appendChild(w);
          });
          n.parentNode.replaceChild(frag, n);
        } else if (n.nodeType === 1 && n.tagName.toLowerCase() !== 'svg') { walk(n); }
      });
    }
    walk(el);
  }
  $$('.split').forEach(splitWords);

  /* ---------- reveal: only things below the fold get a hidden start ---------- */
  (function () {
    if (RM || !('IntersectionObserver' in window)) return;
    var vh = window.innerHeight, targets = $$('[data-reveal],.split');
    var io = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.remove('pre'); io.unobserve(e.target); } }); }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    targets.forEach(function (el) {
      if (el.getBoundingClientRect().top > vh * 0.92) { el.classList.add('reveal', 'pre'); io.observe(el); }
      else if (!el.classList.contains('split')) el.classList.add('reveal');
    });
  }());
}());
