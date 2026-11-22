/* Kayaa Home: the scrolling band. Ported from design/home-v2-concept.html (42px/s base speed, scroll boost x6 capped at 600, decay 0.08^dt).
   The messages and the second (aria-hidden) copy are written into the page by tools/sync-shell.mjs (gen: home-marquee).
   Reduced motion (html.rm): no script, the CSS shows one static wrapped line and hides the button. The loop sleeps off-screen, while paused and while the tab is hidden. */
(function () {
  'use strict';
  var K = window.KayaaHome;
  if (!K || K.RM) return;
  var $ = K.$, track = $('#mqTrack'), pause = $('#mqPause'), band = $('.marquee');
  if (!track || !pause || !band || !track.firstElementChild) return;

  var PLAY = '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5l11 7-11 7z"/></svg>';
  var PAUSE = '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><line x1="9" y1="6" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="18"/></svg>';
  var x = 0, w = 0, boost = 0, dir = 1, lastY = window.scrollY, last = 0;
  var paused = false, inView = true, running = false;

  function measure() { w = track.firstElementChild.offsetWidth; }
  measure();
  window.addEventListener('resize', measure);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(measure);

  /* scrolling the page speeds the band up and sets its direction; the boost decays back to the base speed */
  window.addEventListener('scroll', function () {
    var d = window.scrollY - lastY; lastY = window.scrollY;
    if (d) { dir = d > 0 ? 1 : -1; boost = Math.min(600, boost + Math.abs(d) * 6); }
  }, { passive: true });

  function frame(t) {
    if (!inView || paused || document.hidden) { running = false; return; }
    var dt = Math.min(0.05, (t - last) / 1000); last = t;
    boost *= Math.pow(0.08, dt);
    x -= dir * (42 + boost) * dt;
    if (x <= -w) x += w;
    if (x > 0) x -= w;
    track.style.transform = 'translate3d(' + x.toFixed(1) + 'px,0,0)';
    requestAnimationFrame(frame);
  }
  function start() {
    if (running || !inView || paused || document.hidden) return;
    running = true; last = performance.now(); requestAnimationFrame(frame);
  }
  K.visibleWatcher(band, function (v) { inView = v; if (v) start(); });
  document.addEventListener('visibilitychange', start);

  pause.addEventListener('click', function () {
    paused = !paused;
    pause.setAttribute('aria-pressed', String(paused));
    pause.setAttribute('aria-label', paused ? 'Play the moving text' : 'Pause the moving text');
    pause.innerHTML = paused ? PLAY : PAUSE;
    if (!paused) start();
  });
  band.classList.add('is-live');
}());
