# Backend contract (frontend prototype -> Laravel 12 + Filament 4)

Written from what the prototype in `html/` actually contains, so you do not need to open any HTML. Anything the prototype does not show is marked **(assumption)** or **TBD**. The prototype is static: its JS fakes server behaviour (see "Prototype-only behaviour" at the end). Keep this file in sync with the pages (see CLAUDE.md).

Conventions used by every page
- Money is whole rupees, shown as `Rs. 1,490` (with the full stop and a thousands separator; one helper formats it everywhere, prefix from the site config `currency_prefix`). A sale shows the current price and the struck-through compare-at price.
- UI wording: "Order number" (not "reference"), "Sign in", "Sign out", "Create an account", "Sign in / register". Code and this file may keep "reference" as a technical name. The delivery fee and the free-delivery threshold come from the site config (section 3.17), never typed into pages.
- One shared shell (header, footer, drawers, tab bar) wrapped in `<!-- partial: NAME -->` comments. `<!-- loop: ... -->` marks repeated data, `<!-- blade: ... -->` marks conditionals and generated values.
- Order statuses: `pending`, `confirmed`, `shipped`, `delivered`, `cancelled`. Payment statuses: `pending`, `paid`, `failed`, `refunded`.
- Product images are 4:5. Placeholders (`.media[data-placeholder]`) are replaced by `<img>` markup when photos exist.
- All pages carry `<meta name="robots" content="noindex,nofollow">` on staging; remove it at conversion.

---

## 1. What the frontend needs from the backend (new or changed)

1. **Images per colour.** A product image has a nullable colour. Photos with a colour show only when that colour is selected; photos without a colour (shared) show for every colour. A colour with no photos falls back to the shared set. Each image needs an order and, ideally, alt text. The page renders every image with `data-colour="<colour slug>"` or `data-colour="all"`; JS filters them. `?colour=<slug>` must preselect the colour (the page rewrites the URL with `history.replaceState`).
2. **Low-stock threshold** as an admin-editable setting (default 5). Rendered on the buy form as `data-low-stock-threshold="5"`. The stock note says "Only N left" when `1 <= stock <= threshold`.
3. **Product text.** The short description (a plain-text field; the page shows its first ~160 characters at a word boundary, with an ellipsis, above the buy form; use word-boundary truncation, for example a `Str::limit` with `preserveWords`) stays above the form. There is no separate "Description" accordion any more: **Fabric & care** (a composition line and a care list; the sample "95% combed cotton, 5% elastane" is a placeholder) and **Delivery & returns** (every value from the site config) are the accordions. **Question:** is there a full description field, and are composition and care separate fields?
4. **Variants JSON.** Per product page, an inline block `<script type="application/json" id="product-variants">[{"colour":"lilac","size":"3-6m","stock":4}, ...]</script>`: one row per colour+size variant, `colour` = colour slug, `size` = **size slug** (the size radio value, for example `3-6m`; the box shows the full label "3–6m"). Sizes are rendered in size-scale order.
5. **Review "show more".** The product page shows 5 reviews and a "See more reviews" control that reveals 5 more in place. Prototype: all reviews are in the HTML and JS reveals them. Production options (pick one): (a) render all approved reviews and let JS reveal them, or (b) an endpoint that returns the next page of review-card HTML, like `/cart/panel` (assumption: `GET /products/{slug}/reviews?page=2` returning `<li class="review-card">...</li>` items plus the new "Showing N of M" count). Either way the no-JS fallback is a plain link to the same product page with `?reviews=all#reviews`, which must render every approved review. There is no separate reviews page.
6. **Rating average with decimals** (one decimal, e.g. `4.8`) plus review count and a 5-to-1 breakdown (counts). Stars: whole part = full stars; any fraction .1 to .9 adds one half star (4.0 = 4 stars, 4.1 to 4.9 = 4.5 stars). Individual review ratings are whole numbers 1 to 5.
7. **Onepay payment flow.** Payment is online only, through the Onepay gateway (Visa/Mastercard). Assumption: checkout redirects to Onepay's hosted payment page. Orders start with `payment_status = pending` until the gateway confirms. The frontend has designed the paid, pending, failed, cancelled and expired states and "Resume payment"; the return URL, callback and resume route are needed (TBD). Section 3.6.
8. **Delivery ETA** is shown at checkout (after the district is chosen), in the order summary, on the thank-you page, on the track result and the order page while the order can still arrive, and on the delivery page. The backend supplies **two integers per district (`min_days`, `max_days`)**; the wording is made by the frontend helper `Kayaa.formatEta` (section 3.5). The product page and the cart show no estimate.
9. **Categories (department "Baby", route `/baby/{slug}`):** `newborn` Newborn, `bodysuits` Bodysuits, `sleepwear` Sleepwear, `sets` Sets, `outerwear` Outerwear, `napkins` Napkins, `accessories` Accessories. Each has a name, slug, one-line description (shown in the listing header band; placeholder) and a photo (home tile). The prototype keeps `category.html?c={slug}` for `/baby/{slug}`. The staging site shows these seven; they replace the six categories the prototype first invented.
10. **Admin-managed shell content:** announcement bar (`topbar`: text, style `lilac|cream|sky`, enabled), home promo banners (`home_promo`: style `lilac|sky`, eyebrow, headline, text, button label and URL, optional photo), WhatsApp number/link, contact email, social URLs.
11. **Sizes ("Shop by age")** are fixed: nine size slugs `newborn`, `0-3m`, `3-6m`, `6-9m`, `9-12m`, `12-18m`, `18-24m`, `2y`, `3y`, with the labels Newborn, 0–3m, 3–6m, 6–9m, 9–12m, 12–18m, 18–24m, 2Y, 3Y (en dash, lowercase m). Param `size`. Product-to-size mapping is needed: a product covers a range of sizes. See section 2.
12. **Product flags:** `new`, `featured`, sale price (`was` price = regular, current = sale). Badge priority on cards: Out of stock > Sale > New > Featured (one badge max).
13. **FAQs** from product, category and global FAQs (the product page shows five placeholder questions). TBD: how they are merged and ordered.
14. **Size guide data**: one global table (size, age, height, weight) for the nine sizes, shown on `size-guide.html` and mirrored on the product page; placeholder ranges, confirm with the client. The brand sizes by age band; height and weight are the better guide. Per-product tables are not designed (section 3.13).
15. **Cart drawer endpoints** (see section 4). The prototype does quantity changes and removal client-side; they need real endpoints.
16. **Related products:** same category as the current product, excluding the current product, 4 items.
17. **Fabric & care and Delivery & returns** accordions are static site-wide text (placeholders). TBD whether they become settings.
18. **SEO:** `<title>` `"<Name> | Kayaa"`, meta description, canonical URL, and a JSON-LD `Product` block (name, image, description, category, offers with `priceCurrency: "LKR"`, `price`, `availability`, `aggregateRating`). The prototype has placeholder values and a `<!-- blade: generate from the product -->` comment.

19. **Cart line data** with per-variant stock, the sale/regular price, the "price changed" flag and the colour's thumbnail (section 3.4).
20. **Flat delivery fee rule from config** (`shipping_fee`, `free_shipping_over`; section 3.17). TODO confirm it is not district-based.
21. **Checkout** fields, validation (email optional, mobile required, no postal code), the 25-district list from `config/kayaa.php` and the delivery-ETA data per district as `min_days`/`max_days` (section 3.5).
22. **Payment hand-off:** order creation, **permanent stock decrement at placement (nothing is held)**, **cart emptied server-side when the order is created**, redirect to Onepay, signed return link, callback, "Resume payment", the outcomes paid, pending, failed, cancelled and expired (sections 3.6 and 3.7).
23. **Order reference format `KY-YYMMDD-XXXX`**: uppercase, max 20 characters, never digits only or a fixed length (example `KY-261003-A3F9`). **The thank-you page is reached only through a signed, expiring link supplied by the backend** and is never built from the reference (section 3.7).
24. **Status component mapping** for order and payment statuses (section 3.8).
25. **Order confirmation emails are off by default** (`order_emails_enabled: false`). The thank-you page shows "A confirmation has been sent to {email}" only when the flag is true and the order has an email (section 3.6).

26. **Optional accounts.** Guest checkout and /track always work. Customers and admins share the `users` table; register collects name, email, mobile (`users.phone`) and password (at least 8 characters, no complexity rules, no maximum). **Email is optional for guests (nullable on the order); the mobile number is the required identifier** (section 3.10).
27. **`User::allOrders`**: orders with the customer's `user_id` plus earlier guest orders placed with the same email, **attached only after the account email is verified** (the account itself works unverified; section 3.11).
28. **Track lookup by order reference AND mobile number, both required and matched together**; it exposes only status, payment status, items, totals, last-updated date and the delivery estimate, limited to 20 requests a minute. `?ref=` prefills the reference only and never runs the lookup (section 3.9).
29. **Auth rate limits and safe responses:** login, register, forgot and reset are rate-limited; login errors never say which field was wrong; forgot-password never reveals whether an email exists; reset emails go through the configured mailer (section 3.10).
30. **Review statuses for the customer:** one `approved` flag, so "Awaiting approval" covers pending and hidden; admins can reply; one review per customer per product (section 3.11).
31. **Stepper dates:** one date per step when known (placed; paid or confirmed; shipped; delivered) and none when a step has no date; the cancelled banner shows its date. **The backend adds `delivered_at` and `cancelled_at`**; `confirmed_at` is an open question (sections 3.9 and 3.11).

32. **Contact form endpoint**, limited to 5 messages a minute, with an open question about where messages go and spam protection (section 3.14).
33. **Delivery page estimates** from the same data as the checkout ETA (25 districts grouped by 9 provinces): one source (section 3.13).
34. **FAQs with placement `contact`** (the contact page shows four).
35. **Error views** `resources/views/errors/{404,419,429,500,503}.blade.php`; 500 and 503 must not touch the database or session (section 3.15).
36. **Static or editable content:** the client decides whether About, Delivery, Returns, Privacy and Terms get an admin editor.

37. **Site config** (`tools/site-config.json`): `shipping_fee`, `free_shipping_over`, `pay_button_label`, `order_emails_enabled`, `remember_days`, `low_stock_threshold`. Blade prints these as view variables instead of the `data-cfg` hooks and `window.KAYAA_CONFIG` (section 3.17).
38. **Email verification** (`/account/verify-email`, a signed verification link, a resend route) and a verification banner on every signed-in account page while unverified (section 3.10).
39. **Cancel order** from the account order page while the status is `pending` (`POST /account/orders/{ref}/cancel`, TODO route), with the refund line when the payment is `paid` (section 3.11).
40. **Resume payment** for an existing order (pending or failed payment, online gateway, not cancelled): account order page, track result and the thank-you failed state (section 3.6).

41. **`/categories` page** listing the seven categories with item counts (section 3.18).
42. **Wishlist** (`/wishlist`, a Save toggle on the product page): needs storage; guest wishlists (browser or session) are an open question (section 3.19).
43. **Review fields:** rating, name, email (not shown), optional headline, review text; reviews are moderated; the headline shows in bold on the card when present (section 3.3).
44. **Contact fields:** name, mobile or email (one field), optional order number, message (section 3.14).
45. **Site config keys** added: `delivery_colombo_days`, `delivery_island_days`, `dispatch_cutoff`, `return_window_days`, `refund_days`, `faulty_report_days`, `support_hours`, `whatsapp_display`, `currency_prefix`; `pay_button_label` is now "Pay now" (section 3.17).
46. **District ETA** is seeded with two zones as `{min, max}`: Colombo and suburbs 1–2 working days, the rest of the island 2–4 (TODO until per-district data arrives).
47. **Product fields observed on staging** (section 3.20).

---

## 2. Shell (every page)

### Announcement bar (admin "topbar")
- Data: text (prototype: "Island-wide delivery in 2–4 days" + " · Free over Rs. 7,500", both from the site config), style `lilac|cream|sky`, enabled.
- Dismiss hides it for the current page view only (nothing stored); it returns on every load.

### Header, menus, footer
- Logo/wordmark -> `/`. Primary nav: Shop (`/shop`, with a mega menu), Categories (`/categories`), New in (`/shop?sort=new`), Sale (`/shop?sale=1`), Size guide (`/size-guide`), Our story (`/about`).
- Mega menu: "Shop by size" as a pill grid of the nine sizes (`/shop?size=<slug>`) and "Browse by category" chips for the seven categories (`/baby/{slug}`; prototype `category.html?c=<slug>`), plus a "New this week" feature card -> `/shop?sort=new` (placeholder copy, TBD).
- Category row (desktop): the seven categories. Utility group (desktop): search, **wishlist heart** (`/wishlist`), account (`/account` when signed in, else `/account/login`), cart (opens the cart drawer; the count is the sum of line quantities). Below 900px the mobile drawer carries Categories and Wishlist, sizes as a pill grid, categories as rows, and "Sign in / register".
- Footer: tagline "Soft, honest clothing made for Sri Lankan weather.", "Colombo, Sri Lanka" (TODO confirm), "Shop by size" (nine links), help links (`/track`, `/wishlist`, `/size-guide`, `/delivery`, `/returns`, `/contact`), company links (`/categories`, `/about`, `/privacy`, `/terms`), WhatsApp link, contact email, social URLs, and "Secure card payments" (no gateway name).
- Active nav states use `<body data-page="home|shop|category|product|search|cart|account|...">`; the server should set it per page (space-separated tokens allowed, e.g. `account login`).

### Minimal shell (checkout and thank-you)
These two pages use `header-minimal` and `footer-minimal` partials only: no announcement bar, menu drawer, tab bar or cart drawer.

### Header search form
| | |
| --- | --- |
| Route | `GET /search` |
| Field | `q` (type `search`, optional on submit, example `romper`) |

### Categories (department "Baby", fixed list)
`newborn` Newborn, `bodysuits` Bodysuits, `sleepwear` Sleepwear, `sets` Sets, `outerwear` Outerwear, `napkins` Napkins, `accessories` Accessories (the home tile photo slots are `cat-<slug>`). **Question:** what are "Napkins" (cloth nappies, mealtime napkins?) and what size range do they have?

### Sizes (fixed list; ASCII slugs in the URL)
| Slug | Label |
| --- | --- |
| `newborn` | Newborn |
| `0-3m` | 0–3m |
| `3-6m` | 3–6m |
| `6-9m` | 6–9m |
| `9-12m` | 9–12m |
| `12-18m` | 12–18m |
| `18-24m` | 18–24m |
| `2y` | 2Y |
| `3y` | 3Y |
The labels use an **en dash and a lowercase m**. The URL uses the ASCII slug (`?size=0-3m`); the prototype also accepts the old encoded form `?size=0%E2%80%933m` and normalises it to the slug. **Question:** can the backend's size URLs use slugs instead of the encoded en dash?
Product cards show the range of sizes in short form (Newborn = NB): "4 colours · NB–18m".

---

## 3. Pages

Route map (prototype file -> Laravel route). Status: **built** = designed in the prototype, **stub** = placeholder page only, nothing designed yet.

| Prototype file | Laravel route | Status |
| --- | --- | --- |
| index.html | `/` | built |
| shop.html | `/shop` | built |
| category.html | `/{department}/{category}` | built (prototype uses `?c=<slug>`) |
| search.html | `/search?q=` | built |
| product.html | `/products/{slug}` | built |
| cart.html | `/cart` | built |
| checkout.html | `/checkout` | built (minimal shell) |
| thank-you.html | `/orders/{ref}/thank-you` | built (minimal shell) |
| proto-onepay.html | none | prototype stand-in for the hosted payment page, delete at conversion |
| track.html | `/track` | built |
| contact.html | `/contact` | built |
| size-guide.html, delivery.html, returns.html, about.html, privacy.html, terms.html | same names | built (content template; all facts are placeholders) |
| 404.html, 419.html, 429.html, 500.html, 503.html | error views `resources/views/errors/*.blade.php` | built |
| account/register, login, forgot-password, reset-password | `/account/register`, `/account/login`, `/account/forgot-password`, `/account/reset-password` | built (calm minimal shell) |
| account/index (orders), order, reviews, profile | `/account`, `/account/orders/{ref}`, `/account/reviews`, `/account/profile` | built |
| categories.html | `/categories` | built |
| wishlist.html | `/wishlist` | built |
| review.html | none | review-only index, delete at conversion |

### 3.1 Home (`/`)
- No query params, no forms (the header search is the only form).
- Data: hero (static copy + one photo; H1 "Soft cottons for the first two years.", lede "Breathable cotton and bamboo, cut for Sri Lankan weather. Sized by age, so you order once and it fits." TODO: the fabric claims need client confirmation), trust strip (four static items: "Swap sizes free" with "Exchange within 14 days, unworn" (days from the site config); "Tested for sensitive skin" with "OEKO-TEX certified cotton" (**TODO: needs certificate evidence before launch; never present it as verified**); "Free delivery over Rs. 7,500" with "Island-wide by courier"; "Secure card payment" with "Visa & Mastercard accepted"), "Shop by age" tiles (nine, link `/shop?size=<slug>`, scroll sideways), category tiles (seven, each with a photo and label, link to the category), "New this week" (4 products, newest first, link "View all" `/shop?sort=new`), promo banners (`home_promo`, 2), "Featured products" (8 products with the `featured` flag, button "Shop all" `/shop`).
- Product card data: name, URL, current price, compare-at price when on sale (struck through), **one badge (Sale > New)**, out-of-stock flag, photo, and the meta line "{N} colours · {min}–{max}" (colour count and the size range, for example "4 colours · NB–18m"). The colour dots are not used: **add them back only if the backend supplies colour hex values** (see Questions).
- States: out-of-stock card (muted, no quick add). Empty sections are not designed (assumption: hide the section).

### 3.2 Listing pages: Shop, Category, Search
One shared template. Everything is driven by the query string and works without JS (filters are a real GET form plus plain links). The prototype fakes the filtering with `assets/js/proto-listing.js`; the server does it in Laravel.

Routes: `GET /shop`, `GET /{department}/{category}` (prototype `category.html?c=<slug>`), `GET /search`.

| Param | Where | Allowed values | Notes |
| --- | --- | --- | --- |
| `size` | all | `newborn`, `0-3m`, `3-6m`, `6-9m`, `9-12m`, `12-18m`, `18-24m`, `2y`, `3y` | Invalid value ignored (= all sizes). The old encoded form `0%E2%80%933m` is accepted and normalised. Not shown on Search (no size chips there). |
| `sort` | all | `featured` (default), `new`, `price-asc`, `price-desc` | `new` is a sort (newest first), not a filter. Price directions are TBD with the backend dev. |
| `sale` | all | `1` | Only products with a sale price. |
| `c` | category only | category slug | Prototype stand-in for the route segment. Unknown slug -> "Category not found" with the empty state. No `c` behaves like Shop. |
| `q` | search only | free text | Prototype matches every word against product name and category name, case-insensitively (assumption). Empty `q` shows the search box and the browse links. |
| `page` | all | integer >= 1 | 12 products per page. Out-of-range pages clamp to the last page. |

Form (the toolbar): `GET` to the same page.
| Field | Type | Values | Notes |
| --- | --- | --- | --- |
| `sort` | select | the four sorts above | Auto-submits on change; a Noscript Apply button exists. |
| `sale` | checkbox (`role="switch"`) | `1` | Unchecked sends nothing. |
| `size`, `c`, `q` | hidden | current values | Only rendered when set, so the other filters survive a submit. `page` is dropped on submit. |

Links: size chips ("All ages" + the nine sizes) keep `sort`, `sale`, `c`, `q` and drop `page`; they also show on category pages. Pagination links keep every other param. "Clear filters" removes `size`, `sort`, `sale` and `page` but keeps `c` and `q`.

H1 and intro by state (precedence): Search: `Results for "<q>"` (empty q: "Search"); Category: category name + its one-line description; size: "Size 0–3m" ("Newborn clothing" for newborn); `sale=1`: "Sale"; `sort=new`: "New in"; default: "All baby clothing". With size or category plus `sale`, the intro adds "Showing sale items only." Document title is `"<H1> | Kayaa"`. Breadcrumb: Shop: Home / Shop; Category: **Home / Baby / Category** (Baby links to `/categories`); Search: Home / Search. The sort values and the page size (12) are to be confirmed (see Questions). Sale is a filter (`sale=1`) in the prototype.

Data per page: products (page of 12) with the card data from 3.1, total count (band shows "N products", toolbar shows "Showing 1-12 of 24"), page count.

States
- Empty (no products): package icon, "No products match these filters", "Clear filters" button.
- Search no results: `No results for "<q>"`, three tips, links to the seven categories and the nine sizes. Empty `q` shows the same block titled "What are you looking for?".
- Pagination: Prev / numbers / Next (ellipsis beyond 7 pages); under 600px it shows Prev / "Page N of M" / Next. Hidden when there is one page.

### 3.3 Product (`/products/{slug}`)

Query params: `colour` (colour slug; preselects the colour and its photos; invalid values ignored), `reviews=all` (no-JS fallback: render every approved review). Prototype only: `demo` (see the end of this file).

**Add-to-cart form** - `POST` (action TODO, the prototype posts to `cart.html`; assumption: `POST /cart/items`), `@csrf`. Field names are a TODO to confirm with the backend dev; a single variant id is the alternative.
| Field | Type | Required | Values / validation | Example |
| --- | --- | --- | --- | --- |
| `colour` | radio | yes | a colour slug of this product; one preselected | `lilac` |
| `size` | radio | yes | **size slug** of an in-stock variant for the chosen colour (the box shows the full label); out-of-stock variants render as disabled radios (+ sr-only "(out of stock)") | `3-6m` |
| `quantity` | text, numeric | yes | integer 1 to 10, and at most the variant's stock | `1` |
| product id | hidden | yes | (assumption) the product id or slug | |

Form attributes the page reads: `data-product-name`, `data-unit-price` (current price in rupees, sale price when on sale), `data-low-stock-threshold` (admin setting). Client behaviour: if no size is chosen the form does not submit and shows "Please choose a size". With every variant out of stock the button is disabled and reads "Out of stock".

**Variants JSON**: see section 1.4.

**Data the page needs:** name, slug, eyebrow ("Kayaa Essentials": TODO the source field), category (name, slug), price and sale price (compare-at), flags (`new`, `featured`), short description, composition and care (see section 1.3), colours (name, slug; swatch colour only if supplied), sizes in scale order, variants (stock), images (url, alt, order, nullable colour), rating average (1 decimal), review count and 5-to-1 breakdown, approved reviews (author name, date, rating 1 to 5, optional headline, text, verified-purchase flag, optional admin reply), FAQs, related products (section 1.16: **"Goes well with"**, 4 items from the same category), the user's "already reviewed" flag, the wishlist state.

**Badge** (top-left of the gallery, one max): Sale > New. **Buy area:** the colour legend reads "Colour — Butter" (the selected colour); the stock note reads "In stock · ships from Colombo" (TODO confirm), "Only N left" at or under the threshold; buttons "Add to cart" and a secondary **"Save"** (heart icon, toggles to "Saved", `aria-pressed`; the wishlist is in sessionStorage in the prototype); under them "Order before 2pm for same-day dispatch" (TODO confirm; site config `dispatch_cutoff`).

**Reviews section** (`#reviews`): average, stars, "Based on N reviews", breakdown rows, "Write a review" button (anchor to `#write-review`), review cards (stars, name, date, optional "Verified purchase", the **headline in bold when present**, text, optional "Reply from Kayaa"), "Showing 5 of 12" count and "See more reviews" (section 1.5). Empty state: "No reviews yet — be the first to tell other parents how it fits." with a button to the form.

**Review form** (`#write-review`) - `POST` (action TODO; assumption: `POST /products/{slug}/reviews`), `@csrf`.
| Field | Type | Required | Validation | Example |
| --- | --- | --- | --- | --- |
| `rating` | radio 1 to 5 (rendered as stars) | yes | integer 1 to 5 | `5` |
| `name` | text | yes, for guests only (hide when signed in) | non-empty string | `Amaya R.` |
| `email` | email, `autocomplete="email"` | yes, for guests only; **not shown on the page** | valid email | `amaya@example.com` |
| `headline` | text, `maxlength="100"` | no | optional short title, shown in bold on the review card | `Soft and true to size` |
| `body` | textarea | yes | the review text; non-empty (max length TBD). Field names are TODO with the backend dev | `Lovely and soft.` |

Helper under the text: "Reviews are checked by us before they appear." Signed-out note: "Sign in to have your review marked as a verified purchase." Reviews are moderated: show "Thank you - your review will appear once it has been approved." States: field errors (message per field, `aria-invalid`, plus a summary alert "Please check your review" listing the errors), success alert, rate-limit alert "Too many attempts. Please wait a minute and try again.", and, for signed-in users who already reviewed, the form is replaced by "You've already reviewed this product."

Other: the size guide panel is a static placeholder table of the nine sizes (age, height, weight; section 1.14); the assurance list is static (free delivery over Rs. 7,500; secure card payment, Visa & Mastercard; easy returns, window TBD). The accordions are "Fabric & care" and "Delivery & returns" (section 1.3). Related products and FAQs: sections 1.13 and 1.16.

### 3.4 Cart (`/cart`)
Replaces the stub. No query params. Prototype-only `?demo=` (`empty`, `oos-line`, `low-stock`, `price-changed`, `free-delivery`, `checkout-oos`). Full shell (header, tab bar, drawers).

Data: the cart lines and totals. Each line: product name and URL, colour (slug + label), size (key + label), unit price, regular price when on sale, quantity, current stock of that variant, thumbnail (the selected colour's first photo), availability, "price changed since added" flag. Totals: subtotal (available lines only), delivery fee, total, units count, "Rs X away from free delivery".

Rules (config, TODO confirm the delivery fee is flat and not district-based): delivery is Rs. 450 below a Rs. 7,500 subtotal and free at Rs. 7,500 or more. Quantity per line is 1 to 10 and never above the variant's stock.

Forms (real forms, so the page works without JS; `@csrf`; actions are TODO, assumptions shown):
| Form | Method / action | Fields |
| --- | --- | --- |
| Update quantity (one per line) | `POST` + `_method=PATCH` -> `/cart/items/{line}` | `line` (hidden, the line id), `quantity` (text, numeric, 1 to 10, at most the stock). A `<noscript>` "Update" button is shown; with JS the stepper submits on change. |
| Remove (one per line) | `POST` + `_method=DELETE` -> `/cart/items/{line}` | `line` (hidden) |
| Checkout | link `GET /checkout` | none. Disabled (`aria-disabled`) while any line is unavailable, with the hint "Remove the unavailable item to continue to checkout." |

Line states: "Only N left" caption when `stock <= low-stock threshold` (same admin setting as the product page, `data-low-stock-threshold` on the page); "No longer available" (muted line, disabled stepper and a message; checkout blocked until removed); a quiet info alert "The price of this item changed." Top alert slot (`role="alert"`): "Some items in your bag are no longer available. We've updated your bag." - shown when checkout redirects back to the cart because stock ran out (assumption: a flash message).

Empty state: icon, "Your bag is empty", "Start shopping" -> `/shop`, chips for the five age groups.

Mobile: a sticky bar (total + Checkout) shows when the main Checkout button leaves the viewport and replaces the tab bar (same component as the product page).

### 3.5 Checkout (`/checkout`)
Minimal shell (reduces abandonment): `header-minimal` (wordmark, "Secure checkout" with a lock icon, "Back to bag" -> `/cart`) and `footer-minimal` (Delivery, Returns, Privacy, Terms, Contact). No menu drawer, tab bar, cart drawer or announcement bar. Prototype-only `?demo=`: `errors`, `throttle`, `gateway-error`, `signed-in`, `empty`.

Empty bag: "Your bag is empty" with a link to `/shop` (the server should redirect or render this).

Layout: desktop 7/5 columns (form | sticky order summary). Mobile: a native `<details>` "Order summary · N items · Rs X" at the top, then the form. Summary: compact lines (thumbnail, name, size/colour, quantity, price), subtotal, delivery, total, "Edit bag" -> `/cart`, and the delivery estimate once the district is known.

Form: `POST` (action TODO; assumption `POST /checkout` creates the order, then the server redirects to Onepay), `@csrf`, `novalidate`. The server must repeat every validation rule below. Throttle: 10 attempts per minute (TODO confirm).
| Field (`name`) | Type | Required | Validation | Example |
| --- | --- | --- | --- | --- |
| `email` | `email` (`autocomplete="email"`) | **no** (label "Email (optional)"; nullable on the order) | valid email when given. Helper: "If you create an account later with this email, you'll find this order there." | `amaya@example.com` |
| `phone` | `tel` (`autocomplete="tel"`) | yes: **the required identifier, and what `/track` matches on** | Sri Lankan mobile: `07X XXXXXXX` (10 digits, X in 0-8) or `+94 7X XXXXXXX`, spaces/dashes allowed; **normalise on the server** (`071 234 5678`, `0712345678` and `+94 71 234 5678` are the same number). Helper: "We use your mobile number to deliver your order and to track it." | `071 234 5678` |
| `name` | text (`autocomplete="name"`) | yes | non-empty | `Amaya Ranasinghe` |
| `address_line1` | text (`address-line1`) | yes | non-empty | `42 Temple Road` |
| `address_line2` | text (`address-line2`) | no | | `Apartment 3` |
| `city` | text (`address-level2`) | yes | non-empty | `Nugegoda` |
| `district` | select | yes | one of the 25 districts from `config/kayaa.php`; the option value is the slug | `colombo` |
| `notes` | textarea | no | free text ("delivery notes") | `Call on arrival` |

**There is no postal code** (decided), promo code or gift option. Districts (slug): `ampara`, `anuradhapura`, `badulla`, `batticaloa`, `colombo`, `galle`, `gampaha`, `hambantota`, `jaffna`, `kalutara`, `kandy`, `kegalle`, `kilinochchi`, `kurunegala`, `mannar`, `matale`, `matara`, `monaragala`, `mullaitivu`, `nuwara-eliya`, `polonnaruwa`, `puttalam`, `ratnapura`, `trincomalee`, `vavuniya`.

**Delivery estimate:** shown only here (and in the order summary, on the thank-you page, on the track result and the order page while the order can still arrive), after the district is chosen: "Estimated delivery to Colombo: 2–3 working days". The backend supplies **two integers per district (`min_days`, `max_days`)**; the prototype reads `<script type="application/json" id="district-eta">` mapping the district slug to `{ "min": 2, "max": 3 }` (placeholder numbers). **The wording belongs to the frontend** and lives in one helper, `Kayaa.formatEta(min, max)` in `app.js`: equal values `3 working days`, `1` and `1` `1 working day`, otherwise `2–4 working days` (en dash). Blade can reproduce those rules in one view helper. The delivery page (`/delivery`) shows the same data (section 3.13). Never shown on the product page or the cart.

Contact states: guests see "Have an account? Sign in" (`/account/login`); signed-in users see their fields prefilled and "Signed in as {email} - Not you?".

Payment card (no radio cards, online only): credit-card icon, "Pay by card - Visa or Mastercard", "You'll be taken to Onepay's secure page to enter your card details. We never see or store your card number." (TODO confirm hosted redirect), plain-text Visa and Mastercard badges (TODO official marks and Onepay badge), and "By paying you agree to our Terms and Returns policy" (`/terms`, `/returns`).

**Pay button:** the label comes from config (`pay_button_label`, default "Pay now"; `<span data-cfg="pay_button_label">`). **It never contains an amount** (the total is in the summary). The flow is checkout "Pay now" -> payment gateway -> order confirmed. Lock icon and the helper "Secure payment via Onepay" stay. On a valid submit the button is disabled with `aria-busy="true"`, shows a spinner and "Redirecting to secure payment..." and cannot be submitted twice.

States: field errors (message under each field, `aria-invalid`, `aria-describedby`), an error summary at the top (`role="alert"`, focus moves to it, links to each invalid field), throttle alert ("Too many attempts. Please wait a minute and try again."), gateway error alert ("We couldn't start the payment. Please try again."), stock-ran-out redirect to `/cart`.

### 3.6 Payment hand-off (Onepay) - assumptions, TODO to confirm with the backend dev
Payment is online only, through the Onepay gateway (Visa/Mastercard).
1. `POST /checkout` validates, creates the order with `payment_status = pending` and `status = pending`, **decrements stock permanently at placement (it holds nothing and releases nothing)**, generates the order reference, **empties the cart server-side**, and responds with a redirect to Onepay's hosted payment page. We never handle card numbers.
2. Onepay redirects the shopper back to the thank-you page through **a signed, expiring link supplied by the backend** (return URL) and also calls the server (callback/webhook) to confirm the result; the server sets `payment_status` to `paid`, `failed` (or leaves `pending`). The thank-you page must show the state the server knows, not a state taken from the URL.
3. Outcomes the frontend has designed: **paid**, **pending** (gateway still confirming), **failed**, **cancelled** (order status `cancelled`) and **expired** (the signed link has expired). A payment cancelled at the gateway is stored as order status `cancelled` for now (see Questions).
4. **Resume payment** (not a redo of checkout): "Resume payment" resumes payment for an existing order. It is shown **only** on the account order page and on the track result, and only when the payment status is `pending` or `failed`, the method is the online gateway and the order is not cancelled; plus once as the primary button on the thank-you failed state (TODO: confirm the signed link supports resuming). Route TBD (for example `POST /orders/{ref}/pay`). A guest has no order page, so see Questions.
5. **The bag is emptied when the order is created**, whatever the payment outcome. After a failed or cancelled payment the bag is empty, so no state links back to the bag; the thank-you states offer "Continue shopping" instead, and the minimal header's back link on the thank-you page says "Continue shopping" (`/shop`).
6. **Order emails are off by default** (`order_emails_enabled: false`). The thank-you page shows "A confirmation has been sent to {email}" only when the flag is true **and** the order has an email; it never claims an email otherwise. `?demo=emails-on` shows it in the prototype.
7. Prototype stand-in: `html/proto-onepay.html` (no shell, no brand marks) with four buttons that go to `thank-you.html?state=paid|pending|failed|cancelled`. The simulated order is kept in sessionStorage with a generated reference `KY-<today YYMMDD>-<4 random A-Z0-9>`.

### 3.7 Thank-you
Minimal shell as for checkout. **This page is reached only through the signed, expiring link the backend supplies; it is never built from the order reference** (no `/orders/{ref}/thank-you` URL is constructed anywhere in the frontend). A request with an invalid or expired signature shows the **expired** state. Prototype-only `?state=paid|pending|failed|cancelled|expired` (paid by default) and `?demo=emails-on`; in Laravel the state comes from the order's statuses.

Data needed for every state except expired: order reference, items (name, size, colour, quantity, price), subtotal, delivery fee, total, delivery address (name, lines, city, district, phone), the district's estimated delivery text, payment line "Card - Onepay" with the payment status.

| State | Icon | Heading | Buttons | Status badge |
| --- | --- | --- | --- | --- |
| paid | check, Primary Deep on lilac tint | "Order confirmed" with "Thank you, {first name}" beneath, then next steps; the confirmation-email line when enabled | Track your order (`/track?ref={reference}`), Continue shopping; guests also get "Create an account with the same email to see this order later" (`/account/register`) | `status--paid` |
| pending | clock | "We're confirming your payment" - "This can take a few minutes. Please don't pay again." | Refresh status (reloads the page), Contact us (`/contact`) | `status--pending` |
| failed | alert circle, error colour | "Your payment didn't go through" - "If you were charged, contact us with your order reference." (no promise about held items) | **Resume payment** (primary; TODO confirm the signed link supports it), Continue shopping | `status--failed` |
| cancelled | alert circle, error colour | "This order was cancelled." - "If you were charged, contact us with your order reference." | Continue shopping, Contact us. No resume. | `status--cancelled` |
| expired | hourglass | "This confirmation link has expired" - "Use your order reference and mobile number to check your order." No order details are shown. | Track order (`/track`), Chat on WhatsApp | none |

The "Track your order" link carries **`?ref=` only** (`/track?ref=KY-261003-A3F9`): the track page prefills the reference and never runs the lookup. The order reference is shown large with a Copy button (clipboard; announces "Order reference copied").

**Security note:** the page exposes a name, address and phone, which is why it is only reachable through a signed, expiring link and never by reference. `/track` shows no personal data and needs the mobile number as well as the reference (section 3.9).

### 3.8 Status component (order and payment statuses)
`<span class="status status--paid">Paid</span>`: icon plus text, never colour alone. The mapping is defined once in `components.css` and is reused for order tracking and the account pages:
| Status | Look |
| --- | --- |
| `paid`, `confirmed`, `delivered` | lilac tint + check icon |
| `shipped` | blue tint + truck icon |
| `pending` | neutral outline + clock icon |
| `failed`, `cancelled` | error tint + x icon |
| `refunded` | blue tint + rotate-ccw icon |

### 3.9 Track order (`/track`)
Full shell. Guests and signed-in customers use the same page; it always works without an account. Intro: "Enter the order number from your confirmation and the mobile number you used." Button: "Find my order". Prototype-only: `?ref=` prefills the reference, `?demo=throttle`.

Form: `POST` (action TODO; assumption `POST /track`), `@csrf`, `novalidate`, **limited to 20 requests a minute**. **Both fields are required and are matched together** (an order is found only when the reference and the mobile number both belong to it).
| Field | Type | Required | Validation | Example |
| --- | --- | --- | --- | --- |
| `ref` (label "Order number") | text, `maxlength="20"`, `autocapitalize="characters"`, `autocomplete="off"`, no digit-only pattern | yes | order reference `KY-YYMMDD-XXXX` (uppercase, max 20 characters; **never assume digits only or a fixed length**); trim and uppercase before matching | `KY-261003-A3F9` |
| `phone` (label "Mobile number") | tel, `autocomplete="tel"`, `inputmode="tel"` | yes | the checkout's Sri Lankan mobile rule, normalised on the server; helper "The number you gave at checkout" | `071 234 5678` |

The thank-you page's "Track your order" link carries **`?ref=` only**; the page prefills the reference and **never runs the lookup** (the shopper adds the mobile number and presses "Find my order"). The mobile number is never put in a URL.

**Privacy rule:** the result shows only the status, the payment status, the items, the totals, the last-updated date and the delivery estimate. Never the name, address, phone or email. The estimate is shown while the order can still arrive (not for delivered, cancelled or refunded orders) and does not name the district.

Result data: reference, order date, last-updated date, order status, payment status, items (name, size, colour, quantity, unit price, thumbnail), subtotal, delivery fee, total, delivery estimate (`min_days`, `max_days`), the per-step dates (below).

States
- Found: order head (reference, "Placed {date}", "Last updated {date}", status badge, payment badge), the status stepper, items and totals, and a help card "Questions about your order? Chat on WhatsApp" that quotes the reference.
- Stepper (an `<ol>`: Order placed, Confirmed, Shipped, Delivered; horizontal from 900px, vertical below; completed steps show a check, the current step has `aria-current="step"` and a ring, future steps are muted; icon plus text always):
  - **One date per step when it is known** (placed; paid or confirmed; shipped; delivered) and **nothing when a step has no date** (older orders). The prototype shows one old order with missing dates (`KY-260720-E8Z5`). **The backend adds `delivered_at` and `cancelled_at`** (see Questions for `confirmed_at`: "paid or confirmed" uses `paid_at` until then).
  - pending order + pending payment: step 1 current with the note "Waiting for payment confirmation".
  - pending order + paid payment (waiting to be confirmed): step 2 current with the note "Payment received. We are confirming your order."
  - pending order + failed payment: step 1 shows the error status, the note "Payment failed. If you were charged, contact us with your order reference." and a **"Resume payment"** button (rules in 3.6, point 4).
  - confirmed: step 2 current. shipped: step 3 current. delivered: all steps done, step 4 `aria-current`.
  - cancelled: the stepper is replaced by the banner "This order was cancelled" with "Cancelled on {date}" when `cancelled_at` is known.
  - refunded (payment status): the banner "This order was refunded" with the payment badge (takes precedence over cancelled).
- Not found: alert (`role="alert"`, focus moves to it) "We couldn't find an order with that reference and mobile number." The fields are not marked invalid (it is a combined miss) and the form is kept.
- Validation errors (missing or invalid reference or mobile): the checkout-style error summary plus inline messages.
- Throttled: alert "Too many attempts. Please wait a minute and try again."

### 3.10 Auth pages (`/account/register`, `/account/login`, `/account/forgot-password`, `/account/reset-password`, `/account/verify-email`)
Accounts are optional: guest checkout and `/track` always work, and every auth page says so ("No account needed to order. You can check out as a guest and track an order any time." with links to the shop and to track). Customers and admins share the `users` table. The pages use the calm minimal shell (`header-auth`: wordmark and "Continue shopping"; `footer-minimal`). The login, register, forgot, reset and resend-verification endpoints are **rate-limited** (the prototype shows "Too many attempts. Please try again in 45 seconds."). Prototype `?demo=`: login `errors|throttle|success`; register `errors|throttle`; forgot-password `errors|throttle|sent`; reset-password `errors|invalid-link`; verify-email `verified|invalid|sent|throttle`.

All forms: `POST`, `@csrf`, `novalidate`; the server repeats every rule. Error summary (`role="alert"`, focus moves to it, links to each field) plus inline messages with `aria-invalid` and `aria-describedby`. Password fields have a show/hide button (name "Show password", `aria-pressed`). **Password rule everywhere: at least 8 characters, no complexity rules, no maximum** (helper "At least 8 characters"; no `maxlength`).

**Login** - `POST /login` (TODO action):
| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `email` | email, `autocomplete="username"` | yes | valid email |
| `password` | password, `autocomplete="current-password"` | yes | |
| `remember` | checkbox, `name="remember"`, value `1` | no | label "Keep me signed in for 30 days" (the number is `remember_days` from the site config: `<span data-cfg="remember_days">`) |
Safe responses: a wrong email or password returns ONE alert, "These details don't match our records.", and never says which field was wrong. After a password reset the page shows the success alert "Your password has been updated. Sign in with your new password." (a status flash).

**Register** - `POST /register` (TODO action). Fields: `name`, `email`, `phone`, `password`, `password_confirmation`.
| Field | Type | Required | Validation | Example |
| --- | --- | --- | --- | --- |
| `name` (label "Your name") | text, `autocomplete="name"` | yes | non-empty | `Amaya Ranasinghe` |
| `contact` (label "Mobile or email") | text, `autocomplete="email"` | yes | **one field**: a Sri Lankan mobile number (the checkout rule) or a valid email address | `071 234 5678` or `amaya@example.com` |
| `order_number` (label "Order number (optional)") | text, `maxlength="20"`, `autocapitalize="characters"`, `autocomplete="off"` | no | order number `KY-YYMMDD-XXXX`, uppercase; no digit-only pattern | `KY-261003-A3F9` |
| `message` (label "Message") | textarea (6 rows), `autocomplete="off"` | yes | non-empty (TODO: max length) | `Do you have the dress in 6-9m?` |

Only these four fields; button "Send message". (Considered and not added: topic, a separate mobile number.)
States: success alert "Thanks, we'll get back to you soon." (flash after the redirect; the form comes back empty), error summary "Please check your message" (focus moves to it, links to each field) plus inline messages with `aria-invalid`, throttle alert "Too many messages. Please wait a minute and try again." Spam protection is TODO (honeypot field, Turnstile or reCAPTCHA: backend dev's call). What happens to the message (an email to the shop, a database row, both) is TBD. Never claim an email was sent to the customer.
FAQs: the four on this page come from FAQs with the placement `contact` (wrapped in `<!-- loop: faqs (placement: contact) -->`).

### 3.15 Error pages (404, 419, 429, 500, 503)
One template (`errors.css`): a lilac icon disc, a code label, h1, one sentence, two buttons and a help line. The backend should provide `resources/views/errors/404.blade.php`, `419.blade.php`, `429.blade.php`, `500.blade.php` and `503.blade.php`.
| Code | h1 | Sentence | Buttons | Shell |
| --- | --- | --- | --- | --- |
| 404 | We can't find that page | The link may be old or mistyped. Try a search or head back to the shop. | Back to home, Shop new in; search field (`GET /search`) and the six category chips | full shell |
| 419 | This page has expired | For your security the page timed out. Go back and try again. | Go back (`history.back()`, the href is the fallback), Home | full shell |
| 429 | Too many attempts | Please wait a minute and try again. | Go back, Home | full shell |
| 500 | Something went wrong on our side | Please try again in a moment. | Try again (reload), Home; WhatsApp help line | `header-error` + `footer-minimal` |
| 503 | We'll be right back | We're making a quick update. Please check back soon. | Try again, Contact us; the contact email | `header-error` + `footer-minimal` |

500 and 503 use only the wordmark header and the minimal footer, with no database-driven shell, because the backend may be unhealthy; they must not query the database or session. No technical details are ever shown. The 503 page promises no time. 403 is not designed.
**GitHub Pages only (prototype):** Pages serves `/404.html` for any missing URL at that URL's path, so `html/404.html` carries `<!-- sync-root: /kayaa/ -->` and uses root-absolute `/kayaa/...` links. Blade does not need this: its error view uses the normal asset helpers.

### 3.16 Shell partials added
`header-error` (wordmark only; 500 and 503). `header-auth` (wordmark and "Continue shopping"; the account auth pages). `header-minimal` stays for checkout and thank-you only.


### 3.17 Site config (`tools/site-config.json`)
The prototype keeps its single-source values in `tools/site-config.json`. `node tools/sync-shell.mjs` refreshes every `<span data-cfg="KEY">` (money keys print as `Rs. 7,500`), sets attributes named in `data-cfg-attr="attribute:KEY"` (for example `max:free_shipping_over` on the free-delivery progress), and writes `assets/js/site-config.js` (`window.KAYAA_CONFIG`) for the scripts. **In Blade these become view variables**: print the values where the `data-cfg` spans are, and print `window.KAYAA_CONFIG` from the same config in the layout.
| Key | Value | Used for |
| --- | --- | --- |
| `shipping_fee` | 450 | delivery fee (cart, checkout, order totals, announcement bar, product page, delivery page) |
| `free_shipping_over` | 7500 | free-delivery threshold and the progress bar maximum |
| `pay_button_label` | "Pay now" | the checkout pay button (never contains an amount) |
| `order_emails_enabled` | false | the confirmation-email line on the thank-you page |
| `remember_days` | 30 | the login "Keep me signed in for 30 days" label |
| `low_stock_threshold` | 5 | "Only N left" on the product page, cart lines and the buy form |
| `delivery_colombo_days` | "1–2" | working days for Colombo and suburbs (product accordion, delivery page, contact) |
| `delivery_island_days` | "2–4" | working days for the rest of the island (announcement bar, product accordion, delivery page) |
| `dispatch_cutoff` | "2pm" | "Order before 2pm for same-day dispatch" on the product page (TODO confirm) |
| `return_window_days` | 14 | the exchange window (trust strip, product accordion, returns page; TODO confirm) |
| `refund_days` | 7 | refunds go back to the card within N working days (returns page; TODO confirm) |
| `faulty_report_days` | 7 | tell us within N days about a damaged or wrong item (returns page; TODO confirm) |
| `support_hours` | "Mon–Sat 9am–6pm" | the Hours card on the contact page (TODO confirm) |
| `whatsapp_display` | "077 000 0000" | the WhatsApp number shown in help cards and the contact page (placeholder number) |
| `currency_prefix` | "Rs." | the prefix of every price, written "Rs. 2,450" (one helper formats it, including the free-delivery progress) |


### 3.18 Categories (`/categories`)
Full shell. Breadcrumb Home / Baby. H1 "All categories", a lede, and a grid of the seven categories: placeholder media (a photo later), the name with an arrow, "N items" and a link to the category (`/baby/{slug}`; prototype `category.html?c={slug}`). The grid is 2 columns on phones, 3 from 600px, 4 from 900px and all seven in one row from 1200px. The mega menu stays; "Categories" is a link after "Shop" in the desktop nav and in the mobile drawer. Data: name, slug, item count (active products), photo.

### 3.19 Wishlist (`/wishlist`)
Full shell. H1 "Your wishlist", "N saved products", and a grid of the saved products (the same product card plus **Remove** and **Choose size**, which opens the quick-add sheet with the product's sizes and colours). Empty state: "Nothing saved yet." "Tap Save on a product to keep it here." and a "Shop now" button (`?demo=empty` in the prototype). A heart link sits in the desktop header utility group; "Wishlist" is in the mobile drawer and the footer. The product page has the **Save** toggle ("Save" / "Saved", `aria-pressed`). Removing a card moves focus to the next Remove button (or the empty-state heading) and announces "<name> removed from your wishlist." **TODO / question:** guest wishlists (kept in the browser, or in the session until sign-in?) and whether signed-in wishlists live in the database. Prototype: sessionStorage (`proto-wishlist.js`, first visit starts with three saved products).

### 3.20 Product fields observed on staging
Names, categories, prices and the size range come from the staging site; everything else is placeholder. Sample catalogue (26 products, `tools/catalogue.json`): Ribbed Cotton Bodysuit (Bodysuits, sale Rs. 2,450 from Rs. 2,950, 4 colours, NB–18m), Cotton Kurta Set (Sets, new, 9m–24m, Rs. 4,950), Muslin Sleep Bag 0.5 TOG (Sleepwear, 3m–24m, Rs. 4,200), Bamboo Sleepsuit (Sleepwear, NB–12m, Rs. 3,650), Mittens & Booties Set (Accessories, NB–6m, Rs. 1,650), Reversible Bib Pair (Accessories, 6m–18m, Rs. 1,250), Pointelle Romper (Bodysuits, NB–12m, Rs. 3,200), Waffle Knit Cardigan (Outerwear, 6m–24m, Rs. 3,900), plus invented products so that every size has at least four products and every category at least three. Fields seen on a product: name, category, price and compare-at price, colours (names; the sample colours are Butter, Lilac, Sky, Blush, Dove), a size range, a "Kayaa Essentials" eyebrow, "Fabric & care" and "Delivery & returns" blocks, reviews with an optional headline, and a "Goes well with" related list. Currency is "Rs. 2,450". One size and category combination returns nothing for the empty-state demo (`shop.html?size=3y&sale=1`, `category.html?c=napkins&sale=1`).

---

## 4. Cart drawer (shell, every page)

Root element `[data-cart-drawer]`; its inner markup is one self-contained panel (`.cart-panel`) so the server can return it and JS swaps it in (`GET /cart/panel`, like the existing backend convention).

Panel contents
- Header "Your bag (N)" with N = total units.
- Free-delivery progress: text "Rs X away from free delivery" or "You've unlocked free delivery", a `<progress>` with `value = subtotal` (capped) and `max = 7500` (`data-free-threshold="7500"` on the panel).
- Line items (`<li class="cart-item" data-key data-unit-price>`): thumbnail (4:5, the selected colour's photo), product name (link to the product), meta "Size 3-6M · Lilac", quantity stepper (1 to 10), line total, remove button.
- Subtotal, note "Delivery calculated at checkout · Pay securely by card", primary "Checkout" -> `/checkout`, secondary "View bag" -> `/cart`.
- Empty state (`data-state="empty"`): "Your bag is empty", "Start shopping" -> `/shop`.

The same line data feeds the cart page (3.4). Endpoints (assumption: the prototype changes its demo cart in sessionStorage only; the cart page forms in 3.4 use the same routes)
| Action | Request | Response |
| --- | --- | --- |
| Read panel | `GET /cart/panel` | panel HTML |
| Add line | `POST /cart/items` with `product`, `colour`, `size`, `quantity` | panel HTML (or JSON count + panel) |
| Change quantity | `PATCH /cart/items/{id}` with `quantity` (1 to 10, not above stock) | panel HTML |
| Remove | `DELETE /cart/items/{id}` | panel HTML |

Rules: the header count and every `[data-cart-count]` badge show the unit total and hide at 0. A line is identified by product + colour + size; adding the same line again increases its quantity. Quantity is capped by stock.

### Quick add (product cards)
A bottom sheet / modal opened from a card's "Quick add" button (mouse) or round "+" (touch).
| Field | Type | Required | Values |
| --- | --- | --- | --- |
| `size` | radio | yes | the size key of an in-stock variant (same value as the product page form, e.g. `3-6M`; the prototype shows a static list with one disabled) |
| `colour` | radio | yes | the colour **slug** (e.g. `lilac`; the label is sent alongside in the prototype as `data-label`) - same shape as the product page form |
| product | (assumption) hidden id | yes | |

The button carries `data-name`, `data-price`, optional `data-was`, `data-tone` (prototype only). Production assumption: the sheet's options are rendered per product (or fetched), because sizes and colours differ between products. On submit the line is added and the cart drawer opens.

---

## 4a. SEO needs from the backend
The prototype's head is built from `tools/seo.json`; the Blade views build the same tags per record. The rules come from `docs/content/seo-guide.md` (sections 3, 4, 5, 9 and 10), which supersedes earlier notes.
1. **Meta fields.** Nullable `meta_title` and `meta_description` on products and categories (and the size pages if they are stored). The templates are the fallback: product title `{Product name} | Baby {Category} | Kayaa`, product description the first 150 characters of the description (fallback "{Product name} from Kayaa. Choose your colour and size, pay securely by card and get delivery across Sri Lanka."); category and size titles and descriptions from `docs/content/category-and-age-intros.md`. Titles 60 characters or fewer, descriptions 120 to 160, all unique. The staging site uses one identical description on every page; the per-page ones replace it.
2. **Category description field.** A text field per category for the intro paragraph shown in the listing header band (the prototype: `<!-- blade: category description -->`, from the content pack). The size intros stay static in Blade (nine fixed pages).
3. **301 redirects** whenever a product or category slug changes; unknown URLs return a real 404.
4. **Canonical helper and robots by environment and route.** `canonical` = the clean URL of the page; `robots` is `noindex,nofollow` everywhere while `APP_NOINDEX` is true (staging) and the matrix below otherwise:
   | Page | Robots | Canonical |
   | --- | --- | --- |
   | Home, Shop, /categories, /baby/{slug} | index,follow | itself |
   | /shop?size={slug} | index,follow | itself |
   | /baby/{slug}?size={slug} | noindex,follow | the category page |
   | Product pages | index,follow | the product URL without `?colour=` |
   | Size guide, Delivery, Returns, About, Contact, Privacy, Terms | index,follow | itself |
   | Any `sort` or `sale` parameter, and combinations of filters | noindex,follow | the clean listing (the same listing without sort, sale and page) |
   | Listing page 2 and beyond (`?page=2`) | index,follow | itself |
   | Search results | noindex,follow | itself |
   | Wishlist, cart, checkout, order confirmation, track, all account and auth pages | noindex,nofollow | itself |
   | Error pages | noindex | none |
   The prototype shows the same rules through `proto-listing.js` (canonical, robots, title, description and breadcrumb per state; staging keeps noindex,nofollow and records the live value in `data-live-robots`) and keeps the canonical on the product URL when `?colour=` is chosen (`product.js`).
5. **JSON-LD builders:** `Organization` on the home page (name, URL, logo, social profiles, contact point: placeholders in the prototype), `BreadcrumbList` on category, product and content pages matching the visible breadcrumb (Home > Baby > Category), and `Product` on product pages (name, images, description, brand, `offers` in LKR with price, availability and URL; `aggregateRating` and `review` only from the approved reviews of that product). **No FAQPage markup and no WebSite SearchAction.** No markup for reviews of the store itself.
6. **Sitemap** (`/sitemap.xml`) generated for indexable routes only (see `docs/seo/sitemap.example.xml`); no cart, checkout, account, track, search, wishlist or order URLs.
7. **robots.txt by environment** (`docs/seo/robots.production.txt`: allow all; disallow /cart, /checkout, /account, /track, /search, /wishlist, /orders; the Sitemap line). Staging serves "Disallow: /".
8. **Launch:** remove the unconditional `X-Robots-Tag: noindex` block from `public/.htaccess` (Apache cannot read `.env`), set `APP_NOINDEX=false`, and follow `docs/seo/launch-checklist.md`.
9. **Fonts:** self-hosted (`assets/fonts`); nothing loads from Google Fonts, so no third-party request blocks rendering.

### Content pages from the content pack
Size guide, Delivery, Returns, About, Privacy and Terms are static Blade views whose text comes from `docs/content/*.md` (the prototype renders the same files). The pack's `{{cfg:key}}` values are the site config keys (section 3.17); `[[TODO]]` and `[[PROPOSED]]` markers must all be replaced with approved text before launch (`node tools/list-todos.mjs --fail-on-open`). The size chart is data shared by the size guide and the product page. FAQs come in three placements (product, contact, size guide): the prototype reads `docs/content/faqs.md`. **Question for the client:** do they want an admin editor (Filament rich-text pages) for these pages, or Blade views a developer edits?

---

## 5. Prototype-only behaviour (delete at conversion)

- `assets/js/proto-listing.js` (also the indexing demo, see section 4a): filters, sorts, searches and paginates the 26 sample cards from the query string (`size`, `sort`, `sale`, `c`, `q`, `page`); normalises the old encoded `?size=0%E2%80%933m`; sets H1, intro, breadcrumb (Home / Baby / Category), counts and pagination. `assets/js/catalogue-data.js` (generated from `tools/catalogue.json`) holds the categories, sizes, colours and products for the scripts (the quick-add sheet, the listing, the wishlist); the product cards and menus are generated from the same file by `node tools/sync-shell.mjs` (`<!-- gen: NAME -->` regions). `assets/js/proto-wishlist.js` is the sessionStorage wishlist.
- `assets/js/proto-product.js`: applies `?demo=` states and fakes the review form submit. `demo` values: `regular` (no sale), `new`, `low-stock`, `oos`, `no-reviews`, `reviewed`, `review-success`, `review-error`, `review-throttle`. Every product card opens the same sample product.
- `assets/js/proto-cart.js`: the demo cart (sessionStorage) behind the header counts, the drawer, the cart page and the checkout summary, plus the simulated order. Line shape: `{id, productSlug, name, colourSlug, colourLabel, sizeSlug, sizeLabel, unitPrice, wasPrice, qty, stock, tone}`.
- `assets/js/proto-content.js` (the contact form demo outcomes). `assets/js/proto-orders.js` (thirteen sample orders, the stepper renderer), `proto-account.js` (track, orders, order detail, reviews, profile, log out) and `proto-auth.js` (auth demo outcomes and the demo session). Demo session: `window.KayaaCart.session` (sessionStorage); when set, the Account links in the header, tab bar and menu drawer go to the dashboard. In Laravel the server renders `@auth` and `@guest`.
- `assets/js/proto-cart-page.js`, `proto-checkout.js`, `proto-thankyou.js`, `proto-onepay.js` and `html/proto-onepay.html`: render the pages from the demo cart/order and fake the payment hand-off. Demo params: cart `empty`, `oos-line`, `low-stock`, `price-changed`, `free-delivery`, `checkout-oos`; checkout `errors`, `throttle`, `gateway-error`, `signed-in`, `empty`; thank-you `state=paid|pending|failed|cancelled`.
- `html/review.html` is a review index for the client; remove it.

Production JS that stays: `assets/js/app.js` (shell: menus, drawers, mega menu, search toggle, steppers, active-nav marking), `product.js` (variants, gallery filtering, stock note, quantity cap, size validation, sticky bar, show-more reviews), `cart.js` (sticky checkout bar), `checkout.js` (validation, error summary, delivery estimate, loading state) and `thank-you.js` (copy reference, refresh) `account.js` (auth, profile and contact form validation, error summary, show/hide password) and `content.js` (table of contents behaviour and print). `app.js` also carries the `data-history-back` / `data-reload` hooks of the error pages. The drawer in `app.js` currently renders from the demo cart: in Laravel it swaps in the server panel. Remove the `data-demo-cart` attribute from the buy form when the real cart exists.


---

## 5. Still open (questions for the backend dev)
1. **A payment option other than Onepay.** Your answers still mention one. Payment is online only through Onepay (Visa/Mastercard); please confirm the backend has no other payment path.
2. **Stock and failed or abandoned payments:** with online payment, stock is decremented permanently at placement, so failed or abandoned payments consume stock for good unless it is restored on failure, cancellation or timeout. What restores it?
3. **Guests resuming a failed payment:** a guest has no order page. How does a guest resume payment (the signed thank-you link? the track result with the mobile number)?
4. **Gateway cancel:** can a payment cancelled at the gateway leave the order resumable (status stays `pending`, payment `failed`) instead of making the order `cancelled`?
5. **Refunds on cancel:** what happens, and how long does it take, when a paid order is cancelled? (The dialog says "Your payment will be refunded to your card" with a TODO for the timing.)
6. **`confirmed_at`:** can the backend add a `confirmed_at` column? Until then the "paid or confirmed" step date uses `paid_at`.
7. **Mail configuration:** verification and password-reset emails need real mail configuration before launch.

### Catalogue alignment (staging comparison)
8. **Size URLs:** can they use slugs (`0-3m`) instead of the encoded en dash (`0%E2%80%933m`)? The prototype accepts both.
9. **Sort parameter values:** what are the backend's sort values (the prototype uses `featured`, `new`, `price-asc`, `price-desc`)?
10. **Sale:** is Sale a filter (`sale=1`) only, or also a category or collection?
11. **Colours:** do colours have hex values? Until then product cards show "{N} colours" and no dots.
12. **Product text fields:** are there separate fabric & care (composition, care list) and full description fields?
13. **Wishlist for guests:** kept in the browser, in the session, or only for signed-in accounts?
14. **"Napkins":** what are they (the category has no obvious size range)?
15. **Listing page size:** how many products per page (the prototype uses 12)?
16. **"Kayaa Essentials":** where does the eyebrow come from (a product field, a collection)?
17. **Payment copy that is no longer true:** the staging site still shows an extra payment option and PayHere copy. When will they be removed? (The prototype names no gateway except Onepay at checkout.)
