/* PROTOTYPE ONLY - delete at Blade conversion: the production cart lives on the server.
   A small demo cart in sessionStorage (in memory when storage is blocked) shared by the header counts, the cart
   drawer, the cart page and the checkout summary. Loaded before app.js on every page.

   Line item: { id, productSlug, name, colourSlug, colourLabel, sizeSlug, sizeLabel, unitPrice, wasPrice, qty, stock, tone }
   window.KayaaCart: get() totals() add(item, opts) update(id, qty) remove(id) clear() money(n) toneCss(item)
                     order.save/get/clear/nextRef (the simulated checkout order)
                     clear(silent) empties the bag; the checkout calls it when the order is created
                     session.get/set/clear (the demo signed-in flag: { name, email, verified })
   Events on document: "kayaa:cart" after any change; "kayaa:open-cart" when add() is called with { open: true, trigger }.
   ?demo= on cart.html and checkout.html (oos-line, checkout-oos, low-stock, price-changed, free-delivery, empty) is applied
   as a view over the stored items and never saved, except empty, which clears the bag. */
(function () {
  'use strict';

  var KEY = 'kayaa.proto.cart.v1';
  var ORDER_KEY = 'kayaa.proto.order.v1';
  var MAX = 10;                // quantity cap per line
  var CFG = window.KAYAA_CONFIG || {};   // tools/site-config.json via assets/js/site-config.js (Blade: view variables)
  var FREE_AT = CFG.free_shipping_over;  // free delivery from this subtotal
  var FEE = CFG.shipping_fee;            // flat delivery fee (TODO confirm it is not district-based)
  var COLOUR_TONE = { lilac: 'var(--tone-4)', cream: 'var(--swatch-cream)', sky: 'var(--sky-tint)', blush: 'var(--swatch-blush)', dove: 'var(--swatch-dove)' };

  var memory = {};
  function read(k) {
    try { var v = sessionStorage.getItem(k); if (v !== null) return v; } catch (e) { /* blocked */ }
    return Object.prototype.hasOwnProperty.call(memory, k) ? memory[k] : null;
  }
  function write(k, v) {
    try { sessionStorage.setItem(k, v); } catch (e) { memory[k] = v; }
  }
  function drop(k) {
    try { sessionStorage.removeItem(k); } catch (e) { /* blocked */ }
    delete memory[k];
  }
  var clone = function (x) { return JSON.parse(JSON.stringify(x)); };
  var money = function (n) { var p = (window.KAYAA_CONFIG && window.KAYAA_CONFIG.currency_prefix) || ''; return p + ' ' + String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ','); };
  var slugify = function (s) { return String(s).toLowerCase().replace(/&/g, 'and').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); };

  var SEED = [
    { id: 'ribbed-cotton-bodysuit|lilac|3-6m', productSlug: 'ribbed-cotton-bodysuit', name: 'Ribbed Cotton Bodysuit', colourSlug: 'lilac', colourLabel: 'Lilac', sizeSlug: '3-6m', sizeLabel: '3–6m', unitPrice: 2450, wasPrice: 2950, qty: 1, stock: 8, tone: 'lilac' },
    { id: 'bamboo-sleepsuit|cream|newborn', productSlug: 'bamboo-sleepsuit', name: 'Bamboo Sleepsuit', colourSlug: 'cream', colourLabel: 'Butter', sizeSlug: 'newborn', sizeLabel: 'Newborn', unitPrice: 3650, wasPrice: 0, qty: 1, stock: 9, tone: 'cream' }
  ];

  var page = document.body ? document.body.getAttribute('data-page') : '';
  var demo = new URLSearchParams(location.search).get('demo') || '';
  var demoPage = page === 'cart' || page === 'checkout';

  function load() {
    var raw = read(KEY);
    if (raw === null) { write(KEY, JSON.stringify(SEED)); return clone(SEED); }
    try { var v = JSON.parse(raw); return Array.isArray(v) ? v : []; } catch (e) { return []; }
  }
  function save(items) { write(KEY, JSON.stringify(items)); }
  function notify() { document.dispatchEvent(new CustomEvent('kayaa:cart')); }

  if (demoPage && demo === 'empty') write(KEY, '[]');

  // the demo view (not saved)
  function view(items) {
    items = clone(items);
    if (!demoPage || !items.length) return items;
    if ((demo === 'oos-line' || demo === 'checkout-oos') && items[0]) { items[0].stock = 0; items[0].unavailable = true; }
    if (demo === 'low-stock' && items[0]) { items[0].stock = 2; items[0].qty = Math.min(items[0].qty, 2); }
    if (demo === 'price-changed' && items[1]) { items[1].priceChanged = true; items[1].oldPrice = items[1].unitPrice + 300; }
    if (demo === 'free-delivery' && items[0]) { items[0].qty = 3; items[0].stock = Math.max(items[0].stock, 3); }
    items.forEach(function (it) { if (it.stock <= 0) it.unavailable = true; });
    return items;
  }

  function get() { return view(load()); }

  function totals() {
    var items = get();
    var count = 0, subtotal = 0, unavailable = 0;
    items.forEach(function (it) {
      count += it.qty;
      if (it.unavailable) unavailable++; else subtotal += it.qty * it.unitPrice;
    });
    var delivery = subtotal === 0 ? 0 : (subtotal >= FREE_AT ? 0 : FEE);
    return {
      lines: items.length, count: count, subtotal: subtotal, delivery: delivery, total: subtotal + delivery,
      freeAt: FREE_AT, fee: FEE, free: subtotal >= FREE_AT, away: Math.max(0, FREE_AT - subtotal), unavailable: unavailable
    };
  }

  function clampQty(qty, stock) {
    qty = parseInt(qty, 10);
    if (isNaN(qty) || qty < 1) qty = 1;
    return Math.max(1, Math.min(qty, MAX, stock > 0 ? stock : MAX));
  }

  function add(item, opts) {
    var items = load();
    var line = {
      productSlug: item.productSlug || slugify(item.name),
      name: item.name,
      colourSlug: item.colourSlug,
      colourLabel: item.colourLabel,
      sizeSlug: item.sizeSlug || String(item.sizeLabel).toLowerCase().replace(/[\u2013\u2014]/g, '-'),
      sizeLabel: item.sizeLabel,
      unitPrice: item.unitPrice,
      wasPrice: item.wasPrice || 0,
      stock: item.stock > 0 ? item.stock : 8,   // the sample quick-add sheet has no stock data
      tone: item.tone || item.colourSlug || '1'
    };
    line.id = line.productSlug + '|' + line.colourSlug + '|' + line.sizeSlug;
    var qty = item.qty || 1;
    var existing = items.filter(function (it) { return it.id === line.id; })[0];
    if (existing) {
      existing.qty = clampQty(existing.qty + qty, line.stock);
      existing.unitPrice = line.unitPrice; existing.wasPrice = line.wasPrice; existing.stock = line.stock;
    } else {
      line.qty = clampQty(qty, line.stock);
      items.push(line);
    }
    save(items);
    notify();
    if (opts && opts.open) document.dispatchEvent(new CustomEvent('kayaa:open-cart', { detail: { trigger: opts.trigger } }));
    return existing || line;
  }

  function update(id, qty) {
    var items = load();
    items.forEach(function (it) { if (it.id === id) it.qty = clampQty(qty, it.stock); });
    save(items);
    notify();
  }
  function remove(id) {
    save(load().filter(function (it) { return it.id !== id; }));
    notify();
  }
  // silent = true empties the bag without firing the change event (the checkout empties it as the order is created, while its page is still showing)
  function clear(silent) { save([]); if (!silent) notify(); }

  var order = {
    save: function (o) { write(ORDER_KEY, JSON.stringify(o)); },
    get: function () { try { return JSON.parse(read(ORDER_KEY)); } catch (e) { return null; } },
    clear: function () { drop(ORDER_KEY); },
    // Reference format KY-YYMMDD-XXXX (uppercase, max 20 characters; the backend never assumes digits only or a fixed length)
    nextRef: function () {
      var d = new Date();
      var two = function (n) { return (n < 10 ? '0' : '') + n; };
      var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
      var tail = '';
      for (var i = 0; i < 4; i++) tail += chars.charAt(Math.floor(Math.random() * chars.length));
      return 'KY-' + two(d.getFullYear() % 100) + two(d.getMonth() + 1) + two(d.getDate()) + '-' + tail;
    }
  };

  var SESSION_KEY = 'kayaa.proto.session.v1';
  var session = {
    get: function () { try { return JSON.parse(read(SESSION_KEY)); } catch (e) { return null; } },
    set: function (user) { write(SESSION_KEY, JSON.stringify(user)); },
    clear: function () { drop(SESSION_KEY); }
  };

  // small session-scoped key/value store for the other prototype scripts (cancelled orders, dismissed banners)
  var kv = { get: read, set: write, drop: drop };

  window.KayaaCart = {
    session: session, kv: kv,
    get: get, totals: totals, add: add, update: update, remove: remove, clear: clear, order: order,
    money: money, slugify: slugify, MAX: MAX, FREE_AT: FREE_AT, FEE: FEE,
    toneCss: function (item) {
      var t = String((item && (item.tone || item.colourSlug)) || '1').toLowerCase();
      return COLOUR_TONE[t] || (/^\d+$/.test(t) ? 'var(--tone-' + t + ')' : t);
    }
  };
})();
