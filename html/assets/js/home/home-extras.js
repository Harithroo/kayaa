/* Kayaa Home: the small parts. The drawn garments on the promo banners, a light scroll parallax on them, the reviews list becoming a keyboard-reachable
   scroller when it overflows (phones), and the motion pill.
   The motion pill is a PROTOTYPE-ONLY review aid (remove before launch, see CLAUDE.md): it shows only on devices that ask for reduced motion and lets a
   reviewer switch the animations on for this visit (sessionStorage "kayaa-motion": "on" | "off", read by the head script of index.html), then reload. */
(function () {
  'use strict';
  var K = window.KayaaHome, G = window.KayaaGarments;
  if (!K) return;
  var $ = K.$, $$ = K.$$, RM = K.RM;

  /* ---------- promo drawings: data-art is the garment type, data-c its colour ---------- */
  if (G) $$('.promo__art').forEach(function (el) {
    var c = G.COL[el.dataset.c || 'white'] || G.COL.white;
    el.innerHTML = '<span class="g">' + G.garmentSVG(el.dataset.art, c) + '</span>';
  });

  /* ---------- light parallax (not under reduced motion; on scroll only, no loop) ---------- */
  if (!RM) {
    var els = $$('[data-speed]'), tick = false;
    var update = function () {
      tick = false;
      var vh = window.innerHeight;
      els.forEach(function (el) {
        var r = el.getBoundingClientRect(); if (r.bottom < -100 || r.top > vh + 100) return;
        var c = r.top + r.height / 2 - vh / 2;
        el.style.transform = 'translateY(' + (c * -parseFloat(el.dataset.speed)).toFixed(1) + 'px)';
      });
    };
    if (els.length) {
      window.addEventListener('scroll', function () { if (!tick) { tick = true; requestAnimationFrame(update); } }, { passive: true });
      update();
    }
  }

  /* ---------- CSS loops (the hero blobs, the tape icon) sleep while their section is off-screen ---------- */
  if (!RM) $$('[data-live]').forEach(function (el) { K.visibleWatcher(el, function (v) { el.classList.toggle('is-live', v); }); });

  /* ---------- reviews: when the list scrolls sideways it is a focusable region (arrow keys scroll it) ---------- */
  var list = $('#reviews');
  if (list) {
    var check = function () { if (list.scrollWidth > list.clientWidth + 2) list.setAttribute('tabindex', '0'); else list.removeAttribute('tabindex'); };
    check(); window.addEventListener('resize', check);
  }

  /* ---------- the motion pill (prototype only) ---------- */
  var btn = $('#motionBtn'), txt = $('#motionText');
  if (btn && txt) {
    if (!RM && document.documentElement.classList.contains('rm-device')) { txt.textContent = 'Animations are on for this visit'; btn.textContent = 'Turn off'; }
    btn.addEventListener('click', function () {
      try { sessionStorage.setItem('kayaa-motion', RM ? 'on' : 'off'); sessionStorage.setItem('kayaa-intro', '1'); } catch (e) { /* storage blocked */ }
      location.reload();
    });
  }
}());
