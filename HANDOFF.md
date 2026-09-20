# Kayaa — Handoff

*Current state as of 2026-09-20, checked against the code at commit `416a6bc` (branch `master`). Only what exists in the code is listed here.*

## What it is
Online store for Kayaa, a Sri Lankan baby clothing brand. Laravel 12 + Filament 4 (PHP 8.2+), Blade storefront with Vite-built CSS/JS, MySQL locally. One department (Baby) is live in the data; the schema supports more.

- Repo: `C:\Users\ASUS\Documents\kayaa` (single branch `master`, pushed to `origin`)
- Design source: `C:\Users\ASUS\Documents\kayaa-baby-clothing-app\project\` (tokens, components, 13-screen mobile prototype). `resources/css/app.css` is built from those tokens.
- Setup steps: see `README.md` (`composer install`, `npm install`, copy `.env`, `composer run setup`, `npm run build`, `php artisan serve`).

## Working tree
- Last commit `416a6bc "features"` (2026-09-20) added accounts, reviews, FAQs, customers admin, cart drawer.
- Uncommitted: `.github/workflows/main.yml` and `.env.example` show as modified, but the diff is line endings only — no content change.
- `tests/` is empty. There are no automated tests.
- `.kayaa-src.tar.gz` in the repo root is an old source snapshot, not used by the app.

## Data model (migrations in `database/migrations/`)
| Migration | Tables |
|---|---|
| `0001_01_01_*` | users (+ `is_admin`), cache, jobs |
| `2026_01_01_000010_create_catalog_tables` | size_scales, size_options, departments, categories, colors, products, product_variants, product_images, collections, collection_product |
| `2026_01_01_000020_create_order_tables` | orders, order_items |
| `2026_01_01_000030_create_banners_and_messages_tables` | banners, contact_messages |
| `2026_09_20_000010_create_accounts_reviews_faqs_tables` | adds `users.phone`, `orders.user_id` (nullable), index on `orders.email`; creates reviews, faqs |

Rules in the code:
- Prices are integer cents. Admin enters rupees; the form converts.
- Sizes are `size_option_id` on a variant, ordered by `position` within a size scale.
- Order statuses: `pending, confirmed, shipped, delivered, cancelled`. Payment statuses: `pending, paid, failed, refunded`.
- Customers and admins share the `users` table; `is_admin` gates `/admin`.

## Cart and checkout
- `app/Services/CartService.php` — cart kept in the session.
- `app/Services/OrderService.php::placeOrder` — one DB transaction, variants locked with `lockForUpdate`, totals recalculated server-side, **stock decremented when the order is placed**, order created with `status=pending`, `payment_status=pending`. Throws `OutOfStockException` → back to cart with a message.
- Checkout (`CheckoutController`): guest or signed-in (`user_id` set when signed in), Sri Lankan mobile validation, district list from `config/kayaa.php`, rate-limited 10/min.
- **Payment: cash on delivery only in practice.** If `payhere` is picked while `PAYHERE_MERCHANT_ID` is empty, it is silently placed as COD. There is no PayHere redirect and no `payhere/notify` route — only the CSRF exemption for that path exists in `bootstrap/app.php`. TODO marker at `CheckoutController::store`.
- Shipping: `STORE_SHIPPING_FEE` (default Rs 450), free over `STORE_FREE_SHIPPING_OVER` (default Rs 7,500).
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
- A customer's orders = orders with their `user_id` **plus** earlier guest orders with the same email (`User::allOrders`).
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
Products (variants and photos inline, 4:5 photos, New/Featured toggles) · Categories · Departments · Colours · Size scales · Orders (view, Confirm, Mark shipped, filters by status/payment, pending badge) · Banners (topbar / home_promo, ink/cream/butter style, on/off, date window, position) · Messages (contact inbox, unread badge) · Reviews (approve / hide, bulk approve, admin reply, pending badge) · FAQs · Customers (non-admin users, order count; list only).

## Seed data (`DatabaseSeeder`)
AdminUser (from `ADMIN_EMAIL` / `ADMIN_PASSWORD`) · SizeScale (Baby age) · CatalogStructure (Baby department + categories, colours) · Banner (topbar + promo) · Faq · **DemoProduct — 8 placeholder products with no photos**, still enabled.

## Config / env
`config/kayaa.php`: currency LKR, shipping fee, free-shipping threshold, WhatsApp number (`STORE_WHATSAPP`), store email, `noindex`, the 25 districts.
Other env in use: `PAYHERE_MERCHANT_ID/SECRET/SANDBOX` (empty), `MAIL_MAILER` (log), `APP_NOINDEX`.

## Staging / deployment
- GitHub Actions `.github/workflows/main.yml` (“Deploy Website”): on push to `master` or manual run → PHP 8.4, `composer install --no-dev`, Node 22 `npm ci && npm run build`, FTP sync to `./` using secrets `FTP_SERVER / FTP_USERNAME / FTP_PASSWORD`. Excludes `.env*`, `node_modules`, `tests`, git files, storage caches/logs. After the sync, an SSH step runs `php artisan migrate --force` then `config:cache route:cache view:cache` on the server, using secrets `SSH_HOST / SSH_USERNAME / SSH_PRIVATE_KEY / SSH_APP_PATH` (optional `SSH_PORT`). **If `SSH_HOST` is unset the step is skipped and migrations do not run** — that is what left `reviews`/`faqs` missing in production on 2026-09-20 and 500'd `/contact` and every product page. The server `.env` is managed by hand.
- Staging is kept out of search engines two ways:
  - `NoIndex` middleware sends `X-Robots-Tag: noindex, nofollow` on every Laravel response when `APP_NOINDEX=true`.
  - `public/.htaccess` sets the same header unconditionally for static files. **This block must be deleted before production** (Apache can't read `.env`).

## Known gaps in the current code
- No PayHere integration (redirect or webhook).
- No order confirmation email; mail is log-only.
- Placeholder products still seeded; no real product photos.
- No tests.
- Customer admin is read-only list.
- `project` doc `claude/kayaa-implementation-status.md` (2026-09-17) predates accounts, reviews, FAQs, cart drawer and the deploy workflow — this file supersedes it.
