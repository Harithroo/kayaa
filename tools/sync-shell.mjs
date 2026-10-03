#!/usr/bin/env node
// Syncs the shared shell into every page under html/.
//
//   node tools/sync-shell.mjs           rewrite out-of-date pages
//   node tools/sync-shell.mjs --check   write nothing; list out-of-date pages; exit 1 if any
//
// For each <!-- partial: NAME --> ... <!-- /partial: NAME --> pair in a page, the content between
// the markers is replaced with tools/partials/NAME.html. The marker pair "sprite" is filled from
// html/assets/icons/sprite.svg. In partials, {{root}} becomes "" for html/*.html and "../" for
// html/account/*.html (one "../" per folder of depth). Everything outside the markers is untouched.
// Dev-only: this folder is never published. Plain Node, no dependencies.

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const repo = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const htmlDir = path.join(repo, 'html');
const partialsDir = path.join(repo, 'tools', 'partials');
const spritePath = path.join(htmlDir, 'assets', 'icons', 'sprite.svg');
const check = process.argv.includes('--check');

const toLF = (s) => s.replace(/\r\n/g, '\n');

function walk(dir) {
  return fs.readdirSync(dir, { withFileTypes: true }).flatMap((e) => {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) return walk(p);
    return e.isFile() && e.name.endsWith('.html') ? [p] : [];
  });
}

function loadPartial(name) {
  if (name === 'sprite') {
    // Same wrapper the pages have always used; indented to sit inside <body>.
    const svg = toLF(fs.readFileSync(spritePath, 'utf8')).trim().replace(/^<svg /, '<svg aria-hidden="true" ');
    return svg.split('\n').map((l) => (l ? '  ' + l : l)).join('\n');
  }
  const file = path.join(partialsDir, `${name}.html`);
  if (!fs.existsSync(file)) return null;
  return toLF(fs.readFileSync(file, 'utf8')).replace(/^\n+|\s+$/g, '');
}

const cache = new Map();
const partial = (name) => {
  if (!cache.has(name)) cache.set(name, loadPartial(name));
  return cache.get(name);
};

const stale = [];
const problems = [];
let pages = 0;

for (const file of walk(htmlDir).sort()) {
  pages++;
  const rel = path.relative(htmlDir, file).split(path.sep);
  const root = '../'.repeat(rel.length - 1);
  const label = rel.join('/');
  const original = fs.readFileSync(file, 'utf8');
  const eol = original.includes('\r\n') ? '\r\n' : '\n';
  let text = toLF(original);

  const names = [...text.matchAll(/<!-- partial: ([\w-]+) -->/g)].map((m) => m[1]);
  for (const name of new Set(names)) {
    const body = partial(name);
    if (body === null) {
      problems.push(`${label}: no partial file for "${name}"`);
      continue;
    }
    const re = new RegExp(`([ \\t]*)<!-- partial: ${name} -->[\\s\\S]*?<!-- /partial: ${name} -->`);
    if (!new RegExp(`<!-- /partial: ${name} -->`).test(text)) {
      problems.push(`${label}: "${name}" has no closing marker`);
      continue;
    }
    text = text.replace(re, (_, indent) => {
      const inner = body.replace(/\{\{root\}\}/g, root);
      return `${indent}<!-- partial: ${name} -->\n${inner}\n${indent}<!-- /partial: ${name} -->`;
    });
  }

  const next = text.replace(/\n/g, eol);
  if (next !== original) {
    stale.push(label);
    if (!check) fs.writeFileSync(file, next);
  }
  // Pages copied from the skeleton must have {{root}} resolved by hand outside the markers.
  if (/\{\{root\}\}/.test(text)) problems.push(`${label}: unresolved {{root}} outside a partial`);
}

problems.forEach((p) => console.error('warning: ' + p));

if (check) {
  stale.forEach((f) => console.log('out of date: html/' + f));
  console.log(`${stale.length} of ${pages} pages out of date`);
  process.exit(stale.length || problems.some((p) => !p.includes('outside')) ? 1 : 0);
}
console.log(`${stale.length} of ${pages} pages updated`);
