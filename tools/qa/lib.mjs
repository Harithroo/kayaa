// Shared helpers for the browser audits (dev-only). The test tools are NOT in the repo: install them in a temp folder and point
// KAYAA_QA_MODULES at it (see tools/qa/README.md). Default: <os temp dir>/kayaa-qa.
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
import { startServer } from './serve.mjs';

export const repo = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
export const htmlDir = path.join(repo, 'html');
export const modulesDir = process.env.KAYAA_QA_MODULES || path.join(os.tmpdir(), 'kayaa-qa');
export const outDir = process.env.KAYAA_QA_OUT || path.join(modulesDir, 'out');
fs.mkdirSync(outDir, { recursive: true });
const req = createRequire(path.join(modulesDir, 'package.json'));
export const load = (name) => req(name);
export const PORT = parseInt(process.env.KAYAA_QA_PORT, 10) || 3480;
export const BASE = `http://localhost:${PORT}/kayaa/`;
export const start = () => startServer(PORT);

const walk = (d) => fs.readdirSync(d, { withFileTypes: true }).flatMap((e) => (e.isDirectory() ? walk(path.join(d, e.name)) : e.name.endsWith('.html') ? [path.join(d, e.name)] : []));
export const pages = () => walk(htmlDir).map((f) => path.relative(htmlDir, f).split(path.sep).join('/')).sort();

export const VIEWPORTS = { phone: { width: 375, height: 812 }, desktop: { width: 1280, height: 800 } };

// a few items in the demo bag (sessionStorage) so cart, checkout and drawers have content
export async function seedBag(page) {
  await page.goto(BASE + 'shop.html', { waitUntil: 'networkidle' });
  await page.evaluate(() => {
    window.KayaaCart.add({ productSlug: 'ribbed-cotton-bodysuit', name: 'Ribbed Cotton Bodysuit', sizeLabel: '3–6m', sizeSlug: '3-6m', colourLabel: 'Lilac', colourSlug: 'lilac', tone: 'lilac', qty: 1, unitPrice: 2450 });
  });
}

// Every page and the key states (name, url, optional async prepare(page)). Shared by the axe, keyboard and reflow audits.
export function states() {
  const s = [];
  for (const p of pages()) s.push({ name: p, url: p });
  const add = (name, url, prepare) => s.push({ name, url, prepare });
  add('checkout?demo=errors', 'checkout.html?demo=errors', null);
  add('checkout?demo=cod-on', 'checkout.html?demo=cod-on', null);
  add('checkout?demo=cod-on (cash selected)', 'checkout.html?demo=cod-on', async (pg) => { await pg.locator('input[type=radio][value=cod]').check({ force: true }); });
  for (const d of ['oos', 'low-stock', 'regular', 'new', 'no-reviews', 'reviewed', 'review-error', 'review-success', 'review-throttle']) add('product?demo=' + d, 'product.html?demo=' + d, null);
  for (const t of ['paid', 'cod', 'pending', 'failed', 'cancelled', 'expired']) add('thank-you?state=' + t, 'thank-you.html?state=' + t, null);
  add('menu drawer open', 'index.html', async (pg) => { const t = pg.locator('[data-open-layer="menu"]').first(); if (!(await t.isVisible())) return 'na'; await t.click(); await pg.waitForTimeout(500); });
  add('cart drawer open', 'index.html', async (pg) => { await pg.locator('[data-open-layer="cart"]').first().click(); await pg.waitForTimeout(500); });
  add('quick-add sheet open', 'index.html', async (pg) => { await pg.locator('[data-quick-add]').first().click(); await pg.waitForTimeout(500); });
  add('mega menu open', 'index.html', async (pg) => { const t = pg.locator('[data-mega-toggle]').first(); if (!(await t.isVisible())) return 'na'; await t.click(); await pg.waitForTimeout(400); });
  add('cancel dialog open', 'account/order.html?ref=KY-261003-A3F9', async (pg) => { await pg.locator('[data-o-cancel]').click(); await pg.waitForTimeout(400); });
  add('track result (cash, paid)', 'track.html?ref=KY-260930-C0D2', async (pg) => { await pg.fill('#phone', '071 234 5678'); await pg.locator('[data-track-form] button[type=submit]').click(); await pg.waitForSelector('[data-track-result]:not([hidden])'); });
  return s;
}

export async function open(page, st, withBag = true) {
  if (withBag) await seedBag(page);
  await page.goto(BASE + st.url, { waitUntil: 'networkidle' });
  if (st.prepare) return await st.prepare(page);   // 'na' = the state does not exist at this viewport (for example the mega menu on a phone)
}

export const write = (name, text) => { fs.writeFileSync(path.join(outDir, name), text); return path.join(outDir, name); };
export const resolveModule = (name) => req.resolve(name);
