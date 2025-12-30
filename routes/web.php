<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\PermissionManagementController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GuestProductController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/products', [GuestProductController::class, 'index'])->name('guest.products');

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes - permission-based access control
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserManagementController::class);
    Route::resource('roles', RoleManagementController::class);
    Route::resource('permissions', PermissionManagementController::class);
});

Route::middleware('auth')->group(function () {
    Route::resource('customers', \App\Http\Controllers\CustomerController::class);
    Route::resource('suppliers', \App\Http\Controllers\SupplierController::class);
    Route::resource('resellers', \App\Http\Controllers\ResellerController::class);
    Route::resource('cities', \App\Http\Controllers\CityController::class);
});


// Reseller Management Routes
Route::middleware(['auth'])->prefix('reseller-management')->name('resellers.')->group(function () {
    // Dashboard
    Route::get('dashboard', [\App\Http\Controllers\ResellerManagementController::class, 'dashboard'])->name('dashboard');

    // User Management (Resellers, Direct Resellers, Sub Users)
    Route::get('users', [\App\Http\Controllers\ResellerManagementController::class, 'index'])->name('users.index');
    Route::get('users/create', [\App\Http\Controllers\ResellerManagementController::class, 'create'])->name('users.create');
    Route::post('users', [\App\Http\Controllers\ResellerManagementController::class, 'store'])->name('users.store');
    // Add edit/update/destroy if needed

    // Targets (REMOVED for Resellers)
    // Route::resource('targets', \App\Http\Controllers\ResellerTargetController::class);

    // Payments
    Route::get('payments', [\App\Http\Controllers\ResellerPaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/create', [\App\Http\Controllers\ResellerPaymentController::class, 'create'])->name('payments.create');
    Route::post('payments', [\App\Http\Controllers\ResellerPaymentController::class, 'store'])->name('payments.store');
    Route::get('payments/dues', [\App\Http\Controllers\ResellerPaymentController::class, 'dues'])->name('payments.dues');
});

// Seller Management Routes
Route::middleware(['auth'])->prefix('seller-management')->name('sellers.')->group(function () {
    // Dashboard
    Route::get('dashboard', [\App\Http\Controllers\SellerManagementController::class, 'dashboard'])->name('dashboard');

    // User Management
    Route::get('users', [\App\Http\Controllers\SellerManagementController::class, 'index'])->name('users.index');
    Route::get('users/create', [\App\Http\Controllers\SellerManagementController::class, 'create'])->name('users.create');
    Route::post('users', [\App\Http\Controllers\SellerManagementController::class, 'store'])->name('users.store');

    // Targets
    Route::resource('targets', \App\Http\Controllers\SellerTargetController::class);
});

// Product Management Routes
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::resource('products', \App\Http\Controllers\ProductController::class);
    Route::resource('categories', \App\Http\Controllers\CategoryController::class);
    Route::resource('sub-categories', \App\Http\Controllers\SubCategoryController::class);
    Route::resource('units', \App\Http\Controllers\UnitController::class);
    Route::resource('attributes', \App\Http\Controllers\AttributeController::class);

    // Quick Create Routes (AJAX)
    Route::post('/quick-create/category', [\App\Http\Controllers\QuickCreateController::class, 'storeCategory'])->name('quick.category.store');
    Route::post('/quick-create/sub-category', [\App\Http\Controllers\QuickCreateController::class, 'storeSubCategory'])->name('quick.subcategory.store');
    Route::post('/quick-create/unit', [\App\Http\Controllers\QuickCreateController::class, 'storeUnit'])->name('quick.unit.store');
});

// Order Management System (OMS) Routes
Route::middleware(['auth'])->prefix('orders')->name('orders.')->group(function () {
    // General Orders
    Route::get('/', [\App\Http\Controllers\OrderController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\OrderController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\OrderController::class, 'store'])->name('store');
    Route::get('/call-list', [\App\Http\Controllers\OrderController::class, 'callList'])->name('call-list');
    
    // Status update
    Route::post('/{id}/status', [\App\Http\Controllers\OrderController::class, 'updateStatus'])->name('status.update');

    // Waybill
    Route::get('/waybill', [\App\Http\Controllers\WaybillController::class, 'index'])->name('waybill.index');
    Route::post('/waybill/print', [\App\Http\Controllers\WaybillController::class, 'print'])->name('waybill.print');

    // Packing
    Route::get('/packing', [\App\Http\Controllers\PackingController::class, 'index'])->name('packing.index');
    Route::get('/packing/{id}/process', [\App\Http\Controllers\PackingController::class, 'process'])->name('packing.process');
    Route::post('/packing/{id}/mark-packed', [\App\Http\Controllers\PackingController::class, 'markPacked'])->name('packing.mark-packed');
    
    // Reseller Orders (Add Reseller Orders sub-route for clarity if needed, or keep separate)
});

// Order Management System (OMS) Routes
Route::middleware(['auth'])->prefix('orders')->name('orders.')->group(function () {
    // ... existing routes
});

// Reseller Specific Order Route (Shortcut)
Route::middleware(['auth'])->get('/reseller-orders/create', [\App\Http\Controllers\ResellerOrderController::class, 'create'])->name('reseller-orders.create');

// Purchase Management Routes
Route::middleware(['auth'])->prefix('purchases')->name('purchases.')->group(function () {
    Route::get('/', [\App\Http\Controllers\PurchaseController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\PurchaseController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\PurchaseController::class, 'store'])->name('store');
    Route::get('/{id}', [\App\Http\Controllers\PurchaseController::class, 'show'])->name('show');
    Route::post('/{id}/verify', [\App\Http\Controllers\PurchaseController::class, 'verify'])->name('verify');
});

// Courier Management Routes
Route::middleware(['auth'])->group(function () {
    Route::resource('couriers', \App\Http\Controllers\CourierController::class);
    Route::resource('courier-payments', \App\Http\Controllers\CourierPaymentController::class);
});

// Report Routes
Route::middleware(['auth'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ReportController::class, 'index'])->name('index');
    Route::get('/province', [\App\Http\Controllers\ReportController::class, 'provinceSale'])->name('province');
    Route::get('/profit-loss', [\App\Http\Controllers\ReportController::class, 'profitLoss'])->name('profit-loss');
    Route::get('/stock', [\App\Http\Controllers\ReportController::class, 'stockReport'])->name('stock');
    Route::get('/packet-count', [\App\Http\Controllers\ReportController::class, 'packetCount'])->name('packet-count');
    Route::get('/product-sales', [\App\Http\Controllers\ReportController::class, 'productSales'])->name('product-sales');
    Route::get('/user-sales', [\App\Http\Controllers\ReportController::class, 'userSales'])->name('user-sales');
});

require __DIR__.'/auth.php';
