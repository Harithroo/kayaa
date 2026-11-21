#!/usr/bin/env node
// Smoke test in Chromium, Firefox and WebKit (WebKit is a proxy for iOS Safari, not the same thing), at 390x844 (touch) and 1280x800.
//   node tools/qa/engines.mjs        writes screenshots to out/engines/<engine>-<viewport>-<step>.png and prints checks and metric differences
// Flows: Home (age chips, rail, cloth swatch, tabs, reviews), Shop with filters, a product (colour, size, add to cart), the cart, checkout (card and the
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
      // the preloader has its own test (tools/qa/home.mjs): mark it seen so the page is ready at once
      await page.addInitScript(() => { try { sessionStorage.setItem('kayaa-intro', '1'); } catch (e) { /* storage blocked */ } });
      const errs = [];
      page.on('pageerror', (e) => errs.push(e.message));
      await page.goto(BASE + 'index.html', { waitUntil: 'networkidle' });
      await page.waitForTimeout(1500);
      const m = await page.evaluate(() => ({
        chips: document.querySelectorAll('.age-chip').length, hangers: document.querySelectorAll('.hanger').length, sw: document.documentElement.scrollWidth,
        hero: document.querySelectorAll('.hero__title .w').length, garments: document.querySelectorAll('.pegged svg').length
      }));
      ok('home: nine age chips, seven hangers, the hero washing line, no page overflow', m.chips === 9 && m.hangers === 7 && m.garments === 3 && m.sw <= vp.width, JSON.stringify(m));
      await page.locator('.age-chip').nth(4).click(); await page.waitForTimeout(700);
      ok('home: an age chip changes the panel', (await page.locator('#ageName').textContent()).trim() === '9–12 months', await page.locator('#ageName').textContent());
      await page.evaluate(() => document.getElementById('rail').scrollIntoView()); await page.waitForTimeout(4200);
      const r = await page.evaluate(() => { const s = document.getElementById('railStage').getBoundingClientRect(); const h = [...document.querySelectorAll('.hanger')].map((e) => Math.round(e.getBoundingClientRect().width)); return { stage: Math.round(s.height), doorsGone: document.getElementById('doors').classList.contains('gone'), w: h[0], scroller: document.getElementById('railScroller').scrollWidth > document.getElementById('railScroller').clientWidth + 1 }; });
      ok(vpName === 'desktop' ? 'home: the rail fits (no scrolling) and the doors have opened' : 'home: the rail scrolls sideways on a phone and the doors have opened', r.doorsGone && (vpName === 'desktop' ? !r.scroller : r.scroller), JSON.stringify(r));
      metric('home rail stage height', r.stage);
      metric('home rail hanger width', r.w);
      await snap('home-rail');
      await page.evaluate(() => document.getElementById('clothWrap').scrollIntoView({ block: 'center' })); await page.waitForTimeout(1500);
      const px = async () => page.evaluate(() => { const cv = document.getElementById('cloth'); const x = cv.getContext('2d').getImageData(0, 0, cv.width, cv.height).data; let n = 0; for (let i = 3; i < x.length; i += 4 * 97) if (x[i] > 0) n++; return n; });
      const painted = await px();
      ok('home: the cloth swatch is drawn on the canvas', painted > 200, painted + ' sampled pixels');
      await page.locator('#fab-waffle').click(); await page.waitForTimeout(700);
      ok('home: a fabric tab changes the label and the card', await page.evaluate(() => document.getElementById('clothLabel').textContent === 'Waffle knit' && !document.getElementById('fab-panel-waffle').hidden));
      metric('home cloth canvas', await page.evaluate(() => document.getElementById('cloth').width + 'x' + document.getElementById('cloth').height));
      await snap('home-fabrics');
      await page.evaluate(() => document.getElementById('products').scrollIntoView()); await page.waitForTimeout(1200);
      await page.locator('#tab-best').click(); await page.waitForTimeout(1400);
      ok('home: the Bestsellers tab shows four cards', await page.evaluate(() => document.getElementById('prod-title').textContent === 'Bestsellers' && document.querySelectorAll('#panel-best .product-card').length === 4 && document.documentElement.scrollWidth <= innerWidth));
      await snap('home-products');
      await page.evaluate(() => document.querySelector('.reviews-sec').scrollIntoView({ block: 'center' })); await page.waitForTimeout(1200);
      const rv = await page.evaluate(() => { const l = document.getElementById('reviews'); return { scrolls: l.scrollWidth > l.clientWidth + 1, sw: document.documentElement.scrollWidth }; });
      ok(vpName === 'desktop' ? 'home: three reviews side by side' : 'home: the reviews scroll sideways on a phone, the page does not', rv.sw <= vp.width && (vpName === 'desktop' ? !rv.scrolls : rv.scrolls), JSON.stringify(rv));
      ok('home: no script errors', errs.length === 0, errs.join('|'));
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

    await step('search suggestions', async () => {
      if (vpName === 'desktop') {
        await page.goto(BASE + 'index.html', { waitUntil: 'networkidle' });
        await page.locator('#header-q').click();
        await page.keyboard.type('bod');
        await page.waitForSelector('.suggest:not([hidden]) [role=option]');
        await page.waitForTimeout(300);
        const n = await page.locator('.suggest:not([hidden]) [role=option]').count();
        await page.keyboard.press('ArrowDown');
        const sel = await page.evaluate(() => { const i = document.querySelector('#header-q'); const o = document.getElementById(i.getAttribute('aria-activedescendant')); return !!o && o.getAttribute('aria-selected') === 'true' && document.activeElement === i; });
        ok('search: header suggestions for "bod" open and the arrow keys highlight a row', n === 7 && sel, n + ' options');
        await snap('search-suggest');
      } else {
        await page.goto(BASE + 'search.html?focus=1', { waitUntil: 'networkidle' });
        await page.keyboard.type('gift');
        await page.waitForSelector('.suggest--page:not([hidden]) [role=option]');
        await page.waitForTimeout(300);
        const n = await page.locator('.suggest--page:not([hidden]) [role=option]').count();
        const hidden = await page.evaluate(() => getComputedStyle(document.querySelector('[data-listing-results]')).display === 'none');
        ok('search: the phone page shows the suggestions inline and hides the results', n === 7 && hidden, n + ' options');
        await snap('search-suggest');
      }
      await page.goto(BASE + 'shop.html?tag=gift-idea', { waitUntil: 'networkidle' });
      ok('tags: the tag listing filters and titles itself', (await page.locator('h1').innerText()) === 'Tagged: Gift idea' && (await page.locator('[data-listing-count]').innerText()) === '8 products');
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
