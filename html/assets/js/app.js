/* Kayaa prototype behaviour. Hooks are data-* attributes, never styling classes. */
(function () {
  'use strict';

  var root = document.documentElement;
  var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };

  function formatMoney(n) {
    return 'Rs ' + Math.round(n).toLocaleString('en-US');
  }

  /* ---------- Announcement bar (dismiss remembered for the session) ---------- */
  (function () {
    var bar = $('[data-topbar]');
    if (!bar) return;
    var KEY = 'kayaa.topbar.dismissed';
    try {
      if (sessionStorage.getItem(KEY) === '1') bar.hidden = true;
    } catch (e) { /* storage unavailable: show the bar */ }
    var btn = $('[data-topbar-dismiss]', bar);
    if (btn) {
      btn.addEventListener('click', function () {
        bar.hidden = true;
        try { sessionStorage.setItem(KEY, '1'); } catch (e) { /* ignore */ }
      });
    }
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

  /* ---------- Disclosure dropdown (desktop nav) ---------- */
  $$('[data-dropdown]').forEach(function (dd) {
    var toggle = $('[data-dropdown-toggle]', dd);
    var panel = $('[data-dropdown-panel]', dd);
    if (!toggle || !panel) return;

    function setOpen(open, returnFocus) {
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      panel.hidden = !open;
      if (!open && returnFocus) toggle.focus();
    }

    toggle.addEventListener('click', function () {
      setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });
    toggle.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setOpen(true);
        var link = $('a', panel);
        if (link) link.focus();
      }
    });
    dd.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        e.stopPropagation();
        setOpen(false, true);
      }
    });
    dd.addEventListener('focusout', function (e) {
      if (e.relatedTarget && !dd.contains(e.relatedTarget)) setOpen(false);
    });
    document.addEventListener('click', function (e) {
      if (!dd.contains(e.target)) setOpen(false);
    });
  });

  /* ---------- Quantity steppers (any [data-qty] group) ---------- */
  var QTY_MAX = 10;

  function syncQty(group) {
    var input = $('[data-qty-input]', group);
    var minus = $('[data-qty-minus]', group);
    var plus = $('[data-qty-plus]', group);
    var v = parseInt(input.value, 10);
    if (isNaN(v) || v < 1) v = 1;
    if (v > QTY_MAX) v = QTY_MAX;
    input.value = v;
    if (minus) minus.disabled = v <= 1;
    if (plus) plus.disabled = v >= QTY_MAX;
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
    if (input && e.isTrusted) syncQty(input.closest('[data-qty]'));
  });

  /* ---------- Cart drawer (demo state lives in the DOM) ---------- */
  var cart = $('[data-cart-drawer]');

  function renderCart() {
    if (!cart) return;
    var panel = $('[data-cart-panel]', cart);
    if (!panel) return;
    var threshold = parseInt(panel.getAttribute('data-free-threshold'), 10) || 7500;
    var count = 0;
    var subtotal = 0;

    $$('[data-cart-item]', panel).forEach(function (li) {
      var group = $('[data-qty]', li);
      var qty = syncQty(group);
      var line = qty * parseInt(li.getAttribute('data-unit-price'), 10);
      $('[data-line-total]', li).textContent = formatMoney(line);
      count += qty;
      subtotal += line;
    });

    panel.setAttribute('data-state', count ? 'items' : 'empty');
    $$('[data-cart-count-label]', cart).forEach(function (n) { n.textContent = count; });
    $$('[data-cart-subtotal]', cart).forEach(function (n) { n.textContent = formatMoney(subtotal); });

    var bar = $('[data-cart-progress]', panel);
    var text = $('[data-cart-progress-text]', panel);
    if (bar) {
      bar.max = threshold;
      bar.value = Math.min(subtotal, threshold);
      bar.textContent = Math.round(Math.min(subtotal / threshold, 1) * 100) + '%';
    }
    if (text) {
      text.textContent = subtotal >= threshold
        ? 'You’ve unlocked free delivery'
        : formatMoney(threshold - subtotal) + ' away from free delivery';
    }

    $$('[data-cart-count]').forEach(function (n) {
      n.textContent = count;
      n.hidden = count === 0;
    });
    $$('[data-cart-aria]').forEach(function (n) {
      n.setAttribute('aria-label', 'Open bag, ' + count + (count === 1 ? ' item' : ' items'));
    });
  }

  if (cart) {
    cart.addEventListener('change', renderCart);
    cart.addEventListener('click', function (e) {
      var rm = e.target.closest('[data-cart-remove]');
      if (!rm) return;
      var li = rm.closest('[data-cart-item]');
      var next = li.nextElementSibling || li.previousElementSibling;
      li.parentNode.removeChild(li);
      renderCart();
      var target = next && $('[data-cart-remove]', next);
      if (!target) target = $('[data-autofocus-empty]', cart) || $('[data-close-layer]', cart);
      if (target) target.focus();
    });
    renderCart();
  }

  function addToCart(item) {
    var list = cart && $('[data-cart-items]', cart);
    var tpl = $('[data-cart-item-template]');
    if (!list || !tpl) return;
    var key = item.name + '|' + item.meta;
    var existing = $$('[data-cart-item]', list).filter(function (li) { return li.getAttribute('data-key') === key; })[0];
    if (existing) {
      var input = $('[data-qty-input]', existing);
      input.value = (parseInt(input.value, 10) || 0) + 1;
    } else {
      var li = tpl.content.firstElementChild.cloneNode(true);
      li.setAttribute('data-key', key);
      li.setAttribute('data-unit-price', item.price);
      $('[data-item-name]', li).textContent = item.name;
      $('[data-item-meta]', li).textContent = item.meta;
      $('[data-cart-remove]', li).setAttribute('aria-label', 'Remove ' + item.name);
      $('[data-item-media]', li).style.setProperty('--tone', 'var(--tone-' + item.tone + ')');
      list.appendChild(li);
    }
    renderCart();
  }

  /* ---------- Quick-add sheet ---------- */
  var sheet = $('[data-quick-add-sheet]');
  var current = null;

  if (sheet) {
    var form = $('[data-quick-add-form]', sheet);

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-quick-add]');
      if (!btn) return;
      current = {
        name: btn.getAttribute('data-name'),
        price: parseInt(btn.getAttribute('data-price'), 10),
        was: parseInt(btn.getAttribute('data-was'), 10) || 0,
        tone: btn.getAttribute('data-tone') || '1'
      };
      $('[data-qa-name]', sheet).textContent = current.name;
      $('[data-qa-price]', sheet).textContent = formatMoney(current.price);
      var was = $('[data-qa-was]', sheet);
      was.textContent = current.was ? formatMoney(current.was) : '';
      was.hidden = !current.was;
      $('[data-qa-thumb]', sheet).style.setProperty('--tone', 'var(--tone-' + current.tone + ')');
      openLayer(sheet, btn);
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!current) return;
      var size = $('input[name="size"]:checked', form);
      var colour = $('input[name="colour"]:checked', form);
      addToCart({
        name: current.name,
        price: current.price,
        tone: current.tone,
        meta: 'Size ' + (size ? size.value : '') + ' · ' + (colour ? colour.value : '')
      });
      var trigger = active && active.trigger;
      closeLayer({ restore: false, keep: true });
      openLayer(cart, trigger);
    });
  }
})();
