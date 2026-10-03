/* PROTOTYPE ONLY - delete at Blade conversion.
   Drives the sample product page: gallery, colour/size stock, add to cart, sticky bar, reviews and the
   ?demo= states (sale | new | low-stock | oos | no-reviews | reviewed | review-success | review-error |
   review-throttle). Every product card in the prototype opens this same sample product. */
(function () {
  'use strict';

  var page = document.querySelector('[data-product-page]');
  if (!page) return;

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var params = new URLSearchParams(location.search);
  var demo = params.get('demo') || '';
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var smooth = reduce ? 'auto' : 'smooth';
  var money = function (n) { return 'Rs ' + Math.round(n).toLocaleString('en-US'); };

  /* ---------- Sample data ---------- */
  var NAME = 'Ribbed Cotton Bodysuit';
  var PRICE = demo === 'sale' ? 1190 : 1490;
  var WAS = 1490;
  var LOW = 5;   // TODO: low-stock threshold to be confirmed
  var COLOUR_NAME = { lilac: 'Lilac', cream: 'Cream', sky: 'Sky' };
  var SIZE_NAME = { 'nb': 'NB', '0-3m': '0-3M', '3-6m': '3-6M', '6-9m': '6-9M', '9-12m': '9-12M', '12-18m': '12-18M' };
  var STOCK = {   // stock per colour and size
    lilac: { 'nb': 0, '0-3m': 8, '3-6m': 3, '6-9m': 12, '9-12m': 0, '12-18m': 6 },
    cream: { 'nb': 5, '0-3m': 0, '3-6m': 9, '6-9m': 2, '9-12m': 7, '12-18m': 0 },
    sky:   { 'nb': 0, '0-3m': 4, '3-6m': 0, '6-9m': 10, '9-12m': 6, '12-18m': 3 }
  };
  function stockOf(colour, size) {
    if (demo === 'oos') return 0;
    var n = STOCK[colour][size];
    if (demo === 'low-stock') return n > 0 ? Math.min(n, 3) : 0;
    return n;
  }
  var allOut = demo === 'oos';

  /* ---------- Badge and price (demo states) ---------- */
  var badge = $('[data-product-badge]');
  if (demo === 'sale') {
    badge.className = 'badge badge--accent gallery__badge';
    badge.textContent = 'Sale';
    $('[data-price-row]').innerHTML =
      '<p class="price price--sale price--lg"><span class="visually-hidden">Sale price </span><span class="price__now">' + money(PRICE) +
      '</span> <s class="price__was"><span class="visually-hidden">Was </span>' + money(WAS) + '</s></p>' +
      '<span class="badge badge--lilac">Save ' + money(WAS - PRICE) + '</span>';
  } else if (demo === 'new') {
    badge.className = 'badge badge--lilac gallery__badge';
    badge.textContent = 'New';
  }
  $('[data-sticky-price]').textContent = money(PRICE);

  /* ---------- Gallery ---------- */
  (function () {
    var root = $('[data-gallery]');
    var track = $('[data-gallery-track]', root);
    var slides = $$('.gallery__slide', track);
    var dots = $$('[data-gallery-dot]', root);
    var thumbs = $$('[data-gallery-thumb]', root);
    var count = $('[data-gallery-count]', root);
    var index = 0;

    function setActive(i) {
      index = i;
      count.textContent = (i + 1) + ' / ' + slides.length;
      dots.forEach(function (d, k) { d.setAttribute('aria-current', k === i ? 'true' : 'false'); });
      thumbs.forEach(function (t, k) {
        t.setAttribute('aria-current', k === i ? 'true' : 'false');
        t.tabIndex = k === i ? 0 : -1;
      });
    }
    function goTo(i, instant) {
      i = Math.max(0, Math.min(slides.length - 1, i));
      track.scrollTo({ left: i * track.clientWidth, behavior: instant ? 'auto' : smooth });
      setActive(i);
    }

    var ticking = false;
    track.addEventListener('scroll', function () {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(function () {
        ticking = false;
        var i = Math.round(track.scrollLeft / track.clientWidth);
        if (i !== index) setActive(i);
      });
    });
    dots.forEach(function (d, i) { d.addEventListener('click', function () { goTo(i); }); });
    thumbs.forEach(function (t, i) {
      t.addEventListener('click', function () { goTo(i); });
      t.addEventListener('keydown', function (e) {
        var next = null;
        if (e.key === 'ArrowDown' || e.key === 'ArrowRight') next = i + 1;
        else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') next = i - 1;
        else if (e.key === 'Home') next = 0;
        else if (e.key === 'End') next = thumbs.length - 1;
        if (next === null) return;
        e.preventDefault();
        next = Math.max(0, Math.min(thumbs.length - 1, next));
        goTo(next);
        thumbs[next].focus();
      });
    });
    window.addEventListener('resize', function () { goTo(index, true); });
    setActive(0);
  })();

  /* ---------- Colour, size, stock, quantity ---------- */
  var form = $('[data-buy-form]');
  var sizeInputs = $$('input[name="size"]', form);
  var colourInputs = $$('input[name="colour"]', form);
  var stockNote = $('[data-stock-note]');
  var qtyGroup = $('[data-qty]', form);
  var qtyInput = $('[data-qty-input]', form);
  var addBtn = $('[data-add-btn]', form);
  var sizeGroup = $('[data-size-group]');
  var sizeError = $('[data-size-error]');
  var stickyBar = $('[data-sticky-atc]');
  var stickySize = $('[data-sticky-size]');
  var stickyBtn = $('[data-sticky-btn]');
  var lastTrigger = addBtn;

  function selected() {
    var c = $('input[name="colour"]:checked', form);
    var s = $('input[name="size"]:checked', form);
    return { colour: c ? c.value : 'lilac', size: s ? s.value : null };
  }

  function hideSizeError() {
    sizeError.hidden = true;
    sizeGroup.removeAttribute('aria-invalid');
    sizeGroup.removeAttribute('aria-describedby');
  }

  function refresh() {
    var sel = selected();
    $('[data-colour-label]').textContent = COLOUR_NAME[sel.colour];

    sizeInputs.forEach(function (inp) {
      var out = stockOf(sel.colour, inp.value) <= 0;
      inp.disabled = out;
      if (out && inp.checked) inp.checked = false;
      var oosText = $('[data-oos-text]', inp.parentNode);
      if (oosText) oosText.hidden = !out;
    });

    sel = selected();
    var n = sel.size ? stockOf(sel.colour, sel.size) : null;

    var msg, cls = '';
    if (allOut) { msg = 'Out of stock'; cls = 'is-out'; }
    else if (n === null) msg = 'Choose a size to see availability';
    else if (n <= LOW) { msg = 'Only ' + n + ' left'; cls = 'is-low'; }
    else msg = 'In stock';
    stockNote.textContent = msg;
    stockNote.className = 'buy__stock' + (cls ? ' ' + cls : '');

    qtyGroup.setAttribute('data-qty-max', String(n === null ? 10 : Math.max(1, Math.min(10, n))));
    qtyInput.dispatchEvent(new Event('change', { bubbles: true }));   // app.js clamps the value and updates the +/- buttons

    addBtn.disabled = allOut;
    addBtn.textContent = allOut ? 'Out of stock' : 'Add to cart';
    stickyBtn.disabled = allOut;
    stickyBtn.textContent = allOut ? 'Out of stock' : (sel.size ? 'Add to cart' : 'Choose size');
    stickySize.textContent = sel.size ? 'Size ' + SIZE_NAME[sel.size] + ' · ' + COLOUR_NAME[sel.colour] : (allOut ? 'Currently unavailable' : 'Select a size');

    if (sel.size) hideSizeError();
  }
  form.addEventListener('change', function (e) {
    if (e.target.name === 'colour' || e.target.name === 'size') refresh();
  });

  function showSizeError() {
    sizeError.hidden = false;
    sizeGroup.setAttribute('aria-invalid', 'true');
    sizeGroup.setAttribute('aria-describedby', 'size-error stock-note');
    sizeGroup.focus();
    sizeGroup.scrollIntoView({ block: 'center', behavior: smooth });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (addBtn.disabled) return;
    var sel = selected();
    if (!sel.size) { showSizeError(); return; }
    var qty = Math.max(1, parseInt(qtyInput.value, 10) || 1);
    if (window.KayaaCart) {
      window.KayaaCart.add({
        name: NAME,
        price: PRICE,
        tone: '1',
        meta: 'Size ' + SIZE_NAME[sel.size] + ' · ' + COLOUR_NAME[sel.colour],
        qty: qty
      }, lastTrigger);
    }
  });
  addBtn.addEventListener('click', function () { lastTrigger = addBtn; });

  /* Size guide panel */
  var guideBtn = $('[data-size-guide-toggle]');
  var guide = $('[data-size-guide]');
  guideBtn.addEventListener('click', function () {
    var open = guide.hidden;
    guide.hidden = !open;
    guideBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  /* Delivery estimate (placeholder data) */
  var ETA = { colombo: '2–3 working days', gampaha: '2–3 working days', kandy: '3–4 working days', galle: '3–4 working days', jaffna: '4–6 working days', kurunegala: '3–4 working days' };
  var district = $('[data-district]');
  district.addEventListener('change', function () {
    var label = district.options[district.selectedIndex].text;
    $('[data-delivery-result]').textContent = 'Estimated delivery to ' + label + ': ' + ETA[district.value] + ' · Rs 450';
  });

  /* ---------- Sticky add-to-cart bar (below 900px) ---------- */
  (function () {
    var mq = window.matchMedia('(max-width: 899px)');
    var buttonOut = false;
    function apply() {
      var on = mq.matches && buttonOut;
      stickyBar.classList.toggle('is-visible', on);
      stickyBar.inert = !on;
      document.body.classList.toggle('has-sticky-atc', on);
    }
    if ('IntersectionObserver' in window) {
      var header = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-h'), 10) || 61;
      new IntersectionObserver(function (entries) {
        buttonOut = !entries[0].isIntersecting;
        apply();
      }, { rootMargin: '-' + header + 'px 0px -64px 0px' }).observe(addBtn);
    }
    mq.addEventListener('change', apply);
    stickyBtn.addEventListener('click', function () {
      lastTrigger = stickyBtn;
      if (!selected().size) { showSizeError(); return; }
      if (form.requestSubmit) form.requestSubmit(); else form.dispatchEvent(new Event('submit', { cancelable: true }));
    });
  })();

  /* ---------- Reviews ---------- */
  var reviewsContent = $('[data-reviews-content]');
  var reviewsEmpty = $('[data-reviews-empty]');
  var more = $('[data-reviews-more]');

  if (params.get('reviews') === 'all') {
    $$('[data-review-extra]').forEach(function (li) { li.hidden = false; });
    more.hidden = true;
  } else {
    var u = new URL(location.href);
    u.searchParams.set('reviews', 'all');
    $('a', more).setAttribute('href', u.search + '#reviews');
  }

  if (demo === 'no-reviews') {
    reviewsContent.hidden = true;
    reviewsEmpty.hidden = false;
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
    var control = name === 'rating' ? $$('input[name="rating"]', rf) : [$('[name="' + name + '"]', rf)];
    if (message) {
      err.textContent = message;
      err.hidden = false;
      wrap.classList.add(name === 'rating' ? 'rating-input--error' : 'field--error');
      control.forEach(function (c) { c.setAttribute('aria-invalid', 'true'); c.setAttribute('aria-describedby', err.id); });
    } else {
      err.hidden = true;
      wrap.classList.remove('rating-input--error', 'field--error');
      control.forEach(function (c) { c.removeAttribute('aria-invalid'); c.removeAttribute('aria-describedby'); });
    }
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
    alerts.reviewed = $('[data-review-reviewed]');
    alerts.reviewed.hidden = false;
  }

  var target = $('#write-review');
  if (/^review/.test(demo)) {
    if (demo === 'review-error') validate();
    if (demo === 'review-success') { alerts.success.hidden = false; }
    if (demo === 'review-throttle') { alerts.throttle.hidden = false; }
    window.addEventListener('load', function () { target.scrollIntoView({ behavior: 'auto' }); });
  }

  refresh();
})();
