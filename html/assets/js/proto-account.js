/* PROTOTYPE ONLY - delete at Blade conversion: the server renders tracking, orders, reviews and the profile.
   Uses the sample orders in proto-orders.js. One file for track.html and the account pages, selected by data hooks.
   ?demo= and params:
     track.html          ?ref=KY-261001-K8D3 prefills the reference ONLY (the lookup never runs on load); ?demo=throttle.
                         The lookup needs the reference AND the mobile number (every sample order uses 071 234 5678).
     account/index.html  ?demo=empty, ?page=2
     account/order.html  ?ref=KY-261001-K8D3 (shipped), KY-260914-T5R7 delivered, KY-260910-B2W6 cancelled, KY-261003-A3F9 pending payment,
                         KY-261003-P7X2 payment failed, KY-260828-H4N8 refunded, KY-261002-W5N7 paid and waiting to be confirmed
                         (refund line in the cancel dialog), KY-260720-E8Z5 delivered with missing history dates; unknown = not found
     account/reviews.html ?demo=empty
     account/profile.html ?demo=saved | errors
     any account page    ?demo=unverified | verified switches the demo account's email verification (remembered for the session) */
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

  // demo account: ?demo=unverified | verified sets the verification flag for the session (and signs the demo user in)
  var session = store.session.get();
  if (demo === 'unverified' || demo === 'verified') {
    session = { name: (session && session.name) || 'Amaya', email: (session && session.email) || 'amaya@example.com', verified: demo === 'verified' };
    store.session.set(session);
  }
  var user = session || { name: 'Amaya', email: 'amaya@example.com', verified: true };
  var verified = user.verified !== false;

  /* ---------- Sign out (a POST form in Laravel) ---------- */
  $$('[data-logout-form]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      store.session.clear();
      location.href = '../index.html';
    });
  });

  /* ---------- "Verify your email" banner (shown by the server only while unverified; account.js remembers the dismissal) ---------- */
  $$('[data-verify-banner]').forEach(function (banner) {
    $$('[data-verify-email]', banner).forEach(function (n) { n.textContent = user.email; });
    banner.hidden = verified;
    var status = $('[data-verify-status]', banner);
    var text = $('.verify-banner__text', banner);
    var form = $('[data-resend-form]', banner);
    if (form) form.addEventListener('submit', function (e) {
      e.preventDefault();
      // announced politely, and the visible text confirms it
      text.innerHTML = 'We’ve sent another link to <strong></strong>.';
      $('strong', text).textContent = user.email;
      if (status) status.textContent = 'We’ve sent another verification link.';
    });
  });

  /* ---------- Track ---------- */
  var track = $('[data-track-page]');
  if (track) {
    var form = $('[data-track-form]', track);
    var input = $('#ref', track);
    var phoneInput = $('#phone', track);
    var notFound = $('[data-track-alert="notfound"]', track);
    var throttle = $('[data-track-alert="throttle"]', track);
    var result = $('[data-track-result]', track);

    var showResult = function (o) {
      notFound.hidden = true;
      $('[data-t-ref]', result).textContent = o.ref;
      $('[data-t-date]', result).textContent = 'Placed ' + orders.date(o.date);
      $('[data-t-updated]', result).textContent = 'Last updated ' + orders.date(o.updated);
      $('[data-t-badges]', result).innerHTML = orders.badge('Order status', o.status) + orders.badge('Payment status', o.paymentStatus) + orders.methodBadge(o);
      $('[data-t-method]', result).textContent = orders.methodLabel(o);
      orders.renderStepper($('[data-stepper-root]', result), o);   // also shows "Resume payment" when it applies
      $('[data-t-lines]', result).innerHTML = orders.linesHtml(o);
      $('[data-t-subtotal]', result).textContent = money(o.subtotal);
      $('[data-t-delivery]', result).textContent = o.delivery === 0 ? 'Free' : money(o.delivery);
      $('[data-t-total]', result).textContent = money(o.total);
      // never name, address, phone or email here; the estimate is shown only while the order can still arrive
      var open = o.status !== 'delivered' && o.status !== 'cancelled' && o.paymentStatus !== 'refunded';
      $('[data-t-eta]', result).hidden = !open;
      $('[data-t-eta-text]', result).textContent = 'Delivery estimate: ' + orders.etaText(o.eta);
      $$('[data-help-ref]', result).forEach(function (n) { n.textContent = o.ref; });
      result.hidden = false;
    };

    // ?ref= prefills the reference only: the shopper still adds the mobile number and presses Track order
    var initial = params.get('ref');
    if (initial) input.value = initial.trim().toUpperCase().slice(0, 20);
    if (demo === 'throttle') throttle.hidden = false;

    form.addEventListener('submit', function (e) {
      if (e.defaultPrevented) return;   // account.js found errors and moved focus to the summary
      e.preventDefault();
      throttle.hidden = true;
      var ref = input.value.trim().toUpperCase();
      input.value = ref;
      var u = new URL(location.href);
      u.searchParams.set('ref', ref);   // the reference only; the mobile number is never put in the URL
      u.searchParams.delete('demo');
      if (history.replaceState) history.replaceState(null, '', u.search);
      var o = orders.find(ref, phoneInput.value);
      if (!o) {
        result.hidden = true; notFound.hidden = false; notFound.focus();
        return;
      }
      showResult(o);
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
    // guest orders only join the list once the email is verified
    $('[data-orders-note]').textContent = verified
      ? 'Orders placed as a guest with this email also appear here.'
      : 'Orders you placed as a guest will appear here after you verify your email.';

    var cardHtml = function (o) {
      var units = o.items.reduce(function (s, i) { return s + i.qty; }, 0);
      var thumbs = o.items.slice(0, 3).map(function (it) {
        return '<span class="media media--tile" data-placeholder style="--tone: ' + store.toneCss(it) + '"></span>';
      }).join('');
      return '<li class="order-card"><div class="order-card__head"><p class="order-card__ref"><a href="order.html?ref=' + o.ref + '">' + o.ref + '</a></p>' +
        '<p class="order-card__date">Placed ' + orders.date(o.date) + '</p></div>' +
        '<div class="order-card__badges">' + orders.badge('Order status', o.status) + orders.badge('Payment status', o.paymentStatus) + orders.methodBadge(o) + '</div>' +
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
    var o = orders.find(params.get('ref') || 'KY-261001-K8D3');
    $('[data-order-found]', detail).hidden = !o;
    $('[data-order-notfound]', detail).hidden = !!o;
    if (o) {
      var cancelBtn = $('[data-o-cancel]', detail);
      var paint = function (cancelledNow) {
        document.title = 'Order ' + o.ref + ' | Kayaa';
        $('[data-o-ref]', detail).textContent = o.ref;
        $('[data-o-date]', detail).textContent = 'Placed ' + orders.date(o.date);
        $('[data-o-badges]', detail).innerHTML = orders.badge('Order status', o.status) + orders.badge('Payment status', o.paymentStatus) + orders.methodBadge(o);
        orders.renderStepper($('[data-stepper-root]', detail), o);
        $('[data-o-eta-wrap]', detail).hidden = o.status === 'cancelled' || o.status === 'delivered' || o.paymentStatus === 'refunded';
        $('[data-o-retry]', detail).hidden = !orders.canResume(o);   // "Resume payment"
        cancelBtn.hidden = !orders.canCancel(o);                     // "Cancel order" only while the status is pending
        if (cancelledNow) $('[data-o-cancelled]', detail).hidden = false;
      };
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
      $('[data-o-method]', detail).textContent = orders.methodLabel(o);
      $('[data-o-pay-status]', detail).innerHTML = orders.badge('Payment status', o.paymentStatus);
      var a = o.address;
      $('[data-o-address]', detail).innerHTML = [a.name, a.line1, a.line2, a.city + (a.districtLabel ? ', ' + a.districtLabel : ''), a.phone].filter(Boolean).map(esc).join('<br>');
      $('[data-o-eta]', detail).textContent = 'Estimated delivery to ' + a.districtLabel + ': ' + orders.etaText(o.eta);
      // the Track link carries the reference only (the shopper adds the mobile number there)
      $('[data-o-track]', detail).setAttribute('href', '../track.html?ref=' + encodeURIComponent(o.ref));
      paint(false);

      // cancel dialog: the refund line only when the payment was taken; confirming cancels the order (remembered for this session)
      $('[data-refund-note]', detail).hidden = o.paymentStatus !== 'paid';
      var dlg = $('#cancel-dialog', detail);
      $('[data-cancel-form]', dlg).addEventListener('submit', function (e) {
        e.preventDefault();
        orders.cancel(o.ref);
        o = orders.find(o.ref);
        paint(true);
        // focus goes to the confirmation alert once the dialog has finished closing (account.js has already returned focus by then)
        dlg.addEventListener('close', function () { $('[data-o-cancelled]', detail).focus(); }, { once: true });
        dlg.close();
      });
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
