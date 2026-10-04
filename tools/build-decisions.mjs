#!/usr/bin/env node
// Builds the decisions page (dev-only, zero dependencies): docs/content/01-open-decisions.md -> the <!-- gen: decisions --> region of
// html/review-decisions.html (review-only, noindex, removed when the site moves to Laravel).
//   node tools/build-decisions.mjs            rewrite the region in place
//   node tools/build-decisions.mjs --check    exit 1 if the page is out of date
// tools/sync-shell.mjs fills the same region through renderDecisions(), so a normal sync (and its --check) keeps the page current.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { readMarkdown, inline, escapeHtml } from './markdown.mjs';

const repo = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

export function renderDecisions(repoDir = repo) {
  const { blocks } = readMarkdown(path.join(repoDir, 'docs', 'content', '01-open-decisions.md'));
  const h1 = blocks.find((b) => b.type === 'h1');
  const intro = blocks.filter((b) => b.type === 'p').map((b) => `<p class="review__intro">${inline(b.text)}</p>`);
  const table = blocks.find((b) => b.type === 'table');
  if (!h1 || !table) throw new Error('docs/content/01-open-decisions.md needs a "# title" and a table');
  const head = table.head.map((h) => `<th scope="col">${escapeHtml(h)}</th>`).join('');
  const rows = table.rows.map((r) => `<tr>${r.map((c, i) => (i === 0 ? `<th scope="row">${inline(c)}</th>` : `<td data-label="${escapeHtml(table.head[i])}">${inline(c)}</td>`)).join('')}</tr>`).join('\n');
  return [
    '<header class="review__head">',
    '  <p class="eyebrow">Review only</p>',
    `  <h1>${inline(h1.text)}</h1>`,
    ...intro.map((p) => '  ' + p),
    '  <p class="review__intro">Review only; removed when the site moves to Laravel. Edit the text in docs/content/01-open-decisions.md, then run node tools/sync-shell.mjs.</p>',
    '</header>',
    '<div class="review-table">',
    '  <table>',
    `    <thead><tr>${head}</tr></thead>`,
    '    <tbody>',
    rows.split('\n').map((l) => '      ' + l).join('\n'),
    '    </tbody>',
    '  </table>',
    '</div>'
  ].join('\n');
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const file = path.join(repo, 'html', 'review-decisions.html');
  const text = fs.readFileSync(file, 'utf8').replace(/\r\n/g, '\n');
  const re = /([ \t]*)<!-- gen: decisions -->[\s\S]*?<!-- \/gen: decisions -->/;
  const m = text.match(re);
  if (!m) { console.error('html/review-decisions.html has no gen: decisions region'); process.exit(1); }
  const indent = m[1];
  const body = renderDecisions().split('\n').map((l) => indent + l).join('\n');
  const next = text.replace(re, () => `${indent}<!-- gen: decisions -->\n${body}\n${indent}<!-- /gen: decisions -->`);
  if (process.argv.includes('--check')) {
    console.log(next === text ? 'review-decisions.html is up to date' : 'review-decisions.html is out of date: run node tools/sync-shell.mjs');
    process.exit(next === text ? 0 : 1);
  }
  fs.writeFileSync(file, next);
  console.log('review-decisions.html updated');
}
