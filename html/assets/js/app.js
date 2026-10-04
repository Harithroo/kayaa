/* Kayaa prototype behaviour. Hooks are data-* attributes, never styling classes. */
(function () {
  'use strict';

  var root = document.documentElement;
  var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };

  // one currency helper: "Rs. 2,450" (the prefix comes from the site config)
  function formatMoney(n) {
    var prefix = (window.KAYAA_CONFIG && window.KAYAA_CONFIG.currency_prefix) || '';
    return prefix + ' ' + Math.round(n).toLocaleString('en-US');
  }
  window.Kayaa = window.Kayaa || {};
  window.Kayaa.formatMoney = formatMoney;

  /* Delivery estimate wording, the one helper used by checkout, the delivery page and anything else that shows an ETA.
     The backend sends two integers per district (min_days, max_days); the wording belongs to the frontend:
     equal values "3 working days", 1 and 1 "1 working day", otherwise "2–4 working days" (en dash). */
  window.Kayaa = window.Kayaa || {};
  window.Kayaa.formatEta = function (min, max) {
    min = parseInt(min, 10); max = parseInt(max, 10);
    if (isNaN(min) && isNaN(max)) return '';
    if (isNaN(min)) min = max;
    if (isNaN(max)) max = min;
    if (max < min) { var swap = min; min = max; max = swap; }
    if (min === max) return min + (min === 1 ? ' working day' : ' working days');
    return min + '–' + max + ' working days';
  };

  /* ---------- Announcement bar: dismiss hides it for this page view only (nothing is stored,
     so it is back after a refresh and on every new page). Height collapses over 200ms;
     reduced motion hides it instantly. ---------- */
  (function () {
    var bar = $('[data-topbar]');
    var btn = bar && $('[data-topbar-dismiss]', bar);
    if (!btn) return;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var finished = false;

    function done() {
      if (finished) return;
      finished = true;
      bar.hidden = true;
      window.dispatchEvent(new Event('resize'));   // re-measure --header-h and the sticky offsets
    }

    btn.addEventListener('click', function () {
      var wordmark = $('.site-header .wordmark');
      if (wordmark) wordmark.focus();   // the close button is about to disappear: keep focus somewhere sensible
      if (reduce) { done(); return; }
      bar.style.height = bar.offsetHeight + 'px';
      void bar.offsetHeight;            // commit the start height before animating
      bar.classList.add('is-collapsing');
      bar.style.height = '0px';
      bar.addEventListener('transitionend', function (e) { if (e.propertyName === 'height') done(); });
      setTimeout(done, 260);            // safety net if transitionend never fires
    });
  })();

  /* ---------- Header height -> --header-h (lets sticky bars sit exactly under the header) ---------- */
  (function () {
    var header = $('.site-header');
    if (!header) return;
    function set() { root.style.setProperty('--header-h', header.getBoundingClientRect().height + 'px'); }
    set();
    window.addEventListener('resize', set);
    if (window.ResizeObserver) new ResizeObserver(set).observe(header);
  })();

  /* ---------- Auto-submit: [data-autosubmit] controls submit their form on change.
     Pages ship a <noscript> Apply button for the no-JS case. ---------- */
  document.addEventListener('change', function (e) {
    var el = e.target.closest && e.target.closest('[data-autosubmit]');
    if (!el || !el.form) return;
    if (el.form.requestSubmit) el.form.requestSubmit(); else el.form.submit();
  });

  /* ---------- Demo session (PROTOTYPE): Account links go to the dashboard when signed in.
     In Laravel the server renders @auth / @guest, so this block is deleted at conversion. ---------- */
  (function () {
    var session = window.KayaaCart && window.KayaaCart.session;
    if (!session || !session.get()) return;
    $$('[data-account-link]').forEach(function (a) {
      a.setAttribute('href', a.getAttribute('href').replace(/login\.html$/, 'index.html'));
      var label = $('[data-account-label]', a);
      if (label) label.textContent = 'My account';
    });
    $$('[data-guest-only]').forEach(function (n) { n.hidden = true; });
  })();

  /* ---------- Error pages: [data-history-back] goes back one page (the href is the fallback when there is no history),
     [data-reload] reloads the current page. ---------- */
  document.addEventListener('click', function (e) {
    var back = e.target.closest('[data-history-back]');
    if (back && history.length > 1) { e.preventDefault(); history.back(); return; }
    if (e.target.closest('[data-reload]')) { e.preventDefault(); location.reload(); }
  });

  /* ---------- Active page: <body data-page="home"> marks matching [data-nav] links ----------
     Both attributes take space-separated tokens, e.g. data-page="account login". */
  (function () {
    var pages = (document.body.getAttribute('data-page') || '').split(/\s+/).filter(Boolean);
    if (!pages.length) return;
    $$('[data-nav]').forEach(function (el) {
      var hit = el.getAttribute('data-nav').split(/\s+/).some(function (t) { return pages.indexOf(t) !== -1; });
      if (hit) el.setAttribute('aria-current', 'page');
    });
  })();

  /* ---------- Layers: drawers and sheets (focus trap, ESC, focus return) ---------- */
  var overlay = $('[data-overlay]');
  var active = null; // { el, trigger }

  var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

  function focusables(el) {
    return $$(FOCUSABLE, el).filter(function (n) { return n.getClientRects().length > 0; });
  }

  function layerByName(name) {
    return $('[data-layer="' + name + '"]');
  }

  function openLayer(el, trigger) {
    if (!el) return;
    if (active && active.el !== el) closeLayer({ restore: false, keep: true });
    active = { el: el, trigger: trigger || document.activeElement };
    el.classList.add('is-open');
    el.setAttribute('aria-hidden', 'false');
    if (overlay) overlay.classList.add('is-visible');
    root.style.setProperty('--scrollbar-w', Math.max(0, window.innerWidth - root.clientWidth) + 'px');   // no layout jump when the scrollbar disappears
    root.classList.add('is-locked');
    if (trigger && trigger.hasAttribute('aria-expanded')) trigger.setAttribute('aria-expanded', 'true');
    var first = $('[data-autofocus]', el) || focusables(el)[0];
    if (first) first.focus();
  }

  function closeLayer(opts) {
    if (!active) return;
    opts = opts || {};
    var el = active.el;
    var trigger = active.trigger;
    el.classList.remove('is-open');
    el.setAttribute('aria-hidden', 'true');
    if (trigger && trigger.hasAttribute && trigger.hasAttribute('aria-expanded')) trigger.setAttribute('aria-expanded', 'false');
    active = null;
    if (!opts.keep) {
      if (overlay) overlay.classList.remove('is-visible');
      root.classList.remove('is-locked');
    }
    if (opts.restore !== false && trigger && trigger.focus) trigger.focus();
  }

  document.addEventListener('click', function (e) {
    var opener = e.target.closest('[data-open-layer]');
    if (opener) {
      e.preventDefault();
      openLayer(layerByName(opener.getAttribute('data-open-layer')), opener);
      return;
    }
    if (e.target.closest('[data-close-layer]')) closeLayer();
  });

  document.addEventListener('keydown', function (e) {
    if (!active) return;
    if (e.key === 'Escape') {
      e.preventDefault();
      closeLayer();
      return;
    }
    if (e.key !== 'Tab') return;
    var items = focusables(active.el);
    if (!items.length) return;
    var first = items[0];
    var last = items[items.length - 1];
    if (!active.el.contains(document.activeElement)) {
      e.preventDefault();
      first.focus();
    } else if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  });

  // The hamburger disappears at 900px, so close the menu drawer if the viewport grows past it.
  if (window.matchMedia) {
    window.matchMedia('(min-width: 900px)').addEventListener('change', function (mq) {
      if (mq.matches && active && active.el.getAttribute('data-layer') === 'menu') closeLayer({ restore: false });
    });
  }

  /* ---------- Mega menu (Shop, desktop) ----------
     Mouse: opens on hover intent (120ms), closes after a 250ms grace. Keyboard/touch: the chevron
     button toggles. ESC closes and returns focus to the chevron; outside click and focus leaving close it. */
  (function () {
    var item = $('[data-mega]');
    if (!item) return;
    var toggle = $('[data-mega-toggle]', item);
    var panel = $('[data-mega-panel]', item);
    var scrim = $('[data-mega-scrim]');
    var fine = window.matchMedia('(hover: hover) and (pointer: fine)');
    var desktop = window.matchMedia('(min-width: 900px)');
    var openTimer, closeTimer;
    var isOpen = false;
    var via = '';   // 'hover' or 'click'

    function setOpen(open, by) {
      isOpen = open;
      via = open ? by : '';
      panel.classList.toggle('is-open', open);
      if (open) panel.removeAttribute('inert'); else panel.setAttribute('inert', '');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (scrim) scrim.classList.toggle('is-visible', open);
    }
    function open(by) {
      clearTimeout(openTimer); clearTimeout(closeTimer);
      if (!isOpen) setOpen(true, by);
    }
    function close(restoreFocus) {
      clearTimeout(openTimer); clearTimeout(closeTimer);
      if (!isOpen) return;
      setOpen(false);
      if (restoreFocus) toggle.focus();
    }

    toggle.addEventListener('click', function () {
      if (isOpen && via === 'hover') { via = 'click'; return; }   // a click on a hover-opened menu pins it
      if (isOpen) close(false); else open('click');
    });

    item.addEventListener('mouseenter', function () {
      if (!fine.matches || !desktop.matches) return;
      clearTimeout(closeTimer);
      if (!isOpen) openTimer = setTimeout(function () { open('hover'); }, 120);
    });
    item.addEventListener('mouseleave', function () {
      clearTimeout(openTimer);
      if (isOpen && via === 'hover') closeTimer = setTimeout(function () { close(false); }, 250);
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isOpen) { e.preventDefault(); close(true); }
    });
    document.addEventListener('click', function (e) {
      if (isOpen && !item.contains(e.target)) close(false);
    });
    item.addEventListener('focusout', function (e) {
      if (isOpen && e.relatedTarget && !item.contains(e.relatedTarget)) close(false);
    });
    desktop.addEventListener('change', function () { close(false); });
  })();

  /* ---------- Header search (900-1099px: icon button that opens an inline field) ---------- */
  (function () {
    var form = $('[data-header-search]');
    if (!form) return;
    var toggle = $('[data-search-toggle]', form);
    var input = $('input[type="search"]', form);
    var bar = $('[data-header-bar]');
    var wide = window.matchMedia('(min-width: 1100px)');

    function isOpen() { return form.classList.contains('is-open'); }
    function setOpen(open, restoreFocus) {
      form.classList.toggle('is-open', open);
      if (bar) bar.classList.toggle('is-searching', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) input.focus(); else if (restoreFocus) toggle.focus();
    }

    toggle.addEventListener('click', function () { setOpen(!isOpen(), true); });
    form.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isOpen() && !wide.matches) { e.preventDefault(); setOpen(false, true); }
    });
    form.addEventListener('focusout', function (e) {
      if (isOpen() && !wide.matches && e.relatedTarget && !form.contains(e.relatedTarget)) setOpen(false);
    });
    document.addEventListener('click', function (e) {
      if (isOpen() && !form.contains(e.target)) setOpen(false);
    });
    wide.addEventListener('change', function () { setOpen(false); });
  })();

  /* ---------- Quantity steppers (any [data-qty] group) ---------- */
  var QTY_MAX = 10;

  function syncQty(group) {
    var input = $('[data-qty-input]', group);
    var minus = $('[data-qty-minus]', group);
    var plus = $('[data-qty-plus]', group);
    var v = parseInt(input.value, 10);
    if (isNaN(v) || v < 1) v = 1;
    var max = parseInt(group.getAttribute('data-qty-max'), 10) || QTY_MAX;   // e.g. capped to stock on the product page
    if (v > max) v = max;
    input.value = v;
    if (minus) minus.disabled = v <= 1;
    if (plus) plus.disabled = v >= max;
    return v;
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-qty-minus], [data-qty-plus]');
    if (!btn) return;
    var group = btn.closest('[data-qty]');
    var input = group && $('[data-qty-input]', group);
    if (!input) return;
    var v = parseInt(input.value, 10) || 1;
    input.value = btn.hasAttribute('data-qty-plus') ? v + 1 : v - 1;
    syncQty(group);
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });

  document.addEventListener('change', function (e) {
    var input = e.target.closest && e.target.closest('[data-qty-input]');
    if (input) syncQty(input.closest('[data-qty]'));
  });

  /* ---------- Cart drawer: rendered from the shared demo cart (proto-cart.js) ----------
     The markup contract is unchanged: [data-cart-drawer] holds one self-contained panel. In production the server
     returns that panel (/cart/panel) and the header counts come from the server too. */
  var cart = $('[data-cart-drawer]');
  var store = window.KayaaCart;

  // The wordmark link is {{root}}index.html, so it tells us the path to the site root from any folder (and for 404.html).
  var siteRoot = (function () { var w = $('.wordmark'); return w ? (w.getAttribute('href') || '').replace(/index.html$/, '') : ''; })();

  function fillLine(li, it) {
    li.setAttribute('data-id', it.id);
    li.setAttribute('data-unit-price', it.unitPrice);
    var link = $('[data-item-name]', li);
    link.textContent = it.name;
    link.setAttribute('href', siteRoot + 'product.html');
    $('[data-item-meta]', li).textContent = 'Size ' + it.sizeLabel + ' · ' + it.colourLabel + (it.unavailable ? ' · No longer available' : '');
    $('[data-item-media]', li).style.setProperty('--tone', store.toneCss(it));
    $('[data-cart-remove]', li).setAttribute('aria-label', 'Remove ' + it.name);
    li.classList.toggle('is-unavailable', !!it.unavailable);
    var group = $('[data-qty]', li);
    group.setAttribute('data-qty-max', String(Math.max(1, Math.min(store.MAX, it.stock || store.MAX))));
    var input = $('[data-qty-input]', li);
    if (document.activeElement !== input) input.value = it.qty;
    syncQty(group);
    $('[data-line-total]', li).textContent = formatMoney(it.qty * it.unitPrice);
  }

  function renderCart() {
    if (!store) return;
    var items = store.get();
    var t = store.totals();

    $$('[data-cart-count]').forEach(function (n) {
      n.textContent = t.count;
      n.hidden = t.count === 0;
    });
    $$('[data-cart-aria]').forEach(function (n) {
      n.setAttribute('aria-label', 'Open bag, ' + t.count + (t.count === 1 ? ' item' : ' items'));
    });
    if (!cart) return;
    var panel = $('[data-cart-panel]', cart);
    if (!panel) return;

    panel.setAttribute('data-state', t.count ? 'items' : 'empty');
    $$('[data-cart-count-label]', cart).forEach(function (n) { n.textContent = t.count; });
    $$('[data-cart-subtotal]', cart).forEach(function (n) { n.textContent = formatMoney(t.subtotal); });

    var bar = $('[data-cart-progress]', panel);
    var text = $('[data-cart-progress-text]', panel);
    if (bar) {
      bar.max = t.freeAt;
      bar.value = Math.min(t.subtotal, t.freeAt);
      bar.textContent = Math.round(Math.min(t.subtotal / t.freeAt, 1) * 100) + '%';
    }
    if (text) {
      text.textContent = t.free
        ? 'You’ve unlocked free delivery'
        : formatMoney(t.away) + ' away from free delivery';
    }

    // reconcile the lines in place (keeps focus on the stepper the shopper is using)
    var list = $('[data-cart-items]', panel);
    var tpl = $('[data-cart-item-template]');
    if (!list || !tpl) return;
    var byId = {};
    $$('[data-cart-item]', list).forEach(function (li) {
      var id = li.getAttribute('data-id');
      if (id) byId[id] = li; else li.parentNode.removeChild(li);   // the static sample lines in the markup
    });
    items.forEach(function (it) {
      var li = byId[it.id];
      if (!li) {
        li = tpl.content.firstElementChild.cloneNode(true);
        list.appendChild(li);
      }
      delete byId[it.id];
      fillLine(li, it);
    });
    Object.keys(byId).forEach(function (id) { byId[id].parentNode.removeChild(byId[id]); });
  }

  if (store) {
    document.addEventListener('kayaa:cart', renderCart);
    document.addEventListener('kayaa:open-cart', function (e) {
      if (cart) openLayer(cart, e.detail && e.detail.trigger);
    });
  }

  if (cart && store) {
    cart.addEventListener('change', function (e) {
      var input = e.target.closest('[data-qty-input]');
      var li = input && input.closest('[data-cart-item]');
      if (li) store.update(li.getAttribute('data-id'), input.value);
    });
    cart.addEventListener('click', function (e) {
      var rm = e.target.closest('[data-cart-remove]');
      if (!rm) return;
      var li = rm.closest('[data-cart-item]');
      var neighbour = li.nextElementSibling || li.previousElementSibling;
      var nextId = neighbour && neighbour.getAttribute('data-id');
      store.remove(li.getAttribute('data-id'));
      var target = nextId && $('[data-cart-item][data-id="' + nextId + '"] [data-cart-remove]', cart);
      if (!target) target = $('[data-autofocus-empty]', cart) || $('[data-close-layer]', cart);
      if (target) target.focus();
    });
  }
  renderCart();

  /* ---------- Quick-add sheet ---------- */
  var sheet = $('[data-quick-add-sheet]');
  var current = null;

  if (sheet) {
    var form = $('[data-quick-add-form]', sheet);

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-quick-add]');
      if (!btn) return;
      current = {
        slug: btn.getAttribute('data-slug') || '',
        name: btn.getAttribute('data-name'),
        price: parseInt(btn.getAttribute('data-price'), 10),
        was: parseInt(btn.getAttribute('data-was'), 10) || 0,
        tone: btn.getAttribute('data-tone') || '1'
      };
      fillSheetOptions(btn);
      $('[data-qa-name]', sheet).textContent = current.name;
      $('[data-qa-price]', sheet).textContent = formatMoney(current.price);
      var was = $('[data-qa-was]', sheet);
      was.textContent = current.was ? formatMoney(current.was) : '';
      was.hidden = !current.was;
      setSheetTone();
      openLayer(sheet, btn);
    });

    // sizes and colours of the chosen product come from the card (data-sizes, data-colours: slugs); labels from the catalogue
    function fillSheetOptions(btn) {
      var cat = window.KAYAA_CATALOGUE;
      var sizes = (btn.getAttribute('data-sizes') || '').split(/\s+/).filter(Boolean);
      var colours = (btn.getAttribute('data-colours') || '').split(/\s+/).filter(Boolean);
      if (!cat || !sizes.length) return;   // fall back to the options in the markup
      var sizeLabel = {};
      cat.sizes.forEach(function (s) { sizeLabel[s.slug] = s.label; });
      var esc = function (s) { return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;'); };
      $('[data-qa-sizes]', sheet).innerHTML = sizes.map(function (s, i) {
        return '<label class="size-box"><input class="visually-hidden" type="radio" name="size" value="' + s + '" data-label="' + esc(sizeLabel[s] || s) + '"' + (i === 0 ? ' checked' : '') + '><span>' + esc(sizeLabel[s] || s) + '</span></label>';
      }).join('');
      $('[data-qa-colours]', sheet).innerHTML = colours.map(function (c, i) {
        var label = cat.colours[c] || c;
        return '<label class="swatch"><input class="visually-hidden" type="radio" name="colour" value="' + c + '" data-label="' + esc(label) + '"' + (i === 0 ? ' checked' : '') + '><span class="dot dot--' + c + '" aria-hidden="true"></span><span class="visually-hidden">' + esc(label) + '</span></label>';
      }).join('');
    }

    // the thumbnail follows the selected colour
    function setSheetTone() {
      var colour = $('input[name="colour"]:checked', form);
      $('[data-qa-thumb]', sheet).style.setProperty('--tone', store ? store.toneCss({ tone: colour ? colour.value : current && current.tone }) : '');
    }
    form.addEventListener('change', function (e) {
      if (e.target.name === 'colour') setSheetTone();
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!current) return;
      var size = $('input[name="size"]:checked', form);
      var colour = $('input[name="colour"]:checked', form);
      if (!store || !size || !colour) return;
      // same shape as the product page form: colour slug + label, size key + label
      store.add({
        productSlug: current.slug || store.slugify(current.name),
        name: current.name,
        colourSlug: colour.value,
        colourLabel: colour.getAttribute('data-label') || colour.value,
        sizeSlug: size.value,
        sizeLabel: size.getAttribute('data-label') || size.value,
        unitPrice: current.price,
        wasPrice: current.was,
        qty: 1,
        stock: 8,   // the sample sheet has no stock data
        tone: colour.value
      });
      var trigger = active && active.trigger;
      closeLayer({ restore: false, keep: true });
      openLayer(cart, trigger);
    });
  }
})();
