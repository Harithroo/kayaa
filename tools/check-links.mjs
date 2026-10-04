#!/usr/bin/env node
// Link checker (dev-only): every local href, src and action in html/**/*.html must point at a file that exists.
//   node tools/check-links.mjs        exit 1 if anything is missing
// Root-absolute URLs are not allowed, with ONE exception: html/404.html carries <!-- sync-root: /kayaa/ -->
// (GitHub Pages serves it at the missing URL's path), so inside that file "/kayaa/" maps to html/.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const htmlDir = path.join(path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..'), 'html');
const walk = (d) => fs.readdirSync(d, { withFileTypes: true }).flatMap((e) => (e.isDirectory() ? walk(path.join(d, e.name)) : e.name.endsWith('.html') ? [path.join(d, e.name)] : []));
const problems = [];
let n = 0;
for (const file of walk(htmlDir)) {
  const label = path.relative(htmlDir, file).split(path.sep).join('/');
  const raw = fs.readFileSync(file, 'utf8');
  const syncRoot = (raw.match(/<!-- sync-root: (\S+) -->/) || [])[1];
  // comments and inline script bodies (JSON-LD, the font preload) are not links; <script src> tags are kept
  const text = raw.replace(/<!--[\s\S]*?-->/g, '').replace(/<script(?![^>]*\bsrc=)[^>]*>[\s\S]*?<\/script>/g, '');
  for (const m of text.matchAll(/\b(?:href|src|action)="([^"]+)"/g)) {
    const u = m[1];
    if (/^(https?:|mailto:|tel:|#|data:|javascript:)/.test(u)) continue;
    n++;
    const bare = u.split('#')[0].split('?')[0];
    let target;
    if (syncRoot && u.startsWith(syncRoot)) target = path.join(htmlDir, bare.slice(syncRoot.length));
    else if (u.startsWith('/')) { problems.push(`${label}: root-absolute URL ${u}`); continue; }
    else target = path.join(path.dirname(file), bare);
    if (!fs.existsSync(target)) problems.push(`${label}: missing ${u}`);
  }
}
problems.forEach((p) => console.error(p));
console.log(`${n} links checked, ${problems.length} problem${problems.length === 1 ? '' : 's'}`);
process.exit(problems.length ? 1 : 0);
