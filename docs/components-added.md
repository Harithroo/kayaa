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
| `.topbar` (--lilac / --cream / --sky) | layout.css | Dismiss remembered in sessionStorage. |
| `.site-header`, `.primary-nav`, `.header-search`, `.category-row` | layout.css | See the refinement round below for the Shop mega menu and search. |
| `.drawer`, `.overlay`, `.sheet` | layout.css | Shared layer system in app.js: focus trap, ESC, focus return, scroll lock. |
| `.tabbar` | layout.css | Below 900px only. Active tab = Primary Deep + 3px top indicator. Labels are Ink (Ink Soft is not allowed at 12.5px). |
| `.site-footer` | layout.css | Light, on --secondary. |
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
| `.mega`, `.mega__panel`, `.mega-tile`, `.mega__feature`, `.mega-scrim` | layout.css | Shop mega menu inside the split item (so Tab flows chevron -> panel). Attached to the bottom of the 72px header bar, spans the container width and covers the category row. Hover intent 120ms open / 250ms close, click/Enter/Space toggles, ESC returns focus to the chevron, outside click and focus leaving close it, `inert` + `visibility` while closed. Below 1100px the feature card is hidden so the five age tiles keep a readable width. Scrim is Ink at 8%. |
| `--scrim-light`, `--shift-panel` | tokens.css | Mega backdrop and panel slide distance (zeroed under reduced motion). |
| `.header-search` (rebuilt) | layout.css | 1100px+: 240px pill that grows to 320px on focus. 900-1099px: icon button (`data-search-toggle`) that opens an inline field over the nav; ESC closes and returns focus. Search sits in the right-hand group with account and cart. |
| `.chip-list` | components.css | Moved here from listing.css (used by the mega menu and no-results). |
| `.product-card__quick` | components.css | Two separate rules: touch = 44x44 circle (`border-radius: 50%`); mouse = pill (`--radius-pill`, 11px 22px, 13px bold, `width: max-content`, 16px icon, 8px gap). The 13px is the design-system value and only shows with a mouse. |
| Hover gating | all CSS | Every `:hover` rule now sits inside `@media (hover: hover) and (pointer: fine)` so touch devices never get stuck hover states. |
| `.icon-btn__count` | components.css | Now 12.5px in a 20px badge (was 11px). |
| Scroll lock | layout.css / app.js | `html.is-locked` also locks body and pads by the measured scrollbar width (`--scrollbar-w`) so nothing jumps. |
| `scroll-padding` | base.css / listing.css | Top = `--header-h` + 8px (+76px under the sticky toolbar on mobile); bottom = tab bar + safe area. |
| Footer and menu link lists | layout.css | 8px gap between rows so tap targets never touch. |

### Payment copy
- Online payment only (Onepay, Visa/Mastercard); no cash on delivery anywhere in html/, tools/, docs/ or CLAUDE.md.
- No card or Onepay logos: text and the credit-card icon, with TODO comments to add the official marks and badge.

### Assumptions
- Between 900 and 1099px the mega menu shows the age tiles and category chips without the feature card.
- The announcement bar's focus moves to the wordmark when dismissed, so keyboard users are not dropped at the top of the page.
- The tab bar items touch each other (each is 75px+ wide), so they are treated as one control for the 8px spacing rule.
