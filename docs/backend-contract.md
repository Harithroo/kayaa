# Backend contract (frontend prototype -> Laravel 12 + Filament 4)

Written from what the prototype in `html/` actually contains, so you do not need to open any HTML. Anything the prototype does not show is marked **(assumption)** or **TBD**. The prototype is static: its JS fakes server behaviour (see "Prototype-only behaviour" at the end). Keep this file in sync with the pages (see CLAUDE.md).

Conventions used by every page
- Money is whole rupees, shown as `Rs 1,490` (thousands separator). Free delivery over Rs 7,500; standard delivery Rs 450.
- One shared shell (header, footer, drawers, tab bar) wrapped in `<!-- partial: NAME -->` comments. `<!-- loop: ... -->` marks repeated data, `<!-- blade: ... -->` marks conditionals and generated values.
- Order statuses: `pending`, `confirmed`, `shipped`, `delivered`, `cancelled`. Payment statuses: `pending`, `paid`, `failed`, `refunded`.
- Product images are 4:5. Placeholders (`.media[data-placeholder]`) are replaced by `<img>` markup when photos exist.
- All pages carry `<meta name="robots" content="noindex,nofollow">` on staging; remove it at conversion.

---

## 1. What the frontend needs from the backend (new or changed)

1. **Images per colour.** A product image has a nullable colour. Photos with a colour show only when that colour is selected; photos without a colour (shared) show for every colour. A colour with no photos falls back to the shared set. Each image needs an order and, ideally, alt text. The page renders every image with `data-colour="<colour slug>"` or `data-colour="all"`; JS filters them. `?colour=<slug>` must preselect the colour (the page rewrites the URL with `history.replaceState`).
2. **Low-stock threshold** as an admin-editable setting (default 5). Rendered on the buy form as `data-low-stock-threshold="5"`. The stock note says "Only N left" when `1 <= stock <= threshold`.
3. **Product description** as a plain-text field with line breaks preserved. The page shows the first ~160 characters (cut at a word boundary, ellipsis) as a summary, and the whole text in an open "Description" accordion. The page carries the hint `Str::limit($product->description, 160)`; plain `Str::limit` can cut mid-word, so use word-boundary truncation (for example its `preserveWords` option if your Laravel version has it).
4. **Variants JSON.** Per product page, an inline block `<script type="application/json" id="product-variants">[{"colour":"lilac","size":"3-6M","stock":4}, ...]</script>`: one row per colour+size variant, `colour` = colour slug, `size` = size label (same string as the size radio value). Sizes are rendered in size-scale order.
5. **Review "show more".** The product page shows 5 reviews and a "See more reviews" control that reveals 5 more in place. Prototype: all reviews are in the HTML and JS reveals them. Production options (pick one): (a) render all approved reviews and let JS reveal them, or (b) an endpoint that returns the next page of review-card HTML, like `/cart/panel` (assumption: `GET /products/{slug}/reviews?page=2` returning `<li class="review-card">...</li>` items plus the new "Showing N of M" count). Either way the no-JS fallback is a plain link to the same product page with `?reviews=all#reviews`, which must render every approved review. There is no separate reviews page.
6. **Rating average with decimals** (one decimal, e.g. `4.8`) plus review count and a 5-to-1 breakdown (counts). Stars: whole part = full stars; any fraction .1 to .9 adds one half star (4.0 = 4 stars, 4.1 to 4.9 = 4.5 stars). Individual review ratings are whole numbers 1 to 5.
7. **Onepay payment flow (design pending).** Payment is online only (Visa/Mastercard through the Onepay gateway). There is no cash on delivery. Assumption: checkout redirects to Onepay's hosted payment page. Orders start with `payment_status = pending` until the gateway confirms. The frontend still has to design paid, pending and failed/retry states, so the callback/return URLs and the retry route are needed (TBD).
8. **Delivery ETA only at checkout.** The product page shows no delivery estimate. Checkout shows it after the delivery district is chosen: district list, ETA text and delivery fee per district are needed (TBD; the free-delivery threshold and the Rs 450 standard fee are known).
9. **Category data:** name, slug, one-line description (shown in the listing header band) and a photo (home page tile).
10. **Admin-managed shell content:** announcement bar (`topbar`: text, style `lilac|cream|sky`, enabled), home promo banners (`home_promo`: style `lilac|sky`, eyebrow, headline, text, button label and URL, optional photo), WhatsApp number/link, contact email, social URLs.
11. **Age groups** are fixed in the prototype (5 slugs, see Listing). Product-to-age mapping is needed (a product can fit several ages).
12. **Product flags:** `new`, `featured`, sale price (`was` price = regular, current = sale). Badge priority on cards: Out of stock > Sale > New > Featured (one badge max).
13. **FAQs** from product, category and global FAQs (the product page shows five placeholder questions). TBD: how they are merged and ordered.
14. **Size guide data** is TBD (global table vs per product). The prototype shows a placeholder table (size, age, weight, height) on the product page and will reuse it on `size-guide.html`.
15. **Cart drawer endpoints** (see section 4). The prototype does quantity changes and removal client-side; they need real endpoints.
16. **Related products:** same category as the current product, excluding the current product, 4 items.
17. **Fabric & care and Delivery & returns** accordions are static site-wide text (placeholders). TBD whether they become settings.
18. **SEO:** `<title>` `"<Name> | Kayaa"`, meta description, canonical URL, and a JSON-LD `Product` block (name, image, description, category, offers with `priceCurrency: "LKR"`, `price`, `availability`, `aggregateRating`). The prototype has placeholder values and a `<!-- blade: generate from the product -->` comment.

19. **Cart line data** with per-variant stock, the sale/regular price, the "price changed" flag and the colour's thumbnail (section 3.4).
20. **Flat delivery fee rule from config** (Rs 450, free from Rs 7,500). TODO confirm it is not district-based.
21. **Checkout** fields, validation, the 25-district list from `config/kayaa.php` and the delivery-ETA data per district (sections 3.5).
22. **Payment hand-off:** order creation, stock decrement at order time, redirect to Onepay, return URL and callback, the retry action, the stock-hold expiry (30 minutes shown), the four outcomes, and how a shopper cancel is stored (sections 3.6 and 3.7).
23. **Order reference format** (the prototype uses `KYA-10234`, TODO real format) and a **signed URL or unguessable token** for the thank-you page (section 3.7).
24. **Status component mapping** for order and payment statuses (section 3.8).
25. **Order confirmation emails do not exist yet.** The pages never claim one was sent.

---

## 2. Shell (every page)

### Announcement bar (admin "topbar")
- Data: text (prototype: "Free delivery over Rs 7,500" + " · Secure online payment" as two segments; the second hides under 600px), style `lilac|cream|sky`, enabled.
- Dismiss hides it for the current page view only (nothing stored); it returns on every load.

### Header, menus, footer
- Logo/wordmark -> `/`. Primary nav: Shop (`/shop`, with a mega menu), New in (`/shop?sort=new`), Sale (`/shop?sale=1`), Size guide (`/size-guide`), Our story (`/about`).
- Mega menu: five age links `/shop?age=<slug>` and six category links `/{department}/{category}` (prototype: `category.html?c=<slug>`), plus a "New this week" feature card -> `/shop?sort=new` (placeholder copy, TBD).
- Category row (desktop): the six categories. Account icon -> `/account` when signed in, else `/account/login`. Cart icon opens the cart drawer; the count is the sum of line quantities.
- Footer: age links, help links (`/track`, `/size-guide`, `/delivery`, `/returns`, `/contact`), company links (`/about`, `/privacy`, `/terms`), WhatsApp link, contact email, social URLs.
- Active nav states use `<body data-page="home|shop|category|product|search|cart|account|...">`; the server should set it per page (space-separated tokens allowed, e.g. `account login`).

### Minimal shell (checkout and thank-you)
These two pages use `header-minimal` and `footer-minimal` partials only: no announcement bar, menu drawer, tab bar or cart drawer.

### Header search form
| | |
| --- | --- |
| Route | `GET /search` |
| Field | `q` (type `search`, optional on submit, example `romper`) |

### Categories (fixed list in the prototype)
`bodysuits` Bodysuits, `sleepsuits` Sleepsuits, `sets` Sets, `dresses-rompers` Dresses & Rompers, `hats-mitts` Hats & Mitts, `swaddles-blankets` Swaddles & Blankets (the home tile file name uses `cat-swaddles`).

### Age groups (fixed list in the prototype)
`newborn` Newborn, `0-3-months` 0-3 months, `3-6-months` 3-6 months, `6-12-months` 6-12 months, `1-2-years` 1-2 years.

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
| track.html | `/track` | stub |
| contact.html | `/contact` | stub |
| size-guide.html, delivery.html, returns.html, about.html, privacy.html, terms.html | same names | stub |
| account/register, login, forgot-password, reset-password, index (orders), order, reviews, profile | `/account/...` | stub |
| review.html | none | review-only index, delete at conversion |

### 3.1 Home (`/`)
- No query params, no forms (the header search is the only form).
- Data: hero (static copy + one photo), trust strip (four static items: free delivery over Rs 7,500 with "Standard delivery Rs 450", secure online payment with "Visa & Mastercard accepted", easy returns (window TBD), verified parent reviews), age tiles (5, link `/shop?age=<slug>`), category tiles (6, each with a photo and label, link to the category), "New this week" (4 products, newest first, link "View all" `/shop?sort=new`), promo banners (`home_promo`, 2), "Featured products" (8 products with the `featured` flag, button "Shop all" `/shop`).
- Product card data: name, URL, current price, regular price when on sale, badge (see section 1.12), up to 4 colour dots plus "+N", out-of-stock flag, photo.
- States: out-of-stock card (muted, no quick add). Empty sections are not designed (assumption: hide the section).

### 3.2 Listing pages: Shop, Category, Search
One shared template. Everything is driven by the query string and works without JS (filters are a real GET form plus plain links). The prototype fakes the filtering with `assets/js/proto-listing.js`; the server does it in Laravel.

Routes: `GET /shop`, `GET /{department}/{category}` (prototype `category.html?c=<slug>`), `GET /search`.

| Param | Where | Allowed values | Notes |
| --- | --- | --- | --- |
| `age` | all | `newborn`, `0-3-months`, `3-6-months`, `6-12-months`, `1-2-years` | Invalid value ignored (= all ages). Not shown on Search (no age chips there). |
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
| `age`, `c`, `q` | hidden | current values | Only rendered when set, so the other filters survive a submit. `page` is dropped on submit. |

Links: age chips (`All ages` + 5) keep `sort`, `sale`, `c`, `q` and drop `page`. Pagination links keep every other param. "Clear filters" removes `age`, `sort`, `sale` and `page` but keeps `c` and `q`.

H1 and intro by state (precedence): Search: `Results for "<q>"` (empty q: "Search"); Category: category name + its one-line description; age: `<age label> clothing`; `sale=1`: "Sale"; `sort=new`: "New in"; default: "All baby clothing". With age or category plus `sale`, the intro adds "Showing sale items only." Document title is `"<H1> | Kayaa"`. Breadcrumb: Home / Shop (/ Category); Search: Home / Search.

Data per page: products (page of 12) with the card data from 3.1, total count (band shows "N products", toolbar shows "Showing 1-12 of 24"), page count.

States
- Empty (no products): package icon, "No products match these filters", "Clear filters" button.
- Search no results: `No results for "<q>"`, three tips, links to the six categories and five ages. Empty `q` shows the same block titled "What are you looking for?".
- Pagination: Prev / numbers / Next (ellipsis beyond 7 pages); under 600px it shows Prev / "Page N of M" / Next. Hidden when there is one page.

### 3.3 Product (`/products/{slug}`)

Query params: `colour` (colour slug; preselects the colour and its photos; invalid values ignored), `reviews=all` (no-JS fallback: render every approved review). Prototype only: `demo` (see the end of this file).

**Add-to-cart form** - `POST` (action TODO, the prototype posts to `cart.html`; assumption: `POST /cart/items`), `@csrf`. Field names are a TODO to confirm with the backend dev; a single variant id is the alternative.
| Field | Type | Required | Values / validation | Example |
| --- | --- | --- | --- | --- |
| `colour` | radio | yes | a colour slug of this product; one preselected | `lilac` |
| `size` | radio | yes | size label of an in-stock variant for the chosen colour; out-of-stock variants render as disabled radios (+ sr-only "(out of stock)") | `3-6M` |
| `quantity` | text, numeric | yes | integer 1 to 10, and at most the variant's stock | `1` |
| product id | hidden | yes | (assumption) the product id or slug | |

Form attributes the page reads: `data-product-name`, `data-unit-price` (current price in rupees, sale price when on sale), `data-low-stock-threshold` (admin setting). Client behaviour: if no size is chosen the form does not submit and shows "Please choose a size". With every variant out of stock the button is disabled and reads "Out of stock".

**Variants JSON**: see section 1.4.

**Data the page needs:** name, slug, category (name, slug), price and sale price, flags (`new`, `featured`), description, colours (name, slug, swatch colour), sizes in scale order, variants (stock), images (url, alt, order, nullable colour), rating average (1 decimal), review count and 5-to-1 breakdown, approved reviews (author name, date, rating 1 to 5, comment, verified-purchase flag, optional admin reply), FAQs, related products (section 1.16), the user's "already reviewed" flag.

**Badge** (top-left of the gallery, one max): Sale > New > Featured.

**Reviews section** (`#reviews`): average, stars, "Based on N reviews", breakdown rows, "Write a review" button (anchor to `#write-review`), review cards (stars, name, date, optional "Verified purchase", comment, optional "Reply from Kayaa"; no titles), "Showing 5 of 12" count and "See more reviews" (section 1.5). Empty state: "No reviews yet. Be the first to review this product." with a button to the form.

**Review form** (`#write-review`) - `POST` (action TODO; assumption: `POST /products/{slug}/reviews`), `@csrf`.
| Field | Type | Required | Validation | Example |
| --- | --- | --- | --- | --- |
| `rating` | radio 1 to 5 (rendered as stars) | yes | integer 1 to 5 | `5` |
| `name` | text | yes, for guests only (hide when signed in) | non-empty string | `Amaya R.` |
| `comment` | textarea | yes | non-empty text (max length TBD) | `Lovely and soft.` |

No review titles. Reviews are moderated: show "Thank you - your review will appear once it has been approved." States: field errors (message per field, `aria-invalid`, plus a summary alert "Please check your review" listing the errors), success alert, rate-limit alert "Too many attempts. Please wait a minute and try again.", and, for signed-in users who already reviewed, the form is replaced by "You've already reviewed this product."

Other: the size guide panel is a static placeholder table (TBD, section 1.14); the assurance list is static (free delivery over Rs 7,500; secure card payment, Visa & Mastercard; easy returns, window TBD). Related products and FAQs: sections 1.13 and 1.16.

### 3.4 Cart (`/cart`)
Replaces the stub. No query params. Prototype-only `?demo=` (`empty`, `oos-line`, `low-stock`, `price-changed`, `free-delivery`, `checkout-oos`). Full shell (header, tab bar, drawers).

Data: the cart lines and totals. Each line: product name and URL, colour (slug + label), size (key + label), unit price, regular price when on sale, quantity, current stock of that variant, thumbnail (the selected colour's first photo), availability, "price changed since added" flag. Totals: subtotal (available lines only), delivery fee, total, units count, "Rs X away from free delivery".

Rules (config, TODO confirm the delivery fee is flat and not district-based): delivery is Rs 450 below a Rs 7,500 subtotal and free at Rs 7,500 or more. Quantity per line is 1 to 10 and never above the variant's stock.

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
| `email` | `email` (`autocomplete="email"`) | yes (TODO: is email required for guests?) | valid email | `amaya@example.com` |
| `phone` | `tel` (`autocomplete="tel"`) | yes | Sri Lankan mobile: `07X XXXXXXX` (10 digits, X in 0-8) or `+94 7X XXXXXXX`, spaces/dashes allowed; normalise on the server | `071 234 5678` |
| `name` | text (`autocomplete="name"`) | yes | non-empty | `Amaya Ranasinghe` |
| `address_line1` | text (`address-line1`) | yes | non-empty | `42 Temple Road` |
| `address_line2` | text (`address-line2`) | no | | `Apartment 3` |
| `city` | text (`address-level2`) | yes | non-empty | `Nugegoda` |
| `district` | select | yes | one of the 25 districts from `config/kayaa.php`; the option value is the slug | `colombo` |
| `notes` | textarea | no | free text ("delivery notes") | `Call on arrival` |

No postal code, promo code, gift option or order note beyond `notes` (TODO confirm whether a postal code is needed). Districts (slug): `ampara`, `anuradhapura`, `badulla`, `batticaloa`, `colombo`, `galle`, `gampaha`, `hambantota`, `jaffna`, `kalutara`, `kandy`, `kegalle`, `kilinochchi`, `kurunegala`, `mannar`, `matale`, `matara`, `monaragala`, `mullaitivu`, `nuwara-eliya`, `polonnaruwa`, `puttalam`, `ratnapura`, `trincomalee`, `vavuniya`.

Delivery estimate: shown only here (and in the order summary and on the thank-you page), after the district is chosen: "Estimated delivery to Colombo: 2-3 working days". The prototype reads a placeholder table (`<script type="application/json" id="district-eta">` mapping district slug to text). TODO: the real ETA data per district (where it lives: config, admin or database) and whether more than the text is needed (min and max days). Never shown on the product page or the cart.

Contact states: guests see "Have an account? Log in" (`/account/login`); signed-in users see their fields prefilled and "Signed in as {email} - Not you?".

Payment card (no radio cards, online only): credit-card icon, "Pay by card - Visa or Mastercard", "You'll be taken to Onepay's secure page to enter your card details. We never see or store your card number." (TODO confirm hosted redirect), plain-text Visa and Mastercard badges (TODO official marks and Onepay badge), and "By paying you agree to our Terms and Returns policy" (`/terms`, `/returns`).

Pay button: `Pay Rs 6,750` with a lock icon and the helper "Secure payment via Onepay". On a valid submit the button is disabled with `aria-busy="true"`, shows a spinner and "Redirecting to secure payment..." and cannot be submitted twice. TODO: should the label say "Place order" instead?

States: field errors (message under each field, `aria-invalid`, `aria-describedby`), an error summary at the top (`role="alert"`, focus moves to it, links to each invalid field), throttle alert ("Too many attempts. Please wait a minute and try again."), gateway error alert ("We couldn't start the payment. Please try again."), stock-ran-out redirect to `/cart`.

### 3.6 Payment hand-off (Onepay) - assumptions, all TODO to confirm with the backend dev
1. `POST /checkout` validates, creates the order with `payment_status = pending` and `status = pending`, **decrements stock** (abandoned payments therefore hold stock), generates the order reference, and responds with a redirect to Onepay's hosted payment page. We never handle card numbers.
2. Onepay redirects the shopper back to `/orders/{ref}/thank-you` (return URL) and also calls the server (callback/webhook) to confirm the result; the server sets `payment_status` to `paid`, `failed` (or leaves `pending`). The thank-you page must show the state the server knows, not a state taken from the URL.
3. Outcomes the frontend has designed: **paid**, **pending** (gateway still confirming), **failed**, **cancelled by the shopper**. `cancelled` is not in the payment status list (`pending`, `paid`, `failed`, `refunded`): TBD whether the server maps a shopper cancel to `failed` plus a reason, or to the order status `cancelled`.
4. Retry: "Try payment again" needs an action that restarts payment for the same order (TBD route, for example `POST /orders/{ref}/pay`). The failed/cancelled page also says "We'll hold your items for 30 minutes" (TODO decide the real stock-hold expiry and the job that releases stock).
5. The bag is cleared when the order is paid or pending, and kept on failed and cancelled.
6. No order confirmation email exists yet, so the pages never claim one was sent (TODO show an "email sent" line once order emails exist).
7. Prototype stand-in: `html/proto-onepay.html` (no shell, no brand marks) with four buttons that go to `thank-you.html?state=paid|pending|failed|cancelled`. The order is kept in sessionStorage with a reference like `KYA-10234` (TODO real format).

### 3.7 Thank-you (`/orders/{ref}/thank-you`)
Minimal shell as for checkout. Prototype-only `?state=paid|pending|failed|cancelled` (paid by default); in Laravel the state comes from the order's payment status.

Data needed for every state: order reference, items (name, size, colour, quantity, price), subtotal, delivery fee, total, delivery address (name, lines, city, district, phone), the district's estimated delivery text, payment line "Card - Onepay" with the payment status.

| State | Icon | Heading | Buttons | Status badge |
| --- | --- | --- | --- | --- |
| paid | check, Primary Deep on lilac tint | "Thank you, {first name} - your order is confirmed" + next steps | Track your order (`/track`), Continue shopping; guests also get "Create an account with the same email to see this order later" (`/account/register`) | `status--paid` |
| pending | clock | "We're confirming your payment" - "This can take a few minutes. Please don't pay again." | Refresh status (reloads the page), Contact us (`/contact`) | `status--pending` |
| failed | alert circle, error colour | "Your payment didn't go through" - "If you were charged, contact us with your order reference." + the 30-minute hold line | Try payment again (retry action), Return to bag (`/cart`) | `status--failed` |
| cancelled | alert circle, error colour | "You cancelled the payment" - same copy as failed | Try payment again, Return to bag | `status--cancelled` |

The order reference is shown large with a Copy button (clipboard; announces "Order reference copied").

**Security flag:** `/orders/{ref}/thank-you` exposes a name, address and phone. With a sequential reference (KYA-10234) anyone can enumerate orders. Recommendation: use an unguessable token or a signed URL (for example `/orders/{ref}/thank-you?signature=...` or a random token in the path) and check the session where possible. Decision needed from the backend dev.

### 3.8 Status component (order and payment statuses)
`<span class="status status--paid">Paid</span>`: icon plus text, never colour alone. The mapping is defined once in `components.css` and is reused for order tracking and the account pages:
| Status | Look |
| --- | --- |
| `paid`, `confirmed`, `delivered` | lilac tint + check icon |
| `shipped` | blue tint + truck icon |
| `pending` | neutral outline + clock icon |
| `failed`, `cancelled` | error tint + x icon |
| `refunded` | blue tint + rotate-ccw icon |

### 3.9 Stub pages (track, contact, size-guide, delivery, returns, about, privacy, terms, account/*)
Not designed yet. Only the route and the page name exist. Assumptions for planning, **not** from the prototype: contact will have name, email, message; track will take an order reference and reuse the `.status` component (section 3.8).

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

## 5. Prototype-only behaviour (delete at conversion)

- `assets/js/proto-listing.js`: filters, sorts, searches and paginates the 24 sample cards from the query string; sets H1, intro, breadcrumb, counts, pagination and the empty states. The server does all of this.
- `assets/js/proto-product.js`: applies `?demo=` states and fakes the review form submit. `demo` values: `sale`, `new`, `low-stock`, `oos`, `no-reviews`, `reviewed`, `review-success`, `review-error`, `review-throttle`. Every product card opens the same sample product.
- `assets/js/proto-cart.js`: the demo cart (sessionStorage) behind the header counts, the drawer, the cart page and the checkout summary, plus the simulated order. Line shape: `{id, productSlug, name, colourSlug, colourLabel, sizeSlug, sizeLabel, unitPrice, wasPrice, qty, stock, tone}`.
- `assets/js/proto-cart-page.js`, `proto-checkout.js`, `proto-thankyou.js`, `proto-onepay.js` and `html/proto-onepay.html`: render the pages from the demo cart/order and fake the payment hand-off. Demo params: cart `empty`, `oos-line`, `low-stock`, `price-changed`, `free-delivery`, `checkout-oos`; checkout `errors`, `throttle`, `gateway-error`, `signed-in`, `empty`; thank-you `state=paid|pending|failed|cancelled`.
- `html/review.html` is a review index for the client; remove it.

Production JS that stays: `assets/js/app.js` (shell: menus, drawers, mega menu, search toggle, steppers, active-nav marking), `product.js` (variants, gallery filtering, stock note, quantity cap, size validation, sticky bar, show-more reviews), `cart.js` (sticky checkout bar), `checkout.js` (validation, error summary, delivery estimate, loading state) and `thank-you.js` (copy reference, refresh). The drawer in `app.js` currently renders from the demo cart: in Laravel it swaps in the server panel. Remove the `data-demo-cart` attribute from the buy form when the real cart exists.
