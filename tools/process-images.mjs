#!/usr/bin/env node
// Turns the original photos in tools/image-source/ into the responsive WebP files the prototype uses,
// and wires them into the pages. Dev-only: tools/ and tools/image-source/ are never published
// (the Pages workflow publishes html/ only, and tools/image-source/ is in .gitignore).
//
//   node tools/process-images.mjs                  process everything found, wire the pages
//   node tools/process-images.mjs --source <dir>   read originals from another folder
//
// What it does, per slot (see SLOTS below):
//   1. finds <slot>.<any extension> in the source folder (missing ones are skipped, never fatal)
//   2. crops to the slot ratio (4:5, banners 1:1) with sharp's attention strategy, once, at the largest size,
//      so every width shares the same framing; never upscales
//   3. exports WebP at 480 / 800 / 1200 (hero also 1600) to html/assets/img/<dir>/<name>-<width>.webp,
//      EXIF/GPS stripped; re-encodes at lower quality until the size budget is met
//   4. rewrites each <!-- photo: ID --> ... <!-- /photo: ID --> region in the pages with <img srcset> markup
//      when the photo exists, or with the original placeholder when it does not
//   5. writes docs/image-credits.md from tools/image-source/credits.csv
//      (columns: file, source, photographer, url; optional: alt)
//
// Illustrations (the size guide slider) are a second kind of slot: 4:3, never cropped, never upscaled, and never re-encoded when they
// can be copied. See ILLUSTRATIONS and processIllustration() below.
//
// Safety: the script only ever deletes files it wrote itself. Every file it writes is listed in tools/image-manifest.json; a file that is not
// in the manifest (placed there by hand, or by an older version) is never removed.
//
// sharp is NOT a dependency of the repo. Install it once in a temp folder outside the repo:
//   mkdir %TEMP%\kayaa-sharp && cd %TEMP%\kayaa-sharp && npm init -y && npm i sharp
// then run this script as usual. It looks in $KAYAA_SHARP, else <os temp dir>/kayaa-sharp.
// Plain Node otherwise (fs, path, os).

import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const repo = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const arg = (name) => { const i = process.argv.indexOf(name); return i > -1 ? process.argv[i + 1] : null; };
const sourceDir = path.resolve(arg('--source') || path.join(repo, 'tools', 'image-source'));
const htmlDir = path.join(repo, 'html');
const imgRoot = path.join(htmlDir, 'assets', 'img');

/* ---------------------------------------------------------------- slots */

const R45 = [4, 5];
const R11 = [1, 1];
const ALT_TODO = '<!-- TODO: check this alt text against the photo -->';

// file: base name of the original; dir: output folder under html/assets/img
// Sample gallery layout of product.html: lilac 4, cream 3, sky 3 and one shared photo (data-colour="all").
// Colour photos are named product-<colour>-<n>; photos without a colour are product-<n>.
const COLOUR_SETS = [['lilac', 4], ['cream', 3], ['sky', 3]];
const GALLERY = [
  ...COLOUR_SETS.flatMap(([colour, count]) => Array.from({ length: count }, (_, i) => ({ colour, n: i + 1, id: `product-${colour}-${i + 1}` }))),
  { colour: 'all', n: 1, id: 'product-1' },
];
const GALLERY_TONES = { lilac: ['var(--tone-4)', 'var(--tone-3)'], cream: ['var(--swatch-cream)', 'var(--cream)'], sky: ['var(--sky-tint)', 'var(--accent)'], all: ['var(--tone-2)'] };
const galleryTone = (g) => GALLERY_TONES[g.colour][(g.n - 1) % GALLERY_TONES[g.colour].length];
const galleryLabel = (g) => `Product gallery: ${g.colour === 'all' ? 'shared' : g.colour} ${g.n}`;

const SLOTS = [
  { id: 'hero', label: 'Hero', dir: 'hero', ratio: R45, widths: [480, 800, 1200, 1600], budget: [1200, 130] },
  { id: 'banner-newborn', label: 'Promo banner: newborn', dir: 'banner', ratio: R11 },
  { id: 'banner-sale', label: 'Promo banner: sale', dir: 'banner', ratio: R11 },
  { id: 'cat-newborn', label: 'Category: newborn', dir: 'category', ratio: R45 },
  { id: 'cat-bodysuits', label: 'Category: bodysuits', dir: 'category', ratio: R45 },
  { id: 'cat-sleepwear', label: 'Category: sleepwear', dir: 'category', ratio: R45 },
  { id: 'cat-sets', label: 'Category: sets', dir: 'category', ratio: R45 },
  { id: 'cat-outerwear', label: 'Category: outerwear', dir: 'category', ratio: R45 },
  { id: 'cat-napkins', label: 'Category: napkins', dir: 'category', ratio: R45 },
  { id: 'cat-accessories', label: 'Category: accessories', dir: 'category', ratio: R45 },
  ...GALLERY.map((g) => ({ id: g.id, label: galleryLabel(g), dir: 'product', ratio: R45 })),
].map((s) => ({ widths: [480, 800, 1200], budget: [800, 70], ...s }));   // budget: [checkpoint width, max KB]

// Illustrations: AI-generated line art on a lilac background (Google Gemini, created for Kayaa on 4 October 2026).
// source = the base name of the original in tools/image-source (any extension, any case); id = the slot and the output name.
const ILLUSTRATIONS = [
  { id: 'size-guide-1', source: 'size-guide-1-height', label: 'Size guide: 1 height', alt: 'Illustration of a baby lying flat on a blanket with a tape measure running from head to heel, held in place by a hand.' },
  { id: 'size-guide-2', source: 'size-guide-2-weight', label: 'Size guide: 2 weight', alt: 'Illustration of a baby lying on a baby weighing scale beside a clinic record booklet.' },
  { id: 'size-guide-3', source: 'size-guide-3-between-sizes', label: 'Size guide: 3 between sizes', alt: 'Illustration of two baby bodysuits laid flat, a smaller one with an arrow pointing to a larger one that has a check mark above it.' },
].map((s) => ({ illustration: true, dir: 'size-guide', ratio: [4, 3], widths: [480, 800, 1200], ...s }));
SLOTS.push(...ILLUSTRATIONS);
const ILLUSTRATION_TINT = '#EAE0F2';   // the illustration background (--secondary); used behind a letter-boxed image
const ILLUSTRATION_BUDGET = (w) => (w <= 480 ? 40 : w <= 800 ? 60 : 100);   // KB: 60 at 800w and 100 at 1200w as agreed; 40 at 480w

const CAT_TONES = { 'cat-newborn': 1, 'cat-bodysuits': 2, 'cat-sleepwear': 3, 'cat-sets': 4, 'cat-outerwear': 1, 'cat-napkins': 2, 'cat-accessories': 3 };
const PRODUCT_ALT = 'Product photo placeholder (TODO)';

// Wiring: one region per marker pair in a page. photo() builds the <img> variant, placeholder() the fallback.
const icon = '<svg class="icon" aria-hidden="true" focusable="false"><use href="#i-image"></use></svg>';
const REGIONS = [
  {
    id: 'hero', slot: 'hero', page: 'index.html',
    sizes: '(min-width: 900px) 460px, (min-width: 440px) 420px, calc(100vw - 40px)',
    eager: true, alt: 'A baby wearing soft everyday clothing', altTodo: true,
    wrap: (img) => `<div class="media media--round hero__media" style="--tone: var(--tone-2)">${img}</div>`,
    placeholder: () => `<div class="media media--round hero__media" data-placeholder style="--tone: var(--tone-2)">${icon}</div>`,
  },
  ...['newborn', 'sale'].map((k, i) => ({
    id: `banner-${k}`, slot: `banner-${k}`, page: 'index.html',
    sizes: '(min-width: 900px) 240px, 120px', alt: '',   // decorative: the card text carries the meaning
    wrap: (img) => `<div class="promo__photo" style="--tone: var(--tone-${i ? 4 : 3})">${img}</div>`,
    placeholder: () => '',
  })),
  ...Object.entries(CAT_TONES).map(([id, tone]) => ({
    id, slot: id, page: 'index.html',
    sizes: '(min-width: 1200px) 190px, (min-width: 600px) 31vw, 47vw', alt: '',   // decorative: the label pill names the link
    wrap: (img) => `<div class="media" style="--ratio: 4 / 5; --tone: var(--tone-${tone})">${img}</div>`,
    placeholder: () => `<div class="media" data-placeholder style="--ratio: 4 / 3; --tone: var(--tone-${tone})">${icon}</div>`,
  })),
  ...GALLERY.flatMap((g, k) => [
    {
      id: g.id, slot: g.id, page: 'product.html', widths: [800, 1200], colour: g.colour,
      sizes: '(min-width: 900px) 46vw, calc(100vw - 40px)', eager: k === 0, alt: PRODUCT_ALT,   // product.js rewrites the alt per colour
      wrap: (img) => `<div class="media" data-colour="${g.colour}" style="--tone: ${galleryTone(g)}">${img}</div>`,
      placeholder: () => `<div class="media" data-colour="${g.colour}" data-placeholder style="--tone: ${galleryTone(g)}">${icon}</div>`,
    },
    {
      id: `${g.id}-thumb`, slot: g.id, page: 'product.html', widths: [480], colour: g.colour,
      sizes: '72px', alt: '',   // the thumbnail button already has an aria-label
      // a <span>, not a <div>: the thumbnail sits inside a <button>, which may only hold phrasing content
      wrap: (img) => `<span class="media" data-colour="${g.colour}" style="--tone: ${galleryTone(g)}">${img}</span>`,
      placeholder: () => `<span class="media" data-colour="${g.colour}" data-placeholder style="--tone: ${galleryTone(g)}">${icon}</span>`,
    },
  ]),
];

// Size guide slides. The same three images appear on size-guide.html and, lazily, in the product page's size guide panel.
const SG_ICON = icon;
for (const il of ILLUSTRATIONS) {
  const wrap = (img) => `<div class="sg__media">${img}</div>`;
  const placeholder = () => `<div class="sg__media" data-placeholder>${SG_ICON}</div>`;
  REGIONS.push(
    { id: il.id, slot: il.id, page: 'size-guide.html', largestDims: true, sizes: '(min-width: 1100px) 480px, (min-width: 600px) 640px, calc(100vw - 40px)', eager: il.id === 'size-guide-1', alt: il.alt, wrap, placeholder },
    { id: il.id + '-panel', slot: il.id, page: 'product.html', largestDims: true, sizes: '(min-width: 600px) 480px, calc(100vw - 80px)', eager: false, alt: il.alt, wrap, placeholder }
  );
}

/* ---------------------------------------------------------------- helpers */

const EXTS = ['.jpg', '.jpeg', '.png', '.webp', '.tif', '.tiff', '.avif', '.heic'];
function findSource(name) {
  if (!fs.existsSync(sourceDir)) return null;
  const hit = fs.readdirSync(sourceDir).find((f) => {
    const ext = path.extname(f).toLowerCase();
    return EXTS.includes(ext) && path.basename(f, path.extname(f)).toLowerCase() === name.toLowerCase();
  });
  return hit ? path.join(sourceDir, hit) : null;
}

let sharpMod = null;
async function loadSharp() {
  if (sharpMod) return sharpMod;
  const dir = process.env.KAYAA_SHARP || path.join(os.tmpdir(), 'kayaa-sharp');
  try {
    sharpMod = createRequire(path.join(dir, 'noop.js'))('sharp');
  } catch {
    console.error(`sharp not found in ${dir}.\nInstall it outside the repo:\n  mkdir "${dir}" && cd "${dir}" && npm init -y && npm i sharp\n(or set KAYAA_SHARP to the folder that contains node_modules/sharp)`);
    process.exit(1);
  }
  return sharpMod;
}

// Accept any product-<colour>-<n> (colour photos) and product-<n> (shared photos) that exist, even beyond the sample layout.
if (fs.existsSync(sourceDir)) {
  for (const file of fs.readdirSync(sourceDir)) {
    const ext = path.extname(file).toLowerCase();
    const name = path.basename(file, path.extname(file)).toLowerCase();
    if (EXTS.includes(ext) && /^product-(?:[a-z]+-)?\d+$/.test(name) && !SLOTS.some((s) => s.id === name)) {
      SLOTS.push({ id: name, label: `Product gallery: ${name.replace(/^product-/, '')}`, dir: 'product', ratio: R45, widths: [480, 800, 1200], budget: [800, 70] });
    }
  }
}

// Safety: the pipeline may only delete files it generated itself. Everything it writes is listed in tools/image-manifest.json;
// a .webp that is not listed there (put in place by hand, or by an older run) is never deleted, whatever its name looks like.
const manifestPath = path.join(repo, 'tools', 'image-manifest.json');
let prevManifest = [];
try { prevManifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8')).files || []; } catch (e) { /* first run: nothing is deletable */ }
const produced = new Set();
const relImg = (file) => path.relative(imgRoot, file).split(path.sep).join('/');
function safeDelete(file) {
  const r = relImg(file);
  const inside = !r.startsWith('..') && !path.isAbsolute(r);
  if (!inside || !r.endsWith('.webp') || !prevManifest.includes(r)) return false;
  if (fs.existsSync(file)) fs.unlinkSync(file);
  return true;
}

const kb = (n) => Math.round(n / 1024);
const esc = (s) => s.replace(/&/g, '&amp;').replace(/"/g, '&quot;');

// Rough "dislikes green, yellow or orange" check on the finished crop: share of clearly coloured pixels in hue 20-170.
async function warmGreenShare(sharp, buf) {
  const { data, info } = await sharp(buf).resize(64, 80, { fit: 'fill' }).removeAlpha().raw().toBuffer({ resolveWithObject: true });
  let hit = 0, total = info.width * info.height;
  for (let i = 0; i < data.length; i += 3) {
    const r = data[i] / 255, g = data[i + 1] / 255, b = data[i + 2] / 255;
    const max = Math.max(r, g, b), min = Math.min(r, g, b), d = max - min;
    if (max < 0.3 || d / max < 0.3) continue;
    let h = max === r ? ((g - b) / d) % 6 : max === g ? (b - r) / d + 2 : (r - g) / d + 4;
    h = (h * 60 + 360) % 360;
    if (h >= 20 && h <= 170) hit++;
  }
  return hit / total;
}

/* ---------------------------------------------------------------- processing */

// Illustrations: never cropped, never upscaled. Within 3% of 4:3 the image is used as it is; otherwise it is letter-boxed (object-fit: contain)
// on the lilac tint. A WebP source is copied byte for byte when a variant would have the source's own size and fits its budget;
// smaller widths are resized (lanczos3) and encoded at quality 88 (a lossy source loses as little as possible), stepping down to 72 only when
// the budget needs it. PNG and JPEG sources use quality 82. Metadata is stripped.
const illustrationInfo = [];
async function processIllustration(slot, input, sharp, outDir, stale) {
  const meta = await sharp(input).metadata();
  const srcW = meta.width, srcH = meta.height;
  const ratio = srcW / srcH;
  const off = ratio / (4 / 3) - 1;
  const asIs = Math.abs(off) <= 0.03;
  let widths = slot.widths.filter((w) => w <= srcW);
  if (srcW > 800 && srcW < 1200) widths.push(srcW);
  if (!widths.length) widths = [srcW];
  widths = [...new Set(widths)].sort((a, b) => a - b);
  const isWebp = meta.format === 'webp';
  const startQ = isWebp ? 88 : 82;
  const floorQ = 72;
  const heightFor = (w) => Math.round((w * 3) / 4);
  const render = (w, q) => {
    let img = sharp(input).rotate();
    img = asIs
      ? img.resize(w, heightFor(w), { fit: 'fill', kernel: 'lanczos3' })
      : img.resize(w, heightFor(w), { fit: 'contain', background: ILLUSTRATION_TINT, kernel: 'lanczos3' }).flatten({ background: ILLUSTRATION_TINT });
    return img.webp({ quality: q, effort: 5, smartSubsample: true }).toBuffer();   // no withMetadata(): EXIF, ICC and XMP are dropped
  };
  fs.mkdirSync(outDir, { recursive: true });
  stale().forEach((f) => safeDelete(path.join(outDir, f)));
  const files = [];
  for (const w of widths) {
    const budget = ILLUSTRATION_BUDGET(w) * 1024;
    const target = path.join(outDir, `${slot.id}-${w}.webp`);
    let how, q = null, buf;
    if (isWebp && asIs && w === srcW && fs.statSync(input).size <= budget) {
      buf = fs.readFileSync(input);
      how = 'copied unchanged';
    } else {
      q = startQ;
      buf = await render(w, q);
      while (buf.length > budget && q > floorQ) { q -= 2; buf = await render(w, q); }
      how = `re-encoded q${q}${isWebp ? ' from a lossy WebP' : ''}`;
      if (buf.length > budget) warnings.push(`${slot.id}: ${w}w is ${kb(buf.length)}KB at the quality floor ${floorQ} (budget ${budget / 1024}KB)`);
    }
    fs.writeFileSync(target, buf);
    produced.add(relImg(target));
    files.push({ w, bytes: buf.length, how });
  }
  const largest = widths[widths.length - 1];
  const base = widths.filter((w) => w <= 800).pop() || widths[0];
  results[slot.id] = { dir: slot.dir, widths, base, largest, height: heightFor(base), q: null, files, ratio: [4, 3] };
  report.push({ slot: slot.id, q: 'n/a', files });
  illustrationInfo.push({ id: slot.id, file: path.basename(input), format: meta.format, srcW, srcH, ratio: ratio.toFixed(4), off: (off * 100).toFixed(1) + '%', mode: asIs ? 'used as is (within 3% of 4:3)' : 'contain on lilac (not cropped)', alpha: meta.hasAlpha, bytes: fs.statSync(input).size, files });
}

const results = {};   // slot id -> { widths:[...], width, height, files, ... }
const missing = [];
const warnings = [];
const report = [];

for (const slot of SLOTS) {
  const outDir = path.join(imgRoot, slot.dir);
  const stale = () => fs.existsSync(outDir) ? fs.readdirSync(outDir).filter((f) => f.startsWith(slot.id + '-') && f.endsWith('.webp')) : [];
  const input = findSource(slot.source || slot.id);
  if (!input) {
    stale().forEach((f) => safeDelete(path.join(outDir, f)));   // no source: back to the placeholder (only files the pipeline wrote)
    missing.push(slot.id);
    continue;
  }

  const sharp = await loadSharp();
  if (slot.illustration) { await processIllustration(slot, input, sharp, outDir, stale); continue; }
  const meta = await sharp(input).metadata();
  const swap = (meta.orientation || 1) >= 5;
  const srcW = swap ? meta.height : meta.width;
  const srcH = swap ? meta.width : meta.height;
  const [rw, rh] = slot.ratio;
  const maxCropW = Math.min(srcW, Math.floor((srcH * rw) / rh));
  const maxW = Math.min(slot.widths[slot.widths.length - 1], maxCropW);

  // one attention crop at the largest size; every smaller width is resized from it
  const master = await sharp(input)
    .rotate()
    .resize(maxW, Math.round((maxW * rh) / rw), { fit: 'cover', position: sharp.strategy.attention })
    .png({ compressionLevel: 1 })
    .toBuffer();

  let widths = slot.widths.filter((w) => w <= maxW);
  if (!widths.includes(maxW) && maxW > Math.max(0, ...widths)) widths.push(maxW);
  if (!widths.length) widths = [maxW];
  widths.sort((a, b) => a - b);
  if (maxW < slot.widths[slot.widths.length - 1]) warnings.push(`${slot.id}: original is only ${srcW}x${srcH}, largest output is ${maxW}px (never upscaled)`);

  const encode = (w, q) => sharp(master).resize(w, Math.round((w * rh) / rw), { fit: 'fill' }).webp({ quality: q, effort: 6 }).toBuffer();

  // quality: start at 78, step down until the slot's budget width is under its KB limit
  const checkW = widths.includes(slot.budget[0]) ? slot.budget[0] : widths.filter((w) => w <= slot.budget[0]).pop() || widths[0];
  let q = 78;
  let probe = await encode(checkW, q);
  while (probe.length > slot.budget[1] * 1024 && q > 40) { q -= 6; probe = await encode(checkW, q); }
  if (probe.length > slot.budget[1] * 1024) warnings.push(`${slot.id}: still ${kb(probe.length)}KB at ${checkW}w with quality ${q} (budget ${slot.budget[1]}KB)`);

  fs.mkdirSync(outDir, { recursive: true });
  stale().forEach((f) => safeDelete(path.join(outDir, f)));
  const files = [];
  for (const w of widths) {
    const buf = w === checkW ? probe : await encode(w, q);
    fs.writeFileSync(path.join(outDir, `${slot.id}-${w}.webp`), buf);
    produced.add(relImg(path.join(outDir, `${slot.id}-${w}.webp`)));
    files.push({ w, bytes: buf.length });
  }

  const share = await warmGreenShare(sharp, master);
  if (share > 0.15) warnings.push(`${slot.id}: ${Math.round(share * 100)}% of the image is green/yellow/orange. Check it by eye; the palette is lilac, cream and blue`);

  const base = widths.filter((w) => w <= 800).pop() || widths[0];
  results[slot.id] = { dir: slot.dir, widths, base, height: Math.round((base * rh) / rw), q, files, ratio: slot.ratio };
  report.push({ slot: slot.id, q, files });
}

/* ---------------------------------------------------------------- credits */

function parseCsv(text) {
  const rows = []; let row = [], cell = '', quoted = false;
  for (let i = 0; i < text.length; i++) {
    const c = text[i];
    if (quoted) {
      if (c === '"' && text[i + 1] === '"') { cell += '"'; i++; } else if (c === '"') quoted = false; else cell += c;
    } else if (c === '"') quoted = true;
    else if (c === ',') { row.push(cell); cell = ''; }
    else if (c === '\n' || c === '\r') { if (c === '\r' && text[i + 1] === '\n') i++; row.push(cell); rows.push(row); row = []; cell = ''; }
    else cell += c;
  }
  if (cell || row.length) { row.push(cell); rows.push(row); }
  return rows.filter((r) => r.some((x) => x.trim()));
}

const credits = {};
const csvPath = path.join(sourceDir, 'credits.csv');
if (fs.existsSync(csvPath)) {
  const rows = parseCsv(fs.readFileSync(csvPath, 'utf8'));
  const head = rows.shift().map((h) => h.trim().toLowerCase());
  for (const r of rows) {
    const rec = Object.fromEntries(head.map((h, i) => [h, (r[i] || '').trim()]));
    const key = path.basename(rec.file || '', path.extname(rec.file || '')).toLowerCase();
    if (key) credits[key] = rec;
  }
}
const licenceOf = (rec) => {
  const s = `${rec.source || ''} ${rec.url || ''}`.toLowerCase();
  if (s.includes('unsplash')) return 'Unsplash License';
  if (s.includes('pexels')) return 'Pexels License';
  if (s.includes('pixabay')) return 'Pixabay Content License';
  return 'TODO: confirm licence';
};
const cell = (v) => (v || '—').replace(/\|/g, '\\|');
const creditRows = SLOTS.map((s) => {
  const rec = credits[s.id] || {};
  const url = rec.url ? `<${rec.url}>` : '—';
  return `| ${s.label} | ${rec.file ? cell(rec.file) : (findSource(s.id) ? path.basename(findSource(s.id)) : '— (not supplied yet)')} | ${cell(rec.photographer)} | ${cell(rec.source)} | ${url} | ${rec.file ? licenceOf(rec) : '—'} |`;
});
fs.writeFileSync(path.join(repo, 'docs', 'image-credits.md'),
`# Image credits

Stock photos are placeholders for review. Replace with real product photography before launch.

Generated by \`node tools/process-images.mjs\` from \`tools/image-source/credits.csv\` (that folder is git-ignored). Credits are not shown on the pages.

| Slot | File | Photographer | Source | URL | Licence |
| --- | --- | --- | --- | --- | --- |
${creditRows.filter((_, i) => !SLOTS[i].illustration).join('\n')}

## AI-generated illustrations

AI-generated illustrations (Google Gemini), created for Kayaa on 4 October 2026. They are original artwork for this site, not stock photos, and are used on the size guide and in the product page size guide panel.

| Slot | Original file | Used for |
| --- | --- | --- |
${ILLUSTRATIONS.map((s) => `| ${s.label} | ${findSource(s.source) ? path.basename(findSource(s.source)) : '— (not supplied yet)'} | Size guide slider, step ${s.id.slice(-1)} |`).join('\n')}
`);

/* ---------------------------------------------------------------- wiring */

const altFor = (region) => {
  const rec = credits[region.slot];
  return rec && rec.alt && region.alt !== '' && !region.id.endsWith('-thumb') && !region.slot.startsWith('product') ? rec.alt : region.alt;
};

function photoHtml(region, res) {
  const wanted = region.widths ? res.widths.filter((w) => region.widths.includes(w)) : res.widths;
  const use = wanted.length ? wanted : res.widths;
  const src = (w) => `assets/img/${res.dir}/${region.slot}-${w}.webp`;
  const srcW = use.filter((w) => w <= 800).pop() || use[0];
  const dimW = region.largestDims ? use[use.length - 1] : srcW;   // illustrations: the width and height attributes describe the largest file
  const attrs = [
    `src="${src(srcW)}"`,
    `srcset="${use.map((w) => `${src(w)} ${w}w`).join(', ')}"`,
    `sizes="${region.sizes}"`,
    `width="${dimW}"`,
    `height="${Math.round((dimW * res.ratio[1]) / res.ratio[0])}"`,
    `alt="${esc(altFor(region))}"`,
    'decoding="async"',
    region.eager ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"',
  ];
  return region.wrap(`<img ${attrs.join(' ')}>`);
}

const pages = [...new Set(REGIONS.map((r) => r.page))];
let wired = 0, kept = 0;
for (const page of pages) {
  const file = path.join(htmlDir, page);
  let text = fs.readFileSync(file, 'utf8');
  for (const region of REGIONS.filter((r) => r.page === page)) {
    const open = `<!-- photo: ${region.id} -->`;
    const close = `<!-- /photo: ${region.id} -->`;
    const a = text.indexOf(open);
    const b = text.indexOf(close);
    if (a < 0 || b < a) { warnings.push(`${page}: marker pair for "${region.id}" not found`); continue; }
    const res = results[region.slot];
    const inner = res ? photoHtml(region, res) : region.placeholder();
    const todo = res && region.altTodo && !(credits[region.slot] && credits[region.slot].alt) ? ALT_TODO : '';
    const lineStart = text.lastIndexOf('\n', a) + 1;
    const before = text.slice(lineStart, a);
    let replacement;
    if (/^\s*$/.test(before)) {   // block marker on its own line
      const ind = before;
      replacement = `${open}${todo ? `\n${ind}${todo}` : ''}${inner ? `\n${ind}${inner}` : ''}\n${ind}${close}`;
    } else {                      // inline marker (e.g. inside a button)
      replacement = `${open}${inner}${close}`;
    }
    text = text.slice(0, a) + replacement + text.slice(b + close.length);
    res ? wired++ : kept++;
  }
  fs.writeFileSync(file, text);
}

for (const id of Object.keys(results)) {
  if (!REGIONS.some((r) => r.slot === id)) warnings.push(`${id}: processed, but product.html has no slot for it (the sample gallery is lilac 4, cream 3, sky 3, shared 1)`);
}

// files an earlier run wrote that this run no longer produces (an original was removed): only listed files, via safeDelete
for (const r of prevManifest) if (!produced.has(r)) safeDelete(path.join(imgRoot, r));
for (const entry of fs.existsSync(imgRoot) ? fs.readdirSync(imgRoot, { withFileTypes: true }).filter((e) => e.isDirectory()) : []) {
  const dir = path.join(imgRoot, entry.name);
  if (!fs.readdirSync(dir).length) fs.rmdirSync(dir);
}
fs.writeFileSync(manifestPath, JSON.stringify({ note: 'Written by tools/process-images.mjs: the files it generated under html/assets/img. It deletes only files listed here.', files: [...produced].sort() }, null, 2) + '\n');

/* ---------------------------------------------------------------- summary */

console.log('\nSizes (KB):');
for (const r of report) console.log(`  ${r.slot.padEnd(22)} ${r.q === 'n/a' ? '(see below)' : 'q' + r.q}  ` + r.files.map((f) => `${f.w}w ${kb(f.bytes)}`).join('  '));
if (illustrationInfo.length) {
  console.log('\nIllustrations:');
  for (const i of illustrationInfo) {
    console.log(`  ${i.id}  ${i.file}  ${i.format} ${i.srcW}x${i.srcH}  ratio ${i.ratio} (${i.off} from 4:3)  ${kb(i.bytes)}KB  alpha=${i.alpha}  -> ${i.mode}`);
    for (const f of i.files) console.log(`      ${f.w}w  ${kb(f.bytes)}KB  ${f.how}`);
  }
}
console.log(`\n${Object.keys(results).length} of ${SLOTS.length} images processed; ${wired} regions wired, ${kept} left as placeholders.`);
if (missing.length) console.log(`Missing originals (placeholders kept): ${missing.join(', ')}`);
warnings.forEach((w) => console.log('warning: ' + w));
if (!fs.existsSync(sourceDir)) console.log(`(no source folder at ${sourceDir})`);
