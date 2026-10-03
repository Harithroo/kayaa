#!/usr/bin/env node
// Report-only CSS/asset sanity check (always exits 0). Dev-only, plain Node, no dependencies.
//
//   node tools/check-css.mjs
//
// Reports:
//   ERROR   brace balance and unterminated comments in every html/assets/css/**/*.css
//   ERROR   local stylesheets, scripts, images and CSS url() targets that do not exist
//   WARNING classes used in the HTML that no CSS file defines (is-*, js-* and has-* state hooks are ignored)

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const repo = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const htmlDir = path.join(repo, 'html');
const cssDir = path.join(htmlDir, 'assets', 'css');

const walk = (dir, ext) => fs.readdirSync(dir, { withFileTypes: true }).flatMap((e) => {
  const p = path.join(dir, e.name);
  if (e.isDirectory()) return walk(p, ext);
  return e.name.endsWith(ext) ? [p] : [];
});
const rel = (p) => path.relative(repo, p).split(path.sep).join('/');
const isLocal = (u) => u && !/^(https?:|\/\/|#|mailto:|tel:|data:|\{\{)/.test(u);
const clean = (u) => u.split('#')[0].split('?')[0];
const lineOf = (text, idx) => text.slice(0, idx).split('\n').length;

const errors = [];
const warnings = [];

/* ---------- CSS files: comments, braces, url() targets ---------- */
const cssFiles = walk(cssDir, '.css');
let definedText = '';
for (const file of cssFiles) {
  const text = fs.readFileSync(file, 'utf8');
  const label = rel(file);

  // unterminated comments
  let i = 0;
  let stripped = '';
  while (i < text.length) {
    const open = text.indexOf('/*', i);
    if (open === -1) { stripped += text.slice(i); break; }
    const close = text.indexOf('*/', open + 2);
    if (close === -1) { errors.push(`${label}:${lineOf(text, open)} unterminated comment`); stripped += text.slice(i, open); break; }
    stripped += text.slice(i, open) + ' ';
    i = close + 2;
  }

  // url() targets (checked before strings are removed)
  for (const m of stripped.matchAll(/url\(\s*(['"]?)([^'")]+)\1\s*\)/g)) {
    const u = m[2].trim();
    if (!isLocal(u)) continue;
    if (!fs.existsSync(path.resolve(path.dirname(file), clean(u)))) errors.push(`${label}: url(${u}) points to a missing file`);
  }
  for (const m of stripped.matchAll(/@import\s+(?:url\()?\s*['"]?([^'")\s;]+)/g)) {
    if (isLocal(m[1]) && !fs.existsSync(path.resolve(path.dirname(file), clean(m[1])))) errors.push(`${label}: @import ${m[1]} points to a missing file`);
  }

  // braces (strings and url() contents removed so data URIs cannot confuse the count)
  const code = stripped.replace(/url\([^)]*\)/g, 'url()').replace(/"(?:[^"\\\n]|\\.)*"|'(?:[^'\\\n]|\\.)*'/g, '""');
  let depth = 0;
  let firstExtra = null;
  for (let k = 0; k < code.length; k++) {
    if (code[k] === '{') depth++;
    else if (code[k] === '}') { depth--; if (depth < 0 && firstExtra === null) firstExtra = lineOf(code, k); }
  }
  if (firstExtra !== null) errors.push(`${label}:${firstExtra} closing brace without an opening one`);
  if (depth > 0) errors.push(`${label}: ${depth} unclosed brace${depth > 1 ? 's' : ''}`);

  definedText += '\n' + code;
}

const defined = new Set();
for (const m of definedText.matchAll(/\.(-?[_a-zA-Z][\w-]*)/g)) defined.add(m[1]);

/* ---------- HTML pages: link targets and class usage ---------- */
const used = new Map();   // class -> [pages]
for (const file of walk(htmlDir, '.html')) {
  const raw = fs.readFileSync(file, 'utf8');
  const label = rel(file);
  const text = raw.replace(/<!--[\s\S]*?-->/g, '');   // ignore markup that only appears in comments

  for (const m of text.matchAll(/<(?:link|script|img|source)\b[^>]*>/g)) {
    const tag = m[0];
    const attrs = [...tag.matchAll(/\b(href|src)="([^"]+)"/g)].map((a) => a[2]);
    const isLink = /^<link\b/.test(tag);
    for (const u of attrs) {
      if (!isLocal(u)) continue;
      if (isLink && !/\.css(\?|$)/.test(u)) continue;   // only stylesheets
      if (!fs.existsSync(path.resolve(path.dirname(file), clean(u)))) errors.push(`${label}: ${u} does not exist`);
    }
    for (const s of [...tag.matchAll(/\bsrcset="([^"]+)"/g)]) {
      for (const part of s[1].split(',')) {
        const u = part.trim().split(/\s+/)[0];
        if (isLocal(u) && !fs.existsSync(path.resolve(path.dirname(file), clean(u)))) errors.push(`${label}: srcset ${u} does not exist`);
      }
    }
  }

  for (const m of text.matchAll(/\bclass="([^"]*)"/g)) {
    for (const c of m[1].split(/\s+/).filter(Boolean)) {
      if (/^(is|js|has)-/.test(c) || c.includes('{')) continue;
      if (!used.has(c)) used.set(c, new Set());
      used.get(c).add(label.replace(/^html\//, ''));
    }
  }
}

for (const [c, pages] of [...used].sort((a, b) => a[0].localeCompare(b[0]))) {
  if (!defined.has(c)) warnings.push(`class "${c}" is used in ${pages.size} page${pages.size > 1 ? 's' : ''} (${[...pages][0]}${pages.size > 1 ? ', ...' : ''}) but defined in no CSS file`);
}

/* ---------- report ---------- */
console.log(`Checked ${cssFiles.length} CSS files and ${walk(htmlDir, '.html').length} pages.`);
errors.forEach((e) => console.log('ERROR   ' + e));
warnings.forEach((w) => console.log('WARNING ' + w));
console.log(`${errors.length} error${errors.length === 1 ? '' : 's'}, ${warnings.length} warning${warnings.length === 1 ? '' : 's'} (report only: exit code is always 0)`);
