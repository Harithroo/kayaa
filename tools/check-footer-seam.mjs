#!/usr/bin/env node
// Footer separation check (dev-only, needs Playwright from the temp QA folder, see tools/qa/README.md):
//   node tools/check-footer-seam.mjs [--pages=a.html,b.html]      KAYAA_ROOT_FONT=150 runs it with a 150% browser text size (Firefox)
// The footer must look like its own block, not one more section of the page. For every full-shell page at 375 and 1280 wide this compares the
// page colour just above the footer's top edge with the footer colour 40px inside it (read from a real screenshot, at both side gutters, away
// from any text) and fails when no colour channel differs by at least 8. (The wave that cuts the footer's top edge is a mask, so the page
// shows through above it.) The footer's first colour can be changed per page with <body data-footer-from> (tools/shell-map.json "footerFrom").
// The final lines are a table of every page; exit 1 on any failure.
import fs from 'node:fs';
import path from 'node:path';
import { load, start, BASE, repo, launchBrowser, settleTall } from './qa/lib.mjs';

const only = (process.argv.find((a) => a.startsWith('--pages=')) || '').slice(8).split(',').filter(Boolean);
const map = JSON.parse(fs.readFileSync(path.join(repo, 'tools', 'shell-map.json'), 'utf8'));
const pages = Object.keys(map.pages).filter((p) => map.pages[p] === 'full' && (!only.length || only.includes(p)));
const server = await start();
const browser = await launchBrowser();
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
  const ctx = await browser.newContext({ viewport: { width, height: 900 }, ...(process.env.KAYAA_ROOT_FONT ? {} : { isMobile: width < 900, hasTouch: width < 900 }), reducedMotion: 'reduce' });
  const page = await ctx.newPage();
  for (const p of pages) {
    await page.goto(BASE + p, { waitUntil: 'networkidle' });
    await page.evaluate(() => document.fonts.ready);
    await settleTall(page, width);
    const top = await page.evaluate(() => { const f = document.querySelector('footer.site-footer'); return f ? Math.round(f.getBoundingClientRect().top + scrollY) : -1; });
    const from = await page.evaluate(() => document.body.getAttribute('data-footer-from') || '-');
    const buf = await page.screenshot({ clip: { x: 0, y: top - 6, width, height: 60 } });
    const img = await pixels(buf);
    const px = (x, y) => { const i = (y * img.width + x) * 4; return [img.data[i], img.data[i + 1], img.data[i + 2]]; };
    let worst = 0;
    for (const x of [2, width - 3]) {
      for (let c = 0; c < 3; c++) {
        let above = 0, below = 0;
        for (let y = 0; y < 3; y++) { above += px(x, y)[c]; below += px(x, y + 50)[c]; }   // rows 0-2 are 6-4px above the footer, rows 50-52 are 44-46px inside it
        worst = Math.max(worst, Math.abs(above / 3 - below / 3));
      }
    }
    if (process.env.KAYAA_DEBUG && worst < 8) console.log(p, width, 'top', top, 'above', px(2, 0), 'below', px(2, 50), 'img', img.width, img.data.length / 4 / img.width);
    const ok = worst >= 8;
    if (!ok) failed++;
    rows.push({ page: p, width, from, delta: worst.toFixed(1), result: ok ? 'ok' : 'FAIL' });
  }
  await ctx.close();
}
await browser.close();
server.close();
console.log('page'.padEnd(34) + 'width  from       largest channel step  result');
for (const r of rows) console.log(r.page.padEnd(34) + String(r.width).padEnd(7) + r.from.padEnd(11) + r.delta.padEnd(18) + r.result);
console.log(failed ? `\n${failed} page(s) where the footer is not clearly separate (under 8): check --footer-g1 and data-footer-from` : `\nAll ${rows.length} footers are clearly separate from the page (at least 8 on a channel).`);
process.exit(failed ? 1 : 0);
