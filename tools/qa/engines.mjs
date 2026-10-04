#!/usr/bin/env node
// Smoke test in Chromium, Firefox and WebKit (WebKit is a proxy for iOS Safari, not the same thing), at 390x844 (touch) and 1280x800.
//   node tools/qa/engines.mjs        writes screenshots to out/engines/<engine>-<viewport>-<step>.png and prints checks and metric differences
// Flows: Home (growth steps and the phone row), Shop with filters, a product (colour, size, add to cart), the cart, checkout (card and the
// second payment option), track, sign-in, contact. Each step asserts something real; geometry of a few key elements is compared across engines.
import fs from 'node:fs';
import path from 'node:path';
import { load, start, write, BASE, outDir } from './lib.mjs';

const pw = load('playwright');
const shots = path.join(outDir, 'engines');
fs.mkdirSync(shots, { recursive: true });
const VP = { phone: { width: 390, height: 844, isMobile: true, hasTouch: true }, desktop: { width: 1280, height: 800, isMobile: false, hasTouch: false } };
const ENGINES = ['chromium', 'firefox', 'webkit'];
const checks = [];     // { engine, vp, step, ok, detail }
const metrics = {};    // `${vp}/${key}` -> { engine: value }

const server = await start();
for (const engineName of ENGINES) {
  let browser;
  try { browser = await pw[engineName].launch(); } catch (e) { checks.push({ engine: engineName, vp: '-', step: 'launch', ok: false, detail: String(e).slice(0, 160) }); continue; }
  for (const [vpName, vp] of Object.entries(VP)) {
    const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height }, isMobile: vp.isMobile && engineName !== 'firefox', hasTouch: vp.hasTouch && engineName !== 'firefox' });
    const page = await ctx.newPage();
    const errs = [];
    page.on('pageerror', (e) => errs.push(String(e.message).slice(0, 120)));
    page.on('console', (m) => m.type() === 'error' && !/favicon/.test(m.text()) && errs.push(m.text().slice(0, 120)));
    const ok = (step, cond, detail = '') => checks.push({ engine: engineName, vp: vpName, step, ok: !!cond, detail: String(detail).slice(0, 200) });
    const snap = async (step) => { await page.waitForTimeout(600); await page.screenshot({ path: path.join(shots, `${engineName}-${vpName}-${step}.png`) }); };   // wait: smooth scrolling and the 150ms transitions must finish first
    const metric = (key, v) => { (metrics[`${vpName}/${key}`] = metrics[`${vpName}/${key}`] || {})[engineName] = v; };
    const step = async (name, fn) => { try { await fn(); } catch (e) { ok(name, false, 'threw: ' + String(e).slice(0, 160)); } };

    await step('home', async () => {
      await page.goto(BASE + 'index.html', { waitUntil: 'networkidle' });
      const m = await page.evaluate(() => {
        const tiles = [...document.querySelectorAll('.scroller--age .age-tile')];
        const ul = document.querySelector('.scroller--age');
        return { n: tiles.length, h: tiles.map((t) => Math.round(t.getBoundingClientRect().height)), w: Math.round(tiles[1].getBoundingClientRect().width), scrolls: ul.scrollWidth > ul.clientWidth + 1, sw: document.documentElement.scrollWidth, sb: getComputedStyle(ul).scrollbarWidth };
      });
      ok('home: nine growth tiles, no page overflow', m.n === 9 && m.sw <= vp.width, JSON.stringify(m));
      ok(vpName === 'desktop' ? 'home: steps rise 150 to 230px and the row does not scroll' : 'home: phone row scrolls sideways', vpName === 'desktop' ? (m.h[0] === 150 && m.h[8] === 230 && !m.scrolls) : m.scrolls);
      metric('home tile heights', m.h.join(','));
      metric('home tile width', m.w);
      await page.evaluate(() => document.getElementById('age-title').scrollIntoView());
      await snap('home-steps');
    });

    await step('shop', async () => {
      await page.goto(BASE + 'shop.html', { waitUntil: 'networkidle' });
      const count = () => page.locator('.toolbar__count').first().innerText();
      const before = await count();
      await page.goto(BASE + 'shop.html?size=3-6m', { waitUntil: 'networkidle' });
      const sized = await count();
      await page.selectOption('#sort', 'price-asc');
      await page.waitForTimeout(400);
      const first = await page.locator('[data-product]:visible .product-card__name, [data-product]:visible h3').first().innerText().catch(() => '?');
      const prices = await page.evaluate(() => [...document.querySelectorAll('[data-product]')].filter((e) => e.offsetParent).map((e) => +e.dataset.price));
      ok('shop: size filter changes the list', before !== sized, `${before} -> ${sized}`);
      ok('shop: sort by price ascending', prices.every((p, i) => !i || p >= prices[i - 1]), prices.slice(0, 6).join(','));
      await page.locator('input[name=sale]').evaluate((el) => el.click());
      await page.waitForTimeout(400);
      const sale = await count();
      ok('shop: sale-only switch narrows the list', sale !== sized, sale);
      await snap('shop-filtered');
    });

    await step('product', async () => {
      await page.goto(BASE + 'product.html', { waitUntil: 'networkidle' });
      await page.locator('label.swatch').nth(1).click();
      const colour = await page.evaluate(() => document.querySelector('input[name=colour]:checked').value);
      await page.locator('label.size-box').nth(2).click();
      const size = await page.evaluate(() => document.querySelector('input[name=size]:checked').value);
      const beforeCount = await page.evaluate(() => window.KayaaCart.get().reduce((s, i) => s + i.qty, 0));
      await page.locator('[data-add-btn]').click();
      await page.waitForTimeout(800);
      const afterCount = await page.evaluate(() => window.KayaaCart.get().reduce((s, i) => s + i.qty, 0));
      ok('product: colour and size chosen', colour === 'cream' && size === '3-6m', colour + ' / ' + size);
      ok('product: add to cart adds one item', afterCount === beforeCount + 1, `${beforeCount} -> ${afterCount}`);
      await snap('product-added');
    });

    await step('cart', async () => {
      await page.goto(BASE + 'cart.html', { waitUntil: 'networkidle' });
      const m = await page.evaluate(() => ({ lines: document.querySelectorAll('.cart-item').length, sw: document.documentElement.scrollWidth, total: (document.querySelector('[data-cart-total], [data-summary-total]') || {}).textContent }));
      ok('cart: lines render, no overflow', m.lines >= 1 && m.sw <= vp.width, JSON.stringify(m));
      await snap('cart');
    });

    const fillCheckout = async () => {
      await page.fill('#email', 'amaya@example.com'); await page.fill('#phone', '071 234 5678'); await page.fill('#name', 'Amaya Ranasinghe');
      await page.fill('#address_line1', '42 Temple Road'); await page.fill('#city', 'Nugegoda'); await page.selectOption('#district', 'colombo');
    };
    await step('checkout card', async () => {
      await page.goto(BASE + 'checkout.html', { waitUntil: 'networkidle' });
      await fillCheckout();
      await snap('checkout-card');
      await Promise.all([page.waitForURL(/proto-onepay/, { timeout: 8000 }), page.locator('[data-pay-btn]').click()]);
      ok('checkout card: goes to the gateway stand-in', /proto-onepay/.test(page.url()), page.url().split('/').pop());
    });
    await step('checkout cod-on', async () => {
      await page.goto(BASE + 'shop.html', { waitUntil: 'networkidle' });
      await page.evaluate(() => window.KayaaCart.add({ productSlug: 'ribbed-cotton-bodysuit', name: 'Ribbed Cotton Bodysuit', sizeLabel: '3–6m', sizeSlug: '3-6m', colourLabel: 'Lilac', colourSlug: 'lilac', tone: 'lilac', qty: 1, unitPrice: 2450 }));
      await page.goto(BASE + 'checkout.html?demo=cod-on', { waitUntil: 'networkidle' });
      ok('checkout cod-on: two radio cards, card default', (await page.locator('input[type=radio][name=payment_method]').count()) === 2 && (await page.locator('input[type=radio][value=online]').isChecked()));
      await page.locator('label.pay-option').nth(1).click();
      const label = await page.locator('[data-pay-label]').innerText();
      ok('checkout cod-on: selecting the cash card changes the button', label === 'Place order', label);
      await page.evaluate(() => document.getElementById('co-payment').scrollIntoView());
      await snap('checkout-cod');
      await fillCheckout();
      await Promise.all([page.waitForURL(/thank-you/, { timeout: 8000 }), page.locator('[data-pay-btn]').click()]);
      const t = await page.locator('[data-state-block=cod] .thanks__lede').innerText();
      ok('checkout cod-on: thank-you cod page', /courier on delivery/.test(t), t);
      await snap('thankyou-cod');
    });

    await step('track', async () => {
      await page.goto(BASE + 'track.html?ref=KY-261001-C0D1', { waitUntil: 'networkidle' });
      await page.fill('#phone', '071 234 5678');
      await page.locator('[data-track-form] button[type=submit]').click();
      await page.waitForSelector('[data-track-result]:not([hidden])', { timeout: 5000 });
      const wrong = await (async () => { await page.goto(BASE + 'track.html?ref=KY-261001-C0D1', { waitUntil: 'networkidle' }); await page.fill('#phone', '077 999 9999'); await page.locator('[data-track-form] button[type=submit]').click(); await page.waitForTimeout(500); return page.locator('[data-track-alert=notfound]').isVisible(); })();
      ok('track: right number shows the order, wrong number does not', wrong);
      await page.goto(BASE + 'track.html?ref=KY-261001-C0D1', { waitUntil: 'networkidle' });
      await page.fill('#phone', '071 234 5678'); await page.locator('[data-track-form] button[type=submit]').click();
      await page.waitForSelector('[data-track-result]:not([hidden])');
      await snap('track');
    });

    await step('sign-in', async () => {
      await page.goto(BASE + 'account/login.html', { waitUntil: 'networkidle' });
      await page.locator('form[data-auth-form] button[type=submit]').click();
      await page.waitForTimeout(300);
      const summary = await page.locator('[data-error-summary], #login-summary').first().isVisible();
      ok('sign-in: empty submit shows the error summary', summary);
      await snap('sign-in-errors');
    });

    await step('contact', async () => {
      await page.goto(BASE + 'contact.html', { waitUntil: 'networkidle' });
      await page.locator('form[data-auth-form] button[type=submit]').click();
      await page.waitForTimeout(300);
      ok('contact: empty submit shows the error summary', await page.locator('#contact-summary').isVisible());
      await snap('contact-errors');
    });

    ok('no console or page errors', errs.length === 0, errs.slice(0, 3).join(' | '));
    await ctx.close();
  }
  await browser.close();
}
server.close();

write('engines.json', JSON.stringify({ checks, metrics }, null, 1));
for (const c of checks) if (!c.ok) console.log(`FAIL [${c.engine} ${c.vp}] ${c.step}  ${c.detail}`);
console.log(`\n${checks.filter((c) => c.ok).length} of ${checks.length} checks passed.`);
console.log('\nGeometry compared across engines (differences are worth a look):');
for (const [k, v] of Object.entries(metrics)) {
  const vals = Object.values(v).map(String);
  console.log(`  ${new Set(vals).size === 1 ? 'same' : 'DIFF'}  ${k}: ${Object.entries(v).map(([e, x]) => e + '=' + x).join('  ')}`);
}
