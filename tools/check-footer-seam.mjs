#!/usr/bin/env node
// Footer seam check (dev-only, needs Playwright from the temp QA folder, see tools/qa/README.md):
//   node tools/check-footer-seam.mjs [--pages a.html,b.html]
// For every full-shell page at 375 and 1280 wide it looks at the 6 pixel rows just above the footer and the 6 rows just below its top edge
// (read from a real screenshot, at both side gutters, away from any text) and fails when any colour channel differs by more than 6.
// The footer's gradient starts from <body data-footer-from="white|secondary"> (tools/shell-map.json "footerFrom"); when a page fails, set
// that for the page and run node tools/sync-shell.mjs. The final line is a table of every page; exit 1 on any failure.
import fs from 'node:fs';
import path from 'node:path';
import { load, start, BASE, repo } from './qa/lib.mjs';

const only = (process.argv.find((a) => a.startsWith('--pages=')) || '').slice(8).split(',').filter(Boolean);
const map = JSON.parse(fs.readFileSync(path.join(repo, 'tools', 'shell-map.json'), 'utf8'));
const pages = Object.keys(map.pages).filter((p) => map.pages[p] === 'full' && (!only.length || only.includes(p)));
const { chromium } = load('playwright');
const server = await start();
const browser = await chromium.launch();
const rows = [];
// pixel access without a PNG library: draw the screenshot on a canvas in a blank page
const reader = await (await browser.newContext()).newPage();
const pixels = (buf) => reader.evaluate(async (b64) => {
  const bmp = await createImageBitmap(await (await fetch('data:image/png;base64,' + b64)).blob());
  const cv = new OffscreenCanvas(bmp.width, bmp.height); const g = cv.getContext('2d'); g.drawImage(bmp, 0, 0);
  return { width: bmp.width, data: Array.from(g.getImageData(0, 0, bmp.width, bmp.height).data) };
}, buf.toString('base64'));
let failed = 0;
for (const width of [375, 1280]) {
  const ctx = await browser.newContext({ viewport: { width, height: 900 }, isMobile: width < 900, hasTouch: width < 900, reducedMotion: 'reduce' });
  const page = await ctx.newPage();
  for (const p of pages) {
    await page.goto(BASE + p, { waitUntil: 'networkidle' });
    const top = await page.evaluate(() => { const f = document.querySelector('footer.site-footer'); return f ? Math.round(f.getBoundingClientRect().top + scrollY) : -1; });
    const from = await page.evaluate(() => document.body.getAttribute('data-footer-from') || '-');
    const clip = { x: 0, y: top - 6, width, height: 12 };
    const buf = await page.screenshot({ fullPage: true, clip });
    const img = await pixels(buf);
    const px = (x, y) => { const i = (y * img.width + x) * 4; return [img.data[i], img.data[i + 1], img.data[i + 2]]; };
    let worst = 0;
    for (const x of [2, width - 3]) {
      for (let c = 0; c < 3; c++) {
        let above = 0, below = 0;
        for (let y = 0; y < 6; y++) { above += px(x, y)[c]; below += px(x, y + 6)[c]; }
        worst = Math.max(worst, Math.abs(above / 6 - below / 6));
      }
    }
    const ok = worst <= 6;
    if (!ok) failed++;
    rows.push({ page: p, width, from, delta: worst.toFixed(1), result: ok ? 'ok' : 'FAIL' });
  }
  await ctx.close();
}
await browser.close();
server.close();
console.log('page'.padEnd(34) + 'width  from       max channel diff  result');
for (const r of rows) console.log(r.page.padEnd(34) + String(r.width).padEnd(7) + r.from.padEnd(11) + r.delta.padEnd(18) + r.result);
console.log(failed ? `\n${failed} seam(s) over 6: set data-footer-from for those pages in tools/shell-map.json ("footerFrom") and run node tools/sync-shell.mjs` : `\nAll ${rows.length} seams are within 6 per channel.`);
process.exit(failed ? 1 : 0);
