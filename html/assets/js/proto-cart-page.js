/* PROTOTYPE ONLY - delete at Blade conversion.
   Renders the cart page from the demo cart (proto-cart.js) and handles the update/remove forms in place.
   In Laravel the server renders the lines and the forms post to the backend.
   ?demo=: empty, oos-line, low-stock, price-changed, free-delivery, checkout-oos (applied by proto-cart.js; the
   notice for checkout-oos is shown here). */
(function () {
  'use strict';

  var page = document.querySelector('[data-cart-page]');
  var store = window.KayaaCart;
  if (!page || !store) return;

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var money = store.money;
  var LOW = parseInt(page.getAttribute('data-low-stock-threshold'), 10) || 5;
  var demo = new URLSearchParams(location.search).get('demo') || '';

  var list = $('[data-cart-lines]', page);
  var tpl = $('[data-cart-line-template]');   // the template sits in <main>, outside the page container
  var filled = $('[data-cart-filled]', page);
  var empty = $('[data-cart-empty]', page);
  var notice = $('[data-cart-notice]', page);

  function fill(li, it) {
    li.setAttribute('data-id', it.id);
    li.classList.toggle('is-unavailable', !!it.unavailable);
    $('[data-line-media]', li).style.setProperty('--tone', store.toneCss(it));
    $('[data-line-name]', li).textContent = it.name;
    $('[data-line-meta]', li).textContent = 'Size ' + it.sizeLabel + ' · ' + it.colourLabel;
    var was = it.wasPrice > it.unitPrice ? it.wasPrice : 0;
    $('[data-line-unit]', li).innerHTML = '<span class="price__now">' + money(it.unitPrice) + '</span>' +
      (was ? ' <s class="price__was"><span class="visually-hidden">Was </span>' + money(was) + '</s>' : '');
    $('[data-line-total]', li).textContent = money(it.qty * it.unitPrice);
    $('[data-line-remove-label]', li).textContent = ' ' + it.name;
    $('[name="line"]', li).value = it.id;
    $$('[name="line"]', li).forEach(function (n) { n.value = it.id; });

    // line states
    var note = $('[data-line-note]', li);
    note.className = 'cart-line__note';
    note.hidden = true;
    var noteText = $('[data-line-note-text]', li);
    if (it.unavailable) {
      note.hidden = false; note.classList.add('cart-line__note--out');
      $('use', note).setAttribute('href', '#i-circle-alert');
      noteText.textContent = 'No longer available. Remove it to continue to checkout.';
    } else if (it.stock > 0 && it.stock <= LOW) {
      note.hidden = false;
      $('use', note).setAttribute('href', '#i-info');
      noteText.textContent = 'Only ' + it.stock + ' left';
    }
    $('[data-line-alert]', li).hidden = !it.priceChanged;

    // stepper, capped at stock
    var group = $('[data-qty]', li);
    var max = Math.max(1, Math.min(store.MAX, it.stock || store.MAX));
    group.setAttribute('data-qty-max', String(max));
    var input = $('[data-qty-input]', li);
    if (document.activeElement !== input) input.value = it.qty;
    input.disabled = !!it.unavailable;
    $('[data-qty-minus]', li).disabled = !!it.unavailable || it.qty <= 1;
    $('[data-qty-plus]', li).disabled = !!it.unavailable || it.qty >= max;
  }

  function render() {
    var items = store.get();
    var t = store.totals();

    $('[data-cart-title]', page).textContent = t.lines ? 'Your bag (' + t.count + (t.count === 1 ? ' item' : ' items') + ')' : 'Your bag';
    filled.hidden = !t.lines;
    empty.hidden = !!t.lines;
    notice.hidden = demo !== 'checkout-oos' || !t.lines;

    var byId = {};
    $$('[data-cart-line]', list).forEach(function (li) {
      var id = li.getAttribute('data-id');
      if (id) byId[id] = li; else li.parentNode.removeChild(li);   // static sample lines in the markup
    });
    items.forEach(function (it) {
      var li = byId[it.id];
      if (!li) { li = tpl.content.firstElementChild.cloneNode(true); list.appendChild(li); }
      delete byId[it.id];
      fill(li, it);
    });
    Object.keys(byId).forEach(function (id) { byId[id].parentNode.removeChild(byId[id]); });

    // totals
    $$('[data-cart-subtotal]', page).forEach(function (n) { n.textContent = money(t.subtotal); });
    $$('[data-cart-delivery]', page).forEach(function (n) { n.textContent = t.subtotal > 0 && t.delivery === 0 ? 'Free' : money(t.delivery); });
    $$('[data-cart-total]', page.parentNode).forEach(function (n) { n.textContent = money(t.total); });
    $$('[data-cart-count-text]', page.parentNode).forEach(function (n) { n.textContent = t.count + (t.count === 1 ? ' item' : ' items'); });
    var bar = $('[data-cart-progress]', page);
    var text = $('[data-cart-progress-text]', page);
    if (bar) { bar.max = t.freeAt; bar.value = Math.min(t.subtotal, t.freeAt); bar.textContent = Math.round(Math.min(t.subtotal / t.freeAt, 1) * 100) + '%'; }
    if (text) text.textContent = t.free ? 'You’ve unlocked free delivery' : money(t.away) + ' away from free delivery';

    // checkout is blocked while a line is unavailable
    var blocked = t.unavailable > 0 || t.subtotal === 0;
    $$('[data-checkout-link]', page.parentNode).forEach(function (a) {
      if (blocked) { a.setAttribute('aria-disabled', 'true'); a.setAttribute('tabindex', '-1'); }
      else { a.removeAttribute('aria-disabled'); a.removeAttribute('tabindex'); }
    });
    $('[data-checkout-hint]', page).hidden = !(t.unavailable > 0);
  }

  // forms: update quantity / remove (the server does this in Laravel)
  list.addEventListener('submit', function (e) {
    var li = e.target.closest('[data-cart-line]');
    if (!li) return;
    e.preventDefault();
    var id = li.getAttribute('data-id');
    if (e.target.hasAttribute('data-cart-remove-form')) {
      var neighbour = li.nextElementSibling || li.previousElementSibling;
      var nextId = neighbour && neighbour.getAttribute('data-id');
      store.remove(id);
      var target = nextId && $('[data-cart-line][data-id="' + nextId + '"] [data-cart-remove]', page);
      if (!target) target = $('[data-cart-empty] .btn', page);
      if (target) target.focus();
    } else {
      store.update(id, $('[data-qty-input]', li).value);
    }
  });

  // a disabled checkout link must not navigate
  page.parentNode.addEventListener('click', function (e) {
    var a = e.target.closest('[data-checkout-link]');
    if (a && a.getAttribute('aria-disabled') === 'true') e.preventDefault();
  });

  document.addEventListener('kayaa:cart', render);
  render();
})();
