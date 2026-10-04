/* PROTOTYPE ONLY - delete at Blade conversion.
   In production this page is reached ONLY through a signed, expiring link supplied by the backend; it is never built from the
   order reference. ?state= is the demo switch: paid (default), pending, failed, cancelled, expired.
   ?demo=emails-on shows the "A confirmation has been sent to {email}" line (in production it needs order_emails_enabled in the site
   config AND an email on the order; it is hidden otherwise).
   Fills the order from sessionStorage (a sample order when none exists). The bag was already emptied when the order was created. */
(function () {
  'use strict';

  var page = document.querySelector('[data-thanks-page]');
  var store = window.KayaaCart;
  if (!page || !store) return;

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var money = store.money;

  var STATES = {
    paid: { label: 'Paid', title: 'Order confirmed' },
    pending: { label: 'Pending', title: 'Confirming your payment' },
    failed: { label: 'Failed', title: 'Payment failed' },
    cancelled: { label: 'Cancelled', title: 'Order cancelled' },
    expired: { label: '', title: 'Confirmation link expired' }
  };
  var params = new URLSearchParams(location.search);
  var state = params.get('state');
  if (!STATES[state]) state = 'paid';
  var cfg = window.KAYAA_CONFIG || {};

  var order = store.order.get();
  var sample = false;
  if (!order) {
    sample = true;
    order = {
      ref: 'KY-261003-A3F9', signedIn: false,
      items: [
        { name: 'Ruffle Sleeve Dress', sizeLabel: '3-6M', colourLabel: 'Lilac', colourSlug: 'lilac', tone: 'lilac', qty: 1, unitPrice: 3450 },
        { name: 'Cotton Sleepsuit', sizeLabel: '0-3M', colourLabel: 'Cream', colourSlug: 'cream', tone: 'cream', qty: 1, unitPrice: 2850 }
      ],
      totals: { subtotal: 6300, delivery: cfg.shipping_fee, total: 6300 + cfg.shipping_fee },
      contact: { email: 'amaya@example.com', phone: '071 234 5678' },
      address: { name: 'Amaya Ranasinghe', line1: '42 Temple Road', line2: '', city: 'Nugegoda', district: 'colombo', districtLabel: 'Colombo' },
      eta: { min: 2, max: 3 }
    };
  }

  // state block
  $$('[data-state-block]', page).forEach(function (b) { b.hidden = b.getAttribute('data-state-block') !== state; });
  document.title = STATES[state].title + ' | Kayaa';

  // the bag is already empty in every outcome (the order exists), so the minimal header offers Continue shopping, never Back to bag
  var back = document.querySelector('[data-back-link]');
  if (back) {
    back.setAttribute('href', 'shop.html');
    var label = back.querySelector('[data-back-label]');
    if (label) label.textContent = 'Continue shopping';
  }

  // an expired link shows no order details at all
  var details = $('[data-thanks-details]', page);
  if (state === 'expired') { details.hidden = true; return; }

  // common content
  var first = (order.address.name || 'there').split(/\s+/)[0];
  $$('[data-customer-name]', page).forEach(function (n) { n.textContent = first; });
  $$('[data-order-ref]', page).forEach(function (n) { n.textContent = order.ref; });

  // confirmation email line: only when order emails are enabled AND the guest gave an email; never claim email otherwise
  var emailLine = $('[data-email-line]', page);
  var email = (order.contact && order.contact.email) || '';
  if (emailLine && email && (cfg.order_emails_enabled || params.get('demo') === 'emails-on')) {
    $('[data-confirm-email]', emailLine).textContent = email;
    emailLine.hidden = false;
  }

  // keep the outcome with the order so My orders and /track show the same status
  if (!sample) { order.payment = order.payment || {}; order.payment.status = state; store.order.save(order); }

  // the Track link carries the reference only (the track page prefills it; the shopper adds the mobile number)
  $$('[data-track-link]', page).forEach(function (a) { a.setAttribute('href', 'track.html?ref=' + encodeURIComponent(order.ref)); });

  $('[data-order-lines]', page).innerHTML = order.items.map(function (it) {
    return '<li class="order-line"><div class="media media--tile order-line__media" data-placeholder style="--tone: ' + store.toneCss(it) + '">' +
      '<svg class="icon" aria-hidden="true" focusable="false"><use href="#i-image"></use></svg></div>' +
      '<div><p class="order-line__name">' + it.name + '</p><p class="order-line__meta">Size ' + it.sizeLabel + ' · ' + it.colourLabel + ' · Qty ' + it.qty + '</p></div>' +
      '<p class="order-line__price">' + money(it.qty * it.unitPrice) + '</p></li>';
  }).join('');
  $('[data-order-subtotal]', page).textContent = money(order.totals.subtotal);
  $('[data-order-delivery]', page).textContent = order.totals.delivery === 0 ? 'Free' : money(order.totals.delivery);
  $('[data-order-total]', page).textContent = money(order.totals.total);

  var a = order.address;
  var lines = [a.name, a.line1, a.line2, a.city + (a.districtLabel ? ', ' + a.districtLabel : '')].filter(Boolean);
  $('[data-order-address]', page).innerHTML = lines.map(function (l) { return String(l).replace(/&/g, '&amp;').replace(/</g, '&lt;'); }).join('<br>') +
    (order.contact.phone ? '<br>' + order.contact.phone : '');
  var eta = $('[data-order-eta]', page);
  if (order.eta && window.Kayaa) eta.textContent = 'Estimated delivery to ' + a.districtLabel + ': ' + window.Kayaa.formatEta(order.eta.min, order.eta.max);
  else eta.parentNode.hidden = true;

  // payment line
  var badge = $('[data-payment-status]', page);
  badge.className = 'status status--' + (state === 'paid' ? 'paid' : state);
  badge.textContent = STATES[state].label;

  // guests who paid get a soft prompt to create an account
  $('[data-account-card]', page).hidden = !(state === 'paid' && !order.signedIn);
})();
