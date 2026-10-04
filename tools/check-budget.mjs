#!/usr/bin/env node
// Page weight budget (dev-only, zero dependencies): for every page under html/, the estimated gzip size of the CSS and JS it loads
// and the total font bytes (woff2 files named in the loaded stylesheets).
//   node tools/check-budget.mjs        exit 1 when any page is over a budget
// Budgets: CSS 70 KB, JS 60 KB (gzip, level 9), fonts 120 KB. Images are not counted here (Lighthouse covers them).
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';
import { fileURLToPath } from 'node:url';

const repo = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const htmlDir = path.join(repo, 'html');
const BUDGET = { css: 70 * 1024, js: 60 * 1024, fonts: 120 * 1024 };
const walk = (d) => fs.readdirSync(d, { withFileTypes: true }).flatMap((e) => (e.isDirectory() ? walk(path.join(d, e.name)) : e.name.endsWith('.html') ? [path.join(d, e.name)] : []));
const gz = (file) => zlib.gzipSync(fs.readFileSync(file), { level: 9 }).length;
const kb = (n) => (n / 1024).toFixed(1).padStart(6) + ' KB';

const rows = [];
for (const file of walk(htmlDir).sort()) {
  const label = path.relative(htmlDir, file).split(path.sep).join('/');
  const text = fs.readFileSync(file, 'utf8').replace(/<!--[\s\S]*?-->/g, '');
  const dir = path.dirname(file);
  const syncRoot = (fs.readFileSync(file, 'utf8').match(/<!-- sync-root: (\S+) -->/) || [])[1];
  const resolve = (u) => {
    const bare = u.split('#')[0].split('?')[0];
    if (/^(https?:|data:)/.test(bare)) return null;
    return syncRoot && bare.startsWith(syncRoot) ? path.join(htmlDir, bare.slice(syncRoot.length)) : path.join(dir, bare);
  };
  let css = 0, js = 0, fonts = 0;
  const seen = new Set();
  for (const m of text.matchAll(/<link[^>]+rel="stylesheet"[^>]*href="([^"]+)"/g)) {
    const f = resolve(m[1]);
    if (!f || !fs.existsSync(f) || seen.has(f)) continue;
    seen.add(f);
    css += gz(f);
    for (const u of fs.readFileSync(f, 'utf8').matchAll(/url\(\s*["']?([^"')]+\.woff2)["']?\s*\)/g)) {
      const ff = path.resolve(path.dirname(f), u[1]);
      if (fs.existsSync(ff) && !seen.has(ff)) { seen.add(ff); fonts += fs.statSync(ff).size; }
    }
  }
  for (const m of text.matchAll(/<script[^>]+src="([^"]+)"/g)) {
    const f = resolve(m[1]);
    if (!f || !fs.existsSync(f) || seen.has(f)) continue;
    seen.add(f);
    js += gz(f);
  }
  rows.push({ label, css, js, fonts });
}

let failed = 0;
console.log('page'.padEnd(34) + '   CSS gzip    JS gzip       fonts');
for (const r of rows) {
  const over = [r.css > BUDGET.css && 'CSS', r.js > BUDGET.js && 'JS', r.fonts > BUDGET.fonts && 'fonts'].filter(Boolean);
  if (over.length) failed++;
  console.log(r.label.padEnd(34) + kb(r.css) + ' ' + kb(r.js) + ' ' + kb(r.fonts) + (over.length ? '   OVER BUDGET: ' + over.join(', ') : ''));
}
const max = (k) => Math.max(...rows.map((r) => r[k]));
console.log(`\nLargest: CSS ${kb(max('css')).trim()} of ${BUDGET.css / 1024} KB, JS ${kb(max('js')).trim()} of ${BUDGET.js / 1024} KB, fonts ${kb(max('fonts')).trim()} of ${BUDGET.fonts / 1024} KB.`);
console.log(`${rows.length} pages checked, ${failed} over budget.`);
process.exit(failed ? 1 : 0);
