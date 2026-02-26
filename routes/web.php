<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\PermissionManagementController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GuestProductController;
use App\Http\Controllers\ProductImportController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/shop', [GuestProductController::class, 'index'])->name('guest.products');

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
    Route::resource('direct-resellers', \App\Http\Controllers\DirectResellerController::class)
        ->parameters(['direct-resellers' => 'directReseller']);
    Route::resource('cities', \App\Http\Controllers\CityController::class);
});




// Product Management Routes
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::get('products/export', [\App\Http\Controllers\ProductController::class, 'export'])->name('products.export');
    Route::get('products/{product}/success', [\App\Http\Controllers\ProductController::class, 'success'])->name('products.success');
    Route::post('products/bulk-destroy', [\App\Http\Controllers\ProductController::class, 'bulkDestroy'])->name('products.destroy.bulk');
    Route::get('products/print-barcodes-bulk', [\App\Http\Controllers\ProductController::class, 'bulkPrintBarcode'])->name('products.barcode.bulk');
    Route::get('variants/{variant}/print-barcode', [\App\Http\Controllers\ProductController::class, 'printBarcode'])->name('products.barcode.print');
    
    // Product Import
    Route::get('products/import', [\App\Http\Controllers\ProductImportController::class, 'show'])->name('products.import.show');
    Route::get('products/import/template', [\App\Http\Controllers\ProductImportController::class, 'downloadTemplate'])->name('products.import.template');
    Route::post('products/import/preview', [\App\Http\Controllers\ProductImportController::class, 'preview'])->name('products.import.preview');
    Route::post('products/import/store', [\App\Http\Controllers\ProductImportController::class, 'store'])->name('products.import.store');
    
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
    
    // Search APIs
    Route::get('/search-products', [\App\Http\Controllers\OrderController::class, 'searchProducts'])->name('search-products');
    Route::get('/search-resellers', [\App\Http\Controllers\OrderController::class, 'searchResellers'])->name('search-resellers');
    
    // Waybill (Must be before /{order} wildcard)
    Route::get('/waybill', [\App\Http\Controllers\WaybillController::class, 'index'])->name('waybill.index');
    Route::get('/waybill/{courier}', [\App\Http\Controllers\WaybillController::class, 'show'])->name('waybill.show');
    Route::post('/waybill/print', [\App\Http\Controllers\WaybillController::class, 'print'])->name('waybill.print');
    
    // CRUD & PDF
    Route::get('/{order}', [\App\Http\Controllers\OrderController::class, 'show'])->name('show');
    Route::get('/{order}/edit', [\App\Http\Controllers\OrderController::class, 'edit'])->name('edit');
    Route::put('/{order}', [\App\Http\Controllers\OrderController::class, 'update'])->name('update');
    Route::delete('/{order}', [\App\Http\Controllers\OrderController::class, 'destroy'])->name('destroy');
    Route::get('/{order}/pdf', [\App\Http\Controllers\OrderController::class, 'downloadPdf'])->name('pdf');
    
    // Status update
    Route::post('/{id}/status', [\App\Http\Controllers\OrderController::class, 'updateStatus'])->name('status.update');



    // Packing
    Route::get('/packing', [\App\Http\Controllers\PackingController::class, 'index'])->name('packing.index');
    Route::get('/packing/{id}/process', [\App\Http\Controllers\PackingController::class, 'process'])->name('packing.process');
    Route::post('/packing/{id}/mark-packed', [\App\Http\Controllers\PackingController::class, 'markPacked'])->name('packing.mark-packed');
});

// Reseller Specific Order Route (Shortcut)
Route::middleware(['auth'])->get('/reseller-orders/create', [\App\Http\Controllers\ResellerOrderController::class, 'create'])->name('reseller-orders.create');

// Reseller Targets
Route::middleware(['auth'])->resource('reseller-targets', \App\Http\Controllers\ResellerTargetController::class);
Route::middleware(['auth'])->get('reseller-payments/import', [\App\Http\Controllers\ResellerPaymentImportController::class, 'show'])->name('reseller-payments.import.show');
Route::middleware(['auth'])->post('reseller-payments/import/preview', [\App\Http\Controllers\ResellerPaymentImportController::class, 'preview'])->name('reseller-payments.import.preview');
Route::middleware(['auth'])->post('reseller-payments/import/store', [\App\Http\Controllers\ResellerPaymentImportController::class, 'store'])->name('reseller-payments.import.store');
Route::middleware(['auth'])->get('reseller-payments/template', [\App\Http\Controllers\ResellerPaymentImportController::class, 'downloadTemplate'])->name('reseller-payments.import.template');

Route::middleware(['auth'])->resource('reseller-payments', \App\Http\Controllers\ResellerPaymentController::class)->except(['destroy']);
Route::middleware(['auth'])->post('reseller-payments/{reseller_payment}/cancel', [\App\Http\Controllers\ResellerPaymentController::class, 'cancel'])->name('reseller-payments.cancel');
Route::middleware(['auth'])->get('reseller-payments/{reseller_payment}/download', [\App\Http\Controllers\ResellerPaymentController::class, 'downloadInvoice'])->name('reseller-payments.download');
Route::middleware(['auth'])->get('reseller-payments-bulk', [\App\Http\Controllers\ResellerPaymentController::class, 'downloadBulkInvoices'])->name('reseller-payments.download-bulk');
Route::middleware(['auth'])->get('reseller-dues', [\App\Http\Controllers\ResellerDuesController::class, 'index'])->name('reseller-dues.index');
Route::middleware(['auth'])->get('reseller-dues/{id}', [\App\Http\Controllers\ResellerDuesController::class, 'show'])->name('reseller-dues.show');

// Courier Management Routes
Route::middleware(['auth'])->group(function () {
    // Receive Courier
    Route::get('receive-courier', [\App\Http\Controllers\CourierReceiveController::class, 'index'])->name('courier-receive.index');
    Route::get('receive-courier/search-order', [\App\Http\Controllers\CourierReceiveController::class, 'searchOrder'])->name('courier-receive.search-order');
    Route::get('receive-courier/{courier}', [\App\Http\Controllers\CourierReceiveController::class, 'show'])->name('courier-receive.show');
    Route::post('receive-courier/{courier}/import', [\App\Http\Controllers\CourierReceiveController::class, 'import'])->name('courier-receive.import'); // For Excel preview/process
    Route::post('receive-courier/{courier}/store', [\App\Http\Controllers\CourierReceiveController::class, 'store'])->name('courier-receive.store');   // Final store

    Route::resource('couriers', \App\Http\Controllers\CourierController::class);
    Route::resource('courier-payments', \App\Http\Controllers\CourierPaymentController::class);
    Route::resource('bank-accounts', \App\Http\Controllers\BankAccountController::class)->except(['show']);
    // Purchases
    Route::get('/purchases/{purchase}/pdf', [\App\Http\Controllers\PurchaseController::class, 'pdf'])->name('purchases.pdf');
    Route::resource('purchases', \App\Http\Controllers\PurchaseController::class);
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
