# Kayaa — Handoff

*Updated 2026-11-02. The storefront, checkout and payment sections were re-checked against the code on branch `redesign-frontend`; the Admin section describes the admin overhaul plus the follow-up (refunds, stock history, admin team, guest-order linking). Only what exists in the code is listed here.*

## What it is
Online store for Kayaa, a Sri Lankan baby clothing brand. Laravel 12 + Filament 4 (PHP 8.2+), Blade storefront with Vite-built CSS/JS, MySQL locally. One department (Baby) is live in the data; the schema supports more.

- Repo: `C:\Users\ASUS\Documents\kayaa` (single branch `master`, pushed to `origin`)
- Design source: `C:\Users\ASUS\Documents\kayaa-baby-clothing-app\project\` (tokens, components, 13-screen mobile prototype). `resources/css/app.css` is built from those tokens.
- Setup steps: see `README.md` (`composer install`, `npm install`, copy `.env`, `composer run setup`, `npm run build`, `php artisan serve`).

## Working tree
- Storefront work lives on `redesign-frontend`; the admin overhaul is a separate branch on top of it (see the admin section below).
- **Tests:** `vendor/bin/phpunit` (or `php artisan test`). `phpunit.xml` runs against in-memory SQLite, so no database or `.env` is needed. `tests/Feature/Admin/` covers admin access, the dashboard widgets, the order workflow and packing slip, product delete/duplicate/bulk rules, customers, store settings, refunds, stock history, the admin team and guest-order linking. `tests/Feature/Storefront/` covers the cart, checkout, overselling, customer cancellation, Onepay payments and the Onepay security rules (`OnepaySecurityTest`). Builders live in `tests/Concerns/`: `BuildsShopData` (records), `PlacesStorefrontOrders` (drives the real checkout form) and `FakesOnepay` (credentials and canned gateway responses; no test touches the network). GitHub Actions `tests.yml` has two jobs on every push and PR: PHPUnit on SQLite, and the same suite on a `mysql:8` service (it sets `DB_CONNECTION`/`DB_HOST`/`DB_DATABASE`/... in the job env, because `phpunit.xml` `<env>` entries never override a variable that is already set). Neither deploys.
- **Running the suite on MySQL locally:** create a throwaway database yourself (`kayaa_phpunit`) and set `DB_CONNECTION=mysql` and `DB_DATABASE=kayaa_phpunit` in the shell before `php vendor\bin\phpunit`. `RefreshDatabase` wipes whatever database it connects to, so **never** point it at `kayaa`. The local server is MariaDB, not MySQL 8, so CI's MySQL job is still the closer match to production.
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
| `2026_11_02_000010_add_refunded_at_to_orders` | `refunded_at` |
| `2026_11_02_000020_create_stock_movements_table` | `stock_movements` (append-only stock log; see Admin) |

Rules in the code:
- Prices are integer cents. Admin enters rupees; the form converts.
- Sizes are `size_option_id` on a variant, ordered by `position` within a size scale.
- Order statuses: `pending, confirmed, shipped, delivered, cancelled`. Payment statuses: `pending, paid, failed, refunded`.
- **A cancelled or refunded order is not a sale.** `Order::scopeCountsAsSale` is the one definition; `SalesReport` and the customer totals use it, and `TopSellers` applies the same two conditions to its raw query. A refunded payment stays refunded: `Order::markPaid()` is a no-op on it, so a retried gateway callback or a delivered COD parcel cannot flip it back to paid.
- Customers and admins share the `users` table; `is_admin` gates `/admin`.

## Cart and checkout
- `app/Services/CartService.php` — cart kept in the session.
- `app/Services/OrderService.php::placeOrder` — one DB transaction, variants locked with `lockForUpdate`, totals recalculated server-side, **stock decremented when the order is placed**, order created with `status=pending`, `payment_status=pending`. Throws `OutOfStockException` → back to cart with a message (only when stock runs out between reading the cart and taking the lock: `CartService::lines()` already caps or drops lines at live stock, see Known gaps). Stock moves with query-builder `increment`/`decrement` (no model events) and `OrderService` records each change as a `StockMovement` itself.
- Checkout (`CheckoutController`): guest or signed-in (`user_id` set when signed in), Sri Lankan mobile validation, district list from `config/kayaa.php`, rate-limited 10/min.
- **Payment: cash on delivery, or Onepay (not PayHere).** `app/Services/OnepayGateway.php` talks to Onepay; `PaymentController` handles `POST /orders/{order}/pay` (start and retry), `GET /orders/{order}/payment/return` and `POST /payment/callback` (server-to-server, CSRF-exempt in `bootstrap/app.php`; trusted only after a status lookup confirms the transaction). `PaymentController::settle()` is where both end up, and it refuses to settle when: the order is already paid or refunded; the order is not an `onepay` order; the transaction id is already recorded as `payment_reference` on a different order; or the status response carries an amount that differs from the order total, or a reference (`additional_data` / `additionalData` / `reference`) that is not this order's. Amount and reference are only compared when the response has them (`OnepayGateway::transactionStatus` returns them as `amount` in cents and `reference`, or null). A refused transaction leaves the order exactly as it was: not paid, and not marked failed either. The online option is offered at checkout only when `OnepayGateway::configured()` is true, i.e. `ONEPAY_APP_ID`, `ONEPAY_HASH_SALT` and `ONEPAY_APP_TOKEN` are set (`ONEPAY_BASE_URL` defaults to `https://api.onepay.lk`). Until those are set on the server, the shop is effectively COD-only.
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
- A customer's orders = orders with their `user_id` (`User::allOrders`). Earlier guest orders are attached to the account at sign-in or registration, but only when **both** the email and the phone number match; a matching email alone proves nothing, and an account with no phone claims nothing. The rule is `User::claimableGuestOrders()` / `User::claimGuestOrders()`; the sign-in controller and the admin's *Link guest orders* action both call it.
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
**Menu** (groups set in `AdminPanelProvider`, per-resource `$navigationGroup`): *Sales* (Orders, Customers) · *Catalog* (Products, Categories, Departments, Colours, Size scales, Stock history) · *Engagement* (Reviews, Messages, FAQs) · *Storefront* (Banners, Contact details, Store settings, Admin team). Global search (Ctrl/Cmd+K) finds orders (reference, phone, email, name), products (name, slug, SKU) and customers.

**Branding:** Deep Wisteria `#6E5A8C` as the primary colour (the storefront's `--primary-deep`), a solid-colour logo at `public/assets/img/admin/logo.svg` (the footer wordmark is white, so it can't be reused), `public/favicon.svg`.

**Dashboard** (`app/Filament/Widgets/`, ordered by `$sort`; they lazy-load; figures come from `App\Support\SalesReport`, which ignores cancelled and refunded orders):
- `StoreStats` — sales today (vs yesterday) and this month, orders to action (to confirm / to ship), reviews and messages waiting. Refreshes every 60s.
- `SalesChart` — revenue per day, 7 / 30 / 90 day filter.
- `LatestOrders`, `LowStock` (active variants at or below the Store settings threshold, including sold out), `TopSellers` (last 30 days by units; cancelled and refunded orders excluded).

**Orders** — tabs with live counts (All / To confirm / To ship / Shipped / Delivered / Cancelled); filters for payment status, method, district and a placed-between date range; payment badges distinguish paid, failed, refunded and pending; row WhatsApp link; bulk *Confirm* and *Mark shipped* (orders in the wrong state are skipped and counted). The order page shows items with SKU, a fulfilment block (courier, tracking number, shipped-email status, internal note) and a timeline (placed / paid / shipped / delivered / cancelled). Header actions: Confirm, **Mark shipped** (asks for courier and tracking, then emails the customer once via `OrderMailer::sendShipped` if the order has an email), Mark delivered (a COD order becomes paid), Mark paid (hidden once paid or refunded), **Mark refunded** (shown when `payment_status` is paid; sets `refunded`, stamps `refunded_at` on the timeline and appends an optional note to the internal note as `Refund: ...`; it records a refund made outside the app, it does not send money, and it does not restock), Cancel (restocks; warns when the order is already paid, because cancelling is not a refund), and under *More*: **Print packing slip** (`GET /admin/orders/{order}/packing-slip`, admin-only, includes the COD amount to collect), WhatsApp, **Add note** (staff-only, never shown to the customer). All status rules live in `OrderService` (`confirm`, `ship`, `deliver`, `refund`, `cancel`) so the buttons and bulk actions cannot disagree.

**Products** — list shows a photo, SKUs, price range, a stock badge (sold out / low / ok, using the Store settings threshold) and "n variants low or out"; filters for status, category and *Low or sold out*; **Duplicate** (a draft copy with new slug, SKUs and its own copies of the photo files — the upload field deletes files on removal, so copies must never share one); bulk Publish / Move to draft / Archive / Delete. **A product that appears in any order cannot be deleted** (enforced in `Product::booted()`, so every screen respects it); the edit page offers *Archive* instead.

**Customers** — list shows orders, total spent (cancelled orders excluded) and last order; the customer page shows account details, spending (total, average order, last order) and an order-history table linking to each order. A customer with orders cannot be deleted. Only orders linked by `user_id` count (see `User::allOrders`), so a guest order whose email and phone were never matched at sign-in is not on the page. Total spent and the average exclude cancelled and refunded orders. The **Link guest orders** header action (shown only when something matches, with a badge and a confirmation that say how many) links unowned orders whose email AND phone match this customer.

**Stock history** (Catalog) — read-only log of every stock change (`stock_movements`: variant, snapshot label and SKU, delta, stock after, reason, order, user, note, time), filterable by reason and product. Reasons: `initial` (a new variant with stock, including a duplicated product), `order_placed`, `order_cancelled`, `admin_edit` (stock changed through the product form, with the signed-in user). Two recording paths, never both for one change: `OrderService` uses query-builder increment/decrement (which fire no model events) and calls `StockMovement::record()` itself; `ProductVariant`'s `created`/`updated` events cover the rest. Any new code that changes stock through the model will be logged as an admin edit; code that wants a different reason must use the query builder and record it explicitly.

**Admin team** (Storefront) — the users with `is_admin = true`: create (password required) and edit (password optional, blank keeps the old one). An admin cannot delete themselves and the last remaining admin cannot be deleted or demoted; the rule is in `User` (`canBeDeleted()`, a `deleting` and an `updating` hook), so every screen and script obeys it, and the delete buttons use the same method.

**Others** — Banners (topbar / home_promo, ink/cream/butter style, on/off, date window, position) · Messages (contact inbox, unread badge) · Reviews (approve / hide, bulk approve, admin reply, pending badge) · FAQs · **Contact details** (`App\Filament\Pages\ContactSettings` — store email, call number, WhatsApp number) · **Store settings** (`App\Filament\Pages\StoreSettings` — delivery fee, free-delivery threshold, low-stock warning level) · **Site availability** (`App\Filament\Pages\SiteAvailability` — live / coming soon / under maintenance, the heading, message and date on each notice, and whether signed-in admins keep browsing the shop; the sidebar shows a badge while a notice is up).

Account: `->profile(isSimple: false)` on the panel adds `/admin/profile`, where the signed-in admin changes their name, email and **password** (Filament's built-in page; the current password is required). Reachable from the user menu, top right.

## Seed data (`DatabaseSeeder`)
AdminUser (from `ADMIN_EMAIL` / `ADMIN_PASSWORD`) · SizeScale (Baby age) · CatalogStructure (Baby department + categories, colours) · Banner (topbar + promo) · Faq · **DemoProduct — 8 placeholder products with no photos**, still enabled.

## Config / env
`config/kayaa.php`: currency LKR, shipping fee, free-shipping threshold, WhatsApp number (`STORE_WHATSAPP`), call number (`STORE_PHONE`), store email, `noindex`, the 25 districts, site mode and the two notice texts.
- **Contact details are DB-backed.** The `settings` table (key/value, `App\Models\Setting`, cached forever, busted on save) holds `contact_email / contact_phone / contact_whatsapp`. `AppServiceProvider::bootContactSettings` pushes them over `config('kayaa.*')` at boot, so views keep reading config. Env values are the fallback when a setting is blank. `Setting::map()` swallows a missing table so the site still renders before the migration runs.
- **Store numbers are DB-backed too.** Store settings saves `low_stock_threshold`, `shipping_fee` and `free_shipping_over` (whole rupees) to the same `settings` table; `AppServiceProvider::bootStoreSettings` overlays them on `config('kayaa.*')` at boot (fee and threshold are converted to cents). `0` is a real value ("free delivery on everything"), not "unset". Existing orders keep the fee they were placed with.
- **Site availability is DB-backed too.** Site availability saves `site_mode` (`live` / `coming_soon` / `maintenance`), `site_mode_admin_bypass` and the heading, message and date of each notice to the `settings` table; `AppServiceProvider::bootSiteMode` overlays them on `config('kayaa.*')` at boot. `App\Support\SiteMode` is the single place that answers "which mode, and what does the notice say" (an unknown stored mode reads as `live`). `App\Http\Middleware\SiteAvailability`, appended to the **web** group in `bootstrap/app.php`, serves `resources/views/store/site-notice.blade.php` in place of every storefront page: 200 for coming soon (it is the site's public face before launch) and 503 + `Retry-After` for maintenance, both `no-store` and `X-Robots-Tag: noindex, nofollow`. Always open whatever the mode: `/admin` and what it needs (Livewire, Filament assets) — the panel runs its own middleware stack, so the owner can never lock themselves out — plus `/up`, `/deploy/*`, the Onepay callback and the payment return, so a payment started just before the notice went up still reconciles. A signed-in admin keeps browsing the real shop (with a `.mode-band` across the top) unless the bypass toggle is off. Env fallbacks: `STORE_SITE_MODE`, `STORE_SITE_MODE_ADMIN_BYPASS`.
- `App\Support\StoreContact` builds the `tel:` and `wa.me` links (adds country code 94 to local numbers); `Order::whatsappUrl()` uses it for the admin's WhatsApp buttons.
Other env in use: `ONEPAY_APP_ID / ONEPAY_HASH_SALT / ONEPAY_APP_TOKEN / ONEPAY_BASE_URL`, `MAIL_MAILER` (log), `APP_NOINDEX`, `STORE_LOW_STOCK_THRESHOLD`, `STORE_SITE_MODE`, `STORE_SITE_MODE_ADMIN_BYPASS`.

## Staging / deployment
- GitHub Actions `.github/workflows/main.yml` (“Deploy Website”): on push to `master` or manual run → PHP 8.4, `composer install --no-dev`, Node 22 `npm ci && npm run build`, FTP sync to `./` using secrets `FTP_SERVER / FTP_USERNAME / FTP_PASSWORD`. Excludes `.env*`, `node_modules`, `tests`, git files, storage caches/logs. After the sync, a step POSTs to `DEPLOY_URL` with an `X-Deploy-Token` header; [`DeployController`](app/Http/Controllers/DeployController.php) runs `migrate --force` then `config:cache route:cache view:cache` and returns the artisan output as plain text, so it shows in the Actions log. **This host (Peek, cPanel) has no SSH** — only port 21 is open, so the earlier `appleboy/ssh-action` approach was removed. The site sits behind **Cloudflare**, which serves a managed challenge to GitHub runner IPs, so the step sets `--resolve` from `DEPLOY_ORIGIN_IP` (104.255.169.49) to reach the origin directly — SNI and Host stay correct, so TLS still verifies. LiteSpeed also 403s a POST with no `Content-Type`, hence the `{}` body. Secrets: `DEPLOY_URL`, `DEPLOY_TOKEN` and `DEPLOY_ORIGIN_IP`; the step skips if either is unset, and the route 404s unless `DEPLOY_TOKEN` matches the server `.env`. The server `.env` is managed by hand.
- **Server PHP must load `ext-intl`.** `composer.json` requires it, but `composer install` runs on the GitHub runner (where it is always present), so that check never reaches this host — the sync just copies a `vendor/` that needs an extension cPanel may not be loading. Filament renders a table's row count and pagination summary with `Illuminate\Support\Number::format()`, which throws `RuntimeException: The "intl" PHP extension is required` without it. The symptom is distinctive and misleading: `/admin` and `/admin/login` are fine (the dashboard's stat widgets don't use `Number`), while **every** resource list page 500s. `DeployController` now checks `REQUIRED_EXTENSIONS` first and refuses with a plain-text list of what's missing plus the PHP binary and ini files that served the request, so this shows in the Actions log instead of as a 500.
- **Two PHP families live on this host, and only one of them matters.** The domains run **`ea-php84`** (MultiPHP Manager, and the cPanel-generated `AddHandler application/x-httpd-ea-php84` block in the *server's* `public/.htaccess`). cPanel's **Select PHP Version > Extensions** page is the CloudLinux **PHP Selector**, which configures **`alt-php`** builds only — the server also carries `alt-php72` and friends. Ticking an extension there has no effect on an `ea-php` site: it reads as enabled in cPanel and stays missing at runtime, with the same version number on both sides (8.4.27), which is what made the `intl` outage hard to place. Adding an extension to `ea-php` means installing an EasyApache package (`ea-php84-php-intl`), which needs root — ask the host. Switching a domain to `alt-php84` is the self-service alternative, but the Selector's defaults leave **`pdo_mysql` unticked**, so check the whole list before applying or the database goes down instead.
- **The cPanel handler block is not in git.** The repo's `public/.htaccess` has the rewrite rules only; the `AddHandler` block exists just on the server, where cPanel appends and rewrites it. The FTP sync uploads `public/.htaccess`, so the next time that file changes in the repo the deploy overwrites the server copy and strips the handler, dropping the site to the system default (**PHP 8.2, `ea-php82`**) with no warning. FTP-Deploy-Action only sends changed files, which is the only reason this has not fired yet. Either exclude `public/.htaccess` from the sync and treat it as server-managed, or commit the handler block so the repo copy is authoritative — but then remember to mirror any PHP-version change made in cPanel.
- `GET /deploy/logs` ([`DeployLogController`](app/Http/Controllers/DeployLogController.php)) returns the tail of `storage/logs/laravel.log` as plain text, guarded by the same `X-Deploy-Token` header as `/deploy/migrate` and 404ing when `DEPLOY_TOKEN` is unset. With no SSH and `APP_DEBUG=false`, a 500 is otherwise a dead end. `?bytes=N` caps the tail (default and maximum 256 KB): `curl -H "X-Deploy-Token: …" https://…/deploy/logs?bytes=40000`.
- Staging is kept out of search engines two ways:
  - `NoIndex` middleware sends `X-Robots-Tag: noindex, nofollow` on every Laravel response when `APP_NOINDEX=true`.
  - `public/.htaccess` sets the same header unconditionally for static files. **This block must be deleted before production** (Apache can't read `.env`).

## Known gaps in the current code
- Onepay is built and tested against faked responses, but has never been exercised against live credentials. The amount and reference checks in `settle()` assume Onepay's status response uses the key names `OnepayGateway::transactionStatus` looks for; if a real response puts a *different* value under `amount` (e.g. a fee-inclusive figure) or `reference` (e.g. Onepay's own reference), a genuine payment would be refused and the order left pending (a warning is logged: "Onepay transaction does not match the order"). Check against the sandbox before going live.
- The Onepay return URL is signed by Laravel, and a signature covers the whole query string. If Onepay appends `?transaction_id=...` to `transaction_redirect_url`, a guest's browser return will fail the signature check (404). The server-to-server callback still settles the order; signed-in owners are unaffected. Untested against the real gateway.
- Cart quirks found while writing tests (not fixed): `CartService::count()` and `isEmpty()` count the raw cart, while `lines()` drops deactivated or sold-out variants, so the header shows "Your bag (2 items)" with one line listed. A POST to checkout whose lines have all been dropped, or with an empty cart, ends in a bare HTTP 400 ("Your cart is empty.") instead of a redirect to the cart. For the same reason a shopper whose line is capped by live stock gets the capped order silently, and the friendly out-of-stock redirect only appears when stock runs out between reading the cart and taking the lock.
- Mail is log-only until `MAIL_*` is configured on the server, so neither the confirmation nor the shipped email reaches customers yet.
- Placeholder products still seeded; no real product photos.
- *Mark refunded* records a refund, it does not make one through Onepay: refunds of online orders are still done by hand first.
- Not built: replying to contact messages from the admin.
- `project` doc `claude/kayaa-implementation-status.md` (2026-09-17) predates accounts, reviews, FAQs, cart drawer and the deploy workflow — this file supersedes it.
