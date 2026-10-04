#!/usr/bin/env node
// Shell check (dev-only, zero dependencies): every page must render exactly the shell its variant in tools/shell-map.json allows.
//   node tools/check-shell.mjs        exit 1 on any deviation (sync-shell runs it too, so sync --check fails on a deviation)
// Per page: the marker pairs (<!-- partial: NAME -->) are exactly the variant's, in order, nothing extra; and the rendered landmarks agree:
//   full      skip link, <header class="site-header">, one <main>, <footer class="site-footer">, the tab bar, the menu, cart and quick-add layers
//   checkout  skip link, the minimal header and footer; no tab bar, no drawers
//   error     skip link, the minimal header and footer; no tab bar, no drawers
//   none      no header, footer, tab bar or drawers
// Also: every page under html/ is in the map (and every map entry exists), and tools/page-skeleton.html carries the full shell.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const repoDefault = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const walk = (d) => fs.readdirSync(d, { withFileTypes: true }).flatMap((e) => (e.isDirectory() ? (e.name === 'assets' ? [] : walk(path.join(d, e.name))) : e.name.endsWith('.html') ? [path.join(d, e.name)] : []));

export function checkShell(repo = repoDefault) {
  const map = JSON.parse(fs.readFileSync(path.join(repo, 'tools', 'shell-map.json'), 'utf8'));
  const htmlDir = path.join(repo, 'html');
  const problems = [];
  const files = walk(htmlDir).map((f) => path.relative(htmlDir, f).split(path.sep).join('/')).sort();
  for (const f of files) if (!map.pages[f]) problems.push(`${f}: not in tools/shell-map.json (add it with a variant)`);
  for (const f of Object.keys(map.pages)) if (!files.includes(f)) problems.push(`${f}: in tools/shell-map.json but the page does not exist`);

  const inspect = (label, raw, variantName) => {
    const variant = map.variants[variantName];
    if (!variant) { problems.push(`${label}: unknown variant "${variantName}"`); return; }
    const text = raw.replace(/<!--[\s\S]*?-->/g, '');
    const markers = [...raw.matchAll(/<!-- partial: ([\w-]+) -->/g)].map((m) => m[1]);
    if (markers.join(',') !== variant.join(',')) {
      const extra = markers.filter((m) => !variant.includes(m)), missing = variant.filter((m) => !markers.includes(m));
      problems.push(`${label} (${variantName}): marker pairs differ${missing.length ? '; missing ' + missing.join(', ') : ''}${extra.length ? '; not allowed ' + extra.join(', ') : ''}${!missing.length && !extra.length ? '; wrong order' : ''}`);
    }
    const count = (re) => (text.match(re) || []).length;
    const want = (cond, msg) => { if (!cond) problems.push(`${label} (${variantName}): ${msg}`); };
    const withShell = variantName !== 'none';
    const headers = count(/<header class="site-header[^"]*"/g), footers = count(/<footer class="site-footer[^"]*"/g);
    if (withShell) {
      want(count(/class="skip-link"/g) === 1, 'needs exactly one skip link');
      want(count(/<main[\s>]/g) === 1 || label === 'tools/page-skeleton.html', 'needs exactly one <main>');
      want(headers === 1 && footers === 1 || label === 'tools/page-skeleton.html', `needs one header and one footer (found ${headers} and ${footers})`);
    } else {
      want(headers === 0 && footers === 0 && count(/class="tabbar"/g) === 0, 'must have no header, footer or tab bar');
    }
    const minimalHeader = /<header class="site-header site-header--minimal"/.test(text);
    const minimalFooter = /<footer class="site-footer site-footer--minimal"/.test(text);
    if (variantName === 'full') {
      want(!minimalHeader && !minimalFooter, 'renders a minimal header or footer');
      if (label !== 'tools/page-skeleton.html') {
        want(count(/<nav class="tabbar"/g) === 1, 'needs the mobile tab bar');
        for (const layer of ['menu', 'cart', 'quick-add']) want(new RegExp(`data-layer="${layer}"`).test(text), `needs the ${layer} layer`);
      }
    } else if (withShell) {
      want(minimalHeader && minimalFooter, 'needs the minimal header and footer');
      want(count(/class="tabbar"/g) === 0 && !/data-layer="/.test(text), 'must not render the tab bar or drawers');
    } else {
      want(!/data-layer="/.test(text), 'must not render drawers');
    }
  };

  for (const f of files) if (map.pages[f]) inspect(f, fs.readFileSync(path.join(htmlDir, f), 'utf8'), map.pages[f]);
  const skeleton = path.join(repo, 'tools', 'page-skeleton.html');
  if (fs.existsSync(skeleton)) inspect('tools/page-skeleton.html', fs.readFileSync(skeleton, 'utf8'), 'full');   // the skeleton defaults to the full shell
  return problems;
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const problems = checkShell();
  problems.forEach((p) => console.error('ERROR ' + p));
  const map = JSON.parse(fs.readFileSync(path.join(repoDefault, 'tools', 'shell-map.json'), 'utf8'));
  const counts = {};
  Object.values(map.pages).forEach((v) => { counts[v] = (counts[v] || 0) + 1; });
  console.log(`${Object.keys(map.pages).length} pages checked (${Object.entries(counts).map(([k, v]) => `${v} ${k}`).join(', ')}), ${problems.length} problem${problems.length === 1 ? '' : 's'}`);
  process.exit(problems.length ? 1 : 0);
}
