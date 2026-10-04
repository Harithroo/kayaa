// Builds the content-pack parts of the pages (dev-only; used through tools/catalogue.mjs by sync-shell.mjs).
//   gen: content NAME   a whole content page body (breadcrumb, h1, intro, table of contents, prose, help card) from docs/content/*.md
//   gen: faqs PLACEMENT the FAQ accordion items for "product", "contact" or "size guide" (docs/content/faqs.md)
//   gen: district-eta   the { slug: { min, max } } JSON of the delivery estimate (same data on checkout and the delivery page)
//   gen: todo-counts    the "Content to approve" card on review.html
// Edit docs/content, never the html text.
import fs from 'node:fs';
import path from 'node:path';
import { readMarkdown, inline, plain, slugify, escapeHtml, parseFrontMatter } from './markdown.mjs';

const icon = (name, size) => `<svg class="icon${size ? ' icon--' + size : ''}" aria-hidden="true" focusable="false"><use href="#i-${name}"></use></svg>`;
const titleCase = (slug) => slug.split('-').map((w) => w[0].toUpperCase() + w.slice(1)).join(' ');

export function loadContent(repo) {
  const dir = path.join(repo, 'docs', 'content');
  const config = JSON.parse(fs.readFileSync(path.join(repo, 'tools', 'content.json'), 'utf8'));
  const pages = {};
  for (const [name, opts] of Object.entries(config.pages)) pages[name] = { ...opts, ...readMarkdown(path.join(dir, opts.file)) };
  return { dir, config, pages };
}

export function loadHome(repo) {
  return readMarkdown(path.join(repo, 'docs', 'content', 'home.md'));
}

/* ---------- FAQs (faqs.md) ---------- */
export function loadFaqs(repo) {
  const { blocks } = readMarkdown(path.join(repo, 'docs', 'content', 'faqs.md'));
  const groups = {};
  let group = null, q = null;
  for (const b of blocks) {
    if (b.type === 'h2') { const m = b.text.match(/\(placement:\s*([^)]+)\)/); group = m ? (groups[m[1].trim()] = []) : null; q = null; }
    else if (b.type === 'h3' && group) { q = { q: b.text, body: [] }; group.push(q); }
    else if (q && group) q.body.push(b);
  }
  return groups;
}

const LINK_RE = /\[([^\]]+)\]\(([^)\s]+)\)/g;
function linkRun(text, ctx) {
  const links = [...text.matchAll(LINK_RE)];
  if (links.length < 4) return null;
  const parts = [];
  let last = 0;
  for (const m of links) { parts.push({ text: text.slice(last, m.index) }); parts.push({ label: m[1], href: m[2] }); last = m.index + m[0].length; }
  parts.push({ text: text.slice(last) });
  const out = [];
  let open = false;
  for (const p of parts) {
    if (p.href) {
      if (!open) { out.push('<ul class="chip-list" role="list">'); open = true; }
      out.push(`  <li>${inline(`[${p.label}](${p.href})`, ctx).replace('<a href', '<a class="chip" href')}</li>`);
    } else {
      const words = p.text.replace(/[\s,.;:]+/g, ' ').replace(/\b(and|or)\b/gi, '').trim();
      if (words) { if (open) { out.push('</ul>'); open = false; } out.push(`<p class="chip-list__label">${inline(p.text.trim().replace(/^[\s,.;]+/, ''), ctx)}</p>`); }
    }
  }
  if (open) out.push('</ul>');
  return out.join('\n');
}

export function renderBlocks(blocks, ctx) {
  const inl = (t) => inline(t, ctx);
  return blocks.map((b) => {
    if (b.type === 'p') return linkRun(b.text, ctx) || `<p>${inl(b.text).replace(/\n/g, '<br>\n')}</p>`;
    if (b.type === 'ul') return `<ul>\n${b.items.map((i) => `  <li>${inl(i)}</li>`).join('\n')}\n</ul>`;
    if (b.type === 'ol') return `<ol>\n${b.items.map((i) => `  <li>${inl(i)}</li>`).join('\n')}\n</ol>`;
    return '';
  }).filter(Boolean).join('\n');
}

export function renderFaqs(placement, ctx) {
  const faqs = loadFaqs(ctx.repo)[placement];
  if (!faqs) throw new Error('gen faqs: no group "' + placement + '" in docs/content/faqs.md');
  return faqs.map((f) => `<details class="accordion__item">
  <summary class="accordion__summary">${inline(f.q, ctx)} ${icon('chevron-down')}</summary>
  <div class="accordion__body">
${renderBlocks(f.body, ctx).split('\n').map((l) => '    ' + l).join('\n')}
  </div>
</details>`).join('\n');
}

/* ---------- district estimates ({ min, max } per district) ---------- */
// the two zones come from the site config strings ("1–2", "2–4"); TODO until per-district data arrives from the backend
export function districtEta(ctx) {
  const parse = (s) => { const m = String(s).match(/(\d+)\D+(\d+)/); return m ? { min: +m[1], max: +m[2] } : { min: parseInt(s, 10), max: parseInt(s, 10) }; };
  const colombo = parse(ctx.cfg.delivery_colombo_days), island = parse(ctx.cfg.delivery_island_days);
  const zone = new Set(ctx.content.config.colomboZone);
  const out = {};
  for (const [, ds] of ctx.content.config.provinces) for (const d of ds) out[d] = zone.has(d) ? colombo : island;
  return Object.fromEntries(Object.keys(out).sort().map((d) => [d, out[d]]));
}

export function renderDistrictEtaJson(ctx) {
  const eta = districtEta(ctx);
  return '{\n' + Object.keys(eta).map((d) => `  "${d}": { "min": ${eta[d].min}, "max": ${eta[d].max} }`).join(',\n') + '\n}';
}

function etaText(e) { return e.min === e.max ? `${e.min} working ${e.min === 1 ? 'day' : 'days'}` : `${e.min}–${e.max} working days`; }

function deliveryTable(ctx) {
  const eta = districtEta(ctx);
  const provinces = ctx.content.config.provinces.map(([name, ds]) => `<h3 id="${slugify(name)}">${name} Province <a class="anchor" href="#${slugify(name)}" aria-label="Link to this section: ${name} Province">${icon('link', 16)}</a></h3>
<div class="table-wrap">
  <table class="size-table size-table--two">
    <caption class="visually-hidden">Delivery estimates, ${name} Province</caption>
    <thead>
      <tr><th scope="col">District</th><th scope="col">Estimated delivery</th></tr>
    </thead>
    <tbody>
${ds.map((d) => `      <tr><th scope="row">${titleCase(d)}</th><td data-label="Estimated delivery" data-eta="${d}">${etaText(eta[d])}</td></tr>`).join('\n')}
    </tbody>
  </table>
</div>`).join('\n');
  return `<!-- The estimates come from the same data as the checkout ETA: min and max days per district (two integers each) from one source in Blade. The text is made by Kayaa.formatEta (app.js). -->
<script type="application/json" id="district-eta">
${renderDistrictEtaJson(ctx).split('\n').map((l) => '  ' + l).join('\n')}
</script>
<div class="notice">${icon('info')}<div><p><mark class="todo"><span class="marker-label">To confirm</span> These are the two zones from the site config (Colombo and suburbs, the rest of the island). Per-district estimates will come from the backend. Colombo district is the only district counted as "Colombo and suburbs" for now.</mark></p></div></div>
${provinces}`;
}

/* ---------- a whole content page ---------- */
export function renderContentPage(name, ctx) {
  const page = ctx.content.pages[name];
  if (!page) throw new Error('gen content: unknown page ' + name);
  const { front, blocks } = page;
  const inl = (t) => inline(t, ctx);
  const root = ctx.root;
  const help = `<section class="help-card" aria-labelledby="help-title">
  <div>
    <h2 id="help-title">Still need help?</h2>
    <p>Message us on WhatsApp <span data-cfg="whatsapp_display">${escapeHtml(ctx.cfgText('whatsapp_display'))}</span> or send us a note from the contact page.</p>
  </div>
  <div class="help-card__actions">
    <a class="btn btn--primary" href="https://wa.me/" target="_blank" rel="noopener">${icon('message-circle', 16)} Chat on WhatsApp</a>
    <a class="btn btn--secondary" href="${root}contact.html">Contact us</a>
  </div>
</section>`;

  // lede = the first paragraph before the first heading; other paragraphs before it stay in the prose
  let lede = '';
  const first = blocks.findIndex((b) => b.type !== 'p');
  const head = blocks.slice(0, first < 0 ? blocks.length : first);
  const rest = blocks.slice(head.length);
  if (head.length && head[0].type === 'p') lede = head.shift().text;

  const out = [];
  const tocItems = [];
  const idFor = (text) => (page.ids && page.ids[text]) || slugify(text);
  const body = [];
  if (head.length) body.push(renderBlocks(head, ctx));
  let section = null;   // the heading text of the section being rendered
  let noticeOpen = false;
  const closeNotice = () => { if (noticeOpen) { body.push('  </div></div>'); noticeOpen = false; } };
  let lastHeading = '';
  let cardDone = false;
  for (let i = 0; i < rest.length; i++) {
    const b = rest[i];
    if (b.type === 'h2' || b.type === 'h3') {
      closeNotice();
      if (b.type === 'h2' && page.card && !cardDone && section === page.card.after) { body.push(renderSizeGuideSlider(ctx, 'page')); cardDone = true; }
      const id = idFor(b.text);
      const tag = b.type;
      if (tag === 'h2') { tocItems.push([id, plain(b.text, ctx.cfgText)]); section = b.text; }
      lastHeading = b.text;
      body.push(`<${tag} id="${id}">${inl(b.text)} <a class="anchor" href="#${id}" aria-label="Link to this section: ${escapeHtml(plain(b.text, ctx.cfgText))}">${icon('link', 16)}</a></${tag}>`);
      if (tag === 'h2' && (page.callouts || []).includes(b.text)) { body.push(`<div class="notice">${icon('info')}<div>`); noticeOpen = true; }
      continue;
    }
    if (b.type === 'component') { body.push(deliveryTable(ctx)); continue; }
    if (b.type === 'table') {
      const two = b.head.length === 2;
      body.push(`<div class="table-wrap">
  <table class="size-table${two ? ' size-table--two' : ''}">
    <caption class="visually-hidden">${escapeHtml(plain(lastHeading, ctx.cfgText))}</caption>
    <thead>
      <tr>${b.head.map((h) => `<th scope="col">${inl(h)}</th>`).join('')}</tr>
    </thead>
    <tbody>
${b.rows.map((r) => '      <tr>' + r.map((c, k) => (k === 0 ? `<th scope="row">${inl(c)}</th>` : `<td data-label="${escapeHtml(plain(b.head[k], ctx.cfgText))}"><span>${inl(c)}</span></td>`)).join('') + '</tr>').join('\n')}
    </tbody>
  </table>
</div>`);
      continue;
    }
    if (b.type === 'ol' && page.steps && page.steps[section]) {
      const icons = page.steps[section];
      body.push(`<ol class="how-steps${b.items.length === 4 ? ' how-steps--row' : ''}" role="list">\n${b.items.map((item, k) => {
        const m = item.match(/^\*\*(.+?)\*\*\s*(.*)$/);
        const title = m ? m[1].replace(/\.$/, '') : '';
        return `  <li><span class="how-steps__icon">${icon(icons[k] || 'check', 24)}</span>${title ? `<h3>${inl(title)}</h3>` : ''}<p>${inl(m ? m[2] : item)}</p></li>`;
      }).join('\n')}\n</ol>`);
      continue;
    }
    body.push(renderBlocks([b], ctx));
  }
  closeNotice();
  if (page.faqs) {
    const f = page.faqs;
    tocItems.push([f.id, f.heading]);
    body.push(`<h2 id="${f.id}">${f.heading} <a class="anchor" href="#${f.id}" aria-label="Link to this section: ${f.heading}">${icon('link', 16)}</a></h2>\n<div class="accordion">\n${renderFaqs(f.placement, ctx)}\n</div>`);
  }

  const toc = page.toc ? `<details class="toc" data-toc open>
  <summary class="toc__summary">On this page ${icon('chevron-down', 16)}</summary>
  <ol class="toc__list" role="list">
${tocItems.map(([id, t]) => `    <li><a href="#${id}">${escapeHtml(t)}</a></li>`).join('\n')}
  </ol>
</details>` : '';

  out.push(`<div class="container content">
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <ol>
      <li><a href="${root}index.html">Home</a></li>
      <li aria-current="page">${escapeHtml(page.label)}</li>
    </ol>
  </nav>
  <header class="content__head">
    <h1>${inl(front.h1 || page.label)}</h1>${lede ? `\n    <p class="content__lede">${inl(lede).replace(/\n/g, '<br>')}</p>` : ''}
    <p class="content__updated">Last updated ${updatedHtml(front.updated, inl)}</p>
  </header>
  <div class="content__layout${page.toc ? ' content__layout--toc' : ''}">
${toc ? toc.split('\n').map((l) => '    ' + l).join('\n') + '\n' : ''}    <div class="content__main">
      <div class="prose${page.split ? ' prose--split' : ''}">
${body.join('\n').split('\n').map((l) => '        ' + l).join('\n')}
      </div>
${help.split('\n').map((l) => '      ' + l).join('\n')}
    </div>
  </div>
</div>`);
  return out.join('\n');
}

// "4 October 2026" becomes <time datetime="2026-10-04">4 October 2026</time>; anything else (a marker) goes through the normal inline rules
const MONTHS = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
function updatedHtml(value, inl) {
  const m = String(value || '').trim().match(/^(\d{1,2}) ([A-Za-z]+) (\d{4})$/);
  const mi = m ? MONTHS.indexOf(m[2].toLowerCase()) : -1;
  if (mi < 0) return inl(value || '[[TODO: publication date]]');
  const iso = `${m[3]}-${String(mi + 1).padStart(2, '0')}-${m[1].padStart(2, '0')}`;
  return `<time datetime="${iso}">${m[1]} ${m[2]} ${m[3]}</time>`;
}

/* ---------- size guide slider: "How to find your size" (size guide page card and the product page panel) ----------
   Three steps, each an illustration (a photo marker pair that tools/process-images.mjs fills) and a caption in HTML text.
   Without JavaScript the slides stack; assets/js/size-guide.js turns them into a scroll-snap slider with buttons and dots. */
const SG_STEPS = [
  { lead: 'Measure height.', text: 'With your baby lying flat, measure from the top of the head to the heel.' },
  { lead: 'Check weight.', text: 'The weight from the last clinic visit is fine.' },
  { lead: 'Between two sizes?', text: 'Go up. A slightly roomy fit is more comfortable in the heat.' },
];
export function renderSizeGuideSlider(ctx, variant = 'page') {
  const panel = variant === 'panel';
  const suffix = panel ? '-panel' : '';
  const p = panel ? 'sgp' : 'sg';
  const n = SG_STEPS.length;
  const label = panel ? 'How to measure your baby' : 'How to find your size';
  const placeholder = `<div class="sg__media" data-placeholder>${icon('image', 24)}</div>`;
  const slides = SG_STEPS.map((s, i) => `<div class="sg__slide" role="group" aria-roledescription="slide" aria-label="${i + 1} of ${n}" id="${p}-slide-${i + 1}">
  <!-- photo: size-guide-${i + 1}${suffix} -->
  ${placeholder}
  <!-- /photo: size-guide-${i + 1}${suffix} -->
  <p class="sg__caption" data-sg-caption><strong>${s.lead}</strong> ${s.text}</p>
</div>`).join('\n');
  const dots = SG_STEPS.map((s, i) => `<li><button type="button" class="sg__dot" data-sg-dot aria-label="Go to step ${i + 1}"${i === 0 ? ' aria-current="true"' : ''}></button></li>`).join('\n        ');
  const slider = `<div class="sg" role="group" aria-roledescription="carousel" aria-label="${label}" data-sg>
  <div class="sg__track" tabindex="0" data-sg-track>
${slides.split('\n').map((l) => '    ' + l).join('\n')}
  </div>
  <div class="sg__controls" data-sg-controls hidden>
    <button type="button" class="sg__btn" data-sg-prev aria-label="Previous step" aria-disabled="true">${icon('chevron-left', 24)}</button>
    <div class="sg__status">
      <ul class="sg__dots" role="list">
        ${dots}
      </ul>
      <p class="sg__count"><span data-sg-now>1</span> of ${n}</p>
    </div>
    <button type="button" class="sg__btn" data-sg-next aria-label="Next step">${icon('chevron-right', 24)}</button>
  </div>
  <p class="visually-hidden" role="status" aria-live="polite" data-sg-live></p>
</div>`;
  if (panel) return slider;
  return `<section class="sg-card" aria-labelledby="sg-title">
  <h3 class="sg-card__title" id="sg-title">How to find your size</h3>
${slider.split('\n').map((l) => '  ' + l).join('\n')}
</section>`;
}

/* ---------- size table (the pack is the source: product page and size guide show the same rows) ---------- */
export function sizeTableRows(repo) {
  const { blocks } = readMarkdown(path.join(repo, 'docs', 'content', 'size-guide.md'));
  const t = blocks.find((b) => b.type === 'table');
  if (!t) throw new Error('size-guide.md has no size chart table');
  return t.rows.map(([size, height, weight]) => ({ size, height, weight }));
}

/* ---------- category and size intros (category-and-age-intros.md) ---------- */
// A head value (title, meta description, JSON-LD) never carries a content marker: when the text still holds [[TODO]] or [[PROPOSED]],
// the page's meta_fallback is used instead (the visible page keeps the marker). Without a fallback the bracketed text stays and check-seo fails.
export function safeMeta(raw, fallback, cfgText) {
  return /\[\[(TODO|PROPOSED)/.test(String(raw)) && fallback ? plain(fallback, cfgText) : plain(raw, cfgText);
}

export function loadIntros(repo, ctx) {
  const lines = fs.readFileSync(path.join(repo, 'docs', 'content', 'category-and-age-intros.md'), 'utf8').replace(/\r\n/g, '\n').split('\n');
  const out = { categories: {}, sizes: {} };
  let kind = null, cur = null;
  for (const l of lines) {
    let m;
    if ((m = l.match(/^## (Categories|Sizes)/))) { kind = m[1] === 'Categories' ? 'categories' : 'sizes'; cur = null; continue; }
    if ((m = l.match(/^### .*\(slug:\s*([^)]+)\)/)) && kind) { cur = out[kind][m[1].trim()] = {}; continue; }
    if ((m = l.match(/^- (h1|meta_title|meta_description|meta_fallback|intro):\s*(.*)$/)) && cur) cur[m[1]] = m[2].trim();
  }
  const shaped = {};
  for (const kind2 of ['categories', 'sizes']) {
    shaped[kind2] = {};
    for (const [slug, v] of Object.entries(out[kind2])) {
      shaped[kind2][slug] = {
        h1: plain(v.h1, ctx.cfgText),
        metaTitle: plain(v.meta_title, ctx.cfgText),
        metaDescription: safeMeta(v.meta_description, v.meta_fallback, ctx.cfgText),
        intro: inline(v.intro, ctx)
      };
    }
  }
  return shaped;
}

/* ---------- marker counts for the review index card ---------- */
export function countMarkers(repo) {
  const dir = path.join(repo, 'docs', 'content');
  const rows = [];
  let todo = 0, proposed = 0;
  for (const f of fs.readdirSync(dir).filter((x) => x.endsWith('.md')).sort()) {
    const t = fs.readFileSync(path.join(dir, f), 'utf8');
    const a = (t.match(/\[\[TODO\b/g) || []).length, b = (t.match(/\[\[PROPOSED\b/g) || []).length;
    todo += a; proposed += b;
    if (a || b) rows.push([f, a, b]);
  }
  return { todo, proposed, rows };
}

export function renderTodoCounts(ctx) {
  const c = countMarkers(ctx.repo);
  return `<section class="review__group" aria-labelledby="review-todos">
  <h2 id="review-todos">Content to approve</h2>
  <ul class="review__list" role="list">
    <li class="review__item">
      <div class="review__text"><a class="review__name" href="review-decisions.html">Open decisions for the client</a><p class="review__note"><strong>${c.todo}</strong> facts only Kayaa knows to confirm and <strong>${c.proposed}</strong> proposed defaults to approve, in docs/content. Nothing ships while markers remain; run <code>node tools/list-todos.mjs</code> for the list per page.</p></div>
      <span class="badge badge--outline">${c.todo + c.proposed} open</span>
    </li>
  </ul>
</section>`;
}

export { parseFrontMatter };
