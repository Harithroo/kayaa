#!/usr/bin/env node
// Lists every open content marker (dev-only, zero dependencies).
//   node tools/list-todos.mjs                 counts and a list grouped by page and marker type
//   node tools/list-todos.mjs --fail-on-open  the same, and exit 1 when any marker is open (run it before launch)
//
// Sources
//   docs/content/*.md   [[TODO: ...]] (a fact only Kayaa knows) and [[PROPOSED: ...]] (a default for the client to approve)
//   html/**/*.html      the rendered <mark class="todo"> and <mark class="proposed"> (review.html, review-decisions.html and proto-onepay.html are prototype-only and skipped)
//   html/assets/js/catalogue-data.js   the category and size intros are rendered at runtime from this file, so their markers count as rendered too
//                                      (a marker inside a meta description shows as plain "[TODO: ...]" text, because a meta tag cannot hold markup)
// Markers written inside backticks (for example in the README) are documentation, not open markers.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const repo = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const failOnOpen = process.argv.includes('--fail-on-open');
const SKIP_HTML = new Set(['review.html', 'review-decisions.html', 'proto-onepay.html']);

const walk = (d) => fs.readdirSync(d, { withFileTypes: true }).flatMap((e) => (e.isDirectory() ? walk(path.join(d, e.name)) : e.name.endsWith('.html') ? [path.join(d, e.name)] : []));
const kindOf = (t) => (t === 'TODO' || t === 'todo' ? 'TODO' : 'PROPOSED');

const md = {};      // file -> [{ line, type, text }]
const dir = path.join(repo, 'docs', 'content');
for (const f of fs.readdirSync(dir).filter((x) => x.endsWith('.md')).sort()) {
  const lines = fs.readFileSync(path.join(dir, f), 'utf8').replace(/\r\n/g, '\n').split('\n');
  lines.forEach((line, i) => {
    const bare = line.replace(/`[^`]*`/g, '');
    for (const m of bare.matchAll(/\[\[(TODO|PROPOSED)(?::\s*([^\]]*))?\]\]/g)) (md[f] = md[f] || []).push({ line: i + 1, type: m[1], text: (m[2] || '').trim() });
  });
}

const html = {};    // page -> [{ type, text }]
const htmlDir = path.join(repo, 'html');
for (const file of walk(htmlDir).sort()) {
  const label = path.relative(htmlDir, file).split(path.sep).join('/');
  if (SKIP_HTML.has(label)) continue;
  const text = fs.readFileSync(file, 'utf8');
  for (const m of text.matchAll(/<mark class="(todo|proposed)">([\s\S]*?)<\/mark>/g)) {
    const t = m[2].replace(/<span class="marker-label">[^<]*<\/span>/, '').replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim();
    (html[label] = html[label] || []).push({ type: kindOf(m[1]), text: t });
  }
}

// the category and size intros reach the page at runtime from catalogue-data.js (generated from docs/content/category-and-age-intros.md)
const dataFile = path.join(htmlDir, 'assets', 'js', 'catalogue-data.js');
if (fs.existsSync(dataFile)) {
  const text = fs.readFileSync(dataFile, 'utf8').replace(/\\"/g, '"');
  const label = 'assets/js/catalogue-data.js (category intros)';
  for (const m of text.matchAll(/<mark class="(todo|proposed)">([\s\S]*?)<\/mark>/g)) {
    const t = m[2].replace(/<span class="marker-label">[^<]*<\/span>/, '').replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim();
    (html[label] = html[label] || []).push({ type: kindOf(m[1]), text: t });
  }
  for (const m of text.matchAll(/\[(TODO|PROPOSED): ([^\]]*)\]/g)) (html[label] = html[label] || []).push({ type: m[1], text: m[2].trim() });
}

const count = (group, type) => Object.values(group).flat().filter((x) => kindOf(x.type) === type).length;
const short = (s) => (s.length > 90 ? s.slice(0, 87) + '...' : s || '(no text)');

console.log('Open content markers\n');
console.log(`docs/content (the source):  ${count(md, 'TODO')} to confirm (TODO), ${count(md, 'PROPOSED')} proposed (PROPOSED)`);
console.log(`html pages (rendered):      ${count(html, 'TODO')} to confirm (TODO), ${count(html, 'PROPOSED')} proposed (PROPOSED)\n`);

const section = (title, group, where) => {
  const names = Object.keys(group);
  if (!names.length) return;
  console.log(title);
  for (const n of names) {
    for (const type of ['TODO', 'PROPOSED']) {
      const items = group[n].filter((x) => kindOf(x.type) === type);
      if (!items.length) continue;
      console.log(`  ${n}  ${type} (${items.length})`);
      for (const x of items) console.log(`    ${where === 'md' ? 'line ' + x.line + ': ' : ''}${short(x.text)}`);
    }
  }
  console.log('');
};
section('By file in docs/content:', md, 'md');
section('By page in html/:', html, 'html');

const open = count(md, 'TODO') + count(md, 'PROPOSED') + count(html, 'TODO') + count(html, 'PROPOSED');
if (failOnOpen && open) { console.error(`${open} open marker(s): replace every TODO and PROPOSED with approved text before launch.`); process.exit(1); }
if (!open) console.log('No open markers.');
