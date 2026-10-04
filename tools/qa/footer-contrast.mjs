#!/usr/bin/env node
// Footer text contrast on the gradient (dev-only, Playwright from the temp QA folder, see tools/qa/README.md):
//   node tools/qa/footer-contrast.mjs [page.html ...]      default: index.html product.html size-guide.html
// At 320, 375, 768, 1024, 1280 and 1920 wide: the footer's text is made transparent, the footer is screenshotted, and for every text element the
// real background pixels behind it are read. The ratio is text colour against the WORST (lowest-contrast) pixel behind that element.
// Prints the lowest ratio per width and the element it belongs to; exit 1 when anything is under 4.5:1.
import { load, start, BASE, launchBrowser } from './lib.mjs';

const pages = process.argv.slice(2).length ? process.argv.slice(2) : ['index.html', 'product.html', 'size-guide.html'];
const WIDTHS = [320, 375, 768, 1024, 1280, 1920];
const server = await start();
const browser = await launchBrowser();
const reader = await (await browser.newContext()).newPage();
let failed = 0;
const rows = [];
for (const p of pages) {
  for (const width of WIDTHS) {
    const ctx = await browser.newContext({ viewport: { width, height: 900 }, ...(process.env.KAYAA_ROOT_FONT ? {} : { isMobile: width < 900, hasTouch: width < 900 }), reducedMotion: 'reduce' });
    const page = await ctx.newPage();
    await page.goto(BASE + p, { waitUntil: 'networkidle' });
    await page.evaluate(() => document.querySelectorAll('img[loading=lazy]').forEach((i) => { i.loading = 'eager'; }));
    await page.waitForTimeout(400);
    // text elements: those with a direct, non-empty text node (the art zone is aria-hidden and has none)
    const items = await page.evaluate(() => {
      const f = document.querySelector('footer.site-footer');
      const fr = f.getBoundingClientRect(); const top = fr.top + scrollY;
      const out = [];
      f.querySelectorAll('*').forEach((el) => {
        if (el.closest('[aria-hidden="true"]') || el.closest('svg')) return;
        const own = [...el.childNodes].some((n) => n.nodeType === 3 && n.textContent.trim());
        if (!own) return;
        const r = el.getBoundingClientRect(); if (!r.width || !r.height) return;
        const cs = getComputedStyle(el); const m = cs.color.match(/[\d.]+/g).map(Number);
        out.push({ name: (el.className || el.tagName.toLowerCase()) + ' "' + el.textContent.trim().slice(0, 24) + '"', x: Math.round(r.left), y: Math.round(r.top + scrollY - top), w: Math.round(r.width), h: Math.round(r.height), color: m.slice(0, 3), fs: cs.fontSize });
      });
      return { items: out, top, height: fr.height };
    });
    await page.addStyleTag({ content: '.site-footer *{color:transparent!important;text-decoration-color:transparent!important}.site-footer .icon,.site-footer img.footer__wordmark{visibility:hidden}' });
    const buf = await page.screenshot({ fullPage: true, clip: { x: 0, y: items.top, width, height: items.height } });
    // the pixel maths runs inside the blank page, so the screenshot never has to be copied out as numbers
    const { min, who } = await reader.evaluate(async ({ b64, list }) => {
      const lum = (r, g, b) => { const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; }; return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b); };
      const bmp = await createImageBitmap(await (await fetch('data:image/png;base64,' + b64)).blob());
      const cv = new OffscreenCanvas(bmp.width, bmp.height); const g = cv.getContext('2d'); g.drawImage(bmp, 0, 0);
      const data = g.getImageData(0, 0, bmp.width, bmp.height).data;
      let min = Infinity, who = '';
      for (const it of list) {
        const lt = lum(...it.color); let worst = Infinity;
        for (let y = Math.max(0, it.y); y < Math.min(bmp.height, it.y + it.h); y += 2) {
          for (let x = Math.max(0, it.x); x < Math.min(bmp.width, it.x + it.w); x += 3) {
            const i = (y * bmp.width + x) * 4; const lb = lum(data[i], data[i + 1], data[i + 2]);
            worst = Math.min(worst, (Math.max(lt, lb) + 0.05) / (Math.min(lt, lb) + 0.05));
          }
        }
        if (worst < min) { min = worst; who = it.name; }
      }
      return { min, who };
    }, { b64: buf.toString('base64'), list: items.items });
    if (min < 4.5) failed++;
    rows.push({ p, width, n: items.items.length, min: min.toFixed(2), who, ok: min >= 4.5 });
    await ctx.close();
  }
}
await browser.close();
server.close();
console.log('page'.padEnd(18) + 'width  texts  lowest ratio  where');
for (const r of rows) console.log(r.p.padEnd(18) + String(r.width).padEnd(7) + String(r.n).padEnd(7) + (r.min + (r.ok ? '' : ' FAIL')).padEnd(14) + r.who);
console.log(failed ? `\n${failed} width(s) under 4.5:1: darken the text or move the gradient stops (tokens --footer-g1..g4)` : '\nEvery footer text is at least 4.5:1 against the pixels behind it at every width.');
process.exit(failed ? 1 : 0);
