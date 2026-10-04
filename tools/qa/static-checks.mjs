#!/usr/bin/env node
// DOM hygiene checks on every page and key state, after the page scripts ran (Chromium, over http, 375 wide).
//   node tools/qa/static-checks.mjs
// Checks: unique ids; aria-controls / aria-labelledby / aria-describedby / label[for] / in-page #anchors / <use href="#..."> resolve;
// exactly one visible h1 and no skipped heading level; every form control and every link and button has an accessible name;
// landmarks (banner, main, navigation, contentinfo; one main); <html lang>; target=_blank has rel="noopener"; no positive tabindex.
import { load, start, states, open, write } from './lib.mjs';

const { chromium } = load('playwright');

function inPage() {
  const problems = [];
  const P = (rule, msg) => problems.push(rule + ': ' + msg);
  const visible = (el) => { const r = el.getBoundingClientRect(); const cs = getComputedStyle(el); return cs.display !== 'none' && cs.visibility !== 'hidden' && (r.width > 0 || r.height > 0); };
  const desc = (el) => el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + (el.className && typeof el.className === 'string' ? '.' + el.className.split(/\s+/)[0] : '');

  // unique ids
  const seen = new Map();
  document.querySelectorAll('[id]').forEach((el) => seen.set(el.id, (seen.get(el.id) || 0) + 1));
  seen.forEach((n, id) => { if (n > 1) P('duplicate-id', `#${id} x${n}`); });

  // references
  const has = (id) => !!document.getElementById(id);
  document.querySelectorAll('[aria-controls],[aria-labelledby],[aria-describedby],[aria-owns],[aria-activedescendant]').forEach((el) => {
    for (const a of ['aria-controls', 'aria-labelledby', 'aria-describedby', 'aria-owns', 'aria-activedescendant']) {
      const v = el.getAttribute(a);
      if (v) v.split(/\s+/).forEach((id) => { if (!has(id)) P('aria-ref', `${desc(el)} ${a}="${id}" has no target`); });
    }
  });
  document.querySelectorAll('label[for]').forEach((l) => { if (!has(l.getAttribute('for'))) P('label-for', `label for="${l.getAttribute('for')}" has no control`); });
  document.querySelectorAll('a[href^="#"]').forEach((a) => {
    const id = a.getAttribute('href').slice(1);
    if (id && !has(id) && !document.getElementsByName(id).length) P('anchor', `${a.getAttribute('href')} has no target`);
  });
  document.querySelectorAll('use').forEach((u) => { const h = u.getAttribute('href'); if (h && h.startsWith('#') && !has(h.slice(1))) P('sprite', `<use href="${h}"> has no symbol`); });

  // headings
  const hs = [...document.querySelectorAll('h1,h2,h3,h4,h5,h6')].filter(visible);
  const h1 = hs.filter((h) => h.tagName === 'H1');
  if (h1.length !== 1) P('h1', `${h1.length} visible h1`);
  let prev = 0;
  hs.forEach((h) => { const l = +h.tagName[1]; if (prev && l > prev + 1) P('heading-skip', `h${prev} then h${l} "${h.textContent.trim().slice(0, 40)}"`); prev = l; });

  // names
  const textOf = (el) => (el.getAttribute('aria-label') || '').trim() || (el.getAttribute('aria-labelledby') ? el.getAttribute('aria-labelledby').split(/\s+/).map((i) => (document.getElementById(i) || {}).textContent || '').join(' ').trim() : '') || (el.textContent || '').trim() || (el.querySelector('img[alt]') ? el.querySelector('img[alt]').getAttribute('alt').trim() : '') || (el.getAttribute('title') || '').trim();
  document.querySelectorAll('a[href]').forEach((a) => { if (visible(a) && !textOf(a)) P('link-name', desc(a)); });
  document.querySelectorAll('button').forEach((b) => { if (visible(b) && !textOf(b)) P('button-name', desc(b)); });
  document.querySelectorAll('input,select,textarea').forEach((c) => {
    if (c.type === 'hidden' || c.type === 'submit' || c.type === 'button') return;
    if (!visible(c) && !c.classList.contains('visually-hidden')) return;
    const labelled = (c.id && document.querySelector(`label[for="${CSS.escape(c.id)}"]`)) || c.closest('label') || c.getAttribute('aria-label') || c.getAttribute('aria-labelledby') || c.getAttribute('title');
    if (!labelled) P('control-name', desc(c));
    if (!c.name && c.type !== 'search' && !c.closest('[data-demo-nofield]')) P('control-no-name-attr', desc(c));
  });

  // landmarks and document
  if (!document.documentElement.lang) P('lang', 'missing <html lang>');
  if (document.querySelectorAll('main').length !== 1) P('landmark', `${document.querySelectorAll('main').length} <main>`);
  if (!document.querySelector('header,[role=banner]')) P('landmark', 'no banner');
  if (!document.querySelector('nav')) P('landmark', 'no navigation');
  if (!document.querySelector('footer,[role=contentinfo]')) P('landmark', 'no contentinfo');
  document.querySelectorAll('a[target=_blank]').forEach((a) => { if (!/noopener/.test(a.rel)) P('target-blank', desc(a) + ' without rel=noopener'); });
  document.querySelectorAll('[tabindex]').forEach((el) => { if (parseInt(el.getAttribute('tabindex'), 10) > 0) P('tabindex', desc(el)); });
  return problems;
}

const server = await start();
const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 375, height: 812 }, isMobile: true, hasTouch: true });
const page = await ctx.newPage();
const rows = [];
const all = new Map();
for (const st of states()) {
  try {
    if ((await open(page, st, true)) === 'na') continue;
    const p = await page.evaluate(inPage);
    rows.push([st.name, p]);
    p.forEach((x) => { const k = x.split(':')[0]; if (!all.has(k)) all.set(k, []); all.get(k).push(`${st.name} :: ${x.slice(k.length + 2)}`); });
  } catch (e) { rows.push([st.name, ['error: ' + String(e).slice(0, 160)]]); }
}
await browser.close();
server.close();
write('static-checks.json', JSON.stringify(rows, null, 1));
for (const [rule, list] of all) { console.log(`\n${rule} (${list.length})`); [...new Set(list)].slice(0, 12).forEach((l) => console.log('   ' + l.slice(0, 200))); }
console.log(`\n${rows.length} pages and states checked, ${[...all.values()].flat().length} problems.`);
