# QA report (prompt 14)

Date of the run: 4 October 2026. Site state: prototype with all of prompts 1 to 13 plus the fixes below. Nothing was committed or pushed by this work.

## 1. Tools and versions

| Tool | Version | Used for |
|---|---|---|
| Node | 24.14.0 | everything |
| Playwright | 1.63.0 | browser control |
| Chromium (Playwright build 1243) | | axe, keyboard, modes, hygiene, design audit, engine test |
| Firefox (Playwright build 1543) | | engine test |
| WebKit (Playwright build 2359, **Windows build**) | | engine test (a stand-in for iOS Safari, not the same) |
| axe-core | 4.13.0 | accessibility rules |
| Lighthouse | 13.5.0 (Chrome 154 headless, simulated slow 4G: 150 ms RTT, 1.6 Mbps, 4x CPU slowdown) | performance, accessibility, best practices, SEO |
| html-validate | 11.16.2 | HTML validity |
| `tools/check-*.mjs` | in the repo, zero dependencies | release gate |

Every test tool lives in a temp folder outside the repo (`%TEMP%\kayaa-qa`); nothing was added to the repo's dependencies. The Playwright browsers are in the user cache (`ms-playwright`). All install steps succeeded: Chromium, Firefox and WebKit all downloaded. The audit scripts are in `tools/qa/` with a README.

The audits ran over http against a local Pages-like server (`tools/qa/serve.mjs`: `html/` under `/kayaa/`, `404.html` fallback with status 404, gzip, a 10 minute cache). The file:// case was covered by the earlier prompts' checks and is not repeated here.

## 2. What was covered

- **Pages:** all 35 pages under `html/` (the prototype-only `proto-onepay.html` is included in the automated runs and is the one page with accepted landmark gaps).
- **States (59 pages and states in axe, 58 in the static checks):** header drawer, cart drawer with items, quick-add sheet, mega menu (desktop only), cancel dialog, checkout with the error summary, checkout with the second payment option on (card selected and cash selected), product with `?demo=oos`, `low-stock`, `regular`, `new`, `no-reviews`, `reviewed`, `review-error`, `review-success`, `review-throttle`, thank-you in all six states (paid, cod, pending, failed, cancelled, expired), the 404 page and the other error pages, and a track result for a cash order.
- **Viewports:** 375 and 1280 for axe and the static checks; 320, 375, 640 (200% zoom), 1280 for the layout modes; 390x844 touch and 1280x800 for the three-engine test; Lighthouse mobile and desktop.
- **Earlier in the session** (still valid, not repeated): the mobile definition of done at 320, 360, 375, 390, 412, 430, 667x375, 768, 820, 1024, 1280 and 1920 on the new and changed pages, and the cash-payment flow test.

## 3. Results (final run, after the last edit)

| Audit | Result |
|---|---|
| `sync-shell --check` | 0 of 35 pages out of date, exit 0 |
| `check-css` | 21 CSS files, 35 pages: 0 errors, 0 warnings |
| `check-links` | 3,340 links, 0 problems |
| `check-seo` | 32 pages: 0 errors, 0 warnings |
| `check-copy` | 147 files, 0 problems |
| `check-budget` | 35 pages, 0 over budget; largest CSS 23.0 KB of 70, JS 27.0 KB of 60, fonts 53.3 KB of 120 (gzip) |
| `list-todos` | docs/content 53 to confirm and 12 proposed; rendered pages 53 and 12 |
| **axe-core** (wcag2a, wcag2aa, wcag21a, wcag21aa, wcag22aa, best-practice) | **0 violations** in 59 pages and states at 375 and 1280 |
| Static checks (ids, aria references, anchors, headings, names, landmarks, lang, target=_blank, tabindex) | 3 problems, all on `proto-onepay.html` (no banner, navigation or footer: accepted, a prototype stand-in) |
| Keyboard | 112 of 118 pass; the 6 "failures" are the review list for visual-order jumps (fixed bars come last in the document; the checkout summary sits at the side): accepted, see known issues #12. Every focus indicator, skip link, focus trap, Esc and focus return check passes |
| Layout modes | 108 of 117 pass; the 9 failures are all the same thing, desktop with the text alone doubled at 1280px (known issue #10). 320px, 200% zoom, 400% zoom, text size 200% at 375px, text spacing, reduced motion, forced colours and touch all pass |
| Three engines | **96 of 96 checks pass** in Chromium, Firefox and WebKit, at both viewports; the geometry of the growth steps is identical in all three |
| html-validate | 0 messages across 35 pages (two rules switched off, with reasons) |
| Hygiene | 0 console errors or warnings, 0 failed requests, the missing-page URL returns 404; no mixed-case or oversized files |

### Lighthouse (mobile and desktop, final run after the last edit)

| Page | Mobile perf | Mobile a11y / BP / SEO | LCP | CLS | TBT | Desktop perf |
|---|---:|---|---:|---:|---:|---:|
| Home | 95 | 100 / 100 / 63 | 1.97 s | 0 | 219 ms | 100 |
| Shop | 99 | 100 / 100 / 63 | 1.68 s | 0 | 109 ms | 100 |
| Category | 99 | 100 / 100 / 63 | 1.68 s | 0 | 109 ms | 100 |
| Product | 99 | 100 / 100 / 63 | 1.83 s | 0 | 104 ms | 100 |
| Cart | 100 | 100 / 100 / 63 | 1.68 s | 0 | 17 ms | 100 |
| Checkout | 99 | 100 / 100 / 63 | 1.68 s | 0 | 83 ms | 100 |
| Thank-you | 100 | 100 / 100 / 63 | 1.68 s | 0 | 0 ms | 100 |
| Delivery | 98 | 100 / 100 / 63 | 1.68 s | 0 | 59 ms | 100 |
| Privacy | 100 | 100 / 100 / 63 | 1.68 s | 0 | 59 ms | 100 |

Scores move a few points from run to run on the same machine (the home page was 93 to 95 across runs).

Targets (mobile): Performance 90+ met on every page; Accessibility 100 met; Best Practices 95+ met (100); SEO 100: the only failing audit on every page is "Page is blocked from indexing", caused by the staging noindex (SEO shows 63); LCP under 2.5 s met; CLS 0 met; **TBT under 200 ms met everywhere except the home page (219 ms)**, see known issue #16.

Before the performance work the mobile numbers were: Home 68, LCP 3.5 s, TBT 666 ms; Product 74, TBT 473 ms; Delivery 77, TBT 452 ms.

## 4. What was fixed

**Small fixes (goal 0)**
- Cash order badge by state (pending: outline with the banknote; paid: the lilac check treatment), see CLAUDE.md. New sample order `KY-260930-C0D2` (delivered and paid) shown on track, orders list and order detail.
- No marker or TODO in any title, meta or JSON-LD: the napkins meta description uses its fallback ("Baby napkins from Kayaa. Island-wide delivery across Sri Lanka with secure checkout."); the visible page and the decisions list keep the TODO. `check-seo` fails when a marker reaches the head or the runtime titles and descriptions.
- Checkout "Edit bag" link is a 44px target.
- Account chip nav: right-edge fade, scroll snap, the active chip is scrolled into view on load.
- Light only: `color-scheme` meta and CSS, `theme-color` `#FAF7FB`, in the shared head.

**Accessibility**
- 526 contrast failures: `--ink-soft` and `--error` darkened to pass AA (needs client confirmation).
- Product gallery: invalid list and group nesting replaced with a labelled, keyboard-scrollable group (`aria-required-children` critical, `scrollable-region-focusable`, `aria-allowed-role`).
- Unique landmark names (search forms, "Filter by size", "Browse categories", footer navigation on the minimal pages).
- While a drawer or sheet is open the page behind is `inert` (it was only trapped by a key handler).
- `<main tabindex="-1">` so the skip link really moves focus.
- Selected states, dots, switches, radio marks and mask icons stay visible in forced colours.
- Text at 200%: announcement bar and sticky total wrap instead of being cut off; footer columns no longer overlap; growth-step tiles grow instead of clipping.
- Home "Shop by age" tiles: accessible name from visible text (label-in-name).

**Performance**
- A forced layout in the header-height script and in the product gallery removed; the header height now has correct CSS defaults (the old fallback of 117px was 5px short of the real 122px).
- Thousands separators are formatted by hand: the first `toLocaleString` call cost about 25 ms on every page.
- Empty favicon link: no more 404 on every first visit.
- Local test server gzips and caches like GitHub Pages, so the numbers are realistic.

**Markup and hygiene**
- `<section>` for the announcement bar, `<span>` inside the gallery thumbnail buttons, `name="qty[]"`, footer links in a `<nav>`.
- 14 provably dead CSS rules deleted (an older mega-menu tile design, unused helper classes). One deletion removed a selector that was shared with a live rule and briefly broke the shop "Sort by" label; it was seen in a screenshot, restored, and the full audit set was re-run afterwards.
- Design tokens added for the radii and font sizes that were literals; the cream announcement-bar variant removed.

## 5. What remains

See `known-issues.md` for the full list with reasons. In short: desktop text-only zoom to 200% overflows the header; home page TBT 216 ms; no minification, bundling or long cache lifetimes (build and hosting jobs); a few unused-CSS candidates that are intentional variants; the colour token changes need design sign-off.

## 6. What could not be tested

- A real iPhone or Android phone (use `real-device-checklist.md`).
- Screen readers (VoiceOver, TalkBack, NVDA): only automated name, role and focus checks ran.
- Real network and real hosting headers.
- Windows High Contrast on a real machine (emulated forced-colors only).
- WebKit here is the Windows build: its text rendering is thinner than Safari's; layout and behaviour matched the other engines.
- Print output, email, and anything that needs the backend.

## 7. How to repeat this

Follow `tools/qa/README.md` to install the tools in a temp folder, then run the scripts listed there. The release gate and the review-time audits are listed under "Release gate" in `CLAUDE.md`.
