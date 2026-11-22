/* Shop filters (resources/views/store/shop.blade.php). The panel is a plain GET form that works without scripts
   (it then sits above the results on phones, with its Show results button). With scripts:
   - from 900px it is a sidebar and every change submits the form at once (price fields on change, i.e. Enter or leaving the field);
   - below 900px it is a drawer: the Filters button opens it through app.js's layer handling ([data-open-layer] / [data-layer]:
     focus trap, Escape, the overlay) and nothing is submitted until Show results. */
(function () {
  'use strict';

  var form = document.querySelector('[data-shop-filters]');
  if (!form) return;

  var opener = document.querySelector('[data-open-layer="filters"]');
  var close = form.querySelector('[data-close-layer]');
  var desktop = window.matchMedia('(min-width: 900px)');

  function mode() {
    var phone = !desktop.matches;
    if (phone) {
      form.setAttribute('role', 'dialog');
      form.setAttribute('aria-modal', 'true');
      if (!form.classList.contains('is-open')) form.setAttribute('aria-hidden', 'true');
    } else {
      // leaving phone width with the drawer open: close it through app.js, then make the sidebar a plain region again
      if (form.classList.contains('is-open') && close) close.click();
      form.removeAttribute('role');
      form.removeAttribute('aria-modal');
      form.removeAttribute('aria-hidden');
    }
    if (opener) opener.hidden = !phone;
    if (close) close.hidden = !phone;
  }

  form.classList.add('is-enhanced');
  mode();
  desktop.addEventListener('change', mode);

  form.addEventListener('change', function (e) {
    if (!desktop.matches) return;
    if (!e.target.closest('[data-filter-auto]')) return;
    if (form.requestSubmit) form.requestSubmit(); else form.submit();
  });

  // Empty price fields and the default sort stay out of the URL (?min=&max=&sort=featured would be noise).
  form.addEventListener('submit', function () {
    Array.prototype.forEach.call(form.elements, function (el) {
      if ((el.type === 'number' && el.value === '') || (el.name === 'sort' && el.value === 'featured')) el.disabled = true;
    });
  });
  // Back to this page from the back/forward cache: the fields switched off above must work again.
  window.addEventListener('pageshow', function () {
    Array.prototype.forEach.call(form.elements, function (el) {
      if (el.type === 'number' || el.name === 'sort') el.disabled = false;
    });
  });
}());
