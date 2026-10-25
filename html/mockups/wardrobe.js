/* MOCK-UP ONLY (html/mockups/): behaviour of the wardrobe category carousel. Vanilla JS, no dependencies, passive listeners, pointer events.
   Reads in one place and writes in requestAnimationFrame; nothing animates off screen. Delete with the mock. */
(function () {
  'use strict';
  var wd = document.querySelector('[data-wardrobe]');
  if (!wd) return;
  var track = wd.querySelector('[data-wardrobe-track]');
  var rack = wd.querySelector('.wardrobe__rack');
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
  var SLIDE = [10, 5];   // how far the first and second neighbour nudge along the rod (Neighbours: Nudge)
  var DEFAULTS = { hover: 'forward', doors: '1', sway: '1', view: 'auto', speed: 'normal', neighbours: 'nudge', art: 'ill', ticker: '0' };
  var ui = document.querySelector('[data-wardrobe-ui]');
  var cap = wd.querySelector('[data-wd-caption]');
  var capName = wd.querySelector('[data-wd-cap-name]');
  var capMeta = wd.querySelector('[data-wd-cap-meta]');
  var capGo = wd.querySelector('[data-wd-cap-go]');
  var ticker = document.querySelector('[data-wd-ticker]');
  var ARROW = '<svg class="icon icon--16" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
  var state = {};
  var lefts = [], widths = [], pad = 0;
  var first = 0, last = 0;   // the items that are fully inside the track
  var active = -1;           // the item whose details are open
  var tapLock = -1;          // touch: the item the last tap chose, until the next touch on the track
  var hoverIdx = -1, focusIdx = -1;
  var leaveT, introT, settleT, scrollT, scrollRaf = 0, suppressClick = false, introDone = false;
  var dots = [];

  /* ---------- settings: defaults, then the query string, then the mock controls ---------- */
  var q = new URLSearchParams(window.location.search);
  Object.keys(DEFAULTS).forEach(function (k) { state[k] = q.get(k) !== null ? q.get(k) : DEFAULTS[k]; });
  if (!SPEED[state.speed]) state.speed = 'normal';
  if (['forward', 'pullout'].indexOf(state.hover) < 0) state.hover = 'forward';
  if (['nudge', 'still'].indexOf(state.neighbours) < 0) state.neighbours = 'nudge';
  if (['ill', 'photo'].indexOf(state.art) < 0) state.art = 'ill';
  if (['auto', '3', '5'].indexOf(state.view) < 0) state.view = 'auto';

  function apply() {
    wd.setAttribute('data-hover', state.hover);
    wd.setAttribute('data-sway', state.sway === '0' ? 'off' : 'on');
    wd.setAttribute('data-view', state.view);
    wd.setAttribute('data-speed', state.speed);
    wd.setAttribute('data-art', state.art);
    if (state.art === 'photo') loadPhotos();
    applyTicker();
    spread(active);
    measure();
  }
  function interacted() { wd.classList.add('has-interacted'); }

  /* ---------- geometry (cached; read on resize and when the settings change) ---------- */
  function measure() {
    pad = parseFloat(getComputedStyle(rack).paddingLeft) || 0;
    lefts = items.map(function (li) { return li.offsetLeft; });
    widths = items.map(function (li) { return li.offsetWidth; });
    if (ui) ui.hidden = maxScroll() <= 1;   // everything fits: no arrows, dots or counter
    onScroll();
    if (active >= 0) placeCaption();
  }
  function maxScroll() { return track.scrollWidth - track.clientWidth; }
  function range() {   // the items fully inside the track
    var sl = track.scrollLeft, w = track.clientWidth, f = -1, l = -1;
    for (var i = 0; i < n; i++) {
      if (lefts[i] >= sl - 3 && lefts[i] + widths[i] <= sl + w + 3) { if (f < 0) f = i; l = i; }
    }
    if (f < 0) { f = l = nearestStart(); }
    return [f, l];
  }
  function nearestStart() {
    var sl = track.scrollLeft, best = 0, bd = Infinity;
    for (var i = 0; i < n; i++) { var d = Math.abs(lefts[i] - pad - sl); if (d < bd) { bd = d; best = i; } }
    return best;
  }
  function nearestMid() {
    var mid = track.scrollLeft + track.clientWidth / 2, best = 0, bd = Infinity;
    for (var i = 0; i < n; i++) { var d = Math.abs(lefts[i] + widths[i] / 2 - mid); if (d < bd) { bd = d; best = i; } }
    return best;
  }
  function scrollToIndex(i) {
    i = Math.max(0, Math.min(n - 1, i));
    track.scrollTo({ left: lefts[i] - pad, behavior: mqReduce.matches ? 'auto' : 'smooth' });
  }

  /* ---------- the active item: it comes forward (or turns toward the viewer), the neighbours slide along the rail, the card opens inside the track ---------- */
  function spread(i) {
    var on = i >= 0 && !mqReduce.matches && state.neighbours === 'nudge', amt = SLIDE;
    items.forEach(function (li, j) {
      var d = j - i, a = Math.abs(d), px = on && d !== 0 ? (d < 0 ? -1 : 1) * (a === 1 ? amt[0] : a === 2 ? amt[1] : 0) : 0;
      li.style.setProperty('--slide', px + 'px');
      li.style.setProperty('--nd', d < 0 ? -1 : 1);
    });
  }
  function nudge(i) {   // the neighbours swing a few degrees the other way for a moment (inertia)
    if (mqReduce.matches) return;
    items.forEach(function (li, j) {
      li.classList.remove('is-nudge');
      if (j !== i && Math.abs(j - i) <= 2 && i >= 0) {
        void li.offsetWidth;
        li.classList.add('is-nudge');
        li.addEventListener('animationend', function done(e) { if (e.animationName === 'wd-nudge') { li.classList.remove('is-nudge'); li.removeEventListener('animationend', done); } });
      }
    });
  }
  /* the caption: one element that moves under the active garment (centred, clamped inside the stage) and fades when the active item changes */
  var capT;
  function placeCaption() {
    if (active < 0) return;
    requestAnimationFrame(function () {
      var li = items[active], ir = li.getBoundingClientRect(), sr = wd.getBoundingClientRect(), half = cap.offsetWidth / 2;
      var cx = ir.left + ir.width / 2 - sr.left, x = Math.min(Math.max(cx, half + 8), sr.width - half - 8);
      requestAnimationFrame(function () { cap.style.setProperty('--cap-x', x + 'px'); });
    });
  }
  function fillCaption(i) {
    var li = items[i], name = li.getAttribute('data-name');
    capName.textContent = name;
    capMeta.textContent = li.getAttribute('data-meta');
    capGo.setAttribute('href', li.getAttribute('data-href'));
    capGo.textContent = 'Shop ' + name + ' ';
    capGo.insertAdjacentHTML('beforeend', ARROW);
    placeCaption();
  }
  function showCaption(i, was) {
    clearTimeout(capT);
    if (i < 0) return;
    if (was >= 0 && !mqReduce.matches) {   // from one garment to another: fade out, move, fade in (about 200ms)
      cap.classList.add('is-swap');
      capT = setTimeout(function () { fillCaption(i); cap.classList.remove('is-swap'); }, 90);
    } else { fillCaption(i); }
  }
  function setActive(i) {
    if (i === active) return;
    var was = active;
    active = i;
    items.forEach(function (li, j) { li.classList.toggle('is-active', j === i); });
    wd.classList.toggle('has-active', i >= 0);
    spread(i);
    nudge(i);
    showCaption(i, was);
    if (i >= 0) interacted();
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
    if (countEl) countEl.textContent = (first === last ? (first + 1) : (first + 1) + '–' + (last + 1)) + ' of ' + n;
    var sl = track.scrollLeft;
    if (prevBtn) prevBtn.disabled = sl <= 1;
    if (nextBtn) nextBtn.disabled = sl >= maxScroll() - 1;
    dots.forEach(function (b, i) { if (i >= first && i <= last) b.setAttribute('aria-current', 'true'); else b.removeAttribute('aria-current'); });
  }

  /* ---------- scrolling: the counter follows every frame; on touch the item nearest the middle is the active one ---------- */
  function onScroll() {
    if (scrollRaf) return;
    scrollRaf = requestAnimationFrame(function () {
      scrollRaf = 0;
      var r = range();
      if (r[0] !== first || r[1] !== last) { first = r[0]; last = r[1]; }
      updateUi();
      if (!mqFine.matches && tapLock < 0) setActive(nearestMid());
    });
    clearTimeout(scrollT);
    scrollT = setTimeout(function () {   // the scroll has settled
      var r = range(); first = r[0]; last = r[1]; updateUi();
      if (!mqFine.matches) { if (tapLock < 0) setActive(nearestMid()); if (active >= 0) placeCaption(); }
    }, 110);
  }
  track.addEventListener('scroll', function () { interacted(); onScroll(); }, { passive: true });
  if ('ResizeObserver' in window) new ResizeObserver(measure).observe(track); else window.addEventListener('resize', measure, { passive: true });

  if (prevBtn) prevBtn.addEventListener('click', function () { tapLock = -1; scrollToIndex(first - 1); });
  if (nextBtn) nextBtn.addEventListener('click', function () { tapLock = -1; scrollToIndex(first + 1); });

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
      leaveT = setTimeout(function () { if (hoverIdx < 0 && focusIdx < 0) setActive(-1); }, 260);
    });
  });
  cap.addEventListener('pointerenter', function () { clearTimeout(leaveT); hoverIdx = active; });
  cap.addEventListener('pointerleave', function () { hoverIdx = -1; clearTimeout(leaveT); leaveT = setTimeout(function () { if (hoverIdx < 0 && focusIdx < 0) setActive(-1); }, 260); });
  track.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('.wardrobe__link') : null;
    if (!a) return;
    if (suppressClick) { e.preventDefault(); e.stopPropagation(); return; }   // the end of a drag is not a click
    var i = links.indexOf(a);
    var chosen = tapLock >= 0 ? tapLock : nearestMid();
    if (e.detail !== 0 && !mqFine.matches && i !== chosen) {   // touch: the first tap on a side item brings it to the front
      e.preventDefault();
      tapLock = i;
      scrollToIndex(i);
      setActive(i);
    }
  }, true);

  /* mouse drag: capture only once the pointer has really moved, so a plain click on a link is untouched */
  var drag = null;
  track.addEventListener('pointerdown', function (e) {
    if (e.pointerType === 'touch') tapLock = -1;   // the next touch scroll chooses the active item again
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
    track.style.scrollSnapType = '';
    scrollToIndex(nearestStart());
  }
  track.addEventListener('pointerup', endDrag);
  track.addEventListener('pointercancel', endDrag);

  /* ---------- keyboard: focus opens an item and brings it into view; arrows, Home and End scroll ---------- */
  links.forEach(function (a, i) {
    a.addEventListener('focus', function () {
      focusIdx = i;
      if (!a.matches(':focus-visible')) return;
      setActive(i);
      if (i < first || i > last) scrollToIndex(i);
    });
    a.addEventListener('blur', function () {
      if (focusIdx === i) focusIdx = -1;
      clearTimeout(leaveT);
      leaveT = setTimeout(function () { if (hoverIdx < 0 && focusIdx < 0 && mqFine.matches) setActive(-1); }, 120);
    });
  });
  track.addEventListener('keydown', function (e) {
    var t = null;
    if (e.key === 'ArrowRight') t = active >= 0 ? active + 1 : first;
    else if (e.key === 'ArrowLeft') t = active >= 0 ? active - 1 : first;
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
    clearTimeout(introT); clearTimeout(settleT);
    wd.classList.remove('is-in', 'is-opening', 'is-settled');
    var mul = SPEED[state.speed];
    if (mqReduce.matches) { wd.removeAttribute('data-intro'); wd.setAttribute('data-doors', 'none'); wd.classList.add('is-in', 'is-settled'); return; }
    wd.setAttribute('data-intro', 'armed');
    var settle = function () { settleT = setTimeout(function () { wd.classList.add('is-settled'); }, (1500 + (n - 1) * 90 + 150) * mul); };
    if (state.doors !== '0') {
      wd.setAttribute('data-doors', 'closed');
      void wd.offsetWidth;
      wd.classList.add('is-opening');
      introT = setTimeout(function () { wd.classList.add('is-in'); settle(); }, 620 * mul);   // the hangers start to arrive as the doors finish opening
      setTimeout(function () { if (wd.classList.contains('is-opening')) wd.setAttribute('data-doors', 'none'); }, 960 * mul);
    } else {
      wd.setAttribute('data-doors', 'none');
      requestAnimationFrame(function () { wd.classList.add('is-in'); settle(); });
    }
  }
  function armIntro() {   // before the section is seen: hangers off the rail, doors shut
    if (mqReduce.matches) { wd.setAttribute('data-doors', 'none'); wd.classList.add('is-settled'); return; }
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

  /* ---------- Art: Photo placeholder (flat grey silhouettes through the same per-item values); real cut-outs at ../assets/img/wardrobe/{slug}.webp
     are used for the slugs listed in data-photos on the wardrobe ---------- */
  var photosTried = false;
  function loadPhotos() {
    if (photosTried) return;
    photosTried = true;
    (wd.getAttribute('data-photos') || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean).forEach(function (slug) {
      var li = wd.querySelector('.wardrobe__item[data-slug="' + slug + '"]');
      if (!li) return;
      var img = new Image();
      img.className = 'art wardrobe__photo';
      img.alt = ''; img.draggable = false;
      img.onload = function () { li.querySelector('.wardrobe__front').appendChild(img); li.classList.add('has-photo'); };
      img.src = '../assets/img/wardrobe/' + slug + '.webp';
    });
  }

  /* ---------- the optional ticker (a mock control): moves only when motion is allowed, with a Pause button ---------- */
  function applyTicker() {
    if (!ticker) return;
    ticker.hidden = state.ticker !== '1';
    ticker.style.setProperty('--speed-t', SPEED[state.speed]);
    ticker.classList.toggle('is-running', state.ticker === '1' && !mqReduce.matches);
  }
  var tickPause = document.querySelector('[data-wd-ticker-pause]');
  if (tickPause) tickPause.addEventListener('click', function () {
    var on = tickPause.getAttribute('aria-pressed') !== 'true';
    tickPause.setAttribute('aria-pressed', on ? 'true' : 'false');
    tickPause.textContent = on ? 'Play' : 'Pause';
    ticker.classList.toggle('is-paused', on);
  });

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
}());
