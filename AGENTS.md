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
- purchase intake with moderation and GRN scanning
- unit-level inventory traceability
- order intake, call flow, waybill generation, returns, and a partial packing flow that still needs completion
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
- stock comes from purchases and leaves through orders
- product list filters must work cleanly on both SQLite and MySQL

Barcode behavior:

- product barcode printing is catalog-oriented
- product bulk print supports:
  - one generic label per variant
  - quantity-aware labels per available stock
- purchase barcode printing is inventory-unit oriented and quantity-aware

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
- purchase item structure locks once GRN receiving starts
- a purchase in `complete` is locked from structural editing/deletion

Payment status in purchases is derived, not stored:

- `due`
- `partial`
- `paid`

### GRN and Stock Intake

GRN is scanner-driven from the verified purchase stage.

Rules:

- verified purchases enter GRN checking
- scanning updates progress but stock must not become available until the entire GRN is completed
- the last successful scan completes the purchase and releases stock into inventory
- GRN labels are unique per physical unit

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

- purchases create unit records
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

Order statuses:

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
- `return_requested`
- `returned`
- `cancel`

Rules:

- cancelling an order forces call status and delivery status to `cancel`
- only dispatched orders can be marked delivered
- customer is created/updated from order input using mobile identity
- once order status moves away from `pending`, core order structure becomes locked and only
  limited fields remain editable
- online payment and discount workflows affect status/payment rules; preserve the controller logic
  instead of duplicating it elsewhere

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

- `status = confirm`
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

## Current High-Risk Code Entry Points

Use these instead of scattering new logic:

- inventory and unit lifecycle:
  - `app/Services/InventoryUnitService.php`
- courier-payment attach/detach order side effects:
  - `app/Services/CourierPaymentOrderService.php`
- purchase moderation, GRN, purchase search:
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

The automated test suite in this repo is currently minimal.

Existing tests are only basic examples/profile coverage:

- `tests/Feature/ExampleTest.php`
- `tests/Feature/ProfileTest.php`
- `tests/Unit/ExampleTest.php`

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

### Purchase / GRN / inventory changes

Check:

- `/purchases`
- purchase create/edit/show/PDF
- purchase moderation page
- GRN scanner page
- purchase barcodes
- aggregate stock impact after completion

### Order changes

Check:

- `/orders`
- create/edit/view/print/PDF
- call list
- waybill queue and print
- packing flow
  - treat as partial / TODO, not production-finished
  - current screen still matches by SKU, not by per-piece tracked unit code
  - current scan progress is client-side only and not persisted
  - current completion logic does not verify repeated scans against ordered quantity per SKU
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
