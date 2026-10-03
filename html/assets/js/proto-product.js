/* PROTOTYPE ONLY - delete at Blade conversion.
   Loaded before product.js. The sample variant data lives in the <script id="product-variants"> block of the
   page; this file only applies the ?demo= states (sale, new, low-stock, oos, no-reviews, reviewed,
   review-success, review-error, review-throttle) and fakes the review form submit.
   Every product card in the prototype opens this same sample product. */
(function () {
  'use strict';

  var page = document.querySelector('[data-product-page]');
  if (!page) return;
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var demo = new URLSearchParams(location.search).get('demo') || '';
  var money = function (n) { return 'Rs ' + Math.round(n).toLocaleString('en-US'); };

  /* ---- stock states: rewrite the variants JSON that product.js reads ---- */
  if (demo === 'oos' || demo === 'low-stock') {
    var block = $('#product-variants');
    var data = JSON.parse(block.textContent);
    data.forEach(function (v) { v.stock = demo === 'oos' ? 0 : (v.stock > 0 ? Math.min(v.stock, 3) : 0); });
    block.textContent = JSON.stringify(data);
  }

  /* ---- price and badge ---- */
  var form = $('[data-buy-form]');
  var badge = $('[data-product-badge]');
  if (demo === 'sale') {
    form.setAttribute('data-unit-price', '1190');
    badge.className = 'badge badge--accent gallery__badge';
    badge.textContent = 'Sale';
    $('[data-price-row]').innerHTML =
      '<p class="price price--sale price--lg"><span class="visually-hidden">Sale price </span><span class="price__now">' + money(1190) +
      '</span> <s class="price__was"><span class="visually-hidden">Was </span>' + money(1490) + '</s></p>' +
      '<span class="badge badge--lilac">Save ' + money(300) + '</span>';
  } else if (demo === 'new') {
    badge.className = 'badge badge--lilac gallery__badge';
    badge.textContent = 'New';
  }

  /* ---- reviews ---- */
  if (demo === 'no-reviews') {
    $('[data-reviews-content]').hidden = true;
    $('[data-reviews-empty]').hidden = false;
    $('[data-reviews-summary]').hidden = true;
    var link = $('[data-rating-summary]');
    link.innerHTML = '<span>No reviews yet</span>';
    link.setAttribute('href', '#write-review');
  }

  var rf = $('[data-review-form]');
  var alerts = {};
  $$('[data-review-alert]').forEach(function (a) { alerts[a.getAttribute('data-review-alert')] = a; });
  function hideAlerts() { Object.keys(alerts).forEach(function (k) { alerts[k].hidden = true; }); }

  function setFieldError(name, message) {
    var wrap = $('[data-field="' + name + '"]', rf);
    var err = $('.field__error', wrap);
    var controls = name === 'rating' ? $$('input[name="rating"]', rf) : [$('[name="' + name + '"]', rf)];
    wrap.classList.toggle(name === 'rating' ? 'rating-input--error' : 'field--error', !!message);
    err.hidden = !message;
    err.textContent = message || '';
    controls.forEach(function (c) {
      if (message) { c.setAttribute('aria-invalid', 'true'); c.setAttribute('aria-describedby', err.id); }
      else { c.removeAttribute('aria-invalid'); c.removeAttribute('aria-describedby'); }
    });
  }

  function validate() {
    var errors = [];
    var hasRating = !!$('input[name="rating"]:checked', rf);
    var name = $('[name="name"]', rf).value.trim();
    var comment = $('[name="comment"]', rf).value.trim();
    setFieldError('rating', hasRating ? '' : 'Please choose a rating.');
    setFieldError('name', name ? '' : 'Please enter your name.');
    setFieldError('comment', comment ? '' : 'Please write a few words about the product.');
    if (!hasRating) errors.push('Choose a rating');
    if (!name) errors.push('Enter your name');
    if (!comment) errors.push('Write a comment');
    hideAlerts();
    if (errors.length) {
      $('[data-review-errors]').innerHTML = errors.map(function (m) { return '<li>' + m + '</li>'; }).join('');
      alerts.summary.hidden = false;
      alerts.summary.focus();
      return false;
    }
    return true;
  }

  rf.addEventListener('submit', function (e) {
    e.preventDefault();   // prototype: the real form POSTs to the backend
    if (!validate()) return;
    rf.reset();
    alerts.success.hidden = false;
    alerts.success.focus();
  });

  if (demo === 'reviewed') {
    rf.hidden = true;
    $('[data-review-reviewed]').hidden = false;
  }
  if (/^review/.test(demo)) {
    if (demo === 'review-error') validate();
    if (demo === 'review-success') alerts.success.hidden = false;
    if (demo === 'review-throttle') alerts.throttle.hidden = false;
    window.addEventListener('load', function () { $('#write-review').scrollIntoView({ behavior: 'auto' }); });
  }
})();
