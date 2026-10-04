#!/usr/bin/env node
// Lighthouse (mobile and desktop) on the key pages, served over http by the Pages-like server.
//   node tools/qa/lighthouse.mjs [--only home] [--form mobile|desktop]     writes out/lighthouse.json and out/lh-*.html
// Needs Chrome or Chromium installed (chrome-launcher finds it) and the tools folder from tools/qa/README.md.
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { load, start, write, BASE, modulesDir, outDir } from './lib.mjs';

const PAGES = [['home', 'index.html'], ['shop', 'shop.html'], ['category', 'category.html?c=bodysuits'], ['product', 'product.html'], ['cart', 'cart.html'], ['checkout', 'checkout.html'], ['thank-you', 'thank-you.html?state=paid'], ['delivery', 'delivery.html'], ['privacy', 'privacy.html']];
const onlyIdx = process.argv.indexOf('--only');
const only = onlyIdx > 0 ? process.argv[onlyIdx + 1] : '';
const formIdx = process.argv.indexOf('--form');
const forms = formIdx > 0 ? [process.argv[formIdx + 1]] : ['mobile', 'desktop'];
const lhRoot = path.join(modulesDir, 'node_modules', 'lighthouse');
const lighthouse = (await import(pathToFileURL(path.join(lhRoot, 'core', 'index.js')).href)).default;
const desktopConfig = (await import(pathToFileURL(path.join(lhRoot, 'core', 'config', 'desktop-config.js')).href)).default;
const { launch } = load('chrome-launcher');

const server = await start();
const rows = [];
for (const form of forms) {
  for (const [name, url] of PAGES) {
    if (only && name !== only) continue;
    const chrome = await launch({ chromeFlags: ['--headless=new', '--no-sandbox'] });
    try {
      const flags = { port: chrome.port, output: 'html', logLevel: 'error', onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo'] };
      const res = await lighthouse(BASE + url, flags, form === 'desktop' ? desktopConfig : undefined);
      const lhr = res.lhr;
      fs.writeFileSync(path.join(outDir, `lh-${form}-${name}.html`), res.report);
      const a = lhr.audits;
      const failed = Object.values(a).filter((x) => x.score !== null && x.score < 1 && x.scoreDisplayMode !== 'informative' && x.scoreDisplayMode !== 'manual' && x.scoreDisplayMode !== 'notApplicable').map((x) => ({ id: x.id, score: x.score, title: x.title, display: x.displayValue || '' }));
      rows.push({
        form, page: name,
        performance: Math.round(lhr.categories.performance.score * 100), accessibility: Math.round(lhr.categories.accessibility.score * 100),
        bestPractices: Math.round(lhr.categories['best-practices'].score * 100), seo: Math.round(lhr.categories.seo.score * 100),
        lcp: Math.round(a['largest-contentful-paint'].numericValue), cls: +a['cumulative-layout-shift'].numericValue.toFixed(3), tbt: Math.round(a['total-blocking-time'].numericValue),
        fcp: Math.round(a['first-contentful-paint'].numericValue), si: Math.round(a['speed-index'].numericValue), failed
      });
      const r = rows[rows.length - 1];
      console.log(`${form.padEnd(7)} ${name.padEnd(10)} perf ${r.performance} a11y ${r.accessibility} bp ${r.bestPractices} seo ${r.seo}  LCP ${r.lcp}ms CLS ${r.cls} TBT ${r.tbt}ms`);
    } catch (e) {
      console.log(`${form} ${name} ERROR ${String(e).slice(0, 200)}`);
      rows.push({ form, page: name, error: String(e).slice(0, 200) });
    } finally { await chrome.kill(); }
  }
}
server.close();
write('lighthouse.json', JSON.stringify(rows, null, 1));
const byAudit = new Map();
for (const r of rows) for (const f of r.failed || []) { const k = f.id + ' - ' + f.title; if (!byAudit.has(k)) byAudit.set(k, []); byAudit.get(k).push(`${r.form}/${r.page}${f.display ? ' (' + f.display + ')' : ''}`); }
console.log('\nAudits below 100 (all pages):');
for (const [k, v] of byAudit) console.log(`  ${k}\n     ${v.join(', ')}`);
