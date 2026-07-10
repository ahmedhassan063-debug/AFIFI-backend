# AFIFI Backend

Laravel API backend powering the AFIFI e-commerce platform — catalog, cart, checkout, orders, payments, and admin/CMS management.

## 1. Project Overview

AFIFI Backend is a REST API built with Laravel that serves both the customer-facing storefront and an internal admin dashboard. It covers the full commerce lifecycle: product catalog browsing, cart and wishlist management, checkout with coupons/shipping/inventory reservation, order and payment processing (including refunds), and CMS/settings management for admins — all secured with token-based auth and role/permission-based authorization.

## 2. Tech Stack

- **PHP** 8.3+
- **Laravel** 13.x
- **Laravel Sanctum** — API token authentication
- **Spatie Laravel Permission** — role/permission authorization
- **PHPUnit** — automated testing
- **SQLite** (testing) / MySQL or PostgreSQL (recommended for production — configurable via `.env`)
- **Vite + Tailwind CSS** — asset bundling (admin/auxiliary front-end assets, if used)

## 3. Main Backend Features

- **Catalog** — categories, products, product variants (color/size), brands, collections, tags, size guides
- **Cart & Wishlist** — session/user-scoped cart, quantity validation, wishlist management
- **Checkout & Orders** — cart snapshotting, shipping fee resolution, coupon discounts, stock reservation holds, order creation, status history, shipments, returns
- **Inventory** — stock adjustments, restocks, reservation hold/confirm/release with row-level locking to prevent overselling
- **Payments** — payment record lifecycle (pending → paid), refunds with over-refund protection, automatic order payment-status reconciliation
- **Coupons & Campaigns** — percentage/fixed discounts, usage limits, redemption tracking, campaign-priced products
- **CMS** — homepage sections, banners, about page, FAQ, policy pages, trust strip
- **Settings** — public/admin settings, admin preferences
- **Admin Dashboard** — reporting summary, products, orders, customers, settings, roles, coupons, inventory movements, contact messages, media metadata
- **Auth & Authorization** — Sanctum token auth, Spatie roles/permissions, ownership-based policies for customer resources

## 4. Installation Steps

```bash
# Clone and enter the project
git clone <repository-url> afifi-backend
cd afifi-backend

# Install PHP dependencies
composer install

# Copy environment file and generate app key
cp .env.example .env
php artisan key:generate

# Configure your database in .env, then migrate and seed
php artisan migrate --seed

# (Optional) install and build front-end assets
npm install
npm run build

# Serve the application
php artisan serve
```

## 5. Environment Variables

Key variables in `.env` (see `.env.example` for the full list):

| Variable | Purpose |
|---|---|
| `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` | Core application config |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Database connection |
| `SESSION_DRIVER`, `SESSION_LIFETIME` | Session storage |
| `QUEUE_CONNECTION` | Background job queue driver |
| `CACHE_STORE` | Cache backend |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_FROM_ADDRESS` | Outbound mail |
| `FILESYSTEM_DISK` | Media/file storage disk (local/S3) |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET` | S3-compatible storage (if used) |

For **production**, set `APP_ENV=production`, `APP_DEBUG=false`, and use a persistent database (MySQL/PostgreSQL) rather than SQLite.

## 6. Database: Migrate & Seed

```bash
# Run all migrations
php artisan migrate

# Run migrations and seed reference/demo data (roles, permissions, currencies, shipping, CMS defaults, admin user)
php artisan migrate --seed

# Re-seed only (without re-running migrations)
php artisan db:seed

# Fresh install (drops all tables, re-migrates, and seeds)
php artisan migrate:fresh --seed
```

Seeders include: roles & permissions, admin user, categories, brands, colors, sizes, currencies, governorates, shipping zones/rates, core settings, and CMS defaults.

## 7. Running Tests

```bash
# Run the full test suite
php artisan test

# Run a specific test class
php artisan test --filter=CheckoutTest

# Run only Feature or Unit tests
php artisan test tests/Feature
php artisan test tests/Unit
```

Tests use an in-memory SQLite database (configured in `phpunit.xml`) with `RefreshDatabase`, so they never touch your development database.

**Current test status:** 71 tests passing, 178 assertions.

Test coverage includes: checkout flow (success/failure paths), authentication, authorization (roles/permissions/ownership), `PaymentService` (payment status reconciliation, refund guards), and `InventoryService` (stock reservation lifecycle).

## 8. API Route Groups Overview

All routes are prefixed with `/api`.

| Group | Auth | Description |
|---|---|---|
| `auth/*` | Public (`register`, `login`) / Sanctum (`me`, `logout`, `profile`, `password`) | Authentication & account management |
| `catalog/*` | Public | Categories, products, product variants (browse) |
| `cms/*` | Public | Homepage, banners, about, FAQ, policy pages |
| `settings/public`, `campaigns/active` | Public | Public-facing settings and active campaigns |
| `cart/*`, `wishlist/*` | Sanctum | Cart & wishlist management |
| `addresses/*` | Sanctum | Customer address book (ownership-protected) |
| `checkout` | Sanctum | Cart → order checkout |
| `orders/*`, `returns/*` | Sanctum | Customer order history, cancellation, return requests |
| `admin/*` | Sanctum + `permission:*` | Product/category/variant CRUD, coupons, campaigns, settings, CMS media, order/return status updates, payments/refunds, dashboard |

### Interactive API Documentation (OpenAPI / Swagger)

A full OpenAPI 3.0 specification is available at `public/docs/openapi.yaml`, covering every public and authenticated endpoint grouped by domain (Auth, Catalog, Cart, Wishlist, Address, Checkout, Orders, Payments, Returns, Coupons, Campaigns, CMS, Settings, Admin), with request/response schemas and Bearer token security.

Browse it with Swagger UI (no extra dependencies required) by serving the app and opening:

```
http://localhost:8000/docs/
```

This loads `public/docs/index.html`, which renders `public/docs/openapi.yaml` via the Swagger UI CDN bundle. Click **Authorize** and paste a token obtained from `/auth/login` (as `Bearer <token>`) to try authenticated endpoints directly from the browser.

### Postman Collection

A ready-to-import Postman v2.1 collection is available at `public/docs/postman_collection.json`, covering the same endpoints grouped into folders matching the Swagger tags (Auth, Catalog, Cart, Wishlist, Address, Checkout, Orders, Payments, Returns, Coupons, Campaigns, CMS, Settings, Admin).

To use it:

1. In Postman, click **Import** and select `public/docs/postman_collection.json`.
2. The collection ships with two variables: `base_url` (defaults to `http://127.0.0.1:8000/api`) and `token` (empty). Edit them under the collection's **Variables** tab — update `base_url` if your app runs elsewhere, and set `token` after logging in.
3. Run **Auth > Register** or **Auth > Login**, copy the `token` from the response, and paste it into the collection's `token` variable. All protected requests already send `Authorization: Bearer {{token}}`; public endpoints are marked `No Auth` and skip this header.

## 9. Auth & Permissions Overview

- **Authentication**: Laravel Sanctum issues bearer tokens on register/login (`token` field in the response). Send `Authorization: Bearer <token>` on subsequent requests. Tokens are revoked on logout.
- **Authorization**: [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) provides role/permission-based access control on admin routes via the `permission:*` middleware.
  - **Permissions**: `users.*`, `products.*`, `inventory.*`, `orders.*`, `payments.view`, `payments.update`, `payments.refund`, `coupons.manage`, `campaigns.manage`, `cms.manage`, `settings.manage`, `reports.view`, `roles.view`, `roles.manage`, `contact.view`, `contact.manage`.
  - **Roles**: `super_admin` (all permissions), `catalog_manager`, `fulfillment`, `support`, `marketing` — each scoped to a relevant permission subset (see `database/seeders/RolesAndPermissionsSeeder.php`).
- **Policies**: Customer-owned resources (addresses, carts, wishlists) are additionally protected by ownership checks via Eloquent Policies, independent of the permission system.
- **Error responses**: Unauthenticated requests return `401`; unauthorized/forbidden requests return `403`; validation and business-rule errors return `422`.

## 10. Production Deployment

Full cross-stack checklist (storefront, admin, CORS, rollback): see the storefront repo [`HANDOFF.md`](../../Afifi%20clothing%20brand/HANDOFF.md) §9.

### Requirements

- PHP 8.3+, Composer 2.x
- MySQL 8+ or PostgreSQL 14+ (SQLite is for local dev and PHPUnit only)
- nginx/Apache + PHP-FPM, HTTPS
- Persistent disk or S3 for `storage/app/public`

### Production `.env` (minimum)

```env
APP_NAME=AFIFI
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=afifi_production
DB_USERNAME=afifi
DB_PASSWORD=<strong-password>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

ADMIN_EMAIL=admin@yourdomain.com
ADMIN_PASSWORD=<strong-password>
ADMIN_NAME="AFIFI Admin"
ADMIN_PHONE=01000000000

# Optional: split-host CORS / future SPA cookie auth
SANCTUM_STATEFUL_DOMAINS=shop.yourdomain.com,www.yourdomain.com
# MEDIA_MAX_SIZE_BYTES=10485760
```

Never commit `.env`. Set `ADMIN_PASSWORD` before seeding.

### Deploy commands (run in order)

```bash
# 1. Dependencies
composer install --no-dev --optimize-autoloader

# 2. App key (first deploy only)
php artisan key:generate

# 3. Database
php artisan migrate --force
php artisan db:seed --force                              # first deploy
# php artisan db:seed --class=RolesAndPermissionsSeeder --force   # permission updates
php artisan permission:cache-reset

# 4. Storage
php artisan storage:link

# 5. Optimize (after every deploy)
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Queue worker (if QUEUE_CONNECTION is not sync)
php artisan queue:work --sleep=3 --tries=3
```

### CORS & Sanctum (split frontend/API)

The static storefront and admin send **Bearer tokens** (`Authorization: Bearer <token>`), not cookies. For cross-origin hosting:

1. Publish CORS config: `php artisan config:publish cors`
2. Set `allowed_origins` in `config/cors.php` to your storefront/admin HTTPS origins.
3. Re-run `php artisan config:clear && php artisan config:cache`.

Default Laravel CORS allows `*` — restrict in production when possible. `SANCTUM_STATEFUL_DOMAINS` is only needed for cookie-based SPA mode.

### Media & storage

```bash
php artisan storage:link
```

- Public URLs: `{APP_URL}/storage/{path}`
- Media admin API is metadata-only; files live under `storage/app/public/` or S3.
- MIME/size rules: `config/media.php` (publish `MEDIA_MAX_SIZE_BYTES` via `.env` if needed).
- Run `php artisan config:clear` before `config:cache` when `config/media.php` or other config files change.

### Rate limits (production)

| Limiter | Scope | Limit |
|---------|-------|-------|
| `auth-public` | `POST /api/auth/register`, `POST /api/auth/login` | 10 requests/min per IP |
| `auth-sensitive` | `PUT /api/auth/password` | 5 requests/min per user/IP |

Defined in `app/Providers/AppServiceProvider.php`.

### Queues

`.env.example` sets `QUEUE_CONNECTION=database`. No application jobs are queued today, but the default is ready for future mail/async work. If you switch to `redis` or `database`, run a supervised `queue:work` process.

### Post-deploy smoke tests

```bash
curl -f https://api.yourdomain.com/up
curl -s https://api.yourdomain.com/api/settings/public
curl -s https://api.yourdomain.com/api/catalog/products
php artisan test   # run on CI before deploy; 71 tests
```

### Rollback

1. Redeploy previous release artifact.
2. `php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan permission:cache-reset`
3. Restore DB backup if migrations ran (preferred over `migrate:rollback` on production).
4. Reload PHP-FPM; restart queue workers.
5. Re-run smoke tests.

### Security reminders

- `APP_DEBUG=false`, strong `APP_KEY`, strong `ADMIN_PASSWORD`
- HTTPS only; restrict CORS origins
- Re-seed permissions after adding new permission strings in code
- Health endpoint: `GET /up`
