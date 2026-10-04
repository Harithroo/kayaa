#!/usr/bin/env node
// HTML validity with html-validate (recommended rules) on every page under html/.
//   node tools/qa/validate.mjs [--all]     prints errors grouped by rule; --all also lists every message
import fs from 'node:fs';
import path from 'node:path';
import { load, htmlDir, pages, write } from './lib.mjs';

const { HtmlValidate } = load('html-validate');
const hv = new HtmlValidate({
  extends: ['html-validate:recommended'],
  rules: { 'no-trailing-whitespace': 'off', 'no-inline-style': 'off', 'prefer-native-element': ['error', { exclude: ['list', 'region'] }], 'attribute-boolean-style': 'off', 'void-style': 'off', 'no-implicit-close': 'error', 'wcag/h30': 'off', 'hidden-focusable': 'off', 'no-redundant-role': 'off' }
});
const byRule = new Map();
let total = 0;
for (const page of pages()) {
  const report = await hv.validateString(fs.readFileSync(path.join(htmlDir, page), 'utf8'), page);
  for (const res of report.results) for (const m of res.messages) {
    total++;
    if (!byRule.has(m.ruleId)) byRule.set(m.ruleId, []);
    byRule.get(m.ruleId).push(`${page}:${m.line}:${m.column} ${m.message}`);
  }
}
write('html-validate.json', JSON.stringify([...byRule].map(([k, v]) => ({ rule: k, count: v.length, samples: v.slice(0, 8) })), null, 1));
for (const [rule, list] of [...byRule].sort((a, b) => b[1].length - a[1].length)) {
  console.log(`\n${rule}  (${list.length})`);
  (process.argv.includes('--all') ? list : list.slice(0, 5)).forEach((l) => console.log('   ' + l.slice(0, 200)));
}
console.log(`\n${pages().length} pages, ${total} messages in ${byRule.size} rules.`);
