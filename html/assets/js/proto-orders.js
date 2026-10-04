/* PROTOTYPE ONLY - delete at Blade conversion: orders come from the database.
   Thirteen sample orders with stable references (format KY-YYMMDD-XXXX), plus the order stored by the simulated checkout (shown first).
   window.KayaaOrders: all() find(ref, phone) page(n, per) money(n) date(iso) badge(kind, value) linesHtml(order)
                       renderStepper(root, order) canResume(order) canCancel(order) cancel(ref) STATUS_LABEL
   find(ref) matches the reference only (the account pages); find(ref, phone) needs the reference AND the mobile number (track).
   Any other reference is not found. Every sample order was placed with the mobile number 071 234 5678.
   Sample references: KY-261001-K8D3 shipped, KY-260914-T5R7 delivered, KY-260910-B2W6 cancelled, KY-261003-A3F9 pending payment,
   KY-261003-P7X2 payment failed, KY-260828-H4N8 refunded, KY-261002-W5N7 paid and waiting to be confirmed (shows the refund line in
   the cancel dialog), KY-260720-E8Z5 delivered with missing history dates (the stepper shows no date for those steps),
   plus five more delivered orders for pagination.
   Cancelling an order on the order page is remembered for the session (key kayaa.proto.cancelled.v1). */
(function () {
  'use strict';

  var cart = window.KayaaCart;
  var money = cart ? cart.money : function (n) { return 'Rs ' + Math.round(n).toLocaleString('en-US'); };
  var CFG = window.KAYAA_CONFIG || {};
  var ONLINE = 'Card - Onepay';
  var CANCELLED_KEY = 'kayaa.proto.cancelled.v1';

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

  // dates: one per step when known (placed, paid or confirmed, shipped, delivered) plus cancelled; a missing key means "not known"
  function make(ref, date, updated, items, status, paymentStatus, dates) {
    var subtotal = items.reduce(function (s, i) { return s + i.qty * i.unitPrice; }, 0);
    var delivery = subtotal >= CFG.free_shipping_over ? 0 : CFG.shipping_fee;
    return {
      ref: ref, date: date, updated: updated, items: items, subtotal: subtotal, delivery: delivery, total: subtotal + delivery,
      method: ONLINE, status: status, paymentStatus: paymentStatus, address: ADDRESS, eta: { min: 2, max: 3 }, dates: dates || { placed: date }
    };
  }
  function full(placed, shipped, delivered) { return { placed: placed, confirmed: placed, shipped: shipped, delivered: delivered }; }

  var SAMPLES = [
    make('KY-261003-P7X2', '2026-10-03', '2026-10-03', [item('sleepsuit', '0-3M')], 'pending', 'failed'),
    make('KY-261003-A3F9', '2026-10-03', '2026-10-03', [item('bonnet', '0-3M'), item('cardigan', '3-6M')], 'pending', 'pending'),
    make('KY-261002-W5N7', '2026-10-02', '2026-10-02', [item('romper', '3-6M', 2)], 'pending', 'paid'),
    make('KY-261001-K8D3', '2026-10-01', '2026-10-03', [item('dress', '3-6M'), item('sleepsuit', '0-3M')], 'shipped', 'paid', { placed: '2026-10-01', confirmed: '2026-10-01', shipped: '2026-10-03' }),
    make('KY-260914-T5R7', '2026-09-14', '2026-09-19', [item('bodysuit', '3-6M', 2), item('swaddle', 'NB')], 'delivered', 'paid', full('2026-09-14', '2026-09-16', '2026-09-19')),
    make('KY-260910-B2W6', '2026-09-10', '2026-09-10', [item('romper', '6-9M')], 'cancelled', 'failed', { placed: '2026-09-10', cancelled: '2026-09-10' }),
    make('KY-260828-H4N8', '2026-08-28', '2026-09-02', [item('dress', '3-6M')], 'cancelled', 'refunded', { placed: '2026-08-28', confirmed: '2026-08-28', cancelled: '2026-09-02' }),
    make('KY-260815-C6Y1', '2026-08-15', '2026-08-19', [item('bodysuit', '0-3M')], 'delivered', 'paid', full('2026-08-15', '2026-08-17', '2026-08-19')),
    make('KY-260802-J3V9', '2026-08-02', '2026-08-06', [item('swaddle', 'NB'), item('bonnet', 'NB')], 'delivered', 'paid', full('2026-08-02', '2026-08-04', '2026-08-06')),
    // older order: the backend has no history for the confirmed and shipped steps, so the stepper shows no date for them
    make('KY-260720-E8Z5', '2026-07-20', '2026-07-24', [item('cardigan', '3-6M', 2)], 'delivered', 'paid', { placed: '2026-07-20', delivered: '2026-07-24' }),
    make('KY-260705-U2G4', '2026-07-05', '2026-07-09', [item('sleepsuit', '3-6M')], 'delivered', 'paid', full('2026-07-05', '2026-07-07', '2026-07-09')),
    make('KY-260622-M7S6', '2026-06-22', '2026-06-26', [item('romper', '3-6M')], 'delivered', 'paid', full('2026-06-22', '2026-06-24', '2026-06-26')),
    make('KY-260610-D9L3', '2026-06-10', '2026-06-14', [item('dress', '6-9M'), item('bodysuit', '6-9M')], 'delivered', 'paid', full('2026-06-10', '2026-06-12', '2026-06-14'))
  ];

  // the order stored by the simulated checkout / thank-you flow
  function stored() {
    var o = cart && cart.order.get();
    if (!o) return null;
    var pay = (o.payment && o.payment.status) || 'pending';
    var map = { paid: ['confirmed', 'paid'], pending: ['pending', 'pending'], failed: ['pending', 'failed'], cancelled: ['cancelled', 'failed'] }[pay] || ['pending', 'pending'];
    var d = (o.createdAt || new Date().toISOString()).slice(0, 10);
    var a = o.address || {};
    var dates = { placed: d };
    if (map[0] === 'confirmed') dates.confirmed = d;
    if (map[0] === 'cancelled') dates.cancelled = d;
    return {
      ref: o.ref, date: d, updated: d, items: o.items || [], subtotal: o.totals.subtotal, delivery: o.totals.delivery, total: o.totals.total,
      method: ONLINE, status: map[0], paymentStatus: map[1],
      address: { name: a.name, line1: a.line1, line2: a.line2, city: a.city, district: a.district, districtLabel: a.districtLabel, phone: (o.contact || {}).phone },
      eta: o.eta || { min: 2, max: 3 }, dates: dates
    };
  }

  // orders cancelled on the order page during this session
  function cancelledRefs() {
    try { return JSON.parse((cart && cart.kv.get(CANCELLED_KEY)) || '[]'); } catch (e) { return []; }
  }
  function today() { return new Date().toISOString().slice(0, 10); }
  function applyCancel(o) {
    if (o.status === 'cancelled' || cancelledRefs().indexOf(o.ref) === -1) return o;
    var c = JSON.parse(JSON.stringify(o));
    c.status = 'cancelled'; c.updated = today(); c.dates.cancelled = today();
    return c;
  }
  function cancel(ref) {
    var list = cancelledRefs();
    if (list.indexOf(ref) === -1) list.push(ref);
    if (cart) cart.kv.set(CANCELLED_KEY, JSON.stringify(list));
  }

  function all() {
    var s = stored();
    var list = s ? [s].concat(SAMPLES.filter(function (o) { return o.ref !== s.ref; })) : SAMPLES.slice();
    return list.map(applyCancel);
  }

  // 071 234 5678, 0712345678 and +94 71 234 5678 are the same number
  function digits(phone) {
    var d = String(phone || '').replace(/\D/g, '');
    if (d.indexOf('94') === 0 && d.length === 11) d = '0' + d.slice(2);
    return d;
  }
  function find(ref, phone) {
    ref = String(ref || '').trim().toUpperCase();
    var hit = all().filter(function (o) { return o.ref === ref; })[0] || null;
    if (hit && phone !== undefined && digits(phone) !== digits(hit.address.phone)) return null;
    return hit;
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
  function etaText(eta) { return typeof eta === 'string' ? eta : window.Kayaa.formatEta(eta.min, eta.max); }

  var STATUS_LABEL = { pending: 'Pending', confirmed: 'Confirmed', shipped: 'Shipped', delivered: 'Delivered', cancelled: 'Cancelled', paid: 'Paid', failed: 'Failed', refunded: 'Refunded' };
  function badge(kind, value) {
    return '<span class="status status--' + value + '"><span class="visually-hidden">' + kind + ': </span>' + STATUS_LABEL[value] + '</span>';
  }

  // "Resume payment": only while the payment is pending or failed, the method is the online gateway and the order is not cancelled
  function canResume(o) {
    return (o.paymentStatus === 'pending' || o.paymentStatus === 'failed') && o.method === ONLINE && o.status !== 'cancelled';
  }
  // "Cancel order": only while the order status is pending
  function canCancel(o) { return o.status === 'pending'; }

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
     root holds: [data-stepper] (an <ol>), [data-stepper-note], [data-order-banner], optional [data-retry-payment] ("Resume payment").
     Each step shows one date when the order has it (placed, paid or confirmed, shipped, delivered) and nothing when it has not.
     The cancelled banner shows its date when present. */
  var STEPS = [['Order placed', 'package', 'placed'], ['Confirmed', 'check', 'confirmed'], ['Shipped', 'truck', 'shipped'], ['Delivered', 'home', 'delivered']];
  var icon = function (n) { return '<svg class="icon" aria-hidden="true" focusable="false"><use href="#i-' + n + '"></use></svg>'; };

  function renderStepper(root, o) {
    var ol = root.querySelector('[data-stepper]');
    var note = root.querySelector('[data-stepper-note]');
    var banner = root.querySelector('[data-order-banner]');
    var retry = root.querySelector('[data-retry-payment]');
    var dates = o.dates || {};
    ol.hidden = false; note.hidden = true; banner.hidden = true; if (retry) retry.hidden = !canResume(o);

    if (o.paymentStatus === 'refunded' || o.status === 'cancelled') {
      var refunded = o.paymentStatus === 'refunded';
      ol.hidden = true;
      banner.hidden = false;
      banner.className = 'alert ' + (refunded ? 'alert--info' : 'alert--error');
      banner.innerHTML = icon(refunded ? 'rotate-ccw' : 'x') + '<div><p class="alert__title">' + (refunded ? 'This order was refunded' : 'This order was cancelled') + '</p>' +
        (dates.cancelled ? '<p>Cancelled on ' + date(dates.cancelled) + '</p>' : '') +
        (refunded ? '<p>' + badge('Payment status', 'refunded') + '</p>' : '') + '</div>';
      return;
    }
    var idx = { pending: 0, confirmed: 1, shipped: 2, delivered: 3 }[o.status];
    if (o.status === 'pending' && o.paymentStatus === 'paid') idx = 1;   // paid, waiting to be confirmed
    var states = STEPS.map(function (s, i) { return o.status === 'delivered' ? 'done' : (i < idx ? 'done' : (i === idx ? 'current' : 'upcoming')); });
    if (o.status === 'pending' && o.paymentStatus === 'failed') {
      states[0] = 'error';
      note.hidden = false; note.textContent = 'Payment failed. If you were charged, contact us with your order reference.';
    } else if (o.status === 'pending' && o.paymentStatus === 'paid') {
      note.hidden = false; note.textContent = 'Payment received. We are confirming your order.';
    } else if (o.status === 'pending') {
      note.hidden = false; note.textContent = 'Waiting for payment confirmation';
    }
    var suffix = { done: ' (completed)', current: ' (current step)', upcoming: ' (upcoming)', error: ' (payment failed)' };
    ol.innerHTML = STEPS.map(function (s, i) {
      var st = states[i];
      var ic = st === 'done' ? 'check' : st === 'error' ? 'x' : s[1];
      var current = (st === 'current' || (o.status === 'delivered' && i === 3)) ? ' aria-current="step"' : '';
      // a date only on a step that has happened; a step with no known date shows none
      var d = (st === 'done' || st === 'error' || (st === 'current' && i > 0)) && dates[s[2]] && !(o.status === 'pending' && o.paymentStatus === 'paid' && i === 1) ? '<span class="stepper__date">' + date(dates[s[2]]) + '</span>' : '';
      if (i === 0 && dates.placed) d = '<span class="stepper__date">' + date(dates.placed) + '</span>';
      return '<li class="stepper__step is-' + st + '"' + current + '><span class="stepper__icon">' + icon(ic) + '</span><span class="stepper__label">' + s[0] + '<span class="visually-hidden">' + suffix[st] + '</span>' + d + '</span></li>';
    }).join('');
  }

  window.KayaaOrders = {
    all: all, find: find, page: page, money: money, date: date, badge: badge, linesHtml: linesHtml, renderStepper: renderStepper,
    canResume: canResume, canCancel: canCancel, cancel: cancel, etaText: etaText, STATUS_LABEL: STATUS_LABEL
  };
})();
