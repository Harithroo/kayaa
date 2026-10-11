/* PROTOTYPE ONLY - delete at Blade conversion: the wishlist is stored by the backend.
   The wishlist (a list of product slugs) lives in sessionStorage for this demo.
   - product.html: the Save button ([data-wishlist-toggle]) toggles the product; it reads "Save" or "Saved" (aria-pressed).
   - wishlist.html: shows the saved products with Remove and Choose size (opens the quick add sheet). The first visit starts with
     three saved products; ?demo=empty shows the empty state.
   TODO: guest wishlists (browser or session) are an open question for the backend dev. */
(function () {
  'use strict';

  var store = window.KayaaCart;
  if (!store) return;
  var KEY = 'kayaa.proto.wishlist.v1';
  var SEED = ['ribbed-cotton-bodysuit', 'cotton-kurta-set', 'bamboo-sleepsuit'];
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  function read(seed) {
    var raw = store.kv.get(KEY);
    if (raw === null) { if (seed) { store.kv.set(KEY, JSON.stringify(SEED)); return SEED.slice(); } return []; }
    try { var v = JSON.parse(raw); return Array.isArray(v) ? v : []; } catch (e) { return []; }
  }
  function write(list) { store.kv.set(KEY, JSON.stringify(list)); }

  /* ---------- product page: Save toggle ---------- */
  var toggle = $('[data-wishlist-toggle]');
  if (toggle) {
    var slug = toggle.getAttribute('data-slug');
    var label = $('[data-save-label]', toggle);
    var paint = function () {
      var saved = read(false).indexOf(slug) !== -1;
      toggle.setAttribute('aria-pressed', saved ? 'true' : 'false');
      label.textContent = saved ? 'Saved' : 'Save';
    };
    paint();
    toggle.addEventListener('click', function () {
      var list = read(false);
      var i = list.indexOf(slug);
      if (i === -1) list.push(slug); else list.splice(i, 1);
      write(list);
      paint();
    });
  }

  /* ---------- wishlist page ---------- */
  var page = $('[data-wishlist-page]');
  if (!page) return;
  var grid = $('[data-wishlist-grid]', page);
  var filled = $('[data-wishlist-filled]', page);
  var empty = $('[data-wishlist-empty]', page);
  var count = $('[data-wishlist-count]', page);
  var status = $('[data-wishlist-status]', page);
  var forcedEmpty = new URLSearchParams(location.search).get('demo') === 'empty';
  var cards = $$('[data-product]', grid);
  var bySlug = {};
  cards.forEach(function (li) { bySlug[li.getAttribute('data-slug')] = li; });

  // Remove and Choose size under every card (Choose size reuses the card's quick add data)
  cards.forEach(function (li) {
    var quick = $('.product-card__quick', li);
    var name = li.getAttribute('data-name');
    var box = document.createElement('div');
    box.className = 'wishlist-actions';
    var html = '<button type="button" class="btn btn--secondary btn--sm" data-wishlist-remove>Remove<span class="visually-hidden"> ' + name.replace(/&/g, '&amp;') + ' from your wishlist</span></button>';
    if (quick) html += '<button type="button" class="btn btn--primary btn--sm" data-choose-size>Choose size<span class="visually-hidden"> for ' + name.replace(/&/g, '&amp;') + '</span></button>';
    box.innerHTML = html;
    $('.product-card__info', li).appendChild(box);
    var choose = $('[data-choose-size]', box);
    if (choose) {
      ['data-slug', 'data-name', 'data-price', 'data-was', 'data-tone', 'data-sizes', 'data-colours', 'aria-haspopup'].forEach(function (a) {
        if (quick.hasAttribute(a)) choose.setAttribute(a, quick.getAttribute(a));
      });
      choose.setAttribute('data-quick-add', '');
    }
  });

  function render() {
    var list = forcedEmpty ? [] : read(true).filter(function (s) { return bySlug[s]; });
    cards.forEach(function (li) { li.removeAttribute('data-saved'); });
    list.forEach(function (s) { grid.appendChild(bySlug[s]); bySlug[s].setAttribute('data-saved', ''); });
    filled.hidden = !list.length;
    empty.hidden = list.length > 0;
    count.textContent = list.length ? list.length + (list.length === 1 ? ' saved product' : ' saved products') : 'Products you save are kept here.';
    return list;
  }
  render();

  grid.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-wishlist-remove]');
    if (!btn) return;
    var li = btn.closest('[data-product]');
    var slug = li.getAttribute('data-slug');
    var name = li.getAttribute('data-name');
    var visible = $$('[data-product][data-saved]', grid);
    var next = visible[visible.indexOf(li) + 1] || visible[visible.indexOf(li) - 1];
    write(read(true).filter(function (s) { return s !== slug; }));
    render();
    status.textContent = name + ' removed from your wishlist.';
    // focus goes to the next card's Remove button, or to the empty state heading
    var target = next && next !== li && $('[data-wishlist-remove]', next);
    if (target) target.focus();
    else { var h = $('.empty-state__title', empty); if (h) h.focus(); }
  });
})();
