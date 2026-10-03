/* PROTOTYPE ONLY - delete at Blade conversion.
   Shows the state block for ?state= (paid default, pending, failed, cancelled), fills the order from sessionStorage
   (a sample order when none exists) and clears the bag on paid and pending (never on failed or cancelled). */
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
    cancelled: { label: 'Cancelled', title: 'Payment cancelled' }
  };
  var state = new URLSearchParams(location.search).get('state');
  if (!STATES[state]) state = 'paid';

  var order = store.order.get();
  var sample = false;
  if (!order) {
    sample = true;
    var seed = store.get();
    var t = store.totals();
    order = {
      ref: 'KYA-10234', signedIn: false, items: seed, totals: t,
      contact: { email: 'amaya@example.com', phone: '071 234 5678' },
      address: { name: 'Amaya Ranasinghe', line1: '42 Temple Road', line2: '', city: 'Nugegoda', district: 'colombo', districtLabel: 'Colombo' },
      eta: '2–3 working days'
    };
  }

  // state block
  $$('[data-state-block]', page).forEach(function (b) { b.hidden = b.getAttribute('data-state-block') !== state; });
  document.title = STATES[state].title + ' | Kayaa';

  // common content
  var first = (order.address.name || 'there').split(/\s+/)[0];
  $$('[data-customer-name]', page).forEach(function (n) { n.textContent = first; });
  $$('[data-order-ref]', page).forEach(function (n) { n.textContent = order.ref; });
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
  if (order.eta) eta.textContent = 'Estimated delivery to ' + a.districtLabel + ': ' + order.eta;
  else eta.parentNode.hidden = true;

  // payment line
  var badge = $('[data-payment-status]', page);
  badge.className = 'status status--' + (state === 'paid' ? 'paid' : state);
  badge.textContent = STATES[state].label;

  // guests who paid get a soft prompt to create an account
  $('[data-account-card]', page).hidden = !(state === 'paid' && !order.signedIn);

  // the bag is cleared on paid and pending only, and only for a real (simulated) order, so visiting the
  // review links with the sample order does not wipe the demo bag
  if (!sample && (state === 'paid' || state === 'pending')) store.clear();
})();
