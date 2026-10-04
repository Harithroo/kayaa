/* Search suggestions (production UI behaviour: stays after the Blade conversion).
   Enhances every <form data-search="header|page|error"> that has an <input type="search">: a WAI-ARIA combobox with list autocomplete.
   Where the data comes from is one function: window.KAYAA_SEARCH_PROVIDER(query, signal) -> Promise of
   { query, products:[{ name, url, category, price, compare_at, tone? }], categories:[{ name, url, count }], tags:[{ name, url, count }] }.
   Production leaves it unset and the default below calls GET /search/suggest?q= ; the prototype sets it in proto-search.js.
   - minimum 2 characters, 120ms debounce, a newer request aborts the older one (AbortController), aria-busy and a small spinner while loading
   - empty field on focus: the "Popular" links from the site config (popular_searches)
   - the input keeps focus: ArrowDown, ArrowUp, Home, End move the highlight (aria-activedescendant), Enter opens the highlighted row, otherwise the form submits
     to /search?q=; Escape closes the panel, a second Escape clears the field; outside click, blur and an opening mega menu close it
   - every row is built with textContent (never innerHTML with user text); the matched part of a name is bold, nothing else
   - ?focus=1 on the page form focuses it (the Search tab on a phone). Below 900px the page form shows the panel inline, below the field,
     and hides the results while it is open. */
(function () {
  'use strict';

  var MIN = 2, WAIT = 120, uid = 0;
  var NS = 'http://www.w3.org/2000/svg';
  var cfg = window.KAYAA_CONFIG || {};
  var core = window.KayaaSearch;
  var narrow = window.matchMedia ? window.matchMedia('(max-width: 899px)') : { matches: false };

  function defaultProvider(q, signal) {
    return fetch('/search/suggest?q=' + encodeURIComponent(q), { signal: signal, headers: { Accept: 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error('suggest ' + r.status); return r.json(); });
  }
  var ask = function (q, signal) { return (window.KAYAA_SEARCH_PROVIDER || defaultProvider)(q, signal); };
  var money = function (n) { return window.Kayaa && window.Kayaa.formatMoney ? window.Kayaa.formatMoney(n) : String(n); };
  function el(tag, cls, text) { var n = document.createElement(tag); if (cls) n.className = cls; if (text != null) n.textContent = text; return n; }
  function svg(name) {
    var s = document.createElementNS(NS, 'svg'), u = document.createElementNS(NS, 'use');
    s.setAttribute('class', 'icon'); s.setAttribute('aria-hidden', 'true'); s.setAttribute('focusable', 'false');
    u.setAttribute('href', '#i-' + name); s.appendChild(u); return s;
  }

  function enhance(form) {
    var input = form.querySelector('input[type="search"]');
    if (!input) return;
    var mode = form.getAttribute('data-search') || 'page';
    var root = (form.getAttribute('action') || '').replace(/search\.html.*$/, '');   // pages in a folder or at a sync root keep their prefix
    var id = 'sg' + (++uid);
    var host = form.querySelector('.header-search__field') || form;
    var active = -1, opts = [], timer = 0, seq = 0, aborter = null, last = '';

    host.classList.add('suggest-host');
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('aria-controls', id + '-list');
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('autocapitalize', 'off');
    input.setAttribute('spellcheck', 'false');
    input.setAttribute('enterkeyhint', 'search');

    // the input sits in a box with the clear button and the spinner
    var box = el('span', 'suggest__box');
    input.parentNode.insertBefore(box, input);
    box.appendChild(input);
    var clear = el('button', 'suggest__clear'); clear.type = 'button'; clear.setAttribute('aria-label', 'Clear search'); clear.hidden = true; clear.appendChild(svg('x'));
    var spin = el('span', 'suggest__spinner'); spin.setAttribute('aria-hidden', 'true'); spin.hidden = true;
    box.appendChild(spin); box.appendChild(clear);

    var panel = el('div', 'suggest suggest--' + mode); panel.hidden = true;
    var empty = el('p', 'suggest__empty'); empty.hidden = true;
    var list = el('div', 'suggest__list'); list.id = id + '-list'; list.setAttribute('role', 'listbox'); list.setAttribute('aria-label', 'Search suggestions');
    var live = el('p', 'visually-hidden'); live.setAttribute('role', 'status'); live.setAttribute('aria-live', 'polite');
    panel.appendChild(empty); panel.appendChild(list); panel.appendChild(live);
    host.appendChild(panel);

    function href(u) { return /^(\/|https?:)/.test(u) ? u : root + u; }
    function isOpen() { return !panel.hidden; }
    function setBusy(on) { spin.hidden = !on; list.setAttribute('aria-busy', on ? 'true' : 'false'); input.setAttribute('aria-busy', on ? 'true' : 'false'); }
    function sync() { clear.hidden = !input.value; }
    function inline() { return mode === 'page' && narrow.matches; }
    // an absolute panel never runs past the bottom of the window: it scrolls inside instead
    function fit() {
      if (inline() || mode === 'error') { panel.style.maxHeight = ''; return; }   // these panels sit in the page flow: the page scrolls, not the panel
      var top = panel.getBoundingClientRect().top;
      panel.style.maxHeight = Math.max(200, Math.min(window.innerHeight * 0.7, window.innerHeight - top - 16)) + 'px';
    }
    function open(on) {
      panel.hidden = !on;
      if (on) fit();
      input.setAttribute('aria-expanded', on ? 'true' : 'false');
      if (!on) setActive(-1);
      if (inline()) document.documentElement.classList.toggle('is-suggesting', on);
    }
    function setActive(i) {
      if (active > -1 && opts[active]) opts[active].removeAttribute('aria-selected');
      active = i;
      if (i > -1 && opts[i]) {
        opts[i].setAttribute('aria-selected', 'true');
        input.setAttribute('aria-activedescendant', opts[i].id);
        if (opts[i].scrollIntoView) opts[i].scrollIntoView({ block: 'nearest' });
      } else input.removeAttribute('aria-activedescendant');
    }

    function option(url, cls) {
      var a = el('a', 'suggest__opt ' + cls); a.href = href(url); a.id = id + '-o' + opts.length; a.tabIndex = -1; a.setAttribute('role', 'option');
      opts.push(a); return a;
    }
    function bold(parent, text, q) { core.highlight(text, q).forEach(function (p) { parent.appendChild(p.b ? el('strong', null, p.t) : document.createTextNode(p.t)); }); }
    function group(title, gid) {
      var g = el('div', 'suggest__group'); g.setAttribute('role', 'group'); g.setAttribute('aria-labelledby', id + '-' + gid);
      var l = el('div', 'suggest__label', title); l.id = id + '-' + gid; g.appendChild(l); list.appendChild(g); return g;
    }
    function plain(g, label, url, count, q, cls) {
      var a = option(url, cls || 'suggest__opt--plain'), n = el('span', 'suggest__name'); bold(n, label, q); a.appendChild(n);
      if (count != null) a.appendChild(el('span', 'suggest__count', count + (count === 1 ? ' item' : ' items')));
      g.appendChild(a);
    }

    function render(d, q) {
      list.textContent = ''; opts = []; active = -1; input.removeAttribute('aria-activedescendant');
      var n = d.products.length + d.categories.length + d.tags.length;
      if (d.products.length) {
        var gp = group('Products', 'p');
        d.products.forEach(function (p) {
          var a = option(p.url, 'suggest__opt--product');
          var th = el('span', 'suggest__thumb'); th.setAttribute('aria-hidden', 'true'); if (p.tone) th.style.setProperty('--tone', 'var(--tone-' + p.tone + ')'); th.appendChild(svg('image')); a.appendChild(th);
          var tx = el('span', 'suggest__text'), nm = el('span', 'suggest__name'); bold(nm, p.name, q); tx.appendChild(nm); tx.appendChild(el('span', 'suggest__meta', p.category)); a.appendChild(tx);
          var pr = el('span', 'suggest__price'); pr.appendChild(el('span', 'suggest__now', money(p.price)));
          if (p.compare_at) { var w = el('s', 'suggest__was'); w.appendChild(el('span', 'visually-hidden', 'Was ')); w.appendChild(document.createTextNode(money(p.compare_at))); pr.appendChild(w); }
          a.appendChild(pr); gp.appendChild(a);
        });
      }
      if (d.categories.length) { var gc = group('Categories', 'c'); d.categories.forEach(function (c) { plain(gc, c.name, c.url, c.count, q); }); }
      if (d.tags.length) { var gt = group('Tags', 't'); d.tags.forEach(function (t) { plain(gt, t.name, t.url, t.count, q); }); }
      empty.hidden = n > 0;
      if (!n) {
        empty.textContent = 'No matches for “' + q + '”';
        var gb = group('Browse categories', 'b');
        ((window.KAYAA_CATALOGUE || {}).categories || []).forEach(function (c) { plain(gb, c.label, 'category.html?c=' + c.slug, null, '', 'suggest__opt--chip'); });
      }
      var all = option('search.html?q=' + encodeURIComponent(q), 'suggest__opt--all');
      all.appendChild(el('span', 'suggest__name', 'See all results for “' + q + '”')); all.appendChild(svg('arrow-right')); list.insertBefore(all, n ? null : list.firstChild);
      if (!n) { opts.splice(opts.indexOf(all), 1); opts.unshift(all); }
      live.textContent = '';
      window.setTimeout(function () { live.textContent = n ? n + (n === 1 ? ' suggestion available' : ' suggestions available') : 'No suggestions'; }, 30);
      open(true);
    }

    function popular() {
      var items = cfg.popular_searches || [];
      list.textContent = ''; opts = []; active = -1; empty.hidden = true;
      if (!items.length) { open(false); return; }
      var g = group('Popular', 'pop');
      items.forEach(function (p) { plain(g, p.label, p.url, null, ''); });
      live.textContent = '';
      window.setTimeout(function () { live.textContent = items.length + ' popular searches available'; }, 30);
      open(true);
    }

    function request(q) {
      if (aborter) aborter.abort();
      aborter = window.AbortController ? new AbortController() : null;
      var mine = ++seq;
      setBusy(true);
      ask(q, aborter ? aborter.signal : undefined).then(function (d) {
        if (mine !== seq) return;
        setBusy(false); last = q; render(d, q);
      }, function (e) {
        if (mine !== seq || (e && e.name === 'AbortError')) return;
        setBusy(false); open(false);   // a failed request just means no suggestions: the form still submits to the results page
      });
    }

    function update() {
      var q = input.value.trim();
      window.clearTimeout(timer);
      sync();
      if (!q) { seq++; if (aborter) aborter.abort(); setBusy(false); popular(); return; }
      if (q.length < MIN) { seq++; if (aborter) aborter.abort(); setBusy(false); open(false); return; }
      timer = window.setTimeout(function () { request(q); }, WAIT);
    }

    input.addEventListener('input', update);
    input.addEventListener('focus', function () {
      var q = input.value.trim();
      if (!q) popular(); else if (q.length >= MIN && q === last && list.firstChild) open(true); else if (q.length >= MIN) update();
    });
    input.addEventListener('keydown', function (e) {
      var k = e.key;
      if (k === 'ArrowDown' || k === 'ArrowUp') {
        if (!isOpen()) { update(); return; }
        if (!opts.length) return;
        e.preventDefault();
        setActive(k === 'ArrowDown' ? (active + 1) % opts.length : (active <= 0 ? opts.length - 1 : active - 1));
      } else if ((k === 'Home' || k === 'End') && isOpen() && opts.length) {
        e.preventDefault(); setActive(k === 'Home' ? 0 : opts.length - 1);
      } else if (k === 'Enter') {
        if (isOpen() && active > -1 && opts[active]) { e.preventDefault(); window.location.href = opts[active].href; }
      } else if (k === 'Escape') {
        if (isOpen()) { e.preventDefault(); open(false); e.stopPropagation(); }
        else if (input.value) { e.preventDefault(); input.value = ''; sync(); e.stopPropagation(); }
      }
    });
    clear.addEventListener('click', function () { input.value = ''; sync(); input.focus(); popular(); });
    panel.addEventListener('mousedown', function (e) { e.preventDefault(); });   // the input keeps focus while a row is pressed
    form.addEventListener('focusout', function (e) { if (isOpen() && !(e.relatedTarget && form.contains(e.relatedTarget))) open(false); });
    document.addEventListener('click', function (e) { if (isOpen() && !form.contains(e.target)) open(false); });
    document.addEventListener('mouseover', function (e) { if (isOpen() && e.target.closest && e.target.closest('[data-mega]')) open(false); });   // the mega menu opens on hover
    document.addEventListener('click', function (e) { if (isOpen() && e.target.closest && e.target.closest('[data-mega-toggle]')) open(false); });
    form.addEventListener('submit', function () { open(false); });
    window.addEventListener('resize', function () { if (isOpen()) fit(); });
    window.addEventListener('pageshow', function (e) { if (e.persisted) open(false); });   // coming back with the Back button: no stale panel

    sync();
    if (mode === 'page' && /[?&]focus=1\b/.test(location.search)) {
      input.focus();
      if (window.history.replaceState) window.history.replaceState(null, '', location.pathname + location.search.replace(/([?&])focus=1&?/, '$1').replace(/[?&]$/, '') + location.hash);
    }
  }

  function init() { if (core) Array.prototype.forEach.call(document.querySelectorAll('form[data-search]'), enhance); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
