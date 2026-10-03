/* PROTOTYPE ONLY - delete at Blade conversion: the production cart lives on the server.
   A small demo cart in sessionStorage (in memory when storage is blocked) shared by the header counts, the cart
   drawer, the cart page and the checkout summary. Loaded before app.js on every page.

   Line item: { id, productSlug, name, colourSlug, colourLabel, sizeSlug, sizeLabel, unitPrice, wasPrice, qty, stock, tone }
   window.KayaaCart: get() totals() add(item, opts) update(id, qty) remove(id) clear() money(n) toneCss(item)
                     order.save/get/clear/nextRef (the simulated checkout order)
   Events on document: "kayaa:cart" after any change; "kayaa:open-cart" when add() is called with { open: true, trigger }.
   ?demo= on cart.html and checkout.html (oos-line, checkout-oos, low-stock, price-changed, free-delivery, empty) is applied
   as a view over the stored items and never saved, except empty, which clears the bag. */
(function () {
  'use strict';

  var KEY = 'kayaa.proto.cart.v1';
  var ORDER_KEY = 'kayaa.proto.order.v1';
  var SEQ_KEY = 'kayaa.proto.orderseq.v1';
  var MAX = 10;                // quantity cap per line
  var FREE_AT = 7500;          // free delivery from this subtotal (config)
  var FEE = 450;               // flat delivery fee (config; TODO confirm it is not district-based)
  var COLOUR_TONE = { lilac: 'var(--tone-4)', cream: 'var(--swatch-cream)', sky: 'var(--sky-tint)' };

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
  var money = function (n) { return 'Rs ' + Math.round(n).toLocaleString('en-US'); };
  var slugify = function (s) { return String(s).toLowerCase().replace(/&/g, 'and').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); };

  var SEED = [
    { id: 'ruffle-sleeve-dress|lilac|3-6m', productSlug: 'ruffle-sleeve-dress', name: 'Ruffle Sleeve Dress', colourSlug: 'lilac', colourLabel: 'Lilac', sizeSlug: '3-6m', sizeLabel: '3-6M', unitPrice: 3450, wasPrice: 0, qty: 1, stock: 8, tone: 'lilac' },
    { id: 'cotton-sleepsuit|cream|0-3m', productSlug: 'cotton-sleepsuit', name: 'Cotton Sleepsuit', colourSlug: 'cream', colourLabel: 'Cream', sizeSlug: '0-3m', sizeLabel: '0-3M', unitPrice: 2850, wasPrice: 0, qty: 1, stock: 9, tone: 'cream' }
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
      sizeSlug: item.sizeSlug || String(item.sizeLabel).toLowerCase(),
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
  function clear() { save([]); notify(); }

  var order = {
    save: function (o) { write(ORDER_KEY, JSON.stringify(o)); },
    get: function () { try { return JSON.parse(read(ORDER_KEY)); } catch (e) { return null; } },
    clear: function () { drop(ORDER_KEY); },
    // TODO: real reference format (the prototype counts up from KYA-10234)
    nextRef: function () {
      var n = (parseInt(read(SEQ_KEY), 10) || 10233) + 1;
      write(SEQ_KEY, String(n));
      return 'KYA-' + n;
    }
  };

  window.KayaaCart = {
    get: get, totals: totals, add: add, update: update, remove: remove, clear: clear, order: order,
    money: money, slugify: slugify, MAX: MAX, FREE_AT: FREE_AT, FEE: FEE,
    toneCss: function (item) {
      var t = String((item && (item.tone || item.colourSlug)) || '1').toLowerCase();
      return COLOUR_TONE[t] || (/^\d+$/.test(t) ? 'var(--tone-' + t + ')' : t);
    }
  };
})();
