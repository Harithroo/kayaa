/* Kayaa Home, the category rail: seven hangers hang side-on and turn to face you on hover, keyboard focus or the first tap. Ported from
   design/home-v2-concept.html (angles, scales, spring and damping values, durations, delays and staggers unchanged).
   - Every hanger is a real link to its category page (server-written, tools/sync-shell.mjs "home-rail"); this script draws it and moves it.
   - Each hanger has a hook-back layer BEHIND the rod and a front layer in front, sharing one transform about the hook centre.
   - Physics (a damped pendulum, springs for the turn, the slide and the scale) runs in one frame loop that sleeps when everything is at rest,
     while the stage is off-screen and while the tab is hidden.
   - A hanger with data-photo (html/assets/img/rail/{slug}.webp) shows that cut-out instead of the drawing (docs/rail-photo-guide.md).
   - Reduced motion: no doors, no drop, no swing, no slide; the active hanger simply faces front at 1.12. */
(function () {
  'use strict';
  var H = window.KayaaHome, G = window.KayaaGarments;
  if (!H || !G) return;
  var $ = H.$, $$ = H.$$, RM = H.RM, COARSE = H.COARSE, clamp = H.clamp, COL = G.COL;
  var stage = $('#railStage'), scroller = $('#railScroller'), inner = $('#railInner'), back = $('#railBack'), front = $('#railFront'), rod = $('#railRod');
  var cap = $('#railCaption'), capBody = $('#capBody'), prev = $('#railPrev'), next = $('#railNext'), bar = $('#railBar'), doors = $('#doors'), replay = $('#replay');
  if (!stage || !front) return;

  var hangers = $$('.hanger', front);
  var N = hangers.length, S = [], mode = 'fit', itemW = 120, itemH = 197, hookX = 60, hookY = 10, railY = 70, spacing = 130, slots = [], minGap = 70, captionTop = 300;
  var SIDE = 64, FACE_S = 1.36, active = -1, rov = 0, doorsOpen = false, closeUntil = 0;
  if (COARSE) $('#railSub').textContent = 'Swipe along the rail. Tap a hanger to turn it around, tap again to open the category.';

  hangers.forEach(function (a, i) {
    var d = a.dataset, item = { type: d.type, c: COL[d.c] || COL.white, c2: d.c2 ? COL[d.c2] : undefined };
    var photo = !!d.photo;
    var b = document.createElement('div'); b.className = 'hook-back';
    b.innerHTML = '<span class="turn">' + (photo ? '' : G.hookBackSVG()) + '</span>'; back.appendChild(b);
    a.tabIndex = i === 0 ? 0 : -1;
    var turn = $('.turn', a);
    turn.innerHTML = photo ? '<img class="rail-photo" src="' + d.photo + '" alt="" width="140" height="230" decoding="async">' : G.railSVG(item);
    var side = SIDE + (i % 3 - 1) * 4;
    S.push({ el: a, bk: b, tf: turn, tb: b.firstChild, x: 0, vx: 0, tx: 0, y: 0, vy: 0, s: 1, ts: 1, ry: side, vr: 0, tr: side, side: side, p: { a: 0, va: 0, G: 2400, L: 95, c: 3, max: 0.42 }, delay: 0, drop: false, name: d.name, count: +d.count, range: d.range, meta: d.meta, href: a.getAttribute('href') });
  });

  /* The stage height is also set in CSS (home.css, from the stage width) so nothing shifts when this script runs; the numbers here are the same formula. */
  function layout() {
    var W = stage.clientWidth, fit = W >= 860; mode = fit ? 'fit' : 'scroll'; stage.classList.toggle('scroll', !fit);
    itemW = fit ? Math.min(172, Math.max(118, (W * 0.84 / N) * 1.2)) : (W < 400 ? 126 : 136);
    var k = itemW / 140; itemH = 230 * k; hookX = 70 * k; hookY = 12 * k; railY = fit ? 74 : 76;
    if (fit) { var a = W * 0.08, b = W * 0.92; spacing = (b - a) / N; slots = S.map(function (_, i) { return a + (i + 0.5) * spacing; }); inner.style.width = W + 'px'; rod.style.left = (W * 0.05) + 'px'; rod.style.width = (W * 0.9) + 'px'; }
    else { spacing = itemW * 0.95; var padL = Math.max(itemW * 0.75, 24 + itemW / 2); slots = S.map(function (_, i) { return padL + i * spacing; }); inner.style.width = (slots[N - 1] + padL) + 'px'; rod.style.left = (slots[0] - spacing * 0.7) + 'px'; rod.style.width = (slots[N - 1] - slots[0] + spacing * 1.4) + 'px'; }
    minGap = spacing * 0.45; rod.style.top = (railY - 4) + 'px';
    captionTop = Math.round(railY + (188 - 12) * k * FACE_S + 16);
    stage.style.setProperty('--stage-h', (captionTop + 108) + 'px');
    $('#railFloor').style.top = (captionTop - 26) + 'px';
    S.forEach(function (s, i) {
      [s.el, s.bk].forEach(function (e) { e.style.width = itemW + 'px'; e.style.height = itemH + 'px'; e.style.left = (slots[i] - itemW / 2) + 'px'; e.style.top = (railY - hookY) + 'px'; e.style.transformOrigin = hookX + 'px ' + hookY + 'px'; e.style.perspective = (itemW * 5) + 'px'; e.style.perspectiveOrigin = hookX + 'px ' + (itemH * 0.4) + 'px'; });
      s.tf.style.transformOrigin = s.tb.style.transformOrigin = hookX + 'px 0px';
    });
    cap.style.top = captionTop + 'px'; render(); placeCaption(); updateArrows();
  }

  /* caption: the name in spaced capitals, "{count} styles · {range}" and a "Shop {name}" pill; the hint when nothing is active */
  var shown = null;
  function plural(n) { return n + (n === 1 ? ' style' : ' styles'); }
  function capHTML(i) {
    if (i < 0) return '<span class="cap-hint"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>' + (mode === 'scroll' || COARSE ? 'Swipe the rail, tap a hanger' : 'Hover a hanger to turn it around') + '</span>';
    var s = S[i];
    /* Laravel: the server writes the meta line (data-meta), so an empty category reads "Coming soon" and a category without sizes drops the range */
    return '<p class="cap-name">' + s.name + '</p><p class="cap-meta">' + (s.meta || plural(s.count) + ' · ' + s.range) + '</p><a class="btn btn--primary btn--sm" href="' + s.href + '">Shop ' + s.name + ' <span aria-hidden="true">→</span></a>';
  }
  function setCaption(i) {
    if (shown === i) return; shown = i;
    if (RM) { capBody.innerHTML = capHTML(i); return; }
    cap.classList.add('swap'); clearTimeout(setCaption.t);
    setCaption.t = setTimeout(function () { capBody.innerHTML = capHTML(i); cap.classList.remove('swap'); }, 150);
  }
  function placeCaption() {
    var cw = cap.offsetWidth || 280, x;
    if (active < 0) x = (stage.clientWidth - cw) / 2;
    else { var sl = mode === 'scroll' ? scroller.scrollLeft : 0; x = clamp(slots[active] + S[active].x - sl - cw / 2, 12, stage.clientWidth - cw - 12); }
    cap.style.transform = 'translateX(' + x.toFixed(1) + 'px)';
  }

  function setActive(i) {
    if (i === active) return; var was = active; active = i;
    S.forEach(function (s, j) {
      s.ts = j === i ? (RM ? 1.12 : FACE_S) : 1;
      s.tr = j === i ? 0 : s.side;
      var d = i < 0 ? 0 : j - i; s.tx = (i >= 0 && d !== 0) ? (Math.abs(d) === 1 ? 14 : Math.abs(d) === 2 ? 6 : 0) * Math.sign(d) : 0;
      s.el.classList.toggle('is-active', j === i);
    });
    if (i >= 0 && !RM) S[i].p.va += (was < 0 || was < i ? 1 : -1) * 0.9;
    setCaption(i); kick();
  }

  function render() {
    for (var i = 0; i < N; i++) {
      var s = S[i], t = 'translate(' + s.x.toFixed(2) + 'px,' + s.y.toFixed(2) + 'px) rotate(' + s.p.a.toFixed(4) + 'rad) scale(' + s.s.toFixed(4) + ')';
      s.el.style.transform = t; s.bk.style.transform = t;
      var r = 'rotateY(' + s.ry.toFixed(2) + 'deg)'; s.tf.style.transform = r; s.tb.style.transform = r;
      var light = (0.86 + 0.14 * Math.cos(s.ry * Math.PI / 180)).toFixed(3); s.tf.style.filter = 'brightness(' + light + ')';
    }
    if (active >= 0) placeCaption();
  }

  /* physics */
  var running = false, last = 0, visible = true, drag = null;
  function frame(t) {
    if (!doorsOpen && !drag && performance.now() > closeUntil) { running = false; return; }
    var dt = Math.min(0.034, (t - last) / 1000 || 0.016); last = t;
    var steps = 3, h = dt / steps, energy = 0, i, j;
    for (var k = 0; k < steps; k++) {
      for (i = 0; i < N; i++) {
        var s = S[i], ax;
        if (drag && drag.i === i) { var nx = drag.x; ax = ((nx - s.x) / h - s.vx) / h; s.vx = (nx - s.x) / h; s.x = nx; }
        else { ax = 200 * (s.tx - s.x) - 24 * s.vx; s.vx += ax * h; s.x += s.vx * h; }
        var ar = 160 * (s.tr - s.ry) - 13 * s.vr; s.vr += ar * h; s.ry += s.vr * h;
        s.s += (s.ts - s.s) * (1 - Math.exp(-h * 13));
        if (s.delay > 0) { s.delay -= h; continue; }
        if (s.y < 0 || s.vy !== 0) {
          s.vy += 3200 * h; s.y += s.vy * h;
          if (s.y >= 0) { s.y = 0; if (s.vy > 260) { if (s.drop) { s.p.va += (i % 2 ? -1 : 1) * (1.5 + Math.random()); s.vr += (Math.random() - 0.5) * 300; } s.vy = -s.vy * 0.2; } else { s.vy = 0; s.drop = false; } }
        }
        H.stepPendulum(s.p, h, s.y < 0 ? 0 : clamp(ax, -6000, 6000));
      }
      if (drag) {
        var d = drag.i;
        for (j = d + 1; j < N; j++) { var pj = slots[j] + S[j].x, pp = slots[j - 1] + S[j - 1].x; if (pj - pp < minGap) { var n2 = pp + minGap - slots[j]; S[j].vx = (n2 - S[j].x) / h * 0.5; S[j].x = n2; } }
        for (j = d - 1; j >= 0; j--) { var pj2 = slots[j] + S[j].x, pn = slots[j + 1] + S[j + 1].x; if (pn - pj2 < minGap) { var n3 = pn - minGap - slots[j]; S[j].vx = (n3 - S[j].x) / h * 0.5; S[j].x = n3; } }
      }
    }
    for (i = 0; i < N; i++) { var q = S[i]; energy += Math.abs(q.p.a) + Math.abs(q.p.va) * 0.3 + Math.abs(q.x - q.tx) * 0.05 + Math.abs(q.vx) * 0.01 + Math.abs(q.s - q.ts) * 4 + Math.abs(q.y) * 0.1 + Math.abs(q.vy) * 0.01 + Math.abs(q.ry - q.tr) * 0.02 + Math.abs(q.vr) * 0.002 + (q.delay > 0 ? 1 : 0); }
    render();
    if ((energy > 0.004 || drag) && visible && !document.hidden) requestAnimationFrame(frame); else running = false;   // the loop sleeps when everything is at rest
  }
  function kick() {
    if (RM) { S.forEach(function (s) { s.s = s.ts; s.x = s.tx; s.ry = s.tr; s.y = 0; s.vy = 0; s.delay = 0; }); render(); return; }
    if (!running && visible) { running = true; last = performance.now(); requestAnimationFrame(frame); }
  }
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (es) { visible = es[0].isIntersecting; if (visible) kick(); }, { threshold: 0 }).observe(stage);
    new IntersectionObserver(function (es) { if (es[0].isIntersecting && !doorsOpen) setTimeout(openDoors, 250); }, { threshold: 0.55 }).observe(stage);
  }
  document.addEventListener('visibilitychange', function () { if (!document.hidden) kick(); });

  /* wardrobe doors: hangers wait above, doors swing open, hangers drop onto the rail */
  function liftAll() { S.forEach(function (s, i) { s.y = -(railY + itemH + 40); s.vy = 0; s.delay = 0; s.drop = true; s.p.a = (i % 2 ? 0.22 : -0.22); s.p.va = 0; }); }
  function openDoors() {
    if (doorsOpen) return; doorsOpen = true; doors.classList.remove('gone');
    requestAnimationFrame(function () { doors.classList.add('open'); });
    S.forEach(function (s, i) { s.delay = 0.35 + i * 0.09; }); kick();
    setTimeout(function () { if (doorsOpen) doors.classList.add('gone'); }, 1400);
    if (replay) replay.lastChild.textContent = 'Close the doors';
  }
  function closeDoors() {
    doorsOpen = false; closeUntil = performance.now() + 1300; setActive(-1); doors.classList.remove('gone');
    requestAnimationFrame(function () { doors.classList.remove('open'); });
    setTimeout(function () { if (!doorsOpen) { liftAll(); render(); } }, 1250);
    if (replay) replay.lastChild.textContent = 'Open the doors';
  }
  if (RM || !('IntersectionObserver' in window)) { doorsOpen = true; doors.classList.add('open', 'gone'); }
  else { liftAll(); if (replay) replay.lastChild.textContent = 'Open the doors'; }
  $('#doorBtn').addEventListener('click', openDoors);
  $$('.door', doors).forEach(function (d) { d.addEventListener('click', openDoors); });
  if (replay) { replay.addEventListener('click', function () { if (RM) return; if (doorsOpen) closeDoors(); else openDoors(); }); if (RM) replay.hidden = true; }

  /* pointer: brush, hover, drag, tap */
  var lastPX = null, lastPT = 0;
  stage.addEventListener('pointermove', function (e) {
    if (e.pointerType !== 'mouse' && e.pointerType !== 'pen') return;
    var now = performance.now();
    if (lastPX !== null && !drag && !RM && doorsOpen) {
      var vx = (e.clientX - lastPX) / Math.max(8, now - lastPT) * 1000, sr = stage.getBoundingClientRect(), sl = mode === 'scroll' ? scroller.scrollLeft : 0, px = e.clientX - sr.left + sl, py = e.clientY - sr.top;
      for (var i = 0; i < N; i++) { var cx = slots[i] + S[i].x; if (Math.abs(px - cx) < itemW * 0.34 * S[i].s && py > railY && py < railY + itemH * 0.8 * S[i].s) { S[i].p.va = clamp(S[i].p.va - vx * 0.0012, -3.4, 3.4); if (i !== active) S[i].vr = clamp(S[i].vr + vx * 0.05, -420, 420); } }
      kick();
    }
    if (drag) { var dx = e.clientX - drag.sx; if (Math.abs(dx) > 5) drag.moved = true; var lim = spacing * 1.6; drag.x = clamp(drag.ox + dx, -lim, lim); }
    lastPX = e.clientX; lastPT = now;
  });
  stage.addEventListener('pointerleave', function () { lastPX = null; if (!drag && !(document.activeElement && front.contains(document.activeElement))) setActive(-1); });
  front.addEventListener('pointerover', function (e) { if (e.pointerType !== 'mouse' && e.pointerType !== 'pen') return; var a = e.target.closest('.hanger'); if (!a || drag) return; setActive(+a.dataset.i); });
  front.addEventListener('pointerdown', function (e) {
    var a = e.target.closest('.hanger'); if (!a || (e.pointerType !== 'mouse' && e.pointerType !== 'pen')) return;
    var i = +a.dataset.i; drag = { i: i, sx: e.clientX, ox: S[i].x, x: S[i].x, moved: false, el: a };
    try { a.setPointerCapture(e.pointerId); } catch (_) { /* capture is a nicety */ }
    stage.classList.add('dragging'); kick();
  });
  function endDrag() { if (!drag) return; var d = drag; drag = null; stage.classList.remove('dragging'); S[d.i].vx *= 0.4; if (d.moved) { d.el.dataset.dragged = '1'; setTimeout(function () { delete d.el.dataset.dragged; }, 0); } kick(); }
  front.addEventListener('pointerup', endDrag); front.addEventListener('pointercancel', endDrag); front.addEventListener('lostpointercapture', endDrag);
  /* The hangers are real links. A drag is not a click; on touch the first tap turns and centres the hanger and the second tap follows the link;
     a click without a pointer (Enter on the focused link) always follows it. */
  front.addEventListener('click', function (e) {
    var a = e.target.closest('.hanger'); if (!a) return;
    if (a.dataset.dragged) { e.preventDefault(); return; }
    var i = +a.dataset.i;
    if (e.detail !== 0 && (COARSE || e.pointerType === 'touch') && active !== i) { e.preventDefault(); setActive(i); centre(i); }
  });

  /* keyboard: one tab stop (roving tabindex); arrows, Home and End move; Enter opens the link */
  front.addEventListener('keydown', function (e) {
    var a = e.target.closest('.hanger'); if (!a) return; var i = +a.dataset.i, n = i;
    if (e.key === 'ArrowRight' || e.key === 'ArrowDown') n = Math.min(N - 1, i + 1); else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') n = Math.max(0, i - 1);
    else if (e.key === 'Home') n = 0; else if (e.key === 'End') n = N - 1; else return;
    e.preventDefault(); S[rov].el.tabIndex = -1; rov = n; S[n].el.tabIndex = 0; S[n].el.focus({ preventScroll: true });
  });
  /* Focus caused by a tap must not count as the first tap: only keyboard focus (:focus-visible) turns the hanger. */
  front.addEventListener('focusin', function (e) {
    var a = e.target.closest('.hanger'); if (!a) return; var i = +a.dataset.i; S[rov].el.tabIndex = -1; rov = i; a.tabIndex = 0;
    var kb = true; try { kb = a.matches(':focus-visible'); } catch (_) { /* old engines: treat as keyboard */ }
    if (!kb) return;
    if (!doorsOpen) openDoors(); setActive(i); centre(i);
  });
  front.addEventListener('focusout', function (e) { if (!front.contains(e.relatedTarget) && !cap.contains(e.relatedTarget)) setActive(-1); });

  /* scrolling mode (phones and narrow stages) */
  function centre(i) { if (mode !== 'scroll') return; scroller.scrollTo({ left: slots[i] - scroller.clientWidth / 2, behavior: RM ? 'auto' : 'smooth' }); }
  var lastSL = 0, lastV = 0, lastST = 0, endT;
  scroller.addEventListener('scroll', function () {
    var now = performance.now(), dt = Math.max(8, now - lastST) / 1000, sl = scroller.scrollLeft, v = (sl - lastSL) / dt, dv = v - lastV;
    if (!RM && Math.abs(dv) > 20) { var imp = clamp(dv / 95 * 0.045, -1.6, 1.6); S.forEach(function (s, i) { s.p.va += imp; if (i !== active) s.vr = clamp(s.vr - dv * 0.06, -420, 420); }); kick(); }
    lastSL = sl; lastV = v; lastST = now; if (active >= 0) placeCaption(); updateArrows();
    clearTimeout(endT); endT = setTimeout(function () { lastV = 0; }, 140);
  }, { passive: true });
  function updateArrows() {
    if (mode !== 'scroll') return; var max = scroller.scrollWidth - scroller.clientWidth;
    prev.disabled = scroller.scrollLeft < 4; next.disabled = scroller.scrollLeft > max - 4;
    var f = scroller.clientWidth / scroller.scrollWidth; bar.style.width = (f * 100) + '%'; bar.style.transform = 'translateX(' + ((scroller.scrollLeft / scroller.scrollWidth) * (100 / f)) + '%)';
  }
  prev.addEventListener('click', function () { scroller.scrollBy({ left: -spacing * 2, behavior: RM ? 'auto' : 'smooth' }); });
  next.addEventListener('click', function () { scroller.scrollBy({ left: spacing * 2, behavior: RM ? 'auto' : 'smooth' }); });
  var rt; window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(layout, 80); });
  layout(); setCaption(-1);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(layout);
}());
