# Kayaa — Handoff

*Updated 2026-11-01. The storefront, checkout and payment sections were re-checked against the code on branch `redesign-frontend` (`d832c2b`); the Admin section describes the admin overhaul on top of it. Only what exists in the code is listed here.*

## What it is
Online store for Kayaa, a Sri Lankan baby clothing brand. Laravel 12 + Filament 4 (PHP 8.2+), Blade storefront with Vite-built CSS/JS, MySQL locally. One department (Baby) is live in the data; the schema supports more.

- Repo: `C:\Users\ASUS\Documents\kayaa` (single branch `master`, pushed to `origin`)
- Design source: `C:\Users\ASUS\Documents\kayaa-baby-clothing-app\project\` (tokens, components, 13-screen mobile prototype). `resources/css/app.css` is built from those tokens.
- Setup steps: see `README.md` (`composer install`, `npm install`, copy `.env`, `composer run setup`, `npm run build`, `php artisan serve`).

## Working tree
- Storefront work lives on `redesign-frontend`; the admin overhaul is a separate branch on top of it (see the admin section below).
- **Tests:** `vendor/bin/phpunit` (or `php artisan test`). `phpunit.xml` runs against in-memory SQLite, so no database or `.env` is needed. `tests/Feature/Admin/` covers admin access, the dashboard widgets, the order workflow and packing slip, product delete/duplicate/bulk rules, customers, and store settings. `tests/Concerns/BuildsShopData.php` has the builders. The storefront (cart, checkout, Onepay) has no tests yet. GitHub Actions `tests.yml` runs the suite on every push and PR; it never deploys.
- Tests run on SQLite, production is MySQL. Avoid grouped Eloquent queries in Filament tables (it appends `ORDER BY id`, which MySQL strict mode rejects for grouped rows); `TopSellers` uses `->records()` for that reason.
- **Secrets and archives are not committed.** `.gitignore` blocks `.env.*` (except `.env.example`), private keys, `*.tar.gz`, `*.tgz`, `*.zip` and `*.sql`. The old `.kayaa-src.*` source snapshots were removed from git (they held only `.env.example`, but they were dead weight). Keep `.env`, SSH keys, database dumps and full-project tarballs outside this repo folder, and `.vscode/sftp.json` (FTP password) stays git-ignored.

## Data model (migrations in `database/migrations/`)
| Migration | Tables |
|---|---|
| `0001_01_01_*` | users (+ `is_admin`), cache, jobs |
| `2026_01_01_000010_create_catalog_tables` | size_scales, size_options, departments, categories, colors, products, product_variants, product_images, collections, collection_product |
| `2026_01_01_000020_create_order_tables` | orders, order_items |
| `2026_01_01_000030_create_banners_and_messages_tables` | banners, contact_messages |
| `2026_09_20_000010_create_accounts_reviews_faqs_tables` | adds `users.phone`, `orders.user_id` (nullable), index on `orders.email`; creates reviews, faqs |
| `2026_09_20_000020_*`, `2026_09_20_000030_*` | settings (key/value), cart and wishlist items |
| `2026_10_03_000010_add_fulfilment_columns_to_orders` | `delivered_at`, `cancelled_at`, `confirmation_sent_at` |
| `2026_11_01_000010_add_tracking_to_orders` | `courier`, `tracking_number`, `admin_note` (staff-only), `shipped_email_sent_at` |

Rules in the code:
- Prices are integer cents. Admin enters rupees; the form converts.
- Sizes are `size_option_id` on a variant, ordered by `position` within a size scale.
- Order statuses: `pending, confirmed, shipped, delivered, cancelled`. Payment statuses: `pending, paid, failed, refunded`.
- Customers and admins share the `users` table; `is_admin` gates `/admin`.

## Cart and checkout
- `app/Services/CartService.php` — cart kept in the session.
- `app/Services/OrderService.php::placeOrder` — one DB transaction, variants locked with `lockForUpdate`, totals recalculated server-side, **stock decremented when the order is placed**, order created with `status=pending`, `payment_status=pending`. Throws `OutOfStockException` → back to cart with a message.
- Checkout (`CheckoutController`): guest or signed-in (`user_id` set when signed in), Sri Lankan mobile validation, district list from `config/kayaa.php`, rate-limited 10/min.
- **Payment: cash on delivery, or Onepay (not PayHere).** `app/Services/OnepayGateway.php` talks to Onepay; `PaymentController` handles `POST /orders/{order}/pay` (start and retry), `GET /orders/{order}/payment/return` and `POST /payment/callback` (server-to-server, CSRF-exempt in `bootstrap/app.php`; trusted only after a status lookup confirms the transaction). The online option is offered at checkout only when `OnepayGateway::configured()` is true, i.e. `ONEPAY_APP_ID`, `ONEPAY_HASH_SALT` and `ONEPAY_APP_TOKEN` are set (`ONEPAY_BASE_URL` defaults to `https://api.onepay.lk`). Until those are set on the server, the shop is effectively COD-only.
- Confirmation email: `OrderMailer::sendConfirmation` runs at checkout and records `confirmation_sent_at` only if the message left. With `MAIL_MAILER=log` (the default) nothing is actually delivered; set real mail credentials on the server.
- Shipping: `STORE_SHIPPING_FEE` (default Rs 450) and `STORE_FREE_SHIPPING_OVER` (default Rs 7,500) are the **defaults**. Admin > Storefront > Store settings overrides both (see Config), so the owner no longer needs a developer to change them.
- Cart drawer: `/cart/panel` returns HTML that `resources/js/app.js` swaps into `[data-cart-drawer]`; add/update/remove return the panel when called from JS, a redirect otherwise.

## Storefront routes (`routes/web.php`)
| Route | Notes |
|---|---|
| `/` | hero, shop by age, category tiles, new this week, promo banners, featured products, trust strip |
| `/shop` | age chips, sort (featured / newest / price), sale filter, empty state |
| `/{department}/{category}` e.g. `/baby/bodysuits` | category listing; declared last so static routes win |
| `/products/{slug}` | colour × size picker, size guide toggle, stock note, approved reviews + rating breakdown, review form, FAQs |
| `/cart`, `/cart/panel` | page + drawer |
| `/checkout` | radio cards, ETA by district |
| `/orders/{ref}/thank-you` | confirmation |
| `/track` | lookup by order ref (POST rate-limited 20/min) |
| `/search?q=` | search |
| `/contact` | WhatsApp card, message form (→ admin Messages, 5/min), contact-page FAQs |
| `/size-guide`, `/delivery`, `/returns`, `/about`, `/privacy`, `/terms` | static pages |
| `/products/{product}/reviews` (POST) | 5/min; anyone can post; hidden until approved |
| `/account/*` | see below |
| `/admin` | Filament |
| `/up` | health check |

Layout: `resources/views/components/layouts/store.blade.php` — hamburger drawer + 5-tab bottom bar under 900px; full header nav and category row on desktop.

## Customer accounts (optional — guest checkout and /track still work)
- Register, log in, log out, forgot/reset password (`/account/register|login|forgot-password|reset-password/{token}`), all rate-limited.
- Signed in: `/account` (order list), `/account/orders/{order}`, `/account/reviews`, `/account/profile` (update profile, change password).
- A customer's orders = orders with their `user_id` (`User::allOrders`). Earlier guest orders are attached to the account at sign-in or registration, but only when **both** the email and the phone number match (`CustomerAuthController::claimGuestOrders`); a matching email alone proves nothing.
- Guests are redirected to `/account/login`, not Laravel's `/login`.
- Password-reset email goes through the configured mailer; with `MAIL_MAILER=log` it only lands in `storage/logs`.

## Reviews
- One review per signed-in customer per product; guests must give a name.
- `is_verified_purchase` set automatically when the customer has a confirmed/shipped/delivered order containing the product.
- New reviews are `is_approved=false`; only approved ones show on the product page.

## FAQs
- `placement` = `product` or `contact`. A product FAQ can target one product, one category, or (neither set) every product page. Ordered by `position`.
- `FaqSeeder` seeds starter FAQs.

## Admin (`/admin`, Filament)
**Menu** (groups set in `AdminPanelProvider`, per-resource `$navigationGroup`): *Sales* (Orders, Customers) · *Catalog* (Products, Categories, Departments, Colours, Size scales) · *Engagement* (Reviews, Messages, FAQs) · *Storefront* (Banners, Contact details, Store settings). Global search (Ctrl/Cmd+K) finds orders (reference, phone, email, name), products (name, slug, SKU) and customers.

**Branding:** Deep Wisteria `#6E5A8C` as the primary colour (the storefront's `--primary-deep`), a solid-colour logo at `public/assets/img/admin/logo.svg` (the footer wordmark is white, so it can't be reused), `public/favicon.svg`.

**Dashboard** (`app/Filament/Widgets/`, ordered by `$sort`; they lazy-load; figures come from `App\Support\SalesReport`, which ignores cancelled orders):
- `StoreStats` — sales today (vs yesterday) and this month, orders to action (to confirm / to ship), reviews and messages waiting. Refreshes every 60s.
- `SalesChart` — revenue per day, 7 / 30 / 90 day filter.
- `LatestOrders`, `LowStock` (active variants at or below the Store settings threshold, including sold out), `TopSellers` (last 30 days by units).

**Orders** — tabs with live counts (All / To confirm / To ship / Shipped / Delivered / Cancelled); filters for payment status, method, district and a placed-between date range; payment badges distinguish paid, failed, refunded and pending; row WhatsApp link; bulk *Confirm* and *Mark shipped* (orders in the wrong state are skipped and counted). The order page shows items with SKU, a fulfilment block (courier, tracking number, shipped-email status, internal note) and a timeline (placed / paid / shipped / delivered / cancelled). Header actions: Confirm, **Mark shipped** (asks for courier and tracking, then emails the customer once via `OrderMailer::sendShipped` if the order has an email), Mark delivered (a COD order becomes paid), Mark paid, Cancel (restocks), and under *More*: **Print packing slip** (`GET /admin/orders/{order}/packing-slip`, admin-only, includes the COD amount to collect), WhatsApp, **Add note** (staff-only, never shown to the customer). All status rules live in `OrderService` (`confirm`, `ship`, `deliver`, `cancel`) so the buttons and bulk actions cannot disagree.

**Products** — list shows a photo, SKUs, price range, a stock badge (sold out / low / ok, using the Store settings threshold) and "n variants low or out"; filters for status, category and *Low or sold out*; **Duplicate** (a draft copy with new slug, SKUs and its own copies of the photo files — the upload field deletes files on removal, so copies must never share one); bulk Publish / Move to draft / Archive / Delete. **A product that appears in any order cannot be deleted** (enforced in `Product::booted()`, so every screen respects it); the edit page offers *Archive* instead.

**Customers** — list shows orders, total spent (cancelled orders excluded) and last order; the customer page shows account details, spending (total, average order, last order) and an order-history table linking to each order. A customer with orders cannot be deleted. Only orders linked by `user_id` count (see `User::allOrders`), so a guest order whose email and phone were never matched at sign-in is not on the page.

**Others** — Banners (topbar / home_promo, ink/cream/butter style, on/off, date window, position) · Messages (contact inbox, unread badge) · Reviews (approve / hide, bulk approve, admin reply, pending badge) · FAQs · **Contact details** (`App\Filament\Pages\ContactSettings` — store email, call number, WhatsApp number) · **Store settings** (`App\Filament\Pages\StoreSettings` — delivery fee, free-delivery threshold, low-stock warning level).

Account: `->profile(isSimple: false)` on the panel adds `/admin/profile`, where the signed-in admin changes their name, email and **password** (Filament's built-in page; the current password is required). Reachable from the user menu, top right.

## Seed data (`DatabaseSeeder`)
AdminUser (from `ADMIN_EMAIL` / `ADMIN_PASSWORD`) · SizeScale (Baby age) · CatalogStructure (Baby department + categories, colours) · Banner (topbar + promo) · Faq · **DemoProduct — 8 placeholder products with no photos**, still enabled.

## Config / env
`config/kayaa.php`: currency LKR, shipping fee, free-shipping threshold, WhatsApp number (`STORE_WHATSAPP`), call number (`STORE_PHONE`), store email, `noindex`, the 25 districts.
- **Contact details are DB-backed.** The `settings` table (key/value, `App\Models\Setting`, cached forever, busted on save) holds `contact_email / contact_phone / contact_whatsapp`. `AppServiceProvider::bootContactSettings` pushes them over `config('kayaa.*')` at boot, so views keep reading config. Env values are the fallback when a setting is blank. `Setting::map()` swallows a missing table so the site still renders before the migration runs.
- **Store numbers are DB-backed too.** Store settings saves `low_stock_threshold`, `shipping_fee` and `free_shipping_over` (whole rupees) to the same `settings` table; `AppServiceProvider::bootStoreSettings` overlays them on `config('kayaa.*')` at boot (fee and threshold are converted to cents). `0` is a real value ("free delivery on everything"), not "unset". Existing orders keep the fee they were placed with.
- `App\Support\StoreContact` builds the `tel:` and `wa.me` links (adds country code 94 to local numbers); `Order::whatsappUrl()` uses it for the admin's WhatsApp buttons.
Other env in use: `ONEPAY_APP_ID / ONEPAY_HASH_SALT / ONEPAY_APP_TOKEN / ONEPAY_BASE_URL`, `MAIL_MAILER` (log), `APP_NOINDEX`, `STORE_LOW_STOCK_THRESHOLD`.

## Staging / deployment
- GitHub Actions `.github/workflows/main.yml` (“Deploy Website”): on push to `master` or manual run → PHP 8.4, `composer install --no-dev`, Node 22 `npm ci && npm run build`, FTP sync to `./` using secrets `FTP_SERVER / FTP_USERNAME / FTP_PASSWORD`. Excludes `.env*`, `node_modules`, `tests`, git files, storage caches/logs. After the sync, a step POSTs to `DEPLOY_URL` with an `X-Deploy-Token` header; [`DeployController`](app/Http/Controllers/DeployController.php) runs `migrate --force` then `config:cache route:cache view:cache` and returns the artisan output as plain text, so it shows in the Actions log. **This host (Peek, cPanel) has no SSH** — only port 21 is open, so the earlier `appleboy/ssh-action` approach was removed. The site sits behind **Cloudflare**, which serves a managed challenge to GitHub runner IPs, so the step sets `--resolve` from `DEPLOY_ORIGIN_IP` (104.255.169.49) to reach the origin directly — SNI and Host stay correct, so TLS still verifies. LiteSpeed also 403s a POST with no `Content-Type`, hence the `{}` body. Secrets: `DEPLOY_URL`, `DEPLOY_TOKEN` and `DEPLOY_ORIGIN_IP`; the step skips if either is unset, and the route 404s unless `DEPLOY_TOKEN` matches the server `.env`. The server `.env` is managed by hand.
- Staging is kept out of search engines two ways:
  - `NoIndex` middleware sends `X-Robots-Tag: noindex, nofollow` on every Laravel response when `APP_NOINDEX=true`.
  - `public/.htaccess` sets the same header unconditionally for static files. **This block must be deleted before production** (Apache can't read `.env`).

## Known gaps in the current code
- Onepay is built but has never been exercised against live credentials from this repo; there are no tests for it or for checkout.
- Mail is log-only until `MAIL_*` is configured on the server, so neither the confirmation nor the new shipped email reaches customers yet.
- Placeholder products still seeded; no real product photos.
- Tests cover the admin only. The admin tests run on SQLite; nothing has been run against MySQL in CI.
- Marking an order *cancelled* returns stock but does not change `payment_status`; refunds of paid online orders are manual (no `refunded` action in the admin yet).- `project` doc `claude/kayaa-implementation-status.md` (2026-09-17) predates accounts, reviews, FAQs, cart drawer and the deploy workflow — this file supersedes it.
