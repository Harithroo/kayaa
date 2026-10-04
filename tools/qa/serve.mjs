#!/usr/bin/env node
// Local Pages-like server (dev-only, zero dependencies): serves html/ under /kayaa/ and answers any other path with html/404.html
// and status 404, like GitHub Pages. The browser audits start it themselves; run it by hand to look at the site:
//   node tools/qa/serve.mjs [port]      then open http://localhost:3480/kayaa/
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..', 'html');
const TYPES = { '.html': 'text/html; charset=utf-8', '.css': 'text/css', '.js': 'text/javascript', '.svg': 'image/svg+xml', '.webp': 'image/webp', '.png': 'image/png', '.jpg': 'image/jpeg', '.json': 'application/json', '.woff2': 'font/woff2', '.txt': 'text/plain', '.xml': 'application/xml' };

export function startServer(port = 3480) {
  const server = http.createServer((req, res) => {
    const p = decodeURIComponent(req.url.split('?')[0]);
    let file = null;
    if (p.startsWith('/kayaa/')) {
      let rel = p.slice(7);
      if (!rel || rel.endsWith('/')) rel += 'index.html';
      const f = path.join(root, rel);
      if (f.startsWith(root) && fs.existsSync(f) && fs.statSync(f).isFile()) file = f;
    }
    if (file) {
      // like GitHub Pages: gzip for text, a 10 minute cache
      const type = TYPES[path.extname(file)] || 'application/octet-stream';
      const headers = { 'Content-Type': type, 'Cache-Control': 'max-age=600' };
      if (/text|javascript|json|svg|xml/.test(type) && /gzip/.test(req.headers['accept-encoding'] || '')) {
        headers['Content-Encoding'] = 'gzip';
        res.writeHead(200, headers);
        return fs.createReadStream(file).pipe(zlib.createGzip()).pipe(res);
      }
      res.writeHead(200, headers);
      return fs.createReadStream(file).pipe(res);
    }
    res.writeHead(404, { 'Content-Type': 'text/html; charset=utf-8' });
    fs.createReadStream(path.join(root, '404.html')).pipe(res);
  });
  return new Promise((resolve, reject) => { server.on('error', reject); server.listen(port, () => resolve(server)); });
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const port = parseInt(process.argv[2], 10) || 3480;
  startServer(port).then(() => console.log(`Pages-like server: http://localhost:${port}/kayaa/  (Ctrl+C to stop)`));
}
