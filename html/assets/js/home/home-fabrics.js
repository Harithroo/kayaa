/* Kayaa Home: the fabric swatch. A cloth on three pegs (verlet grid, 17 x 18 or 13 x 14 when narrow) drawn on a canvas with affine-mapped texture
   triangles, blurred light and shade and a drop shadow. Ported from design/home-v2-concept.html (constants unchanged).
   The tabs and cards are written into the page from docs/content/fabrics.md (gen: home-fabric-tabs / home-fabric-panels): data-key, data-name,
   data-col, data-stiff and data-flutter on each tab. Canvas rules: DPR capped at 2, touch-action: pan-y (CSS), role="img" with a label.
   The loop runs only while the swatch is on screen and the tab is visible. Reduced motion: a settled static drape, no loop; a grab re-settles it.
   Needs window.KayaaGarments (garments.js) and window.KayaaHome (home-core.js). */
(function () {
  'use strict';
  var K = window.KayaaHome, G = window.KayaaGarments;
  if (!K || !G) return;
  var $ = K.$, $$ = K.$$, RM = K.RM;
  var wrap = $('#clothWrap'), cv = $('#cloth'), hint = $('#clothHint'), label = $('#clothLabel'), tabsEl = $('#fabTabs'), swap = $('#fabSwap');
  if (!wrap || !cv || !tabsEl || !cv.getContext) return;
  var ctx = cv.getContext('2d');
  var tabs = $$('.fab-tab', tabsEl);
  if (!tabs.length) return;
  var FAB = tabs.map(function (t) { return { key: t.dataset.key, name: t.dataset.name, col: t.dataset.col, stiff: parseFloat(t.dataset.stiff) || 1, flutter: parseFloat(t.dataset.flutter) || 1 }; });
  var panels = FAB.map(function (f) { return document.getElementById('fab-panel-' + f.key); });

  /* ---------- cloth model ---------- */
  var cur = 0, dpr = 1, W = 0, H = 0, COLS = 17, ROWS = 18, P = [], C = [], pins = [], tex = null, grabbed = -1, gx = 0, gy = 0, time = 0;
  var mouse = { x: -999, y: -999, vx: 0, vy: 0, in: false };
  var shadeCv = document.createElement('canvas'), sx = shadeCv.getContext('2d');

  function build() {
    var r = wrap.getBoundingClientRect(); W = r.width; H = r.height;
    if (!W || !H) return false;
    dpr = Math.min(2, window.devicePixelRatio || 1);
    cv.width = Math.round(W * dpr); cv.height = Math.round(H * dpr); shadeCv.width = cv.width; shadeCv.height = cv.height;
    var small = W < 520; COLS = small ? 13 : 17; ROWS = small ? 14 : 18;
    var cw = Math.min(W * 0.72, 420), ch = Math.min(H * 0.66, cw * 1.08), rest = cw / (COLS - 1), restY = ch / (ROWS - 1);
    var ox = (W - cw) / 2, oy = Math.max(70, H * 0.15), i, j;
    P = []; C = [];
    for (j = 0; j < ROWS; j++) for (i = 0; i < COLS; i++) { var x = ox + i * rest, y = oy + j * restY; P.push({ x: x, y: y, z: 0, px: x, py: y, pz: 0, pin: false, u: i / (COLS - 1), v: j / (ROWS - 1) }); }
    pins = [1, Math.floor((COLS - 1) / 2), COLS - 2]; pins.forEach(function (k) { P[k].pin = true; });
    function link(a, b, k) { var A = P[a], B = P[b]; C.push({ a: a, b: b, len: Math.hypot(A.x - B.x, A.y - B.y), k: k }); }
    for (j = 0; j < ROWS; j++) for (i = 0; i < COLS; i++) {
      var id = j * COLS + i;
      if (i < COLS - 1) link(id, id + 1, 1);
      if (j < ROWS - 1) link(id, id + COLS, 1);
      if (i < COLS - 1 && j < ROWS - 1) { link(id, id + COLS + 1, 0.5); link(id + 1, id + COLS, 0.5); }
      if (i < COLS - 2) link(id, id + 2, 0.15);
      if (j < ROWS - 2) link(id, id + COLS * 2, 0.15);
    }
    grabbed = -1;
    settle(160);
    return true;
  }
  function setFabric(i) { var f = FAB[i]; tex = G.clothTexture(f.key, f.col, 512); label.textContent = f.name; cv.setAttribute('aria-label', 'A ' + f.name.toLowerCase() + ' swatch hanging from pegs, moving in a breeze'); }
  function settle(n) { for (var k = 0; k < n; k++) sim(1 / 60, 0, true); }

  /* structural, shear and bend links; iterations scale with the fabric's stiffness; gravity and a breeze that varies by fabric */
  function sim(dt, t, quiet) {
    var f = FAB[cur], g = 900, damp = 0.986, i, p, windZ, windX;
    for (i = 0; i < P.length; i++) {
      p = P[i]; if (p.pin) continue;
      var vx = (p.x - p.px) * damp, vy = (p.y - p.py) * damp, vz = (p.z - p.pz) * damp;
      p.px = p.x; p.py = p.y; p.pz = p.z;
      windZ = quiet ? 0 : (Math.sin(t * 1.3 + p.u * 3 + p.v * 2) + Math.sin(t * 2.7 + p.v * 5) * 0.55) * 260 * f.flutter * p.v;
      windX = quiet ? 0 : Math.sin(t * 0.7 + p.v * 2) * 60 * f.flutter * p.v;
      p.x += vx + windX * dt * dt; p.y += vy + g * dt * dt; p.z += vz + windZ * dt * dt;
    }
    var it = Math.round(4 * f.stiff);
    for (var k = 0; k < it; k++) {
      for (i = 0; i < C.length; i++) {
        var c = C[i], A = P[c.a], B = P[c.b], dx = B.x - A.x, dy = B.y - A.y, dz = B.z - A.z, d = Math.sqrt(dx * dx + dy * dy + dz * dz) || 1;
        var diff = (d - c.len) / d * 0.5 * c.k * (c.k < 1 ? f.stiff : 1);
        if (c.k < 1 && d < c.len) diff *= 0.2;
        var ox = dx * diff, oy = dy * diff, oz = dz * diff;
        if (!A.pin) { A.x += ox; A.y += oy; A.z += oz; }
        if (!B.pin) { B.x -= ox; B.y -= oy; B.z -= oz; }
      }
      if (grabbed >= 0) { var Gp = P[grabbed]; Gp.x = gx; Gp.y = gy; Gp.z = -30; }
    }
    /* brushing the cloth with the pointer nudges it */
    if (!quiet && mouse.in && grabbed < 0) {
      var sp = Math.min(1, (Math.abs(mouse.vx) + Math.abs(mouse.vy)) / 400);
      for (i = 0; i < P.length; i++) {
        p = P[i]; if (p.pin) continue;
        var ddx = p.x - mouse.x, ddy = p.y - mouse.y, dd = ddx * ddx + ddy * ddy;
        if (dd < 3600) { var w = 1 - Math.sqrt(dd) / 60; p.x += mouse.vx * dt * w * 0.35; p.y += mouse.vy * dt * w * 0.35; p.z += w * 2.5 * sp; }
      }
    }
  }

  /* ---------- drawing ---------- */
  /* one textured triangle: an affine map from texture space to the screen, grown 0.8px to hide seams; the source rect is limited to the triangle (+2px) */
  function tri(x0, y0, x1, y1, x2, y2, u0, v0, u1, v1, u2, v2) {
    var cx = (x0 + x1 + x2) / 3, cy = (y0 + y1 + y2) / 3, e = 0.8;
    function ex(x, y) { var dx = x - cx, dy = y - cy, l = Math.hypot(dx, dy) || 1; return [x + dx / l * e, y + dy / l * e]; }
    var a = ex(x0, y0), b = ex(x1, y1), c = ex(x2, y2);
    var d = u0 * (v1 - v2) + u1 * (v2 - v0) + u2 * (v0 - v1); if (!d) return;
    ctx.save(); ctx.beginPath(); ctx.moveTo(a[0], a[1]); ctx.lineTo(b[0], b[1]); ctx.lineTo(c[0], c[1]); ctx.closePath(); ctx.clip();
    var m11 = (x0 * (v1 - v2) + x1 * (v2 - v0) + x2 * (v0 - v1)) / d, m12 = (y0 * (v1 - v2) + y1 * (v2 - v0) + y2 * (v0 - v1)) / d,
        m21 = (x0 * (u2 - u1) + x1 * (u0 - u2) + x2 * (u1 - u0)) / d, m22 = (y0 * (u2 - u1) + y1 * (u0 - u2) + y2 * (u1 - u0)) / d,
        dx = (x0 * (u1 * v2 - u2 * v1) + x1 * (u2 * v0 - u0 * v2) + x2 * (u0 * v1 - u1 * v0)) / d,
        dy = (y0 * (u1 * v2 - u2 * v1) + y1 * (u2 * v0 - u0 * v2) + y2 * (u0 * v1 - u1 * v0)) / d;
    ctx.transform(m11, m12, m21, m22, dx, dy);
    var su = Math.max(0, Math.min(u0, u1, u2) - 2), sv = Math.max(0, Math.min(v0, v1, v2) - 2), eu = Math.min(512, Math.max(u0, u1, u2) + 2), ev = Math.min(512, Math.max(v0, v1, v2) + 2);
    ctx.drawImage(tex, su, sv, eu - su, ev - sv, su, sv, eu - su, ev - sv);
    ctx.restore();
  }
  function outline(c) {
    var i, j, p;
    for (i = 0; i < COLS; i++) { p = P[i]; i ? c.lineTo(p.x, p.y) : c.moveTo(p.x, p.y); }
    for (j = 1; j < ROWS; j++) { p = P[j * COLS + COLS - 1]; c.lineTo(p.x, p.y); }
    for (i = COLS - 2; i >= 0; i--) { p = P[(ROWS - 1) * COLS + i]; c.lineTo(p.x, p.y); }
    for (j = ROWS - 2; j > 0; j--) { p = P[j * COLS]; c.lineTo(p.x, p.y); }
    c.closePath();
  }
  var L = [-0.38, -0.55, 0.74]; (function () { var l = Math.hypot(L[0], L[1], L[2]); L = L.map(function (v) { return v / l; }); }());

  function draw() {
    if (!P.length) return;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0); ctx.clearRect(0, 0, W, H);
    var a = P[pins[0]], m = P[pins[1]], b = P[pins[2]], i, j;
    ctx.strokeStyle = '#9D8FB5'; ctx.lineWidth = 1.6; ctx.beginPath(); ctx.moveTo(-4, a.y - 6); ctx.quadraticCurveTo(W / 2, m.y + 6, W + 4, b.y - 6); ctx.stroke();   // the line
    ctx.save(); ctx.beginPath(); outline(ctx); ctx.shadowColor = 'rgba(110,90,140,.28)'; ctx.shadowBlur = 34; ctx.shadowOffsetX = 10; ctx.shadowOffsetY = 22; ctx.fillStyle = '#F3EDF8'; ctx.fill(); ctx.restore();   // soft shadow
    sx.setTransform(1, 0, 0, 1, 0, 0); sx.clearRect(0, 0, shadeCv.width, shadeCv.height); sx.setTransform(dpr, 0, 0, dpr, 0, 0);   // shading goes to its own layer first
    for (j = 0; j < ROWS - 1; j++) for (i = 0; i < COLS - 1; i++) {
      var A = P[j * COLS + i], B = P[j * COLS + i + 1], Cc = P[(j + 1) * COLS + i], Dd = P[(j + 1) * COLS + i + 1];
      tri(A.x, A.y, B.x, B.y, Cc.x, Cc.y, A.u * 512, A.v * 512, B.u * 512, B.v * 512, Cc.u * 512, Cc.v * 512);
      tri(B.x, B.y, Dd.x, Dd.y, Cc.x, Cc.y, B.u * 512, B.v * 512, Dd.u * 512, Dd.v * 512, Cc.u * 512, Cc.v * 512);
      /* light and shade from the cloth's normal */
      var e1x = B.x - A.x, e1y = B.y - A.y, e1z = B.z - A.z, e2x = Cc.x - A.x, e2y = Cc.y - A.y, e2z = Cc.z - A.z;
      var nx = e1y * e2z - e1z * e2y, ny = e1z * e2x - e1x * e2z, nz = e1x * e2y - e1y * e2x, nl = Math.hypot(nx, ny, nz) || 1;
      var sh = (nx * L[0] + ny * L[1] + nz * L[2]) / nl, base = 0.74, al;
      sx.beginPath(); sx.moveTo(A.x, A.y); sx.lineTo(B.x, B.y); sx.lineTo(Dd.x, Dd.y); sx.lineTo(Cc.x, Cc.y); sx.closePath();
      if (sh > base) { al = Math.min(0.35, (sh - base) * 1.6); sx.fillStyle = 'rgba(255,255,255,' + al.toFixed(3) + ')'; }
      else { al = Math.min(0.42, (base - sh) * 0.8); sx.fillStyle = 'rgba(61,53,71,' + al.toFixed(3) + ')'; }
      sx.fill();
    }
    ctx.save(); ctx.beginPath(); outline(ctx); ctx.clip(); ctx.setTransform(1, 0, 0, 1, 0, 0);
    try { ctx.filter = 'blur(' + (5 * dpr) + 'px)'; } catch (e) { /* no canvas filters: the shading stays sharp */ }
    ctx.drawImage(shadeCv, 0, 0); ctx.filter = 'none'; ctx.restore();
    pins.forEach(function (k) {   // the pegs
      var q = P[k]; ctx.save(); ctx.translate(q.x, q.y); ctx.fillStyle = '#FFFFFF'; ctx.strokeStyle = 'rgba(110,90,140,.6)'; ctx.lineWidth = 1.3;
      ctx.beginPath(); if (ctx.roundRect) ctx.roundRect(-5, -16, 10, 26, 3); else ctx.rect(-5, -16, 10, 26); ctx.fill(); ctx.stroke();
      ctx.beginPath(); ctx.moveTo(-3, -3); ctx.lineTo(3, -3); ctx.stroke(); ctx.restore();
    });
  }

  /* ---------- the loop: only while the swatch is on screen and the tab is visible ---------- */
  var inView = false, running = false, last = 0;
  function frame(t) {
    if (!inView || document.hidden || RM) { running = false; return; }
    var t0 = window.KAYAA_CLOTH_STATS ? performance.now() : 0;
    var dt = Math.min(0.033, (t - last) / 1000 || 0.016); last = t; time += dt;
    sim(1 / 60, time, false); if (dt > 0.02) sim(1 / 60, time, false);
    mouse.vx *= 0.85; mouse.vy *= 0.85;
    draw();
    if (t0) (window.KAYAA_CLOTH_STATS.push(performance.now() - t0));   // review aid: window.KAYAA_CLOTH_STATS = [] makes the loop record its cost per frame
    requestAnimationFrame(frame);
  }
  function start() { if (running || RM || !inView || document.hidden) return; running = true; last = performance.now(); requestAnimationFrame(frame); }
  K.visibleWatcher(wrap, function (v) { inView = v; if (v) start(); });
  document.addEventListener('visibilitychange', start);

  /* the swatch textures, the cloth settling and the first draw cost about 0.2s of script on a phone, so they wait until the section is near the screen
     (the box has a fixed CSS height, so nothing moves when they run) */
  var ready = false;
  function init() {
    if (ready) return; ready = true;
    tabs.forEach(function (t, i) { var sw = $('i', t); if (sw) sw.style.backgroundImage = 'url(' + G.clothTexture(FAB[i].key, FAB[i].col, 96).toDataURL() + ')'; });
    setFabric(cur);
    if (build()) draw();
    start();
  }
  if ('IntersectionObserver' in window) {
    var near = new IntersectionObserver(function (es) { if (es[0].isIntersecting) { near.disconnect(); init(); } }, { rootMargin: '600px 0px' });
    near.observe(wrap);
  } else init();
  var rt;
  window.addEventListener('resize', function () { if (!ready) return; clearTimeout(rt); rt = setTimeout(function () { if (build()) draw(); }, 120); });

  /* ---------- pointer: brush, press and drag to grab the nearest point ---------- */
  function pos(e) { var r = cv.getBoundingClientRect(); return { x: e.clientX - r.left, y: e.clientY - r.top }; }
  function settleWithGrab() { settle(40); draw(); }
  cv.addEventListener('pointermove', function (e) {
    var q = pos(e);
    mouse.vx = (q.x - mouse.x) * 30; mouse.vy = (q.y - mouse.y) * 30;
    if (Math.abs(mouse.vx) > 3000) mouse.vx = 0;
    if (Math.abs(mouse.vy) > 3000) mouse.vy = 0;
    mouse.x = q.x; mouse.y = q.y; mouse.in = true;
    if (grabbed >= 0) { gx = q.x; gy = q.y; if (RM) settleWithGrab(); }
  });
  cv.addEventListener('pointerleave', function () { mouse.in = false; });
  cv.addEventListener('pointerdown', function (e) {
    var q = pos(e), best = -1, bd = 40 * 40, i;
    for (i = 0; i < P.length; i++) { if (P[i].pin) continue; var d = (P[i].x - q.x) * (P[i].x - q.x) + (P[i].y - q.y) * (P[i].y - q.y); if (d < bd) { bd = d; best = i; } }
    if (best < 0) return;
    grabbed = best; gx = q.x; gy = q.y;
    try { cv.setPointerCapture(e.pointerId); } catch (err) { /* capture not available */ }
    if (hint) hint.classList.add('hide');
    if (RM) settleWithGrab(); else start();
  });
  function release() { if (grabbed < 0) return; grabbed = -1; if (RM) { settle(200); draw(); } }
  cv.addEventListener('pointerup', release); cv.addEventListener('pointercancel', release); cv.addEventListener('lostpointercapture', release);

  /* ---------- tabs: texture, colour and motion change with a small ripple; the card crossfades ---------- */
  function showPanel(i) { panels.forEach(function (p, k) { if (p) p.hidden = k !== i; }); }
  function select(i, focus) {
    init();
    if (i === cur) return;
    cur = i;
    tabs.forEach(function (b, k) { b.setAttribute('aria-selected', String(k === i)); b.tabIndex = k === i ? 0 : -1; });
    if (focus) tabs[i].focus();
    if (RM || !swap) showPanel(i);
    else { swap.classList.add('out'); setTimeout(function () { showPanel(i); swap.classList.remove('out'); }, 200); }
    setFabric(i);
    if (!RM) { P.forEach(function (p) { if (!p.pin) p.pz -= (1 + p.v * 3); }); } else settle(120);
    draw();
  }
  tabs.forEach(function (b, i) { b.addEventListener('click', function () { select(i); }); });
  tabsEl.addEventListener('keydown', function (e) {
    var n = tabs.length, k = e.key;
    if (k === 'ArrowRight') { e.preventDefault(); select((cur + 1) % n, true); }
    else if (k === 'ArrowLeft') { e.preventDefault(); select((cur + n - 1) % n, true); }
    else if (k === 'Home') { e.preventDefault(); select(0, true); }
    else if (k === 'End') { e.preventDefault(); select(n - 1, true); }
  });
}());
