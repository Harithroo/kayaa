/* The server cart, in place of the prototype's proto-cart.js.

   Everything still works without JS: every cart control is a real form, and the
   server answers with a redirect that re-renders the page (and sets cart_open so
   the drawer is showing on arrival). This module is the enhancement on top:

   - add to cart, quick add, quantity and remove post in the background with the
     X-Cart-Panel: 1 header, which makes CartController return just the drawer
     panel. The returned HTML replaces [data-cart-panel] in place, so the page the
     shopper is reading never reloads.
   - the header, tab bar and drawer counts are read back out of that HTML, so the
     server stays the only thing that decides what is in the bag.
   - after an add the drawer opens, by clicking the header's own [data-open-layer]
     button so app.js keeps ownership of focus, inert and the overlay.
   - <body data-cart-open> (from the cart_open flash) opens the drawer on load.

   The markup contract is the prototype's, unchanged. */
(function () {
  'use strict';

  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

  var drawer = $('[data-cart-drawer]');
  var token = $('meta[name="csrf-token"]');

  function openDrawer() {
    var trigger = $('.site-header__actions [data-open-layer="cart"]') || $('[data-open-layer="cart"]');
    if (trigger) trigger.click();
  }

  /* Pull the fresh counts out of the panel the server just returned and write them
     into the shell (the panel itself carries the authoritative number). */
  function syncCounts(panel) {
    var label = $('[data-cart-count-label]', panel);
    var count = label ? parseInt(label.textContent, 10) || 0 : 0;
    $$('[data-cart-count]').forEach(function (n) {
      n.textContent = count;
      n.hidden = count === 0;
    });
    $$('[data-cart-aria]').forEach(function (n) {
      n.setAttribute('aria-label', 'Open bag, ' + count + (count === 1 ? ' item' : ' items'));
    });
  }

  function swapPanel(html) {
    var parsed = new DOMParser().parseFromString(html, 'text/html');
    var fresh = $('[data-cart-panel]', parsed);
    if (!fresh || !drawer) return false;
    var old = $('[data-cart-panel]', drawer);
    if (!old) return false;
    old.replaceWith(fresh);
    syncCounts(fresh);
    return true;
  }

  function post(form) {
    var body = new FormData(form);
    var headers = { 'X-Cart-Panel': '1', 'X-Requested-With': 'XMLHttpRequest' };
    if (token) headers['X-CSRF-TOKEN'] = token.getAttribute('content');
    return fetch(form.action, { method: 'POST', body: body, headers: headers, credentials: 'same-origin' })
      .then(function (res) { return res.ok ? res.text() : Promise.reject(res); });
  }

  /* One handler for every cart form on the page. data-cart-form marks the ones that
     should stay on the page: add to cart, quick add, and the drawer's own controls. */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.matches('[data-cart-form], [data-quick-add-form], [data-cart-panel] form')) return;
    // A submitter with its own formaction (the product page's Save button) is not
    // posting to the cart: let the browser handle it.
    if (e.submitter && e.submitter.hasAttribute('data-native-submit')) return;
    if (!drawer || !window.fetch || !window.DOMParser) return;   // no JS path: let it post normally

    var opens = form.hasAttribute('data-cart-form') || form.hasAttribute('data-quick-add-form');
    e.preventDefault();

    var submit = $('button[type="submit"]', form);
    if (submit) submit.setAttribute('aria-busy', 'true');

    post(form).then(function (html) {
      if (!swapPanel(html)) { form.submit(); return; }
      if (opens) {
        // A quick add happens from inside its own sheet: close that first.
        var close = $('[data-quick-add-sheet].is-open [data-close-layer]');
        if (close) close.click();
        openDrawer();
      }
    }).catch(function () {
      form.submit();   // sold out, an expired session, anything: let the server say so on a full page
    }).then(function () {
      if (submit) submit.removeAttribute('aria-busy');
    });
  });

  // The server asked for the drawer to be showing (cart_open after a no-JS add).
  if (document.body.hasAttribute('data-cart-open')) openDrawer();
})();
