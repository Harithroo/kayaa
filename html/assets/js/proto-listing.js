/* PROTOTYPE ONLY - the server does this in Laravel; delete at Blade conversion.
   Reads the query string, then filters, sorts, searches and paginates the product cards on
   shop.html, category.html and search.html (selected by <body data-page>).
   Params: age, sort (featured|new|price-asc|price-desc), sale=1, c (category.html), q (search.html), page. */
(function () {
  'use strict';

  var PER_PAGE = 12;
  var AGES = [
    ['newborn', 'Newborn'], ['0-3-months', '0–3 months'], ['3-6-months', '3–6 months'],
    ['6-12-months', '6–12 months'], ['1-2-years', '1–2 years']
  ];
  var SORTS = ['featured', 'new', 'price-asc', 'price-desc'];
  // TODO: confirm category descriptions with client
  var CATS = {
    'bodysuits': ['Bodysuits', 'Everyday bodysuits for every stage.'],
    'sleepsuits': ['Sleepsuits', 'Comfortable sleepsuits for day and night.'],
    'sets': ['Sets', 'Matching pieces, ready to wear together.'],
    'dresses-rompers': ['Dresses & Rompers', 'Easy dresses and rompers for little ones.'],
    'hats-mitts': ['Hats & Mitts', 'Hats, mitts and booties.'],
    'swaddles-blankets': ['Swaddles & Blankets', 'Swaddles and blankets for cosy moments.']
  };

  var page = document.body.getAttribute('data-page');
  var root = document.querySelector('[data-listing]');
  if (!root || ['shop', 'category', 'search'].indexOf(page) === -1) return;

  var file = root.getAttribute('data-listing');
  var qs = new URLSearchParams(location.search);
  var $ = function (s, c) { return (c || root).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || root).querySelectorAll(s)); };

  /* ---------- State from the query string ---------- */
  var ageLabel = {};
  AGES.forEach(function (a) { ageLabel[a[0]] = a[1]; });
  var age = ageLabel[qs.get('age')] ? qs.get('age') : '';
  var sort = SORTS.indexOf(qs.get('sort')) !== -1 ? qs.get('sort') : 'featured';
  var sale = qs.get('sale') === '1';
  var cat = page === 'category' ? (qs.get('c') || '') : '';
  var catKnown = !!CATS[cat];
  var q = page === 'search' ? (qs.get('q') || '').trim() : '';
  var pageNo = Math.max(1, parseInt(qs.get('page'), 10) || 1);

  /* ---------- Filter, search, sort ---------- */
  var items = $$('[data-product]');
  var words = q.toLowerCase().split(/\s+/).filter(Boolean);

  function matches(li) {
    var d = li.dataset;
    if (age && (' ' + d.age + ' ').indexOf(' ' + age + ' ') === -1) return false;
    if (sale && d.sale !== '1') return false;
    if (cat && d.category !== cat) return false;
    if (page === 'category' && cat && !catKnown) return false;
    if (page === 'search') {
      if (!words.length) return false;
      var cname = CATS[d.category] ? CATS[d.category][0] : d.category;
      var hay = (d.name + ' ' + cname + ' ' + d.category).toLowerCase();
      return words.every(function (w) { return hay.indexOf(w) !== -1; });
    }
    return true;
  }

  var num = function (li, k) { return parseFloat(li.dataset[k]); };
  var sorters = {
    'featured': function (a, b) { return num(a, 'featured') - num(b, 'featured'); },
    'new': function (a, b) { return num(b, 'added') - num(a, 'added'); },
    'price-asc': function (a, b) { return num(a, 'price') - num(b, 'price'); },
    'price-desc': function (a, b) { return num(b, 'price') - num(a, 'price'); }
  };

  var results = items.filter(matches).sort(sorters[sort]);
  var total = results.length;
  var pages = Math.max(1, Math.ceil(total / PER_PAGE));
  if (pageNo > pages) pageNo = pages;
  var start = (pageNo - 1) * PER_PAGE;
  var shown = results.slice(start, start + PER_PAGE);

  var grid = $('[data-listing-grid]');
  items.forEach(function (li) { li.hidden = true; });
  shown.forEach(function (li) { li.hidden = false; grid.appendChild(li); });

  /* ---------- URLs that keep the other params ---------- */
  function url(mods) {
    var p = new URLSearchParams(location.search);
    Object.keys(mods).forEach(function (k) {
      if (mods[k] === null || mods[k] === '') p.delete(k); else p.set(k, mods[k]);
    });
    var s = p.toString();
    return file + (s ? '?' + s : '');
  }

  /* ---------- Title, intro, breadcrumb, document title ---------- */
  var title, intro;
  if (page === 'search') {
    title = q ? 'Results for “' + q + '”' : 'Search';
    intro = q ? 'Everything in the shop that matches your search.' : 'Type what you are looking for.';
  } else if (page === 'category' && cat) {
    title = catKnown ? CATS[cat][0] : 'Category not found';
    intro = catKnown ? CATS[cat][1] : 'We could not find that category.';
  } else if (age) {
    title = ageLabel[age] + ' clothing';
    intro = age === 'newborn' ? 'Clothes for newborns.' : 'Clothes for babies aged ' + ageLabel[age] + '.';
  } else if (sale) {
    title = 'Sale';
    intro = 'Selected styles at lower prices.';
  } else if (sort === 'new') {
    title = 'New in';
    intro = 'The newest arrivals first.';
  } else {
    title = 'All baby clothing';
    intro = 'Soft everyday essentials, sorted by age and stage.';
  }
  if (sale && page !== 'search' && (age || (page === 'category' && cat))) intro += ' Showing sale items only.';

  $('[data-listing-title]').textContent = title;
  $('[data-listing-intro]').textContent = intro;
  $('[data-listing-count]').textContent = total + (total === 1 ? ' product' : ' products');
  document.title = title + ' | Kayaa';

  var crumbs = $('[data-breadcrumb]');
  var li = function (text, href) {
    var el = document.createElement('li');
    if (href) {
      var a = document.createElement('a');
      a.href = href; a.textContent = text; el.appendChild(a);
    } else {
      el.textContent = text; el.setAttribute('aria-current', 'page');
    }
    return el;
  };
  crumbs.textContent = '';
  crumbs.appendChild(li('Home', 'index.html'));
  if (page === 'search') {
    crumbs.appendChild(li('Search'));
  } else if (page === 'category' && catKnown) {
    crumbs.appendChild(li('Shop', 'shop.html'));
    crumbs.appendChild(li(CATS[cat][0]));
  } else {
    crumbs.appendChild(li('Shop'));
  }

  /* ---------- Search box, chips, toolbar ---------- */
  var qInput = $('#listing-q');
  if (qInput) qInput.value = q;

  $$('.age-chips a').forEach(function (a, i) {
    var slug = i === 0 ? '' : AGES[i - 1][0];
    a.setAttribute('href', url({ age: slug, page: null }));
    if (slug === age) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current');
  });

  var keep = { age: age, c: cat, q: q };
  $$('input[data-keep]').forEach(function (inp) {
    var v = keep[inp.getAttribute('data-keep')];
    inp.value = v || '';
    inp.disabled = !v;
  });
  var sortEl = $('select[name="sort"]');
  if (sortEl) sortEl.value = sort;
  var saleEl = $('input[name="sale"]');
  if (saleEl) saleEl.checked = sale;

  var range = $('[data-listing-range]');
  range.textContent = total === 0 ? 'No products'
    : 'Showing ' + (total === 1 ? '1' : (start + 1) + '-' + (start + shown.length)) + ' of ' + total;

  /* ---------- Pagination ---------- */
  var nav = $('[data-listing-pagination]');
  var ico = function (name) {
    return '<svg class="icon icon--16" aria-hidden="true" focusable="false"><use href="#i-' + name + '"></use></svg>';
  };
  function pageHref(n) { return url({ page: n === 1 ? null : String(n) }); }
  function numbersToShow() {
    if (pages <= 7) return Array.apply(null, { length: pages }).map(function (_, i) { return i + 1; });
    var set = [1, pages, pageNo - 1, pageNo, pageNo + 1].filter(function (n) { return n >= 1 && n <= pages; });
    set = set.filter(function (n, i) { return set.indexOf(n) === i; }).sort(function (a, b) { return a - b; });
    var out = [];
    set.forEach(function (n, i) {
      if (i && n - set[i - 1] > 1) out.push(0);
      out.push(n);
    });
    return out;
  }
  if (pages < 2) {
    nav.hidden = true;
  } else {
    nav.hidden = false;
    var h = '';
    h += pageNo > 1
      ? '<li><a class="pagination__link pagination__edge" href="' + pageHref(pageNo - 1) + '" rel="prev">' + ico('chevron-left') + '<span class="pagination__edge-label">Prev</span></a></li>'
      : '<li><span class="pagination__link pagination__edge" aria-disabled="true">' + ico('chevron-left') + '<span class="pagination__edge-label">Prev</span></span></li>';
    numbersToShow().forEach(function (n) {
      if (!n) { h += '<li class="pagination__num"><span class="pagination__gap" aria-hidden="true">…</span></li>'; return; }
      h += '<li class="pagination__num"><a class="pagination__link" href="' + pageHref(n) + '"' +
        (n === pageNo ? ' aria-current="page"' : '') + ' aria-label="Page ' + n + '">' + n + '</a></li>';
    });
    h += '<li class="pagination__status">Page ' + pageNo + ' of ' + pages + '</li>';
    h += pageNo < pages
      ? '<li><a class="pagination__link pagination__edge" href="' + pageHref(pageNo + 1) + '" rel="next"><span class="pagination__edge-label">Next</span>' + ico('chevron-right') + '</a></li>'
      : '<li><span class="pagination__link pagination__edge" aria-disabled="true"><span class="pagination__edge-label">Next</span>' + ico('chevron-right') + '</span></li>';
    $('.pagination__list', nav).innerHTML = h;
  }

  /* ---------- Empty and no-results states ---------- */
  var empty = $('[data-state="empty"]');
  var noResults = $('[data-state="no-results"]');
  var results$ = $('[data-listing-results]');
  var isSearchMiss = page === 'search' && total === 0;
  results$.hidden = total === 0;
  if (empty) empty.hidden = !(total === 0 && !isSearchMiss);
  if (noResults) {
    noResults.hidden = !isSearchMiss;
    $('[data-no-results-title]', noResults).textContent = q ? 'No results for “' + q + '”' : 'What are you looking for?';
  }
  var clear = $('[data-clear-filters]');
  if (clear) clear.setAttribute('href', url({ age: null, sort: null, sale: null, page: null }));
})();
