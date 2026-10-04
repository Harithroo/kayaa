// Tiny markdown renderer for the content pack (docs/content/*.md). Dev-only, zero dependencies.
// Supports what the pack uses: front matter, ## and ### headings, paragraphs (a newline inside a paragraph becomes <br>),
// - and 1. lists, pipe tables, **bold**, [text](link), and these markers:
//   [[TODO: text]]      a fact only Kayaa knows         -> <mark class="todo">   with the label "To confirm"
//   [[PROPOSED: text]]  a default for the client to approve -> <mark class="proposed"> with the label "Proposed"
//   {{cfg:key}}         a value from tools/site-config.json -> <span data-cfg="key">
//   [[TABLE: text]]     (a line of its own) a component the page builder fills in
import fs from 'node:fs';

export function parseFrontMatter(src) {
  const text = src.replace(/\r\n/g, '\n');
  const m = text.match(/^---\n([\s\S]*?)\n---\n?/);
  if (!m) return { front: {}, body: text };
  const front = {};
  for (const line of m[1].split('\n')) {
    const i = line.indexOf(':');
    if (i > 0) front[line.slice(0, i).trim()] = line.slice(i + 1).trim();
  }
  return { front, body: text.slice(m[0].length) };
}

export const escapeHtml = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

export function slugify(text) {
  return String(text).toLowerCase().replace(/<[^>]+>/g, '').replace(/^\d+\.\s*/, '').replace(/&/g, ' and ')
    .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}

const MARKER_RE = /\[\[(TODO|PROPOSED)(?::\s*([^\]]*?))?\]\]/g;

// plain text for a <meta> value or a <title>: markers become "[TODO: ...]" text, cfg keys become their values, no markup
export function plain(text, cfgText) {
  return String(text)
    .replace(MARKER_RE, (m, type, body) => '[' + type + (body ? ': ' + body.trim() : '') + ']')
    .replace(/\{\{cfg:(\w+)\}\}/g, (m, key) => (cfgText ? cfgText(key) : key))
    .replace(/\*\*(.+?)\*\*/g, '$1').replace(/\[([^\]]+)\]\(([^)]+)\)/g, '$1');
}

// links in the pack are written for html/ (privacy.html): pages in a subfolder or at a sync-root need the prefix
const prefixed = (href, root) => (/^(?:[a-z]+:|#|\/|\.\.\/)/i.test(href) ? href : root + href);

export function inline(text, { root = '', cfgText } = {}) {
  const stash = [];
  const keep = (html) => '\u0000' + (stash.push(html) - 1) + '\u0000';
  let s = String(text);
  // markers first (they contain brackets that the link rule would otherwise read)
  s = s.replace(MARKER_RE, (m, type, body) => {
    const kind = type === 'TODO' ? 'todo' : 'proposed';
    const label = type === 'TODO' ? 'To confirm' : 'Proposed';
    return keep(`<mark class="${kind}"><span class="marker-label">${label}</span>${body && body.trim() ? ' ' + escapeHtml(body.trim()) : ''}</mark>`);
  });
  s = s.replace(/\{\{cfg:(\w+)\}\}/g, (m, key) => keep(`<span data-cfg="${key}">${escapeHtml(cfgText ? cfgText(key) : key)}</span>`));
  s = escapeHtml(s);
  s = s.replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, (m, label, href) => `<a href="${prefixed(href.replace(/&amp;/g, '&'), root).replace(/&/g, '&amp;')}">${label}</a>`);
  s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
  s = s.replace(/\u0000(\d+)\u0000/g, (m, i) => stash[+i]);
  return s;
}

export function parseBlocks(body) {
  const lines = body.replace(/\r\n/g, '\n').split('\n');
  const blocks = [];
  let i = 0;
  while (i < lines.length) {
    const line = lines[i];
    if (!line.trim()) { i++; continue; }
    let m;
    if ((m = line.match(/^(#{1,4})\s+(.*)$/))) { blocks.push({ type: 'h' + m[1].length, text: m[2].trim() }); i++; continue; }
    if (/^\[\[TABLE:/.test(line.trim())) { blocks.push({ type: 'component', text: line.trim().replace(/^\[\[TABLE:\s*/, '').replace(/\]\]$/, '') }); i++; continue; }
    if (line.startsWith('|')) {
      const rows = [];
      while (i < lines.length && lines[i].startsWith('|')) { rows.push(lines[i]); i++; }
      const cells = (r) => r.replace(/^\|/, '').replace(/\|\s*$/, '').split('|').map((c) => c.trim());
      blocks.push({ type: 'table', head: cells(rows[0]), rows: rows.slice(2).map(cells) });
      continue;
    }
    if (/^[-*]\s+/.test(line)) {
      const items = [];
      while (i < lines.length && /^[-*]\s+/.test(lines[i])) { items.push(lines[i].replace(/^[-*]\s+/, '').trim()); i++; }
      blocks.push({ type: 'ul', items });
      continue;
    }
    if (/^\d+\.\s+/.test(line)) {
      const items = [];
      while (i < lines.length && /^\d+\.\s+/.test(lines[i])) { items.push(lines[i].replace(/^\d+\.\s+/, '').trim()); i++; }
      blocks.push({ type: 'ol', items });
      continue;
    }
    const para = [];
    while (i < lines.length && lines[i].trim() && !/^(#{1,4}\s|\||[-*]\s+|\d+\.\s+|\[\[TABLE:)/.test(lines[i])) { para.push(lines[i].trim()); i++; }
    blocks.push({ type: 'p', text: para.join('\n') });
  }
  return blocks;
}

export function readMarkdown(file) {
  const { front, body } = parseFrontMatter(fs.readFileSync(file, 'utf8'));
  return { front, blocks: parseBlocks(body) };
}
