/* Smooth scrolling for the whole storefront (Laravel addition; Lenis, MIT).

   - Mouse wheels and trackpads glide to a stop instead of jumping. Touch screens keep the phone's own
     scrolling (Lenis leaves touch alone by default), and so do the keyboard and the scrollbar.
   - Nothing at all under prefers-reduced-motion; it follows the setting live if it changes.
   - Anything that scrolls on its own (the drawers, the filter sidebar, the mega menu, the search
     suggestions, the rail and the reviews scroller) keeps its own scrolling (allowNestedScroll).
   - While a drawer or sheet has locked the page (html.is-locked, app.js) the page does not move.
   - Anchor links are left to the browser (base.css already makes them smooth), so a skip link still
     moves focus; Lenis follows any scroll it did not start, scrollIntoView included. */
import Lenis from 'lenis';

(function () {
  'use strict';

  if (!window.matchMedia) return;

  var root = document.documentElement;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
  var lenis = null;

  function sync() {
    if (!lenis) return;
    if (root.classList.contains('is-locked')) lenis.stop(); else lenis.start();
  }

  function on() {
    if (lenis || reduce.matches) return;
    lenis = new Lenis({
      autoRaf: true,
      lerp: 0.1,
      allowNestedScroll: true,
      stopInertiaOnNavigate: true,
    });
    window.Kayaa = window.Kayaa || {};
    window.Kayaa.lenis = lenis;
    sync();
  }

  function off() {
    if (!lenis) return;
    lenis.destroy();
    lenis = null;
    if (window.Kayaa) window.Kayaa.lenis = null;
  }

  new MutationObserver(sync).observe(root, { attributes: true, attributeFilter: ['class'] });
  reduce.addEventListener('change', function () { if (reduce.matches) off(); else on(); });
  on();
}());
