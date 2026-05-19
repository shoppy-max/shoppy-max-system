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
- Inventory movement: purchase intake, manual retail/warehouse store placement, unit-level stock tracking, and orders
- Orders: create/edit/view/print/PDF, call list, waybill queue, waybill Excel export queue, plus packing and return flows that exist in part but should still be treated as work-in-progress operational areas
- Finance flows: reseller targets/payments/dues, courier payments, bank accounts
- Reports: stock, stock movement, packed/pick-from-rack counts, product wise sales, and user wise sales with filtered PDF/Excel downloads
- User logs: centralized audit trail for authentication, CRUD writes, uploads, exports/downloads, and model-level changes

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
  - order structure locking after intake progresses, with manual edit/cancel/delete blocked once waybill printing starts
  - packing pages split into Ready To Pick, Picking, and Packed queues: Ready To Pick creates a numbered pick GRN with rack locations and opens a printable/save-as-PDF pick sheet, Picking handles scanner scans, and the final required scan automatically moves the order to Packed
  - note: packing core flow exists, but it is still a work-in-progress operational area and broader hardening / full browser QA are still pending
  - note: the `returned` state exists in backend accounting/inventory logic, but returns are still a work-in-progress operational area until the dedicated post-dispatch return screen/action exists
- Purchase workflow with:
  - forward-only moderation (`pending -> checking -> verified -> complete`)
  - no automatic stock creation when a purchase is added
  - manual placement into Retail Store or Warehouse Store rack rows before stock updates
  - immutable purchase date and purchasing ID after creation
- Unit-level inventory traceability:
  - purchase, product quantity, and order picking labels repeat the variant SKU per physical quantity
  - unique internal inventory-unit codes remain hidden backend traceability references
  - store/rack metadata on manually placed stock units
  - orders allocate real inventory units
  - delivered/cancel/return flows update tracked units
- Waybill workflow:
  - queue based on `call_status = confirm`
  - print generates waybill numbers
  - printed orders leave the pending waybill queue
  - printed orders enter a courier-specific Excel export queue
  - Excel export defaults to not-yet-downloaded rows and can optionally include already-downloaded rows
- Courier receive and courier payment reconciliation
- User activity auditing with:
  - permission-gated `/user-logs` review surface
  - login success/failure and logout records
  - authenticated page/search view logging
  - non-read request logging for create/update/delete/import/scan/status actions
  - download/export/print/barcode access logging
  - filtered Excel/PDF exports of the full audit result set
  - model-level old/new value deltas for operational records
  - admin-readable operation labels such as "Opened Units Edit Page" and "Downloaded User Logs Export Excel" before technical route names
  - sanitized request/file metadata so secrets and file contents are not stored
- Reseller commission/penalty logic (reseller-only, not direct reseller)
- Reseller/direct-reseller login account creation:
  - email is required for both reseller types
  - creating a reseller creates a linked user account with a generated password
  - generated login details are shown once in a popup with a copy button
  - linked user name, email, mobile, and role stay synced when the reseller record is edited
  - list reset-password actions confirm the exact reseller, invalidate the old password, and show the new copyable login popup once
  - deleting a reseller retires the dedicated linked login account
  - reseller dashboards show the linked business, due balance, order counts, and return fee where applicable
- PDF/print/export support across modules

## Tech Stack

- PHP 8.2+
- Laravel 12
- SQLite (default) or MySQL/MariaDB
- Blade + Alpine.js + Tailwind CSS + Flowbite
- SweetAlert2
- Spatie Permission
- DomPDF (`barryvdh/laravel-dompdf`)
- Laravel Excel (`maatwebsite/excel`)
- Backblaze B2 private bucket image storage via Laravel's S3 filesystem adapter
- Vite for asset bundling

## System Requirements

- PHP `^8.2`
- Composer
- Node.js + npm
- SQLite (default) or MySQL/MariaDB

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

For MySQL/MariaDB, update `.env` DB values, then:

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
- Inventory units and barcode-traceable demo stock
- Courier payment examples with linked settled orders
- Eligible courier-receive examples for testing receive flow

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
- Product and product-variant image uploads use Backblaze B2 through the `b2` filesystem disk.
- Uploaded product image values stored in the database are B2 object keys, not public URLs.
- Product image display uses temporary signed URLs generated at render/API time, so the bucket can stay private.
- Required B2 env values:
  - `PRODUCT_IMAGE_DISK=b2`
  - `B2_KEY_ID`
  - `B2_APPLICATION_KEY`
  - `B2_BUCKET=shoppymax-img`
  - `B2_REGION`
  - `B2_ENDPOINT` such as the bucket region's S3 endpoint
  - `B2_USE_PATH_STYLE_ENDPOINT=false` unless the B2 endpoint setup requires path-style requests
  - `B2_SERVER_SIDE_ENCRYPTION=AES256` for Backblaze-managed SSE-B2

## Operational Business Rules

These are current implemented behaviors.

### Inventory

- Product and variant creation starts stock at `0`.
- Purchases do not create stock or inventory units automatically.
- Stock is added only when a verified purchase item is scanned into a selected Retail Store or Warehouse Store rack row.
- Orders allocate real inventory units, not only aggregate stock counts.
- Stock decreases when units are allocated to active orders.
- Delivered orders mark units as delivered.
- Cancelling, returning, or deleting orders releases units appropriately.
- Operator-facing stock, order, and Pick GRN barcode labels show the variant SKU; internal
  inventory unit codes are kept only for traceability and state transitions.
- Use the inventory-unit reconciliation commands if aggregate stock drifts from tracked units.

### Purchases and Store Placement

Purchase statuses:

- `pending`
- `checking`
- `verified`
- `complete`

Rules:

- New purchases start at `pending`.
- Moderation is forward-only:
  - `pending -> checking -> verified -> complete`
- Purchase date and purchasing ID are locked after creation.
- Verified purchase items can be scanned into either Retail Store or Warehouse Store.
- Rack rows are maintained separately for Retail Store and Warehouse Store.
- The operator selects a rack, starts adding, then each SKU barcode scan creates one available inventory unit, records the store/rack, and increments product variant stock by one.
- The JSON/display value returned after a scan is the SKU barcode value, not the internal inventory-unit code.
- A purchase becomes `complete` only when all purchased item quantities have been placed into store stock.
- Once store placement starts, purchase structure is locked.
- Completed purchases are locked from structural edits and deletion.

### Orders

- `order_number` is unique and generated daily (`ORD-YYYYMMDD-####`).
- Soft-deleted orders still reserve their order numbers.
- Once an order moves beyond early intake, core order details lock:
  - only payment entries and notes remain editable.

Supported statuses:

- Internal order status (system-managed): `pending`, `hold`, `confirm`, `cancel`
- Call status: `pending`, `confirm`, `hold`, `cancel`
- Delivery status:
  - `pending`
  - `waybill_printed`
  - `picked_from_rack`
  - `packed`
  - `dispatched`
  - `delivered`
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
- Only dispatched orders can move to `delivered`.

### Reseller commission and return fee

- Commission applies only for `reseller` type accounts (not direct reseller).
- Commission is suppressed for cancelled/returned orders.
- Returned reseller orders can apply reseller return fee penalty and adjust due amounts.

### Courier receive and courier payments

Orders are eligible for courier receive only when:

- `call_status = confirm`
- `payment_method = COD`
- `delivery_status = dispatched`
- waybill number exists

### Reports

- `/reports/stock` shows one row per product variant/SKU with available PCS and FIFO stock value from available inventory units.
- Stock movement detail shows purchasing, sale, cancel, and return stock changes with running available quantity, reference number, timestamp, and value change.
- Stock movement detail can be filtered by movement type, reference number, and date range, and reference numbers link back to the source purchase or order when the source is available.
- `/reports/packet-count` shows packed and pick-from-rack counts by user with date filtering.
- `/reports/product-sales` excludes cancelled orders from total PCS and shows delivered PCS, returned PCS, and return percentage by product/SKU/variant.
- `/reports/user-sales` excludes cancelled orders from total orders and PCS, then shows delivered/returned counts and return percentages by creator.
- Report screen views are paginated. PDF and Excel downloads use the full filtered dataset, not only the current page.
- courier matches the receive screen
- order is not already linked to a courier payment

Settlement values:

- System delivery charge: order delivery charge at placement time
- Real delivery charge: what the courier actually charged
- Courier commission: system delivery charge minus real delivery charge
- Received amount: order total minus real delivery charge

Behavior:

- Receiving courier payment marks linked orders as delivered.
- Editing a courier payment can add/remove linked orders.
- Removing an order from a courier payment reverts that order to `dispatched`,
  clears settlement-specific values, and restores COD payment state to pending.
- Whole courier payment deletion is disabled; correction is done through edit/unlink flow.

### Waybill queue

Orders are eligible for waybill print only when:

- `call_status = confirm`
- `delivery_status = pending`
- waybill number is empty

Printing waybill:

- assigns waybill number
- updates delivery status to `waybill_printed`
- stamps `waybill_printed_at`

Waybill Excel export:

- is grouped by courier
- includes orders with saved printed waybill IDs
- defaults to pending-download rows only
- can include previously downloaded rows with the explicit filter
- marks newly exported rows as downloaded without blocking later re-export

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
- purchase PDF and barcode print
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
- Purchase moderation: `/purchases/moderation`
- Retail store placement: `/purchases/store-placement/retail`
- Warehouse store placement: `/purchases/store-placement/warehouse`
- Retail racks: `/purchases/store-racks/retail`
- Warehouse racks: `/purchases/store-racks/warehouse`
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
- User Logs: `/user-logs`

## Useful Commands

Setup helper:

```bash
composer run setup
```

This runs migrations, seeds the production-safe roles/default-admin bootstrap, installs npm
dependencies, and builds frontend assets. It does not seed demo operational data.

Migrate:

```bash
php artisan migrate
php artisan migrate:fresh --seed
```

Inventory reconciliation:

```bash
php artisan inventory-units:backfill
php artisan inventory-units:sync-stock
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
php artisan route:clear
php artisan view:clear
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

- The automated test suite is currently light and does not cover the main operational workflows.
- After changing purchases, store placement, orders, courier settlement, or stock logic, manual workflow
  verification is still required even if `php artisan test` passes.
- `Tests\Feature\ExampleTest` expects `/` to redirect to the login page, matching the current app entry point.

## Troubleshooting

### `UNIQUE constraint failed: orders.order_number` after deleting orders

Cause:

- Orders are soft-deleted, and old order numbers still exist.

Current behavior:

- Generator now includes soft-deleted orders, preventing reuse collisions.

### Missing images on product upload

- Ensure Backblaze B2 env config is valid and the bucket/key allow S3-compatible put/get signed URL operations.
- Keep the B2 bucket private; the app generates temporary signed URLs for display.
- If image upload fails, verify `B2_ENDPOINT`, `B2_REGION`, `B2_BUCKET`, `B2_KEY_ID`, and `B2_APPLICATION_KEY`.

### No data in app

- Run:
  - `php artisan migrate:fresh --seed`

### Stock count does not match tracked inventory units

- Run:
  - `php artisan inventory-units:sync-stock`

### Inventory-unit store is missing for an older dataset

- Run only on a dataset that does not already have inventory units:
  - `php artisan inventory-units:backfill`

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
7. Configure Backblaze B2 image storage credentials and verify private-bucket signed URLs
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

This project is proprietary software owned by Codezela Technologies.

See the full license terms in [LICENSE](LICENSE).
