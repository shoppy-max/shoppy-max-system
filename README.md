# Shoppy Max

Shoppy Max is a Laravel 12 application for inventory, order operations, reseller management, courier settlement, and reporting.

It includes role-based access control, product variants, stock-safe purchase/order flows, waybill generation, imports/exports, and print/PDF outputs across multiple modules.

## Table of Contents

1. Overview
2. Core Capabilities
3. Tech Stack
4. System Requirements
5. Quick Start (SQLite)
6. Alternative Database Setup
7. Running the App
8. Seed Data and Default Credentials
9. Environment Configuration
10. Operational Business Rules
11. Imports, Exports, and Documents
12. Main Route Map
13. Useful Commands
14. Testing and Quality
15. Troubleshooting
16. Security and Production Checklist
17. Project Structure
18. License

## Overview

This system manages:

- Contacts: customers, suppliers, resellers, direct resellers, cities
- Product catalog: categories, sub-categories, units, products, variants, pricing
- Inventory movement: purchases increase stock, orders decrease stock
- Orders: create/edit/view/print/PDF, call list, waybill queue, packing flow
- Finance flows: reseller targets/payments/dues, courier payments, bank accounts
- Reports: province sales, profit/loss, stock, packet count, product sales, user sales

Authentication is provided by Laravel Breeze, and permissions are handled by Spatie Permission.

## Core Capabilities

- Full CRUD for operational masters and transactions
- Product variants with:
  - unit + unit value
  - unique SKU
  - selling price + limit price
  - stock quantity and alert quantity
- Product import with preview validation:
  - auto SKU generation
  - category/sub-category/unit validation
  - same product name is case-insensitive
  - conflict checks before final import
- Order workflow with:
  - customer auto-create/update by mobile
  - delivery status lifecycle
  - call status tracking
  - payment entries and derived payment status
  - order lock behavior after status moves away from `pending`
- Waybill workflow:
  - queue based on `call_status = confirm`
  - print generates waybill numbers
  - printed orders leave the pending waybill queue
- Courier receive and courier payment tracking
- Reseller commission/penalty logic (reseller-only, not direct reseller)
- PDF/print/export support across modules

## Tech Stack

- PHP 8.2+
- Laravel 12
- SQLite (default) or MySQL/PostgreSQL
- Blade + Alpine.js + Tailwind CSS + Flowbite
- SweetAlert2
- Spatie Permission
- DomPDF (`barryvdh/laravel-dompdf`)
- Laravel Excel (`maatwebsite/excel`)
- Cloudinary Laravel SDK (image upload integration)
- Vite for asset bundling

## System Requirements

- PHP `^8.2`
- Composer
- Node.js + npm
- SQLite (default) or another supported DB

## Quick Start (SQLite)

```bash
# From the project root
cd shoppy-max

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Create SQLite DB file:

```bash
mkdir -p database
touch database/database.sqlite
```

Set in `.env`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/shoppy-max/database/database.sqlite
```

Run migrations and seed demo data:

```bash
php artisan migrate:fresh --seed
```

Create frontend assets:

```bash
npm run build
```

Start app:

```bash
php artisan serve
```

Open:

- `http://127.0.0.1:8000/login`
- `http://127.0.0.1:8000/shop` (public product page)

## Alternative Database Setup

For MySQL/PostgreSQL, update `.env` DB values, then:

```bash
php artisan migrate:fresh --seed
```

## Running the App

Development (all-in-one with server, queue listener, logs, and Vite):

```bash
composer run dev
```

Or run manually in separate terminals:

```bash
php artisan serve
php artisan queue:work
npm run dev
```

## Seed Data and Default Credentials

Seeders used:

- `RolesAndPermissionsSeeder`
- `DemoSystemSeeder`

Default users:

- Super Admin
  - Email: `admin@shoppy-max.com`
  - Password: `password`
  - Role: `super admin`
- Manager
  - Email: `manager@shoppy-max.com`
  - Password: `password`
  - Role: `admin`

Seed data includes:

- Units, categories, sub-categories
- Cities (with district/province)
- Couriers + rate sets
- Bank accounts
- Suppliers
- Resellers and direct resellers
- Products/variants
- Demo purchases/orders/payments/targets/logs

## Environment Configuration

### Important app settings

- `APP_ENV`, `APP_DEBUG`, `APP_URL`
- `APP_TIMEZONE` (recommended to set explicitly, e.g. `Asia/Colombo`)

### DB / session / queue defaults

- `DB_CONNECTION=sqlite`
- `SESSION_DRIVER=database`
- `QUEUE_CONNECTION=database`
- `CACHE_STORE=database`

If using database-backed drivers, ensure migrations are run.

### File/image handling

- Local filesystem default: `FILESYSTEM_DISK=local`
- Product image uploads use Cloudinary integration in controllers.
  - Configure `CLOUDINARY_URL` or related Cloudinary env vars for image upload support.

## Operational Business Rules

These are current implemented behaviors.

### Inventory

- Product and variant creation starts stock at `0`.
- Stock increases from purchases.
- Stock decreases from active orders.
- Deleting an order restores its stock.
- Updating/deleting purchases reverts stock with non-negative guard:
  - operation is blocked if revert would make stock negative.

### Orders

- `order_number` is unique and generated daily (`ORD-YYYYMMDD-####`).
- Soft-deleted orders still reserve their order numbers.
- If an order status changes away from `pending`, core order details lock:
  - only payment entries and notes remain editable.

Supported statuses:

- Order status: `pending`, `hold`, `confirm`, `cancel`
- Call status: `pending`, `confirm`, `hold`, `cancel`
- Delivery status:
  - `pending`
  - `waybill_printed`
  - `picked_from_rack`
  - `packed`
  - `dispatched`
  - `delivered`
  - `return_requested`
  - `returned`
  - `cancel`

Cancellation behavior:

- Cancelling order auto-sets call status to `cancel`
- Cancelling order auto-sets delivery status to `cancel`

### Customer sync from orders

When creating/updating orders:

- customer is upserted by `mobile`
- customer address + location fields are saved:
  - `country`
  - `city`
  - `district`
  - `province`

### Payment and status rules

- Order remains `pending` when discount exists or method is `Online Payment`.
- Payment status auto-resolves to `paid` when:
  - delivery status is `delivered`, or
  - online payment is fully paid.

### Reseller commission and return fee

- Commission applies only for `reseller` type accounts (not direct reseller).
- Commission is suppressed for cancelled/returned orders.
- Returned reseller orders can apply reseller return fee penalty and adjust due amounts.

### Waybill queue

Orders are eligible for waybill print only when:

- `call_status = confirm`
- order status is not `cancel`
- waybill number is empty

Printing waybill:

- assigns waybill number
- updates delivery status to `waybill_printed`
- stamps `waybill_printed_at`

## Imports, Exports, and Documents

### Product import

- Template download available
- Preview validates rows before final import
- SKU auto-generated in import path
- Duplicate/conflicting data validation applied

### Reseller payment import

- Template + preview + final store flow available

### Exports / PDFs

Available across relevant modules:

- contact exports (Excel/PDF)
- product export
- order print/PDF/bulk PDF ZIP
- reseller payment invoice downloads

## Main Route Map

Important entry points:

- Auth: `/login`
- Dashboard: `/dashboard`
- Public shop: `/shop`
- Contacts:
  - `/customers`
  - `/suppliers`
  - `/resellers`
  - `/direct-resellers`
  - `/cities`
- Products: `/admin/products`
- Orders:
  - `/orders`
  - `/orders/create`
  - `/orders/call-list`
  - `/orders/waybill`
- Purchases: `/purchases`
- Couriers:
  - `/couriers`
  - `/receive-courier`
  - `/courier-payments`
- Bank Accounts: `/bank-accounts`
- Reseller ops:
  - `/reseller-targets`
  - `/reseller-payments`
  - `/reseller-dues`
- Reports: `/reports`
- User Logs (coming soon): `/user-logs`

## Useful Commands

Setup helper:

```bash
composer run setup
```

Migrate:

```bash
php artisan migrate
php artisan migrate:fresh --seed
```

Cache and optimize:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

Clear caches:

```bash
php artisan optimize:clear
```

## Testing and Quality

Run tests:

```bash
php artisan test
```

Run formatter:

```bash
./vendor/bin/pint
```

Note:

- The default Laravel sample test `Tests\Feature\ExampleTest` expects `/` to return `200`.
- In this app, `/` redirects to login, so that sample test can fail with `302` unless adjusted.

## Troubleshooting

### `UNIQUE constraint failed: orders.order_number` after deleting orders

Cause:

- Orders are soft-deleted, and old order numbers still exist.

Current behavior:

- Generator now includes soft-deleted orders, preventing reuse collisions.

### Missing images on product upload

- Ensure Cloudinary env config is valid when uploading images.
- If Cloudinary is not configured, avoid image upload until configured.

### No data in app

- Run:
  - `php artisan migrate:fresh --seed`

### Session/queue/cache errors on fresh setup

- Run migrations (database drivers are used by default):
  - `php artisan migrate`

## Security and Production Checklist

Before production:

1. Set `APP_ENV=production`, `APP_DEBUG=false`
2. Set strong unique credentials and rotate seeded defaults
3. Configure real database credentials
4. Set `APP_URL` correctly
5. Configure queue worker as a managed process
6. Configure backup/monitoring/log rotation
7. Configure Cloudinary credentials (if image uploads are required)
8. Build frontend assets with `npm run build`
9. Cache config/routes/views

## Project Structure

```text
app/
  Http/Controllers/        Business workflows and CRUD endpoints
  Models/                  Domain models
database/
  migrations/              Schema evolution
  seeders/                 Roles/permissions + demo data
resources/
  views/                   Blade UI (admin + operational screens)
routes/
  web.php                  Main app routes
  api.php                  API routes (city lookup, auth user)
```

## License

MIT License.
