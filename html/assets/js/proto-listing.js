/* PROTOTYPE ONLY - the server does this in Laravel; delete at Blade conversion.
   Reads the query string, then filters, sorts, searches and paginates the product cards on
   shop.html, category.html and search.html (selected by <body data-page>).
   Params: size (slug: newborn, 0-3m, 3-6m, 6-9m, 9-12m, 12-18m, 18-24m, 2y, 3y), sort (featured|new|price-asc|price-desc), sale=1,
   c (category slug, category.html; documented as /baby/{slug}), q (search.html), page.
   The old encoded form ?size=0%E2%80%933m (en dash) is accepted and normalised to 0-3m. Categories and sizes come from
   window.KAYAA_CATALOGUE (assets/js/catalogue-data.js, generated from tools/catalogue.json). */
(function () {
  'use strict';

  var PER_PAGE = 12;   // TODO: confirm the real listing page size with the backend dev
  var DATA = window.KAYAA_CATALOGUE || { categories: [], sizes: [] };
  var SORTS = ['featured', 'new', 'price-asc', 'price-desc'];   // TODO: confirm the backend's sort values
  var CATS = {};
  DATA.categories.forEach(function (c) { CATS[c.slug] = [c.label, c.blurb]; });
  var SIZES = DATA.sizes.map(function (s) { return [s.slug, s.label]; });
  var INTROS = DATA.intros || { categories: {}, sizes: {} };   // h1, meta title, meta description and intro from docs/content/category-and-age-intros.md
  var SEO = window.KAYAA_SEO || { staging: true, siteUrl: '', pages: {} };

  // ?size=0%E2%80%933m (en dash, any case) -> 0-3m
  function normaliseSize(v) {
    return String(v || '').trim().toLowerCase().replace(/[\u2010-\u2015\u2212]/g, '-').replace(/\s+/g, '');
  }

  var page = document.body.getAttribute('data-page');
  var root = document.querySelector('[data-listing]');
  if (!root || ['shop', 'category', 'search'].indexOf(page) === -1) return;

  var file = root.getAttribute('data-listing');
  var qs = new URLSearchParams(location.search);
  var $ = function (s, c) { return (c || root).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || root).querySelectorAll(s)); };

  /* ---------- State from the query string ---------- */
  var sizeLabel = {};
  SIZES.forEach(function (s) { sizeLabel[s[0]] = s[1]; });
  // an old ?size=0%E2%80%933m link is rewritten to the slug form in the address bar
  if (qs.get('size') && normaliseSize(qs.get('size')) !== qs.get('size') && sizeLabel[normaliseSize(qs.get('size'))] && history.replaceState) {
    qs.set('size', normaliseSize(qs.get('size')));
    history.replaceState(null, '', location.pathname + '?' + qs.toString() + location.hash);
  }
  var rawSize = normaliseSize(qs.get('size'));
  var size = sizeLabel[rawSize] ? rawSize : '';
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
    if (size && (' ' + d.size + ' ').indexOf(' ' + size + ' ') === -1) return false;
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
  } else if (size) {
    title = size === 'newborn' ? 'Newborn clothing' : 'Size ' + sizeLabel[size];
    intro = size === 'newborn' ? 'Clothes for newborns.' : 'Clothes for babies in size ' + sizeLabel[size] + '. Height and weight are the better guide.';
  } else if (sale) {
    title = 'Sale';
    intro = 'Selected styles at lower prices.';
  } else if (sort === 'new') {
    title = 'New in';
    intro = 'The newest arrivals first.';
  } else {
    title = 'All baby clothing';
    intro = 'Soft everyday essentials, sized by age.';
  }

  // the h1 and the intro paragraph of a category or a size come from the content pack (<!-- blade: category description / size intro -->)
  var escText = function (s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;'); };
  var introHtml = escText(intro);
  var metaTitle = title + ' | Kayaa';
  var metaDesc = (SEO.pages.shop && SEO.pages.shop.description) || '';
  var fromPack = page === 'category' && catKnown ? INTROS.categories[cat] : (page !== 'category' && page !== 'search' && size ? INTROS.sizes[size] : null);
  if (fromPack) { title = fromPack.h1; introHtml = fromPack.intro; metaTitle = fromPack.metaTitle; metaDesc = fromPack.metaDescription; }
  else if (page === 'search' && SEO.pages.search) { metaTitle = q ? title + ' | Kayaa' : SEO.pages.search.title; metaDesc = SEO.pages.search.description; }
  else if (page === 'shop' && !sale && sort === 'featured' && SEO.pages.shop) { metaTitle = SEO.pages.shop.title; }
  if (sale && page !== 'search' && (size || (page === 'category' && cat))) introHtml += ' Showing sale items only.';

  $('[data-listing-title]').textContent = title;
  $('[data-listing-intro]').innerHTML = introHtml;
  $('[data-listing-count]').textContent = total + (total === 1 ? ' product' : ' products');

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
    crumbs.appendChild(li('Baby', 'categories.html'));   // department Baby -> /baby/{slug}; Baby links to the categories page
    crumbs.appendChild(li(CATS[cat][0]));
  } else {
    crumbs.appendChild(li('Shop'));
  }

  /* ---------- Head: the indexing rules (docs/content/seo-guide.md section 4) ----------
     Staging forces noindex,nofollow everywhere; the rule that applies once it is live is kept on the robots tag as data-live-robots.
       /baby/{slug}                    index,follow, canonical itself          /shop?size={slug}   index,follow, canonical itself
       /baby/{slug}?size={slug}        noindex,follow, canonical the category  sort or sale        noindex,follow, canonical the clean listing
       ?page=2 and beyond              index,follow, canonical itself          search              noindex,follow, canonical itself */
  (function () {
    var enc = encodeURIComponent;
    var hasSort = qs.has('sort') && sort !== 'featured';
    var hasSale = qs.get('sale') === '1';
    var route = page === 'category' ? '/baby/' + (cat || 'all') : (page === 'search' ? '/search' : '/shop');
    var keep = [];                                   // the params a canonical may keep
    if (page === 'shop' && size) keep.push('size=' + size);
    if (page === 'search' && q) keep.push('q=' + enc(q));
    var clean = route + (keep.length ? '?' + keep.join('&') : '');
    var robots = 'index,follow';
    var canonical = clean;
    if (pageNo > 1) canonical = clean + (keep.length ? '&' : '?') + 'page=' + pageNo;
    if (page === 'search') robots = 'noindex,follow';
    else if (hasSort || hasSale) { robots = 'noindex,follow'; canonical = clean; }
    else if (page === 'category' && size) { robots = 'noindex,follow'; canonical = route; }
    else if (page === 'category' && !catKnown) robots = 'noindex,follow';
    var set = function (sel, attr, value) { var el = document.querySelector(sel); if (el) el.setAttribute(attr, value); };
    var url = (SEO.siteUrl || '') + canonical;
    document.title = metaTitle;
    set('meta[name="description"]', 'content', metaDesc);
    set('meta[property="og:title"]', 'content', metaTitle);
    set('meta[property="og:description"]', 'content', metaDesc);
    set('meta[name="twitter:title"]', 'content', metaTitle);
    set('meta[name="twitter:description"]', 'content', metaDesc);
    set('link[rel="canonical"]', 'href', url);
    set('meta[property="og:url"]', 'content', url);
    set('meta[name="robots"]', 'content', SEO.staging === false ? robots : 'noindex,nofollow');
    set('meta[name="robots"]', 'data-live-robots', robots);
    // BreadcrumbList for the state: Home / Baby / Category on a category page, Home / Shop on the shop
    var ld = $$('script[type="application/ld+json"]', document).filter(function (s) { return /BreadcrumbList/.test(s.textContent); })[0];
    if (ld && page !== 'search') {
      var trail = page === 'category' && catKnown ? [['Home', '/'], ['Baby', '/categories'], [CATS[cat][0], '/baby/' + cat]] : [['Home', '/'], ['Shop', '/shop']];
      ld.textContent = JSON.stringify({ '@context': 'https://schema.org', '@type': 'BreadcrumbList', itemListElement: trail.map(function (c, i) { return { '@type': 'ListItem', position: i + 1, name: c[0], item: (SEO.siteUrl || '') + c[1] }; }) }, null, 2);
    }
  })();

  /* ---------- Search box, chips, toolbar ---------- */
  var qInput = $('#listing-q');
  if (qInput) qInput.value = q;

  $$('.age-chips a').forEach(function (a, i) {
    var slug = i === 0 ? '' : SIZES[i - 1][0];
    a.setAttribute('href', url({ size: slug, page: null }));
    if (slug === size) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current');
  });

  var keep = { size: size, c: cat, q: q };
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
  if (clear) clear.setAttribute('href', url({ size: null, sort: null, sale: null, page: null }));
})();
