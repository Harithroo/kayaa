# Components and tokens added beyond the design system

Anything below is derived from the tokens in `design/kayaa-final-design-system.html`, not defined by it. Append per page.

## Foundation

| Item | Where | Derivation / reason |
| --- | --- | --- |
| Fluid type scale (`--fs-display` ... `--fs-h4`) | tokens.css | Desktop sizes from the design system; mobile floor is ~75%. `clamp()` interpolates between 375px and 1200px viewports. H4 floor is 16px (75% would be 12.75px, below the 14px minimum). |
| Body 16px, `--fs-dense` 14.5px, `--fs-ui` 14px | tokens.css | Body at 16px everywhere (system lists 16 as Body Large, 14.5 as Body). 14px is the floor for buttons, labels and form errors. |
| Overline 12px -> 12.5px | base.css `.eyebrow` | Brief allows nothing under 14px except 12.5px captions. Badge text (system: 12px, card badge 10.5px) also raised to 12.5px. |
| Button / chip / size-box / label text 13-13.5px -> 14px | components.css | Same 14px floor. Field labels and errors 12.5px -> 14px. |
| Size box 46x40 -> min 46x44 | components.css | 44px minimum tap target. |
| `.btn--sm` min-height 40px desktop, 44px below 900px | components.css | Keeps the compact look on desktop while meeting the mobile tap target. |
| `.btn--lg` min-height 48px | components.css | Derived from lg padding. |
| `--tone-1..4` (#EAE0F2, #F2EBF7, #E1D5EE, #D9CBE9) | tokens.css | Soft lilac tints around Secondary for `.media` placeholders. No blue/green. |
| `--shadow-button-lift-danger` | tokens.css | Same recipe as the primary lift, with Error as the colour (design system shows it on danger hover). |
| `--shadow-field-focus` | tokens.css | `0 0 0 3px rgba(110,90,140,.15)` from the form-field spec. |
| `--lift-*`, `--shift-arrow`, `--pop-scale-from` | tokens.css / base.css | Movement distances as variables so `prefers-reduced-motion` zeroes them in one place (system: "drop translateY ... to a plain opacity change"). Primary/danger buttons get an opacity change in that mode. |
| Z-index scale | tokens.css | base 1, raised 2, dropdown 10, sticky 20, bottom-bar 30, overlay 40, drawer 50, modal 60, toast 70. Not defined by the system. |
| `--bottom-bar-h` 64px | tokens.css | Mobile 5-tab bar height; body gets matching padding below 900px. |
| `.icon-btn` / `.icon-btn__count` | components.css | From the system's icon-button demo (44px round, border, 2px lift). Count badge text is 11px, a glyph in a badge rather than readable copy. |
| `.icon-btn--plain` | components.css | Borderless variant for the header and drawer close button. |
| `.link-arrow` uses the `arrow-right` sprite icon | components.css | System used a text arrow; no text glyphs as icons. Min height 44px for tap target. |
| `.chip` states `[aria-pressed]`, `.is-active` | components.css | System shows `.is-active` only. |
| `.size-box` states `.is-selected`, `.is-oos` | components.css | System used `.selected` / `.oos`; renamed to the `is-` convention. Also responds to `aria-pressed` / `aria-checked` / `:disabled`. |
| `.progress` | components.css | Not in the system. Native `<progress>`, 8px, Line track, Primary Deep fill (avoids inline widths). Intended for the free-delivery meter. |
| `.qty` stepper | components.css | Not in the system. Pill border (Line), 44px buttons, Secondary hover. |
| `.price`, `.price--sale`, `.price--lg`, `.price__was` | components.css | System has `.pv-price` (800, Primary Deep). Was-price uses Ink Soft, 14px, line-through. On sale, the current price switches to Ink so one colour does not carry two meanings. |
| `.media` | components.css | Placeholder replaces the emoji thumbs in the card demo. Default 4:5 via `--ratio`, tint via `--tone`, icon from the sprite. |
| `.section`, `.section--tint`, `.container` | layout.css | Container 1200px (system doc uses 1100px; brief specifies 1200px). Section padding 48px mobile, 64px desktop. |

## Notes / conflicts with the design system

- The card quick-add pill in the system is Ink-filled with Bg text (a dark element). It is small, not a section, so it is allowed to stay, but flag if the client reads it as "dark".
- Card hover dims the photo (`filter: brightness(.72)`). On touch devices there is no hover, so Quick add must be a visible control on mobile (decide when the card is built).

## Shell and Home (page 1)

| Item | Where | Notes |
| --- | --- | --- |
| Inline sprite instead of `assets/icons/sprite.svg#id` | every page | External `<use>` fails on file:// in Chrome. Sprite is inlined once per page; the file stays as the source. Blade: `@include` a partial. |
| `i-ruler` icon | sprite.svg | Lucide, for the hero tag. |
| `--cream` #FBF6EE, `--sky-tint` #DDE7F3, `--line-strong`, `--scrim` | tokens.css | Derived: topbar/promo variants, footer dividers, drawer scrim (Ink at 40%). |
| `--swatch-*`, `.dot`, `.swatch` | tokens / components | Product colour dots (lilac, sky, cream, blush, dove). No green. Selectable swatch is a radio with a ring. |
| `.topbar` (--lilac / --sky) | layout.css | Dismiss remembered in sessionStorage. |
| `.site-header`, `.primary-nav`, `.header-search`, `.category-row` | layout.css | See the refinement round below for the Shop mega menu and search. |
| `.drawer`, `.overlay`, `.sheet` | layout.css | Shared layer system in app.js: focus trap, ESC, focus return, scroll lock. |
| `.tabbar` | layout.css | Below 900px only. Active tab = Primary Deep + 3px top indicator. Labels are Ink (Ink Soft is not allowed at 12.5px). |
| `.site-footer` | footer.css | Light lilac gradient, see "Footer redesign" below. (`.site-footer--minimal` stays flat on --secondary in layout.css.) |
| `.scroller` | layout.css | Scroll-snap row below 900px, grid at 900+. |
| `.product-card` | components.css | Hover effects only under (hover: hover) and (pointer: fine). Touch gets a persistent 44px round + button. Card is white with a 1px line border (as in the design system). |
| `.cart-item`, `.cart-summary`, `.empty-state` | components.css | Cart drawer content. |
| `.size-box` and `.swatch` as radios | components.css | Uses :has(input:checked). |
| `.hero`, `.trust`, `.age-tile`, `.cat-tile`, `.promo` | pages/home.css | Promo --sky uses a Periwinkle tint. |

### Gaps and assumptions
- Accent appears on Sale badges, the sky promo and the sky topbar option, which strains "one highlight per view". The sky promo was requested; consider lilac Sale badges if the client objects.
- Category tiles are 3 columns from 600px as specified and 6 across from 1200px (three huge tiles looked wrong on desktop).
- The menu drawer also carries New in, Sale and Our story, which the mobile bar otherwise cannot reach.
- Account links use `account/login.html`; pages inside /account need `../` (Blade `route()` solves this).
- WhatsApp number, email, social URLs, fabric claim and returns window are TODO.
- Cart state is demo-only and resets on reload.

## Stubs and listing pages (shop, category, search)

| Item | Where | Notes |
| --- | --- | --- |
| `.stub`, `.stub__card`, `.stub__actions` | pages/stub.css | Centred "still being designed" card on every unbuilt page. Whole `<main>` is replaced when the page is built. |
| `i-chevron-left` icon | sprite.svg | Lucide, for pagination Prev. |
| `.breadcrumb` | pages/listing.css | `nav > ol`, "/" separators via CSS, last item `aria-current="page"`. Links have a 44px minimum target. |
| `.listing-head` (header band) | pages/listing.css | Rounded `--tone-2` container (not full-bleed) with H1, one-line intro and result count. |
| `.listing-search` | pages/listing.css | Search field and button inside the band on search.html (mobile has no header search). |
| `.age-chips` | pages/listing.css | Chip links; scroll-snap row with a faded right edge below 900px (alpha mask), wrapping from 900px. Active chip uses `aria-current="page"` (added to `.chip` states). |
| `.toolbar` | pages/listing.css | GET form: range text, sort select, Sale switch. Sticky under the header below 900px using `--header-h` (measured in app.js). The range text is screen-reader only below 600px and the sort label is too, to give the select room. |
| `.switch` | components.css | Checkbox with `role="switch"` styled as a toggle. Off = Ink Soft outline, on = Primary Deep. Focus ring on the track. |
| `data-autosubmit` | app.js | Submits the form on change. Pages ship a `<noscript>` Apply button. |
| `.pagination` | pages/listing.css | Prev / numbers / Next with `aria-current`; ellipsis when more than 7 pages; below 600px it compacts to Prev / "Page 1 of 2" / Next. |
| `.listing-empty` | pages/listing.css | Reuses `.empty-state` (package icon, Clear filters). |
| `.no-results`, `.chip-list` | pages/listing.css | Search-only: three tips plus category and age chip links. |
| `.size-help` | pages/listing.css | Size-guide band under the grid. |
| `assets/js/proto-listing.js` | js | PROTOTYPE ONLY. Filters, sorts, searches and paginates the cards. Delete at Blade conversion. |

### Listing assumptions
- H1 precedence when several filters are active: category > age > sale > sort=new > default. With age or category plus sale, the intro adds "Showing sale items only."
- sort=new is a sort, not a filter, so "New in" lists every product, newest first.
- "Clear filters" removes age, sort, sale and page but keeps c and q, so category and search pages stay on their category or query.
- category.html with no `c` behaves like Shop; an unknown `c` shows "Category not found" with the empty state.
- Search matches every word in the query against product name and category name, case-insensitively.
- Category descriptions are placeholders (TODO in proto-listing.js).

## Refinement round: shell, payment copy, mega menu, mobile pass

| Item | Where | Notes |
| --- | --- | --- |
| `.topbar` dismiss | app.js / layout.css | No storage at all: it returns on every load. Height collapses over 200ms (`.is-collapsing`), instant under reduced motion, then `--header-h` is re-measured. `.topbar__extra` (second segment) is hidden below 600px so the bar stays on one line. |
| `i-credit-card` icon | sprite.svg | Lucide. Used for "Secure online payment" (trust strip) and "Secure card payments" (footer). `i-banknote` stays in the sprite but is unused. |
| `.primary-nav__split`, `.primary-nav__chevron` | layout.css | "Shop" is a split control: link to shop.html plus a 44px chevron button (`aria-expanded`, `aria-controls="mega-shop"`). The old "Shop by age" dropdown (`.dropdown`, `.has-dropdown`, `[data-dropdown]`) is removed. |
| `.mega`, `.mega__panel`, `.mega__feature`, `.mega-scrim` | layout.css | Shop mega menu inside the split item (so Tab flows chevron -> panel). Attached to the bottom of the 72px header bar, spans the container width and covers the category row. Hover intent 120ms open / 250ms close, click/Enter/Space toggles, ESC returns focus to the chevron, outside click and focus leaving close it, `inert` + `visibility` while closed. Below 1100px the feature card is hidden so the five age tiles keep a readable width. Scrim is Ink at 8%. |
| `--scrim-light`, `--shift-panel` | tokens.css | Mega backdrop and panel slide distance (zeroed under reduced motion). |
| `.header-search` (rebuilt) | layout.css | 1100px+: 240px pill that grows to 320px on focus. 900-1099px: icon button (`data-search-toggle`) that opens an inline field over the nav; ESC closes and returns focus. Search sits in the right-hand group with account and cart. |
| `.chip-list` | components.css | Moved here from listing.css (used by the mega menu and no-results). |
| `.product-card__quick` | components.css | Two separate rules: touch = 44x44 circle (`border-radius: 50%`); mouse = pill (`--radius-pill`, 11px 22px, 13px bold, `width: max-content`, 16px icon, 8px gap). The 13px is the design-system value and only shows with a mouse. |
| Hover gating | all CSS | Every `:hover` rule now sits inside `@media (hover: hover) and (pointer: fine)` so touch devices never get stuck hover states. |
| `.icon-btn__count` | components.css | Now 12.5px in a 20px badge (was 11px). |
| Scroll lock | layout.css / app.js | `html.is-locked` also locks body and pads by the measured scrollbar width (`--scrollbar-w`) so nothing jumps. |
| `scroll-padding` | base.css / listing.css | Top = `--header-h` + 8px (+76px under the sticky toolbar on mobile); bottom = tab bar + safe area. |
| Footer and menu link lists | footer.css (footer), layout.css (menu) | 8px gap between rows so tap targets never touch. |

### Payment copy
- Online payment only (Onepay, Visa/Mastercard).
- No card or Onepay logos: text and the credit-card icon, with TODO comments to add the official marks and badge.

## Footer redesign (prompt 19)

| Component | Where | Notes |
| --- | --- | --- |
| Footer gradient, `--footer-g1`..`--footer-g4` | tokens.css, footer.css | `linear-gradient(180deg, #EDE3F5 0%, #E6DAF2 28%, #DACBEA 62%, #CDB9E3 100%)`. The first stop is a visible step darker than the page (#FAF7FB) and the top edge is a 36px soft wave (a repeating SVG `mask`, also `-webkit-mask`), so the footer reads as its own block. Light only, never dark; no dark mode. |
| `<body data-footer-from="white|| `<body data-footer-from="white|secondary">` | tools/shell-map.json (`footerFrom`), written by sync-shell | Optional override of the gradient's first stop; no page uses it. `tools/check-footer-seam.mjs` checks the opposite of a seam now: the page colour above the footer and the footer colour 40px inside it must differ by at least 8 on a channel. |
| Footer text | footer.css | All text, headings, links and the focus ring are Ink: Ink Soft and Primary Deep fail 4.5:1 on the lower stops. Contact links are bold and underlined. `tools/qa/footer-contrast.mjs` proves it. |
| Layout | footer.css | 1200px container. Desktop: brand column plus Shop by size, Help, Company. Below 900px: two columns, the brand block spans both. 44px tap rows for touch, compact rows for a mouse from 900px. Legal row (© and "Secure card payments · Island-wide delivery"). Contact lines kept. |
| Social buttons (`.footer__social`, `.footer__social-link`) | tools/partials/footer.html, footer.css, sprite.svg | Instagram, Facebook, TikTok, WhatsApp: a `<ul>` in `<nav aria-label="Social media">`, 44x44 round, translucent white with a 1px #D9CBEA border, Ink glyph, `aria-label`. Mouse only: 3px lift and a white fill. Instagram, Facebook and TikTok are `href="#"` with a TODO comment (add `target="_blank" rel="noopener noreferrer"` with the real URL); WhatsApp is `https://wa.me/` pending the number. There are no text-only social links any more. |
| Filled brand glyphs `i-instagram`, `i-facebook`, `i-tiktok`, `i-whatsapp` | sprite.svg | Paths from simple-icons 16.34.0 (CC0), fetched to a temp folder outside the repo; `fill="currentColor"`, no stroke (the rest of the sprite is stroke icons). Brand marks are trademarks of their owners; use them only to link to Kayaa's own accounts. |
| Art zone (`.footer__art`, aria-hidden) | footer.html, footer.css | The supplied art, not redrawn. The clothesline (`footer-clothesline.svg`) is inlined by `gen: footer-clothesline` so its garments can sway: 100% wide, `slice`, 150-230px high (`max(110px, 12.4vw)`: just tall enough that the slice never cuts a hem); `@keyframes footer-sway` runs only under `prefers-reduced-motion: no-preference` and only while the zone is on screen (app.js adds `.is-live` with an IntersectionObserver). `footer-waves.svg` is a lazy `<img>` over the bottom 62% and reaches the footer's bottom edge. The two supplied SVGs carried a ~7.8 KB Content Credentials (c2pa) metadata block each; it was stripped (originals kept in git-ignored tools/image-source/footer/). |
| Giant "kayaa" wordmark | html/assets/img/footer/wordmark.svg | An SVG PATH (no font at runtime, no layout shift) of Urbanist 800 made by `tools/make-wordmark.mjs`; tight viewBox with 14 units below the descender so the tail of the y is whole; white 98% to 55% vertical gradient; container width + 2vw; its top is tucked behind the clothesline (45% of the way down the garments, so the hems hang over the tops of the letters; the line sits in front); aria-hidden, at the very bottom. Regenerate with the script, never edit the path by hand. |
| Bottom room | footer.css | Below 900px the art zone's bottom padding is the tab bar height + 16px + `env(safe-area-inset-bottom)`, and `body:has(full footer)` drops its own padding, so the waves reach the screen bottom behind the bar. |
| Print and forced colours | footer.css | Print: white background, art hidden. Forced colours: art hidden, Canvas background. |

The art zone uses `content-visibility: auto` with an estimated intrinsic height, so the art costs nothing until it is scrolled near (Lighthouse mobile home went from 93 to 98 with it; nothing below the footer can shift, CLS 0). Weight: the three art files total about 10 KB (clothesline 6.5 KB after stripping, waves 0.5 KB, wordmark path 2.4 KB), under the 12 KB / 15 KB limits; no raster images. Inlining the clothesline adds about 1.2 KB gzip per page (under the 3 KB limit that would have switched it to an `<img>`).

### Assumptions
- Between 900 and 1099px the mega menu shows the age tiles and category chips without the feature card.
- The announcement bar's focus moves to the wordmark when dismissed, so keyboard users are not dropped at the top of the page.
- The tab bar items touch each other (each is 75px+ wide), so they are treated as one control for the 8px spacing rule.

## Product page

| Item | Where | Notes |
| --- | --- | --- |
| `.stars` (display), `.stars--lg` | components.css | Five outline stars, filled ones use `.is-on` (Primary Deep, no gold). Rounded to whole stars; half stars are a TODO. |
| `.rating-input` (star input) | components.css | Five real radios in DOM order 1 to 5, styled as 44px stars. A star is filled when its radio or any later radio is checked (`label:has(~ input:checked)`), so it works without JS and with arrow keys. `.rating-input--error` turns the stars red. |
| `.rating-row` | components.css | Breakdown row: label, existing `.progress`, count. |
| `.review-card`, `.review-card__reply` | components.css | Stars, name, date, optional "Verified purchase" outline badge, comment, tinted "Reply from Kayaa" block. No titles (not in the backend). |
| `.alert` (info, success, error) | components.css | Success is a lilac tint with a check icon and Primary Deep (no green). Error uses the new `--error-tint` token. `role="alert"` for errors, `role="status"` for success. |
| `--error-tint` | tokens.css | Error at about 8% on white. |
| `i-info`, `i-circle-alert` icons | sprite.svg | Lucide, for alerts and inline errors. |
| `.accordion` | components.css | Native `<details>`; 56px summary, chevron rotates when open. No height animation, so reduced motion needs no special case. |
| `.size-table` | components.css | Size guide table. Under 420px each row becomes a card (cells show their `data-label`) so nothing scrolls sideways at 320px. Same data as the future size-guide.html (placeholders, TODO). |
| `.back-link` | components.css | "< Back to Bodysuits"; replaces the breadcrumb under 600px. |
| `.breadcrumb` | components.css | Moved from listing.css so the product page can use it. Separator spacing is now 4px. |
| `size-box--lg` and radio size boxes | product.css / components.css | Min 56x44px. Disabled = struck through with sr-only "(out of stock)". |
| `.gallery` (carousel + thumbs) | pages/product.css | Below 900px a native scroll-snap carousel with a "1 / 5" pill and 44px dot buttons; from 900px the main image plus a vertical thumbnail rail (buttons with `aria-current`, arrow keys, roving tabindex). Smooth scrolling is turned off under reduced motion. |
| `.sticky-atc` | pages/product.css | Fixed bar below 900px, shown by an IntersectionObserver once the main Add to cart button leaves the viewport. Hides the tab bar while visible (`body.has-sticky-atc`), uses safe-area insets, 64px tall (52px landscape). |
| `.buy`, `.assure`, `.review-form` | pages/product.css | Form layout, assurance list (returns row is one 44px link), review form. The district delivery estimate was removed (ETA is checkout-only). |
| `data-qty-max` | app.js | A `[data-qty]` group can cap its quantity (used for stock). `syncQty` now honours it and runs on synthetic change events. |
| `window.KayaaCart.add(item, trigger)` | app.js | Adds a line item (with `qty`) to the cart drawer demo and opens it. |
| `assets/js/proto-product.js` | js | PROTOTYPE ONLY. Sample data, stock matrix per colour and size, the demo states and review validation. Delete at Blade conversion. |

### Product page assumptions
- The sample product is Ribbed Cotton Bodysuit; every product card in the prototype opens it (noted on review.html).
- Demo states: sale, new, low-stock, oos, no-reviews, reviewed, review-success, review-error, plus review-throttle (rate-limit alert) and reviews=all.
- If a colour change makes the chosen size unavailable, the size is cleared rather than silently switched.
- With no size chosen the sticky bar's button says "Choose size" and scrolls to and focuses the size group.

## Photography

| Item | Where | Notes |
| --- | --- | --- |
| `tools/process-images.mjs` | tools | Crop, WebP export, budgets, credits doc and page wiring. Re-runnable and idempotent; a missing original keeps its placeholder markup. |
| `<!-- photo: ID -->` regions | index.html, product.html | Generated content. Hero, 2 promo banners, 6 category tiles, 5 product gallery slides and 5 thumbnails. |
| `.media > img` | components.css | Photo variant of `.media`: `<img>` with `object-fit: cover` inside the existing aspect-ratio box. The `--tone` background shows while it loads. The placeholder variant (`data-placeholder`) is unchanged. |
| `.promo__photo` | pages/home.css | Banner photo masked into an organic blob (`border-radius`) over the big circle. Absolutely positioned and behind the text, so the card height does not change; text stays on the solid tint. 24% of the card width on mobile (72-120px), 34% from 900px (130-220px); the title and text narrow to avoid it. |
| Category tile photo variant | pages/home.css | Triggered by `:has(.media:not([data-placeholder]))`. Photo fills the tile at 4:5; the label becomes a cream pill at bottom-left (no overlay, no text on the photo). The whole tile lifts on hover. |
| `.gallery__badge.badge--outline` | pages/product.css | Gets a solid surface background so it stays readable over a photo. |
| `/tools/image-source/` | .gitignore | Originals are never committed. |

### Photography assumptions
- Banner and category photos are decorative (`alt=""`): the card text and the label pill name the link. The hero alt is a generic default (a TODO comment is written next to it) unless credits.csv has an `alt` column. Product gallery photos use "Product photo placeholder (TODO)"; thumbnails are `alt=""` because the buttons have aria-labels.
- Category photos are 4:5 while the placeholder tile is 4:3, so a tile changes ratio when its photo arrives. If only some categories have photos, the row mixes both.
- Licence in docs/image-credits.md is inferred from the source (Unsplash, Pexels, Pixabay) and otherwise marked TODO.

## Product page decisions round (colour photos, half stars, show more, hardening)

| Item | Where | Notes |
| --- | --- | --- |
| `data-colour` gallery filtering | product.html, product.js | Every slide (`li.gallery__slide`) and thumbnail button carries `data-colour="<slug>"` or `"all"`. Picking a colour shows its photos plus the shared ones, resets to the first, rebuilds the dots, updates the counter, thumbnail rail and the main image alt; `?colour=<slug>` deep-links (and is kept in the URL). A colour with no photos shows the shared set. Placeholder tones differ per colour (lilac `--tone-4`/`--tone-3`, cream `--swatch-cream`/`--cream`, sky `--sky-tint`/`--accent`, shared `--tone-2`). |
| `.star-half` | components.css | Half star: an outline star with a filled star on top, clipped to 50% width (`clip-path`). Used for averages only: whole part = full stars, .1 to .9 adds one half star. |
| `.accordion__body--text` | components.css | `white-space: pre-line` for the backend description. The Description accordion is open by default; the two-line summary above it is the first ~160 characters. |
| Reviews "See more" | product.html, product.js | `.reviews__more` holds the count ("Showing 5 of 12"), the link/button and an `aria-live` region. The link (`?reviews=all#reviews`) is replaced by a button that reveals 5 more, moves focus to the first new review (`article tabindex="-1"`) and announces "5 more reviews shown". 12 sample reviews, 5 visible. |
| `#product-variants` JSON | product.html | `[{"colour","size","stock"}]`, read by product.js. Blade generates it. The sample matrix moved here from the old JS. |
| `data-low-stock-threshold` | buy form | Admin setting (5). |
| `assets/js/product.js` / `proto-product.js` | js | product.js is production behaviour; proto-product.js only applies `?demo=` states, price/badge variants and the fake review validation, and runs first so it can rewrite the variants JSON. |
| Cart and quick-add thumbnails | app.js | `toneValue()` maps a colour slug to its placeholder tone, so cart lines and the quick-add sheet thumbnail follow the selected colour. `window.KayaaCart.add` accepts a colour slug as `tone`. |
| Delivery estimate removed | product.html, product.css | Delivery ETA is only shown at checkout. |
| `styles` partial | tools/partials/styles.html | Direct `<link>` tags for tokens, base, components and layout in every page head. `app.css` and its `@import` chain are gone. |
| `?v=<sha1>` cache busting | tools/sync-shell.mjs | Every local .css/.js reference gets the first 8 hex chars of the file's sha1; `--check` flags stale versions. |
| `tools/check-css.mjs` | tools | Report-only: brace balance, unterminated comments, missing link/url/srcset targets, and a warning list of classes used in HTML but defined in no CSS. Current warnings are unstyled structural hooks (`hero__copy`, `mega__feature-body`, `pagination__edge-label`, `rating-row__label`, `switch__label`). |
| Footer link rhythm | footer.css | No min-height: rows are line-height 1.5 plus 8px above and below (about 40px) with no gap; with a mouse from 900px the links are compact (2px padding, 6px between). |
| `docs/backend-contract.md` | docs | Contract for the backend dev; update it whenever a page, form or param changes. |

### Assumptions
- 12 sample reviews: ten 5-star and two 4-star, so the average is 4.8 and shows 4.5 stars under the half-star rule.
- Review ratings stay whole numbers; only averages get half stars.

## Cart, checkout, payment hand-off and thank-you

| Item | Where | Notes |
| --- | --- | --- |
| `proto-cart.js` (demo cart) | js | PROTOTYPE ONLY. sessionStorage store (memory fallback) behind `window.KayaaCart`; events `kayaa:cart` and `kayaa:open-cart`. Header counts, drawer, cart page and checkout all read it. The `scripts` partial loads it before app.js. |
| Drawer renders from the store | app.js | Lines are reconciled in place by id (focus stays on the stepper). The drawer markup and the `[data-cart-drawer]` contract are unchanged. Quick-add sends colour slug + label and size key + label; the product page form sends the same shape. |
| `.status` | components.css | Order/payment status: icon + text. The icon is a CSS mask so the mapping lives in one place (paid/confirmed/delivered lilac + check, shipped blue + truck, pending outline + clock, failed/cancelled error tint + x, refunded blue + rotate-ccw). Reused for tracking and account pages. |
| `.spinner`, `.btn[aria-busy]` | components.css | Busy button keeps the Primary Deep fill; under reduced motion the spinner is a static ring plus the text. |
| `.btn-text` | components.css | Text button with an icon (Remove), 44px target. |
| `.summary-card`, `.totals`, `.order-lines`/`.order-line` | components.css | Shared by cart, checkout and thank-you. |
| `.assure` list | components.css | Moved from product.css (cart summary reuses it). |
| `.sticky-atc` | components.css | Moved from product.css; the cart uses it as a sticky checkout bar. |
| `i-clock`, `i-lock`, `i-copy` icons | sprite.svg | Lucide. |
| `.site-header--minimal`, `.secure-label`, `.site-footer--minimal`, `.footer-min` | layout.css | Minimal shell for checkout and thank-you. Below 600px the "Secure checkout" label sits on its own row under the wordmark and "Back to bag". `body:has(.tabbar)` now owns the tab-bar padding. |
| `header-minimal`, `footer-minimal`, `scripts` partials | tools/partials | sync-shell fills only the markers a page contains. |
| Cart page (`pages/cart.css`, cart.js, proto-cart-page.js) | cart.html | Lines with real update/remove forms, line states (only N left, no longer available, price changed), sticky summary, mobile sticky bar, empty state. |
| Checkout (`pages/checkout.css`, checkout.js, proto-checkout.js) | checkout.html | 7/5 layout, collapsible mobile summary, numbered cards, delivery estimate card from a JSON table, payment card, error summary and loading state. |
| `proto-onepay.html` | html | PROTOTYPE ONLY gateway stand-in. |
| Thank-you (`pages/thank-you.css`, thank-you.js, proto-thankyou.js) | thank-you.html | Four state blocks (paid, pending, failed, cancelled), copyable order reference. |

### Assumptions
- Checkout's mobile "Edit bag" link and the order summary list only available lines; unavailable lines are excluded from totals.
- The thank-you pages keep a "Back to bag" link from the minimal header on every state.
- Terms and Returns links in the pay card are inline links with a padded tap area rather than separate buttons.

## Track order, auth pages and account area

| Item | Where | Notes |
| --- | --- | --- |
| `.stepper` | components.css | Status stepper (Order placed, Confirmed, Shipped, Delivered): `<ol>` with `is-done`, `is-current` (`aria-current="step"`, ring), `is-upcoming`, `is-error`. Vertical below 900px, horizontal from 900px, icon + text on every step, no per-step dates. Cancelled and refunded orders show an `.alert` banner instead. `.stepper-note` carries "Waiting for payment confirmation". |
| `.password-field` | components.css | Input with a 44px show/hide button; the button keeps the name "Show password" and uses `aria-pressed`; `aria-controls` points at the input. |
| `.checkbox` | components.css | Real checkbox, styled box with a check icon, 44px row, visible focus ring ("Remember me"). |
| `.order-head` | components.css | Reference, date line and status badges (track result, order detail). |
| `.pagination` | components.css | Moved from listing.css; reused by My orders. Prev/Next are icon-only under 400px. |
| `.empty-state--card` | components.css | Empty state on a surface card (orders, reviews). |
| Auth card (`.auth`, `.auth-card`, `.auth-form`, `.auth-row`, `.auth-link`, `.auth-guest`) | pages/auth.css | Centred card for login, register, forgot and reset, with the guest-checkout note under every form. |
| Shell variants | tools/shell-map.json, tools/check-shell.mjs | full / checkout / error / none per page, checked by `sync --check`. The `header-auth` partial was removed: the auth pages use the full shell. |
| Account layout and nav (`.account`, `.account-nav`) | pages/account.css | Sidebar from 900px, scrollable chip nav below with `aria-current`; the desktop sidebar holds Sign out, mobile puts it at the bottom of the profile page. |
| Order card (`.order-card`) | pages/account.css | Reference, date, status and payment badges, up to 3 thumbnails, item count, total, "View order". Stacked on mobile, one compact row from 900px. |
| Review list item | pages/account.css | The shared `.review-card` with a product header and a `.status` badge: "Published" = `status--confirmed`, "Awaiting approval" = `status--pending`. |
| Track page (`pages/track.css`) | track.html | Lookup form, found/not-found/throttle states, help card with the reference. |
| `proto-orders.js`, `proto-account.js`, `proto-auth.js`, `account.js` | js | Sample orders and stepper renderer, page demos, auth demo outcomes (all prototype), and production form validation (account.js). |
| `tools/check-css.allow` | tools | Allow-list of intentional structural classes for check-css. |

### Assumptions
- The track page, order detail and thank-you page share one status vocabulary (`.status` and the stepper); the delivered, cancelled and refunded orders hide the delivery estimate.
- The stored checkout order replaces any sample order with the same reference; new references are KY-<today YYMMDD>-<4 random A-Z0-9>.

## Content template and error pages

| Item | Where | Notes |
| --- | --- | --- |
| Content layout (`.content`, `.content__head`, `.content__lede`, `.content__updated`, `.content__layout`) | pages/content.css | Breadcrumb, h1, intro, "Last updated [date]" and a readable column (`.prose`, 72ch). `--toc` adds the sidebar column from 1100px. |
| Table of contents (`.toc`, `data-toc`) | pages/content.css, js/content.js | A `<details>` block under the intro below 1100px (starts closed), a sticky always-open sidebar from 1100px. Links are 44px tall with 8px gaps. Markup ships `open` so it works without JS. |
| Prose (`.prose`) | pages/content.css | 16px / 1.6 text, lists, underlined links, `dl`, h2/h3 with `scroll-margin-top` (the html scroll-padding clears the sticky header and fixed bars), `.placeholder` bracket highlight. |
| Heading anchor (`.anchor`) | pages/content.css | Link icon after each h2/h3, shown on hover or keyboard focus on desktop only (the table of contents serves touch). |
| Notice (`.notice`) | pages/content.css | Lilac tint callout with an info icon (placeholder notice, between sizes). |
| Content tables | pages/content.css | The shared `.size-table` (stacks into labelled cards under 420px) inside `.table-wrap`; `.size-table--two` fixes the column widths of the delivery tables. |
| Icon steps (`.how-steps`) | pages/content.css | Numbered ordered list with an icon tile (how to measure, how delivery works; `--row` makes two columns from 600px). |
| Help card (`.help-card`) | pages/content.css | "Still need help?" with WhatsApp button and Contact link; closes every content page except Contact. |
| Values and about media (`.values`, `.about-media`) | pages/content.css | Three-card values row and two `.media` placeholders. |
| Contact cards and form (`.contact-card`, `.contact-form`) | pages/content.css | Lilac icon discs (no green, no brand logo); the form reuses `.field`, the error-summary alert and `account.js` validation (now also for textareas). |
| Print stylesheet | pages/content.css | `body[data-print]`: shell, table of contents, anchors and chips hidden, white background, full-width text, link URLs after links; accordions are opened by content.js before printing. |
| Error template (`.error-page`, `.error-disc`, `.error-code`, `.error-actions`, `.error-search`, `.error-cats`) | pages/errors.css | Centred card: lilac icon disc, code label, h1, sentence, two buttons, help line; 404 adds the search field and category chips. |
| `header-error` partial | tools/partials | Wordmark-only header for 500 and 503 (header-minimal says "Secure checkout" and "Back to bag"). **Deviation from the brief**, which asked for header-minimal. |
| `sync-root` override | tools/sync-shell.mjs | `<!-- sync-root: /kayaa/ -->` in 404.html makes {{root}} resolve to `/kayaa/`. |
| `tools/check-links.mjs` | tools | Dev-only link checker; allows root-absolute URLs only in the sync-root page. |
| `data-history-back`, `data-reload` | js/app.js | Error page buttons; the href is the fallback without history or JS. |
| New icons | assets/icons/sprite.svg | wrench, search-x, scale, hourglass, triangle-alert, mail, link, list-checks. |

### Assumptions
- All six content pages ship placeholder facts in brackets; Privacy and Terms are skeletons only.
- The delivery page repeats the checkout's placeholder estimates in brackets so the two stay consistent until the real data exists.
- app.js builds the cart drawer's product links from the wordmark link, so they resolve from account/ and from 404.html at any path (this also fixes the drawer's links on the account pages).

## Config hooks, cancel dialog, verification banner and verify-email page

| Item | Where | Notes |
| --- | --- | --- |
| `tools/site-config.json`, `[data-cfg]`, `[data-cfg-attr]`, `site-config.js` | tools/sync-shell.mjs | Single source for the fee, free-delivery threshold, pay button label, order-email flag, remember days and low-stock threshold. sync-shell refreshes the text and attributes (idempotent, `--check` flags stale text) and writes `assets/js/site-config.js` (`window.KAYAA_CONFIG`). Blade replaces both with view variables. |
| `Kayaa.formatEta(min, max)` | js/app.js | The one place delivery estimates are worded ("3 working days", "1 working day", "2–4 working days"). Used by checkout.js, content.js (delivery page) and the prototype order pages. |
| Dialog (`.dialog`) | components.css, js/account.js | Native `<dialog>` confirmation: opened by `[data-dialog-open="ID"]`, closed by `[data-dialog-close]`, ESC or the backdrop; Tab is kept inside; focus returns to the opener. Used by "Cancel this order?" (Keep order primary, Yes, cancel order danger). |
| Stepper date (`.stepper__date`) | components.css | One date under a step label when the order has it; nothing when it does not. |
| Verification banner (`.verify-banner`) | pages/account.css, js/account.js | `role="region"` banner with Resend email and a Dismiss button, on every signed-in account page while unverified. Dismissal is remembered in sessionStorage. |
| Verify-email page (`account/verify-email.html`, `.auth-card__icon`, `.verify-actions`) | pages/auth.css | Auth-card template with three states: waiting (Check your email), verified and invalid link. |
| Track form fields (`.track-form__fields`) | pages/track.css | Reference and mobile number side by side from 600px, with the checkout-style error summary above. |
| Thank-you "expired" state and email line (`.thanks__email`) | pages/thank-you.css | Expired signed link (no order details) and the optional confirmation-email line. |

## Catalogue alignment (categories, sizes, wishlist, product page)

| Item | Where | Notes |
| --- | --- | --- |
| `tools/catalogue.json`, `tools/catalogue.mjs`, `<!-- gen: NAME args -->` regions, `catalogue-data.js` | tools/sync-shell.mjs | Single source for the seven categories, nine sizes, colours and 26 sample products. sync-shell regenerates the header category row and mega menu chips, the drawer lists, the footer sizes, the home tiles, the listing chips, every product grid, the related list and the size-table rows (idempotent; `--check` flags stale regions; photo markers keep what process-images wrote). Fails if a size has fewer than 4 products or a category fewer than 3. |
| Product card meta line (`.product-card__meta`) | components.css | "4 colours · NB–18m" replaces the colour dots; one badge (Sale > New); the compare-at price is struck through. |
| Size pills (`.mega__sizes`, `.menu-group__chips`) | layout.css | The nine sizes as a pill grid in the mega menu and the drawer; a two-column list in the footer (`.footer__list--cols`). |
| Shop-by-age row | pages/home.css | Nine tiles that keep scrolling sideways at every width (the shared scroller becomes a grid at 900px; this one does not). |
| Category tiles (`.cat-grid`, `.cat-tile`, `.cat-tile__count`) | components.css (moved from home.css) | Shared by the home page (seven tiles in one row from 1200px) and the categories page (with item counts). |
| Categories page (`categories.html`) | pages/categories.css | "All categories": the seven tiles; Home / Baby breadcrumb. |
| Wishlist (`wishlist.html`, `proto-wishlist.js`, `.wishlist-actions`) | pages/wishlist.css | Saved product cards with Remove and Choose size (opens the quick-add sheet), an empty state, and a heart link in the desktop header (`.site-header__wishlist`), the drawer and the footer. The product page has a Save toggle (`.buy__save`, `aria-pressed`). |
| Quick-add sheet options | js/app.js | Sizes and colours are filled from the card (`data-sizes`, `data-colours`) with labels from `window.KAYAA_CATALOGUE`. |
| Product page additions | pages/product.css | Eyebrow "Kayaa Essentials", "Colour — Butter", the dispatch line (`.buy__dispatch`), the care list (`.care-list`), review headline (`.review-card__headline`) and the sign-in note (`.review-form__signin`). |
| Currency helper | js/app.js (`Kayaa.formatMoney`), tools/sync-shell.mjs | "Rs. 2,450" everywhere; the prefix is the site config `currency_prefix`. |
| `contact` validation rule | js/account.js | One field that accepts a Sri Lankan mobile number or an email address. |
| Header category row, announcement bar | tools/partials | "Island-wide delivery in 2–4 days · Free over Rs. 7,500" (config values). |

### Assumptions
- The sample catalogue (26 products) uses the names, categories, prices and size ranges the brief took from the staging site; all other product facts are placeholders.
- Colours are labelled Butter, Lilac, Sky, Blush and Dove (slug `cream` is shown as "Butter"); there are no colour dots on cards because the backend supplies no colour values yet.
- District ETA is seeded with two zones as `{min, max}` (Colombo 1–2, the rest of the island 2–4), TODO until per-district data arrives.

## Content pack, SEO head and self-hosted fonts

| Item | Where | Notes |
| --- | --- | --- |
| Content markers (`mark.todo`, `mark.proposed`, `.marker-label`) | components.css | Open text from the content pack: a lilac highlight with a dashed outline and a small label ("To confirm" / "Proposed"); AA readable, never green or yellow. |
| `tools/markdown.mjs`, `tools/content.mjs`, `tools/content.json` | tools | Render docs/content/*.md into the pages: `<!-- gen: content NAME -->` (breadcrumb, h1, intro, table of contents, prose, callouts, icon steps, tables, help card), `faqs`, `listing-intro`, `district-eta`, `todo-counts`. |
| Listing header | category.html, proto-listing.js | The h1 and intro paragraph of a category or size come from category-and-age-intros.md (`window.KAYAA_CATALOGUE.intros`); the page also sets its title, description, canonical, robots and breadcrumb per state. |
| `<!-- partial: seo -->`, `tools/seo.json`, `tools/seo.mjs` | every <head> | One source for title, description, canonical, robots, Open Graph, Twitter card and JSON-LD; `"staging": true` forces noindex,nofollow. |
| `tools/check-seo.mjs`, `tools/list-todos.mjs` | tools | Page-level SEO errors and warnings; open content markers by page (`--fail-on-open` before launch). |
| Self-hosted fonts (`assets/fonts`, `assets/css/fonts.css`) | assets | Urbanist and Public Sans, latin variable woff2, font-display: swap, size-adjusted Arial fallbacks; no Google Fonts requests. |
| Content to approve card | review.html | Counts of open markers and a link to review-decisions.html. |
| docs/seo | docs | robots.production.txt, sitemap.example.xml, launch-checklist.md (not published). |

## Payment choice, growth steps and the decisions page (prompt 13)

| Component | Where | Notes |
| --- | --- | --- |
| Payment method cards (`.pay-choice`, `.pay-option`) | checkout.html, pages/checkout.css | A fieldset "Payment method" with two radio cards (`name="payment_method"`, values online and cod), shown only when the admin setting `cod_enabled` is on (prototype: `?demo=cod-on`). The checked card gets a deep-lilac border, a lilac fill and a filled ring (never colour alone); the button label, icon and helper text follow the choice. Off: the single card panel. |
| Payment badge (`.pay-badge`) | components.css | Outline neutral pill with the banknote icon and the text "Pay on delivery"; used on the thank-you, track, order and orders-list pages for a cash order only. |
| Growth steps (`.age-steps`, `.age-tile` with `--step`) | index.html, pages/home.css | Shop by age: nine tiles whose tint deepens with `--step` (0 to 8, from the lightest tone towards Wisteria). 1100px and up: one row, bottom-aligned, each tile 10px taller than the last (150px to 230px). 600 to 1099px: a 3 x 3 grid. Below 600px: scroll-snap row with a peek, hidden scrollbar, right-edge fade and a "See all sizes" link. Height and weight come from the size chart in docs/content/size-guide.md. |
| `tools/check-copy.mjs` | tools | Fails when the cash payment wording appears outside the checkout payment step and the order pages. |
| `tools/build-decisions.mjs`, `html/review-decisions.html` | tools, review | Renders docs/content/01-open-decisions.md as a table (stacked on narrow screens); review only. |

## Size guide slider (prompt 15)

| Component | Where | Notes |
| --- | --- | --- |
| Size guide slider (`.sg`, `.sg__track`, `.sg__slide`, `.sg__media`, `.sg__caption`, `.sg__controls`, `.sg__btn`, `.sg__dots`, `.sg__dot`, `.sg__count`) | pages/size-guide.css, assets/js/size-guide.js, generated by `renderSizeGuideSlider` | Three steps, each a 4:3 illustration on the lilac tint (`object-fit: contain`, never cropped) and an HTML caption. Native scroll-snap track (swipe); previous and next buttons (44px, `aria-disabled` at the ends), dots (44px buttons, `aria-label="Go to step N"`, `aria-current` on the active one), a counter "1 of 3"; ArrowLeft and ArrowRight work inside; no autoplay; reduced motion scrolls instantly; a polite live region announces the new caption. `role="group" aria-roledescription="carousel"`, each slide `role="group"` "1 of 3". Without JavaScript the slides stack and the controls stay hidden. On narrow containers (under 300px, a 320px phone's product panel) the buttons share a row and the dots sit below. |
| Size guide card (`.sg-card`, `.prose--split`) | size-guide.html | "How to find your size" card: under the chart below 1100px; from 1100px the prose becomes two columns (7/5) and the card sits in the right column, sticky under the header (header height + 24px) when the viewport is at least 720px tall, otherwise static. |
| Size guide disclosure (`.sg-disclosure`) | product.html, the size guide panel | Native `<details>` "How to measure your baby" under the panel table with the same slider; its images are lazy, so nothing loads until it is opened. |
| `tools/image-manifest.json` | tools | The files `process-images.mjs` generated; the only files it may delete. |

## Tags and search suggestions (prompt 16)

| Component | Where | Notes |
| --- | --- | --- |
| Tag chip row (`.product__tags`) | product.html, pages/product.css, generated by `gen: product-tags` | A "Tags" label and a `<nav aria-label="Product tags">` chip list linking to `shop.html?tag={slug}`; chips 44px tall, 8px gaps, wrapping; wrapped in `<!-- loop: tags -->`; hidden when a product has no tags (`?demo=no-tags`). No tags on cards. |
| Tag listings | proto-listing.js | `?tag=` on shop and category: H1 "Tagged: {label}", breadcrumb Home > Shop > {label}, noindex,follow with the clean canonical, "Clear filters" drops it, the size chips keep it. |
| Search suggestion panel (`.suggest`, `.suggest__opt`, `.suggest__box`) | suggest.css, search-suggest.js, search-core.js | A WAI-ARIA combobox (list autocomplete) on every `form[data-search]`: the header field, the search page field and the 404 field. Groups Products (5, thumb, name with the matched text bold, category, price and compare-at), Categories (3, counts), Tags (3, counts) and a last row "See all results for “q”"; "Popular" links when the field is empty; a no-match state with category chips. Desktop: the panel hangs under the field, 480px at most, 20px radius, above the header, scrolls inside. Below 900px the search page shows it inline, full width, 48px rows, and hides the results while open. Minimum 2 characters, 120ms debounce, stale requests aborted, spinner and `aria-busy`, clear button (44px), Escape closes then clears, closes when the mega menu opens. Rows are built with `textContent`. |
| "Also matches" row (`.also-matches`) | search.html | Categories and tags that match the query, as chips above the results. |
| Shell variants | tools/shell-map.json | See the shell variants entry above. |

### Assumptions
- The size chart in docs/content/size-guide.md replaces the earlier placeholder ranges, on the size guide and on the product page.
- The delivery estimates table shows the two zones from the site config; only the Colombo district counts as "Colombo and suburbs" until the backend supplies per-district data.
- Canonical URLs use https://example.com until the live domain is known.

### Breakpoints in em (footer, search suggestions)
`footer.css` and `suggest.css` (and the matching `matchMedia` in `search-suggest.js`) use em breakpoints (`56.25em` and `56.1875em` = 900px and 899px at the normal text size), so a larger browser text size moves the layout break with it. The only px breakpoint left in the footer is the one that follows the tab bar (layout.css, still px). While the phone suggestion panel is open on the search page, the results, the toolbar, "Also matches" and the "Not sure about the size?" card are hidden and return when it closes.

## Menu round (mobile drawer and desktop mega menu)

| Component | Where | Notes |
| --- | --- | --- |
| Menu drawer head | menu-drawer.html, layout.css | Lilac (`--secondary`) band, 34px wordmark ("menu" is visually hidden for screen readers) and a white round close button. The cart drawer keeps its plain head. |
| Menu rows | layout.css `.menu-group a` | The 44px `min-height` and the 8px gap are gone: line-height 1.5 plus 8px above and below = 40px rows with no gap. Below the 44px tap guideline on purpose (client request); still above WCAG 2.2's 24px minimum. |
| Group headings | layout.css `.menu-group__title`, `.mega__title` | One style everywhere: 14px, weight 800, uppercase, 0.08em tracking, Primary Deep, with a hairline above each group (drawer) or below the heading (mega menu). |
| Folded groups (`.menu-group--fold`) | menu-drawer.html | "Shop by category" (first) and "Shop by age" are native `<details>` (closed by default, work without JS): the heading is the summary, with a chevron that turns. The first screen is the main links and Account. The Help links are not in the drawer any more (they are in the footer). |
| Sizes in menus | catalogue.mjs `size-links-drawer`, `size-links-mega` | Plain text links like every other row (two to a line in the drawer), no pills. `size-chips-mega` and `cat-chips` stay for the cart, 404 and search "browse" blocks. |
| Mega menu (desktop) | header.html, layout.css | Two text lists (categories first, in one column; "Shop by age" in two columns) under hairline headings instead of pill chips. The "Fresh styles just landed" card with the "Shop new in" link became an editorial image with a caption (`.mega__feature`, shown from 1100px): one link, no card, no button. Placeholder copy still to confirm with the client. |

### Footer and menu follow-ups
The footer's first column is "Shop by category" (the seven categories) instead of the sizes. The art zone is shorter: the clothesline is `max(110px, 12.4vw)` high and the giant wordmark overlaps it on purpose (client request), which replaces the earlier rule that no garment may touch a letter.

### Footer separation
The footer used to fade out of the page with no edge; it now has a wave-cut top edge and a darker first stop. check-footer-seam.mjs and footer-contrast.mjs take their screenshots with a viewport as tall as the page (`settleTall` in tools/qa/lib.mjs), because a full-page capture resizes the viewport and vh-sized sections would move the footer.

## Experiments: wardrobe category carousel (mock-up, prototype only)

Follows the client's reference reel: an open, minimal stage with ONE thin rod, small garments with a lot of air, all seven visible at once on a laptop, the hovered piece grows to about 1.5x in front of its neighbours, and a small caption with a pill button sits in open space under it (no card over the garments).

| Component | Where | Notes |
| --- | --- | --- |
| `html/mockups/wardrobe.html`, `wardrobe.css`, `wardrobe.js` | mockups/ | Standalone mock-up that would replace the Home "Shop by category" tiles if approved. Shell variant `none` (stub header with the wordmark and "Back to Home"), noindex, a noindex entry in tools/seo.json, listed on review.html under "Experiments (prototype only)". No existing page or shared partial is touched. Delete the whole folder (and the `wardrobe-items` and `wardrobe-ticker` cases in tools/catalogue.mjs, the shell-map and seo entries, the review.html group, the check-css/check-seo/list-todos allowances and the lighthouse.mjs entry) before launch unless approved. How to swap the drawings for photographs: docs/wardrobe-photo-guide.md. |
| Stage | wardrobe.css | 28px-radius container, pale wall `#F4EEF8` with very faint panel lines, a light from the top, a soft floor shadow, no top board. 500px high on desktop, 460px on phones (both grow with the text size: `--h` and `--capzone` add 5px per 1px of extra font size, so a larger text never overflows or covers a garment). |
| Rod and hooks | wardrobe.css | A thin metal rod (`--rail-r` 4px: plum-grey gradient, 1px highlight, soft wall shadow) on a constant centre line (`--rail-y`), with a cylinder cap at each end. From 1100px with "Items in view: Auto" all seven fit between 12% margins (the rod spans 76% of the stage); otherwise the rod runs the whole scrollable width. Every hook is a ring of rod radius + 2.5px (6.5px) drawn in CSS pixels. Each item has two layers sharing the ring centre as their pivot: the BACK half of the ring (z 1, behind the rod), the rod (z 2) and the FRONT layer (z 3: front half of the ring, stem, hanger, garment; z 6 when active). The items have no stacking context of their own, so the rod can sit between the layers. Every transform has its origin at the ring centre, so the hook never leaves the rod (tested within 0.5px at rest, while hovering, swinging, scaling and pulling out, and in the intro: it only ever rises above the line). The rod ignores the pointer. |
| Shared motion values | wardrobe.css (`@property`) | `--swing`, `--lag`, `--ry`, `--slide`, `--dy`, `--tz`, `--zs`, `--dim`, `--ds`, `--cap-x` are registered, so items and the caption animate or transition them and both layers read the same values. A browser without registered-property animation still shows every state, just without the in-between motion. |
| Replaceable artwork (`.art`) | wardrobe.html, `gen: wardrobe-items` | One element per item: an inline SVG today, a transparent WebP or AVIF cut-out later. Its size (`--aw`, `--ah` in cells), the hook point inside it (`--hx`, `--hy` as fractions), the wall-shadow offset (`--sx`, `--sy`) and the hover scale (`--hs`) are per-item CSS custom properties, never script. Garments differ in length and width (the SVG uses `preserveAspectRatio="none"`): about 130 to 160px from the hook to the hem at 1280px, the Newborn smallest, the Sleepwear tallest, the cloth nappy about 70%. |
| Hangers and garments | wardrobe.html (`<symbol>`) | Plastic hangers drawn as plum-outlined white tubes with curved shoulder arms and a neck stem (standard, small for the Newborn, a straight clip bar for Napkins, a mini hanger with a clip arm for Accessories). Each garment has a BACK symbol (`wd-b-{slug}`: the darker inside of the back neck, the Set's trousers, the cardigan's inner top, the mittens) and a FRONT symbol (`wd-g-{slug}`) drawn over the hanger arms: the shoulder seam follows the arms with a small bulge, the stem shows through the neckline, with a soft same-hue gradient on the fabric, 2 to 3 fold lines, a contact shadow under the arms and uneven hems. Newborn: small bodysuit with a star. Bodysuits: three snaps. Sleepwear: drooping footed sleepsuit with zip and moon. Sets: top on a shaped hanger, trousers clipped on a bar below. Outerwear: cardigan with open front edges, drooping lapels and buttons. Napkins: a folded cloth nappy (triangle, two corner snaps, printed corner) on a clip bar. Accessories: a bib (round neck cut-out, scalloped edge, ties over the hanger arms) with a pair of mittens clipped beside it. `wd-s-{slug}` are the flat grey silhouettes of the photo placeholder. |
| Items | `gen: wardrobe-items` (tools/catalogue.mjs) | Seven `<li>` in catalogue order with a link to `../category.html?c={slug}` and the item's name, details and href as data attributes; counts and size range ("NB–3Y") come from the products; the description is the catalogue blurb (placeholder copy, TODO comment in the page). The link's name is "Bodysuits, 5 items" (a visually hidden span) and `aria-describedby` points at the item's details text, which is the same text the caption shows. No `aria-live`. |
| Caption (`.wardrobe__caption`) | wardrobe.html, wardrobe.css, wardrobe.js | One element in open space under the rod zone (reserved height `--capzone`, 96px): the name in small spaced capitals (600, 0.08em), one line "5 items · NB–24m" and a pill button "Shop Bodysuits →" (a real link to the category, `tabindex="-1"` because the garment itself is the same link). It is centred under the ACTIVE item, clamped inside the stage, and moves with a 200ms ease while fading (opacity and 8px) when the active item changes. The garments are sized in cells and capped (`--cap`) so that the tallest, enlarged to 1.5x, still ends at least 24px above it (measured: at least 21px at 13 widths x 7 items x 2 styles). The pointer can travel from the garment to the button (a 260ms grace and the caption keeps the item active). With nothing active on desktop a "Hover a garment" hint shows and fades after the first interaction. |
| Hover and focus | wardrobe.css / .js | `data-hover="forward"` (default, the reference): the active piece scales to 1.5 about its ring, takes a higher z-index and overlaps its neighbours, which nudge at most 10px along the rod (Neighbours: Nudge, or Still) and dim to 0.9; a damped pendulum kick of about 4deg and a deeper wall shadow, 250 to 400ms with a small overshoot. `"pullout"`: the piece moves 90px toward the viewer in 3D and turns from -12deg to 0 with a small overshoot. Fine pointers: hover and focus-visible. Touch and coarse pointers (carousel): the item nearest the middle is active; the first tap on a side item brings it forward without navigating (it stays chosen until the next touch scroll), a tap on the active item follows the link. |
| Carousel | wardrobe.js / .css | Start-aligned scroll-snap track (3.2 items in view from 48em, 1.7 on phones, 3 or 5 with the mock control); below 1100px, or with 3 or 5 chosen, it scrolls: drag with a mouse (pointer capture only after 6px, so a click stays a click), prev and next buttons, dots with `aria-current` and a visible-range counter ("2–4 of 7"), arrow, Home and End keys. When everything fits, the arrows, dots and counter are hidden. "View all categories" stays underneath. |
| Motion | wardrobe.css / .js | Idle: +-1deg sway about the rod, staggered 5 to 7s, only while on screen. Intro (after the doors, unchanged; off with `?doors=0`): each hanger arrives left to right 90ms apart from 70px above the rod tilted -12deg, drops (ease-in), catches with a 3px bounce, then swings and settles over about 900ms; the garment follows 50ms behind with more angle (`--lag`). Reduced motion: no doors, drop, sway, nudge or swing; the active piece scales to 1.08 with an outline ring and the caption fades in (150ms). Forced colours: CanvasText outlines on every garment, the ring and silhouettes, a border on the caption's button. |
| Mock controls (mock-only) | `<details data-mock-only>` | Hover style (Forward / Pull-out), Intro doors, Idle sway, Neighbours (Nudge / Still), Items in view (Auto, 3, 5), Art (Illustration / Photo placeholder), Ticker (Off / On), Animation speed, Replay intro and Reset; the same as a query string (`?hover=pullout&doors=0&sway=0&neighbours=still&view=3&art=photo&ticker=1&speed=slow`). Nothing is stored. Delete the block and the "mock controls" part of wardrobe.js. |
| Optional ticker (`.wardrobe-ticker`, off by default) | wardrobe.html, `gen: wardrobe-ticker` | A slim lilac strip under the stage with four messages read from tools/site-config.json (free delivery over the threshold, swaps within the return window, secure card payment, island-wide delivery). It scrolls slowly only when motion is allowed, with a visible Pause / Play button (`aria-pressed`, WCAG 2.2.2); with reduced motion it is static and wrapped and the button is not shown. Ink on `#EAE0F2`, 9:1. |
| Tokens | wardrobe.css | `--wardrobe-wall`, `--wardrobe-wall-deep`, `--wardrobe-panel`, `--wardrobe-rail`, `--wardrobe-wood` (lilac, used by the doors), `--wardrobe-shadow`. |
| Weight | | See the report: wardrobe.js about 5.7 KB gzip, wardrobe.css about 7.5 KB gzip, no raster images. |
