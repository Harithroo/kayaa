#!/usr/bin/env node
// Hygiene audit (Chromium over http): console errors and warnings, failed requests, unused CSS rules, file names and sizes.
//   node tools/qa/hygiene.mjs
// 1. every page and key state at 375 and 1280: console error/warning/pageerror and any request that failed or returned 400+
// 2. CSS rules whose selector matches no element on any page or state AND whose classes appear in no script (candidates for deletion);
//    a coverage summary per stylesheet
// 3. files with spaces or upper case in their names, and files over 200 KB
import fs from 'node:fs';
import path from 'node:path';
import { load, start, states, open, write, htmlDir, repo } from './lib.mjs';

const { chromium } = load('playwright');
const server = await start();
const browser = await chromium.launch();
const problems = [];
const matched = new Map();   // "sheet|selector" -> true when it matched at least once
const allRules = new Map();  // "sheet|selector" -> { sheet, selector }

function ruleScan() {
  const out = [];
  const walk = (rules, sheet, nested) => {
    for (const r of rules) {
      if (r.cssRules && r.type !== 1) { walk(r.cssRules, sheet, true); continue; }   // @media, @supports
      if (r.type !== 1) continue;
      r.selectorText.split(',').forEach((raw) => {
        const sel = raw.trim();
        // states and pseudo-elements cannot be queried: drop them and test the element part
        const base = sel.replace(/::?(hover|focus|focus-visible|focus-within|active|visited|checked|disabled|before|after|placeholder|first-line|first-letter|selection|-webkit-[\w-]+|-moz-[\w-]+|backdrop|marker|file-selector-button)(\([^)]*\))?/g, '').replace(/\s+([>+~])\s*$/, '').trim() || '*';
        let hit = false;
        try { hit = !!document.querySelector(base); } catch (e) { hit = true; }
        out.push({ sheet, sel, hit });
      });
    }
  };
  for (const sheet of document.styleSheets) {
    let rules; try { rules = sheet.cssRules; } catch (e) { continue; }
    const name = (sheet.href || 'inline').split('/assets/')[1] || sheet.href || 'inline';
    walk(rules, name.split('?')[0], false);
  }
  return out;
}

for (const [vpName, vp] of [['phone', { width: 375, height: 812 }], ['desktop', { width: 1280, height: 800 }]]) {
  const ctx = await browser.newContext({ viewport: vp, isMobile: vpName === 'phone', hasTouch: vpName === 'phone' });
  const page = await ctx.newPage();
  let where = '';
  page.on('console', (m) => { if (['error', 'warning'].includes(m.type()) && where !== 'missing page') problems.push(`${m.type()} [${vpName} ${where}] ${m.text().slice(0, 160)}`); });
  page.on('pageerror', (e) => problems.push(`pageerror [${vpName} ${where}] ${String(e.message).slice(0, 160)}`));
  page.on('requestfailed', (r) => problems.push(`request failed [${vpName} ${where}] ${r.url().slice(0, 120)}`));
  page.on('response', (r) => { if (r.status() >= 400 && where !== 'missing page') problems.push(`HTTP ${r.status()} [${vpName} ${where}] ${r.url().slice(0, 120)}`); });
  for (const st of states()) {
    where = st.name;
    try {
      if ((await open(page, st, !/^(404|500|503|419|429)/.test(st.name))) === 'na') continue;
      for (const r of await page.evaluate(ruleScan)) {
        const k = r.sheet + '|' + r.sel;
        allRules.set(k, { sheet: r.sheet, sel: r.sel });
        if (r.hit) matched.set(k, true);
      }
    } catch (e) { problems.push(`threw [${vpName} ${where}] ${String(e).slice(0, 120)}`); }
  }
  // a page that does not exist: the 404 fallback
  where = 'missing page';
  const res = await page.goto('http://localhost:' + (process.env.KAYAA_QA_PORT || 3480) + '/kayaa/no-such-page', { waitUntil: 'networkidle' });
  if (res.status() !== 404) problems.push('the missing-page fallback did not return 404');
  await ctx.close();
}
await browser.close();
server.close();

// scripts: a class named in any script may be added at run time
const scripts = fs.readdirSync(path.join(htmlDir, 'assets', 'js')).map((f) => fs.readFileSync(path.join(htmlDir, 'assets', 'js', f), 'utf8')).join('\n');
const unused = [];
for (const [k, r] of allRules) {
  if (matched.get(k)) continue;
  const tokens = [...r.sel.matchAll(/[.#]([A-Za-z_][\w-]*)/g)].map((m) => m[1]);
  if (tokens.some((t) => scripts.includes(t))) continue;
  unused.push(r);
}
const perSheet = new Map();
for (const r of allRules.values()) { const e = perSheet.get(r.sheet) || { total: 0, unused: 0 }; e.total++; perSheet.set(r.sheet, e); }
unused.forEach((r) => { perSheet.get(r.sheet).unused++; });

// files
const hygiene = [];
const walk = (d) => fs.readdirSync(d, { withFileTypes: true }).flatMap((e) => (['node_modules', '.git', 'image-source'].includes(e.name) ? [] : e.isDirectory() ? walk(path.join(d, e.name)) : [path.join(d, e.name)]));
for (const f of walk(repo)) {
  const rel = path.relative(repo, f).split(path.sep).join('/');
  if (/[\sA-Z]/.test(path.basename(rel)) && !/(CLAUDE|README|LICENSES)/.test(path.basename(rel)) && !rel.startsWith('design/')) hygiene.push('name has a space or capital letter: ' + rel);
  const size = fs.statSync(f).size;
  if (size > 200 * 1024 && rel.startsWith('html/')) hygiene.push(`large file (${Math.round(size / 1024)} KB): ${rel}`);
}

write('hygiene.json', JSON.stringify({ problems: [...new Set(problems)], unused, perSheet: [...perSheet], hygiene }, null, 1));
console.log('Console and network problems (' + new Set(problems).size + '):'); [...new Set(problems)].slice(0, 30).forEach((p) => console.log('  ' + p));
console.log('\nCSS rules that match no element on any page or state and are named in no script (' + unused.length + '):'); unused.slice(0, 60).forEach((r) => console.log(`  ${r.sheet}  ${r.sel}`));
console.log('\nPer stylesheet (selectors, unused candidates):'); [...perSheet].forEach(([s, e]) => console.log(`  ${s.padEnd(28)} ${String(e.total).padStart(5)} ${String(e.unused).padStart(4)}`));
console.log('\nFiles:'); (hygiene.length ? hygiene : ['none']).forEach((h) => console.log('  ' + h));
