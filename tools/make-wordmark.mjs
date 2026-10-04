#!/usr/bin/env node
// Writes html/assets/img/footer/wordmark.svg: the word "kayaa" in Urbanist 800 as an SVG PATH, so the giant footer wordmark never depends on
// font loading and cannot shift the layout. Dev-only; the repo has no dependencies, so fontkit and wawoff2 are installed once OUTSIDE it:
//   mkdir %TEMP%\kayaa-assets && cd %TEMP%\kayaa-assets && npm init -y && npm i fontkit wawoff2
// (or set KAYAA_ASSETS to the folder that contains node_modules/fontkit and node_modules/wawoff2). Then:
//   node tools/make-wordmark.mjs [word]
// wawoff2 unpacks the woff2 to a plain font file first: fontkit cannot make a weight instance of a variable font straight from a woff2.
// The glyph outlines come from the self-hosted variable font html/assets/fonts/urbanist-latin-wght-normal.woff2 at weight 800.
// The viewBox is fitted to the glyph bounds (side bearings trimmed) with 14 units kept below the lowest point so the tail of the "y" is whole.
// The fill is a vertical gradient from white at 98% opacity to white at 55%. If the font cannot be read the script stops (no silent fallback).
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const repo = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const word = process.argv[2] || 'kayaa';
const dir = process.env.KAYAA_ASSETS || path.join(os.tmpdir(), 'kayaa-assets');
let fontkit, woff2;
try {
  const req = createRequire(path.join(dir, 'package.json'));
  fontkit = req('fontkit');
  woff2 = req('wawoff2');
} catch (e) {
  console.error(`fontkit and wawoff2 not found in ${dir}.\nInstall them outside the repo:\n  mkdir "${dir}" && cd "${dir}" && npm init -y && npm i fontkit wawoff2`);
  process.exit(1);
}
const fontFile = path.join(repo, 'html', 'assets', 'fonts', 'urbanist-latin-wght-normal.woff2');
const base = fontkit.create(Buffer.from(await woff2.decompress(fs.readFileSync(fontFile))));
const font = base.getVariation({ wght: 800 });
// the glyph ids come from the default instance (the cmap); outlines and advances from the weight 800 instance
const ids = base.layout(word).glyphs.map((g) => g.id);
const glyphs = ids.map((id) => font.getGlyph(id));

// every glyph outline, moved to its pen position, y flipped (SVG y grows downwards), in font units
// (no kerning pair is applied: the letters of this word have none worth keeping)
const cmds = [];
let pen = 0;
for (const g of glyphs) {
  for (const c of g.path.commands) cmds.push({ c: c.command, a: c.args.map((v, k) => (k % 2 === 0 ? v + pen : -v)) });
  pen += g.advanceWidth;
}
const xs = [], ys = [];
cmds.forEach((c) => c.a.forEach((v, k) => (k % 2 === 0 ? xs : ys).push(v)));
const minX = Math.min(...xs), maxX = Math.max(...xs), minY = Math.min(...ys), maxY = Math.max(...ys);

// scale so the word is 1000 units wide (the viewBox is in these units)
const s = 1000 / (maxX - minX);
const r = (n) => String(Math.round(n * 10) / 10);
const X = (v) => r((v - minX) * s), Y = (v) => r((v - minY) * s);
const d = cmds.map((c) => {
  if (c.c === 'moveTo') return `M${X(c.a[0])} ${Y(c.a[1])}`;
  if (c.c === 'lineTo') return `L${X(c.a[0])} ${Y(c.a[1])}`;
  if (c.c === 'quadraticCurveTo') return `Q${X(c.a[0])} ${Y(c.a[1])} ${X(c.a[2])} ${Y(c.a[3])}`;
  if (c.c === 'bezierCurveTo') return `C${X(c.a[0])} ${Y(c.a[1])} ${X(c.a[2])} ${Y(c.a[3])} ${X(c.a[4])} ${Y(c.a[5])}`;
  if (c.c === 'closePath') return 'Z';
  throw new Error('unknown path command ' + c.c);
}).join('');
const w = 1000, h = Math.round((maxY - minY) * s + 14);   // 14 units of room below the lowest point (the tail of the y)
const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${w} ${h}" width="${w}" height="${h}" aria-hidden="true" focusable="false"><defs><linearGradient id="kg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".98"/><stop offset="1" stop-color="#fff" stop-opacity=".55"/></linearGradient></defs><path fill="url(#kg)" d="${d}"/></svg>\n`;
const out = path.join(repo, 'html', 'assets', 'img', 'footer', 'wordmark.svg');
fs.mkdirSync(path.dirname(out), { recursive: true });
fs.writeFileSync(out, svg);
console.log(`wrote html/assets/img/footer/wordmark.svg: "${word}" ${w}x${h}, ${svg.length} bytes (path generated from Urbanist 800)`);
