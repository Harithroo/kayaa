/* PROTOTYPE ONLY - delete at Blade conversion.
   Loaded before product.js. The sample variant data lives in the <script id="product-variants"> block of the
   page; this file only applies the ?demo= states (regular, new, low-stock, oos, no-reviews, reviewed,
   review-success, review-error, review-throttle, no-tags) and fakes the review form submit. The sample product is on sale by default
   (the current price with the struck-through compare-at price).
   Every product card in the prototype opens this same sample product. */
(function () {
  'use strict';

  var page = document.querySelector('[data-product-page]');
  if (!page) return;
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var demo = new URLSearchParams(location.search).get('demo') || '';
  var money = window.Kayaa.formatMoney;

  /* ---- stock states: rewrite the variants JSON that product.js reads ---- */
  if (demo === 'oos' || demo === 'low-stock') {
    var block = $('#product-variants');
    var data = JSON.parse(block.textContent);
    data.forEach(function (v) { v.stock = demo === 'oos' ? 0 : (v.stock > 0 ? Math.min(v.stock, 3) : 0); });
    block.textContent = JSON.stringify(data);
  }

  /* ---- no tags: the whole Tags row is hidden (the page renders it only when the product has tags) ---- */
  if (demo === 'no-tags') { var tagsRow = $('[data-product-tags]'); if (tagsRow) tagsRow.hidden = true; }

  /* ---- price and badge ---- */
  var form = $('[data-buy-form]');
  var badge = $('[data-product-badge]');
  if (demo === 'regular' || demo === 'new') {
    // no sale: the compare-at price and the Save badge go away
    form.setAttribute('data-unit-price', '2950');
    form.removeAttribute('data-was-price');
    $('[data-price-row]').innerHTML = '<p class="price price--lg"><span class="price__now">' + money(2950) + '</span></p>';
    if (demo === 'new') { badge.className = 'badge badge--lilac gallery__badge'; badge.textContent = 'New'; }
    else badge.hidden = true;
    $('[data-sticky-price]').textContent = money(2950);
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
    var email = $('[name="email"]', rf).value.trim();
    var body = $('[name="body"]', rf).value.trim();
    var emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    setFieldError('rating', hasRating ? '' : 'Please choose a rating.');
    setFieldError('name', name ? '' : 'Please enter your name.');
    setFieldError('email', !email ? 'Please enter your email.' : (emailOk ? '' : 'Enter a valid email address, for example name@example.com.'));
    setFieldError('body', body ? '' : 'Please write a few words about the product.');
    if (!hasRating) errors.push('Choose a rating');
    if (!name) errors.push('Enter your name');
    if (!emailOk) errors.push(email ? 'Enter a valid email address' : 'Enter your email');
    if (!body) errors.push('Write your review');
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
