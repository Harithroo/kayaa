# Kayaa - frontend prototype (branch `frontend`)
Static prototype of the Kayaa storefront. Backend (Laravel 12 + Filament 4) is on `master`, built by someone else. Every page here will be converted to Blade later.

## Stack and rules
- Plain HTML + CSS (custom properties) + vanilla JS. No frameworks, no Tailwind, no build step, no npm deps in the repo.
- Must work via file:// and a static server: use RELATIVE asset paths. No root-absolute URLs, with ONE exception: html/404.html carries `<!-- sync-root: /kayaa/ -->` (prototype-only: GitHub Pages serves it at the missing URL's path) and uses `/kayaa/...` links; `node tools/check-links.mjs` and check-css accept that file only (404.html therefore looks unstyled over file://: view it through a server).
- Source of truth for design: design/kayaa-final-design-system.html. Light mode only. No green. No pure black. Accent (Periwinkle Sky) used sparingly. No dark sections, including the footer.
- Mobile-first. Breakpoints: 600, 900, 1200. At 900 the nav switches: below 900 = hamburger drawer + 5-tab bottom bar; 900+ = full header nav + desktop category row.
- Never use emoji as UI. Icons come from assets/icons/sprite.svg. Chrome blocks external <use> over file://, so each page inlines the sprite once (<!-- partial: sprite -->) and references icons as <use href="#i-search">. assets/icons/sprite.svg stays the source; `node tools/sync-shell.mjs` refreshes every page.
- Prototype-only JS lives in assets/js/proto-*.js and is deleted at Blade conversion. Production behaviour (for example product.js) stays and must not depend on the prototype files.
- Scripts: the `scripts` partial (tools/partials/scripts.html, markers in every page) loads proto-cart.js then app.js; page scripts follow it (production first, then the proto-* file that fakes the server).
- CSS: there is no app.css and no @import. The `styles` partial (tools/partials/styles.html, markers in every page head) holds direct <link> tags for tokens, base, components and layout; page CSS is a separate <link> after it. `node tools/sync-shell.mjs` also sets ?v=<first 8 hex of the file's sha1> on every local .css and .js reference, so re-run it after editing any CSS or JS.
- Product images are 4:5. Use the .media placeholder with data-placeholder until real photos exist.

## Conversion-to-Blade conventions
- Every page = shared shell + <main>. Wrap shell regions in identical comments: <!-- partial: header --> ... <!-- /partial: header -->.
- Never hand-edit content between partial markers in a page. Edit tools/partials/*.html, then run `node tools/sync-shell.mjs` (`--check` lists out-of-date pages). New pages are copied from tools/page-skeleton.html and contain only <main> content plus their page CSS (the skeleton has empty marker pairs, including `styles`). tools/ is dev-only and not published.
- Each page sets <body data-page="home"> (space-separated tokens allowed); app.js marks matching [data-nav] links with aria-current="page". Partials use {{root}} for paths ("" in html/, "../" in html/account/).
- Repeated data: render several static items and wrap them in <!-- loop: products --> ... <!-- /loop -->. Conditionals get <!-- blade: @if sale --> hints.
- No inline styles (except --tone / --ratio custom properties), no inline event handlers. JS hooks use data-* attributes (never styling classes).
- Cart drawer root is [data-cart-drawer]. Its inner markup is a self-contained panel, because the backend's /cart/panel returns HTML that JS swaps into it.
- Checkout and Thank-you use a minimal shell to reduce abandonment: only the `header-minimal` (wordmark, "Secure checkout" with a lock icon, "Back to bag"; `header-auth` and `header-error` are wordmark-only variants for login and 500/503) and `footer-minimal` (Delivery, Returns, Privacy, Terms, Contact) partials, plus sprite, styles and scripts. No announcement bar, menu drawer, tab bar or cart drawer, and their marker pairs are omitted from those pages (sync-shell only fills the markers a page has). The cart page keeps the full shell.
- Delivery fee is the flat rule from config: Rs 450, free from Rs 7,500 (TODO confirm it is not district-based). Stock is decremented when the order is placed; abandoned payments hold stock (the failed state says "We'll hold your items for 30 minutes", TODO decide the expiry). There are no promo codes or gift options, and no order confirmation email exists yet, so never claim one was sent.
- Delivery ETA is shown only at checkout, after the delivery district is chosen.
- Prototype cart: assets/js/proto-cart.js keeps a demo cart in sessionStorage (seeded with two sample items; line shape {id, productSlug, name, colourSlug, colourLabel, sizeSlug, sizeLabel, unitPrice, wasPrice, qty, stock, tone}). The header counts, the drawer, the cart page and the checkout summary all read it through window.KayaaCart (get, totals, add, update, remove, clear). Products are added with colour and size slugs plus labels, the same shape from the product page form and the quick-add sheet. It is deleted at Blade conversion (the server owns the cart).
- Update docs/backend-contract.md whenever a page, form or param changes.
- Money is "Rs 1,490" (whole rupees, thousands separator). Free delivery over Rs 7,500; standard delivery Rs 450.
- Order statuses: pending, confirmed, shipped, delivered, cancelled. Payment statuses: pending, paid, failed, refunded.
- Payment is online only through the Onepay gateway (Visa/Mastercard). There is no cash on delivery. Checkout redirects to Onepay's hosted payment page (assumption, TODO confirm with the backend dev). Orders start with payment_status pending until the gateway confirms; design paid, pending and failed/retry states.
- Do not invent policy or product facts (return window, fabric certifications, delivery times). Use plausible placeholder text and mark it <!-- TODO: confirm with client -->.

## Route map (prototype file -> Laravel route)
index.html -> / | shop.html -> /shop | category.html -> /{department}/{category} | product.html -> /products/{slug} | cart.html -> /cart | checkout.html -> /checkout | thank-you.html -> /orders/{ref}/thank-you | track.html -> /track | search.html -> /search?q= | contact.html -> /contact | size-guide.html, delivery.html, returns.html, about.html, privacy.html, terms.html -> same names | account/register.html, login.html, forgot-password.html, reset-password.html, index.html (orders), order.html, reviews.html, profile.html -> /account/...

## Listing query-param contract (shop.html, category.html, search.html; GET form plus plain links, works without JS)
- age = newborn | 0-3-months | 3-6-months | 6-12-months | 1-2-years
- sort = featured | new | price-asc | price-desc
- sale = 1
- c = category slug (category.html only) | q = search text (search.html only) | page = page number (12 per page)
- Filters are limited to what the backend supports: age, sort, sale. No colour, size or price-range filters.

## Product page contract (product.html)
Full detail for the backend dev is in docs/backend-contract.md. Summary:
- Add-to-cart form: method post, radios named colour (slug, e.g. lilac) and size (the size label, e.g. 3-6M), text input quantity (1 to 10, capped at the variant's stock), action TODO. Field names are a TODO to confirm with the backend dev (a single variant id is the alternative). `data-low-stock-threshold` (an admin setting, 5 in the prototype), `data-product-name` and `data-unit-price` sit on the form.
- Variants come from an inline `<script type="application/json" id="product-variants">` block: [{"colour":"lilac","size":"3-6M","stock":4}, ...]. Blade generates it. Sizes are ordered by position in the size scale; a variant with no stock is a disabled radio with sr-only "(out of stock)".
- Photos follow the colour: every gallery slide and thumbnail carries data-colour="<slug>" or data-colour="all" (shared). Choosing a colour shows that colour's photos plus the shared ones; a colour with no photos shows the shared set. `?colour=<slug>` deep-links. Tones differ per colour in the prototype so the swap is visible.
- Averages show half stars: the whole part gives full stars and any fraction from .1 to .9 adds one half star (4.0 = 4 stars, 4.1 to 4.9 = 4.5 stars); the numeric average stays next to the stars. Review ratings are whole numbers.
- Description is a backend field (plain text, line breaks kept). The two-line summary is its first ~160 characters cut at a word boundary with an ellipsis; the Description accordion holds the full text and is open by default. Fabric & care and Delivery & returns are static site-wide placeholders.
- Reviews are a section of the product page (no separate page). "See more reviews" reveals the next 5 in place; without JS it is a link to the same product page with ?reviews=all#reviews. Review form: method post, radios rating (1 to 5), text name (guests only), textarea comment, no titles; reviews are moderated.
- Related products: same category, excluding the current product, 4 items.
- Behaviour lives in assets/js/product.js (stays after the Blade conversion). assets/js/proto-product.js (prototype only) applies the ?demo= states: sale, new, low-stock, oos, no-reviews, reviewed, review-success, review-error, review-throttle. Other params: colour, reviews=all. Every product card in the prototype opens this same sample product.

## Cart, checkout and order demo params
- cart.html: ?demo=empty | oos-line | low-stock | price-changed | free-delivery | checkout-oos.
- checkout.html: ?demo=errors | throttle | gateway-error | signed-in | empty. A valid submit stores a simulated order and goes to proto-onepay.html (prototype stand-in for Onepay's hosted page, no shell, no brand marks), whose four buttons go to thank-you.html?state=paid | pending | failed | cancelled. The bag is cleared on paid and pending only.
- Order and payment statuses use the .status component (icon + text, never colour alone): paid, confirmed, delivered = lilac tint + check; shipped = blue tint + truck; pending = neutral outline + clock; failed, cancelled = error tint + x; refunded = blue tint + rotate-ccw. Never green.

## Accounts, orders and tracking (prototype)
- Accounts are optional: guest checkout and /track always work, and every auth page says so. Customers and admins share the users table. A customer's orders are their user_id orders plus earlier guest orders with the same email (a quiet note says so on My orders).
- Demo session: window.KayaaCart.session (sessionStorage, set by the login/register demo submit, cleared by Log out). When set, the Account links in the header, tab bar and menu drawer go to account/index.html, otherwise to account/login.html (data-account-link hook in app.js). No page forces a redirect; every page is reachable by URL.
- Sample orders (assets/js/proto-orders.js): KYA-10234 shipped, KYA-10201 delivered, KYA-10198 cancelled, KYA-10240 pending payment, KYA-10250 payment failed, KYA-10180 refunded, plus six more delivered (12 total, 6 per page). The order saved by the simulated checkout is shown first. Any other reference is not found. New simulated orders start at KYA-10261.
- Demo params: track.html ?ref=<reference> and ?demo=throttle; account/login.html ?demo=errors | throttle | success; register ?demo=errors | throttle; forgot-password ?demo=errors | throttle | sent; reset-password ?demo=errors | invalid-link; account/index.html ?demo=empty, ?page=2; account/order.html ?ref=<reference>; account/reviews.html ?demo=empty; account/profile.html ?demo=saved | errors. Testers: password "wrong" on login shows invalid credentials; email taken@example.com on register shows "already registered".
- Track shows only status, payment status, items, totals, last-updated date and the delivery estimate, never name, address, phone or email. Login errors never say which field was wrong; forgot-password never reveals whether an email exists. Review statuses for customers: "Awaiting approval" (pending or hidden, one approved flag) and "Published". Cancelling orders, editing reviews and deleting accounts are not in the backend and are not designed.
- The status stepper (Order placed, Confirmed, Shipped, Delivered) uses icon + text on every step, no per-step dates, and a banner instead of the stepper for cancelled and refunded orders.
- Auth pages use the header-auth partial (wordmark and "Continue shopping") with footer-minimal; checkout and thank-you keep header-minimal.
- tools/check-css.allow lists intentional unstyled structural classes so check-css only reports real problems.

## Content pages and error pages
- Content template (assets/css/pages/content.css, assets/js/content.js): breadcrumb, h1, one-line intro, "Last updated [date]", content in a 72ch column. Long pages (Delivery, Returns, Privacy, Terms) get an "On this page" table of contents (<details> under 1100px, sticky sidebar from 1100px) and h2/h3 ids with heading anchor links. Prose, notice (lilac callout), tables that stack into labelled cards under 420px (shared .size-table), numbered icon steps, and a closing "Still need help?" card on every content page except Contact. <body data-print> turns on the print stylesheet (Size guide, Delivery, Returns, Privacy, Terms).
- Placeholder rules: never invent facts, policies, certifications, legal text, founder stories, staff names, addresses, phone numbers or delivery times. Use visible bracketed placeholders ("[X days]", "[return window]", "[WhatsApp number]") plus <!-- TODO: confirm with client -->, and neutral filler prose. Privacy and Terms are skeletons with the visible notice "Placeholder text. The final policy will be supplied by Kayaa." and no legal clauses. No emoji, no stock photos (use .media placeholders).
- The size table on size-guide.html and product.html must be the same data; the delivery estimates must be the same data as the checkout ETA (checkout's district-eta JSON). Delivery shows them in brackets until confirmed.
- contact.html: name, email, message only (POST, 5/min throttle, reuses account.js validation). ?demo=sent | errors | throttle. assets/js/proto-content.js fakes the server.
- Error pages (404, 419, 429, 500, 503) share errors.css. 404, 419, 429 use the full shell; 500 and 503 use header-error (wordmark only) + footer-minimal, with no DB-driven parts. 419/429 "Go back" uses [data-history-back], 500/503 "Try again" uses [data-reload] (app.js). 404.html is the only page with the sync-root override (see Stack and rules).
- tools/: sync-shell.mjs (partials, cache busting, sync-root), check-css.mjs, check-links.mjs (every local href/src/action exists; exit 1 on a problem).

## Photography (tools/process-images.mjs)
- Originals live in tools/image-source/ (git-ignored, never published): hero, banner-newborn, banner-sale, cat-bodysuits, cat-sleepsuits, cat-sets, cat-dresses-rompers, cat-hats-mitts, cat-swaddles, product-<colour>-<n> for colour photos (for example product-lilac-1) and product-<n> for photos shared by every colour (any extension) plus credits.csv (file, source, photographer, url; optional alt).
- Re-run with `node tools/process-images.mjs` (optional `--source <dir>`). It needs sharp, which is NOT a repo dependency: install it once outside the repo (`mkdir %TEMP%\kayaa-sharp`, `npm init -y`, `npm i sharp` there; or point KAYAA_SHARP at that folder).
- It crops to 4:5 (banners 1:1) with the attention strategy, exports WebP at 480/800/1200 (hero also 1600) to html/assets/img/<slot>/, strips EXIF, never upscales, re-encodes to meet the budget (hero 1200w <= 130KB, others 800w <= 70KB), writes docs/image-credits.md, and rewrites every <!-- photo: ID --> ... <!-- /photo: ID --> region in index.html and product.html. Colour photos map to data-colour="<colour>", shared product-<n> photos to data-colour="all"; the sample gallery has lilac 4, cream 3, sky 3 and one shared photo, and extra files are processed but not placed. A missing original keeps its placeholder. Never hand-edit between photo markers.
- Photo rules: <img> with srcset + sizes, width/height, decoding="async", loading="lazy" (hero and first gallery image: eager + fetchpriority="high"). Alt: one plain sentence for informative images, alt="" for decorative ones, never "image of". Palette is lilac, cream and blue: no green, yellow or orange photos.

## Accessibility and SEO
- Semantic landmarks, one h1 per page, skip link, labelled form fields, aria-expanded/aria-controls on toggles, dialog semantics for drawers and sheets (focus trap, ESC closes, focus returns to trigger), prefers-reduced-motion honoured.
- Every page: <title>, meta description, meta viewport with viewport-fit=cover, and <meta name="robots" content="noindex,nofollow"> (staging; removed at Blade conversion).

## Mobile definition of done
Most visitors are on phones. Every page is checked at 320, 360, 375, 390, 412 and 430px, a 667x375 landscape phone, and 768/820px tablets.
- No horizontal scroll or clipped text at any width; 320px stays usable.
- Tap targets are at least 44x44px with at least 8px between neighbours (steppers, chips, close buttons, size boxes, colour swatches with a 44px hit area, pagination).
- Fixed chrome (announcement + sticky header + sticky toolbar + bottom tab bar) never covers more than about a third of the portrait viewport, and nothing focused hides behind it (html scroll-padding top and bottom, safe-area insets). Sticky toolbars are turned off on landscape phones.
- Inputs, selects and textareas are 16px minimum so iOS Safari does not zoom on focus.
- Drawers, the quick-add sheet and the menu use 100dvh (with a 100vh fallback), overscroll-behavior: contain and safe-area padding, and lock background scroll without a layout jump (scrollbar width compensated).
- Hover styles live only inside @media (hover: hover) and (pointer: fine). Controls set touch-action: manipulation; the tap highlight is soft lilac.
- Horizontal scroll rows show a peek of the next item and never trap vertical scrolling.
- Media reserves space with aspect-ratio. Below-the-fold images need loading="lazy" decoding="async" (width/height too); the hero image is eager. Placeholders are divs for now, so loops carry an <!-- img: ... --> hint for Blade.
- Body text is at least 16px with line-height 1.5-1.6. Nothing is under 14px except 12.5px captions (and the 13px hover-only Quick add pill, which never shows on touch). h1/h2 use text-wrap: balance.
- Respect prefers-reduced-motion: movement becomes an opacity change or nothing.

## Process
- After each page, log new components in docs/components-added.md.
- After building a page, add or update its entry in html/review.html.
- Do not commit or push. Do not touch files outside html/, docs/, design/, tools/, CLAUDE.md and .github/workflows/pages.yml.
- Done = checked at 375px, 768px and 1280px: no horizontal scroll, no console errors, keyboard usable, and the mobile definition of done above. Also run `node tools/sync-shell.mjs --check` (exit 0), `node tools/check-links.mjs` (exit 0) and `node tools/check-css.mjs` (report only: fix errors, review warnings).
