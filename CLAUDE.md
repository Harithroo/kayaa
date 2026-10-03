# Kayaa - frontend prototype (branch `frontend`)
Static prototype of the Kayaa storefront. Backend (Laravel 12 + Filament 4) is on `master`, built by someone else. Every page here will be converted to Blade later.

## Stack and rules
- Plain HTML + CSS (custom properties) + vanilla JS. No frameworks, no Tailwind, no build step, no npm deps in the repo.
- Must work via file:// and a static server: use RELATIVE asset paths.
- Source of truth for design: design/kayaa-final-design-system.html. Light mode only. No green. No pure black. Accent (Periwinkle Sky) used sparingly. No dark sections, including the footer.
- Mobile-first. Breakpoints: 600, 900, 1200. At 900 the nav switches: below 900 = hamburger drawer + 5-tab bottom bar; 900+ = full header nav + desktop category row.
- Never use emoji as UI. Icons come from assets/icons/sprite.svg. Chrome blocks external <use> over file://, so each page inlines the sprite once (<!-- partial: sprite -->) and references icons as <use href="#i-search">. assets/icons/sprite.svg stays the source; `node tools/sync-shell.mjs` refreshes every page.
- Product images are 4:5. Use the .media placeholder with data-placeholder until real photos exist.

## Conversion-to-Blade conventions
- Every page = shared shell + <main>. Wrap shell regions in identical comments: <!-- partial: header --> ... <!-- /partial: header -->.
- Never hand-edit content between partial markers in a page. Edit tools/partials/*.html, then run `node tools/sync-shell.mjs` (`--check` lists out-of-date pages). New pages are copied from tools/page-skeleton.html and contain only <main> content plus their page CSS. tools/ is dev-only and not published.
- Each page sets <body data-page="home"> (space-separated tokens allowed); app.js marks matching [data-nav] links with aria-current="page". Partials use {{root}} for paths ("" in html/, "../" in html/account/).
- Repeated data: render several static items and wrap them in <!-- loop: products --> ... <!-- /loop -->. Conditionals get <!-- blade: @if sale --> hints.
- No inline styles (except --tone / --ratio custom properties), no inline event handlers. JS hooks use data-* attributes (never styling classes).
- Cart drawer root is [data-cart-drawer]. Its inner markup is a self-contained panel, because the backend's /cart/panel returns HTML that JS swaps into it.
- Money is "Rs 1,490" (whole rupees, thousands separator). Free delivery over Rs 7,500; standard delivery Rs 450.
- Order statuses: pending, confirmed, shipped, delivered, cancelled. Payment statuses: pending, paid, failed, refunded.
- Payment is cash on delivery in practice (PayHere is not live). Never imply card payment works.
- Do not invent policy or product facts (return window, fabric certifications, delivery times). Use plausible placeholder text and mark it <!-- TODO: confirm with client -->.

## Route map (prototype file -> Laravel route)
index.html -> / | shop.html -> /shop | category.html -> /{department}/{category} | product.html -> /products/{slug} | cart.html -> /cart | checkout.html -> /checkout | thank-you.html -> /orders/{ref}/thank-you | track.html -> /track | search.html -> /search?q= | contact.html -> /contact | size-guide.html, delivery.html, returns.html, about.html, privacy.html, terms.html -> same names | account/register.html, login.html, forgot-password.html, reset-password.html, index.html (orders), order.html, reviews.html, profile.html -> /account/...

## Accessibility and SEO
- Semantic landmarks, one h1 per page, skip link, labelled form fields, aria-expanded/aria-controls on toggles, dialog semantics for drawers and sheets (focus trap, ESC closes, focus returns to trigger), prefers-reduced-motion honoured.
- Every page: <title>, meta description, meta viewport with viewport-fit=cover, and <meta name="robots" content="noindex,nofollow"> (staging; removed at Blade conversion).

## Process
- After each page, log new components in docs/components-added.md.
- After building a page, add or update its entry in html/review.html.
- Do not commit or push. Do not touch files outside html/, docs/, design/, tools/, CLAUDE.md and .github/workflows/pages.yml.
- Done = checked at 375px, 768px and 1280px: no horizontal scroll, no console errors, keyboard usable.
