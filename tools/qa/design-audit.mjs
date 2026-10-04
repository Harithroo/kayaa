#!/usr/bin/env node
// Design-system and colour audit (Chromium over http, every page and key state at 375 and 1280).
//   node tools/qa/design-audit.mjs        writes out/design-audit.json and prints the findings
// Collects computed colours (text, background, border, outline, fill, stroke, shadows, ::before and ::after) and maps each to a token in
// tokens.css; flags off-token colours; flags hues that look green (70-170 deg) or yellow, orange or peach (20-65 deg) outside product colour
// swatches and image placeholders; compares font sizes, padding, gaps and radii with the token scales.
import fs from 'node:fs';
import path from 'node:path';
import { load, start, states, open, write, htmlDir } from './lib.mjs';

const { chromium } = load('playwright');
const tokensCss = fs.readFileSync(path.join(htmlDir, 'assets', 'css', 'tokens.css'), 'utf8');
const names = (re) => [...new Set([...tokensCss.matchAll(re)].map((m) => m[1]))];
const COLOUR_TOKENS = [...tokensCss.matchAll(/(--[\w-]+):\s*(#[0-9a-fA-F]{3,8}|rgba?\([^)]*\)|var\(--[\w-]+\))/g)].map((m) => m[1]);
const FS_TOKENS = names(/(--fs-[\w-]+):/g), SPACE_TOKENS = names(/(--space-[\w-]+):/g), RADIUS_TOKENS = names(/(--radius-[\w-]+):/g);

function inPage({ COLOUR_TOKENS, FS_TOKENS, SPACE_TOKENS, RADIUS_TOKENS }) {
  const probe = document.createElement('div');
  probe.style.cssText = 'position:absolute;visibility:hidden';
  document.body.appendChild(probe);
  const resolve = (prop, token) => { probe.style.cssText = `position:absolute;visibility:hidden;${prop}:var(${token})`; return getComputedStyle(probe)[prop === 'color' ? 'color' : prop === 'font-size' ? 'fontSize' : prop === 'padding-left' ? 'paddingLeft' : 'borderTopLeftRadius']; };
  const tokenColour = {};
  COLOUR_TOKENS.forEach((t) => { tokenColour[t] = resolve('color', t); });
  const fsSet = new Set(FS_TOKENS.map((t) => resolve('font-size', t)));
  const spaceSet = new Set(SPACE_TOKENS.map((t) => resolve('padding-left', t)));
  const radiusSet = new Set(RADIUS_TOKENS.map((t) => resolve('border-radius', t)));
  probe.remove();
  const byValue = {};
  Object.entries(tokenColour).forEach(([t, v]) => { (byValue[v] = byValue[v] || []).push(t); });

  const placeholderCtx = (el) => !!el.closest('.dot, .swatch, [class*="dot--"], .media, [data-placeholder], .gallery__slide, .gallery__thumb, .cat-tile, .product-card__media, .order-line__media, .cart-item__media, .promo, .hero__media');
  const sel = (el) => el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + (typeof el.className === 'string' && el.className.trim() ? '.' + el.className.trim().split(/\s+/)[0] : '');
  const colours = [];   // { value, prop, sel, ph }
  const sizes = [], spaces = [], radii = [];
  const push = (value, prop, el, pseudo) => { if (!value || value === 'none' || value === 'transparent' || /rgba\(\s*\d+,\s*\d+,\s*\d+,\s*0\)/.test(value)) return; colours.push({ value, prop, sel: sel(el) + (pseudo || ''), ph: placeholderCtx(el) }); };
  const shadows = (s) => (s && s !== 'none' ? (s.match(/rgba?\([^)]*\)|color\([^)]*\)/g) || []) : []);
  const scan = (el, pseudo) => {
    const cs = getComputedStyle(el, pseudo || null);
    if (pseudo && (cs.content === 'none' || cs.content === 'normal')) return;
    push(cs.color, 'color', el, pseudo);
    push(cs.backgroundColor, 'background', el, pseudo);
    ['Top', 'Right', 'Bottom', 'Left'].forEach((s) => { if (cs['border' + s + 'Style'] !== 'none' && parseFloat(cs['border' + s + 'Width']) > 0) push(cs['border' + s + 'Color'], 'border', el, pseudo); });
    if (cs.outlineStyle !== 'none' && parseFloat(cs.outlineWidth) > 0) push(cs.outlineColor, 'outline', el, pseudo);
    if (el instanceof SVGElement) { push(cs.fill, 'fill', el); push(cs.stroke, 'stroke', el); }
    if (/underline/.test(cs.textDecorationLine)) push(cs.textDecorationColor, 'underline', el, pseudo);
    shadows(cs.boxShadow).forEach((c) => push(c, 'shadow', el, pseudo));
    const fsz = cs.fontSize;
    if (!pseudo && [...el.childNodes].some((n) => n.nodeType === 3 && n.textContent.trim())) sizes.push({ v: fsz, sel: sel(el) });
    if (!pseudo) {
      ['paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft', 'rowGap', 'columnGap'].forEach((p) => { const v = cs[p]; if (v && v !== 'normal' && v !== '0px') spaces.push({ v, p, sel: sel(el) }); });
      ['borderTopLeftRadius', 'borderTopRightRadius', 'borderBottomLeftRadius', 'borderBottomRightRadius'].forEach((p) => { const v = cs[p]; if (v && v !== '0px') radii.push({ v, sel: sel(el) }); });
    }
  };
  document.querySelectorAll('body *').forEach((el) => {
    if (/^(SCRIPT|STYLE|LINK|META|NOSCRIPT)$/i.test(el.tagName)) return;
    const r = el.getBoundingClientRect();
    const cs = getComputedStyle(el);
    if (cs.display === 'none' || (r.width === 0 && r.height === 0 && !(el instanceof SVGElement))) return;
    if (el.closest('.visually-hidden') || el.closest('svg[style*="display:none"]')) return;   // the hidden icon sprite is not drawn
    scan(el); scan(el, '::before'); scan(el, '::after');
  });
  return { byValue, colours, sizes, spaces, radii, fsSet: [...fsSet], spaceSet: [...spaceSet], radiusSet: [...radiusSet] };
}

const toRgb = (s) => { const m = s.match(/rgba?\(\s*([\d.]+)[,\s]+([\d.]+)[,\s]+([\d.]+)(?:[,\s/]+([\d.]+%?))?/); if (m) { let a = m[4] === undefined ? 1 : m[4].endsWith('%') ? parseFloat(m[4]) / 100 : parseFloat(m[4]); return [+m[1], +m[2], +m[3], a]; } const c = s.match(/color\(srgb\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)(?:\s*\/\s*([\d.]+))?/); return c ? [c[1] * 255, c[2] * 255, c[3] * 255, c[4] === undefined ? 1 : +c[4]].map((v, i) => (i < 3 ? Math.round(v) : v)) : null; };
const hsl = ([r, g, b]) => { r /= 255; g /= 255; b /= 255; const mx = Math.max(r, g, b), mn = Math.min(r, g, b); const l = (mx + mn) / 2; const d = mx - mn; let h = 0, s = 0; if (d) { s = d / (1 - Math.abs(2 * l - 1)); h = mx === r ? ((g - b) / d) % 6 : mx === g ? (b - r) / d + 2 : (r - g) / d + 4; h = (h * 60 + 360) % 360; } return { h, s, l }; };
const hex = ([r, g, b]) => '#' + [r, g, b].map((v) => Math.round(v).toString(16).padStart(2, '0')).join('').toUpperCase();

const server = await start();
const browser = await chromium.launch();
const colourUse = new Map();   // normalized value -> { n, tokens, examples:Set, states:Set, ph }
const sizeUse = new Map(), spaceUse = new Map(), radiusUse = new Map();
let tokenMap = {};
const sets = { fs: new Set(), space: new Set(), radius: new Set() };
const add = (map, key, ex, st) => { if (!map.has(key)) map.set(key, { n: 0, ex: new Set(), st: new Set() }); const e = map.get(key); e.n++; if (e.ex.size < 3) e.ex.add(ex); e.st.add(st); };

for (const [vpName, vp] of [['phone', { width: 375, height: 812 }], ['desktop', { width: 1280, height: 800 }]]) {
  const ctx = await browser.newContext({ viewport: vp, isMobile: vpName === 'phone', hasTouch: vpName === 'phone' });
  const page = await ctx.newPage();
  for (const st of states()) {
    try {
      if ((await open(page, st, !/^(404|500|503|419|429)/.test(st.name))) === 'na') continue;
      const r = await page.evaluate(inPage, { COLOUR_TOKENS, FS_TOKENS, SPACE_TOKENS, RADIUS_TOKENS });
      Object.entries(r.byValue).forEach(([v, t]) => { tokenMap[v] = t; });
      r.fsSet.forEach((v) => sets.fs.add(v)); r.spaceSet.forEach((v) => sets.space.add(v)); r.radiusSet.forEach((v) => sets.radius.add(v));
      const where = `${vpName} ${st.name}`;
      for (const c of r.colours) {
        const rgb = toRgb(c.value); if (!rgb) continue;
        const key = rgb[3] < 1 ? `rgba(${rgb[0]}, ${rgb[1]}, ${rgb[2]}, ${+rgb[3].toFixed(2)})` : `rgb(${rgb[0]}, ${rgb[1]}, ${rgb[2]})`;
        if (!colourUse.has(key)) colourUse.set(key, { n: 0, ex: new Set(), st: new Set(), ph: 0, rgb });
        const e = colourUse.get(key); e.n++; if (e.ex.size < 4) e.ex.add(`${c.sel} (${c.prop})`); e.st.add(where); if (c.ph) e.ph++;
      }
      r.sizes.forEach((s) => add(sizeUse, s.v, s.sel, where));
      r.spaces.forEach((s) => add(spaceUse, s.v, `${s.sel} ${s.p}`, where));
      r.radii.forEach((s) => add(radiusUse, s.v, s.sel, where));
    } catch (e) { console.log('ERROR', vpName, st.name, String(e).slice(0, 120)); }
  }
  await ctx.close();
}
await browser.close();
server.close();

const out = { tokens: tokenMap, colours: [], offToken: [], hue: [], sizes: [], spaces: [], radii: [] };
const WHITE = 'rgb(255, 255, 255)';
for (const [key, e] of [...colourUse].sort((a, b) => b[1].n - a[1].n)) {
  let toks = tokenMap[key] || (key === WHITE ? ['(white, = --surface)'] : null);
  if (!toks && e.rgb[3] < 1) {   // a token colour at partial opacity (shadows, scrims, borders)
    const opaque = `rgb(${e.rgb[0]}, ${e.rgb[1]}, ${e.rgb[2]})`;
    if (tokenMap[opaque]) toks = [`${tokenMap[opaque][0]} at ${Math.round(e.rgb[3] * 100)}% opacity`];
  }
  const ex0 = [...e.ex][0] || '';
  if (!toks && /age-tile/.test(ex0)) toks = ['--primary mixed into --tone-2 (color-mix, the growth-step tints)'];
  const h = hsl(e.rgb);
  const row = { value: key, hex: hex(e.rgb), alpha: e.rgb[3], uses: e.n, tokens: toks, examples: [...e.ex], hue: Math.round(h.h), sat: +h.s.toFixed(2), light: +h.l.toFixed(2), placeholderUses: e.ph };
  out.colours.push(row);
  if (!toks) out.offToken.push(row);
  const hueBad = e.rgb[3] >= 0.5 && h.s >= 0.15 && h.l > 0.08 && h.l < 0.97 && ((h.h >= 70 && h.h <= 170) || (h.h >= 20 && h.h <= 65));
  if (hueBad) out.hue.push({ ...row, outsidePlaceholders: e.n - e.ph });
}
const num = (v) => parseFloat(v);
const scale = (use, set, key) => { for (const [v, e] of [...use].sort((a, b) => num(a[0]) - num(b[0]))) if (!set.has(v)) out[key].push({ value: v, uses: e.n, examples: [...e.ex], states: [...e.st].slice(0, 3) }); };
scale(sizeUse, sets.fs, 'sizes'); scale(spaceUse, sets.space, 'spaces'); scale(radiusUse, sets.radius, 'radii');
out.scales = { fontSizes: [...sets.fs], space: [...sets.space], radius: [...sets.radius] };
write('design-audit.json', JSON.stringify(out, null, 1));

console.log(`Distinct colours used: ${out.colours.length}; on a token: ${out.colours.length - out.offToken.length}; off token: ${out.offToken.length}`);
console.log('\nOff-token colours:'); out.offToken.slice(0, 40).forEach((r) => console.log(`  ${r.value} ${r.hex} x${r.uses} hue ${r.hue} sat ${r.sat}  e.g. ${r.examples.slice(0, 2).join('; ')}`));
console.log('\nGreen / yellow / peach hues (hue 70-170 or 20-65, saturation >= 0.15):'); out.hue.forEach((r) => console.log(`  ${r.value} ${r.hex} hue ${r.hue} sat ${r.sat} x${r.uses} (outside placeholders ${r.outsidePlaceholders}) tokens ${r.tokens ? r.tokens.join(',') : '-'}  e.g. ${r.examples.slice(0, 3).join('; ')}`));
for (const k of ['sizes', 'spaces', 'radii']) { console.log(`\nOff-scale ${k}:`); out[k].slice(0, 30).forEach((r) => console.log(`  ${r.value} x${r.uses}  e.g. ${r.examples.slice(0, 2).join('; ')}`)); }
