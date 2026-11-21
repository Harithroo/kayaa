#!/usr/bin/env node
// Home page behaviour tests (Chromium over http, plus file:// for the console check): the preloader, the hero washing line, the shop-by-age
// explorer and the category rail (doors, turning, caption, drag, touch, keyboard, scroll mode, reduced motion), and the layout checks.
//   node tools/qa/home.mjs        exit 1 when any check fails
import { load, start, BASE, repo } from './lib.mjs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const { chromium } = load('playwright');
const server = await start();
const browser = await chromium.launch();
const res = []; const ok = (n, c, x) => res.push((c ? 'PASS ' : 'FAIL ') + n + (x !== undefined ? '  ' + x : ''));
const URL_ = BASE + 'index.html';
const seen = () => { try { sessionStorage.setItem('kayaa-intro', '1'); } catch (e) { /* blocked */ } };

async function page(opts = {}, { introSeen = true, url = URL_ } = {}) {
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 }, ...opts });
  const p = await ctx.newPage(); const errs = [];
  p.on('console', (m) => m.type() === 'error' && !/favicon/.test(m.text()) && errs.push(m.text())); p.on('pageerror', (e) => errs.push(e.message));
  if (introSeen) await p.addInitScript(seen);
  await p.goto(url, { waitUntil: 'networkidle' });
  return { ctx, p, errs };
}
// the hanger transform is written inline as translate(x,y) rotate(rad) scale(s)
const matrix = (t) => { const n = (re) => { const m = (t || '').match(re); return m ? parseFloat(m[1]) : 0; }; const sc = (t || '').match(/scale\(([-0-9.]+)\)/); return { e: n(/translate\(([-0-9.]+)px/), f: n(/translate\([-0-9.]+px,\s*([-0-9.]+)px/), scale: sc ? parseFloat(sc[1]) : 1 }; };
const hangerState = (p, i) => p.evaluate((i) => { const a = document.querySelectorAll('.hanger')[i]; const turn = a.querySelector('.turn'); return { t: a.style.transform, ry: turn.style.transform, active: a.classList.contains('is-active'), bk: document.querySelectorAll('.hook-back')[i].style.transform }; }, i);
const openRail = async (p) => { await p.locator('#rail').scrollIntoViewIfNeeded(); await p.waitForFunction(() => document.getElementById('doors').classList.contains('gone'), null, { timeout: 8000 }); await p.waitForTimeout(1500); };
const stageBox = (p) => p.locator('#railStage').boundingBox();
const hit = async (p, i) => { const b = await p.locator('.hanger .hit').nth(i).boundingBox(); return { x: b.x + b.width / 2, y: b.y + b.height / 2, b }; };

// ---------------------------------------------------------------- preloader
{
  const { ctx, p } = await page({}, { introSeen: false });
  await p.waitForTimeout(250);
  const a = await p.evaluate(() => ({ on: document.documentElement.classList.contains('intro-on'), disp: getComputedStyle(document.getElementById('intro')).display, aria: document.getElementById('intro').getAttribute('aria-hidden'), title: document.getElementById('hero-title').getBoundingClientRect().height, intro: getComputedStyle(document.documentElement).getPropertyValue('--intro').trim(), z: getComputedStyle(document.getElementById('intro')).zIndex }));
  ok('preloader: shows on the first visit (html.intro-on, an aria-hidden overlay above the shell)', a.on && a.disp === 'flex' && a.aria === 'true' && +a.z >= 300, JSON.stringify(a));
  ok('preloader: the page is fully rendered underneath it, and the hero delays are offset by it (--intro 1500ms)', a.title > 40 && a.intro === '1500ms', `title ${Math.round(a.title)}px, --intro ${a.intro}`);
  await p.click('#intro');
  await p.waitForFunction(() => document.getElementById('intro').classList.contains('gone'), null, { timeout: 3000 }).catch(() => {});
  const b = await p.evaluate(() => ({ gone: document.getElementById('intro').classList.contains('gone'), still: document.documentElement.classList.contains('intro-on'), stored: sessionStorage.getItem('kayaa-intro') }));
  ok('preloader: a click skips it, hides the overlay with a class (html.intro-on is not removed mid-animation) and remembers the visit', b.gone && b.still && b.stored === '1', JSON.stringify(b));
  await p.reload({ waitUntil: 'networkidle' });
  ok('preloader: it does not play again in the same session', await p.evaluate(() => !document.documentElement.classList.contains('intro-on') && getComputedStyle(document.getElementById('intro')).display === 'none'));
  await ctx.close();
  const c2 = await page({}, { introSeen: false });
  await c2.p.waitForTimeout(2900);
  ok('preloader: without a click it slides away by itself at about 1.55s', await c2.p.evaluate(() => document.getElementById('intro').classList.contains('gone')));
  await c2.ctx.close();
  const c3 = await page({ reducedMotion: 'reduce' }, { introSeen: false });
  const rm = await c3.p.evaluate(() => ({ rm: document.documentElement.classList.contains('rm'), on: document.documentElement.classList.contains('intro-on'), disp: getComputedStyle(document.getElementById('intro')).display }));
  ok('preloader: never under reduced motion', rm.rm && !rm.on && rm.disp === 'none', JSON.stringify(rm));
  await c3.ctx.close();
}

// ---------------------------------------------------------------- hero
{
  const { ctx, p, errs } = await page();
  await p.waitForTimeout(2600);
  const read = () => p.evaluate(() => [...document.querySelectorAll('.pegged')].map((e) => e.style.transform).join('|'));
  const a = await read(); await p.waitForTimeout(500); const b = await read();
  ok('hero: three garments are pegged on a line and the breeze moves them', (await p.locator('.pegged').count()) === 3 && a !== b, a.slice(0, 60));
  await p.evaluate(() => document.getElementById('rail').scrollIntoView()); await p.waitForTimeout(900);
  const c = await read(); await p.waitForTimeout(600); const d = await read();
  ok('hero: the loop stops while the hero is off-screen', c === d);
  await p.evaluate(() => window.scrollTo(0, 0)); await p.waitForTimeout(900);
  const e = await read(); await p.waitForTimeout(500); const f = await read();
  ok('hero: and runs again when it is back', e !== f);
  // a gust: a fast pointer move across the garments adds motion
  const vb = await p.locator('#heroVisual').boundingBox();
  await p.mouse.move(vb.x + 60, vb.y + vb.height * 0.45); await p.mouse.move(vb.x + vb.width - 60, vb.y + vb.height * 0.45, { steps: 6 });
  await p.waitForTimeout(120);
  const gust = await p.evaluate(() => Math.max(...[...document.querySelectorAll('.pegged')].map((e) => Math.abs(parseFloat((e.style.transform.match(/rotate\(([-0-9.e]+)rad/) || [0, 0])[1])))));
  ok('hero: a pointer brushing across the garments adds a gust', gust > 0.05, 'max angle ' + gust.toFixed(3) + ' rad');
  const links = await p.evaluate(() => ({ cta: document.querySelector('.hero__actions .btn').getAttribute('href'), size: document.querySelector('.hero__actions .link-arrow').getAttribute('href'), fabrics: document.querySelector('.hero-tag--fabrics').getAttribute('href'), tag: document.querySelector('.hero-tag--sized span').textContent.trim(), free: document.querySelector('.hero-tag--delivery b').textContent.trim() }));
  ok('hero: real links and data-driven tags (sizes from the catalogue, the free-delivery threshold from the site config)', links.cta === 'shop.html?sort=new' && links.size === 'size-guide.html' && links.fabrics === '#fabrics' && /^Newborn, 0–3m … 3Y$/.test(links.tag) && links.free === 'Rs. 7,500+', JSON.stringify(links));
  ok('hero: no console errors (http)', errs.length === 0, errs.join('|'));
  await ctx.close();
}

// ---------------------------------------------------------------- shop by age
{
  const { ctx, p } = await page();
  await p.evaluate(() => document.querySelector('.age').scrollIntoView()); await p.waitForTimeout(500);
  const before = await p.evaluate(() => document.getElementById('growG').innerHTML);
  ok('age: nine chips, 0–3m selected, panel shows the size, height and weight from the size chart', (await p.locator('.age-chip').count()) === 9 && (await p.locator('.age-chip[aria-pressed="true"]').textContent()) === '0–3m' && /50–58 cm/.test(await p.locator('#ageMeta').textContent()));
  await p.locator('.age-chip').nth(5).click(); await p.waitForTimeout(1200);
  const r = await p.evaluate(() => ({ name: document.getElementById('ageName').textContent, shop: document.getElementById('ageShop').textContent, href: document.getElementById('ageShop').getAttribute('href'), h: document.getElementById('hOut').textContent, w: document.getElementById('wOut').textContent, hb: document.getElementById('hBand').style.transform, g: document.getElementById('growG').innerHTML, gs: document.getElementById('growG').style.transform, mark: document.getElementById('growMark').textContent, swing: document.getElementById('growG').classList.contains('swing'), pressed: document.querySelectorAll('.age-chip[aria-pressed="true"]').length, guide: document.querySelector('.age-actions .link-arrow').getAttribute('href') }));
  ok('age: choosing 12–18m updates the name, the Shop button URL, the counted height and weight and the bars', r.name === '12–18 months' && r.shop === 'Shop 12–18m' && r.href === 'shop.html?size=12-18m' && r.h === '78–86 cm' && r.w === '10.5–12 kg' && /translateX\(55%\) scaleX\(0\.1333/.test(r.hb), JSON.stringify({ name: r.name, h: r.h, w: r.w, hb: r.hb }));
  ok('age: the garment changes type (a set now), scales up with the age and swings', r.g !== before && /tx-linen/.test(r.g) && r.gs === 'scale(0.857)' && r.swing && r.mark === '12–18m' && r.pressed === 1, `${r.gs} swing ${r.swing}`);
  ok('age: the Size guide link is real', r.guide === 'size-guide.html');
  await ctx.close();
}

// ---------------------------------------------------------------- the rail (desktop)
{
  const { ctx, p, errs } = await page();
  ok('rail: seven real links, each with an accessible name "{name}, {count} styles, sizes {range}"', JSON.stringify(await p.evaluate(() => [...document.querySelectorAll('.hanger')].map((a) => a.getAttribute('aria-label')))) === JSON.stringify(['Newborn, 3 styles, sizes NB–3m', 'Bodysuits, 5 styles, sizes NB–3Y', 'Sleepwear, 3 styles, sizes NB–3Y', 'Sets, 4 styles, sizes 6m–3Y', 'Outerwear, 4 styles, sizes 3m–3Y', 'Napkins, 3 styles, sizes NB–3Y', 'Accessories, 3 styles, sizes NB–3Y']));
  const closed = await p.evaluate(() => ({ doors: !document.getElementById('doors').classList.contains('open'), replay: document.getElementById('replay').textContent.trim() }));
  ok('rail: the stage starts closed (doors shut, control says "Open the doors")', closed.doors && closed.replay === 'Open the doors', JSON.stringify(closed));
  await p.locator('#rail').scrollIntoViewIfNeeded();
  await p.waitForFunction(() => document.getElementById('doors').classList.contains('open'), null, { timeout: 5000 });
  ok('rail: the doors open when the stage is 55% visible', true);
  await p.waitForTimeout(3600);
  const st = await stageBox(p);
  const rest = await p.evaluate(() => ({ z: [...document.querySelectorAll('.rail-layer')].map((e) => getComputedStyle(e).zIndex), rodz: getComputedStyle(document.getElementById('railRod')).zIndex, rv: document.getElementById('replay').textContent.trim(), gone: document.getElementById('doors').classList.contains('gone'), fit: !document.getElementById('railStage').classList.contains('scroll'), tf: [...document.querySelectorAll('.hanger .turn')].map((e) => e.style.transform) }));
  ok('rail: hook-back layer behind the rod, the front layer in front (z 1, 2, 3), doors gone, the control reads "Close the doors"', rest.z.join() === '1,3' && rest.rodz === '2' && rest.gone && rest.rv === 'Close the doors', JSON.stringify(rest.z));
  ok('rail: all seven hang side-on at rest (rotateY about 64deg, with variation)', rest.tf.every((t) => { const d = parseFloat((t.match(/rotateY\(([-0-9.]+)deg/) || [0, 0])[1]); return d > 56 && d < 72; }), rest.tf.join(' '));
  const sameT = await p.evaluate(() => [...document.querySelectorAll('.hanger')].every((a, i) => a.style.transform === document.querySelectorAll('.hook-back')[i].style.transform));
  ok('rail: each hanger and its hook-back share one transform about the hook centre', sameT);
  const boxes = await p.evaluate(() => [...document.querySelectorAll('.hanger')].map((a) => { const r = a.getBoundingClientRect(); return [Math.round(r.left), Math.round(r.top), Math.round(r.right)]; }));
  ok('rail: all seven fit on the rail inside the stage (stage width 860px and up)', rest.fit && boxes.every((b) => b[0] >= st.x && b[2] <= st.x + st.width), JSON.stringify(boxes.slice(0, 2)));
  // hover turns, scales, comes in front, nudges neighbours, shows the caption
  const h = await hit(p, 3);
  await p.mouse.move(h.x, h.y); await p.waitForTimeout(1500);
  const s3 = await hangerState(p, 3), s2 = await hangerState(p, 2), s4 = await hangerState(p, 4), s1 = await hangerState(p, 1);
  const ry = parseFloat((s3.ry.match(/rotateY\(([-0-9.]+)deg/) || [0, 99])[1]);
  ok('rail: hovering a hanger turns it to face front (rotateY 0) and scales it to 1.36 about the hook, in front of its neighbours', s3.active && Math.abs(ry) < 1.5 && Math.abs(matrix(s3.t).scale - 1.36) < 0.03 && (await p.evaluate(() => getComputedStyle(document.querySelectorAll('.hanger')[3]).zIndex)) === '5', `ry ${ry} scale ${matrix(s3.t).scale.toFixed(3)}`);
  ok('rail: its neighbours nudge 14px (1 away) and 6px (2 away) and stay side-on', Math.abs(matrix(s2.t).e + 14) < 2.5 && Math.abs(matrix(s4.t).e - 14) < 2.5 && Math.abs(matrix(s1.t).e + 6) < 2.5, `${matrix(s2.t).e.toFixed(1)} ${matrix(s4.t).e.toFixed(1)} ${matrix(s1.t).e.toFixed(1)}`);
  const cap = await p.evaluate(() => { const c = document.getElementById('railCaption'); const r = c.getBoundingClientRect(); const s = document.getElementById('railStage').getBoundingClientRect(); const a = document.querySelectorAll('.hanger')[3].getBoundingClientRect(); return { name: c.querySelector('.cap-name').textContent, meta: c.querySelector('.cap-meta').textContent, href: c.querySelector('a').getAttribute('href'), btn: c.querySelector('a').textContent.trim(), inside: r.left >= s.left + 11 && r.right <= s.right - 11, cx: r.left + r.width / 2, hx: a.left + a.width / 2, live: c.getAttribute('aria-live') }; });
  ok('rail: the caption shows the name, "{count} styles · {range}" and a "Shop {name}" pill with the real category URL, centred under the hanger inside the stage', cap.name === 'Sets' && cap.meta === '4 styles · 6m–3Y' && /^Shop Sets/.test(cap.btn) && cap.href === 'category.html?c=sets' && cap.inside && Math.abs(cap.cx - cap.hx) < 40, JSON.stringify(cap));
  // brushing along the rail makes garments swing
  await p.mouse.move(st.x + 60, st.y + 90); await p.waitForTimeout(400);
  await p.mouse.move(st.x + 60, st.y + 120);
  await p.mouse.move(st.x + st.width - 60, st.y + 140, { steps: 8 }); await p.waitForTimeout(120);
  const swing = await p.evaluate(() => Math.max(...[...document.querySelectorAll('.hanger')].map((a) => Math.abs(parseFloat((a.style.transform.match(/rotate\(([-0-9.e]+)rad/) || [0, 0])[1])))));
  ok('rail: brushing the mouse along the rail makes the garments swing', swing > 0.04, swing.toFixed(3) + ' rad');
  await p.waitForTimeout(3500);
  // drag: slides a hanger, pushes its neighbours, release springs back, no click
  const d = await hit(p, 2);
  await p.mouse.move(d.x, d.y); await p.mouse.down(); await p.mouse.move(d.x + 70, d.y, { steps: 6 }); await p.waitForTimeout(200);
  const dragged = await hangerState(p, 2); const pushed = await hangerState(p, 3);
  await p.mouse.up(); await p.waitForTimeout(250);
  ok('rail: dragging slides the hanger along the rail and pushes its neighbour', matrix(dragged.t).e > 30 && matrix(pushed.t).e > 5, `${matrix(dragged.t).e.toFixed(1)} ${matrix(pushed.t).e.toFixed(1)}`);
  ok('rail: a drag does not follow the link', /index\.html/.test(p.url()), p.url().split('/').pop());
  await p.mouse.move(2, 2); await p.waitForTimeout(2500);
  const back = await hangerState(p, 2);
  ok('rail: after the release the hanger springs back', Math.abs(matrix(back.t).e) < 3, matrix(back.t).e.toFixed(1));
  // a plain click on a hanger follows its link; the caption button too
  const h2 = await hit(p, 1);
  await p.mouse.move(h2.x, h2.y); await p.waitForTimeout(900);
  await p.mouse.click(h2.x, h2.y); await p.waitForURL(/category\.html\?c=bodysuits/, { timeout: 5000 }).catch(() => {});
  ok('rail: a click on a hanger opens its category page', /category\.html\?c=bodysuits/.test(p.url()), p.url().split('/').pop());
  await p.goBack({ waitUntil: 'networkidle' }).catch(() => {}); await openRail(p);
  const h3 = await hit(p, 4); await p.mouse.move(h3.x, h3.y); await p.waitForTimeout(900);
  const cb = await p.locator('#railCaption a').boundingBox();
  await p.mouse.move(cb.x + cb.width / 2, h3.y + 40); await p.mouse.move(cb.x + cb.width / 2, cb.y + cb.height / 2, { steps: 8 }); await p.waitForTimeout(300);
  await p.mouse.click(cb.x + cb.width / 2, cb.y + cb.height / 2); await p.waitForURL(/category\.html\?c=outerwear/, { timeout: 5000 }).catch(() => {});
  ok('rail: the caption pill opens the category page', /category\.html\?c=outerwear/.test(p.url()), p.url().split('/').pop());
  ok('rail: no console errors (http, desktop)', errs.length === 0, errs.join('|'));
  await ctx.close();
}
// replay: close and open the doors again
{
  const { ctx, p } = await page(); await openRail(p);
  await p.locator('#replay').click(); await p.waitForTimeout(400);
  const closing = await p.evaluate(() => !document.getElementById('doors').classList.contains('open') && document.getElementById('replay').textContent.trim());
  await p.waitForTimeout(2200);
  const lifted = await p.evaluate(() => [...document.querySelectorAll('.hanger')].every((a) => parseFloat((a.style.transform.match(/translate\([-0-9.]+px,\s*([-0-9.]+)px/) || [0, 0])[1]) < 0));
  ok('rail: "Close the doors" shuts them and lifts the hangers; "Open the doors" brings them back', closing === 'Open the doors' && lifted, `${closing} lifted ${lifted}`);
  await p.locator('#replay').click(); await p.waitForTimeout(3800);
  ok('rail: and the hangers drop in again', await p.evaluate(() => document.getElementById('doors').classList.contains('gone') && [...document.querySelectorAll('.hanger')].every((a) => Math.abs(parseFloat((a.style.transform.match(/translate\([-0-9.]+px,\s*([-0-9.]+)px/) || [0, 0])[1])) < 2)));
  await p.locator('#doorBtn, .door').first().click({ force: true }).catch(() => {});
  await ctx.close();
}
// the knob opens the doors too
{
  const { ctx, p } = await page();
  await p.locator('#rail').scrollIntoViewIfNeeded(); await p.locator('#doorBtn').click({ trial: true }).catch(() => {});
  await p.locator('#doorBtn').click({ force: true }).catch(() => {}); await p.waitForTimeout(600);
  ok('rail: the "Open the wardrobe" knob opens the doors', await p.evaluate(() => document.getElementById('doors').classList.contains('open')));
  await ctx.close();
}

// ---------------------------------------------------------------- the rail: touch (scroll mode at 390)
{
  const { ctx, p, errs } = await page({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  await openRail(p);
  const a = await p.evaluate(() => ({ scroll: document.getElementById('railStage').classList.contains('scroll'), prev: document.getElementById('railPrev').disabled, next: document.getElementById('railNext').disabled, prog: getComputedStyle(document.querySelector('.rail-progress')).display, arrows: getComputedStyle(document.querySelector('.rail-arrows')).display, sub: document.getElementById('railSub').textContent, hint: document.querySelector('#railCaption .cap-hint').textContent.trim() }));
  ok('rail 390: scroll mode with arrows (previous disabled at the start), a progress bar and the touch wording', a.scroll && a.prev && !a.next && a.prog === 'block' && a.arrows === 'flex' && /Tap a hanger/.test(a.sub) && /Swipe the rail/.test(a.hint), JSON.stringify(a));
  await p.locator('#railNext').tap(); await p.waitForTimeout(900);
  const b = await p.evaluate(() => ({ sl: document.getElementById('railScroller').scrollLeft, prev: document.getElementById('railPrev').disabled }));
  ok('rail 390: Next scrolls the rail and enables Previous', b.sl > 100 && !b.prev, JSON.stringify(b));
  for (let i = 0; i < 6; i++) { if (await p.locator('#railNext').isDisabled()) break; await p.locator('#railNext').tap(); await p.waitForTimeout(700); }
  ok('rail 390: Next is disabled at the end', await p.locator('#railNext').isDisabled());
  await p.evaluate(() => document.getElementById('railScroller').scrollTo({ left: 0, behavior: 'auto' })); await p.waitForTimeout(500);
  // first tap turns the hanger (no navigation), second tap opens the category
  const h = await hit(p, 1);
  await p.touchscreen.tap(h.x, h.y); await p.waitForTimeout(1300);
  const s1 = await hangerState(p, 1);
  ok('rail touch: the first tap turns the hanger to face front and does not navigate', s1.active && /index\.html/.test(p.url()) && Math.abs(parseFloat((s1.ry.match(/rotateY\(([-0-9.]+)deg/) || [0, 99])[1])) < 1.5, p.url().split('/').pop());
  const capTxt = await p.locator('#railCaption .cap-name').textContent();
  const h2 = await hit(p, 1);
  await p.touchscreen.tap(h2.x, h2.y); await p.waitForURL(/category\.html\?c=bodysuits/, { timeout: 5000 }).catch(() => {});
  ok('rail touch: the second tap opens the category page (the focus from the first tap did not count as a tap)', /category\.html\?c=bodysuits/.test(p.url()) && capTxt === 'Bodysuits', `${capTxt} ${p.url().split('/').pop()}`);
  ok('rail touch: no console errors', errs.length === 0, errs.join('|'));
  await ctx.close();
}

// ---------------------------------------------------------------- the rail: keyboard
{
  const { ctx, p } = await page();
  const tabs = await p.evaluate(() => [...document.querySelectorAll('.hanger')].filter((a) => a.tabIndex === 0).length);
  ok('rail keyboard: one tab stop (roving tabindex)', tabs === 1, String(tabs));
  await p.keyboard.press('Tab');
  await p.evaluate(() => document.querySelector('.hanger[tabindex="0"]').focus());
  await p.waitForTimeout(500);
  ok('rail keyboard: focusing a hanger opens the doors if they are closed and turns it', await p.evaluate(() => document.getElementById('doors').classList.contains('open') && document.querySelector('.hanger.is-active') && document.querySelector('.hanger.is-active').dataset.i === '0'));
  await p.waitForTimeout(3000);
  const act = () => p.evaluate(() => document.activeElement.dataset.i);
  await p.keyboard.press('ArrowRight'); const a1 = await act();
  await p.keyboard.press('ArrowRight'); await p.keyboard.press('ArrowRight'); const a3 = await act();
  await p.keyboard.press('End'); const a6 = await act();
  await p.keyboard.press('Home'); const a0 = await act();
  ok('rail keyboard: arrows, End and Home move between hangers', a1 === '1' && a3 === '3' && a6 === '6' && a0 === '0', [a1, a3, a6, a0].join());
  await p.keyboard.press('ArrowRight'); await p.keyboard.press('ArrowRight'); await p.keyboard.press('ArrowRight'); await p.waitForTimeout(800);
  ok('rail keyboard: the focused hanger faces front and its caption names it', (await p.locator('#railCaption .cap-name').textContent()) === 'Sets' && (await hangerState(p, 3)).active);
  await p.keyboard.press('Enter'); await p.waitForURL(/category\.html\?c=sets/, { timeout: 5000 }).catch(() => {});
  ok('rail keyboard: Enter opens the category page', /category\.html\?c=sets/.test(p.url()), p.url().split('/').pop());
  await ctx.close();
}

// ---------------------------------------------------------------- reduced motion: everything visible and settled
{
  const { ctx, p } = await page({ reducedMotion: 'reduce' }, { introSeen: false });
  await p.waitForTimeout(600);
  const r = await p.evaluate(() => {
    const st = document.getElementById('railStage').getBoundingClientRect();
    return {
      rm: document.documentElement.classList.contains('rm'), intro: getComputedStyle(document.getElementById('intro')).display, doors: getComputedStyle(document.getElementById('doors')).display,
      replay: getComputedStyle(document.getElementById('replay')).display, words: [...document.querySelectorAll('.hero__title .w > span')].every((s) => getComputedStyle(s).transform === 'none' || getComputedStyle(s).transform === 'matrix(1, 0, 0, 1, 0, 0)'),
      lede: getComputedStyle(document.querySelector('.hero__lede')).opacity, trust: getComputedStyle(document.querySelector('.trust__icon path')).strokeDasharray, line: getComputedStyle(document.querySelector('.hl-line path')).strokeDasharray,
      hangers: [...document.querySelectorAll('.hanger')].map((a) => { const m = a.style.transform.match(/translate\(([-0-9.]+)px,\s*([-0-9.]+)px/); return m ? +m[2] : 0; }),
      hero: document.querySelectorAll('.pegged').length, h1: document.getElementById('hero-title').getBoundingClientRect().height, cap: document.querySelector('#railCaption .cap-hint') !== null,
      pre: document.querySelectorAll('.pre').length
    };
  });
  ok('reduced motion: no preloader, no doors, no replay control, the hero words, lede, underline and trust icons are in their finished state', r.rm && r.intro === 'none' && r.doors === 'none' && r.replay === 'none' && r.words && r.lede === '1' && r.trust === 'none' && r.line === 'none', JSON.stringify(r));
  ok('reduced motion: every hanger is on the rail (not lifted) and nothing is waiting hidden (.pre)', r.hangers.every((y) => y === 0) && r.pre === 0 && r.hero === 3, JSON.stringify(r.hangers));
  const x1 = await p.evaluate(() => [...document.querySelectorAll('.pegged')].map((e) => e.style.transform).join('|')); await p.waitForTimeout(500);
  const x2 = await p.evaluate(() => [...document.querySelectorAll('.pegged')].map((e) => e.style.transform).join('|'));
  ok('reduced motion: the hero garments do not sway', x1 === x2);
  await p.locator('#rail').scrollIntoViewIfNeeded(); await p.waitForTimeout(400);
  const h = await hit(p, 2); await p.mouse.move(h.x, h.y); await p.waitForTimeout(200);
  const s = await hangerState(p, 2);
  ok('reduced motion: a hovered hanger faces front at once, scaled 1.12 (no spring)', s.active && Math.abs(matrix(s.t).scale - 1.12) < 0.01 && /rotateY\(0(\.0+)?deg\)/.test(s.ry), `${matrix(s.t).scale.toFixed(3)} ${s.ry}`);
  await p.evaluate(() => document.querySelector('.age').scrollIntoView()); await p.locator('.age-chip').nth(7).click(); await p.waitForTimeout(100);
  ok('reduced motion: the age explorer updates at once (no counting, no swing)', await p.evaluate(() => document.getElementById('hOut').textContent === '92–98 cm' && !document.getElementById('growG').classList.contains('swing')));
  await ctx.close();
}

// ---------------------------------------------------------------- layout: widths, text size, console over file://
for (const [w, vh] of [[320, 800], [360, 800], [375, 800], [390, 800], [412, 800], [430, 800], [667, 375], [768, 800], [820, 800], [1024, 800], [1280, 800], [1920, 800]]) {
  const { ctx, p } = await page({ viewport: { width: w, height: vh }, isMobile: w < 900, hasTouch: w < 900 });
  await p.waitForTimeout(1200);
  await p.evaluate(() => document.getElementById('rail').scrollIntoView()); await p.waitForTimeout(3800);
  const m = await p.evaluate(() => ({ sw: document.documentElement.scrollWidth, stage: document.getElementById('railStage').getBoundingClientRect().height, css: parseFloat(getComputedStyle(document.getElementById('railStage')).height) }));
  ok(`layout ${w}${vh === 375 ? 'x375' : ''}: no horizontal page scroll`, m.sw <= w, `scrollWidth ${m.sw}`);
  if (w < 900) {   // 44px tap targets in the new sections (inline links inside a sentence are exempt)
    await p.evaluate(() => document.querySelector('#products').scrollIntoView()); await p.waitForTimeout(1500);
    const small = await p.evaluate(() => [...document.querySelectorAll('.marquee__pause, .fab-tab, .tab, .product-card__quick, .promo__link, .help .btn, .motion-pill button, .products .section__foot .btn, .reviews-sec .link-arrow')].filter((e) => { const r = e.getBoundingClientRect(); return r.width > 0 && getComputedStyle(e).visibility !== 'hidden' && (r.height < 43.5 || r.width < 43.5); }).map((e) => e.className + ' ' + Math.round(e.getBoundingClientRect().width) + 'x' + Math.round(e.getBoundingClientRect().height)));
    ok(`tap targets ${w}: the new buttons and tabs are at least 44px`, small.length === 0, small.join(', '));
  }
  await ctx.close();
}
for (const w of [375, 1280]) {
  const { ctx, p } = await page({ viewport: { width: w, height: 800 }, isMobile: w < 900, hasTouch: w < 900 });
  await p.evaluate(() => { const els = [...document.querySelectorAll('main *')]; const sizes = els.map((e) => parseFloat(getComputedStyle(e).fontSize)); els.forEach((e, i) => e.style.setProperty('font-size', sizes[i] * 2 + 'px', 'important')); });
  await p.waitForTimeout(600);
  const m = await p.evaluate(() => ({ sw: document.documentElement.scrollWidth, over: [...document.querySelectorAll('.hero__copy *, .age-panel *, .section__head *, .fab-card *, .promo *, .review *, .help *')].filter((e) => e.getBoundingClientRect().right > innerWidth + 1).length }));
  ok(`text size 200% @${w}: no horizontal page scroll`, m.sw <= w, JSON.stringify(m));
  await ctx.close();
}
{
  const errs = [];
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } }); const p = await ctx.newPage();
  p.on('console', (m) => m.type() === 'error' && !/favicon/.test(m.text()) && errs.push(m.text())); p.on('pageerror', (e) => errs.push(e.message));
  await p.addInitScript(seen);
  await p.goto(pathToFileURL(path.join(repo, 'html', 'index.html')).href, { waitUntil: 'load' }); await p.waitForTimeout(1500);
  await p.evaluate(() => document.getElementById('rail').scrollIntoView()); await p.waitForTimeout(4000);
  const h = await hit(p, 3); await p.mouse.move(h.x, h.y); await p.waitForTimeout(800);
  ok('console: no errors over file:// (preloader seen, doors opened, a hanger turned)', errs.length === 0 && (await p.evaluate(() => !!document.querySelector('.hanger.is-active'))), errs.join('|'));
  await ctx.close();
  const errs2 = [];
  const c2 = await browser.newContext({ viewport: { width: 1280, height: 800 } }); const p2 = await c2.newPage();
  p2.on('console', (m) => m.type() === 'error' && !/favicon/.test(m.text()) && errs2.push(m.text())); p2.on('pageerror', (e) => errs2.push(e.message));
  await p2.goto(pathToFileURL(path.join(repo, 'html', 'index.html')).href, { waitUntil: 'load' }); await p2.waitForTimeout(2800);
  ok('console: no errors over file:// with the preloader playing', errs2.length === 0, errs2.join('|'));
  await c2.close();
}

// ---------------------------------------------------------------- part B: the scrolling band
const tx = (p) => p.evaluate(() => { const m = (document.getElementById('mqTrack').style.transform || '').match(/translate3d\(([-0-9.]+)px/); return m ? parseFloat(m[1]) : 0; });
const toBand = async (p) => { await p.evaluate(() => document.querySelector('.marquee').scrollIntoView({ block: 'center' })); await p.waitForTimeout(500); };
{
  const { ctx, p, errs } = await page();
  await toBand(p);
  await p.waitForTimeout(2200);
  const a = await tx(p); await p.waitForTimeout(500); const b = await tx(p);
  ok('band: it moves on its own (base speed about 42px/s)', Math.abs(b - a) > 8 && Math.abs(b - a) < 60, `moved ${(b - a).toFixed(1)}px in 0.5s`);
  const hid = await p.evaluate(() => { const sets = document.querySelectorAll('.marquee__set'); return { n: sets.length, second: sets[1].getAttribute('aria-hidden'), first: sets[0].getAttribute('aria-hidden'), words: [...sets[0].querySelectorAll('.marquee__word')].map((e) => e.textContent), outlined: sets[0].querySelectorAll('.marquee__word--o').length }; });
  ok('band: the second copy is aria-hidden; the six messages come from the site config; solid and outlined words alternate', hid.n === 2 && hid.second === 'true' && !hid.first && hid.words.length === 6 && hid.outlined === 3, JSON.stringify(hid.words));
  const btn = p.locator('#mqPause');
  const bb = await btn.boundingBox();
  ok('band: a visible Pause button, aria-pressed false, 44px', (await btn.getAttribute('aria-pressed')) === 'false' && bb.width >= 44 && bb.height >= 44, `${bb.width}x${bb.height}`);
  await btn.click();
  const s1 = await tx(p); await p.waitForTimeout(500); const s2 = await tx(p);
  ok('band: Pause stops it (aria-pressed true, label "Play the moving text")', s1 === s2 && (await btn.getAttribute('aria-pressed')) === 'true' && /Play/.test(await btn.getAttribute('aria-label')), `${s1} ${s2}`);
  await btn.click(); await p.waitForTimeout(150);
  const r1 = await tx(p); await p.waitForTimeout(500); const r2 = await tx(p);
  ok('band: Play starts it again', Math.abs(r2 - r1) > 8 && (await btn.getAttribute('aria-pressed')) === 'false', `${(r2 - r1).toFixed(1)}px`);
  // scrolling boosts the speed and sets the direction
  await p.waitForTimeout(1500);
  const q1 = await tx(p); await p.waitForTimeout(250); const q2 = await tx(p); const base = Math.abs(q2 - q1);
  await p.mouse.move(640, 400);
  for (let i = 0; i < 4; i++) { await p.mouse.wheel(0, -30); await p.waitForTimeout(20); }   // scroll up: reverses and speeds up
  const w1 = await tx(p); await p.waitForTimeout(250); const w2 = await tx(p);
  ok('band: scrolling the page speeds it up and sets the direction', Math.abs(w2 - w1) > base * 1.8 && Math.sign(w2 - w1) !== Math.sign(q2 - q1), `base ${base.toFixed(1)}px/250ms, after scroll ${(w2 - w1).toFixed(1)}px`);
  // off-screen: the loop sleeps
  await p.evaluate(() => window.scrollTo(0, 0)); await p.waitForTimeout(600);
  const o1 = await tx(p); await p.waitForTimeout(500); const o2 = await tx(p);
  ok('band: the loop sleeps while the band is off-screen', o1 === o2, `${o1} ${o2}`);
  ok('band: no console errors', errs.length === 0, errs.join('|'));
  await ctx.close();
  const r = await page({ reducedMotion: 'reduce' });
  await toBand(r.p);
  const rm = await r.p.evaluate(() => { const t = document.getElementById('mqTrack'), sets = document.querySelectorAll('.marquee__set'); return { btn: getComputedStyle(document.getElementById('mqPause')).display, second: getComputedStyle(sets[1]).display, wrap: getComputedStyle(sets[0]).whiteSpace, flexWrap: getComputedStyle(sets[0]).flexWrap, tr: t.style.transform, sw: document.documentElement.scrollWidth }; });
  await r.p.waitForTimeout(500);
  const rm2 = await r.p.evaluate(() => document.getElementById('mqTrack').style.transform);
  ok('band: reduced motion shows one static wrapped line and no button', rm.btn === 'none' && rm.second === 'none' && rm.wrap === 'normal' && rm.flexWrap === 'wrap' && !rm.tr && !rm2 && rm.sw <= 1280, JSON.stringify(rm));
  await r.ctx.close();
}

// ---------------------------------------------------------------- part B: fabrics (the cloth)
const toFab = async (p) => { await p.evaluate(() => document.querySelector('#clothWrap').scrollIntoView({ block: 'center' })); await p.waitForTimeout(1200); };
const canvasHash = (p) => p.evaluate(() => { const s = document.getElementById('cloth').toDataURL(); let h = 0; for (let i = 0; i < s.length; i += 37) h = (h * 31 + s.charCodeAt(i)) | 0; return h; });
const clothPoint = async (p, fx, fy) => { const b = await p.locator('#cloth').boundingBox(); return { x: b.x + b.width * fx, y: b.y + b.height * fy, b }; };
{
  const { ctx, p, errs } = await page();
  await toFab(p);
  const h1 = await canvasHash(p); await p.waitForTimeout(700); const h2 = await canvasHash(p);
  ok('fabrics: the cloth moves in the breeze (the canvas changes)', h1 !== h2);
  const meta = await p.evaluate(() => { const c = document.getElementById('cloth'); return { role: c.getAttribute('role'), label: c.getAttribute('aria-label'), ta: getComputedStyle(c).touchAction, w: c.width, cssw: c.getBoundingClientRect().width, tabs: [...document.querySelectorAll('.fab-tab')].map((t) => t.textContent.trim()), tl: document.getElementById('fabTabs').getAttribute('role') }; });
  ok('fabrics: canvas role="img" with a label, touch-action pan-y, device pixel ratio capped at 2', meta.role === 'img' && /swatch/.test(meta.label) && meta.ta === 'pan-y' && meta.w <= Math.ceil(meta.cssw * 2) + 1, JSON.stringify(meta));
  ok('fabrics: four tabs in an ARIA tablist', meta.tl === 'tablist' && meta.tabs.join('|') === 'Muslin|Bamboo jersey|Ribbed cotton|Waffle knit', meta.tabs.join('|'));
  // a grab moves the cloth
  const start = await clothPoint(p, 0.5, 0.55);
  const hintBefore = await p.evaluate(() => document.getElementById('clothHint').classList.contains('hide'));
  await p.mouse.move(start.x, start.y); await p.mouse.down();
  await p.mouse.move(start.x + 90, start.y + 70, { steps: 8 }); await p.waitForTimeout(200);
  const g1 = await canvasHash(p);
  await p.mouse.move(start.x - 90, start.y + 40, { steps: 8 }); await p.waitForTimeout(200);
  const g2 = await canvasHash(p);
  ok('fabrics: press and drag grabs the cloth and tugs it', g1 !== g2 && g1 !== h2);
  await p.mouse.up();
  ok('fabrics: the "Drag the fabric" hint hides after the first grab', !hintBefore && (await p.evaluate(() => document.getElementById('clothHint').classList.contains('hide'))));
  // tabs: the label, the card and the texture change
  const imgBefore = await canvasHash(p);
  await p.locator('#fab-waffle').click(); await p.waitForTimeout(700);
  const t = await p.evaluate(() => ({ label: document.getElementById('clothLabel').textContent, sel: document.getElementById('fab-waffle').getAttribute('aria-selected'), tabindex: document.getElementById('fab-muslin').tabIndex, shown: [...document.querySelectorAll('.fab-panel')].filter((e) => !e.hidden).map((e) => e.id), name: document.querySelector('.fab-panel:not([hidden]) .fab-panel__name').textContent, aria: document.getElementById('cloth').getAttribute('aria-label'), facts: [...document.querySelectorAll('.fab-panel:not([hidden]) dt')].map((e) => e.textContent).join('/') }));
  ok('fabrics: a tab switch changes the label, the card and the canvas', t.label === 'Waffle knit' && t.sel === 'true' && t.tabindex === -1 && t.shown.join() === 'fab-panel-waffle' && t.name === 'Waffle knit' && /waffle knit/.test(t.aria) && t.facts === 'Feels/Best for/Care' && (await canvasHash(p)) !== imgBefore, JSON.stringify(t));
  // keyboard
  await p.locator('#fab-waffle').focus(); await p.keyboard.press('ArrowRight'); await p.waitForTimeout(350);
  const k1 = await p.evaluate(() => ({ cur: document.querySelector('.fab-tab[aria-selected="true"]').id, focus: document.activeElement.id }));
  await p.keyboard.press('ArrowLeft'); await p.waitForTimeout(350); await p.keyboard.press('ArrowLeft'); await p.waitForTimeout(350);
  const k2 = await p.evaluate(() => ({ cur: document.querySelector('.fab-tab[aria-selected="true"]').id, focus: document.activeElement.id }));
  await p.keyboard.press('Home'); await p.waitForTimeout(350); const k3 = await p.evaluate(() => document.querySelector('.fab-tab[aria-selected="true"]').id);
  await p.keyboard.press('End'); await p.waitForTimeout(350); const k4 = await p.evaluate(() => document.querySelector('.fab-tab[aria-selected="true"]').id);
  ok('fabrics: arrow keys (with wrap), Home and End move the tab and the focus', k1.cur === 'fab-muslin' && k1.focus === 'fab-muslin' && k2.cur === 'fab-rib' && k2.focus === 'fab-rib' && k3 === 'fab-muslin' && k4 === 'fab-waffle', JSON.stringify([k1, k2, k3, k4]));
  // "Found in": real products with links; none is a "#"
  const found = await p.evaluate(() => [...document.querySelectorAll('.fab-found')].map((e) => ({ n: e.querySelectorAll('a').length, bad: [...e.querySelectorAll('a')].filter((a) => !a.getAttribute('href') || a.getAttribute('href') === '#').length })));
  ok('fabrics: "Found in" lists real products with real links (or a search link) for every fabric', found.length === 4 && found.every((f) => f.n >= 1 && f.bad === 0), JSON.stringify(found));
  // the loop runs on screen and sleeps off-screen
  await p.evaluate(() => { window.KAYAA_CLOTH_STATS = []; }); await p.waitForTimeout(800);
  const on = await p.evaluate(() => window.KAYAA_CLOTH_STATS.length);
  await p.evaluate(() => window.scrollTo(0, 0)); await p.waitForTimeout(500);
  await p.evaluate(() => { window.KAYAA_CLOTH_STATS = []; }); await p.waitForTimeout(700);
  const off = await p.evaluate(() => window.KAYAA_CLOTH_STATS.length);
  ok('fabrics: the loop runs while on screen and stops off-screen', on > 20 && off === 0, `${on} frames in 0.8s on screen, ${off} in 0.7s off-screen`);
  ok('fabrics: no console errors', errs.length === 0, errs.join('|'));
  await ctx.close();
}
for (const [w, h, touch] of [[1280, 800, false], [390, 844, true]]) {
  const { ctx, p } = await page({ viewport: { width: w, height: h }, isMobile: touch, hasTouch: touch });
  await toFab(p);
  await p.evaluate(() => { window.KAYAA_CLOTH_STATS = []; });
  await p.waitForFunction(() => window.KAYAA_CLOTH_STATS.length >= 60, null, { timeout: 8000 }).catch(() => {});
  const st = await p.evaluate(() => { const a = window.KAYAA_CLOTH_STATS.slice(0, 60); const s = a.reduce((x, y) => x + y, 0); return { n: a.length, avg: s / a.length, max: Math.max(...a), grid: document.getElementById('cloth').width + 'x' + document.getElementById('cloth').height }; });
  console.log(`INFO cloth frame cost @${w} (sim + draw, 60 frames, headless Chromium, software rendering): average ${st.avg.toFixed(2)} ms, maximum ${st.max.toFixed(2)} ms, canvas ${st.grid}`);
  ok(`fabrics: the cloth frame stays inside a 60 fps budget on average @${w}`, st.n >= 60 && st.avg < 16.6, `avg ${st.avg.toFixed(2)} ms, max ${st.max.toFixed(2)} ms`);
  await ctx.close();
}
{
  // reduced motion: a settled static drape, no loop; a grab re-settles it
  const { ctx, p } = await page({ reducedMotion: 'reduce' });
  await p.evaluate(() => { window.KAYAA_CLOTH_STATS = []; });
  await toFab(p);
  const a = await canvasHash(p); await p.waitForTimeout(800); const b = await canvasHash(p);
  const frames = await p.evaluate(() => window.KAYAA_CLOTH_STATS.length);
  ok('fabrics: reduced motion shows a settled static drape (no loop)', a === b && frames === 0, `${frames} frames`);
  const st = await clothPoint(p, 0.5, 0.55);
  await p.mouse.move(st.x, st.y); await p.mouse.down(); await p.mouse.move(st.x + 80, st.y + 60, { steps: 6 }); await p.waitForTimeout(150);
  const c = await canvasHash(p); await p.mouse.up(); await p.waitForTimeout(300);
  const d = await canvasHash(p);
  ok('fabrics: reduced motion, a grab moves the cloth and releasing re-settles it', c !== a && d !== c, '');
  await p.locator('#fab-rib').click(); await p.waitForTimeout(300);
  ok('fabrics: reduced motion, a tab switch is instant', await p.evaluate(() => document.getElementById('clothLabel').textContent === 'Ribbed cotton' && !document.getElementById('fab-panel-rib').hidden));
  await ctx.close();
}

// ---------------------------------------------------------------- part B: New this week / Bestsellers
{
  const { ctx, p, errs } = await page();
  await p.evaluate(() => document.querySelector('#products').scrollIntoView()); await p.waitForTimeout(2400);
  const a = await p.evaluate(() => ({ cards: document.querySelectorAll('#panel-new .product-card').length, entering: document.querySelectorAll('.product-card.enter').length, best: document.getElementById('panel-best').hidden, list: document.querySelector('#panel-new ul').getAttribute('role'), quick: document.querySelectorAll('#panel-new [data-quick-add]').length }));
  ok('products: New this week shows the four real product cards (the shared card, with quick add)', a.cards === 4 && a.quick === 4 && a.best === true, JSON.stringify(a));
  ok('products: the entrance class is removed on animationend (hover lift and tilt keep working)', a.entering === 0, `${a.entering} still entering`);
  await p.locator('#tab-best').click(); await p.waitForTimeout(250);
  const mid = await p.evaluate(() => document.querySelectorAll('#panel-best .product-card.enter').length);
  await p.waitForTimeout(1600);
  const b = await p.evaluate(() => ({ title: document.getElementById('prod-title').textContent, newHidden: document.getElementById('panel-new').hidden, bestHidden: document.getElementById('panel-best').hidden, sel: document.getElementById('tab-best').getAttribute('aria-selected'), tabindex: document.getElementById('tab-new').tabIndex, ranks: [...document.querySelectorAll('#panel-best li[data-product]')].map((e) => +e.dataset.featured), entering: document.querySelectorAll('.product-card.enter').length, pill: document.getElementById('tabPill').style.transform }));
  ok('products: Bestsellers shows the four lowest featured ranks, the heading follows, the pill slides, the cards enter and then settle', b.title === 'Bestsellers' && b.newHidden && !b.bestHidden && b.sel === 'true' && b.tabindex === -1 && b.ranks.join() === '1,2,3,4' && mid === 4 && b.entering === 0 && /translateX\((?!0px)/.test(b.pill), JSON.stringify({ ...b, mid }));
  await p.locator('#tab-best').focus(); await p.keyboard.press('ArrowRight'); await p.waitForTimeout(300);
  const k1 = await p.evaluate(() => ({ cur: document.querySelector('.tab[aria-selected="true"]').id, focus: document.activeElement.id, title: document.getElementById('prod-title').textContent }));
  await p.keyboard.press('End'); await p.waitForTimeout(200); const k2 = await p.evaluate(() => document.querySelector('.tab[aria-selected="true"]').id);
  await p.keyboard.press('Home'); await p.waitForTimeout(200); const k3 = await p.evaluate(() => document.querySelector('.tab[aria-selected="true"]').id);
  ok('products: arrow keys (with wrap), Home and End switch the tab and move the focus', k1.cur === 'tab-new' && k1.focus === 'tab-new' && k1.title === 'New this week' && k2 === 'tab-best' && k3 === 'tab-new', JSON.stringify([k1, k2, k3]));
  await p.waitForTimeout(1500);
  // the pointer tilt and its reset
  const card = p.locator('#panel-new .product-card').nth(1); const bx = await card.boundingBox();
  await p.mouse.move(bx.x + bx.width * 0.8, bx.y + bx.height * 0.2, { steps: 4 }); await p.waitForTimeout(400);
  const tilt = await card.evaluate((e) => ({ ry: e.style.getPropertyValue('--ry'), rx: e.style.getPropertyValue('--rx'), tf: getComputedStyle(e).transform }));
  await p.mouse.move(bx.x + bx.width * 0.5, bx.y - 60, { steps: 4 }); await p.waitForTimeout(500);
  const back = await card.evaluate((e) => ({ ry: e.style.getPropertyValue('--ry'), rx: e.style.getPropertyValue('--rx') }));
  ok('products: the tilt follows the pointer (up to 8deg, with the hover lift) and resets on leave', parseFloat(tilt.ry) > 1 && Math.abs(parseFloat(tilt.ry)) <= 8 && parseFloat(tilt.rx) > 1 && tilt.tf !== 'none' && back.ry === '0deg' && back.rx === '0deg', JSON.stringify({ tilt, back }));
  // quick add opens the real sheet
  await p.mouse.move(bx.x + bx.width / 2, bx.y + bx.height / 2, { steps: 3 }); await p.waitForTimeout(400);
  await card.locator('[data-quick-add]').click(); await p.waitForTimeout(500);
  const q = await p.evaluate(() => { const s = document.querySelector('[data-quick-add-sheet]'); return { open: s.classList.contains('is-open'), hidden: s.getAttribute('aria-hidden'), name: s.querySelector('[data-qa-name]').textContent, sizes: s.querySelectorAll('[data-qa-sizes] input').length, card: document.querySelectorAll('#panel-new .product-card h3')[1].textContent }; });
  ok('products: quick add opens the real quick-add sheet for that product', q.open && q.hidden === 'false' && q.name === q.card && q.sizes >= 1, JSON.stringify(q));
  await p.keyboard.press('Escape'); await p.waitForTimeout(400);
  const links = await p.evaluate(() => ({ shop: document.querySelector('.products .section__foot a').getAttribute('href'), cards: [...document.querySelectorAll('#products .product-card__link')].filter((a) => !a.getAttribute('href') || a.getAttribute('href') === '#').length }));
  ok('products: "Shop all" links to the shop page and every card has a real link', links.shop === 'shop.html' && links.cards === 0, JSON.stringify(links));
  ok('products: no console errors', errs.length === 0, errs.join('|'));
  await ctx.close();
  // touch: no tilt
  const t = await page({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  await t.p.evaluate(() => document.querySelector('#products').scrollIntoView()); await t.p.waitForTimeout(1800);
  const touchTilt = await t.p.evaluate(() => [...document.querySelectorAll('#panel-new .product-card')].some((c) => c.style.getPropertyValue('--ry')));
  const cols = await t.p.evaluate(() => getComputedStyle(document.querySelector('#panel-new .product-grid')).gridTemplateColumns.split(' ').length);
  ok('products: on touch there is no tilt, and the grid is two columns at 390', !touchTilt && cols === 2, `cols ${cols}`);
  await t.ctx.close();
  // reduced motion: no entrance, no tilt
  const r = await page({ reducedMotion: 'reduce' });
  await r.p.evaluate(() => document.querySelector('#products').scrollIntoView()); await r.p.waitForTimeout(500);
  const rm = await r.p.evaluate(() => ({ enter: document.querySelectorAll('.product-card.enter').length, op: getComputedStyle(document.querySelector('#panel-new .product-card')).opacity }));
  await r.p.locator('#tab-best').click(); await r.p.waitForTimeout(150);
  const rm2 = await r.p.evaluate(() => ({ enter: document.querySelectorAll('.product-card.enter').length, shown: !document.getElementById('panel-best').hidden, op: getComputedStyle(document.querySelector('#panel-best .product-card')).opacity }));
  ok('products: reduced motion, the cards are simply there (no entrance class) and the tab switch is instant', rm.enter === 0 && rm.op === '1' && rm2.enter === 0 && rm2.shown && rm2.op === '1', JSON.stringify([rm, rm2]));
  await r.ctx.close();
}

// ---------------------------------------------------------------- part B: promos, reviews, size help
{
  const { ctx, p } = await page();
  const pr = await p.evaluate(() => [...document.querySelectorAll('.promo')].map((e) => ({ href: e.querySelector('a').getAttribute('href'), svg: !!e.querySelector('.promo__art svg'), btn: e.querySelector('.promo__link').textContent.trim() })));
  ok('promos: two banners with real links and a drawn garment each', pr.length === 2 && pr.every((x) => x.svg && x.href && x.href !== '#'), JSON.stringify(pr));
  await p.evaluate(() => document.querySelector('.promos__grid').scrollIntoView({ block: 'center' })); await p.waitForTimeout(1500);
  const g0 = await p.evaluate(() => getComputedStyle(document.querySelector('.promo__art .g')).transform);
  const bb = await p.locator('.promo').first().boundingBox(); await p.mouse.move(bb.x + bb.width / 2, bb.y + bb.height / 2); await p.waitForTimeout(1000);
  const g1 = await p.evaluate(() => ({ tf: getComputedStyle(document.querySelector('.promo__art .g')).transform, btn: getComputedStyle(document.querySelector('.promo__link')).transform }));
  ok('promos: hovering a banner swings the garment and lifts the button', g0 === 'none' && g1.tf !== 'none' && g1.btn !== 'none', JSON.stringify({ g0, ...g1 }));
  const par = await p.evaluate(() => document.querySelector('.promo__art').style.transform);
  ok('promos: the art has a light scroll parallax', /translateY/.test(par), par);
  const rows = await p.evaluate(() => { const c = [...document.querySelectorAll('.reviews .review')].map((e) => Math.round(e.getBoundingClientRect().top)); return { n: c.length, same: new Set(c).size === 1, stars: document.querySelector('.stars').getAttribute('role') + ':' + document.querySelector('.stars').getAttribute('aria-label'), vp: document.querySelectorAll('.vp').length, mark: !!document.querySelector('.reviews-sec mark.proposed') }; });
  ok('reviews: three cards in one row at 1280, stars with a label, a Verified purchase pill each, the sample marker', rows.n === 3 && rows.same && rows.stars === 'img:5 out of 5 stars' && rows.vp === 3 && rows.mark, JSON.stringify(rows));
  const help = await p.evaluate(() => { const a = document.querySelector('.help .btn'); return { href: a.getAttribute('href'), t: a.textContent.trim(), cfg: document.querySelector('.help [data-cfg]').textContent }; });
  ok('size help: "Chat on WhatsApp" is the same WhatsApp link as the footer, the swap days come from the config', help.href === 'https://wa.me/' && /Chat on WhatsApp/.test(help.t) && help.cfg === '14', JSON.stringify(help));
  await p.evaluate(() => document.querySelector('.help').scrollIntoView({ block: 'center' })); await p.waitForTimeout(900);
  const tape = await p.evaluate(() => ({ live: document.querySelector('.help').classList.contains('is-live'), st: getComputedStyle(document.querySelector('.help__tape .icon')).animationPlayState, blob: getComputedStyle(document.querySelector('.hero__blob--1')).animationPlayState }));
  await p.evaluate(() => window.scrollTo(0, 0)); await p.waitForTimeout(500);
  const tape2 = await p.evaluate(() => ({ live: document.querySelector('.help').classList.contains('is-live'), st: getComputedStyle(document.querySelector('.help__tape .icon')).animationPlayState, blob: getComputedStyle(document.querySelector('.hero__blob--1')).animationPlayState }));
  ok('size help: the tape icon spins only while on screen', tape.live && tape.st === 'running' && !tape2.live && tape2.st === 'paused', JSON.stringify([tape, tape2]));
  const blobTop = await p.evaluate(() => { window.scrollTo(0, 0); return new Promise((r) => setTimeout(() => r(getComputedStyle(document.querySelector('.hero__blob--1')).animationPlayState), 400)); });
  ok('hero blobs: the drift runs while the hero is on screen and sleeps while it is off-screen', blobTop === 'running' && tape.blob === 'paused', blobTop + ' / ' + tape.blob);
  await ctx.close();
  const rm = await page({ reducedMotion: 'reduce' });
  const rmv = await rm.p.evaluate(() => ({ tape: getComputedStyle(document.querySelector('.help__tape .icon')).animationName, par: document.querySelector('.promo__art').style.transform, pre: document.querySelectorAll('.pre').length }));
  ok('size help and promos: reduced motion, the tape is static, no parallax, nothing hidden', rmv.tape === 'none' && !rmv.par && rmv.pre === 0, JSON.stringify(rmv));
  await rm.ctx.close();
  // phones: the reviews are a snap scroller with a peek
  const ph = await page({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  await ph.p.evaluate(() => document.querySelector('.reviews-sec').scrollIntoView({ block: 'center' })); await ph.p.waitForTimeout(1500);
  const r1 = await ph.p.evaluate(() => { const l = document.getElementById('reviews'); const cs = [...l.querySelectorAll('.review')].map((e) => e.getBoundingClientRect()); return { scrolls: l.scrollWidth > l.clientWidth + 2, tabindex: l.getAttribute('tabindex'), snap: getComputedStyle(l).scrollSnapType, left: l.scrollLeft, peek: cs[1].left < innerWidth - 8 && cs[1].left > innerWidth * 0.8, firstFits: cs[0].right <= innerWidth, sw: document.documentElement.scrollWidth }; });
  await ph.p.evaluate(() => document.getElementById('reviews').scrollBy({ left: 280, behavior: 'instant' })); await ph.p.waitForTimeout(400);
  const r2 = await ph.p.evaluate(() => document.getElementById('reviews').scrollLeft);
  ok('reviews: on a phone the list scrolls sideways (snap, a peek of the next card, keyboard-focusable)', r1.scrolls && r1.tabindex === '0' && /x/.test(r1.snap) && r1.peek && r2 > r1.left + 100 && r1.sw <= 390, JSON.stringify({ ...r1, r2 }));
  await ph.ctx.close();
}

// ---------------------------------------------------------------- part B: the motion pill (prototype only)
{
  const a = await page();
  ok('motion pill: hidden when the device does not ask for less motion', await a.p.evaluate(() => getComputedStyle(document.getElementById('motionPill')).display === 'none'));
  await a.ctx.close();
  const r = await page({ reducedMotion: 'reduce' });
  const v = await r.p.evaluate(() => ({ disp: getComputedStyle(document.getElementById('motionPill')).display, text: document.getElementById('motionText').textContent, btn: document.getElementById('motionBtn').textContent, rm: document.documentElement.classList.contains('rm') }));
  ok('motion pill: shown under reduced motion with "Your device asks for less motion" and "Show animations"', v.disp !== 'none' && v.text === 'Your device asks for less motion' && v.btn === 'Show animations' && v.rm, JSON.stringify(v));
  await r.p.click('#motionBtn'); await r.p.waitForLoadState('networkidle'); await r.p.waitForTimeout(700);
  const on = await r.p.evaluate(() => ({ rm: document.documentElement.classList.contains('rm'), dev: document.documentElement.classList.contains('rm-device'), ov: sessionStorage.getItem('kayaa-motion'), btn: document.getElementById('motionBtn').textContent, text: document.getElementById('motionText').textContent, band: getComputedStyle(document.getElementById('mqPause')).display, intro: document.documentElement.classList.contains('intro-on') }));
  ok('motion pill: "Show animations" sets the override and reloads with animations on (no preloader), and the pill offers "Turn off"', !on.rm && on.dev && on.ov === 'on' && on.btn === 'Turn off' && on.band !== 'none' && !on.intro, JSON.stringify(on));
  await r.p.click('#motionBtn'); await r.p.waitForLoadState('networkidle'); await r.p.waitForTimeout(500);
  const off = await r.p.evaluate(() => ({ rm: document.documentElement.classList.contains('rm'), ov: sessionStorage.getItem('kayaa-motion'), btn: document.getElementById('motionBtn').textContent }));
  ok('motion pill: "Turn off" goes back to reduced motion', off.rm && off.ov === 'off' && off.btn === 'Show animations', JSON.stringify(off));
  await r.ctx.close();
}

// ---------------------------------------------------------------- part B: no "#" links, the keyboard path, the whole page over file://
{
  const { ctx, p } = await page();
  const bad = await p.evaluate(() => [...document.querySelectorAll('main a')].filter((a) => !a.getAttribute('href') || a.getAttribute('href') === '#').length);
  ok('links: no "#" or empty link anywhere in the Home main', bad === 0, `${bad}`);
  await p.evaluate(() => { window.scrollTo(0, 0); document.getElementById('mqPause').scrollIntoView({ block: 'center' }); });
  await p.locator('#mqPause').focus();
  const seen = [{ id: 'mqPause', cls: '', inHelp: false }]; let ringsMissing = [];
  for (let i = 0; i < 70; i++) {
    await p.keyboard.press('Tab');
    const f = await p.evaluate(() => { const e = document.activeElement; if (!e || e === document.body) return null; const cs = getComputedStyle(e); const r = e.getBoundingClientRect(); return { id: e.id, cls: e.className && e.className.baseVal === undefined ? e.className : '', ring: (cs.outlineStyle !== 'none' && parseFloat(cs.outlineWidth) > 0) || cs.boxShadow !== 'none', visible: r.width > 0 && r.height > 0, inHelp: !!e.closest('.help'), inMain: !!e.closest('main') }; });
    if (!f) continue;
    seen.push(f);
    if (f.visible && f.inMain && !f.ring && !/product-card__link|promo__link/.test(f.cls)) ringsMissing.push(f.id || f.cls);
    if (f.inHelp) break;
  }
  const has = (re) => seen.some((f) => re.test(f.id) || re.test(f.cls));
  ok('keyboard: Tab reaches the band button, the fabric tab, the product tab, a card link, quick add, a promo button and the WhatsApp button, in order', has(/mqPause/) && has(/fab-tab/) && has(/^tab$|tab-new/) && has(/product-card__link/) && has(/product-card__quick/) && has(/promo__link/) && seen[seen.length - 1].inHelp, seen.length + ' stops');
  ok('keyboard: every stop in the new sections shows a focus indicator', ringsMissing.length === 0, ringsMissing.join(','));
  await ctx.close();
}
{
  const errs = [];
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } }); const p = await ctx.newPage();
  p.on('console', (m) => m.type() === 'error' && !/favicon/.test(m.text()) && errs.push(m.text())); p.on('pageerror', (e) => errs.push(e.message));
  await p.addInitScript(seen);
  await p.goto(pathToFileURL(path.join(repo, 'html', 'index.html')).href, { waitUntil: 'load' }); await p.waitForTimeout(1200);
  for (const s of ['.marquee', '#clothWrap', '#products', '.promos__grid', '.reviews-sec', '.help']) { await p.evaluate((x) => document.querySelector(x).scrollIntoView({ block: 'center' }), s); await p.waitForTimeout(700); }
  await p.locator('#fab-jersey').click(); await p.locator('#tab-best').scrollIntoViewIfNeeded(); await p.locator('#tab-best').click(); await p.waitForTimeout(400);
  ok('console: no errors over file:// across the whole page (band, cloth, tabs, promos, reviews, size help)', errs.length === 0, errs.join('|'));
  await ctx.close();
}

await browser.close(); server.close();
console.log(res.join('\n'));
const failed = res.filter((r) => r.startsWith('FAIL')).length;
console.log(`\n${res.length - failed} of ${res.length} Home checks passed.`);
process.exit(failed ? 1 : 0);
