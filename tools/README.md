# tools/

Dev-only scripts, all zero-dependency Node (no `npm install`). Run from the repository root.

| Command | What it does |
|---|---|
| `node tools/sync-shell.mjs` | Regenerates everything that is generated: shared partials, the seo head, `gen` regions (catalogue, content pages, FAQs, intros), config hooks, cache-busting hashes, `html/review-decisions.html`. `--check` exits 1 if anything is out of date (keep it at exit 0). |
| `node tools/check-css.mjs` | CSS hygiene report (unused or undefined tokens and classes). Report only. |
| `node tools/check-links.mjs` | Every local `href`, `src` and `action` in `html/` points at a file that exists. |
| `node tools/check-seo.mjs` | One title, description, canonical and h1 per page; length limits; unique titles; heading order; image alt; BreadcrumbList on indexable pages. Exit 1 on errors. |
| `node tools/check-shell.mjs` | Every page renders the shell its variant allows (`tools/shell-map.json`: full, checkout, error, none): exact marker pairs and rendered landmarks. `sync-shell --check` runs it. |
| `node tools/check-copy.mjs` | Fails if the cash payment option is worded anywhere except the checkout payment step and the order-specific pages (see the payment rule in CLAUDE.md). |
| `node tools/check-budget.mjs` | Page weight: estimated gzip size of the CSS and JS each page loads and its font bytes. Budgets CSS 70 KB, JS 60 KB, fonts 120 KB; exit 1 when over. |
| `node tools/list-todos.mjs` | Open `[[TODO]]` and `[[PROPOSED]]` content markers, grouped by page. `--fail-on-open` exits 1 (the day before launch). |
| `node tools/build-decisions.mjs` | Renders `docs/content/01-open-decisions.md` to `html/review-decisions.html` (sync-shell runs it). |
| `node tools/check-footer-seam.mjs` | Browser check (Playwright from the temp QA folder, see tools/qa/README.md): the 6 pixel rows above the footer against the 6 below its top edge on every full-shell page at 375 and 1280; fails over 6 per channel; prints a table. Fix a page with `footerFrom` in `tools/shell-map.json`, then `sync-shell` (it writes `<body data-footer-from>`). |
| `node tools/make-wordmark.mjs` | Regenerates `html/assets/img/footer/wordmark.svg` (the giant footer "kayaa", an SVG path of Urbanist 800, gradient fill, 14 units below the descender). Dev-only; needs fontkit and wawoff2 installed OUTSIDE the repo: `mkdir %TEMP%kayaa-assets && cd %TEMP%kayaa-assets && npm init -y && npm i fontkit wawoff2` (or set `KAYAA_ASSETS`). It stops if the font cannot be read. |
| `node tools/process-images.mjs` | Turns originals in `tools/image-source/` into the published images (originals are never committed or published). Also the three size guide illustrations (4:3, never cropped or upscaled; a WebP original is copied unchanged when it fits). It deletes only files listed in `tools/image-manifest.json`. |

Browser audits (axe, keyboard, zoom and text size, Lighthouse, three engines, HTML validity, colours) are in `tools/qa/`; see `tools/qa/README.md` for how to install their tools outside the repo.

Data files: `site-config.json` (numbers and switches, including `cod_enabled`), `catalogue.json` (categories, sizes, colours), `seo.json` (per-page SEO), `content.json` (content page options).
