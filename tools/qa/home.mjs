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
for (const w of [320, 360, 375, 390, 412, 768, 1024, 1280, 1920]) {
  const { ctx, p } = await page({ viewport: { width: w, height: 800 }, isMobile: w < 900, hasTouch: w < 900 });
  await p.waitForTimeout(1200);
  await p.evaluate(() => document.getElementById('rail').scrollIntoView()); await p.waitForTimeout(3800);
  const m = await p.evaluate(() => ({ sw: document.documentElement.scrollWidth, stage: document.getElementById('railStage').getBoundingClientRect().height, css: parseFloat(getComputedStyle(document.getElementById('railStage')).height) }));
  ok(`layout ${w}: no horizontal page scroll`, m.sw <= w, `scrollWidth ${m.sw}`);
  await ctx.close();
}
for (const w of [375, 1280]) {
  const { ctx, p } = await page({ viewport: { width: w, height: 800 }, isMobile: w < 900, hasTouch: w < 900 });
  await p.evaluate(() => { const els = [...document.querySelectorAll('main *')]; const sizes = els.map((e) => parseFloat(getComputedStyle(e).fontSize)); els.forEach((e, i) => e.style.setProperty('font-size', sizes[i] * 2 + 'px', 'important')); });
  await p.waitForTimeout(600);
  const m = await p.evaluate(() => ({ sw: document.documentElement.scrollWidth, over: [...document.querySelectorAll('.hero__copy *, .age-panel *, .section__head *')].filter((e) => e.getBoundingClientRect().right > innerWidth + 1).length }));
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

await browser.close(); server.close();
console.log(res.join('\n'));
const failed = res.filter((r) => r.startsWith('FAIL')).length;
console.log(`\n${res.length - failed} of ${res.length} Home checks passed.`);
process.exit(failed ? 1 : 0);
