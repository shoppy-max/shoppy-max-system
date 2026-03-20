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
    Route::get('user-logs', [\App\Http\Controllers\UserLogController::class, 'index'])->name('user-logs.index');
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
    Route::get('/export', [\App\Http\Controllers\OrderController::class, 'export'])->name('export');
    Route::get('/create', [\App\Http\Controllers\OrderController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\OrderController::class, 'store'])->name('store');
    Route::get('/call-list', [\App\Http\Controllers\OrderController::class, 'callList'])->name('call-list');
    
    // Search APIs
    Route::get('/search-products', [\App\Http\Controllers\OrderController::class, 'searchProducts'])->name('search-products');
    Route::get('/search-resellers', [\App\Http\Controllers\OrderController::class, 'searchResellers'])->name('search-resellers');
    Route::get('/search-customers', [\App\Http\Controllers\OrderController::class, 'searchCustomers'])->name('search-customers');
    
    // Waybill (Must be before /{order} wildcard)
    Route::get('/waybill', [\App\Http\Controllers\WaybillController::class, 'index'])->name('waybill.index');
    Route::get('/waybill/{courier}', [\App\Http\Controllers\WaybillController::class, 'show'])->name('waybill.show');
    Route::post('/waybill/print', [\App\Http\Controllers\WaybillController::class, 'print'])->name('waybill.print');
    Route::post('/waybill/reprint-bulk', [\App\Http\Controllers\WaybillController::class, 'bulkReprint'])->name('waybill.reprint-bulk');
    Route::get('/{order}/waybill/reprint', [\App\Http\Controllers\WaybillController::class, 'reprint'])->name('waybill.reprint');
    Route::post('/bulk-pdf', [\App\Http\Controllers\OrderController::class, 'downloadBulkPdf'])->name('bulk-pdf');
    
    // CRUD & PDF
    Route::get('/{order}/print', [\App\Http\Controllers\OrderController::class, 'printView'])->name('print');
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
    Route::post('/packing/{id}/scan', [\App\Http\Controllers\PackingController::class, 'scan'])->name('packing.scan');
    Route::post('/packing/{id}/mark-picked', [\App\Http\Controllers\PackingController::class, 'markPicked'])->name('packing.mark-picked');
    Route::post('/packing/{id}/mark-packed', [\App\Http\Controllers\PackingController::class, 'markPacked'])->name('packing.mark-packed');
    Route::post('/packing/{id}/mark-dispatched', [\App\Http\Controllers\PackingController::class, 'markDispatched'])->name('packing.mark-dispatched');
});

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

Route::middleware(['auth'])->get('direct-reseller-payments/import', [\App\Http\Controllers\DirectResellerPaymentImportController::class, 'show'])->name('direct-reseller-payments.import.show');
Route::middleware(['auth'])->post('direct-reseller-payments/import/preview', [\App\Http\Controllers\DirectResellerPaymentImportController::class, 'preview'])->name('direct-reseller-payments.import.preview');
Route::middleware(['auth'])->post('direct-reseller-payments/import/store', [\App\Http\Controllers\DirectResellerPaymentImportController::class, 'store'])->name('direct-reseller-payments.import.store');
Route::middleware(['auth'])->get('direct-reseller-payments/template', [\App\Http\Controllers\DirectResellerPaymentImportController::class, 'downloadTemplate'])->name('direct-reseller-payments.import.template');
Route::middleware(['auth'])->resource('direct-reseller-payments', \App\Http\Controllers\DirectResellerPaymentController::class)
    ->parameters(['direct-reseller-payments' => 'reseller_payment'])
    ->except(['destroy']);
Route::middleware(['auth'])->post('direct-reseller-payments/{reseller_payment}/cancel', [\App\Http\Controllers\DirectResellerPaymentController::class, 'cancel'])->name('direct-reseller-payments.cancel');
Route::middleware(['auth'])->get('direct-reseller-payments/{reseller_payment}/download', [\App\Http\Controllers\DirectResellerPaymentController::class, 'downloadInvoice'])->name('direct-reseller-payments.download');
Route::middleware(['auth'])->get('direct-reseller-payments-bulk', [\App\Http\Controllers\DirectResellerPaymentController::class, 'downloadBulkInvoices'])->name('direct-reseller-payments.download-bulk');
Route::middleware(['auth'])->get('direct-reseller-dues', [\App\Http\Controllers\DirectResellerDuesController::class, 'index'])->name('direct-reseller-dues.index');
Route::middleware(['auth'])->get('direct-reseller-dues/{id}', [\App\Http\Controllers\DirectResellerDuesController::class, 'show'])->name('direct-reseller-dues.show');

// Courier Management Routes
Route::middleware(['auth'])->group(function () {
    // Receive Courier
    Route::get('receive-courier', [\App\Http\Controllers\CourierReceiveController::class, 'index'])->name('courier-receive.index');
    Route::get('receive-courier/search-order', [\App\Http\Controllers\CourierReceiveController::class, 'searchOrder'])->name('courier-receive.search-order');
    Route::get('receive-courier/{courier}', [\App\Http\Controllers\CourierReceiveController::class, 'show'])->name('courier-receive.show');
    Route::post('receive-courier/{courier}/import', [\App\Http\Controllers\CourierReceiveController::class, 'import'])->name('courier-receive.import'); // For Excel preview/process
    Route::post('receive-courier/{courier}/store', [\App\Http\Controllers\CourierReceiveController::class, 'store'])->name('courier-receive.store');   // Final store

    Route::get('couriers/{courier}/waybills', [\App\Http\Controllers\CourierWaybillController::class, 'index'])->name('couriers.waybills.index');
    Route::post('couriers/{courier}/waybills', [\App\Http\Controllers\CourierWaybillController::class, 'store'])->name('couriers.waybills.store');
    Route::resource('couriers', \App\Http\Controllers\CourierController::class);
    Route::get('courier-payments/create', function () {
        return redirect()
            ->route('courier-receive.index')
            ->with('info', 'Add new courier payments from Receive Courier Payment.');
    })->name('courier-payments.create');
    Route::resource('courier-payments', \App\Http\Controllers\CourierPaymentController::class)->except(['create', 'store', 'destroy']);
    Route::resource('bank-accounts', \App\Http\Controllers\BankAccountController::class)->except(['show']);
    // Purchases
    Route::get('/purchases/{purchase}/pdf', [\App\Http\Controllers\PurchaseController::class, 'pdf'])->name('purchases.pdf');
    Route::get('/purchases/{purchase}/success', [\App\Http\Controllers\PurchaseController::class, 'success'])->name('purchases.success');
    Route::get('/purchases/{purchase}/barcodes', [\App\Http\Controllers\PurchaseController::class, 'printBarcodes'])->name('purchases.barcodes');
    Route::get('/purchases/{purchase}/items/{item}/barcodes', [\App\Http\Controllers\PurchaseController::class, 'printItemBarcodes'])->name('purchases.items.barcodes');
    Route::get('/purchases/moderation', function () {
        return redirect()->route('purchases.moderation.checking');
    })->name('purchases.moderation.index');
    Route::get('/purchases/moderation/checking', [\App\Http\Controllers\PurchaseController::class, 'moderationChecking'])->name('purchases.moderation.checking');
    Route::get('/purchases/moderation/verifying', [\App\Http\Controllers\PurchaseController::class, 'moderationVerifying'])->name('purchases.moderation.verifying');
    Route::get('/purchases/moderation/grn-checking', [\App\Http\Controllers\PurchaseController::class, 'moderationGrn'])->name('purchases.moderation.grn');
    Route::post('/purchases/{purchase}/approve-stage', [\App\Http\Controllers\PurchaseController::class, 'approveModerationStage'])->name('purchases.moderation.approve');
    Route::get('/purchases/{purchase}/grn-checking', [\App\Http\Controllers\PurchaseController::class, 'showGrn'])->name('purchases.grn.show');
    Route::post('/purchases/{purchase}/grn-checking/scan', [\App\Http\Controllers\PurchaseController::class, 'scanGrnUnit'])->name('purchases.grn.scan');
    Route::get('/purchases/search-suppliers', [\App\Http\Controllers\PurchaseController::class, 'searchSuppliers'])->name('purchases.search-suppliers');
    Route::get('/purchases/search-products', [\App\Http\Controllers\PurchaseController::class, 'searchProducts'])->name('purchases.search-products');
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
