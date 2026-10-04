#!/usr/bin/env node
// Keyboard audit (Chromium over http): tab through the key flows and exercise every dialog, drawer, sheet and the mega menu.
//   node tools/qa/keyboard.mjs
// Flows: home, shop, product, cart, checkout, track, sign-in, contact (phone width 375 and desktop 1280).
// Per flow: the skip link is the first stop and works; every stop shows a visible focus indicator; the tab cycle closes (no trap);
// big upward jumps in the visual order are listed for a human to look at.
// Per layer: focus moves in on open, Tab and Shift+Tab stay inside, Esc closes, focus returns to the trigger, the page behind is inert or hidden.
import { load, start, open, seedBag, write, BASE, VIEWPORTS } from './lib.mjs';

const { chromium } = load('playwright');
const FLOWS = ['index.html', 'size-guide.html', 'shop.html', 'product.html', 'cart.html', 'checkout.html', 'track.html', 'account/login.html', 'contact.html'];

function describe() {
  const el = document.activeElement;
  if (!el || el === document.body) return null;
  const r = el.getBoundingClientRect();
  const cs = getComputedStyle(el);
  const ring = (cs2) => (cs2.outlineStyle !== 'none' && parseFloat(cs2.outlineWidth) >= 1 && !/rgba\(\d+, \d+, \d+, 0\)/.test(cs2.outlineColor)) || (cs2.boxShadow && cs2.boxShadow !== 'none');
  let indicator = ring(cs) || ring(getComputedStyle(el, '::after')) || ring(getComputedStyle(el, '::before'));   // a stretched link draws its ring on ::after
  let via = 'self';
  if (!indicator && (r.width <= 2 || r.height <= 2)) {   // visually hidden control: the ring is drawn on the next sibling or the wrapping label
    const sib = el.nextElementSibling;
    if (sib && ring(getComputedStyle(sib))) { indicator = true; via = 'sibling'; }
    const lab = el.closest('label');
    if (!indicator && lab && [...lab.children].some((c) => ring(getComputedStyle(c)))) { indicator = true; via = 'label child'; }
  }
  if (!indicator) {   // a border or background change on the element itself is also accepted: compare with the not-focused look is not possible here, so flag it
    indicator = false;
  }
  const name = el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + (el.className && typeof el.className === 'string' ? '.' + el.className.split(/\s+/)[0] : '') + ' "' + ((el.getAttribute('aria-label') || el.textContent || el.value || '').trim().slice(0, 30)) + '"';
  return { name, indicator, via, top: Math.round(r.top + scrollY), left: Math.round(r.left), w: Math.round(r.width), h: Math.round(r.height), inView: r.bottom > 0 && r.top < innerHeight };
}

const results = [];
const note = (flow, vp, kind, ok, detail) => results.push({ flow, vp, kind, ok, detail });

const server = await start();
const browser = await chromium.launch();

for (const [vpName, vp] of Object.entries(VIEWPORTS)) {
  const ctx = await browser.newContext({ viewport: vp, isMobile: vpName === 'phone', hasTouch: vpName === 'phone' });
  const page = await ctx.newPage();

  /* ---------- flows ---------- */
  for (const f of FLOWS) {
    await seedBag(page);
    await page.goto(BASE + f, { waitUntil: 'networkidle' });
    await page.evaluate(() => { document.activeElement && document.activeElement.blur(); window.scrollTo(0, 0); });
    const stops = [];
    const seen = new Set();
    let cycle = false;
    for (let i = 0; i < 160; i++) {
      await page.keyboard.press('Tab');
      const d = await page.evaluate(describe);
      if (!d) { cycle = true; break; }      // focus left the document (browser UI) or returned to body: the cycle closed
      const key = d.name + d.top + d.left;
      if (seen.has(key)) { cycle = true; break; }
      seen.add(key);
      stops.push(d);
    }
    const first = stops[0];
    note(f, vpName, 'skip link is the first stop', !!first && /skip-link/.test(first.name), first && first.name);
    if (first && /skip-link/.test(first.name)) {
      await page.evaluate(() => { document.activeElement.blur(); window.scrollTo(0, 0); });
      await page.keyboard.press('Tab'); await page.keyboard.press('Enter');
      const landed = await page.evaluate(() => ({ hash: location.hash, inMain: !!document.activeElement && !!document.activeElement.closest('main'), isMain: document.activeElement && document.activeElement.id === 'main' }));
      note(f, vpName, 'skip link moves focus to main', landed.hash === '#main' && (landed.inMain || landed.isMain), JSON.stringify(landed));
    }
    const noRing = stops.filter((s) => !s.indicator);
    note(f, vpName, `visible focus indicator on every stop (${stops.length} stops)`, noRing.length === 0, noRing.slice(0, 5).map((s) => s.name).join(' | '));
    note(f, vpName, 'tab cycle closes (no trap)', cycle, `after ${stops.length} stops`);
    const jumps = [];
    for (let i = 1; i < stops.length; i++) if (stops[i].top < stops[i - 1].top - 400 && !/skip-link/.test(stops[i - 1].name)) jumps.push(`${stops[i - 1].name}@${stops[i - 1].top} -> ${stops[i].name}@${stops[i].top}`);
    note(f, vpName, 'no large upward jumps in the order (review list)', jumps.length === 0, jumps.slice(0, 4).join(' | '));
  }

  /* ---------- layers ---------- */
  const layers = [];
  if (vpName === 'phone') layers.push(['menu drawer', 'index.html', '[data-open-layer="menu"]', '#menu-drawer', true]);
  layers.push(['cart drawer', 'index.html', '[data-open-layer="cart"]', '[data-cart-panel], #cart-drawer', true]);
  layers.push(['quick-add sheet', 'index.html', '[data-quick-add]', '[data-quick-add-sheet]', true]);
  layers.push(['cancel dialog', 'account/order.html?ref=KY-261003-A3F9', '[data-o-cancel]', '#cancel-dialog', true]);
  if (vpName === 'desktop') layers.push(['mega menu', 'index.html', '[data-mega-toggle]', '#mega-shop', false]);
  for (const [name, url, trigSel, layerSel, modal] of layers) {
    await seedBag(page);
    await page.goto(BASE + url, { waitUntil: 'networkidle' });
    const trig = page.locator(trigSel).first();
    if (!(await trig.isVisible())) { note(name, vpName, 'trigger visible', false, trigSel); continue; }
    await trig.focus();
    await page.keyboard.press('Enter');
    await page.waitForTimeout(600);
    const inside = () => page.evaluate((sel) => { const l = document.querySelector(sel.split(',').map((s) => s.trim()).find((s) => document.querySelector(s))); return !!l && !!document.activeElement && l.contains(document.activeElement); }, layerSel);
    note(name, vpName, 'focus moves into the layer on open', modal ? await inside() : true, modal ? '' : 'non-modal');
    if (modal) {
      let escaped = 0;
      for (let i = 0; i < 25; i++) { await page.keyboard.press('Tab'); if (!(await inside())) escaped++; }
      for (let i = 0; i < 25; i++) { await page.keyboard.press('Shift+Tab'); if (!(await inside())) escaped++; }
      note(name, vpName, 'Tab and Shift+Tab stay inside', escaped === 0, escaped ? `${escaped} stops outside` : '');
      const bg = await page.evaluate((sel) => {
        const l = document.querySelector(sel.split(',').map((s) => s.trim()).find((s) => document.querySelector(s)));
        const main = document.querySelector('main');
        const inertOrHidden = (el) => !!el && (el.inert || el.closest('[inert]') || el.closest('[aria-hidden="true"]'));
        return { dialogModal: l && l.tagName === 'DIALOG' && l.open, mainInert: !!inertOrHidden(main) };
      }, layerSel);
      note(name, vpName, 'page behind is not focusable (inert, aria-hidden or native modal dialog)', bg.dialogModal || bg.mainInert, JSON.stringify(bg));
    }
    await page.keyboard.press('Escape');
    await page.waitForTimeout(500);
    const after = await page.evaluate((sel) => { const l = document.querySelector(sel.split(',').map((s) => s.trim()).find((s) => document.querySelector(s))); const hidden = !l || l.hidden || l.hasAttribute('inert') || !l.open && l.tagName === 'DIALOG' || getComputedStyle(l).visibility === 'hidden' || getComputedStyle(l).display === 'none' || l.getAttribute('aria-hidden') === 'true' || l.closest('[aria-hidden="true"]') || !l.getBoundingClientRect().width || l.getBoundingClientRect().right <= 0 || l.getBoundingClientRect().left >= innerWidth || l.getBoundingClientRect().bottom <= 0 || l.getBoundingClientRect().top >= innerHeight; return { closed: !!hidden, active: document.activeElement ? document.activeElement.tagName.toLowerCase() + (document.activeElement.className ? '.' + String(document.activeElement.className).split(/\s+/)[0] : '') : null }; }, layerSel);
    note(name, vpName, 'Esc closes', after.closed, JSON.stringify(after));
    const returned = await page.evaluate((sel) => { const t = document.querySelector(sel); const a = document.activeElement; if (a === t) return true; return !!t && t.contains(a); }, trigSel.split(',')[0]);
    note(name, vpName, 'focus returns to the trigger', returned, '');
  }
  await ctx.close();
}
await browser.close();
server.close();

write('keyboard.json', JSON.stringify(results, null, 1));
const bad = results.filter((r) => !r.ok);
for (const r of results) console.log(`${r.ok ? 'PASS' : 'FAIL'} [${r.vp}] ${r.flow}: ${r.kind}${r.ok ? '' : '  -> ' + r.detail}`);
console.log(`\n${results.length - bad.length} of ${results.length} checks passed, ${bad.length} failed.`);
