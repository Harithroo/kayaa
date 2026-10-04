#!/usr/bin/env node
// Copy checker (dev-only, zero dependencies): the cash payment option may be worded in a few places only.
//   node tools/check-copy.mjs        exit 1 if a banned phrase appears outside the allowed files
// Banned everywhere else (static, marketing, policy, FAQ, meta, SEO and review-index text, and never as a negation):
//   "cash on delivery", "COD", "pay the courier", "pay on delivery", "paying the courier"
// Allowed: the checkout payment step (shown only when the admin setting cod_enabled is on), the order-specific pages (thank-you,
// track, account order and orders list, which word it only for an order whose payment method is cash), the scripts and styles behind
// them, and the developer documents that describe the rule.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const repo = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const BANNED = [/cash on delivery/i, /\bCOD\b/, /pay(ing)? (the )?courier/i, /pay(ing)? on delivery/i];
const TEXT = /\.(html|md|js|mjs|css|json|txt|xml|yml|yaml)$/i;
const SKIP_DIRS = new Set(['node_modules', '.git', 'image-source']);

// repo-relative paths; a trailing slash allows everything below it
const ALLOWED = [
  'html/checkout.html', 'html/thank-you.html', 'html/track.html', 'html/account/order.html', 'html/account/index.html',
  'html/assets/js/', 'html/assets/css/',
  'tools/check-copy.mjs', 'tools/README.md', 'tools/site-config.json',
  'docs/backend-contract.md', 'docs/components-added.md', 'CLAUDE.md',
  // the client decisions list names the switch once (row 18); the review page renders it
  'docs/content/01-open-decisions.md', 'docs/01-open-decisions.md', 'html/review-decisions.html'
];
const allowed = (rel) => ALLOWED.some((a) => (a.endsWith('/') ? rel.startsWith(a) : rel === a));

const walk = (d) => fs.readdirSync(d, { withFileTypes: true }).flatMap((e) => {
  if (e.isDirectory()) return SKIP_DIRS.has(e.name) ? [] : walk(path.join(d, e.name));
  return TEXT.test(e.name) ? [path.join(d, e.name)] : [];
});

const problems = [];
let n = 0;
for (const file of walk(repo)) {
  const rel = path.relative(repo, file).split(path.sep).join('/');
  n++;
  if (allowed(rel)) continue;
  fs.readFileSync(file, 'utf8').split(/\r?\n/).forEach((line, i) => {
    for (const re of BANNED) if (re.test(line)) { problems.push(`${rel}:${i + 1}: "${line.match(re)[0]}"`); break; }
  });
}
problems.forEach((p) => console.error(p));
console.log(`${n} files checked, ${problems.length} problem${problems.length === 1 ? '' : 's'}`);
process.exit(problems.length ? 1 : 0);
