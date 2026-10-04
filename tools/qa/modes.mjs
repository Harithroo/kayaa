#!/usr/bin/env node
// Reflow, zoom, text size, text spacing, reduced motion, forced colours and touch audits (Chromium over http).
//   node tools/qa/modes.mjs
// Modes
//   reflow 320     a 320 CSS px wide viewport
//   zoom 200%      640 CSS px wide (a 1280px window at 200%)         zoom 400%   320 CSS px wide (a 1280px window at 400%)
//   text 200%      every font size doubled, the layout widths unchanged (what Android "large text" or a 200% font setting does to px type)
//   text spacing   WCAG 1.4.12 overrides: line height 1.5, paragraph spacing 2x, letter spacing 0.12em, word spacing 0.16em
//   reduced motion prefers-reduced-motion: reduce -> no animation or transition above 0.01ms, no smooth scrolling
//   forced colors  forced-colors: active -> controls keep a border or outline; screenshots to open by hand
//   touch          coarse pointer, no hover -> nothing important is revealed by :hover alone
// Per page and mode: horizontal scroll, clipped text (overflow hidden around text that does not fit), overlapping text blocks.
import { load, start, open, seedBag, write, BASE, outDir } from './lib.mjs';
import path from 'node:path';

const { chromium } = load('playwright');
const KEY = ['index.html', 'size-guide.html', 'shop.html', 'category.html?c=bodysuits', 'product.html', 'cart.html', 'checkout.html', 'checkout.html?demo=cod-on', 'thank-you.html?state=cod', 'track.html', 'account/login.html', 'contact.html', 'delivery.html', 'privacy.html'];
const results = [];
const note = (mode, url, ok, detail) => results.push({ mode, url, ok, detail });

// text clipped by an overflow:hidden box, and text blocks that overlap each other
function layoutProblems() {
  const out = [];
  const label = (el) => el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + (typeof el.className === 'string' && el.className ? '.' + el.className.trim().split(/\s+/)[0] : '') + ' "' + (el.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 28) + '"';
  if (document.documentElement.scrollWidth > window.innerWidth + 1) out.push('horizontal scroll: page is ' + document.documentElement.scrollWidth + ' wide in a ' + window.innerWidth + ' viewport');
  const hiddenByClip = (cs) => (cs.clipPath && cs.clipPath !== 'none') || (cs.clip && cs.clip !== 'auto');   // the visually-hidden pattern
  const stuck = (el) => { for (let n = el; n && n !== document.body; n = n.parentElement) { const p = getComputedStyle(n).position; if (p === 'fixed' || p === 'sticky') return true; } return false; };
  const visible = (el) => { const cs = getComputedStyle(el); const r = el.getBoundingClientRect(); return (!el.checkVisibility || el.checkVisibility()) && !hiddenByClip(cs) && cs.display !== 'none' && cs.visibility !== 'hidden' && r.width > 1 && r.height > 1 && !el.closest('[hidden],[inert],[aria-hidden="true"],.visually-hidden,[data-layer]:not(.is-open),dialog:not([open])'); };
  const text = (el) => [...el.childNodes].some((n) => n.nodeType === 3 && n.textContent.trim());
  document.querySelectorAll('body *').forEach((el) => {
    if (!visible(el)) return;
    const cs = getComputedStyle(el);
    const clips = /hidden|clip/.test(cs.overflowX) || /hidden|clip/.test(cs.overflowY);
    if (clips && !/auto|scroll/.test(cs.overflowX + cs.overflowY) && !cs.webkitLineClamp.match(/^\d/) && cs.textOverflow !== 'ellipsis' && !/^(svg|img|picture)$/i.test(el.tagName)) {
      const box = el.getBoundingClientRect();
      const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
      let n, bad = null;
      while ((n = walker.nextNode()) && !bad) {
        if (!n.textContent.trim() || !n.parentElement.checkVisibility() || n.parentElement.closest('.visually-hidden')) continue;
        { let hid = false; for (let a = n.parentElement; a && a !== el.parentElement; a = a.parentElement) { const s = getComputedStyle(a); const q = a.getBoundingClientRect(); if ((s.clipPath && s.clipPath !== 'none') || q.width <= 2 || q.height <= 2) { hid = true; break; } } if (hid) continue; }
        const rg = document.createRange(); rg.selectNodeContents(n);
        for (const q of rg.getClientRects()) if (q.width > 1 && (q.right > box.right + 1.5 || q.bottom > box.bottom + 1.5 || q.left < box.left - 1.5 || q.top < box.top - 1.5)) { bad = n.textContent.trim().slice(0, 24); break; }
      }
      if (bad && !el.closest('.gallery, .media, [data-placeholder]')) out.push('clipped: ' + label(el) + ' text "' + bad + '" sticks out of its box');
    }
  });
  const small = (el) => { for (let n = el; n && n !== document.body; n = n.parentElement) { const q = n.getBoundingClientRect(); if (q.width <= 2 || q.height <= 2 || /^\d/.test(getComputedStyle(n).webkitLineClamp)) return true; } return false; };
  const leaves = [...document.querySelectorAll('body *')].filter((el) => visible(el) && text(el) && !small(el) && !el.closest('.tabbar, .topbar, header.site-header, .site-header, [data-sticky], .buy-bar, .sticky-bar, .overlay, svg'));
  const rects = leaves.filter((el) => !stuck(el)).map((el) => { const rg = document.createRange(); rg.selectNodeContents(el); const b = [...rg.getClientRects()].filter((r) => r.width > 1 && r.height > 1); return { el, b, fixed: getComputedStyle(el).position === 'fixed' }; }).filter((x) => x.b.length && !x.fixed);
  let found = 0;
  for (let i = 0; i < rects.length && found < 6; i++) {
    for (let j = i + 1; j < rects.length && found < 6; j++) {
      const a = rects[i], b = rects[j];
      if (a.el.contains(b.el) || b.el.contains(a.el)) continue;
      const hit = a.b.some((p) => b.b.some((q) => Math.min(p.right, q.right) - Math.max(p.left, q.left) > 3 && Math.min(p.bottom, q.bottom) - Math.max(p.top, q.top) > 3));
      if (hit) { out.push('overlap: ' + label(a.el) + ' with ' + label(b.el)); found++; }
    }
  }
  return out;
}

const server = await start();
const browser = await chromium.launch();

async function pass(mode, ctxOpts, prep) {
  const ctx = await browser.newContext(ctxOpts);
  const page = await ctx.newPage();
  const errs = [];
  page.on('pageerror', (e) => errs.push(e.message));
  page.on('console', (m) => m.type() === 'error' && !/favicon/.test(m.text()) && errs.push(m.text()));
  for (const url of KEY) {
    await seedBag(page);
    await page.goto(BASE + url, { waitUntil: 'networkidle' });
    if (prep) await prep(page);
    await page.waitForTimeout(150);
    const p = await page.evaluate(layoutProblems);
    note(mode, url, p.length === 0, p.slice(0, 4).join(' | '));
  }
  if (errs.length) note(mode, '(console)', false, errs.slice(0, 3).join(' | '));
  await ctx.close();
}

await pass('reflow 320', { viewport: { width: 320, height: 640 }, isMobile: true, hasTouch: true });
await pass('zoom 200% (640 wide)', { viewport: { width: 640, height: 450 } });
await pass('zoom 400% (320 wide)', { viewport: { width: 320, height: 256 } });
for (const w of [375, 1280]) {
  await pass(`text 200% @${w}`, { viewport: { width: w, height: 800 }, isMobile: w < 600, hasTouch: w < 600 }, async (page) => {
    await page.evaluate(() => {
      const els = [...document.querySelectorAll('body *')];
      const sizes = els.map((e) => parseFloat(getComputedStyle(e).fontSize));
      els.forEach((e, i) => e.style.setProperty('font-size', sizes[i] * 2 + 'px', 'important'));
    });
  });
  await pass(`text spacing @${w}`, { viewport: { width: w, height: 800 }, isMobile: w < 600, hasTouch: w < 600 }, async (page) => {
    await page.addStyleTag({ content: '* { line-height: 1.5 !important; letter-spacing: 0.12em !important; word-spacing: 0.16em !important; } p, li, dd { margin-bottom: 2em !important; }' });
  });
}

/* ---------- reduced motion ---------- */
{
  const ctx = await browser.newContext({ viewport: { width: 375, height: 800 }, isMobile: true, hasTouch: true, reducedMotion: 'reduce' });
  const page = await ctx.newPage();
  for (const url of KEY) {
    await seedBag(page);
    await page.goto(BASE + url, { waitUntil: 'networkidle' });
    const bad = await page.evaluate(() => {
      const out = [];
      const sec = (v) => Math.max(...String(v).split(',').map((x) => (x.trim().endsWith('ms') ? parseFloat(x) / 1000 : parseFloat(x)) || 0));
      document.querySelectorAll('body *').forEach((el) => {
        const cs = getComputedStyle(el);
        if (sec(cs.animationDuration) > 0.00001 && cs.animationName !== 'none') out.push(el.tagName.toLowerCase() + '.' + String(el.className).split(' ')[0] + ' animation ' + cs.animationDuration);
        if (sec(cs.transitionDuration) > 0.00001 && cs.transitionProperty !== 'none') out.push(el.tagName.toLowerCase() + '.' + String(el.className).split(' ')[0] + ' transition ' + cs.transitionDuration);
      });
      if (getComputedStyle(document.documentElement).scrollBehavior === 'smooth') out.push('html scroll-behavior smooth');
      return [...new Set(out)];
    });
    note('reduced motion', url, bad.length === 0, bad.slice(0, 4).join(' | '));
  }
  // accordions and the carousel still work
  await page.goto(BASE + 'product.html', { waitUntil: 'networkidle' });
  const acc = await page.evaluate(() => { const d = document.querySelector('details.accordion__item'); if (!d) return 'no accordion'; const was = d.open; d.querySelector('summary').click(); return d.open !== was ? 'ok' : 'did not toggle'; });
  note('reduced motion', 'product.html accordion toggles', acc === 'ok', acc);
  const car = await page.evaluate(async () => { const t = document.querySelector('[data-gallery-track]'); if (!t) return 'no track'; const before = t.scrollLeft; t.scrollTo({ left: t.clientWidth, behavior: 'auto' }); await new Promise((r) => setTimeout(r, 200)); return t.scrollWidth > t.clientWidth ? (t.scrollLeft !== before ? 'ok' : 'did not scroll') : 'single slide'; });
  note('reduced motion', 'product.html gallery scrolls', car === 'ok' || car === 'single slide', car);
  await ctx.close();
}

/* ---------- forced colors ---------- */
{
  const ctx = await browser.newContext({ viewport: { width: 375, height: 800 }, isMobile: true, hasTouch: true, forcedColors: 'active' });
  const page = await ctx.newPage();
  for (const url of ['index.html', 'product.html', 'checkout.html?demo=cod-on', 'thank-you.html?state=cod', 'track.html', 'account/login.html']) {
    await seedBag(page);
    await page.goto(BASE + url, { waitUntil: 'networkidle' });
    const bad = await page.evaluate(() => {
      const out = [];
      document.querySelectorAll('button, input:not([type=hidden]):not(.visually-hidden), select, textarea, .btn, .chip, .badge, .status').forEach((el) => {
        const r = el.getBoundingClientRect();
        if (r.width < 3 || r.height < 3 || getComputedStyle(el).visibility === 'hidden') return;
        const cs = getComputedStyle(el);
        const border = cs.borderStyle !== 'none' && parseFloat(cs.borderTopWidth) > 0;
        const outline = cs.outlineStyle !== 'none' && parseFloat(cs.outlineWidth) > 0;
        const type = el.type;
        if (!border && !outline && !(el.tagName === 'BUTTON' && el.classList.contains('btn--ghost')) && !el.closest('.qty, .search-field, .tabbar, .icon-btn, .switch, .checkbox') && !(el.querySelector('svg') && !el.textContent.trim()) && !el.classList.contains('link-btn') && !el.classList.contains('gallery__dot') && type !== 'checkbox' && type !== 'radio') out.push(el.tagName.toLowerCase() + '.' + String(el.className).split(' ')[0] + ' "' + (el.textContent || el.value || '').trim().slice(0, 20) + '"');
      });
      return [...new Set(out)];
    });
    note('forced colors', url, bad.length === 0, bad.slice(0, 5).join(' | '));
    await page.screenshot({ path: path.join(outDir, 'forced-' + url.replace(/[^a-z0-9]+/gi, '-') + '.png') });
  }
  await ctx.close();
}

/* ---------- touch: nothing only on hover ---------- */
{
  const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  const page = await ctx.newPage();
  const m = await (async () => { await page.goto(BASE + 'index.html'); return page.evaluate(() => ({ coarse: matchMedia('(pointer: coarse)').matches, noHover: matchMedia('(hover: none)').matches })); })();
  note('touch', 'pointer media', m.coarse && m.noHover, JSON.stringify(m));
  for (const url of ['index.html', 'shop.html', 'product.html', 'category.html?c=bodysuits']) {
    await page.goto(BASE + url, { waitUntil: 'networkidle' });
    const r = await page.evaluate(() => {
      const out = [];
      // 1. stylesheet rules that use :hover to reveal something (outside a hover media query)
      const walk = (rules, inHover) => {
        for (const rule of rules) {
          if (rule.type === 4) { walk(rule.cssRules, inHover || /hover:\s*hover/.test(rule.conditionText || rule.media.mediaText)); continue; }
          if (rule.selectorText && /:hover/.test(rule.selectorText) && !inHover) {
            const s = rule.style;
            if (s.display === 'block' || s.display === 'flex' || s.visibility === 'visible' || (s.opacity === '1' && /> |\s/.test(rule.selectorText.replace(/:hover.*/, ' ').trim() ? ' ' : '')) ) out.push('reveals on :hover outside a hover media query: ' + rule.selectorText.slice(0, 80));
          }
        }
      };
      for (const sheet of document.styleSheets) { try { walk(sheet.cssRules, false); } catch (e) { /* cross-origin */ } }
      // 2. controls that stay hidden on touch: the quick-add button on product cards
      const q = [...document.querySelectorAll('.product-card:not(.product-card--oos) .product-card__quick')].find((x) => x.getClientRects().length);
      if (q) { const cs = getComputedStyle(q); const rc = q.getBoundingClientRect(); if (cs.opacity === '0' || cs.visibility === 'hidden' || rc.width < 10) out.push('quick-add button is hidden on touch'); }
      return out;
    });
    note('touch', url, r.length === 0, r.slice(0, 3).join(' | '));
  }
  // the mega menu is a desktop control: on touch the chevron button opens it
  await ctx.close();
}
await browser.close();
server.close();

write('modes.json', JSON.stringify(results, null, 1));
for (const r of results) console.log(`${r.ok ? 'PASS' : 'FAIL'} [${r.mode}] ${r.url}${r.ok ? '' : '  -> ' + r.detail}`);
console.log(`\n${results.filter((r) => r.ok).length} of ${results.length} checks passed, ${results.filter((r) => !r.ok).length} failed.`);
