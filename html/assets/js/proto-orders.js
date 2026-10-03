/* PROTOTYPE ONLY - delete at Blade conversion: orders come from the database.
   Twelve sample orders with stable references, plus the order stored by the simulated checkout (shown first).
   window.KayaaOrders: all() find(ref) page(n, per) money(n) date(iso) linesHtml(order) renderStepper(root, order) statusLabel
   Any other reference is not found.
   Sample references: KYA-10234 shipped, KYA-10201 delivered, KYA-10198 cancelled, KYA-10240 pending payment,
   KYA-10250 payment failed, KYA-10180 refunded (+ six more delivered orders for pagination). */
(function () {
  'use strict';

  var cart = window.KayaaCart;
  var money = cart ? cart.money : function (n) { return 'Rs ' + Math.round(n).toLocaleString('en-US'); };

  var P = {
    dress: { name: 'Ruffle Sleeve Dress', slug: 'ruffle-sleeve-dress', unitPrice: 3450, tone: 'lilac' },
    sleepsuit: { name: 'Cotton Sleepsuit', slug: 'cotton-sleepsuit', unitPrice: 2850, tone: 'cream' },
    bodysuit: { name: 'Ribbed Cotton Bodysuit', slug: 'ribbed-cotton-bodysuit', unitPrice: 1490, tone: 'sky' },
    swaddle: { name: 'Muslin Swaddle Wrap', slug: 'muslin-swaddle-wrap', unitPrice: 2250, tone: 'sky' },
    romper: { name: 'Terry Romper', slug: 'terry-romper', unitPrice: 1540, tone: 'cream' },
    bonnet: { name: 'Knit Bonnet & Booties Set', slug: 'knit-bonnet-and-booties-set', unitPrice: 1760, tone: 'cream' },
    cardigan: { name: 'Pointelle Knit Cardigan', slug: 'pointelle-knit-cardigan', unitPrice: 1120, tone: 'lilac' }
  };
  var COLOUR = { lilac: 'Lilac', cream: 'Cream', sky: 'Sky' };
  function item(key, size, qty) {
    var p = P[key];
    return { productSlug: p.slug, name: p.name, sizeLabel: size, colourLabel: COLOUR[p.tone], colourSlug: p.tone, tone: p.tone, qty: qty || 1, unitPrice: p.unitPrice };
  }
  var ADDRESS = { name: 'Amaya Ranasinghe', line1: '42 Temple Road', line2: '', city: 'Nugegoda', district: 'colombo', districtLabel: 'Colombo', phone: '071 234 5678' };

  function make(ref, date, updated, items, status, paymentStatus) {
    var subtotal = items.reduce(function (s, i) { return s + i.qty * i.unitPrice; }, 0);
    var delivery = subtotal >= 7500 ? 0 : 450;
    return {
      ref: ref, date: date, updated: updated, items: items, subtotal: subtotal, delivery: delivery, total: subtotal + delivery,
      method: 'Card - Onepay', status: status, paymentStatus: paymentStatus, address: ADDRESS, eta: '2–3 working days'
    };
  }

  var SAMPLES = [
    make('KYA-10250', '2026-10-03', '2026-10-03', [item('sleepsuit', '0-3M')], 'pending', 'failed'),
    make('KYA-10240', '2026-10-03', '2026-10-03', [item('bonnet', '0-3M'), item('cardigan', '3-6M')], 'pending', 'pending'),
    make('KYA-10234', '2026-10-01', '2026-10-03', [item('dress', '3-6M'), item('sleepsuit', '0-3M')], 'shipped', 'paid'),
    make('KYA-10201', '2026-09-14', '2026-09-19', [item('bodysuit', '3-6M', 2), item('swaddle', 'NB')], 'delivered', 'paid'),
    make('KYA-10198', '2026-09-10', '2026-09-10', [item('romper', '6-9M')], 'cancelled', 'failed'),
    make('KYA-10180', '2026-08-28', '2026-09-02', [item('dress', '3-6M')], 'cancelled', 'refunded'),
    make('KYA-10170', '2026-08-15', '2026-08-19', [item('bodysuit', '0-3M')], 'delivered', 'paid'),
    make('KYA-10165', '2026-08-02', '2026-08-06', [item('swaddle', 'NB'), item('bonnet', 'NB')], 'delivered', 'paid'),
    make('KYA-10158', '2026-07-20', '2026-07-24', [item('cardigan', '3-6M', 2)], 'delivered', 'paid'),
    make('KYA-10150', '2026-07-05', '2026-07-09', [item('sleepsuit', '3-6M')], 'delivered', 'paid'),
    make('KYA-10142', '2026-06-22', '2026-06-26', [item('romper', '3-6M')], 'delivered', 'paid'),
    make('KYA-10135', '2026-06-10', '2026-06-14', [item('dress', '6-9M'), item('bodysuit', '6-9M')], 'delivered', 'paid')
  ];

  // the order stored by the simulated checkout / thank-you flow
  function stored() {
    var o = cart && cart.order.get();
    if (!o) return null;
    var pay = (o.payment && o.payment.status) || 'pending';
    var map = { paid: ['confirmed', 'paid'], pending: ['pending', 'pending'], failed: ['pending', 'failed'], cancelled: ['cancelled', 'failed'] }[pay] || ['pending', 'pending'];
    var d = (o.createdAt || new Date().toISOString()).slice(0, 10);
    var a = o.address || {};
    return {
      ref: o.ref, date: d, updated: d, items: o.items || [], subtotal: o.totals.subtotal, delivery: o.totals.delivery, total: o.totals.total,
      method: 'Card - Onepay', status: map[0], paymentStatus: map[1],
      address: { name: a.name, line1: a.line1, line2: a.line2, city: a.city, district: a.district, districtLabel: a.districtLabel, phone: (o.contact || {}).phone },
      eta: o.eta || '2–3 working days'
    };
  }

  function all() {
    var s = stored();
    return s ? [s].concat(SAMPLES.filter(function (o) { return o.ref !== s.ref; })) : SAMPLES.slice();
  }
  function find(ref) {
    ref = String(ref || '').trim().toUpperCase();
    return all().filter(function (o) { return o.ref === ref; })[0] || null;
  }
  function page(n, per) {
    per = per || 6;
    var list = all();
    var pages = Math.max(1, Math.ceil(list.length / per));
    n = Math.max(1, Math.min(pages, parseInt(n, 10) || 1));
    return { items: list.slice((n - 1) * per, n * per), page: n, pages: pages, total: list.length };
  }

  var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  function date(iso) { var p = iso.split('-'); return parseInt(p[2], 10) + ' ' + MONTHS[parseInt(p[1], 10) - 1] + ' ' + p[0]; }

  var STATUS_LABEL = { pending: 'Pending', confirmed: 'Confirmed', shipped: 'Shipped', delivered: 'Delivered', cancelled: 'Cancelled', paid: 'Paid', failed: 'Failed', refunded: 'Refunded' };
  function badge(kind, value) {
    return '<span class="status status--' + value + '"><span class="visually-hidden">' + kind + ': </span>' + STATUS_LABEL[value] + '</span>';
  }

  function linesHtml(o) {
    return o.items.map(function (it) {
      var tone = cart ? cart.toneCss(it) : '';
      return '<li class="order-line"><div class="media media--tile order-line__media" data-placeholder style="--tone: ' + tone + '">' +
        '<svg class="icon" aria-hidden="true" focusable="false"><use href="#i-image"></use></svg></div>' +
        '<div><p class="order-line__name">' + it.name.replace(/&/g, '&amp;') + '</p><p class="order-line__meta">Size ' + it.sizeLabel + ' · ' + it.colourLabel + ' · Qty ' + it.qty + '</p></div>' +
        '<p class="order-line__price">' + money(it.qty * it.unitPrice) + '</p></li>';
    }).join('');
  }

  /* ---------- Status stepper ----------
     root holds: [data-stepper] (an <ol>), [data-stepper-note], [data-order-banner]. History timestamps are not shown (TODO: the backend may not store them). */
  var STEPS = [['Order placed', 'package'], ['Confirmed', 'check'], ['Shipped', 'truck'], ['Delivered', 'home']];
  var icon = function (n) { return '<svg class="icon" aria-hidden="true" focusable="false"><use href="#i-' + n + '"></use></svg>'; };

  function renderStepper(root, o) {
    var ol = root.querySelector('[data-stepper]');
    var note = root.querySelector('[data-stepper-note]');
    var banner = root.querySelector('[data-order-banner]');
    var retry = root.querySelector('[data-retry-payment]');
    ol.hidden = false; note.hidden = true; banner.hidden = true; if (retry) retry.hidden = true;

    if (o.paymentStatus === 'refunded' || o.status === 'cancelled') {
      var refunded = o.paymentStatus === 'refunded';
      ol.hidden = true;
      banner.hidden = false;
      banner.className = 'alert ' + (refunded ? 'alert--info' : 'alert--error');
      banner.innerHTML = icon(refunded ? 'rotate-ccw' : 'x') + '<div><p class="alert__title">' + (refunded ? 'This order was refunded' : 'This order was cancelled') + '</p>' +
        (refunded ? '<p>' + badge('Payment status', 'refunded') + '</p>' : '') + '</div>';
      return;
    }
    var idx = { pending: 0, confirmed: 1, shipped: 2, delivered: 3 }[o.status];
    var states = STEPS.map(function (s, i) { return o.status === 'delivered' ? 'done' : (i < idx ? 'done' : (i === idx ? 'current' : 'upcoming')); });
    if (o.status === 'pending' && o.paymentStatus === 'failed') {
      states[0] = 'error';
      note.hidden = false; note.textContent = 'Payment failed. Your items are held for a short time.';
      if (retry) retry.hidden = false;
    } else if (o.status === 'pending') {
      note.hidden = false; note.textContent = 'Waiting for payment confirmation';
    }
    var suffix = { done: ' (completed)', current: ' (current step)', upcoming: ' (upcoming)', error: ' (payment failed)' };
    ol.innerHTML = STEPS.map(function (s, i) {
      var st = states[i];
      var ic = st === 'done' ? 'check' : st === 'error' ? 'x' : s[1];
      var current = (st === 'current' || (o.status === 'delivered' && i === 3)) ? ' aria-current="step"' : '';
      return '<li class="stepper__step is-' + st + '"' + current + '><span class="stepper__icon">' + icon(ic) + '</span><span class="stepper__label">' + s[0] + '<span class="visually-hidden">' + suffix[st] + '</span></span></li>';
    }).join('');
  }

  window.KayaaOrders = { all: all, find: find, page: page, money: money, date: date, badge: badge, linesHtml: linesHtml, renderStepper: renderStepper, STATUS_LABEL: STATUS_LABEL };
})();
