# E-commerce prototype: technical playbook

Extracted from the Kayaa frontend prototype (branch `frontend`) so the same routine can be repeated in another project. It describes how the repo is built, not what the brand says. Everything here exists in the repo; things that were planned but not built are in section 20. Where a value is an example it is labelled "example from Kayaa" (short). Rules are tagged **[G]** generic (keep) or **[B]** brand or client specific (replace).

Hand this file to a fresh Claude Code session together with the new project's brief. Section 17 is the build order; section 19 is a CLAUDE.md to start from.

---

## 1. Overview

- **Stack [G]:** static HTML, CSS custom properties, vanilla JS. No framework, no Tailwind, no build step, no npm dependencies in the repo. The prototype is meant to be converted page by page to server templates later (Kayaa: Blade), so every page is "shared shell + `<main>`".
- **Runs two ways [G]:** over `file://` (double click) and over any static server. Therefore all asset paths are relative, the SVG icon sprite is inlined into every page (Chrome blocks external `<use>` over `file://`), and fonts are preloaded only when `location.protocol !== 'file:'`. One page (the 404 page, because GitHub Pages serves it at the missing URL's path) may use a root-absolute prefix through `<!-- sync-root: /kayaa/ -->`.
- **Tooling [G]:** dev-only Node scripts in `tools/` (zero dependencies, ES modules, `.mjs`) generate and check the pages. Browser audits in `tools/qa/` need Playwright, axe, Lighthouse and html-validate, which are installed in a temp folder **outside** the repo. `sharp` (images) and `fontkit`/`wawoff2` (wordmark) are also installed outside the repo.
- **Hosting workflow:** `.github/workflows/pages.yml` publishes the `html/` folder to GitHub Pages on every push to the `frontend` branch (`actions/upload-pages-artifact` with `path: html`, then `actions/deploy-pages`; permissions `contents: read, pages: write, id-token: write`; a `pages` concurrency group). `tools/`, `docs/` and `design/` are never published. `.github/workflows/main.yml` is a different job (a backend deploy for the `master` branch: PHP/Composer/npm build, FTP sync, a token-protected migration call). It is backend infrastructure, not part of the frontend routine; ignore it in a new frontend repo.
- **Branch model (example from Kayaa):** the frontend lives on branch `frontend`; the backend, built by another person, is on `master`. The repo `.gitignore` is the backend's Laravel one plus `/tools/image-source/`.
- **Single sources [G]:** every fact that appears on many pages is defined once and generated: partials (shell), `tools/catalogue.json` (categories, sizes, colours, products, tags), `tools/site-config.json` (numbers and switches), `tools/seo.json` (head tags), `docs/content/*.md` (page text), `tools/shell-map.json` (which shell a page gets), `html/assets/icons/sprite.svg` (icons). `node tools/sync-shell.mjs` regenerates everything from them.

---

## 2. Folder map

```
CLAUDE.md                 standing rules for the Claude Code session (section 3, template in section 19)
.github/workflows/        pages.yml (frontend preview), main.yml (backend deploy, not frontend)
design/                   the design-system HTML (source of truth for tokens and components)
docs/                     documents for people (never published)
  backend-contract.md     what the backend must build, from what the prototype shows (section 10)
  components-added.md     every component or token added beyond the design system, per page/round
  image-credits.md        written by process-images from credits.csv
  01-open-decisions.md    copy of docs/content/01-open-decisions.md (the two files are identical today)
  content/                the content pack: page text in markdown + seo-guide + open decisions (section 11)
  qa/                     qa-report.md, known-issues.md, design-audit.md, real-device-checklist.md
  seo/                    robots.production.txt, sitemap.example.xml, launch-checklist.md (never under html/)
html/                     the published prototype
  *.html                  one file per page; account/*.html for the account area; mockups/ for experiments
  assets/css/             tokens, base, components, layout, fonts, footer, suggest + pages/<page>.css
  assets/js/              production JS, proto-*.js (prototype only), generated site-config.js and catalogue-data.js
  assets/icons/sprite.svg the icon source, inlined into pages by sync-shell
  assets/fonts/           self-hosted woff2 + LICENSES.txt
  assets/img/             footer art (svg) and processed WebP (written by process-images)
tools/                    dev-only (never published)
  partials/               the shared shell, one HTML file per region
  page-skeleton.html      the copy-me page with every marker pair
  *.json                  catalogue, site-config, seo, shell-map, content, image-manifest
  *.mjs                   generators and checks (section 6)
  qa/                     browser audits and a Pages-like server (section 6)
  image-source/           originals (git-ignored)
  check-css.allow         allow-list of intentionally unstyled structural classes
```

Important files, one line each:

| File | Purpose |
|---|---|
| `tools/sync-shell.mjs` | The one generator: partials, SEO head, `gen` regions, config hooks, cache hashes, `--check` |
| `tools/catalogue.mjs` | Renders every `<!-- gen: NAME -->` region; validates the catalogue |
| `tools/content.mjs`, `tools/markdown.mjs`, `tools/content.json` | Render `docs/content/*.md` into pages (tiny markdown renderer, markers) |
| `tools/seo.mjs`, `tools/seo.json` | Build the `<head>` block per page |
| `tools/check-shell.mjs` + `shell-map.json` | Verify each page has exactly the shell its variant allows |
| `tools/build-decisions.mjs` | Render the open-decisions table into `html/review-decisions.html` |
| `html/review.html` | Client-facing index of every screen, with notes (prototype only) |
| `html/assets/js/app.js` | Shell behaviour: menus, drawers, layers (focus trap, `inert`), steppers, active nav, money and ETA helpers |
| `html/assets/js/proto-cart.js` | The demo cart (sessionStorage) behind `window.KayaaCart` |
| `html/assets/js/catalogue-data.js`, `site-config.js` | **Generated**; never edit |

---

## 3. CLAUDE.md digest

The repo's `CLAUDE.md` is long (182 lines). Below, every standing rule grouped by topic. Short rules are quoted. **[G]** = keep in a new project, **[B]** = brand/client specific: replace with your own.

### 3.1 Stack, paths, markup
- [G] "Plain HTML + CSS (custom properties) + vanilla JS. No frameworks, no Tailwind, no build step, no npm deps in the repo."
- [G] "Must work via file:// and a static server: use RELATIVE asset paths. No root-absolute URLs", with one declared exception (the 404 page, `sync-root`).
- [G] Mobile-first. [B] Breakpoints 600, 900, 1200 (the CSS also uses 420 and 1100, and em breakpoints in two files; see section 14). [G] At the nav breakpoint (900) the nav switches: below = hamburger drawer + bottom tab bar; above = full header nav.
- [G] "Never use emoji as UI." Icons come from one sprite; each page inlines it once (`<!-- partial: sprite -->`), references are `<use href="#i-name">`.
- [G] Every page = shared shell + `<main>`, shell regions wrapped in identical comments `<!-- partial: header --> ... <!-- /partial: header -->`. "Never hand-edit content between partial markers in a page."
- [G] Pages set `<body data-page="home">` (space-separated tokens allowed); `app.js` marks matching `[data-nav]` links `aria-current="page"`.
- [G] Repeated data is rendered as several static items wrapped in `<!-- loop: NAME --> ... <!-- /loop -->`; conditionals get `<!-- blade: @if ... -->` hints (conversion hints, not executable).
- [G] "No inline styles (except --tone / --ratio custom properties), no inline event handlers. JS hooks use data-* attributes (never styling classes)."
- [G] A drawer's root is a data hook (`[data-cart-drawer]`) and its inner markup is one self-contained panel, so a server response can be swapped into it.
- [G] Product images use one ratio (4:5) with a `.media` placeholder (`data-placeholder`) until real photos exist.
- [G] "Update docs/backend-contract.md whenever a page, form or param changes." "After each page, log new components in docs/components-added.md." "After building a page, add or update its entry in html/review.html."

### 3.2 CSS
- [G] No `@import`, no bundle: the `styles` partial holds direct `<link>` tags (tokens, base, components, layout...), page CSS is a separate `<link>` after it.
- [G] `sync-shell` appends `?v=<first 8 hex of the file's sha1>` to every local `.css`/`.js` reference, so re-run it after editing any CSS or JS.
- [G] Hover styles only inside `@media (hover: hover) and (pointer: fine)`; controls set `touch-action: manipulation`.
- [G] Media reserves space with `aspect-ratio`. `h1/h2` use `text-wrap: balance`.
- [G] Drawers and sheets use `100dvh` with a `100vh` fallback, `overscroll-behavior: contain`, safe-area padding, and lock background scroll without a layout jump (scrollbar width compensated).
- [B] "Light mode only. No green. No pure black. Accent used sparingly. No dark sections, including the footer." (colour palette rules; keep the idea of writing such rules down, replace the content).
- [G] `tools/check-css.allow` lists intentional unstyled structural classes so `check-css` only reports real problems.

### 3.3 JS
- [G] Production behaviour (for example `product.js`, `search-suggest.js`) stays after conversion and must not depend on prototype files.
- [G] Prototype-only JS lives in `assets/js/proto-*.js` and is deleted at conversion (section 9).
- [G] The `scripts` partial loads shared scripts then `app.js`; page scripts follow it (production file first, then the `proto-*` file that fakes the server).
- [G] Config values are read from `window.KAYAA_CONFIG` (generated), never typed in scripts.
- [G] Search matching rules live in one file used by both the suggestions and the results page; rows are built with `textContent`.

### 3.4 Accessibility and mobile definition of done
- [G] Semantic landmarks, one h1 per page, skip link, labelled fields, `aria-expanded`/`aria-controls` on toggles, dialog semantics for drawers and sheets (focus trap, ESC closes, focus returns), `prefers-reduced-motion` honoured.
- [G] Checked at 320, 360, 375, 390, 412, 430, a 667x375 landscape phone, and 768/820 tablets. No horizontal scroll or clipped text; 320 stays usable.
- [G] Tap targets at least 44x44 with at least 8px between neighbours (one documented client-requested exception for menu rows; see `components-added.md`, "Menu round").
- [G] Fixed chrome (announcement + sticky header + sticky toolbar + bottom bar) never covers more than about a third of the portrait viewport; `scroll-padding` top and bottom; sticky toolbars off on landscape phones.
- [G] Inputs, selects, textareas at least 16px (iOS zoom). Body text at least 16px, line-height 1.5 to 1.6; nothing under 14px except 12.5px captions.
- [G] Horizontal scroll rows show a peek of the next item and never trap vertical scrolling.
- [G] Done = checked at 375, 768 and 1280: no horizontal scroll, no console errors, keyboard usable.

### 3.5 Copy and content rules
- [G] "Do not invent policy or product facts." Use plausible placeholder text marked `<!-- TODO: confirm with client -->`; in content pages, visible bracketed placeholders and markers (section 11).
- [G] Never type a number that lives in site config; use `<span data-cfg="key">` (section 4).
- [B] Money format (a currency prefix from config, whole units, thousands separator, one helper), size labels (en dash, lowercase m), UI word choices ("Order number", "Sign in"), order statuses and payment statuses lists, reference format and length rules, positioning sentences ("sizes follow age bands...").
- [B] The payment-wording rule (a phrase allowed in only two places, enforced by `check-copy`). The pattern is [G]: pick any wording you must confine and write a checker.
- [B] Guest/account rules, track-lookup privacy rules, signed thank-you link, "bag emptied at order creation", stock decremented at placement, no promo codes, email flags. Keep the habit of writing every business rule once in CLAUDE.md and in the contract.

### 3.6 Shell variants
- [G] Every page uses the full shell except explicit exceptions listed in `tools/shell-map.json` (`full`, `checkout`, `error`, `none`). `check-shell` fails on any page not in the map or with extra/missing marker pairs. A new page must be added to the map.
- [G] Reduced-exit shell for checkout; wordmark-only header for error pages that cannot depend on the database.

### 3.7 Footer rules (brand look, generic mechanics)
- [B] Light gradient footer, all footer text one dark ink colour (contrast reasons), first stop darker than the page, wave-cut top edge, giant wordmark as generated SVG path, art zone `aria-hidden`.
- [G] Mechanics worth keeping: a contrast script that reads real pixels behind text (`footer-contrast.mjs`), a seam script (`check-footer-seam.mjs`), social links as labelled icon buttons in `<nav aria-label>`, never text-only; `data-footer-from` override via shell-map.

### 3.8 Photography and illustrations
- [G] Originals live in `tools/image-source/` (git-ignored, never published), processed by one script to WebP with `srcset`, budgets, credits file; "Never hand-edit between photo markers." Alt rules: one plain sentence for informative images, `alt=""` for decorative, never "image of". Hero/first gallery image eager + `fetchpriority="high"`, the rest `loading="lazy" decoding="async"`, all with width/height.
- [G] The pipeline deletes only files listed in `tools/image-manifest.json`.
- [G] AI-generated illustrations: 4:3, never cropped, never upscaled, letter-boxed on a tint when the source is more than 3% off the ratio, captions as HTML text never part of the image, alt text kept in the script's `ILLUSTRATIONS` table, creation tool and date recorded.
- [B] Photo palette limits, slot names, client-supplied size chart marked FINAL.

### 3.9 SEO
- [G] One `<!-- partial: seo -->` pair per head; filled from `seo.json`; a new page needs an entry; titles 60 chars or fewer, descriptions 120 to 160, all unique; `staging: true` forces noindex,nofollow everywhere; meta viewport with `viewport-fit=cover` (section 12).

### 3.10 Release gate
- [G] Before every push: sync, sync `--check`, check-css, check-links, check-seo, check-copy, check-shell, check-budget, check-footer-seam, list-todos. Before a client review: the browser audits plus a real-device pass. Before launch: `list-todos --fail-on-open` and the SEO launch checklist. Exact list in section 7.

### 3.11 Process rules
- [G] "Do not commit or push." (the session never commits unless asked). "Do not touch files outside html/, docs/, design/, tools/, CLAUDE.md and .github/workflows/pages.yml."
- [G] When two sessions run in one repo, each prompt names the files it may not touch (used in practice; not written in CLAUDE.md).

---

## 4. Partials and sync

### 4.1 Marker syntax (all verified in the repo)

| Marker | Written by | Meaning |
|---|---|---|
| `<!-- partial: NAME --> ... <!-- /partial: NAME -->` | you place the empty pair; sync fills it | Content from `tools/partials/NAME.html`. `sprite` comes from `html/assets/icons/sprite.svg`; `seo` is built from `seo.json` |
| `{{root}}` (inside partials) | sync | `""` for `html/*.html`, `"../"` for `html/account/*.html` (one `../` per folder), or the `sync-root` override |
| `<!-- sync-root: /x/ -->` | you (one page) | Makes `{{root}}` resolve to that prefix; used only by the 404 page |
| `<!-- gen: NAME args --> ... <!-- /gen: NAME -->` | you place; sync fills | A region generated by `tools/catalogue.mjs` (the closing marker carries the name only). `<!-- photo: ID -->` markers inside keep what `process-images` wrote |
| `<!-- photo: ID --> ... <!-- /photo: ID -->` | process-images | Image markup or the placeholder; never hand-edit |
| `<!-- loop: NAME --> ... <!-- /loop -->` | you | Conversion hint: several static items stand for a server loop |
| `<!-- blade: @if ... -->` | you | Conversion hint for a conditional or generated value |
| `<!-- img: ... -->` | you | Conversion hint for the real image markup when a placeholder is a div |
| `data-cfg="KEY"` | you; sync refreshes the text | Element text replaced by the config value (money keys print with the currency prefix) |
| `data-cfg-attr="attr:KEY ..."` | you; sync sets the attributes | Raw config values as attributes (example: `max:free_shipping_over`) |
| `data-page="a b"` on `<body>` | you | Tokens that highlight `[data-nav]` links (`aria-current="page"`) |
| `data-footer-from="..."` on `<body>` | sync, from `shell-map.json` `footerFrom` | Optional footer first-stop override |
| `data-*` hooks | you | The only way JS finds elements (`data-cart-drawer`, `data-open-layer`, `data-autosubmit`, `data-qty`, `data-quick-add`, `data-search` ...) |

### 4.2 What `sync-shell.mjs` does (read from the script)
1. Loads `site-config.json`, `catalogue.json` (validates), the content pack, `seo.json`; builds the generated files `assets/js/site-config.js` (`window.KAYAA_CONFIG` plus a small `window.KAYAA_SEO`) and `assets/js/catalogue-data.js` (`window.KAYAA_CATALOGUE`).
2. For every `.html` under `html/` (recursively): fills each partial marker pair (unknown partial or missing closing marker = warning), expands `gen` regions (keeping `photo` regions), refreshes `data-cfg` text and `data-cfg-attr` attributes (unknown key = warning), writes `data-footer-from`, rewrites every local `.css`/`.js` `href`/`src` to `?v=<sha1 8>` (missing file = warning), preserves each file's line endings, and warns on any `{{root}}` left outside a partial.
3. Runs `check-shell` (exit 1 on a deviation) and prints `N of M pages updated`.
4. Flags: no flag = write; `--check` = write nothing, list `out of date: html/...` (generated JS files count as pages), exit 1 if anything is stale, any shell problem, or any warning other than "outside". Run it twice (sync, then `--check`).

### 4.3 How to add a page
1. Copy `tools/page-skeleton.html` to `html/<page>.html` (or `html/account/<page>.html`).
2. Replace `{{root}}` outside the markers by hand (`""` or `"../"`).
3. Set `<body data-page="...">`.
4. Add the page to `tools/shell-map.json` (`pages`, with a variant) and to `tools/seo.json` (title, description, path, robots, crumbs).
5. Add a `<link>` to its page CSS after the `styles` partial; write `<main>`.
6. `node tools/sync-shell.mjs`, then the checks. Add the page to `html/review.html`, log new components in `docs/components-added.md`, and update `docs/backend-contract.md`.

### 4.4 How to add a partial
Create `tools/partials/NAME.html` (use `{{root}}` for paths; icons as `<use href="#i-...">`), add `NAME` to each variant list in `tools/shell-map.json` that should carry it (this is also what `check-shell` expects; it may need a landmark rule in `check-shell.mjs`), add an empty marker pair to `page-skeleton.html` and to the pages, run sync.

### 4.5 Minimal worked example
`tools/partials/announcement.html`:
```html
<section class="topbar" data-topbar aria-label="Announcement">
  <p>Delivery in <span data-cfg="delivery_days">2–4</span> days</p>
</section>
```
Page:
```html
<!-- partial: announcement -->
<!-- /partial: announcement -->
<main id="main" tabindex="-1">
  <ul>
    <!-- gen: cat-links -->
    <!-- /gen: cat-links -->
  </ul>
  <!-- loop: promos -->
  <article class="promo">...</article>
  <!-- /loop -->
</main>
```
After `node tools/sync-shell.mjs` the first pair holds the partial, the `gen` pair holds links generated from `catalogue.json` with the right `{{root}}`, and the config span holds the current config value.

### 4.6 Config equivalent
`tools/site-config.json` -> `html/assets/js/site-config.js` -> `window.KAYAA_CONFIG` (Kayaa's global name; use your own). In the server templates both the `data-cfg` text and the global become view variables.

---

## 5. Single-source data (field names and types only)

**`tools/catalogue.json`**
```
_note: string
categories: [{ slug: string, label: string, tone: number, blurb: string }]
tags:       [{ slug: string, label: string }]            // slug ASCII lower-case-hyphen, label sentence case
sizes:      [{ slug: string, label: string, lo: string, hi: string }]   // ordered scale
colours:    { <slug>: <label> }
products:   [{ slug, name, category(slug), tags: [slug] (2 to 4), price: number, was?: number,
               from: sizeSlug, to: sizeSlug, colours: [slug], featured: number, added: number, tone: number,
               new?: number, oos?: number }]
```
Validated when loaded (`loadCatalogue`) and by `checkCoverage`: product size range ordered and known; known category; tag slugs unique and well formed, tag labels sentence case, each product has 2 to 4 distinct known tags, each tag used by at least 2 products; every size has at least 4 products; every category at least 3. The loader derives `sizes` per product from `from`/`to`.

**`tools/site-config.json`** (all keys read by `data-cfg`, `data-cfg-attr` or `window.KAYAA_CONFIG`)
```
shipping_fee: number, free_shipping_over: number, pay_button_label: string, cod_enabled: boolean,
cod_button_label: string, order_emails_enabled: boolean, remember_days: number, low_stock_threshold: number,
delivery_colombo_days: string, delivery_island_days: string, dispatch_cutoff: string, return_window_days: number,
refund_days: number, faulty_report_days: number, support_hours: string, whatsapp_display: string,
currency_prefix: string, popular_searches: [{ label: string, url: string }]
```
(Zone and contact key names are Kayaa's; rename. Money keys are the set `MONEY_KEYS` in `sync-shell.mjs`.) `sync-shell` rejects unknown keys referenced from pages.

**`tools/seo.json`**
```
_note: string, staging: boolean, site: { name, url, locale },
pages: { "<file>.html": { path: string (the server route), robots?: string, og?: "website"|"product",
                          title?, description?, content?: "<name>" (front matter in docs/content), intro?: "<kind>/<slug>",
                          crumbs?: [[label, path], ...], canonical?: false } }
```
Rules in `seo.mjs`: `staging !== false` forces `noindex,nofollow`; no `crumbs` = no BreadcrumbList; `canonical: false` = no canonical tag; titles/descriptions come from the entry, the content-pack front matter (`content`) or the intros file (`intro`).

**`tools/shell-map.json`**
```
_note: string,
variants: { full: [partialNames], checkout: [...], error: [...], none: [...] },   // ordered marker pairs
footerFrom: { "<file>.html": "white" | "secondary" },   // optional
_footerFrom: string,
pages: { "<file>.html": "full" | "checkout" | "error" | "none" }
```

**`tools/content.json`**: `pages: { <name>: { file, label, toc: bool, split?: bool, card?: { after }, ids: { "<heading>": "<id>" }, callouts?: [heading], steps?: { "<heading>": [iconName] }, faqs?: { placement, heading, id } } }`, plus `provinces` (list of [name, ...]) and `colomboZone` (list of slugs) used by the delivery table.

**`tools/image-manifest.json`**: `{ note: string, files: [path] }` (files the pipeline wrote; the only files it may delete).

What validates what: `sync-shell` (catalogue, config keys, partials, shell via `check-shell`), `check-seo` (the result of `seo.json`), `check-links`, `check-css`, `check-copy`, `list-todos` (markers in content), `check-budget`.

---

## 6. Tools

All tools are plain Node ES modules run from the repository root. Dependencies: none, except where noted.

### 6.1 `tools/*.mjs`

| Script | Command | Reads | Writes | Exit code | When |
|---|---|---|---|---|---|
| `sync-shell.mjs` | `node tools/sync-shell.mjs [--check]` | partials, sprite, json files, `docs/content` | every page, `site-config.js`, `catalogue-data.js`, `review-decisions.html` | `--check`: 1 if stale, shell problem or warning | after any edit of a partial, JSON, markdown, CSS or JS |
| `check-shell.mjs` | `node tools/check-shell.mjs` | pages, `shell-map.json` | nothing | 1 on any deviation (also run by sync) | gate |
| `check-css.mjs` | `node tools/check-css.mjs` | CSS, pages, `check-css.allow` | nothing | **always 0** (report only): brace balance, unterminated comments, missing link/url/srcset targets (ERROR); classes used in HTML but defined nowhere (WARNING) | gate, "0 errors, 0 warnings" is the release target |
| `check-links.mjs` | `node tools/check-links.mjs` | every `href`/`src`/`action` in pages | nothing | 1 if a local target is missing; root-absolute allowed only in the `sync-root` page | gate |
| `check-seo.mjs` | `node tools/check-seo.mjs` | pages, `seo.json`, `catalogue-data.js` | nothing | 1 on errors (one title/description/canonical/h1 per page, a marker or the word TODO in head, duplicates, skipped heading level, img without alt/width/height, unresolved internal link, invalid JSON-LD, indexable page without BreadcrumbList). Warnings (not exit): title over 60, description outside 120 to 160, missing Open Graph, wrong staging robots. Skips prototype-only pages | gate |
| `check-copy.mjs` | `node tools/check-copy.mjs` | every text file | nothing | 1 if a banned phrase appears outside an allow-listed path list | gate (the pattern: one confined phrase) |
| `check-budget.mjs` | `node tools/check-budget.mjs` | pages and the CSS/JS/fonts they load | nothing | 1 if a page is over: CSS 70 KB, JS 60 KB, fonts 120 KB (gzip level 9; images not counted) | gate |
| `list-todos.mjs` | `node tools/list-todos.mjs [--fail-on-open]` | `docs/content/*.md`, rendered `<mark class="todo/proposed">`, `catalogue-data.js` | nothing | 1 only with `--fail-on-open` and any open marker | gate (counts) and before launch |
| `build-decisions.mjs` | `node tools/build-decisions.mjs [--check]` | `docs/content/01-open-decisions.md` | the region in `html/review-decisions.html` | `--check` 1 if stale | run by sync |
| `process-images.mjs` | `node tools/process-images.mjs [--source dir]` | `tools/image-source/*`, `credits.csv` | `html/assets/img/*`, `image-manifest.json`, `docs/image-credits.md`, photo regions | warnings on budget misses | when photos or illustrations change; needs `sharp` outside the repo (`KAYAA_SHARP`) |
| `make-wordmark.mjs` | `node tools/make-wordmark.mjs [word]` | the self-hosted variable font | `html/assets/img/footer/wordmark.svg` | stops if the font cannot be read | rarely; needs `fontkit` + `wawoff2` outside the repo (`KAYAA_ASSETS`) |
| `check-footer-seam.mjs` | `node tools/check-footer-seam.mjs [--pages=a.html]` | pages in a browser | nothing | 1 if the footer is not clearly separate (a channel differs by at least 8, at 375 and 1280) | gate; needs the QA temp folder; `KAYAA_ROOT_FONT=125..200` runs it in Firefox with larger text |

Modules, not commands: `catalogue.mjs` (the gen renderer and catalogue validation), `content.mjs` (content pack renderers: pages, FAQs, district table, todo counts, size-guide slider, listing intros), `markdown.mjs` (the markdown subset and markers), `seo.mjs` (head builder; JSON-LD).

### 6.2 `tools/qa/*.mjs` (browser audits, Playwright etc. installed outside the repo)

Setup, outputs and run times are in section 7.3. Every script starts its own Pages-like server (`serve.mjs`, port 3480, `KAYAA_QA_PORT` changes it; `html/` under `/kayaa/` (the prefix matches the GitHub Pages path), 404 fallback with status 404, gzip, 10-minute cache).

| Script | Asserts / produces | Fails when |
|---|---|---|
| `axe.mjs` | axe-core (wcag2a, 2aa, 21a, 21aa, 22aa, best-practice) on every page and key state at 375 and 1280; `out/axe.json` | any violation (serious and critical must be zero) |
| `static-checks.mjs` | unique ids, aria references, anchors, sprite symbols, one visible h1, no skipped heading level, names for controls/links/buttons, landmarks, `lang`, `target=_blank` has `rel`, no positive tabindex | any of those |
| `keyboard.mjs` | tab through 9 flows at 375 and 1280, every layer (drawer, sheet, mega menu, dialog) | skip link not first or not working, no visible focus indicator, a tab cycle that never closes, focus not moving in/staying in/returning, Esc not closing; upward jumps are listed for a human |
| `modes.mjs` | 320px reflow, 200% and 400% zoom, 200% text, WCAG 1.4.12 text spacing, reduced motion, forced colours, touch | horizontal scroll, clipped or overlapping text, animation over 0.01 ms under reduced motion, a control with no border/outline in forced colours, hover-only content |
| `engines.mjs` | Chromium, Firefox, WebKit at 390x844 touch and 1280x800; screenshots; geometry differences | any flow step fails in any engine |
| `validate.mjs` | html-validate recommended rules on every page | any error (documented exceptions in the script) |
| `hygiene.mjs` | console errors/warnings, failed requests, unused CSS rules, file names and sizes | a console message, a failed request, a rule matching nothing and named in no script |
| `design-audit.mjs` | computed colours mapped to tokens; hue checks (green, yellow/orange); font sizes, spacing, radii against the token scales; `out/design-audit.json` | report; read the findings |
| `footer-contrast.mjs` | footer text against the real gradient pixels at 320 to 1920 | anything under 4.5:1 |
| `lighthouse.mjs` | Lighthouse mobile and desktop on 9 pages (`--only home`, `--form mobile`) | report against targets (section 7.2) |
| `serve.mjs` | just the server: `node tools/qa/serve.mjs [port]` | n/a |
| `lib.mjs` | shared helpers: the module loader (`KAYAA_QA_MODULES`), `states()` (every page plus about 20 key states: demo params, open drawers, dialogs), `seedBag`, `launchBrowser`, `settleTall` | n/a |

---

## 7. Release gate

### 7.1 Ordered commands (from `CLAUDE.md`; all from the repository root)

```
node tools/sync-shell.mjs            # then:
node tools/sync-shell.mjs --check    # exit 0
node tools/check-css.mjs             # 0 errors, 0 warnings
node tools/check-links.mjs           # exit 0
node tools/check-seo.mjs             # exit 0, 0 warnings
node tools/check-copy.mjs            # exit 0
node tools/check-shell.mjs           # exit 0
node tools/check-budget.mjs          # exit 0 (CSS 70 KB, JS 60 KB, fonts 120 KB, gzip)
node tools/check-footer-seam.mjs     # exit 0 (needs the QA temp folder)
node tools/list-todos.mjs            # report the counts
```
Before a client review also: `axe`, `static-checks`, `keyboard`, `modes`, `engines`, `validate`, `hygiene`, `design-audit`, `footer-contrast`, `lighthouse` (all in `tools/qa/`), then `docs/qa/real-device-checklist.md` on a real iPhone and a real Android phone, read `docs/qa/known-issues.md`, and record the run in `docs/qa/qa-report.md`. Before launch also: `node tools/list-todos.mjs --fail-on-open` exits 0 and `docs/seo/launch-checklist.md` is done.

### 7.2 Thresholds that exist in the repo
| Item | Threshold |
|---|---|
| Page weight (gzip) | CSS 70 KB, JS 60 KB, fonts 120 KB per page (`check-budget`) |
| Search JS | `search-core.js` + `search-suggest.js` under 8 KB gzip combined (CLAUDE.md rule; not enforced by a script I found) |
| Axe | 0 violations |
| Contrast | 4.5:1 minimum for footer text (`footer-contrast`); two text colour tokens were darkened to pass AA |
| Footer seam | at least 8 on one colour channel |
| Lighthouse (mobile) | Performance 90+, Accessibility 100, Best Practices 95+, SEO 100 (except "blocked from indexing" while staging), LCP under 2.5 s, CLS 0, TBT under 200 ms |
| Widths | pages 320, 360, 375, 390, 412, 430, 667x375 landscape, 768/820; audits at 375 and 1280, modes at 320/640/1280, engines at 390x844 and 1280x800 |
| Browsers | Chromium, Firefox, WebKit (WebKit on Windows is only a proxy for iOS Safari) |
| Images | hero 1200w at most 130 KB; others 800w at most 70 KB; illustrations 40 KB (480w), 60 KB (800w), 100 KB (1200w) |

### 7.3 QA tools install (outside the repo)
```powershell
$qa = Join-Path $env:TEMP 'kayaa-qa'      # use your own folder name; the path is a constant in tools/qa/lib.mjs
New-Item -ItemType Directory -Force $qa | Out-Null
Set-Location $qa
npm init -y
npm i playwright axe-core lighthouse html-validate chrome-launcher
npx playwright install chromium firefox webkit     # about 600 MB, user cache
```
If the folder is elsewhere set `KAYAA_QA_MODULES`; results go to `<folder>/out` (`KAYAA_QA_OUT` overrides). Lighthouse also needs Chrome or Chromium. Typical run times: axe about 4 minutes, keyboard 3, modes 6, engines 4, Lighthouse 12, others under 3. Same pattern for images: a temp folder with `sharp` (`KAYAA_SHARP`) and for the wordmark (`KAYAA_ASSETS`).

---

## 8. Components

`docs/components-added.md` is the log: one table per page or round with columns Item / Where / Notes, plus "Assumptions" bullets. Components in it, with the pattern each follows (brand styling omitted):

| Component | Pattern |
|---|---|
| Buttons, chips, icon buttons, link-arrow | 44px minimum target (small variants 40 on desktop, 44 below the nav breakpoint); `is-*` state classes plus `aria-pressed`/`aria-current`; hover only under the hover media query |
| Size box / swatch as radios | Real radios styled via `:has(input:checked)`; disabled = struck through plus sr-only "(out of stock)" |
| `.progress`, `.qty` stepper | Native `<progress>`; stepper 44px buttons, `data-qty`, optional `data-qty-max` |
| `.price`, `.price--sale`, `.price__was` | Current price + struck-through compare-at; one helper formats money |
| `.media` | Placeholder (`data-placeholder`, `--ratio`, `--tone`, icon) or `<img>` with `object-fit: cover` |
| Layers: `.drawer`, `.overlay`, `.sheet` | One layer system in `app.js`: opener `[data-open-layer]`, focus trap, ESC, focus return, scroll lock without jump, background `inert` |
| Announcement bar | `[data-topbar]`, dismiss collapses (no storage), focus moves to the wordmark |
| Header, primary nav, search, category row, mega menu | Split Shop control (link + chevron button `aria-expanded`/`aria-controls`), hover intent 120/250 ms, ESC returns focus, `inert` while closed |
| Tab bar | Below the nav breakpoint only; active tab via `data-nav`; padding owned by `body:has(.tabbar)` |
| Footer (full, minimal) | Partials; art zone decorative; social icon buttons in a labelled `nav` |
| `.scroller` / growth steps | Scroll-snap rows with a peek; grid from the nav breakpoint |
| Product card | One badge by priority; meta line; touch gets a persistent 44px quick-add button, mouse gets a pill on hover |
| Listing: breadcrumb, header band, chips, toolbar, switch, pagination, empty/no-results | GET form + plain links, works without JS; `data-autosubmit` + `<noscript>` Apply; sticky toolbar below the nav breakpoint; compact pagination under 600 |
| Product page: gallery, sticky add-to-cart, accordion, size table, stars, rating input, review card, alerts | Gallery filtered by `data-colour`; sticky bar via IntersectionObserver; native `<details>` accordions; size table stacks into labelled cards under 420; star input = five real radios; alerts `role="alert"`/`status` |
| Cart: lines, summary, sticky bar, status | Real update/remove forms; line states as captions and `aria-disabled` |
| Checkout: summary `<details>`, payment cards, error summary, busy button | Error summary `role="alert"` with focus and per-field links; `aria-busy` spinner; no double submit |
| `.status` | Icon (CSS mask) + text, never colour alone; one mapping reused everywhere |
| Stepper | `<ol>` with `is-done/is-current/is-upcoming/is-error`, `aria-current="step"`, icon + text, optional date |
| Auth card, `.password-field`, `.checkbox`, dialog (`<dialog>`), verification banner | Show/hide button with `aria-pressed`+`aria-controls`; native dialog opened by `[data-dialog-open]`, closed by `[data-dialog-close]` |
| Account layout, order card | Sidebar from 900, scroll-snap chip nav below |
| Content template: `.content`, `.prose` (72ch), TOC `<details>`/sticky sidebar from 1100, notice, how-steps, help card, print stylesheet (`body[data-print]`) | Generated from markdown |
| Error template | One CSS file for five codes; full shell vs wordmark-only shell by variant; `data-history-back`, `data-reload` |
| Tags and search suggestions | Combobox on every `form[data-search]`: groups, 2-character minimum, 120 ms debounce, abort stale requests, `aria-activedescendant`, `textContent` only |
| Size-guide slider | `.sg` scroll-snap track, prev/next (`aria-disabled` at ends), dots, counter, live region, no autoplay, works stacked without JS |
| Wardrobe carousel mock-up | A prototype-only experiment (own files, shell `none`, noindex) |

---

## 9. JS architecture

| File group | Responsibility |
|---|---|
| `app.js` | Shell behaviour (production): layers, mega menu, search toggle, steppers, active nav, header height measurement, topbar, history-back/reload hooks; also the helpers `Kayaa.formatMoney` and `Kayaa.formatEta` and the cart-drawer rendering (which reads the demo cart in the prototype) |
| `product.js`, `cart.js`, `checkout.js`, `thank-you.js`, `account.js`, `content.js`, `size-guide.js`, `search-suggest.js` | Production page behaviour. Each says in its header that it "stays after the Blade conversion". Pattern: find a data-hook root, return early if absent, never read `proto-*` |
| `search-core.js` | Pure matching functions shared by the suggestions and the results page; in the prototype it is also the spec for the server |
| `proto-*.js` | Prototype only (deleted at conversion): `proto-cart`, `-cart-page`, `-checkout`, `-thankyou`, `-onepay`, `-orders`, `-account`, `-auth`, `-content`, `-listing`, `-product`, `-search`, `-wishlist` |
| `site-config.js`, `catalogue-data.js` | Generated by sync-shell; the server prints its own |

**The `proto-*` convention [G]:** each file starts with "PROTOTYPE ONLY - delete at Blade conversion" and says what the server does instead. Pages load production script first, then the `proto-*` file that fakes the server. `proto-cart.js` exposes `window.KayaaCart` (get, totals, add, update, remove, clear, money, order store, demo session); events `kayaa:cart` and `kayaa:open-cart`.

**Demo switches [G]:** `?demo=<name>` (`cart.html?demo=empty`, `checkout.html?demo=errors`, `product.html?demo=oos` ...) and `?state=<name>` (the order outcome page) force a page into a state without a backend. They are read with `new URLSearchParams(location.search)`. Every state is listed in CLAUDE.md and `backend-contract.md`; `tools/qa/lib.mjs` `states()` visits them. Listing pages are driven by real query params (`size`, `sort`, `sale`, `c`, `q`, `tag`, `page`) so the same URLs work on the server.

**Storage keys [G]:** keys are prefixed and versioned: `kayaa.proto.cart.v1`, `kayaa.proto.wishlist.v1`, `kayaa.proto.order.v1`, `kayaa.proto.session.v1`, `kayaa.proto.cancelled.v1` (all prototype, `sessionStorage`; `proto-cart.js` falls back to memory when storage is blocked), and one production key `kayaa.verifyBannerDismissed` (`sessionStorage`, wrapped in try/catch). Rule of thumb in the repo: `proto.` in the key = deleted at conversion. No `localStorage` use was found.

---

## 10. Backend contract doc

`docs/backend-contract.md` (562 lines) is written "from what the prototype actually contains, so you do not need to open any HTML". Anything not shown is marked **(assumption)** or **TBD**. Structure to copy:

| Section | What it records |
|---|---|
| Header + conventions | Money format, UI wording, statuses, image ratio, staging noindex |
| 1. What the frontend needs from the backend | A numbered list of data/behaviour requests (images per colour, variants JSON, stock threshold, ETA integers, review paging, payment flow, emails flag...). Grows by round |
| 2. Shell (every page) | Announcement data, header/menu/footer data, shell variants, search form, fixed lists (categories, sizes with slugs) |
| 3. Pages | Route map table (prototype file, server route, status built/stub), then 3.x per page: query params table, form field tables (name, type, required, validation, example), data needed, states and wording, rules, prototype-only `?demo=` switches |
| 3.17 Site config | A table of every config key, value and where it is used |
| 4. Cart drawer | The self-contained panel, endpoints table (read, add, change quantity, remove) |
| 4a. SEO needs from the backend | Meta fields, fallbacks, 301s, the robots/canonical matrix, JSON-LD builders, sitemap, robots by environment, launch steps |
| 4b. Decided by product, pending backend confirmation | Product decisions the backend dev confirms or corrects |
| 5. Prototype-only behaviour | Every `proto-*` file and demo param, and the production JS that stays |
| 6. Still open | Numbered questions for the backend developer |

**Typical open-questions pattern:** each question is one numbered paragraph with a bold title and the consequence if unanswered, for example: stock restoration after failed/abandoned payments; how a guest resumes a failed payment; whether a gateway cancel leaves the order resumable; refund timing on cancel; a missing timestamp column; mail configuration before launch; who maintains per-district delivery numbers; what happens to stock when a cash order fails. In this repo they are payment, stock, mail and data-ownership questions; pick the equivalents in your project.

---

## 11. Content workflow

- **Layout:** `docs/content/` holds `00-README.md` (markers, how it is used, positioning, legal note, file list), one markdown file per page (`size-guide`, `delivery`, `returns`, `about`, `privacy-policy`, `terms`, `home`), `faqs.md`, `category-and-age-intros.md`, `seo-guide.md`, and `01-open-decisions.md` (a table the client reads: #, Decision, "Staging site says", Status Required/Confirm).
- **Front matter** (the tiny parser reads `key: value` lines between `---`): at least `route`, `meta_title`, `meta_description`, `robots` (seen in `home.md`); `seo.json` entries point at it with `"content": "<name>"`.
- **Markers** (rendered by `markdown.mjs`): `[[TODO: text]]` is a fact only the client knows -> `<mark class="todo">` labelled "To confirm"; `[[PROPOSED: text]]` is a default for the client to approve -> `<mark class="proposed">` "Proposed"; `{{cfg:key}}` -> `<span data-cfg="key">` from site config; `[[TABLE: ...]]` on its own line becomes a component the page builder fills in. Markers inside backticks are documentation, not open markers.
- **Build:** `tools/content.json` says how each page is built (heading ids to keep, callout sections, icon steps, table of contents, FAQs placement). `gen: content NAME`, `gen: faqs PLACEMENT`, `gen: listing-intro KIND/SLUG`, `gen: district-eta`, `gen: todo-counts`. "Edit the markdown, never the html text."
- **`list-todos`:** counts and lists open markers by page; `--fail-on-open` exits 1 (launch gate). It reads both the markdown and the rendered HTML, so a marker that only appears in rendered text is also found.
- **Review pages (prototype only):** `html/review.html` is the hand-edited screen index (groups, notes, status, an "Experiments" group, the "Content to approve" card from `gen: todo-counts`). `html/review-decisions.html` is generated from `01-open-decisions.md` by `build-decisions.mjs` (run by sync). Both are removed at conversion.
- **Legal pages:** skeletons with a visible placeholder notice; flagged for a lawyer review.

---

## 12. SEO setup

- **Head block:** one `<!-- partial: seo -->` pair per page head, built by `seo.mjs`: title, meta description, canonical (`site.url` + the server route, not the file name), robots, Open Graph (title, description, type, url, site name, locale), Twitter card (`summary`), JSON-LD. `og:image`/`twitter:image` are TODO until a logo exists.
- **`seo.json` fields:** see section 5. `staging: true` forces `noindex,nofollow` on every page and writes the real value in a comment; false at launch activates each page's own `robots`.
- **Indexing matrix (pattern, from the contract section 4a):** home, shop, categories, category pages, size listing pages, product pages (canonical without the colour param), content pages = `index,follow` (self canonical); category + size, any sort/sale/filter combination, tag listings = `noindex,follow` with the clean listing as canonical; page 2+ of a listing = `index,follow`, self canonical; search = `noindex,follow`; wishlist, cart, checkout, order confirmation, track, account and auth = `noindex,nofollow`; error pages = `noindex`, no canonical. The prototype demonstrates the listing rules in `proto-listing.js` and the product canonical in `product.js`.
- **JSON-LD used:** `Organization` on the home page, `BreadcrumbList` wherever a trail exists (must match the visible breadcrumb), `Product` on the product page (name, image, description, `brand`, `offers` with `priceCurrency`, price, availability, URL; `aggregateRating`/`review` only from approved reviews; **read back from the visible markup** so it cannot drift). Everything is escaped (`<` -> `<`).
- **Deliberately not used:** `FAQPage` markup and `WebSite` `SearchAction` (stated in `seo.mjs` and CLAUDE.md; no reason is written down there). I did not find the reasoning in the repo; the usual reason (rich results for FAQPage are restricted, SearchAction is rarely used) is my inference, not a repo fact.
- **Supporting files:** `docs/seo/robots.production.txt` (allow all; disallow cart, checkout, account, track, search, wishlist, orders; Sitemap line; `?tag=` listings deliberately not blocked so their noindex is seen), `sitemap.example.xml` (indexable routes only; the backend generates the real one), `launch-checklist.md` (9 steps), `docs/content/seo-guide.md` (keyword table, titles and descriptions, the matrix, launch steps).
- **Checks:** `check-seo` (section 6), Lighthouse SEO (63 on staging only because of the noindex; known issue 1).

---

## 13. Image pipeline (`tools/process-images.mjs`)

- **Originals:** `tools/image-source/` (git-ignored). Names are slot ids: `hero`, `banner-<name>`, `cat-<slug>`, `product-<colour>-<n>` (colour photo) or `product-<n>` (shared by every colour), any extension, plus `credits.csv` (columns `file, source, photographer, url`, optional `alt`). A missing original keeps the placeholder.
- **Slots and ratios:** hero 4:5 (widths 480/800/1200/1600), promo banners 1:1, categories 4:5, product gallery 4:5 (gallery images at 800/1200, thumbnails at 480), size-guide illustrations 4:3. Crop uses sharp's attention strategy once at the largest size so all widths share the framing; never upscale.
- **Output:** WebP at the slot's widths to `html/assets/img/<dir>/<name>-<width>.webp`, EXIF/GPS stripped; re-encode at lower quality until the slot's budget passes (starting at 78, down to 40 for photos); a warning if the budget cannot be met.
- **Budgets:** hero 130 KB at 1200w; other photos 70 KB at 800w; illustrations 40/60/100 KB at 480/800/1200w.
- **Wiring:** rewrites every `<!-- photo: ID -->` region in the named pages with `<img srcset sizes width height decoding>`, eager + `fetchpriority="high"` only for the hero and first gallery image. Decorative images get `alt=""`; hero and product alts are TODO placeholders unless `credits.csv` has `alt`. The hero preload (`<link rel="preload" as="image">`) is added to the seo head block by `seo.mjs` (through sync-shell) only when a real photo exists in the hero.
- **Manifest rule:** every written file is listed in `tools/image-manifest.json`; the script deletes only files in the manifest, never files placed by hand.
- **Credits:** `docs/image-credits.md` is written from `credits.csv`; licence is inferred from the source name (Unsplash, Pexels, Pixabay) and otherwise marked TODO.
- **AI-generated images:** the three size-guide illustrations were generated with an AI tool; the tool and date are recorded in CLAUDE.md. Rules: 4:3, never cropped (letter-boxed on the tint if more than 3% off), never upscaled, a WebP original is copied byte for byte when a variant has its own width and fits its budget, smaller widths are resized and encoded at quality 88 (floor 72), PNG/JPEG sources at 82, metadata stripped; captions are HTML text; alt text lives in `ILLUSTRATIONS`.
- **State of this repo:** `html/assets/img/` contains only the footer art and the size-guide illustrations; no product, hero or category photos exist yet (placeholders everywhere else).

---

## 14. Accessibility and performance rules that were enforced

| Rule | Where it is enforced |
|---|---|
| Breakpoints (px): 420, 600, 900 (nav), 1100, 1200 in the page CSS; em breakpoints `56.25em`/`56.1875em` (= 900/899 px at normal text) in `footer.css`, `suggest.css` and the matching `matchMedia` in `search-suggest.js`, so larger browser text moves the break; the tab-bar breakpoint stays px | CSS; `check-footer-seam` and `footer-contrast` with `KAYAA_ROOT_FONT` |
| Tap target 44x44, 8px gaps (`--tap`) | tokens; design audit; one documented client-approved exception |
| Focus: `:focus-visible` ring from tokens (`--focus-ring`, `--focus-offset`); skip link first; `<main tabindex="-1">` so the skip link moves focus | base.css; keyboard audit |
| Layers: focus moves in, Tab/Shift+Tab trapped, ESC closes, focus returns, background `inert` while open | app.js; keyboard audit |
| Combobox (search): WAI-ARIA list autocomplete, input keeps focus, `aria-activedescendant`, ArrowUp/Down/Home/End/Enter, Escape closes then clears, 120 ms debounce, 2-character minimum, stale requests aborted, `aria-busy`, rows by `textContent` | search-suggest.js |
| Colour never alone: `.status` icon + text, selected states with a ring/border, errors with text | components.css; forced-colours check |
| Contrast AA: two text tokens darkened; footer text all in one ink colour; measured on pixels | tokens; axe; footer-contrast |
| Reduced motion: movement variables (`--lift-*`, `--shift-*`) zeroed in one place; animations only under `no-preference` | tokens/base; `modes.mjs` |
| Forced colours: icon art hidden/outlined, controls keep a border | CSS; `modes.mjs` |
| Touch: no hover-only content; hover under `(hover: hover) and (pointer: fine)` | CSS; `modes.mjs` |
| Mobile: 16px inputs, safe-area insets, `100dvh`, scroll lock without jump, scroll padding for fixed bars | CSS; app.js |
| Performance: self-hosted variable woff2 (latin subset, `font-display: swap`, size-adjusted fallbacks), two fonts preloaded (not over file://), no third-party requests, `content-visibility: auto` on the footer art, explicit `width/height` and `aspect-ratio` (CLS 0), hand-written thousands separator (avoids a 25 ms `toLocaleString` cold call), no forced layout in header-height script | `check-budget`, Lighthouse, hygiene |
| Budgets | section 7.2 |

---

## 15. Prototype-only items and pre-launch removal checklist

Delete or replace at conversion (names are Kayaa's; the categories are generic):
1. `html/assets/js/proto-*.js` (13 files, section 9) and their `<script>` tags in the `scripts` partial and pages; the `data-demo-cart` attribute on the buy form.
2. `html/proto-onepay.html` (stand-in for a hosted payment page; shell `none`).
3. `html/mockups/` (experiments) plus its `gen` case in `catalogue.mjs`, its `shell-map`/`seo.json` entries and its `review.html` group.
4. `html/review.html` and `html/review-decisions.html` (and `tools/build-decisions.mjs`).
5. `?demo=` and `?state=` handling; sample orders and the sample catalogue (`catalogue-data.js`, `site-config.js` are replaced by the server's data).
6. `search-core.js` is a spec for the server's matching (the contract says delete it; `search-suggest.js` stays).
7. `<!-- sync-root -->` and the root-absolute links of the 404 page (GitHub Pages only).
8. `"staging": true` in `seo.json` -> false; the unconditional noindex rule on the web server; the placeholder canonical host (`example.com`); the empty favicon link `data:,`; missing `og:image`.
9. Open content markers (`list-todos --fail-on-open`), placeholder phone/email/social URLs, official payment marks, brand claims that need evidence.
10. `html/` -> server templates: `{{root}}` -> asset helpers, `data-cfg` and `KAYAA_CONFIG` -> view variables, `gen` regions -> database loops, `loop`/`blade` comments -> real control flow.

---

## 16. Known issues and accepted deviations: things to expect

From `docs/qa/known-issues.md` (19 accepted items, plus "could not be verified"), generalised:

- **Lighthouse "SEO" is low on staging** because staging is noindex on purpose.
- **No minification, bundling or long cache lifetimes** (build/hosting jobs); Lighthouse will flag minify, cache lifetimes, render-blocking CSS and unused CSS on the shared component file.
- **Some html-validate rules are switched off** with reasons in the script (closed layers are hidden-but-focusable to a static check; deliberate `role="list"` on styled lists).
- **Design-file colour tokens fail AA** on some backgrounds; the implemented tokens were darkened; the design source still shows the old values and needs designer sign-off. Expect the same when copying a design system.
- **A prototype stand-in for a third-party page** has no landmarks.
- **Desktop text-only zoom to 200% overflows the header**; browser zoom and phone layouts pass.
- **WebKit on Windows renders thinner text** than iOS Safari; check on a real iPhone.
- **Keyboard audit lists "upward jumps"** for fixed bars and a side summary; the document order is logical, so these are accepted.
- **Forced-colours:** icon-only buttons have no border of their own; verify on a real Windows contrast theme.
- **Home page TBT** can sit slightly over 200 ms on a simulated slow phone because of first layout of a long page.
- **Unused-CSS candidates** (about 44 selectors) are base defaults and state variants the audit cannot trigger.
- **A tile's accessible name is its visible text** (WCAG 2.5.3 label-in-name beats a nicer label).
- **Empty favicon** until a logo exists.
- **Cannot be verified from a desktop:** real phones, screen readers, real network, production hosting headers, print, email.
- **Documentation drift to expect** (found while reading): CLAUDE.md says the catalogue has 26 sample products, `catalogue.json` holds 25; CLAUDE.md describes the `scripts` partial as loading `proto-cart.js` then `app.js`, the partial actually also loads the generated config, the catalogue data, `search-core`, `search-suggest` and `proto-search`; CLAUDE.md states breakpoints "600, 900, 1200" but the CSS also uses 420 and 1100; one `components-added.md` table row has a broken cell (`data-footer-from`); the "Header category row" and auth-page shell notes in older sections are superseded by later ones ("The `header-auth` partial was removed"); `docs/01-open-decisions.md` duplicates `docs/content/01-open-decisions.md`. Keep one source per fact in the new project and re-read CLAUDE.md after each round.

---

## 17. Bootstrap recipe

Ordered. After each stage run the checks named; never move on with a failing one.

1. **Repo + rules.** `git init`, branch `frontend`, `.gitignore` (add `/tools/image-source/`). Write `CLAUDE.md` from section 19. Create empty `docs/`, `docs/content/`, `docs/qa/`, `docs/seo/`, `design/`, `html/`, `tools/`, `tools/partials/`.
2. **Design source.** Put the design-system HTML in `design/`. Extract tokens to `html/assets/css/tokens.css` (colours, type scale, spacing, radii, shadows, z-index, motion, `--tap`, focus ring) and note derived tokens in `docs/components-added.md`.
3. **Base CSS and fonts.** `fonts.css` (self-hosted variable woff2 + `LICENSES.txt`, size-adjusted fallbacks), `base.css` (reset, focus-visible, reduced motion, scroll padding), `components.css`, `layout.css`. No `@import`.
4. **Sprite.** `html/assets/icons/sprite.svg` with `<symbol id="i-...">`.
5. **Partials first.** `tools/partials/styles.html` (links + font preload with the file:// guard), `scripts.html`, `announcement`, `header`, `footer`, `tabbar`, `menu-drawer`, `cart-drawer`, `quick-add`, then the reduced shells (`header-minimal`, `footer-minimal`, `header-error`).
6. **`tools/page-skeleton.html` + `tools/shell-map.json`** (variants and pages).
7. **Write `tools/sync-shell.mjs` and `tools/check-shell.mjs` first** (partials, `{{root}}`, sprite, `?v=` hashes, `--check`). Create `html/index.html` from the skeleton. Run sync, then `--check`.
8. **`tools/check-links.mjs` and `tools/check-css.mjs` (+ `.allow`)** as soon as there are two pages.
9. **`tools/site-config.json` + the `data-cfg` hooks** in sync-shell; generate `site-config.js`.
10. **`tools/catalogue.json` + `tools/catalogue.mjs`** (gen regions: category row, drawer lists, tiles, size chips, product grids), validation rules (counts, tags). Generate `catalogue-data.js`.
11. **`app.js`** (layers, menu, active nav, steppers, money/ETA helpers), then **`proto-cart.js`** (demo cart). Build pages in this order: home, listing (`proto-listing.js`), product (`product.js` + `proto-product.js`), cart, checkout, payment stand-in, thank-you, track, auth + account, content pages, error pages. After each page: review.html entry, components log, contract update, the mobile definition of done at 375/768/1280.
12. **`tools/seo.json` + `tools/seo.mjs`** and **`check-seo.mjs`** (before the second content page). Keep `staging: true`.
13. **Content pack:** `docs/content/*.md`, `markdown.mjs`, `content.mjs`, `content.json`, `list-todos.mjs`, `build-decisions.mjs`, review pages. Move page text into markdown; never edit the generated HTML text.
14. **`check-copy.mjs`** once you have a wording rule; **`check-budget.mjs`** once there is a stylesheet per page.
15. **Image pipeline:** `process-images.mjs`, `credits.csv`, manifest; install `sharp` outside the repo; photo markers in pages.
16. **`docs/backend-contract.md`**: start at step 11 and update with every page; add the open-questions list as they appear.
17. **Search suggestions, tags, size-guide slider** (all optional); each with its own CSS/JS, a size note and a QA state.
18. **Footer art and its two checks** (`footer-contrast`, `check-footer-seam`) if the design has a decorative footer.
19. **`tools/qa/`**: `serve.mjs`, `lib.mjs` (`states()` is the list of pages and key states; keep it current), then `axe`, `static-checks`, `keyboard`, `modes`, `engines`, `validate`, `hygiene`, `design-audit`, `lighthouse`. Install the tools outside the repo. Run them, fix, record in `docs/qa/qa-report.md`, put accepted items in `known-issues.md` with reasons.
20. **`.github/workflows/pages.yml`** publishing `html/`; `docs/seo/` files; `real-device-checklist.md`.
21. **`tools/README.md`** listing every tool; keep it current (it was already stale once; section 16).

---

## 18. Prompt history

**Not in repo.** There is no file with prompt text and no changelog of prompts. The only traces are headings that name a prompt number, which I list as found, without guessing the others:

| Where | Heading | One-line purpose (from the heading's own content) |
|---|---|---|
| `docs/content/00-README.md` | "Prompt 12" | Pages pull their text from the content pack |
| `docs/components-added.md` | "Payment choice, growth steps and the decisions page (prompt 13)" | Optional second payment method, growth-step tiles, decisions page |
| `docs/qa/qa-report.md` | "QA report (prompt 14)" | The full audit run and fixes |
| `docs/components-added.md` | "Size guide slider (prompt 15)" | Three-step size slider and illustration pipeline |
| `docs/components-added.md` | "Tags and search suggestions (prompt 16)" | Product tags and the search combobox |
| `docs/components-added.md`, `docs/qa/design-audit.md` | "Footer redesign (prompt 19)", "Footer: contrast and seam (prompt 19)" | Footer redesign, contrast and seam checks |

`git log` has short commit messages (for example "Footer Update", "Menu updates", "Mockup Creation") but they are not prompt titles. Prompts 1 to 11, 17 and 18 are not identifiable from the repo.

---

## 19. Reusable CLAUDE.md template

Copy to the new repo root and fill every `<PLACEHOLDER>`. Delete rules you do not need; keep the generic ones.

```markdown
# <PROJECT> - frontend prototype (branch `<BRANCH>`)
Static prototype of the <PROJECT> storefront. Backend (<BACKEND STACK>) is on `<BACKEND BRANCH>`, built by <WHO>. Every page here will be converted to <TEMPLATE ENGINE> later.

## Stack and rules
- Plain HTML + CSS (custom properties) + vanilla JS. No frameworks, no Tailwind, no build step, no npm deps in the repo.
- Must work via file:// and a static server: use RELATIVE asset paths. No root-absolute URLs<, with ONE exception: html/404.html carries `<!-- sync-root: <PREFIX> -->`>.
- Source of truth for design: design/<DESIGN FILE>. <PALETTE RULES: light/dark, forbidden hues, accent use, no dark sections>.
- Mobile-first. Breakpoints: <BREAKPOINTS>. At <NAV BREAKPOINT> the nav switches: below = hamburger drawer + <N>-tab bottom bar; above = full header nav.
- Never use emoji as UI. Icons come from assets/icons/sprite.svg; each page inlines the sprite once (<!-- partial: sprite -->) and references icons as <use href="#i-name">. `node tools/sync-shell.mjs` refreshes every page.
- Prototype-only JS lives in assets/js/proto-*.js and is deleted at conversion. Production behaviour stays and must not depend on proto files.
- Scripts: the `scripts` partial loads <LIST> then app.js; page scripts follow it (production first, then the proto-* file that fakes the server).
- CSS: no app.css and no @import. The `styles` partial holds direct <link> tags for tokens, base, components, layout; page CSS is a separate <link> after it. sync-shell sets ?v=<first 8 hex of sha1> on every local .css and .js reference; re-run it after editing any CSS or JS.
- Product images are <RATIO>. Use the .media placeholder with data-placeholder until real photos exist.

## Conversion conventions
- Every page = shared shell + <main>. Shell regions are wrapped in <!-- partial: NAME --> ... <!-- /partial: NAME -->. Never hand-edit between markers: edit tools/partials/*.html and run `node tools/sync-shell.mjs` (`--check` lists out-of-date pages). New pages are copied from tools/page-skeleton.html and added to tools/shell-map.json and tools/seo.json.
- <body data-page="home"> (space-separated tokens allowed); app.js marks matching [data-nav] links aria-current="page". Partials use {{root}} for paths.
- Repeated data: several static items wrapped in <!-- loop: NAME --> ... <!-- /loop -->. Conditionals get <!-- blade: @if ... --> hints.
- No inline styles (except --tone / --ratio), no inline event handlers. JS hooks are data-* attributes.
- Update docs/backend-contract.md whenever a page, form or param changes. Log new components in docs/components-added.md. Update html/review.html after each page.
- <MONEY FORMAT RULE and the one helper that formats it>
- <UI WORDING LIST>
- <STATUS LISTS: order, payment>
- <BUSINESS RULES: guest checkout, stock, cart emptying, emails, promo codes>
- Do not invent policy or product facts. Use plausible placeholder text marked <!-- TODO: confirm with client -->.

## Shell variants
Every page uses the FULL shell except: <VARIANT TABLE: checkout, error, none>. tools/shell-map.json lists every page and its variant; tools/check-shell.mjs verifies it (sync-shell --check runs it).

## Catalogue, site config, SEO (single sources)
- tools/catalogue.json: <CATEGORIES, SIZES, COLOURS, PRODUCTS, TAGS>; sync-shell fills every <!-- gen: NAME --> region and writes assets/js/catalogue-data.js. Never hand-edit between gen markers.
- tools/site-config.json: <KEYS>. Never type these values by hand: use <span data-cfg="key"> and data-cfg-attr="attr:key"; scripts read window.<CONFIG GLOBAL>.
- tools/seo.json: every page has an entry (title <= 60, description 120-160, all unique); "staging": true forces noindex,nofollow.

## Content workflow
- Page text lives in docs/content/*.md. Edit the markdown, never the html. Markers: [[TODO: ...]] (client fact), [[PROPOSED: ...]] (default to approve), {{cfg:key}}, [[TABLE: ...]]. `node tools/list-todos.mjs` lists open markers; --fail-on-open before launch.
- <COPY RULES: positioning, forbidden claims>

## Photography and illustrations
- Originals in tools/image-source/ (git-ignored). `node tools/process-images.mjs` (needs sharp installed OUTSIDE the repo; KAYAA_SHARP-style env var) writes WebP at <WIDTHS>, budgets <BUDGETS>, docs/image-credits.md and the <!-- photo: ID --> regions. Never hand-edit between photo markers. It deletes only files in tools/image-manifest.json.
- Alt: one plain sentence for informative images, alt="" for decorative ones. Hero and first gallery image eager + fetchpriority="high"; others lazy.
- <AI ILLUSTRATION RULES: ratio, no crop, no upscale, captions as HTML, tool and date>

## Accessibility and mobile definition of done
- Semantic landmarks, one h1, skip link, labelled fields, aria-expanded/aria-controls on toggles, dialogs with focus trap, ESC and focus return, prefers-reduced-motion honoured.
- Checked at 320, 360, 375, 390, 412, 430, landscape 667x375, 768/820. No horizontal scroll; tap targets >= 44x44 with >= 8px gaps; fixed chrome <= a third of the portrait viewport; inputs >= 16px; drawers use 100dvh, overscroll-behavior: contain and safe-area padding; hover only inside (hover: hover) and (pointer: fine); media reserves space with aspect-ratio; body text >= 16px; nothing under 14px except 12.5px captions.

## Process
- Do not commit or push unless asked. Do not touch files outside html/, docs/, design/, tools/, CLAUDE.md and .github/workflows/pages.yml.
- Done = checked at 375, 768 and 1280, plus the release gate below.

## Release gate (before every push; all clean)
node tools/sync-shell.mjs && node tools/sync-shell.mjs --check
node tools/check-css.mjs          # 0 errors, 0 warnings
node tools/check-links.mjs
node tools/check-seo.mjs          # 0 warnings
node tools/check-copy.mjs         # <YOUR CONFINED-WORDING RULE>
node tools/check-shell.mjs
node tools/check-budget.mjs       # CSS <N> KB, JS <N> KB, fonts <N> KB gzip
node tools/list-todos.mjs         # report counts
Before a client review: the audits in tools/qa/ (axe 0 violations, static-checks, keyboard, modes, engines, validate, hygiene, design-audit, lighthouse with <TARGETS>), then docs/qa/real-device-checklist.md on a real iPhone and Android phone; record in docs/qa/qa-report.md; read docs/qa/known-issues.md.
Before launch: list-todos --fail-on-open exits 0; docs/seo/launch-checklist.md done.
```

---

## 20. Planned, not built

Only items the repo itself names as not done:
- Real logo/favicon and `og:image`/`twitter:image` (TODO comments in `seo.mjs` and the styles partial).
- Official payment marks and gateway badge, real social URLs, real contact details (TODO comments in partials).
- Per-district delivery data (two zones are seeded).
- Colour dots on product cards (only if the backend supplies colour values).
- Editing reviews and deleting accounts ("not designed"), a 403 error page ("not designed"), empty states for home sections ("not designed").
- Typo tolerance in search ("later, behind the same endpoint").
- Minification, bundling and long cache lifetimes (left to the server's build and hosting).
- Analytics ("none in v1").
- Admin editor for content pages (an open question for the client).
- Real-device and screen-reader passes (checklist exists; no results recorded).
