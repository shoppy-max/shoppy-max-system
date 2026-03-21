# Shoppy Max — Full Gap Analysis & Development Roadmap

> **Prepared:** 2026-03-21  
> **Purpose:** Identify every gap in the current codebase — runtime bugs, stub/placeholder implementations, missing features, orphaned files, and tech-debt — so that development priorities can be set clearly.

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Critical Runtime Bugs](#2-critical-runtime-bugs)
3. [Stub & Placeholder Implementations](#3-stub--placeholder-implementations)
4. [Missing Features — No Backend or Frontend Exists](#4-missing-features--no-backend-or-frontend-exists)
5. [Incomplete Features — Backend Exists, Frontend is a Stub](#5-incomplete-features--backend-exists-frontend-is-a-stub)
6. [Incomplete Features — Frontend Exists, Backend is Broken or Weak](#6-incomplete-features--frontend-exists-backend-is-broken-or-weak)
7. [Potentially Orphaned / Legacy Files](#7-potentially-orphaned--legacy-files)
8. [Tech Debt & Code Quality Issues](#8-tech-debt--code-quality-issues)
9. [Feature Completeness Matrix](#9-feature-completeness-matrix)
10. [Recommended Action Plan](#10-recommended-action-plan)

---

## 1. System Overview

Shoppy Max is a **Laravel 12** B2B/B2C order and inventory management platform with:

| Area | Status Summary |
|------|---------------|
| Order Management (CRUD, call flow, dispatch) | ✅ Substantially complete |
| Inventory Unit Tracking (GRN → allocated → delivered) | ✅ Substantially complete |
| Purchase Management (supplier POs, GRN scanning) | ✅ Substantially complete |
| Courier Integration (waybills, payments, settlements) | ✅ Substantially complete |
| Reseller & Direct-Reseller Management | ✅ Substantially complete |
| Packing Workflow (scanner UI) | ✅ Core complete, batch stub |
| Product Management (variants, barcodes, import) | ✅ Substantially complete |
| Reports | ⚠️ Some reports broken |
| Dashboard | ❌ Completely empty (placeholders) |
| User Activity Logs | ❌ Not implemented |
| Return / RMA Workflow | ❌ Not implemented |
| Guest Shop (Browse-only) | ⚠️ Works, no cart/checkout |
| Batch Packing | ❌ Stub only |
| Attribute Value Editing | ⚠️ Partial (name only) |
| Category / Product Image Upload | ⚠️ Field exists, no upload logic |
| API Layer | ⚠️ Only 2 endpoints |

---

## 2. Critical Runtime Bugs

These will throw **PHP/Laravel exceptions** when the page or action is visited.

---

### 2.1 `ReportController::packetCount()` — Undefined Relationship

**File:** `app/Http/Controllers/ReportController.php` — line 137  
**Route:** `GET /reports/packet-count`

```php
$packers = User::withCount(['packedOrders' => function($q){
     $q->where('status', 'confirm');
}])->get();
```

**Problem:** The `packedOrders` relationship **does not exist** on the `User` model. The `User` model currently has no Eloquent relationships defined at all (no `orders()`, no `packedOrders()`).

**Fix required:**
1. Add a `packedOrders()` relationship to `app/Models/User.php`:
   ```php
   public function packedOrders()
   {
       return $this->hasMany(Order::class, 'packed_by');
   }
   ```
2. Also add a general `orders()` relationship (used by `userSales()` below):
   ```php
   public function orders()
   {
       return $this->hasMany(Order::class, 'user_id');
   }
   ```

---

### 2.2 `ReportController::userSales()` — Undefined Relationship

**File:** `app/Http/Controllers/ReportController.php` — lines 155–163  
**Route:** `GET /reports/user-sales`

```php
$userSales = User::withSum(['orders' => function($q) {
    $q->where('status', 'confirm');
}], 'total_amount')
->withCount(['orders' => function($q) {
    $q->where('status', 'confirm');
}])
->get();
```

**Problem:** Same root cause — `orders` relationship is not defined on `User`.

**Fix:** Add `orders()` to User model (see 2.1).

---

### 2.3 `StockService::deductStock()` — References Non-Existent Fields

**File:** `app/Services/StockService.php` — lines 18–64

```php
$product->quantity -= $quantity;   // Product has no 'quantity' attribute
$product->save();

$batches = PurchaseItem::where('product_id', $product->id)
    ->where('remaining_quantity', '>', 0)   // PurchaseItem has no 'remaining_quantity'
    ->whereHas('purchase', function($q) {
        $q->where('status', 'verified');
    })
    ...

$batchCost = $batch->purchasing_price;  // PurchaseItem has no 'purchasing_price'
```

**Problems:**
- `Product::$quantity` does not exist; stock is tracked per `ProductVariant::$quantity`
- `PurchaseItem::$remaining_quantity` does not exist; the field is `purchase_price` and there is no FIFO remainder field
- `PurchaseItem::$purchasing_price` does not exist; the column is `purchase_price`
- `StockService` is **not imported or called anywhere** in the current application — see §7.1

**Fix:** Either:
- Delete the service (it has been superseded by `InventoryUnitService`), or
- Rewrite it to reference the correct model fields if FIFO cost is still needed for reporting

---

### 2.4 `ReportController::stockReport()` — Assumes Non-Existent Relationship & Column

**File:** `app/Http/Controllers/ReportController.php` — lines 118–131  
**Route:** `GET /reports/stock`

```php
$products = Product::with(['purchaseItems' => function($q) {
    $q->where('remaining_quantity', '>', 0);
}])->get();
```

**Problems:**
- `Product` has no `purchaseItems()` relationship defined
- `remaining_quantity` does not exist on `PurchaseItem` (same issue as 2.3)

**Fix:** Either:
- Add a `purchaseItems()` relationship to `Product` model and use actual column names, or
- Rewrite the stock report to use `InventoryUnit` and `PurchaseItem` correctly (e.g., count units with `status = 'available'` grouped by variant and purchase)

---

## 3. Stub & Placeholder Implementations

These exist in code with comments explicitly marking them as incomplete.

---

### 3.1 `DashboardController::index()` — All Stats Hardcoded to Zero

**File:** `app/Http/Controllers/DashboardController.php`

```php
// Placeholder Stats (Replace with actual queries later)
$stats = [
    'total_sales_count'  => 0,
    'total_sales_value'  => 0,
    'pending_orders'     => 0,
    'confirmed_orders'   => 0,
    'hold_orders'        => 0,
    'total_commission'   => 0,
    'paid_commission'    => 0,
];
```

**Additionally:** The `dashboard.blade.php` view **does not render the `$stats` array at all** — it only shows a welcome message and a role badge. The stats are passed from the controller but never displayed.

**Fix required:**
1. Replace placeholder with actual queries, e.g.:
   ```php
   $stats = [
       'total_sales_count'  => Order::where('status', 'confirm')->count(),
       'total_sales_value'  => Order::where('status', 'confirm')->sum('total_amount'),
       'pending_orders'     => Order::where('status', 'pending')->count(),
       'confirmed_orders'   => Order::where('call_status', 'confirm')->count(),
       'hold_orders'        => Order::where('status', 'hold')->count(),
       'total_commission'   => Order::where('status', 'confirm')->sum('total_commission'),
       'paid_commission'    => 0, // Define if/how commissions are paid
   ];
   ```
2. Update `dashboard.blade.php` to render `$stats` in a stats-card grid.

---

### 3.2 `UserLogController::index()` — Empty Stub

**File:** `app/Http/Controllers/UserLogController.php`

```php
public function index()
{
    return view('user-logs.index');
}
```

**Additionally:** `resources/views/user-logs/index.blade.php` shows a "Coming Soon" placeholder message. There is **no logging system** in the application at all — no package (e.g., `spatie/laravel-activitylog`), no custom `UserLog` model, no log table.

**Fix required:**
1. Choose a logging strategy: install `spatie/laravel-activitylog` (recommended) **or** create a custom `user_logs` table + model
2. Add log-write calls at key actions (login, order create/update, purchase approve, etc.)
3. Implement `UserLogController::index()` with pagination, filters (user, date range, action type)
4. Replace the "Coming Soon" view with a table displaying log entries

---

### 3.3 `PackingController::createBatch()` — Returns "Coming Soon"

**File:** `app/Http/Controllers/PackingController.php` — lines 221–225

```php
public function createBatch(Request $request)
{
    // Batch logic to be implemented
    return back()->with('info', 'Batch creation feature coming soon.');
}
```

**Note:** This method is not currently wired to any route in `routes/web.php` (no route calls `packing.create-batch`), so it cannot be triggered from the UI. The stub is dead code.

**Fix required:**
- Add route and UI for batch packing if the feature is needed, or
- Remove the method and keep the focus on individual-order packing (which is complete)

---

### 3.4 `AttributeController::update()` — Cannot Modify Attribute Values

**File:** `app/Http/Controllers/AttributeController.php` — lines 48–57

```php
public function update(Request $request, Attribute $attribute)
{
    $request->validate([
        'name' => 'required',
        // Update values logic can be complex (add/remove), keeping simple name update for now
    ]);

    $attribute->update(['name' => $request->name]);
    ...
}
```

**Problem:** Users can only rename an attribute. Existing values cannot be added to, removed, or renamed.

**Fix required:**
- Update the `edit` view to show current values with remove buttons and an add-value field
- Update `update()` to accept a `values` array and sync `AttributeValue` records (delete missing, create new)

---

### 3.5 `CategoryController::store()` — Image Field Passthrough Without Upload

**File:** `app/Http/Controllers/CategoryController.php` — lines 41–46

```php
// Simple placeholder logic for image handling (to be improved if needed)
$input = $request->all();
Category::create($input);
```

**Problem:** The `image` column exists in the `categories` table. If a file is submitted, it will try to store the file object as a string (or store a temp filename). No file upload validation, storage, or path-setting logic exists.

**Fix required:**
- Add file upload validation (`'image' => 'nullable|image|max:2048'`)
- Store the file to `storage/app/public/categories/` and save the path
- Same treatment needed for `Product::$image` and `ProductVariant::$image` fields (both have image columns, neither has upload logic)

---

## 4. Missing Features — No Backend or Frontend Exists

These features have **zero implementation** in any layer.

---

### 4.1 Return / RMA Workflow

**What exists:** The `delivery_status` column supports a `returned` value, and `OrderController` sets this status and applies a `reseller_return_fee`. The inventory unit service calls `releaseOrderUnits()` on return.

**What does NOT exist:**
- No dedicated return UI or workflow page
- No way to initiate a return from the admin dashboard (the only way is via the courier payment removal, which sets status to `dispatched`, not `returned`)
- No reason/RMA tracking
- No return approval workflow
- No restocking/re-shelving step
- No customer-facing return request

**Recommended implementation:**
1. Add a `POST /orders/{order}/return` route and `OrderController::markReturned()` method
2. Add a Return Reason field (or a separate `return_reason` column)
3. Trigger `InventoryUnitService::releaseOrderUnits()` and reseller return fee from there
4. Add a "Returned Orders" section to the orders list/dashboard

---

### 4.2 User Activity / Audit Log System

**What exists:** `OrderLog` model records order-level events. `InventoryUnitEvent` records unit-level state transitions. There is no system-wide user activity log.

**What does NOT exist:**
- No `user_logs` table or migration
- No logging of: login/logout, product create/edit, purchase approval, settings changes
- No `UserLog` model
- `UserLogController` returns only a "Coming Soon" view

**Recommended implementation:**
1. Install `spatie/laravel-activitylog` or create a custom `user_activity_logs` table
2. Log key actions via model observers or controller hooks
3. Implement `UserLogController` with filters (user, date, action)
4. Replace the placeholder view with a sortable, filterable table

---

### 4.3 Guest Shop Cart & Checkout

**What exists:** `GET /shop` renders `GuestProductController::index()` — a fully-functional product browsing page with search, filter, and sorting.

**What does NOT exist:**
- No "Add to Cart" button or basket
- No checkout flow
- No guest order placement
- No product detail/single product page (`/shop/{product}`)

**Decision needed:** Is `/shop` intended to become a B2C storefront, or is it a catalog reference only? If catalog-only, add a note on the page. If it should become a shop, a significant amount of work is required.

---

### 4.4 Product–Attribute Assignment on Products

**What exists:**
- `Attribute` and `AttributeValue` models are implemented
- `product_attributes` pivot table migration exists (links `product_id → attribute_id → attribute_value_id`)
- CRUD for managing attribute definitions works

**What does NOT exist:**
- No UI or controller logic to **assign** attribute values to individual products
- `Product` model has no `attributes()` or `attributeValues()` relationship defined
- The pivot table (`product_attributes`) is never read or written to anywhere in the application
- Attributes are not shown on any product view

**Impact:** The entire `product_attributes` table is currently dead data.

**Recommended implementation:**
1. Add `attributes()` many-to-many relationship to `Product` model
2. Add an attribute assignment section to the product create/edit form
3. Display assigned attributes on the product show/list view

---

### 4.5 API Layer

**What exists:** `routes/api.php` has two endpoints:
```
GET  /api/user       (auth:sanctum)
GET  /api/cities     (getCitiesByDistrict)
```

**What does NOT exist:** Any meaningful API for:
- Order management
- Product/variant lookup
- Stock queries
- Reseller operations
- Purchase management
- Courier integration

**Impact:** No third-party or mobile app integration is possible.

**Decision needed:** Is an API planned? If yes, define the scope before implementation.

---

## 5. Incomplete Features — Backend Exists, Frontend is a Stub

---

### 5.1 Dashboard Metrics

**Backend:** `DashboardController` passes a `$stats` array to the view (though it is all zeros currently).  
**Frontend:** `dashboard.blade.php` never renders `$stats`. It shows only a welcome message.

**Fix:** Once the controller stats are real (§3.1), add a stats-card grid to the view using the `x-stats-card` component that already exists in `resources/views/components/stats-card.blade.php`.

---

### 5.2 Profit & Loss Report — COGS May Be Inaccurate

**File:** `app/Http/Controllers/ReportController.php` — `profitLoss()`

The report calculates COGS from `order_items.cost_price`. That field (`cost_price`) was added in a migration but is **only set to 0 by default** — no controller sets it at order creation time. The `StockService::deductStock()` method was supposed to snapshot it, but that service is never called.

**Result:** The P&L report very likely shows $0 COGS for all orders, making the gross profit calculation misleading.

**Fix required:**
- At order creation time in `OrderController::store()`, snapshot the current purchase cost for each item into `order_items.cost_price`
- This can use `InventoryUnitService` + the `purchase_price` from the linked `PurchaseItem`

---

### 5.3 Stock Report — Relationship & Column Missing

**Route:** `GET /reports/stock`  
Already documented in §2.4 as a runtime bug. The view exists (`resources/views/reports/stock.blade.php`) but will never render successfully.

---

## 6. Incomplete Features — Frontend Exists, Backend is Broken or Weak

---

### 6.1 Packet Count Report

**Route:** `GET /reports/packet-count`  
**View:** `resources/views/reports/packet_count.blade.php` — view exists  
**Bug:** Already documented in §2.1. Will throw an error.

---

### 6.2 User Sales Report

**Route:** `GET /reports/user-sales`  
**View:** `resources/views/reports/user_sales.blade.php` — view exists  
**Bug:** Already documented in §2.2. Will throw an error.

---

## 7. Potentially Orphaned / Legacy Files

---

### 7.1 `app/Services/StockService.php` — Effectively Dead Code

**Status:** The file exists and implements FIFO stock deduction. However:
- It is **not imported or instantiated anywhere** in the application (confirmed by a full codebase search)
- It references `Product::$quantity` and `PurchaseItem::$remaining_quantity` which do not exist
- The inventory tracking it was designed to support has been superseded by `InventoryUnitService`

**Recommendation:** Either delete this file and add a migration note, or rewrite it from scratch if FIFO cost tracking is still desired for reporting purposes.

---

### 7.2 `Product::$barcode_data` — Noted as Legacy

**File:** `app/Models/Product.php` — fillable list includes `barcode_data`

The comment in the migration says:  
```
'barcode_data' can store base64 image or just rely on sku to generate it
```

Barcode generation now uses the variant SKU via `BarcodeGeneratorPNG`. The `barcode_data` field on Product is never written to by any controller.

**Recommendation:** Remove `barcode_data` from `$fillable` and consider dropping the column in a migration.

---

### 7.3 `routes/auth.php` — Standard Laravel Auth Routes

These are the standard Breeze auth routes (login, register, password reset, email verification). They are fully functional. However, **registration** (`GET/POST /register`) is included and allows anyone to create an account. If the application is internal-staff-only, registration should be disabled or admin-gated.

---

### 7.4 `resources/views/guest/` — Shop With No Checkout

The guest product listing at `/shop` is a polished, functional browse-only page. Without a cart or checkout, it serves only as a public catalog. If this is intentional, fine — but if it is meant to eventually support orders, plan for the guest checkout flow.

---

### 7.5 Multiple Redundant "Standardize Status" Migrations

The following migrations exist only to backfill/normalize data that was inconsistent from earlier versions:

```
2026_02_26_080000_standardize_order_call_status_values.php
2026_02_26_083000_force_pending_status_for_discount_or_online_orders.php
2026_02_26_090000_standardize_order_status_values.php
2026_02_26_121000_sync_call_status_with_order_cancel_status.php
2026_03_18_161000_remove_return_requested_from_order_delivery_statuses.php
2026_03_21_053100_backfill_order_timeline_audit_data.php
```

These are necessary history but represent a period of significant schema churn. Future status changes should be handled via the service layer, not migrations.

---

## 8. Tech Debt & Code Quality Issues

---

### 8.1 User Model Has No Order-Related Relationships

`app/Models/User.php` defines no Eloquent relationships. The `Order` model has several `belongsTo(User::class, 'packed_by')` etc., but the reverse is never defined. Any `withCount`, `withSum`, or eager loading via `User::with('orders')` will silently fail or throw.

**Missing relationships to add:**
```php
public function orders() { return $this->hasMany(Order::class, 'user_id'); }
public function packedOrders() { return $this->hasMany(Order::class, 'packed_by'); }
public function dispatchedOrders() { return $this->hasMany(Order::class, 'dispatched_by'); }
public function deliveredOrders() { return $this->hasMany(Order::class, 'delivered_by'); }
```

---

### 8.2 Product Model Has No PurchaseItems Relationship

`app/Models/Product.php` has no `purchaseItems()` relationship. The `ReportController::stockReport()` method calls `Product::with('purchaseItems')` which will fail. Add:
```php
public function purchaseItems()
{
    return $this->hasMany(PurchaseItem::class);
}
```

---

### 8.3 Large Controllers

| Controller | Approximate Lines |
|-----------|------------------|
| `OrderController` | ~600+ lines |
| `PurchaseController` | ~500+ lines |
| `InventoryUnitService` | ~800+ lines |

These are above typical size limits but are not a critical problem. Consider extracting reusable query logic into repository classes or action classes if further features are added.

---

### 8.4 Default Credentials in Production Seeder

**File:** `database/seeders/RolesAndPermissionsSeeder.php`

Creates a super-admin user with a known password. If this seeder runs on production, the account is compromised.

**Fix:** Gate the super-admin creation behind an environment check, or remove from the default seeder and use a separate `php artisan db:seed --class=...` command for local only.

---

### 8.5 No Formal Middleware for Role-Based Access Control

Routes are protected with `auth` middleware but there is no per-route role or permission gate. Admin routes (`/admin/...`) are accessible to any authenticated user. The `@can` Blade directive is used on the dashboard, but there are no `middleware('can:...')` or `middleware('role:...')` guards on routes.

**Fix:** Add permission/role gates to sensitive routes such as admin user management, purchase moderation, and report access.

---

### 8.6 `payment_data` vs `payments_data` Field Inconsistency

Both `orders` and `purchases` store payment entries as JSON. The field names are:
- `orders.payments_data`
- `purchases.payments_data`

This is consistent. However, the field is cast as `array` and allows any structure. There is no validation schema for the payment entries JSON. If the structure drifts, views that iterate over `payments_data` will silently produce nothing.

---

## 9. Feature Completeness Matrix

| Module | Controller | Routes | Views | Tests | Overall |
|--------|-----------|--------|-------|-------|---------|
| **Auth** | ✅ Complete | ✅ Complete | ✅ Complete | ✅ Basic | ✅ |
| **Dashboard** | ❌ Placeholder | ✅ Exists | ❌ Empty | ❌ None | ❌ |
| **User Management** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Role Management** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Permission Management** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **User Activity Logs** | ❌ Stub | ✅ Exists | ❌ Coming Soon | ❌ None | ❌ |
| **Products** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Product Import** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Product Barcodes** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Categories** | ⚠️ No image upload | ✅ Complete | ✅ Complete | ❌ None | ⚠️ |
| **Sub-Categories** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Units** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Attributes** | ⚠️ Can't edit values | ✅ Complete | ✅ Complete | ❌ None | ⚠️ |
| **Product ↔ Attribute Assignment** | ❌ Missing | ❌ Missing | ❌ Missing | ❌ None | ❌ |
| **Orders (CRUD)** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Orders (Call List)** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Orders (Export)** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Orders (PDF)** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Waybill Printing** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Packing (Individual)** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Packing (Batch)** | ❌ Stub | ❌ No route | ❌ N/A | ❌ None | ❌ |
| **Order Returns** | ❌ Missing UI | ❌ No route | ❌ N/A | ❌ None | ❌ |
| **Customers** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Suppliers** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Resellers** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Reseller Targets** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Reseller Payments** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Reseller Dues** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Direct Resellers** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Direct Reseller Payments** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Direct Reseller Dues** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Purchases (CRUD)** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Purchase Moderation** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **GRN Scanning** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Purchase Barcodes** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Couriers** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Courier Waybill Ranges** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Courier Receive / Payment** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Bank Accounts** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Cities** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Reports (Province Sales)** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Reports (Profit & Loss)** | ⚠️ COGS inaccurate | ✅ Complete | ✅ Complete | ❌ None | ⚠️ |
| **Reports (Stock)** | ❌ Runtime bug | ✅ Complete | ✅ Exists | ❌ None | ❌ |
| **Reports (Packet Count)** | ❌ Runtime bug | ✅ Complete | ✅ Exists | ❌ None | ❌ |
| **Reports (Product Sales)** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Reports (User Sales)** | ❌ Runtime bug | ✅ Complete | ✅ Exists | ❌ None | ❌ |
| **Reports (Summary)** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Inventory Units** | ✅ Complete | (console only) | N/A | ❌ None | ✅ |
| **Guest Shop (Browse)** | ✅ Complete | ✅ Complete | ✅ Complete | ❌ None | ✅ |
| **Guest Shop (Cart/Checkout)** | ❌ Missing | ❌ Missing | ❌ Missing | ❌ None | ❌ |
| **API Layer** | ⚠️ 2 endpoints only | ⚠️ Very limited | N/A | ❌ None | ⚠️ |

---

## 10. Recommended Action Plan

Items are ordered by **impact and urgency**.

---

### Priority 1 — Fix Runtime Bugs (Breaks Existing UI)

| # | Task | File(s) | Effort |
|---|------|---------|--------|
| 1a | Add `packedOrders()` and `orders()` relationships to `User` model | `app/Models/User.php` | ~30 min |
| 1b | Add `purchaseItems()` relationship to `Product` model | `app/Models/Product.php` | ~10 min |
| 1c | Fix `ReportController::stockReport()` to use correct columns & relationships | `app/Http/Controllers/ReportController.php` | ~2 hrs |
| 1d | Fix `ReportController::packetCount()` and `userSales()` (blocked on 1a) | `app/Http/Controllers/ReportController.php` | ~30 min |

---

### Priority 2 — Implement Core Missing Features

| # | Task | File(s) | Effort |
|---|------|---------|--------|
| 2a | Implement real dashboard stats in `DashboardController` | `DashboardController.php`, `dashboard.blade.php` | ~3 hrs |
| 2b | Implement User Activity Log (logging + controller + view) | New migration, `UserLogController.php`, `user-logs/index.blade.php` | ~1 day |
| 2c | Implement Return / RMA workflow (route + controller method + view) | `OrderController.php`, new route, new view | ~1–2 days |
| 2d | Fix COGS snapshot at order creation time | `OrderController::store()` and `update()` | ~4 hrs |

---

### Priority 3 — Complete Partial Implementations

| # | Task | File(s) | Effort |
|---|------|---------|--------|
| 3a | Fix `AttributeController::update()` to allow value add/remove | `AttributeController.php`, `attributes/edit.blade.php` | ~2 hrs |
| 3b | Implement image upload for Categories, Products, Variants | `CategoryController.php`, `ProductController.php` | ~4 hrs |
| 3c | Implement Product → Attribute assignment on product create/edit | `ProductController.php`, product form views | ~4 hrs |
| 3d | Either remove or rewrite `StockService` | `StockService.php` | ~2 hrs |

---

### Priority 4 — Clean Up & Security

| # | Task | File(s) | Effort |
|---|------|---------|--------|
| 4a | Add route-level role/permission middleware to admin and sensitive routes | `routes/web.php` | ~2 hrs |
| 4b | Remove `barcode_data` from `Product::$fillable`, deprecate column | `Product.php`, new migration | ~30 min |
| 4c | Gate registration to admin-only or disable public registration | `routes/auth.php`, `RegisteredUserController.php` | ~1 hr |
| 4d | Gate default seeder credentials behind environment check | `RolesAndPermissionsSeeder.php` | ~30 min |
| 4e | Remove or document `PackingController::createBatch()` | `PackingController.php` | ~15 min |

---

### Priority 5 — Future Features (Backlog)

| # | Task | Notes |
|---|------|-------|
| 5a | Guest shop cart & checkout | Requires design decision: B2C or catalog-only? |
| 5b | Batch packing workflow | Depends on operational need |
| 5c | REST API expansion | Requires scope decision and auth strategy |
| 5d | Automated test coverage | No meaningful tests exist; start with service layer |
| 5e | Operational expense (OpEx) tracking in P&L | Currently only COGS and courier costs |
| 5f | Inventory cycle count / reconciliation UI | Console commands exist, no web UI |

---

*This document was generated through full codebase analysis on 2026-03-21. Review and update it as features are completed.*
