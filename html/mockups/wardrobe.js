/* MOCK-UP ONLY (html/mockups/): behaviour of the wardrobe category carousel. Vanilla JS, no dependencies, passive listeners, pointer events.
   Reads in one place and writes in requestAnimationFrame; nothing animates off screen. Delete with the mock. */
(function () {
  'use strict';
  var wd = document.querySelector('[data-wardrobe]');
  if (!wd) return;
  var track = wd.querySelector('[data-wardrobe-track]');
  var items = [].slice.call(wd.querySelectorAll('.wardrobe__item'));
  var links = items.map(function (li) { return li.querySelector('.wardrobe__link'); });
  var n = items.length;
  var prevBtn = document.querySelector('[data-wd-prev]');
  var nextBtn = document.querySelector('[data-wd-next]');
  var countEl = document.querySelector('[data-wd-count]');
  var dotsEl = document.querySelector('[data-wd-dots]');
  var mqReduce = window.matchMedia('(prefers-reduced-motion: reduce)');
  var mqFine = window.matchMedia('(hover: hover) and (pointer: fine)');
  var SPEED = { slow: 1.7, normal: 1, fast: 0.6 };
  var DEFAULTS = { hover: 'forward', doors: '1', sway: '1', view: 'auto', speed: 'normal' };
  var state = {};
  var centres = [];
  var cur = 0;            // the item nearest the middle of the track
  var active = -1;        // the item whose details are open
  var hoverIdx = -1, focusIdx = -1;
  var leaveT, introT, scrollT, scrollRaf = 0, suppressClick = false, introDone = false;
  var dots = [];

  /* ---------- settings: defaults, then the query string, then the mock controls ---------- */
  var q = new URLSearchParams(window.location.search);
  Object.keys(DEFAULTS).forEach(function (k) { state[k] = q.get(k) !== null ? q.get(k) : DEFAULTS[k]; });
  if (!SPEED[state.speed]) state.speed = 'normal';
  if (['forward', 'lift'].indexOf(state.hover) < 0) state.hover = 'forward';
  if (['auto', '3', '5'].indexOf(state.view) < 0) state.view = 'auto';

  function apply() {
    wd.setAttribute('data-hover', state.hover);
    wd.setAttribute('data-sway', state.sway === '0' ? 'off' : 'on');
    wd.setAttribute('data-view', state.view);
    wd.setAttribute('data-speed', state.speed);
    spread(active);
    measure();
  }

  /* ---------- geometry ---------- */
  function measure() {
    centres = items.map(function (li) { return li.offsetLeft + li.offsetWidth / 2; });
    onScroll();
  }
  function nearest() {
    var mid = track.scrollLeft + track.clientWidth / 2, best = 0, bd = Infinity;
    for (var i = 0; i < centres.length; i++) {
      var d = Math.abs(centres[i] - mid);
      if (d < bd) { bd = d; best = i; }
    }
    return best;
  }
  function scrollToIndex(i) {
    i = Math.max(0, Math.min(n - 1, i));
    track.scrollTo({ left: centres[i] - track.clientWidth / 2, behavior: mqReduce.matches ? 'auto' : 'smooth' });
  }

  /* ---------- the active item: the garment comes forward, its neighbours spread, the card opens inside the track ---------- */
  function spread(i) {
    var on = i >= 0 && state.hover === 'forward' && !mqReduce.matches;
    items.forEach(function (li, j) {
      var d = j - i, a = Math.abs(d), px = on && d !== 0 ? (d < 0 ? -1 : 1) * (a === 1 ? 34 : a === 2 ? 14 : 0) : 0;
      li.style.setProperty('--s', px + 'px');
    });
  }
  function clampCard(i) {
    requestAnimationFrame(function () {
      var li = items[i], card = li.querySelector('.wardrobe__card');
      var tr = track.getBoundingClientRect(), ir = li.getBoundingClientRect(), w = card.offsetWidth;
      var cx = ir.left + ir.width / 2 - (parseFloat(li.style.getPropertyValue('--s')) || 0);
      var target = Math.min(Math.max(cx, tr.left + 8 + w / 2), tr.right - 8 - w / 2);
      requestAnimationFrame(function () { li.style.setProperty('--card-x', (target - cx) + 'px'); });
    });
  }
  function setActive(i) {
    if (i === active) return;
    active = i;
    items.forEach(function (li, j) { li.classList.toggle('is-active', j === i); });
    wd.classList.toggle('has-active', i >= 0);
    spread(i);
    if (i >= 0) clampCard(i);
  }

  /* ---------- counter, dots, prev and next ---------- */
  function buildDots() {
    if (!dotsEl) return;
    links.forEach(function (a, i) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'wardrobe-ui__dot';
      b.setAttribute('aria-label', 'Show ' + a.querySelector('.visually-hidden').textContent.split(',')[0]);
      b.addEventListener('click', function () { scrollToIndex(i); });
      dotsEl.appendChild(b);
      dots.push(b);
    });
  }
  function updateUi() {
    if (countEl) countEl.textContent = (cur + 1) + ' of ' + n;
    if (prevBtn) prevBtn.disabled = cur <= 0;
    if (nextBtn) nextBtn.disabled = cur >= n - 1;
    dots.forEach(function (b, i) { if (i === cur) b.setAttribute('aria-current', 'true'); else b.removeAttribute('aria-current'); });
  }

  /* ---------- scrolling: update the counter every frame; on touch the item nearest the middle is the active one ---------- */
  function onScroll() {
    if (scrollRaf) return;
    scrollRaf = requestAnimationFrame(function () {
      scrollRaf = 0;
      var i = nearest();
      if (i !== cur) { cur = i; updateUi(); }
      if (!mqFine.matches) setActive(i);
    });
    clearTimeout(scrollT);
    scrollT = setTimeout(function () {   // the scroll has settled
      cur = nearest(); updateUi();
      if (!mqFine.matches) { setActive(cur); if (active >= 0) clampCard(active); }
    }, 110);
  }
  track.addEventListener('scroll', onScroll, { passive: true });
  if ('ResizeObserver' in window) new ResizeObserver(measure).observe(track); else window.addEventListener('resize', measure, { passive: true });

  if (prevBtn) prevBtn.addEventListener('click', function () { scrollToIndex(cur - 1); });
  if (nextBtn) nextBtn.addEventListener('click', function () { scrollToIndex(cur + 1); });

  /* ---------- pointer: hover, click and tap ---------- */
  items.forEach(function (li, i) {
    li.addEventListener('pointerenter', function (e) {
      if (e.pointerType === 'touch' || !mqFine.matches) return;
      clearTimeout(leaveT); hoverIdx = i; setActive(i);
    });
    li.addEventListener('pointerleave', function (e) {
      if (e.pointerType === 'touch' || !mqFine.matches) return;
      if (hoverIdx === i) hoverIdx = -1;
      clearTimeout(leaveT);
      leaveT = setTimeout(function () { if (hoverIdx < 0 && focusIdx < 0) setActive(-1); }, 120);
    });
  });
  track.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('.wardrobe__link') : null;
    if (!a) return;
    if (suppressClick) { e.preventDefault(); e.stopPropagation(); return; }   // the end of a drag is not a click
    var i = links.indexOf(a);
    if (e.detail !== 0 && !mqFine.matches && i !== cur) {   // touch: the first tap on a side item brings it to the middle
      e.preventDefault();
      scrollToIndex(i);
      setActive(i);
    }
  }, true);

  /* mouse drag: capture only once the pointer has really moved, so a plain click on a link is untouched */
  var drag = null;
  track.addEventListener('pointerdown', function (e) {
    if (e.pointerType !== 'mouse' || e.button !== 0) return;
    drag = { id: e.pointerId, x: e.clientX, left: track.scrollLeft, moved: false };
  });
  track.addEventListener('pointermove', function (e) {
    if (!drag || e.pointerId !== drag.id) return;
    var dx = e.clientX - drag.x;
    if (!drag.moved && Math.abs(dx) > 6) {
      drag.moved = true;
      track.setPointerCapture(e.pointerId);
      track.classList.add('is-dragging');
      track.style.scrollSnapType = 'none';
      wd.classList.add('is-dragging');
    }
    if (drag.moved) track.scrollLeft = drag.left - dx;
  });
  function endDrag(e) {
    if (!drag || e.pointerId !== drag.id) return;
    var moved = drag.moved;
    drag = null;
    if (!moved) return;
    suppressClick = true;
    setTimeout(function () { suppressClick = false; }, 60);
    track.classList.remove('is-dragging');
    wd.classList.remove('is-dragging');
    track.style.scrollSnapType = '';
    scrollToIndex(nearest());
  }
  track.addEventListener('pointerup', endDrag);
  track.addEventListener('pointercancel', endDrag);

  /* ---------- keyboard: focus opens an item and brings it into view; arrows, Home and End scroll ---------- */
  links.forEach(function (a, i) {
    a.addEventListener('focus', function () {
      focusIdx = i;
      if (!a.matches(':focus-visible')) return;
      setActive(i);
      scrollToIndex(i);
    });
    a.addEventListener('blur', function () {
      if (focusIdx === i) focusIdx = -1;
      clearTimeout(leaveT);
      leaveT = setTimeout(function () { if (hoverIdx < 0 && focusIdx < 0 && mqFine.matches) setActive(-1); }, 120);
    });
  });
  track.addEventListener('keydown', function (e) {
    var t = null;
    if (e.key === 'ArrowRight') t = cur + 1;
    else if (e.key === 'ArrowLeft') t = cur - 1;
    else if (e.key === 'Home') t = 0;
    else if (e.key === 'End') t = n - 1;
    if (t === null) return;
    e.preventDefault();
    t = Math.max(0, Math.min(n - 1, t));
    scrollToIndex(t);
    setActive(t);
  });

  /* ---------- on screen: the idle sway runs only while the wardrobe is visible; the intro plays once ---------- */
  function intro() {
    clearTimeout(introT);
    wd.classList.remove('is-in', 'is-opening');
    var mul = SPEED[state.speed];
    if (mqReduce.matches) { wd.removeAttribute('data-intro'); wd.setAttribute('data-doors', 'none'); wd.classList.add('is-in'); return; }
    wd.setAttribute('data-intro', 'armed');
    if (state.doors !== '0') {
      wd.setAttribute('data-doors', 'closed');
      void wd.offsetWidth;
      wd.classList.add('is-opening');
      introT = setTimeout(function () { wd.classList.add('is-in'); }, 620 * mul);          // the hangers start dropping as the doors finish opening
      setTimeout(function () { if (wd.classList.contains('is-opening')) wd.setAttribute('data-doors', 'none'); }, 960 * mul);
    } else {
      wd.setAttribute('data-doors', 'none');
      requestAnimationFrame(function () { wd.classList.add('is-in'); });
    }
  }
  function armIntro() {   // before the section is seen: hangers off the rail, doors shut
    if (mqReduce.matches) { wd.setAttribute('data-doors', 'none'); return; }
    wd.setAttribute('data-intro', 'armed');
    wd.setAttribute('data-doors', state.doors !== '0' ? 'closed' : 'none');
  }
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (es) {
      var on = es[es.length - 1].isIntersecting;
      wd.classList.toggle('is-live', on);
      if (on && !introDone) { introDone = true; intro(); }
    }, { threshold: 0.3 }).observe(wd);
    var vis = new IntersectionObserver(function (es) {
      es.forEach(function (e) { e.target.classList.toggle('is-vis', e.isIntersecting); });
    }, { root: track, threshold: 0.1 });
    items.forEach(function (li) { vis.observe(li); });
  } else {
    wd.classList.add('is-live'); items.forEach(function (li) { li.classList.add('is-vis'); }); introDone = true; intro();
  }

  /* ---------- mock controls (mock-only: delete this block with the controls bar) ---------- */
  var bar = document.querySelector('[data-mock-only]');
  function syncRadios() {
    if (!bar) return;
    Object.keys(state).forEach(function (k) {
      var r = bar.querySelector('input[name="' + k + '"][value="' + state[k] + '"]');
      if (r) r.checked = true;
    });
  }
  if (bar) {
    bar.addEventListener('change', function (e) {
      var t = e.target;
      if (!t.name || !(t.name in state)) return;
      state[t.name] = t.value;
      apply();
      if (t.name === 'doors') { introDone = true; intro(); }
    });
    var rep = bar.querySelector('[data-mock-replay]');
    var rst = bar.querySelector('[data-mock-reset]');
    if (rep) rep.addEventListener('click', function () { introDone = true; intro(); });
    if (rst) rst.addEventListener('click', function () {
      Object.keys(DEFAULTS).forEach(function (k) { state[k] = DEFAULTS[k]; });
      syncRadios(); apply(); introDone = true; intro();
      track.scrollTo({ left: 0, behavior: 'auto' });
    });
    if (window.location.search) bar.open = true;
  }

  /* ---------- go ---------- */
  buildDots();
  syncRadios();
  armIntro();
  apply();
  updateUi();
})();
