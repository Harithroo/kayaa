#!/usr/bin/env node
// axe-core on every page and the key states, at 375 and 1280 (Chromium, over http).
//   node tools/qa/axe.mjs [--filter text]     writes out/axe.json and prints violations grouped by rule
// Tags: wcag2a, wcag2aa, wcag21a, wcag21aa, wcag22aa, best-practice.
import fs from 'node:fs';
import { load, start, states, open, write, VIEWPORTS, resolveModule } from './lib.mjs';

const { chromium } = load('playwright');
const axeSource = fs.readFileSync(resolveModule('axe-core/axe.min.js'), 'utf8');
const filterArg = process.argv.indexOf('--filter');
const filter = filterArg > 0 ? process.argv[filterArg + 1] : '';
const TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'];

const server = await start();
const browser = await chromium.launch();
const results = [];
for (const [vpName, vp] of Object.entries(VIEWPORTS)) {
  const ctx = await browser.newContext({ viewport: vp, isMobile: vpName === 'phone', hasTouch: vpName === 'phone' });
  const page = await ctx.newPage();
  for (const st of states()) {
    if (filter && !st.name.includes(filter)) continue;
    try {
      if ((await open(page, st, !/^(404|500|503|419|429)/.test(st.name))) === 'na') continue;
      await page.addScriptTag({ content: axeSource });
      const r = await page.evaluate(async (tags) => {
        const out = await window.axe.run(document, { runOnly: { type: 'tag', values: tags }, resultTypes: ['violations'] });
        return out.violations.map((v) => ({ id: v.id, impact: v.impact, help: v.help, nodes: v.nodes.map((n) => ({ target: n.target.join(' '), html: n.html.slice(0, 160), summary: (n.failureSummary || '').split('\n').slice(1, 3).join(' ') })) }));
      }, TAGS);
      results.push({ state: st.name, viewport: vpName, violations: r });
    } catch (e) {
      results.push({ state: st.name, viewport: vpName, error: String(e).slice(0, 300) });
    }
  }
  await ctx.close();
}
await browser.close();
server.close();

write('axe.json', JSON.stringify(results, null, 1));
const byRule = new Map();
for (const r of results) {
  if (r.error) { console.log('ERROR', r.viewport, r.state, r.error); continue; }
  for (const v of r.violations) {
    const k = v.id + ' [' + v.impact + ']';
    if (!byRule.has(k)) byRule.set(k, { help: v.help, hits: [] });
    for (const n of v.nodes) byRule.get(k).hits.push(`${r.viewport} ${r.state} :: ${n.target} :: ${n.summary}`);
  }
}
const impactOrder = { critical: 0, serious: 1, moderate: 2, minor: 3 };
const keys = [...byRule.keys()].sort((a, b) => impactOrder[a.match(/\[(\w+)\]/)[1]] - impactOrder[b.match(/\[(\w+)\]/)[1]]);
for (const k of keys) {
  const v = byRule.get(k);
  console.log(`\n${k}  ${v.help}  (${v.hits.length} node${v.hits.length === 1 ? '' : 's'})`);
  [...new Set(v.hits)].slice(0, 6).forEach((h) => console.log('   ' + h.slice(0, 230)));
}
const states2 = new Set(results.map((r) => r.state)).size;
console.log(`\n${states2} pages and states x ${Object.keys(VIEWPORTS).length} viewports, ${keys.length} distinct rules violated.`);
