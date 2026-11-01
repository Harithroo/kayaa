/* Cart page behaviour (production code: stays after the Blade conversion).
   Shows the sticky checkout bar below 900px once the main Checkout button leaves the viewport. The bar replaces the
   tab bar while visible. Quantity and remove are real forms (the server handles them); [data-autosubmit] on the
   quantity input submits the form when the stepper changes. */
(function () {
  'use strict';

  var page = document.querySelector('[data-cart-page]');
  if (!page) return;

  var bar = page.querySelector('[data-sticky-atc]') || document.querySelector('[data-sticky-atc]');
  var main = page.querySelector('[data-checkout-btn]');
  var filled = page.querySelector('[data-cart-filled]');
  if (!bar || !main) return;

  var mq = window.matchMedia('(max-width: 899px)');
  var buttonOut = false;

  function apply() {
    var on = mq.matches && buttonOut && !(filled && filled.hidden);
    bar.classList.toggle('is-visible', on);
    bar.inert = !on;
    document.body.classList.toggle('has-sticky-atc', on);
  }

  if ('IntersectionObserver' in window) {
    var header = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-h'), 10) || 61;
    new IntersectionObserver(function (entries) {
      buttonOut = !entries[0].isIntersecting;
      apply();
    }, { rootMargin: '-' + header + 'px 0px -64px 0px' }).observe(main);
  }
  mq.addEventListener('change', apply);
  document.addEventListener('kayaa:cart', function () { requestAnimationFrame(apply); });   // the prototype re-renders the page in place
})();
