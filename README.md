# Kayaa — storefront

Laravel 12 + Filament 4. Baby clothing store, Sri Lanka. Built from the approved mockup and the build plan in the project.

## First run (Windows, MySQL)

Needs PHP 8.2+, Composer, Node 18+, and a local MySQL server. Laravel Herd or Laragon gives you all of these.

```bash
# 1. Create the database in MySQL
#    CREATE DATABASE kayaa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 2. Install
composer install
npm install

# 3. Configure — copy .env.example to .env, set DB_USERNAME / DB_PASSWORD and ADMIN_PASSWORD
copy .env.example .env

# 4. Key, tables, seed data, storage link
composer run setup
#    (= key:generate, migrate --seed, storage:link)

# 5. Build the CSS/JS once, then run the app
npm run build
php artisan serve
```

Re-run `npm run build` whenever you edit `resources/css/app.css` or `resources/js/app.js` (it takes ~100ms). If you're iterating on styling and want changes to appear without rebuilding, run `npm run dev` in a second terminal instead — that's Vite's watcher with hot reload, not a second copy of the site. Production only ever uses the built files in `public/build/`.

Storefront: http://localhost:8000 · Admin: http://localhost:8000/admin (login with ADMIN_EMAIL / ADMIN_PASSWORD from `.env`).

The seeder creates the "Baby age" size scale, the Baby department with its categories, seven colours, and eight **placeholder products with no photos** so the storefront has something to show. Delete them in admin (or remove `DemoProductSeeder` from `DatabaseSeeder`) once real products are in.

## What's here

| Area | Files |
|---|---|
| Schema | `database/migrations/2026_01_01_*` — size scales/options, departments, categories, colours, products, variants, images, collections, orders, order items. Prices are integer cents. |
| Models | `app/Models/` |
| Cart & checkout logic | `app/Services/CartService.php` (session cart), `app/Services/OrderService.php` (transaction + `lockForUpdate`, server-side totals, stock decrement) |
| Storefront | `app/Http/Controllers/`, `resources/views/store/`, layout in `resources/views/components/layouts/store.blade.php`, CSS from the mockup in `resources/css/app.css` |
| Admin | `app/Filament/Resources/` — Products (variants + photos inline), Categories, Departments, Colours, Size scales, Orders (view + status actions) |
| Store settings | `config/kayaa.php` — shipping fee, free-shipping threshold, districts; values come from `.env` |

## Routes

`/` home · `/shop` all products (`?size=0–3m`, `?sale=1`, `?sort=price_asc`) · `/baby/bodysuits` category · `/products/{slug}` · `/cart` · `/checkout` · `/orders/{ref}/thank-you` · `/track` · `/size-guide` · `/delivery` `/returns` `/about` `/privacy` `/terms` · `/admin`

## Not done yet (by design)

- **PayHere** — checkout shows only cash on delivery until `PAYHERE_MERCHANT_ID` is set. `CheckoutController::store` has the TODO; the webhook route `payhere/notify` is already CSRF-exempt in `bootstrap/app.php`.
- **Order confirmation email** — `MAIL_MAILER=log` for now.
- **Product photos** — upload in admin → Products → Photos (4:5 portrait). Card and gallery fall back to a placeholder until then.
- Customer accounts, discount codes, reviews — v2 per the plan.

## Deploying to cPanel

See §9 of the Laravel reference doc in the project. Short version: point the document root at `public/`, `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `php artisan storage:link`, then `config:cache route:cache view:cache`. Set `APP_DEBUG=false`.
