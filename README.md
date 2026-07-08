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

**Current test status:** 35 tests passing, 67 assertions.

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
  - **Permissions**: `users.*`, `products.*`, `inventory.*`, `orders.*`, `payments.view`, `coupons.manage`, `campaigns.manage`, `cms.manage`, `settings.manage`, `reports.view`, `roles.view`, `roles.manage`, `contact.view`, `contact.manage`.
  - **Roles**: `super_admin` (all permissions), `catalog_manager`, `fulfillment`, `support`, `marketing` — each scoped to a relevant permission subset (see `database/seeders/RolesAndPermissionsSeeder.php`).
- **Policies**: Customer-owned resources (addresses, carts, wishlists) are additionally protected by ownership checks via Eloquent Policies, independent of the permission system.
- **Error responses**: Unauthenticated requests return `401`; unauthorized/forbidden requests return `403`; validation and business-rule errors return `422`.

## 10. Production Notes

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Use a production-grade database (MySQL/PostgreSQL) — SQLite is for local/testing only.
- Run `php artisan config:cache`, `route:cache`, and `view:cache` after deployment.
- Run `php artisan migrate --force` for production migrations (no interactive prompt).
- Ensure a real queue worker (`php artisan queue:work`) is running if using queued jobs; `QUEUE_CONNECTION` should not be `sync` in production.
- Configure a persistent `FILESYSTEM_DISK` (e.g. S3) for media uploads rather than `local`.
- Review and rotate `APP_KEY` and Sanctum token expiration (`config/sanctum.php`) according to your security requirements.
- Stock reservation holds use row-level locking (`lockForUpdate`) to prevent overselling under concurrent checkouts — ensure your database driver supports transactions (MySQL InnoDB/PostgreSQL).
- Monitor the `stock_reservations` table for expired-but-unreleased holds; consider a scheduled job to release expired reservations if one is not already in place.
