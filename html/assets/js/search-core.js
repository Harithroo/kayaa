/* Search core (production code: the matching rules, shared by the suggestions and the results page so they always agree).
   Pure functions, no DOM. Matches product NAME, CATEGORY name and TAG names (never descriptions).
   Rules (v1, no typo tolerance):
   - text is normalised: lower case, diacritics stripped, anything that is not a letter or digit becomes a space, spaces collapsed;
   - every query token must prefix-match a word of the product name, its category or one of its tags; a token of 3 or more characters that
     only appears inside a word is a weaker match;
   - ranking: 0 the name starts with the whole query, 1 every token starts a word of the name, 2 the match needs the category or a tag,
     3 weaker (substring); ties: featured first, then name.
   The backend implements the same rules (docs/backend-contract.md, "Search"). The data is window.KAYAA_CATALOGUE (generated from tools/catalogue.json). */
(function (root) {
  'use strict';

  function normalise(s) {
    return String(s == null ? '' : s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, ' ').replace(/\s+/g, ' ').trim();
  }
  function tokens(s) { var n = normalise(s); return n ? n.split(' ') : []; }

  function prefix(t, words) { for (var i = 0; i < words.length; i++) if (words[i].indexOf(t) === 0) return true; return false; }
  function inside(t, text) { return t.length >= 3 && text.indexOf(t) !== -1; }

  var cache = null;
  function index() {
    var data = root.KAYAA_CATALOGUE || { categories: [], tags: [], products: [] };
    if (cache && cache.data === data) return cache;
    var cats = {}, tags = {}, count = { c: {}, t: {} };
    data.categories.forEach(function (c) { cats[c.slug] = c; });
    (data.tags || []).forEach(function (t) { tags[t.slug] = t; });
    var recs = data.products.map(function (p) {
      var c = cats[p.category] || { label: p.category, slug: p.category };
      var tl = (p.tags || []).map(function (s) { return tags[s]; }).filter(Boolean);
      count.c[p.category] = (count.c[p.category] || 0) + 1;
      (p.tags || []).forEach(function (s) { count.t[s] = (count.t[s] || 0) + 1; });
      var nameN = normalise(p.name);
      var nameW = nameN.split(' ');
      var other = tokens(c.label).concat.apply(tokens(c.label), tl.map(function (t) { return tokens(t.label); }));
      return { p: p, cat: c, tags: tl, nameN: nameN, nameW: nameW, all: nameW.concat(other), text: nameN + ' ' + other.join(' ') };
    });
    cache = { data: data, recs: recs, cats: cats, tags: tags, count: count };
    return cache;
  }

  // 0..3 (lower is better) or -1 when the product does not match
  function tier(rec, qN, toks) {
    if (!toks.length) return -1;
    var nameAll = toks.every(function (t) { return prefix(t, rec.nameW); });
    if (nameAll) return rec.nameN.indexOf(qN) === 0 ? 0 : 1;
    if (toks.every(function (t) { return prefix(t, rec.all); })) return 2;
    if (toks.every(function (t) { return prefix(t, rec.all) || inside(t, rec.text); })) return 3;
    return -1;
  }

  // products, best first: [{ product, rec, tier }]
  function searchProducts(q) {
    var qN = normalise(q), toks = qN ? qN.split(' ') : [];
    return index().recs.map(function (rec) { return { product: rec.p, rec: rec, tier: tier(rec, qN, toks) }; })
      .filter(function (r) { return r.tier >= 0; })
      .sort(function (a, b) { return a.tier - b.tier || (a.product.featured || 99) - (b.product.featured || 99) || a.product.name.localeCompare(b.product.name); });
  }

  function labelMatches(list, q, counts, kind) {
    var qN = normalise(q), toks = qN ? qN.split(' ') : [];
    if (!toks.length) return [];
    return list.map(function (x) {
      var w = tokens(x.label), t;
      if (toks.every(function (k) { return prefix(k, w); })) t = normalise(x.label).indexOf(qN) === 0 ? 0 : 1;
      else if (toks.every(function (k) { return prefix(k, w) || inside(k, w.join(' ')); })) t = 2;
      else return null;
      return { slug: x.slug, label: x.label, count: counts[x.slug] || 0, tier: t };
    }).filter(Boolean).filter(function (x) { return x.count > 0; })
      .sort(function (a, b) { return a.tier - b.tier || b.count - a.count || a.label.localeCompare(b.label); });
  }
  function searchCategories(q) { var i = index(); return labelMatches(i.data.categories, q, i.count.c); }
  function searchTags(q) { var i = index(); return labelMatches(i.data.tags || [], q, i.count.t); }

  // the same shape the endpoint returns (GET /search/suggest?q=): { query, products, categories, tags }, limits 5 / 3 / 3
  function suggest(q) {
    var i = index();
    return {
      query: q,
      products: searchProducts(q).slice(0, 5).map(function (r) {
        return { name: r.product.name, url: 'product.html', category: r.rec.cat.label, price: r.product.price, compare_at: r.product.was || null, tone: r.product.tone };
      }),
      categories: searchCategories(q).slice(0, 3).map(function (c) { return { name: c.label, url: 'category.html?c=' + c.slug, count: c.count }; }),
      tags: searchTags(q).slice(0, 3).map(function (t) { return { name: t.label, url: 'shop.html?tag=' + t.slug, count: t.count }; })
    };
  }

  // [{ t: 'text', b: true|false }] pieces: the matched part of a name in bold (the caller builds nodes with textContent, never markup)
  function highlight(text, q) {
    var low = String(text).toLowerCase(), marks = [];
    tokens(q).forEach(function (t) {
      var from = 0, at;
      while ((at = low.indexOf(t, from)) !== -1) {
        if (at === 0 || /[^a-z0-9]/.test(low.charAt(at - 1))) { marks.push([at, at + t.length]); break; }
        from = at + 1;
      }
    });
    marks.sort(function (a, b) { return a[0] - b[0]; });
    var out = [], pos = 0;
    marks.forEach(function (m) {
      if (m[0] < pos) { if (m[1] > pos) { out[out.length - 1].t += text.slice(pos, m[1]); pos = m[1]; } return; }
      if (m[0] > pos) out.push({ t: text.slice(pos, m[0]), b: false });
      out.push({ t: text.slice(m[0], m[1]), b: true });
      pos = m[1];
    });
    if (pos < text.length) out.push({ t: text.slice(pos), b: false });
    return out;
  }

  root.KayaaSearch = { normalise: normalise, tokens: tokens, searchProducts: searchProducts, searchCategories: searchCategories, searchTags: searchTags, suggest: suggest, highlight: highlight };
})(window);
