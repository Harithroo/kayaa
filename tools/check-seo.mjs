#!/usr/bin/env node
// SEO check for every page under html/ (dev-only, zero dependencies).
//   node tools/check-seo.mjs        errors exit 1; warnings are reported only
//
// Errors:  not exactly one <title>, meta description and h1; no canonical (or one on a page that must have none);
//          a duplicate title or description; a skipped heading level; an <img> without alt, width or height;
//          an internal link that does not resolve; invalid JSON-LD; an indexable page (except Home) without a BreadcrumbList.
// Warnings: a title over 60 characters; a description outside 120 to 160 characters; missing Open Graph tags;
//          on staging, a robots tag that is not noindex,nofollow.
// Prototype-only pages (review.html, review-decisions.html, proto-onepay.html) are skipped. Headings and h1s inside hidden elements (the other states of a
// page) are not counted. "Indexable" comes from the production robots values in tools/seo.json, not the staging override.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const repo = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const htmlDir = path.join(repo, 'html');
const seo = JSON.parse(fs.readFileSync(path.join(repo, 'tools', 'seo.json'), 'utf8'));
const SKIP = new Set(['review.html', 'review-decisions.html', 'proto-onepay.html']);
const VOID = new Set(['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr']);
const walk = (d) => fs.readdirSync(d, { withFileTypes: true }).flatMap((e) => (e.isDirectory() ? walk(path.join(d, e.name)) : e.name.endsWith('.html') ? [path.join(d, e.name)] : []));
const decode = (s) => String(s).replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#39;/g, "'");

const errors = [], warnings = [];
const err = (page, msg) => errors.push([page, msg]);
const warn = (page, msg) => warnings.push([page, msg]);
const titles = new Map(), descriptions = new Map();

// visible headings and h1 count: walk the tags and skip everything inside an element that has the hidden attribute
function visibleHeadings(body) {
  const out = [];
  const stack = [];                 // { tag, hidden }
  let hiddenDepth = 0;
  const re = /<(\/?)([a-zA-Z][\w-]*)([^>]*)>/g;
  let m;
  while ((m = re.exec(body))) {
    const closing = m[1] === '/', tag = m[2].toLowerCase(), attrs = m[3];
    if (VOID.has(tag) || /\/\s*$/.test(attrs)) continue;
    if (!closing) {
      const hidden = /\shidden(\s|=|$)/.test(' ' + attrs);
      stack.push({ tag, hidden });
      if (hidden) hiddenDepth++;
      if (/^h[1-6]$/.test(tag) && !hiddenDepth) {
        const end = body.indexOf('</' + tag + '>', m.index);
        out.push({ level: +tag[1], text: body.slice(m.index + m[0].length, end).replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim() });
      }
    } else {
      for (let i = stack.length - 1; i >= 0; i--) {
        if (stack[i].tag === tag) { const popped = stack.splice(i); popped.forEach((p) => { if (p.hidden) hiddenDepth--; }); break; }
      }
    }
  }
  return out;
}

for (const file of walk(htmlDir).sort()) {
  const label = path.relative(htmlDir, file).split(path.sep).join('/');
  if (SKIP.has(label)) continue;
  const raw = fs.readFileSync(file, 'utf8').replace(/\r\n/g, '\n');
  const noComments = raw.replace(/<!--[\s\S]*?-->/g, '');
  const entry = seo.pages[label];
  const syncRoot = (raw.match(/<!-- sync-root: (\S+) -->/) || [])[1];
  if (!entry) { err(label, 'no entry in tools/seo.json'); continue; }

  // title, description, canonical
  const titleTags = [...noComments.matchAll(/<title>([\s\S]*?)<\/title>/g)];
  if (titleTags.length !== 1) err(label, `${titleTags.length} <title> tags (need 1)`);
  const title = titleTags[0] ? decode(titleTags[0][1]).trim() : '';
  const descTags = [...noComments.matchAll(/<meta name="description" content="([^"]*)">/g)];
  if (descTags.length !== 1) err(label, `${descTags.length} meta descriptions (need 1)`);
  const desc = descTags[0] ? decode(descTags[0][1]).trim() : '';
  const canon = [...noComments.matchAll(/<link rel="canonical" href="([^"]*)">/g)];
  if (entry.canonical === false) { if (canon.length) err(label, 'an error page must not have a canonical'); }
  else if (canon.length !== 1) err(label, `${canon.length} canonical links (need 1)`);
  if (title.length > 60) warn(label, `title is ${title.length} characters (60 or fewer): "${title}"`);
  if (desc.length < 120 || desc.length > 160) warn(label, `description is ${desc.length} characters (120 to 160)`);
  if (title) titles.set(title, [...(titles.get(title) || []), label]);
  if (desc) descriptions.set(desc, [...(descriptions.get(desc) || []), label]);

  const robots = (noComments.match(/<meta name="robots" content="([^"]*)">/) || [])[1];
  if (!robots) err(label, 'no robots meta');
  else if (seo.staging !== false && robots !== 'noindex,nofollow') warn(label, `staging is on but robots is "${robots}"`);
  if (!/property="og:title"/.test(noComments) || !/name="twitter:card"/.test(noComments)) warn(label, 'missing Open Graph or Twitter tags');

  // headings
  const body = noComments.replace(/<script[\s\S]*?<\/script>/g, '').replace(/<style[\s\S]*?<\/style>/g, '');
  const headings = visibleHeadings(body.slice(body.indexOf('<body')));
  const h1s = headings.filter((h) => h.level === 1);
  if (h1s.length !== 1) err(label, `${h1s.length} visible h1 elements (need 1)`);
  let prev = 0;
  for (const h of headings) {
    if (prev && h.level > prev + 1) err(label, `heading level skipped: h${prev} then h${h.level} ("${h.text.slice(0, 40)}")`);
    prev = h.level;
  }

  // images
  for (const m of noComments.matchAll(/<img\b[^>]*>/g)) {
    if (!/\salt="/.test(m[0])) err(label, 'an <img> has no alt: ' + m[0].slice(0, 60));
    if (!/\swidth="/.test(m[0]) || !/\sheight="/.test(m[0])) err(label, 'an <img> has no width and height: ' + m[0].slice(0, 60));
  }

  // internal links
  for (const m of body.matchAll(/\b(?:href|src)="([^"]+)"/g)) {
    const u = m[1];
    if (/^(https?:|mailto:|tel:|#|data:|javascript:)/.test(u)) continue;
    const bare = u.split('#')[0].split('?')[0];
    if (!bare) continue;
    let target;
    if (syncRoot && u.startsWith(syncRoot)) target = path.join(htmlDir, bare.slice(syncRoot.length));
    else if (u.startsWith('/')) { err(label, 'root-absolute link ' + u); continue; }
    else target = path.join(path.dirname(file), bare);
    if (!fs.existsSync(target)) err(label, 'link does not resolve: ' + u);
  }

  // JSON-LD
  let hasBreadcrumb = false;
  for (const m of raw.matchAll(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/g)) {
    try { const j = JSON.parse(m[1]); if (j['@type'] === 'BreadcrumbList') hasBreadcrumb = true; }
    catch (e) { err(label, 'invalid JSON-LD: ' + e.message); }
  }
  const indexable = /^index/.test(entry.robots || '');
  if (indexable && label !== 'index.html' && !hasBreadcrumb) err(label, 'indexable page without a BreadcrumbList');
}

for (const [map, what] of [[titles, 'title'], [descriptions, 'description']]) {
  for (const [value, pages] of map) if (pages.length > 1) err(pages.join(', '), `duplicate ${what}: "${value.slice(0, 70)}"`);
}

const group = (list) => {
  const by = new Map();
  for (const [p, m] of list) by.set(p, [...(by.get(p) || []), m]);
  return by;
};
for (const [p, ms] of group(errors)) { console.log(`ERROR   ${p}`); ms.forEach((m) => console.log(`          ${m}`)); }
for (const [p, ms] of group(warnings)) { console.log(`WARNING ${p}`); ms.forEach((m) => console.log(`          ${m}`)); }
const pages = walk(htmlDir).filter((f) => !SKIP.has(path.relative(htmlDir, f).split(path.sep).join('/'))).length;
console.log(`Checked ${pages} pages: ${errors.length} error${errors.length === 1 ? '' : 's'}, ${warnings.length} warning${warnings.length === 1 ? '' : 's'}.`);
process.exit(errors.length ? 1 : 0);
