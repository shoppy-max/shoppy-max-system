# AGENTS.md

This file is the working guide for agents and maintainers operating in this repository.
It is intentionally specific to the current Shoppy Max implementation and should be
kept in sync with the real system behavior, not with generic Laravel defaults.

## Purpose

Use this file to:

- understand the system before changing operational workflows
- find the correct code entry points quickly
- avoid breaking inventory, purchase, order, reseller, and courier settlement rules
- know which commands and manual checks are expected before handing work back

If `README.md` and this file diverge, update both. `README.md` is user-facing. This file
is operator-facing.

## System Snapshot

Shoppy Max is a Laravel 12 operations platform for:

- product and variant management
- purchase intake with moderation and manual retail/warehouse store placement
- unit-level inventory traceability
- order intake, call flow, waybill generation, waybill Excel export, plus packing and return flows that exist in part but should still be treated as work-in-progress operational areas
- reseller/direct-reseller balances and payments
- courier settlement and bank-account tracking
- reporting and print/PDF exports

The application is Blade + Alpine + Tailwind/Flowbite on the frontend. Business logic
is mostly controller-driven, with critical state transitions delegated to services in
`app/Services`.

The default local database is SQLite, but the system also has to work on MySQL. Do not
write query logic that only works on one engine.

## First Files To Read

When working on a feature area, start here:

- `README.md`
- `routes/web.php`
- `database/seeders/DemoSystemSeeder.php`
- `app/Http/Controllers/OrderController.php`
- `app/Http/Controllers/PurchaseController.php`
- `app/Http/Controllers/CourierReceiveController.php`
- `app/Http/Controllers/CourierPaymentController.php`
- `app/Services/InventoryUnitService.php`
- `app/Services/CourierPaymentOrderService.php`

## Repository Map

- `app/Http/Controllers`: primary workflow logic
- `app/Models`: Eloquent models and computed business helpers
- `app/Services`: high-risk state transition logic
- `database/migrations`: schema evolution
- `database/seeders`: roles, permissions, and demo data
- `resources/views`: Blade UI
- `routes/web.php`: all main app routes
- `routes/console.php`: inventory reconciliation/backfill commands
- `server_utils`: sensitive manual server/debug helpers

## Core Business Invariants

These are not suggestions. Preserve them when changing code.

### Products and Variants

- product names are unique case-insensitively
- SKUs are generated and must remain unique
- product/variant stock is not manually edited from product CRUD
- stock comes from manual retail/warehouse store placement and leaves through orders
- product list filters must work cleanly on both SQLite and MySQL

Barcode behavior:

- product barcode printing is catalog-oriented
- product bulk print supports:
  - one generic label per variant
  - quantity-aware labels per available stock, repeating the variant SKU barcode for each quantity
- purchase barcode printing is SKU-oriented and quantity-aware:
  - print one repeated SKU barcode label per purchased unit quantity
  - do not print unique inventory-unit codes as the purchase label barcode
  - purchase barcode printing must not require inventory-unit records to already exist
- order picking, Pick GRN sheets, packing screens, order print/PDF surfaces, and label summaries
  must show the same SKU barcode value operators scanned at purchase/store placement time
- internal `inventory_units.unit_code` values are still unique for traceability and database state,
  but they are not the operator-facing barcode

### Purchases

Purchase statuses are fixed:

- `pending`
- `checking`
- `verified`
- `complete`

Rules:

- new purchases start at `pending`
- moderation is forward-only
- `pending -> checking -> verified -> complete`
- do not allow moving backward or skipping stages
- purchase date and purchase number are immutable after creation
- purchase creation/editing must not create stock or inventory units automatically
- verified purchase quantities are added to stock only through scanner-based Retail Store or Warehouse Store placement
- purchase item structure locks once store placement starts
- a purchase in `complete` is locked from structural editing/deletion

Payment status in purchases is derived, not stored:

- `due`
- `partial`
- `paid`

### Store Placement and Stock Intake

Stock intake is manual from the verified purchase stage.

Rules:

- verified purchase items can be scanned into either `retail` or `warehouse` store
- each store has its own simple rack rows
- stock updates only after an operator chooses a rack, starts adding, and scans SKU barcode labels
- each successful scan creates one available inventory unit, stores the rack/store metadata, and increments variant stock by one
- do not allow placed quantity to exceed the purchased quantity remaining for that item
- the purchase becomes `complete` only when all item quantities are fully placed into store stock
- purchase-printed barcode labels repeat the SKU per physical unit quantity
- product and order-facing barcode labels also repeat the SKU per physical unit quantity; internal
  `IU-*` inventory unit codes remain only for backend traceability

### Inventory Units and Traceability

The system now tracks unit-level inventory.

`InventoryUnit` statuses:

- `pending_receipt`
- `grn_scanned`
- `available`
- `allocated`
- `delivered`
- `archived`

Rules:

- manual store placement creates available unit records
- orders allocate actual units, not abstract stock only
- delivered orders mark units delivered
- cancel/return/delete flows release units appropriately
- the same product variant can be sourced from multiple purchases, and allocated order units
  must still remember the original purchase source

Do not patch stock by hand if the real issue is unit-state drift. Use the reconciliation
commands in `routes/console.php`.

### Orders

Order number format is daily and unique:

- `ORD-YYYYMMDD-####`

Internal order statuses:

- `pending`
- `hold`
- `confirm`
- `cancel`

Call statuses:

- `pending`
- `confirm`
- `hold`
- `cancel` (auto-only when order is cancelled)

Delivery statuses:

- `pending`
- `waybill_printed`
- `picked_from_rack`
- `packed`
- `dispatched`
- `delivered`
- `returned`
- `cancel`

Rules:

- cancelling an order forces call status and delivery status to `cancel`
- only dispatched orders can be marked delivered
- customer is created/updated from order input using mobile identity
- once the order moves beyond early intake, core order structure becomes locked and only
  limited fields remain editable
- online payment and discount workflows affect status/payment rules; preserve the controller logic
  instead of duplicating it elsewhere
- waybill Excel export is courier-specific and uses only orders that already have printed waybill IDs
- newly exported rows are marked downloaded, but downloaded rows can still be explicitly included later

### Resellers and Direct Resellers

There are two distinct reseller types:

- regular reseller
- direct reseller

Rules:

- return fee applies only to regular resellers
- target management is for regular resellers only
- direct resellers do not use return fee penalties
- order commission logic applies only where the current controller/model rules allow it

Do not mix reseller and direct-reseller financial behavior.

### Courier Receive and Courier Payments

Courier settlement is constrained.

Only orders that match all of the following are eligible for courier receive:

- `call_status = confirm`
- `payment_method = COD`
- `delivery_status = dispatched`
- matching courier
- non-empty waybill number
- not already linked to a courier payment

Settlement values:

- system delivery charge = order delivery charge at placement time
- real delivery charge = what the courier actually charged
- courier commission = system delivery charge - real delivery charge
- received amount = order total - real delivery charge

Rules:

- receiving courier payment marks linked orders delivered
- removing an order from a courier payment reverts the order to dispatched
- unlinking also reverts payment-side settlement fields on the order
- whole courier-payment deletion is intentionally disabled at route/UI level
- corrections happen through editing the payment and removing linked orders there

The shared order settlement logic for this area lives in
`app/Services/CourierPaymentOrderService.php`. Reuse it. Do not re-implement attach/detach
side effects in controllers.

### Reports

Reports must be generated from real operational data, not placeholder totals.

Rules:

- stock report rows are product-variant/SKU based
- stock report quantity comes from available inventory units
- stock value uses available units and their linked purchase item cost as FIFO/source value
- stock movement detail includes purchasing, sale, cancel, and return movements with reference numbers
- stock movement detail filters include movement type, reference search, and date range
- source references in stock movement detail should link to the source purchase or order whenever the event stores enough source data
- product wise sale and user wise sale exclude cancelled orders from total order/PCS counts
- product wise sale return percentage is `returned PCS / total non-cancelled PCS`
- user wise sale return percentages are based on non-cancelled order and PCS totals
- packed and pick-from-rack report uses `packed_by/packed_at` and `picked_by/picked_at`
- report table views are paginated
- PDF and Excel exports must use the complete filtered dataset, not the current page only
- PDF report layouts must avoid horizontal overflow by using compact landscape tables

## Current High-Risk Code Entry Points

Use these instead of scattering new logic:

- inventory and unit lifecycle:
  - `app/Services/InventoryUnitService.php`
- courier-payment attach/detach order side effects:
  - `app/Services/CourierPaymentOrderService.php`
- purchase moderation, store placement, purchase search:
  - `app/Http/Controllers/PurchaseController.php`
- order lifecycle, payment resolution, commission, reseller penalty, allocation:
  - `app/Http/Controllers/OrderController.php`
- courier receive:
  - `app/Http/Controllers/CourierReceiveController.php`
- courier payment edit/list/show:
  - `app/Http/Controllers/CourierPaymentController.php`

If a change affects statuses, stock, payment status, due balances, or traceability, check
all related controller/service paths before editing.

## Database Compatibility Rules

This repo is developed on SQLite but must remain compatible with MySQL.

Avoid these mistakes:

- `DISTINCT` queries ordered by expressions not present in the select list
- `GROUP BY some_id` combined with `select(table.*)` in MySQL strict mode
- SQLite-only raw SQL such as `strftime(...)` without a MySQL alternative

Preferred approach:

- use Eloquent relationships, `withCount`, `withMin`, scoped filters, and computed PHP sorting
  where reasonable
- if raw date formatting is unavoidable, branch by driver
- after query changes, think about both SQLite and MySQL, not just local behavior

## Product Image Storage

Product and product-variant images are stored in Backblaze B2 through Laravel's
S3-compatible `b2` filesystem disk.

Rules:

- keep the B2 bucket private
- store B2 object keys in product records, not public or temporary URLs
- generate temporary signed URLs only when rendering/admin APIs need an image URL
- use `app/Services/ProductImageService.php` for uploads, remote import image copying,
  and URL generation
- keep product-image storage on the configured private B2 disk only
- do not commit B2 application keys or other storage secrets

## Setup and Development Commands

Install:

```bash
composer install
npm install
```

Bootstrap local env:

```bash
cp .env.example .env
php artisan key:generate
```

Fresh local build:

```bash
php artisan migrate:fresh --seed
npm run build
```

Full dev workflow:

```bash
composer run dev
```

Manual dev processes:

```bash
php artisan serve
php artisan queue:work
npm run dev
```

Tests and formatting:

```bash
php artisan test
./vendor/bin/pint
```

Useful caches:

```bash
php artisan view:cache
php artisan route:cache
php artisan config:cache
```

When changing routes or seeing stale route behavior:

```bash
php artisan route:clear
php artisan route:cache
```

When checking view compilation:

```bash
php artisan view:clear
php artisan view:cache
```

## Inventory Recovery Commands

These are defined in `routes/console.php`:

```bash
php artisan inventory-units:backfill
php artisan inventory-units:sync-stock
```

Use cases:

- `inventory-units:backfill`: create inventory-unit records from an older state when no
  units exist yet
- `inventory-units:sync-stock`: force aggregate variant quantity to match available units

Do not run backfill casually on a live dataset that already has inventory units.

## Seed Data Expectations

Primary seeders:

- `RolesAndPermissionsSeeder`
- `DemoSystemSeeder`

Current demo seed should include:

- admin and manager users
- categories, sub-categories, units, products, and variants
- cities
- suppliers
- couriers and bank accounts
- resellers and direct resellers
- purchases across moderation states
- orders across operational states
- courier payments with linked orders
- inventory units consistent with stock

Fresh-seed consistency should hold:

- variant quantity equals available inventory unit count
- active order item quantity equals allocated/delivered inventory unit count
- courier payment records are not empty
- at least one order is eligible for receive-courier flow

If seed changes touch orders, purchases, courier payments, or inventory units, verify these
relationships again.

## Test Reality

The automated test suite in this repo is still lighter than the operational surface.

Existing tests cover Breeze auth, profile basics, root redirect behavior, and a few
operational safeguards:

- `tests/Feature/ExampleTest.php`
- `tests/Feature/OrderRoutingTest.php`
- `tests/Feature/OperationalSafeguardsTest.php`
- `tests/Feature/ProfileTest.php`
- `tests/Feature/Auth/*`
- `tests/Unit/ExampleTest.php`

`OrderRoutingTest` currently protects the `/orders/packing` route from being captured by
the `/orders/{order}` wildcard.

That means `php artisan test` is necessary but not sufficient for operational changes.

## Manual Verification Expectations

If you touch a workflow, verify the full flow, not just syntax.

### Product/catalog changes

Check:

- `/admin/products`
- product create/edit
- product view modal
- import preview/store
- barcode print actions

### Purchase / store placement / inventory changes

Check:

- `/purchases`
- purchase create/edit/show/PDF
- purchase moderation page
- `/purchases/store-placement/retail`
- `/purchases/store-placement/warehouse`
- `/purchases/store-racks/retail`
- `/purchases/store-racks/warehouse`
- purchase barcodes
- aggregate stock impact after manual placement

### Order changes

Check:

- `/orders`
- create/edit/view/print/PDF
- call list
- waybill queue and print
- waybill Excel export queue
- packing flow
  - `/orders/packing/ready` lists waybill-printed orders waiting for a pick GRN; creating the pick GRN validates rack locations, assigns a `PGRN-YYYYMMDD-####` number, opens the printable/save-as-PDF pick sheet, and moves the order to Picking
  - `/orders/packing/picking` lists picked-from-rack orders that are currently being scanned
  - `/orders/packing/packed` lists fully scanned packed orders ready for dispatch
  - the per-order scanner accepts repeated SKU barcode scans, persists scan progress on inventory units, and automatically moves the order to `packed` on the final required scan
  - packing is still a work-in-progress operational area; do not describe it as fully hardened or fully browser-QA-proven without a dedicated end-to-end verification pass
- returns
  - backend logic for `returned` is implemented
  - returns are still a work-in-progress operational area
  - post-dispatch / post-delivery operational return UI is still TODO
  - do not claim returns are fully operational end-to-end until that dedicated path exists
- cancel/return behavior
- payment entries and payment status

### Courier settlement changes

Check:

- `/receive-courier`
- `/receive-courier/{courier}`
- `/courier-payments`
- `/courier-payments/{id}`
- `/courier-payments/{id}/edit`

Verify both apply and revert behavior.

### Report changes

Check:

- `/reports`
- `/reports/stock`
- `/reports/stock/{variant}`
- `/reports/packet-count`
- `/reports/product-sales`
- `/reports/user-sales`
- filter behavior on each report
- PDF and Excel downloads for the filtered results
- paginated views still show only page rows while downloads include the full filtered output

### Reseller/direct reseller changes

Check:

- reseller and direct-reseller CRUD
- payments
- dues
- order side effects on due and penalties

## UI and UX Standards

This project already has established list/detail/form patterns. Follow them.

- keep filter bars compact and aligned
- prefer two-row filter/action layouts over oversized labeled rows
- match button sizing and spacing with nearby pages
- use SweetAlert/toast patterns instead of browser `alert()` when user-facing messaging exists
- do not introduce a different visual system for an existing module

If a page is operationally dense, prefer:

- compact summary cards
- concise helper text
- expandable detail rows instead of bloated tables

## Safety Rules

- do not edit `.env` for permanent behavior changes
- do not commit secrets, auth dumps, or private keys
- do not modify `server_utils/` utilities unless explicitly required
- keep operational timestamps in the configured app timezone (`APP_TIMEZONE`, default
  `Asia/Colombo`) and make seeded/demo audit times match their business event dates
- do not bypass service-layer business rules for inventory or courier settlement
- do not add direct SQL shortcuts for order/purchase status changes if controllers/services already
  coordinate side effects
- do not assume browser caches/route caches are fresh after changing Blade/routes

## Sensitive Areas

Treat these as high-risk:

- `server_utils/debug_login.php`
- `server_utils/database_fresh_install.sql`
- migrations touching orders, purchases, inventory units, reseller balances, or courier payments
- controller code that changes statuses
- barcode generation and print templates

## Recommended Change Discipline

For every change, think through the full workflow before editing. Treat edge cases,
data integrity, authorization, validation, UI consistency, database compatibility,
and rollback/recovery behavior as part of the task. Prefer the best established
repo pattern over the fastest local patch, and verify the real affected path before
claiming the work is complete.

For medium or high-risk changes:

1. identify the workflow and the system invariant it touches
2. find the existing controller/service that already owns the transition
3. change the fewest files necessary
4. run syntax checks and relevant artisan caches
5. run manual workflow verification for the touched module
6. if seed data assumptions changed, reseed or update `DemoSystemSeeder.php`
7. update this file and `README.md` if the operator or user contract changed

## Further Reading

- `README.md`
- `LICENSE`
- `routes/web.php`
- `routes/console.php`
- `database/seeders/DemoSystemSeeder.php`
- `server_utils/README.md`
