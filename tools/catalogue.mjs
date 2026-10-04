// Generators for the catalogue-driven regions (dev-only; used by sync-shell.mjs).
// A page marks a region with <!-- gen: NAME args --> ... <!-- /gen: NAME --> and sync-shell fills it from tools/catalogue.json,
// so categories, sizes and products are defined once. Photo markers (<!-- photo: ID -->) inside a region keep what
// tools/process-images.mjs wrote there. {{root}} inside a generated link is the page's root.
//
// Regions
//   cat-row | cat-chips | cat-drawer          the seven categories as a header row, mega-menu chips, drawer list
//   cat-tiles-home | cat-tiles-page           category tiles (home, with photo markers; categories page, with item counts)
//   size-chips-mega | size-chips-drawer | size-links-footer | size-tiles-home   the nine sizes
//   size-chips FILE                           listing chips: "All ages" + the nine sizes (shop.html, category.html, search.html)
//   size-rows                                 <tr> rows of the size table on the product page (the rows come from docs/content/size-guide.md)
//   content NAME | faqs PLACEMENT | home-intro | district-eta | todo-counts | listing-intro KIND/SLUG   built from docs/content (see tools/content.mjs)
//   product-grid SCOPE                        <li> cards: all | new | featured | related:SLUG
import fs from 'node:fs';
import path from 'node:path';
import { renderContentPage, renderFaqs, renderHomeIntro, renderDistrictEtaJson, renderTodoCounts, sizeTableRows } from './content.mjs';

export function loadCatalogue(repo) {
  const cat = JSON.parse(fs.readFileSync(path.join(repo, 'tools', 'catalogue.json'), 'utf8'));
  const sizeIndex = Object.fromEntries(cat.sizes.map((s, i) => [s.slug, i]));
  cat.sizeIndex = sizeIndex;
  cat.sizeBySlug = Object.fromEntries(cat.sizes.map((s) => [s.slug, s]));
  cat.catBySlug = Object.fromEntries(cat.categories.map((c) => [c.slug, c]));
  for (const p of cat.products) {
    if (!(p.from in sizeIndex) || !(p.to in sizeIndex) || sizeIndex[p.from] > sizeIndex[p.to]) throw new Error('catalogue: bad size range for ' + p.slug);
    if (!cat.catBySlug[p.category]) throw new Error('catalogue: unknown category for ' + p.slug);
    p.sizes = cat.sizes.slice(sizeIndex[p.from], sizeIndex[p.to] + 1).map((s) => s.slug);
  }
  return cat;
}

const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
const icon = (name, size) => `<svg class="icon${size ? ' icon--' + size : ''}" aria-hidden="true" focusable="false"><use href="#i-${name}"></use></svg>`;
const money = (cfg, n) => `${cfg.currency_prefix} ${Number(n).toLocaleString('en-US')}`;
const tone = (n) => `var(--tone-${n})`;

function rangeText(cat, p) {
  return `${cat.sizeBySlug[p.from].lo}–${cat.sizeBySlug[p.to].hi}`;
}

function card(cat, cfg, p, root) {
  const sale = !!p.was;
  const badge = p.oos ? `<span class="badge badge--muted product-card__badge">Out of stock</span>`
    : sale ? `<span class="badge badge--accent product-card__badge">Sale</span>`
    : p.new ? `<span class="badge badge--lilac product-card__badge">New</span>` : '';
  const quick = p.oos ? '' : `
      <button type="button" class="product-card__quick" data-quick-add data-slug="${p.slug}" data-name="${esc(p.name)}" data-price="${p.price}"${sale ? ` data-was="${p.was}"` : ''} data-tone="${p.tone}" data-sizes="${p.sizes.join(' ')}" data-colours="${p.colours.join(' ')}" aria-haspopup="dialog" aria-label="Quick add, ${esc(p.name)}">
        ${icon('plus')}
        <span class="product-card__quick-label">Quick add</span>
      </button>`;
  const price = sale
    ? `<p class="price price--sale"><span class="visually-hidden">Sale price </span><span class="price__now">${money(cfg, p.price)}</span> <s class="price__was"><span class="visually-hidden">Was </span>${money(cfg, p.was)}</s></p>`
    : `<p class="price"><span class="price__now">${money(cfg, p.price)}</span></p>`;
  const n = p.colours.length;
  return `<li data-product data-slug="${p.slug}" data-name="${esc(p.name)}" data-price="${p.price}" data-size="${p.sizes.join(' ')}" data-category="${p.category}" data-new="${p.new ? 1 : 0}" data-sale="${sale ? 1 : 0}" data-featured="${p.featured}" data-added="${p.added}">
  <article class="product-card${p.oos ? ' product-card--oos' : ''}">
    <div class="product-card__media">
      <div class="media" data-placeholder style="--tone: ${tone(p.tone)}">${icon('image')}</div>
      ${badge}${quick}
    </div>
    <div class="product-card__info">
      <h3 class="product-card__name"><a class="product-card__link" href="${root}product.html">${esc(p.name)}</a></h3>
      <p class="product-card__meta">${n} ${n === 1 ? 'colour' : 'colours'} · ${rangeText(cat, p)}</p>
      ${price}
    </div>
  </article>
</li>`;
}

export function generate(name, args, ctx) {
  const { cat, cfg, root } = ctx;
  const C = cat.categories, S = cat.sizes;
  const catHref = (c) => `${root}category.html?c=${c.slug}`;
  const sizeHref = (s) => `${root}shop.html?size=${s.slug}`;
  switch (name) {
    case 'cat-row':
      return C.map((c) => `<li><a href="${catHref(c)}">${esc(c.label)}</a></li>`).join('\n');
    case 'cat-chips':
      return C.map((c) => `<li><a class="chip" href="${catHref(c)}">${esc(c.label)}</a></li>`).join('\n');
    case 'cat-drawer':
      return C.map((c) => `<li><a href="${catHref(c)}">${esc(c.label)} ${icon('chevron-right', 16)}</a></li>`).join('\n');
    case 'cat-tiles-home':
      return C.map((c) => `<li>
  <a class="cat-tile" href="${catHref(c)}">
    <!-- photo: cat-${c.slug} -->
    <div class="media" data-placeholder style="--ratio: 4 / 3; --tone: ${tone(c.tone)}">${icon('image')}</div>
    <!-- /photo: cat-${c.slug} -->
    <span class="cat-tile__label">${esc(c.label)} ${icon('arrow-right', 16)}</span>
  </a>
</li>`).join('\n');
    case 'cat-tiles-page':
      return C.map((c) => {
        const count = cat.products.filter((p) => p.category === c.slug).length;
        return `<li>
  <a class="cat-tile" href="${catHref(c)}">
    <div class="media" data-placeholder style="--ratio: 4 / 3; --tone: ${tone(c.tone)}">${icon('image')}</div>
    <span class="cat-tile__label">${esc(c.label)} ${icon('arrow-right', 16)}</span>
    <span class="cat-tile__count">${count} ${count === 1 ? 'item' : 'items'}</span>
  </a>
</li>`;
      }).join('\n');
    case 'size-chips-mega':
      return S.map((s) => `<li><a class="chip" href="${sizeHref(s)}">${esc(s.label)}</a></li>`).join('\n');
    case 'size-chips-drawer':
      return S.map((s) => `<li><a class="chip" href="${sizeHref(s)}">${esc(s.label)}</a></li>`).join('\n');
    case 'size-links-footer':
      return S.map((s) => `<li><a href="${sizeHref(s)}">${esc(s.label)}</a></li>`).join('\n');
    case 'size-tiles-home':
      return S.map((s, i) => `<li><a class="age-tile" href="${sizeHref(s)}" style="--tone: ${tone((i % 4) + 1)}"><span class="age-tile__label">${esc(s.label)}</span><span class="age-tile__go">Shop ${icon('arrow-right', 16)}</span></a></li>`).join('\n');
    case 'size-chips': {
      const file = (args || 'shop.html').trim();
      return [`<li><a class="chip" href="${root}${file}" aria-current="page">All ages</a></li>`]
        .concat(S.map((s) => `<li><a class="chip" href="${root}${file}?size=${s.slug}">${esc(s.label)}</a></li>`)).join('\n');
    }
    case 'size-rows': {
      const pack = sizeTableRows(ctx.repo);
      return S.map((s) => {
        const row = pack.find((x) => x.size === s.label);
        if (!row) throw new Error('size chart in docs/content/size-guide.md has no row for ' + s.label);
        return `<tr><th scope="row">${esc(s.label)}</th><td data-label="Height">${esc(row.height)}</td><td data-label="Weight">${esc(row.weight)}</td></tr>`;
      }).join('\n');
    }
    case 'content': return renderContentPage((args || '').trim(), ctx);
    case 'faqs': return renderFaqs((args || '').trim(), ctx);
    case 'home-intro': return renderHomeIntro(ctx);
    case 'listing-intro': {
      const [kind, slug] = (args || '').trim().split('/');
      const i = ctx.intros[kind] && ctx.intros[kind][slug];
      if (!i) throw new Error('gen listing-intro: no intro for ' + args);
      return `<h1 data-listing-title>${esc(i.h1)}</h1>\n<p class="listing-head__intro" data-listing-intro>${i.intro}</p>`;
    }
    case 'district-eta': return `<script type="application/json" id="district-eta">\n${renderDistrictEtaJson(ctx).split('\n').map((l) => '  ' + l).join('\n')}\n</script>`;
    case 'todo-counts': return renderTodoCounts(ctx);
    case 'product-grid': {
      const scope = (args || 'all').trim();
      let list = cat.products.slice();
      if (scope === 'new') list = list.filter((p) => p.new).sort((a, b) => b.added - a.added).slice(0, 4);
      else if (scope === 'featured') list = list.sort((a, b) => a.featured - b.featured).slice(0, 8);
      else if (scope.startsWith('related:')) {
        const slug = scope.slice(8);
        const me = cat.products.find((p) => p.slug === slug);
        if (!me) throw new Error('gen related: unknown product ' + slug);
        list = list.filter((p) => p.category === me.category && p.slug !== slug).sort((a, b) => a.featured - b.featured).slice(0, 4);
      } else if (scope !== 'all') throw new Error('gen product-grid: unknown scope ' + scope);
      else list.sort((a, b) => a.featured - b.featured);
      return list.map((p) => card(cat, cfg, p, root)).join('\n');
    }
    default:
      throw new Error('unknown gen region: ' + name);
  }
}

// assets/js/catalogue-data.js (window.KAYAA_CATALOGUE) for the prototype scripts; Blade prints the same data from the database
export function catalogueJs(cat, intros) {
  const data = {
    department: { slug: 'baby', label: 'Baby' },
    categories: cat.categories.map(({ slug, label, blurb }) => ({ slug, label, blurb })),
    sizes: cat.sizes.map(({ slug, label, lo, hi }) => ({ slug, label, lo, hi })),
    colours: cat.colours,
    // h1, meta title, meta description and intro (html) per category and per size: docs/content/category-and-age-intros.md
    intros,
    products: cat.products.map((p) => ({ slug: p.slug, name: p.name, category: p.category, price: p.price, was: p.was || 0, sizes: p.sizes, colours: p.colours, tone: p.tone, oos: !!p.oos }))
  };
  return `/* Generated by tools/sync-shell.mjs from tools/catalogue.json. Do not edit by hand.
   Prototype data for the scripts (quick add sheet, listing filters, wishlist). Blade prints the real catalogue instead. */
window.KAYAA_CATALOGUE = ${JSON.stringify(data, null, 2)};
`;
}

export function checkCoverage(cat) {
  const problems = [];
  for (const s of cat.sizes) {
    const n = cat.products.filter((p) => p.sizes.includes(s.slug)).length;
    if (n < 4) problems.push(`size ${s.slug} has ${n} products (need 4)`);
  }
  for (const c of cat.categories) {
    const n = cat.products.filter((p) => p.category === c.slug).length;
    if (n < 3) problems.push(`category ${c.slug} has ${n} products (need 3)`);
  }
  return problems;
}
