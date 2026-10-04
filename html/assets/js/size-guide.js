/* Size guide slider (production code: stays after the Blade conversion).
   Markup (tools/content.mjs, renderSizeGuideSlider): .sg[data-sg] > .sg__track[data-sg-track] > .sg__slide x N, then .sg__controls[data-sg-controls]
   with [data-sg-prev], [data-sg-next], .sg__dot[data-sg-dot] and [data-sg-now], and a polite live region [data-sg-live].
   - Without this script the slides stack and the controls stay hidden.
   - The track is a native scroll-snap row: swipe works by itself. The buttons and dots call scrollTo; the dots, the counter and the buttons
     follow the position (IntersectionObserver). ArrowLeft and ArrowRight work while focus is inside the slider.
   - No autoplay. Reduced motion: scrolling is instant. Changing the step announces its caption politely. */
(function () {
  'use strict';

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');
  var behaviour = function () { return reduce && reduce.matches ? 'auto' : 'smooth'; };

  Array.prototype.forEach.call(document.querySelectorAll('[data-sg]'), function (root) {
    var track = root.querySelector('[data-sg-track]');
    var slides = Array.prototype.slice.call(root.querySelectorAll('.sg__slide'));
    var controls = root.querySelector('[data-sg-controls]');
    var prev = root.querySelector('[data-sg-prev]');
    var next = root.querySelector('[data-sg-next]');
    var dots = Array.prototype.slice.call(root.querySelectorAll('[data-sg-dot]'));
    var now = root.querySelector('[data-sg-now]');
    var live = root.querySelector('[data-sg-live]');
    if (!track || slides.length < 2) return;

    root.classList.add('is-enhanced');
    if (controls) controls.hidden = false;

    var index = 0;
    var announce = false;   // the first paint is not announced, a change is

    function paint(i) {
      index = i;
      dots.forEach(function (d, k) { if (k === i) d.setAttribute('aria-current', 'true'); else d.removeAttribute('aria-current'); });
      if (now) now.textContent = String(i + 1);
      if (prev) prev.setAttribute('aria-disabled', i === 0 ? 'true' : 'false');
      if (next) next.setAttribute('aria-disabled', i === slides.length - 1 ? 'true' : 'false');
      if (announce && live) {
        var cap = slides[i].querySelector('[data-sg-caption]');
        live.textContent = '';
        window.setTimeout(function () { live.textContent = (i + 1) + ' of ' + slides.length + '. ' + (cap ? cap.textContent.trim() : ''); }, 30);   // re-trigger the live region
      }
    }

    function go(i) {
      i = Math.max(0, Math.min(slides.length - 1, i));
      track.scrollTo({ left: slides[i].offsetLeft - track.offsetLeft, behavior: behaviour() });
      announce = true;
      paint(i);
    }

    if (prev) prev.addEventListener('click', function () { if (index > 0) go(index - 1); });
    if (next) next.addEventListener('click', function () { if (index < slides.length - 1) go(index + 1); });
    dots.forEach(function (d, k) { d.addEventListener('click', function () { go(k); }); });

    root.addEventListener('keydown', function (e) {
      if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
      if (e.target.closest && e.target.closest('input, select, textarea')) return;
      e.preventDefault();
      go(index + (e.key === 'ArrowRight' ? 1 : -1));
    });

    // the dots follow a swipe: the slide that is more than 60% visible is the current one
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (!en.isIntersecting || en.intersectionRatio < 0.6) return;
          var i = slides.indexOf(en.target);
          if (i !== -1 && i !== index) { announce = true; paint(i); }
        });
      }, { root: track, threshold: [0.6] });
      slides.forEach(function (s) { io.observe(s); });
    }

    // inside a closed <details> the track has no width: when it opens, start again at the first step
    var details = root.closest('details');
    if (details) details.addEventListener('toggle', function () { if (details.open) { track.scrollTo({ left: 0, behavior: 'auto' }); paint(0); } });

    paint(0);
    announce = false;
  });
})();
