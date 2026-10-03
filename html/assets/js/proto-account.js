/* PROTOTYPE ONLY - delete at Blade conversion: the server renders tracking, orders, reviews and the profile.
   Uses the sample orders in proto-orders.js. One file for track.html and the account pages, selected by data hooks.
   ?demo= and params:
     track.html          ?ref=KYA-10234 (any reference; other values = not found), ?demo=throttle
     account/index.html  ?demo=empty, ?page=2
     account/order.html  ?ref=KYA-10234 (shipped), 10201 delivered, 10198 cancelled, 10240 pending payment,
                         10250 payment failed, 10180 refunded; unknown = not found
     account/reviews.html ?demo=empty
     account/profile.html ?demo=saved | errors */
(function () {
  'use strict';

  var orders = window.KayaaOrders;
  var store = window.KayaaCart;
  if (!orders || !store) return;

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var params = new URLSearchParams(location.search);
  var demo = params.get('demo') || '';
  var money = orders.money;
  var esc = function (s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;'); };
  var session = store.session.get();
  var user = session || { name: 'Amaya', email: 'amaya@example.com' };

  /* ---------- Log out (a POST form in Laravel) ---------- */
  $$('[data-logout-form]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      store.session.clear();
      location.href = '../index.html';
    });
  });

  /* ---------- Track ---------- */
  var track = $('[data-track-page]');
  if (track) {
    var form = $('[data-track-form]', track);
    var input = $('#ref', track);
    var fieldError = $('#ref-error', track);
    var notFound = $('[data-track-alert="notfound"]', track);
    var throttle = $('[data-track-alert="throttle"]', track);
    var result = $('[data-track-result]', track);

    var setInvalid = function (msg) {
      input.setAttribute('aria-invalid', 'true');
      input.setAttribute('aria-describedby', 'ref-error');
      fieldError.textContent = msg;
      fieldError.hidden = false;
    };
    var clearInvalid = function () { input.removeAttribute('aria-invalid'); input.removeAttribute('aria-describedby'); fieldError.hidden = true; };

    var showResult = function (ref) {
      notFound.hidden = true; result.hidden = true; clearInvalid();
      if (!ref.trim()) { setInvalid('Enter your order reference.'); return; }
      var o = orders.find(ref);
      if (!o) { notFound.hidden = false; setInvalid('No order found for that reference.'); return; }
      $('[data-t-ref]', result).textContent = o.ref;
      $('[data-t-date]', result).textContent = 'Placed ' + orders.date(o.date);
      $('[data-t-updated]', result).textContent = 'Last updated ' + orders.date(o.updated);
      $('[data-t-badges]', result).innerHTML = orders.badge('Order status', o.status) + orders.badge('Payment status', o.paymentStatus);
      orders.renderStepper($('[data-stepper-root]', result), o);
      $('[data-t-lines]', result).innerHTML = orders.linesHtml(o);
      $('[data-t-subtotal]', result).textContent = money(o.subtotal);
      $('[data-t-delivery]', result).textContent = o.delivery === 0 ? 'Free' : money(o.delivery);
      $('[data-t-total]', result).textContent = money(o.total);
      // never name, address, phone or email here; the estimate is shown only while the order can still arrive
      var open = o.status !== 'delivered' && o.status !== 'cancelled' && o.paymentStatus !== 'refunded';
      $('[data-t-eta]', result).hidden = !open;
      $('[data-t-eta-text]', result).textContent = 'Delivery estimate: ' + o.eta;
      $$('[data-help-ref]', result).forEach(function (n) { n.textContent = o.ref; });
      result.hidden = false;
    };

    var initial = params.get('ref');
    if (demo === 'throttle') throttle.hidden = false;
    else if (initial) { input.value = initial.toUpperCase(); showResult(initial); }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      throttle.hidden = true;
      var ref = input.value;
      var u = new URL(location.href);
      u.searchParams.set('ref', ref.trim().toUpperCase());
      u.searchParams.delete('demo');
      if (history.replaceState) history.replaceState(null, '', u.search);
      showResult(ref);
    });
  }

  /* ---------- Orders list ---------- */
  var list = $('[data-orders-page]');
  if (list) {
    $$('[data-greeting-name]').forEach(function (n) { n.textContent = user.name; });
    var data = orders.page(params.get('page'), 6);
    var empty = demo === 'empty';
    $('[data-orders-filled]').hidden = empty;
    $('[data-orders-empty]').hidden = !empty;

    var cardHtml = function (o) {
      var units = o.items.reduce(function (s, i) { return s + i.qty; }, 0);
      var thumbs = o.items.slice(0, 3).map(function (it) {
        return '<span class="media media--tile" data-placeholder style="--tone: ' + store.toneCss(it) + '"></span>';
      }).join('');
      return '<li class="order-card"><div class="order-card__head"><p class="order-card__ref"><a href="order.html?ref=' + o.ref + '">' + o.ref + '</a></p>' +
        '<p class="order-card__date">Placed ' + orders.date(o.date) + '</p></div>' +
        '<div class="order-card__badges">' + orders.badge('Order status', o.status) + orders.badge('Payment status', o.paymentStatus) + '</div>' +
        '<div class="order-card__body"><div class="order-card__items"><span class="order-card__thumbs" aria-hidden="true">' + thumbs + '</span><span>' + units + (units === 1 ? ' item' : ' items') + '</span></div>' +
        '<p class="order-card__total">' + money(o.total) + '</p></div>' +
        '<div class="order-card__actions"><a class="link-arrow" href="order.html?ref=' + o.ref + '">View order<span class="visually-hidden"> ' + o.ref + '</span> ' +
        '<svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-arrow-right"></use></svg></a></div></li>';
    };
    $('[data-order-list]', list).innerHTML = data.items.map(cardHtml).join('');

    var nav = $('[data-orders-pagination]', list);
    if (data.pages < 2) nav.hidden = true;
    else {
      var href = function (n) { return n === 1 ? 'index.html' : 'index.html?page=' + n; };
      var ico = function (n) { return '<svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-' + n + '"></use></svg>'; };
      var h = data.page > 1
        ? '<li><a class="pagination__link pagination__edge" href="' + href(data.page - 1) + '" rel="prev">' + ico('chevron-left') + '<span class="pagination__edge-label">Prev</span></a></li>'
        : '<li><span class="pagination__link pagination__edge" aria-disabled="true">' + ico('chevron-left') + '<span class="pagination__edge-label">Prev</span></span></li>';
      for (var n = 1; n <= data.pages; n++) {
        h += '<li class="pagination__num"><a class="pagination__link" href="' + href(n) + '"' + (n === data.page ? ' aria-current="page"' : '') + ' aria-label="Page ' + n + '">' + n + '</a></li>';
      }
      h += '<li class="pagination__status">Page ' + data.page + ' of ' + data.pages + '</li>';
      h += data.page < data.pages
        ? '<li><a class="pagination__link pagination__edge" href="' + href(data.page + 1) + '" rel="next"><span class="pagination__edge-label">Next</span>' + ico('chevron-right') + '</a></li>'
        : '<li><span class="pagination__link pagination__edge" aria-disabled="true"><span class="pagination__edge-label">Next</span>' + ico('chevron-right') + '</span></li>';
      $('.pagination__list', nav).innerHTML = h;
    }
  }

  /* ---------- Order detail ---------- */
  var detail = $('[data-order-page]');
  if (detail) {
    var o = orders.find(params.get('ref') || 'KYA-10234');
    $('[data-order-found]', detail).hidden = !o;
    $('[data-order-notfound]', detail).hidden = !!o;
    if (o) {
      document.title = 'Order ' + o.ref + ' | Kayaa';
      $('[data-o-ref]', detail).textContent = o.ref;
      $('[data-o-date]', detail).textContent = 'Placed ' + orders.date(o.date);
      $('[data-o-badges]', detail).innerHTML = orders.badge('Order status', o.status) + orders.badge('Payment status', o.paymentStatus);
      orders.renderStepper($('[data-stepper-root]', detail), o);
      var canReview = o.status === 'shipped' || o.status === 'delivered';
      $('[data-o-lines]', detail).innerHTML = o.items.map(function (it) {
        return '<li class="order-line"><div class="media media--tile order-line__media" data-placeholder style="--tone: ' + store.toneCss(it) + '">' +
          '<svg class="icon" aria-hidden="true" focusable="false"><use href="#i-image"></use></svg></div>' +
          '<div><p class="order-line__name">' + esc(it.name) + '</p><p class="order-line__meta">Size ' + it.sizeLabel + ' · ' + it.colourLabel + ' · Qty ' + it.qty + '</p></div>' +
          '<p class="order-line__price">' + money(it.qty * it.unitPrice) + '</p>' +
          (canReview ? '<div class="order-line__extra"><a class="review-link" href="../product.html#write-review">Write a review<span class="visually-hidden"> for ' + esc(it.name) + '</span></a></div>' : '') + '</li>';
      }).join('');
      $('[data-o-subtotal]', detail).textContent = money(o.subtotal);
      $('[data-o-delivery]', detail).textContent = o.delivery === 0 ? 'Free' : money(o.delivery);
      $('[data-o-total]', detail).textContent = money(o.total);
      $('[data-o-method]', detail).textContent = o.method;
      $('[data-o-pay-status]', detail).innerHTML = orders.badge('Payment status', o.paymentStatus);
      var a = o.address;
      $('[data-o-address]', detail).innerHTML = [a.name, a.line1, a.line2, a.city + (a.districtLabel ? ', ' + a.districtLabel : ''), a.phone].filter(Boolean).map(esc).join('<br>');
      $('[data-o-eta]', detail).textContent = 'Estimated delivery to ' + a.districtLabel + ': ' + o.eta;
      $('[data-o-eta-wrap]', detail).hidden = o.status === 'cancelled' || o.status === 'delivered' || o.paymentStatus === 'refunded';
      $('[data-o-track]', detail).setAttribute('href', '../track.html?ref=' + encodeURIComponent(o.ref));
      $('[data-o-retry]', detail).hidden = !(o.paymentStatus === 'pending' || o.paymentStatus === 'failed') || o.status === 'cancelled';
    }
  }

  /* ---------- My reviews ---------- */
  var reviews = $('[data-reviews-page]');
  if (reviews) {
    var none = demo === 'empty';
    $('[data-review-list]', reviews).hidden = none;
    $('[data-reviews-empty]', reviews).hidden = !none;
  }

  /* ---------- Profile ---------- */
  var profile = $('[data-profile-page]');
  if (profile) {
    var set = function (id, v) { var el = document.getElementById(id); if (el) el.value = v; };
    var dForm = $('#details-form');
    var pForm = $('#password-form');
    var dAlert = $('[data-profile-alert="details"]');
    var pAlert = $('[data-profile-alert="password"]');
    set('name', user.name === 'Amaya' ? 'Amaya Ranasinghe' : user.name);
    set('email', user.email);
    set('phone', '071 234 5678');
    if (demo === 'saved') dAlert.hidden = false;
    if (demo === 'errors') {
      set('name', ''); set('phone', '071123'); set('current_password', ''); set('password', 'abc'); set('password_confirmation', 'abd');
      dForm.dispatchEvent(new Event('submit', { cancelable: true }));
      pForm.dispatchEvent(new Event('submit', { cancelable: true }));
      window.scrollTo(0, 0);
    }
    dForm.addEventListener('submit', function (e) {
      if (e.defaultPrevented) return;
      e.preventDefault();
      pAlert.hidden = true; dAlert.hidden = false; dAlert.focus();
    });
    pForm.addEventListener('submit', function (e) {
      if (e.defaultPrevented) return;
      e.preventDefault();
      pForm.reset();
      dAlert.hidden = true; pAlert.hidden = false; pAlert.focus();
    });
  }
})();
