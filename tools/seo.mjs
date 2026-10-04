// Builds the <!-- partial: seo --> head block of every page from tools/seo.json (dev-only; used by sync-shell.mjs).
// Writes <title>, meta description, canonical, robots, Open Graph, Twitter card, and JSON-LD:
//   BreadcrumbList where the page has a trail, Organization on the home page, Product on the product page (read back from the
//   visible markup so it always matches what the shopper sees). No FAQPage and no WebSite SearchAction markup.
import fs from 'node:fs';
import path from 'node:path';
import { escapeHtml, plain } from './markdown.mjs';
import { loadHome } from './content.mjs';

export function loadSeo(repo) {
  return JSON.parse(fs.readFileSync(path.join(repo, 'tools', 'seo.json'), 'utf8'));
}

const decode = (s) => String(s).replace(/<[^>]+>/g, '').replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/\s+/g, ' ').trim();
const ld = (obj) => JSON.stringify(obj, null, 2).replace(/</g, '\\u003c');

// title and description of a page: from the content pack front matter, the category/size intros, or seo.json itself
export function metaOf(label, ctx) {
  const e = ctx.seo.pages[label];
  if (!e) return null;
  const cfgText = ctx.cfgText;
  if (e.content) {
    const front = e.content === 'home' ? loadHome(ctx.repo).front : ctx.content.pages[e.content].front;
    return { title: plain(front.meta_title, cfgText), description: plain(front.meta_description, cfgText) };
  }
  if (e.intro) {
    const [kind, slug] = e.intro.split('/');
    const i = ctx.intros[kind][slug];
    return { title: i.metaTitle, description: i.metaDescription };
  }
  return { title: e.title, description: e.description };
}

function productSchema(text, canonical, entry) {
  const pick = (re) => { const m = text.match(re); return m ? m[1] : ''; };
  const name = decode(pick(/<h1 class="product__title">([\s\S]*?)<\/h1>/));
  const lede = decode(pick(/<p class="product__lede">([\s\S]*?)<\/p>/)).replace(/[….]+$/, '').trim();
  const price = parseInt(pick(/data-unit-price="(\d+)"/), 10);
  let inStock = false;
  try { inStock = JSON.parse(pick(/<script type="application\/json" id="product-variants">([\s\S]*?)<\/script>/)).some((v) => v.stock > 0); } catch (e) { /* no variants */ }
  const avg = parseFloat(pick(/<p class="rating-summary__avg">([\d.]+)<\/p>/));
  const count = parseInt(pick(/Based on (\d+) reviews?/), 10);
  const reviews = [];
  for (const m of text.matchAll(/<li class="review-card"([^>]*)>([\s\S]*?)<\/li>/g)) {
    if (/\shidden\b/.test(m[1])) continue;   // only the reviews that are visible on the page
    const body = m[2];
    const r = { '@type': 'Review', author: { '@type': 'Person', name: '' } };
    const grab = (re) => { const x = body.match(re); return x ? decode(x[1]) : ''; };
    r.author.name = grab(/review-card__name">([\s\S]*?)<\/p>/);
    const dm = body.match(/datetime="([^"]+)"/);
    if (dm) r.datePublished = dm[1];
    r.reviewRating = { '@type': 'Rating', ratingValue: parseInt(grab(/aria-label="Rated (\d) out of 5"/), 10), bestRating: 5 };
    const headline = grab(/review-card__headline">([\s\S]*?)<\/p>/);
    if (headline) r.name = headline;
    r.reviewBody = grab(/review-card__text">([\s\S]*?)<\/p>/);
    reviews.push(r);
  }
  const crumb = entry.crumbs && entry.crumbs[2] ? entry.crumbs[2][0] : undefined;
  const schema = {
    '@context': 'https://schema.org', '@type': 'Product', name, description: lede, sku: pick(/data-product-slug="([^"]+)"/), brand: { '@type': 'Brand', name: 'Kayaa' }
  };
  if (crumb) schema.category = crumb;
  schema.offers = { '@type': 'Offer', url: canonical, priceCurrency: 'LKR', price, availability: 'https://schema.org/' + (inStock ? 'InStock' : 'OutOfStock') };
  if (avg && count) schema.aggregateRating = { '@type': 'AggregateRating', ratingValue: avg, reviewCount: count, bestRating: 5 };
  if (reviews.length) schema.review = reviews;
  return schema;
}

export function seoBlock(label, text, ctx) {
  const entry = ctx.seo.pages[label];
  if (!entry) throw new Error('tools/seo.json has no entry for ' + label);
  const m = metaOf(label, ctx);
  const site = ctx.seo.site;
  const staging = ctx.seo.staging !== false;
  const production = entry.robots || 'index,follow';
  const robots = staging ? 'noindex,nofollow' : production;
  const canonical = entry.canonical === false ? '' : site.url + entry.path;
  const t = escapeHtml(m.title), d = escapeHtml(m.description);
  const L = [];
  L.push(`<title>${t}</title>`);
  L.push(`<meta name="description" content="${d}">`);
  if (canonical) L.push(`<link rel="canonical" href="${canonical}"> <!-- blade: url() -->`);
  L.push(`<meta name="robots" content="${robots}">${staging ? ` <!-- staging: noindex,nofollow everywhere ("staging": true in tools/seo.json). Live: ${production} -->` : ''}`);
  L.push(`<meta property="og:title" content="${t}">`);
  L.push(`<meta property="og:description" content="${d}">`);
  L.push(`<meta property="og:type" content="${entry.og || 'website'}">`);
  if (canonical) L.push(`<meta property="og:url" content="${canonical}">`);
  L.push(`<meta property="og:site_name" content="${site.name}">`);
  L.push(`<meta property="og:locale" content="${site.locale}">`);
  L.push('<!-- TODO: og:image and twitter:image once a logo exists -->');
  L.push('<meta name="twitter:card" content="summary">');
  L.push(`<meta name="twitter:title" content="${t}">`);
  L.push(`<meta name="twitter:description" content="${d}">`);

  // the LCP image: preloaded only when a real photo exists in the hero
  if (label === 'index.html') {
    const hero = text.match(/<!-- photo: hero -->([\s\S]*?)<!-- \/photo: hero -->/);
    const img = hero && hero[1].match(/<img\b[^>]*>/);
    if (img) {
      const attr = (n) => { const x = img[0].match(new RegExp(n + '="([^"]*)"')); return x ? x[1] : ''; };
      if (attr('srcset')) L.push(`<link rel="preload" as="image" imagesrcset="${attr('srcset')}"${attr('sizes') ? ` imagesizes="${attr('sizes')}"` : ''} fetchpriority="high">`);
    }
  }

  const blocks = [];
  if (label === 'index.html') {
    blocks.push(['<!-- TODO: logo, social profile URLs and the contact telephone are placeholders; confirm with the client -->', {
      '@context': 'https://schema.org', '@type': 'Organization', name: site.name, url: site.url + '/',
      logo: site.url + '/assets/img/logo.png', sameAs: ['https://www.instagram.com/kayaa', 'https://www.facebook.com/kayaa'],
      contactPoint: { '@type': 'ContactPoint', contactType: 'customer service', telephone: '+94770000000', email: 'hello@example.com', areaServed: 'LK', availableLanguage: 'English' }
    }]);
  }
  if (entry.crumbs && entry.crumbs.length > 1) {
    blocks.push(['', {
      '@context': 'https://schema.org', '@type': 'BreadcrumbList',
      itemListElement: entry.crumbs.map(([name, p], i) => ({ '@type': 'ListItem', position: i + 1, name, item: site.url + p }))
    }]);
  }
  if (label === 'product.html') blocks.push(['<!-- TODO: add "image" to this Product once product photos exist. Blade builds this block per product from the same data as the page. -->', productSchema(text, canonical, entry)]);
  for (const [comment, obj] of blocks) {
    if (comment) L.push(comment);
    L.push('<script type="application/ld+json">');
    L.push(...ld(obj).split('\n'));
    L.push('</script>');
  }
  return L.map((l) => (l ? '  ' + l : l)).join('\n');
}
