/* Kayaa Home hero: a washing line in a soft breeze; the pointer is a gust, the line has a light scroll parallax. Ported from
   design/home-v2-concept.html (tuning unchanged). Under reduced motion the garments are placed and stay still. The loop sleeps while the
   hero is off-screen and while the tab is hidden. */
(function () {
  'use strict';
  var H = window.KayaaHome, G = window.KayaaGarments;
  if (!H || !G) return;
  var $ = H.$, $$ = H.$$, RM = H.RM, clamp = H.clamp, COL = G.COL;
  var vis = $('#heroVisual'), arch = $('#arch'), path = $('#heroLinePath'), svg = $('#heroLine');
  if (!vis || !arch || !path || !svg) return;

  var items = [{ type: 'bodysuit', c: COL.peri, fx: 0.25 }, { type: 'sleepsuit', c: COL.lilac, fx: 0.52 }, { type: 'newborn', c: COL.white, fx: 0.79 }];
  items.forEach(function (it, i) {
    var d = document.createElement('div'); d.className = 'pegged'; d.setAttribute('aria-hidden', 'true');
    d.innerHTML = G.peggedSVG(it.type, it.c);
    vis.appendChild(d); it.el = d; it.p = { a: 0, va: 0, G: 2200, L: 110, c: 2.2, max: 0.45 }; it.ph = i * 1.7;
  });
  /* swatch dots in the "Feel the fabrics" tag */
  var FAB = [['muslin', '#FFFFFF'], ['jersey', '#DCCDED'], ['rib', '#C6D6EC']];
  $$('#heroSw i').forEach(function (el, i) { var f = FAB[i]; if (f) el.style.backgroundImage = 'url(' + G.clothTexture(f[0], f[1], 96).toDataURL() + ')'; });

  var W = 0, H2 = 0, ax0 = 0, ay0 = 0, par = 0;
  function lineY(t) { var y0 = H2 * 0.2, y1 = H2 * 0.24, sag = H2 * 0.1; return (1 - t) * (1 - t) * y0 + 2 * (1 - t) * t * (y0 + sag * 2) + t * t * y1; }
  function layout() {
    var r = arch.getBoundingClientRect(), vr = vis.getBoundingClientRect();
    W = r.width; H2 = r.height; ax0 = r.left - vr.left; ay0 = r.top - vr.top;
    svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H2);
    var y0 = H2 * 0.2, y1 = H2 * 0.24, sag = H2 * 0.1;
    path.setAttribute('d', 'M-4 ' + y0 + ' Q ' + (W / 2) + ' ' + (y0 + sag * 2) + ' ' + (W + 4) + ' ' + y1);
    items.forEach(function (it) {
      var w = it.el.offsetWidth, h = it.el.offsetHeight, peg = h * (10 / 170);
      it.el.style.left = (ax0 + W * it.fx - w / 2) + 'px'; it.el.style.top = (ay0 + lineY(it.fx) - peg) + 'px';
      it.el.style.transformOrigin = '50% ' + peg + 'px';
    });
  }
  layout(); window.addEventListener('resize', layout);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(layout);
  if (RM) return;

  var on = true, running = false, last = performance.now(), lastX = null, lastT = 0, gust = 0;
  function frame(t) {
    if (!on || document.hidden) { running = false; return; }
    var dt = Math.min(0.033, (t - last) / 1000 || 0.016); last = t; var time = t / 1000;
    gust *= Math.pow(0.25, dt);
    items.forEach(function (it) {
      var wind = (Math.sin(time * 0.9 + it.ph) * 1.1 + Math.sin(time * 2.3 + it.ph * 2) * 0.45) * 1.4 + gust;
      H.stepPendulum(it.p, dt / 2, 0, wind); H.stepPendulum(it.p, dt / 2, 0, wind);
      it.el.style.transform = 'translateY(' + (par * (0.5 + it.fx * 0.6)).toFixed(1) + 'px) rotate(' + it.p.a.toFixed(4) + 'rad)';
    });
    requestAnimationFrame(frame);
  }
  function wake() { if (running || !on || document.hidden) return; running = true; last = performance.now(); requestAnimationFrame(frame); }
  H.visibleWatcher(vis, function (v) { on = v; if (v) wake(); });
  document.addEventListener('visibilitychange', wake);   // the loop also sleeps while the tab is hidden
  wake();
  vis.addEventListener('pointermove', function (e) {
    var now = performance.now();
    if (lastX !== null) {
      var vx = (e.clientX - lastX) / Math.max(8, now - lastT) * 1000;
      items.forEach(function (it) { var r = it.el.getBoundingClientRect(); if (e.clientX > r.left - 10 && e.clientX < r.right + 10 && e.clientY > r.top && e.clientY < r.bottom) { it.p.va = clamp(it.p.va - vx * 0.0013, -3.2, 3.2); } });
      gust = clamp(gust - vx * 0.002, -6, 6);
    }
    lastX = e.clientX; lastT = now;
  });
  vis.addEventListener('pointerleave', function () { lastX = null; });
  window.addEventListener('scroll', function () { par = Math.min(window.scrollY, 700) * -0.12; }, { passive: true });
}());
