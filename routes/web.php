<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\Login;
use App\Livewire\Install;

// Installation route (no middleware)
Route::get('/install', Install::class)->name('install');

// Redirect root to login or install
Route::get('/', function () {
    if (!file_exists(storage_path('installed.lock'))) {
        return redirect('/install');
    }
    return redirect('/login');
});

// Authentication routes
Route::get('/login', Login::class)
    ->name('login')
    ->middleware('guest');

Route::post('/logout', function () {
    \App\Services\ActivityLogService::logLogout();
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// Protected routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', App\Livewire\Dashboard::class)
        ->name('dashboard')
        ->middleware('permission:dashboard.view');

    Route::get('/users', App\Livewire\Users::class)
        ->name('users')
        ->middleware('permission:users.view');

    Route::get('/branches', App\Livewire\Branches::class)
        ->name('branches')
        ->middleware('permission:branches.view');

    Route::get('/roles', App\Livewire\Roles::class)
        ->name('roles')
        ->middleware('permission:roles.view');

    Route::get('/departments', App\Livewire\Departments::class)
        ->name('departments')
        ->middleware('permission:departments.view');

    Route::get('/municipalities', App\Livewire\Municipalities::class)
        ->name('municipalities')
        ->middleware('permission:municipalities.view');

    Route::get('/tax-documents', App\Livewire\TaxDocuments::class)
        ->name('tax-documents')
        ->middleware('permission:tax_documents.view');

    Route::get('/currencies', App\Livewire\Currencies::class)
        ->name('currencies')
        ->middleware('permission:currencies.view');

    Route::get('/payment-methods', App\Livewire\PaymentMethods::class)
        ->name('payment-methods')
        ->middleware('permission:payment_methods.view');

    Route::get('/taxes', App\Livewire\Taxes::class)
        ->name('taxes')
        ->middleware('permission:taxes.view');

    Route::get('/system-documents', App\Livewire\SystemDocuments::class)
        ->name('system-documents')
        ->middleware('permission:system_documents.view');

    // Product Catalog Routes
    Route::get('/categories', App\Livewire\Categories::class)
        ->name('categories')
        ->middleware('permission:categories.view');

    Route::get('/subcategories', App\Livewire\Subcategories::class)
        ->name('subcategories')
        ->middleware('permission:subcategories.view');

    Route::get('/brands', App\Livewire\Brands::class)
        ->name('brands')
        ->middleware('permission:brands.view');

    Route::get('/units', App\Livewire\Units::class)
        ->name('units')
        ->middleware('permission:units.view');

    Route::get('/product-models', App\Livewire\ProductModels::class)
        ->name('product-models')
        ->middleware('permission:product_models.view');

    Route::get('/presentations', App\Livewire\Presentations::class)
        ->name('presentations')
        ->middleware('permission:presentations.view');

    Route::get('/colors', App\Livewire\Colors::class)
        ->name('colors')
        ->middleware('permission:colors.view');

    Route::get('/imeis', App\Livewire\Imeis::class)
        ->name('imeis')
        ->middleware('permission:imeis.view');

    // Customer Management Routes
    Route::get('/customers', App\Livewire\Customers::class)
        ->name('customers')
        ->middleware('permission:customers.view');

    Route::get('/suppliers', App\Livewire\Suppliers::class)
        ->name('suppliers')
        ->middleware('permission:suppliers.view');

    // Products Management Routes
    Route::get('/products', App\Livewire\Products::class)
        ->name('products')
        ->middleware('permission:products.view');

    Route::get('/product-field-config', App\Livewire\ProductFieldConfig::class)
        ->name('product-field-config')
        ->middleware('permission:product_field_config.view');

    // Combos Management Routes
    Route::get('/combos', App\Livewire\Combos::class)
        ->name('combos')
        ->middleware('permission:combos.view');

    // Services Management Routes
    Route::get('/services', App\Livewire\Services::class)
        ->name('services')
        ->middleware('permission:services.view');

    // Purchases Management Routes
    Route::get('/purchases', App\Livewire\Purchases::class)
        ->name('purchases')
        ->middleware('permission:purchases.view');

    Route::get('/purchases/create', App\Livewire\PurchaseCreate::class)
        ->name('purchases.create')
        ->middleware('permission:purchases.create');

    Route::get('/purchases/{id}/edit', App\Livewire\PurchaseCreate::class)
        ->name('purchases.edit')
        ->middleware('permission:purchases.edit');

    Route::get('/inventory-adjustments', App\Livewire\InventoryAdjustments::class)
        ->name('inventory-adjustments')
        ->middleware('permission:inventory_adjustments.view');

    Route::get('/inventory-transfers', App\Livewire\InventoryTransfers::class)
        ->name('inventory-transfers')
        ->middleware('permission:inventory_transfers.view');

    Route::get('/cash-registers', App\Livewire\CashRegisters::class)
        ->name('cash-registers')
        ->middleware('permission:cash_registers.view');

    Route::get('/cash-reconciliations', App\Livewire\CashReconciliations::class)
        ->name('cash-reconciliations')
        ->middleware('permission:cash_reconciliations.view');

    // Sales
    Route::get('/sales', App\Livewire\Sales::class)
        ->name('sales')
        ->middleware('permission:sales.view');

    // Billing Settings
    Route::get('/billing-settings', App\Livewire\BillingSettings::class)
        ->name('billing-settings')
        ->middleware('permission:billing_settings.view');

    // Point of Sale
    Route::get('/pos', App\Livewire\PointOfSale::class)
        ->name('pos')
        ->middleware('permission:pos.access');

    // POS Receipt
    Route::get('/receipt/{sale}', function (App\Models\Sale $sale) {
        // Load relationships needed for the receipt
        $sale->load([
            'branch.department',
            'branch.municipality',
            'customer.taxDocument',
            'user',
            'items',
            'payments.paymentMethod',
            'cashReconciliation.cashRegister',
        ]);
        
        return view('receipts.pos-receipt', compact('sale'));
    })->name('receipt.show');

    // Refund Receipt
    Route::get('/refund-receipt/{refund}', function (App\Models\Refund $refund) {
        $refund->load([
            'sale.branch.department',
            'sale.branch.municipality',
            'sale.customer.taxDocument',
            'user',
            'items',
        ]);
        
        return view('receipts.refund-receipt', compact('refund'));
    })->name('refund-receipt.show');

    // Purchase Receipt
    Route::get('/purchase-receipt/{purchase}', function (App\Models\Purchase $purchase) {
        $purchase->load([
            'branch',
            'supplier',
            'user',
            'items.product.unit',
            'paymentMethod',
        ]);
        
        return view('receipts.purchase-receipt', compact('purchase'));
    })->name('purchase-receipt.show')->middleware('permission:purchases.view');

    // Reports
    Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
        Route::get('/products-sold', App\Livewire\Reports\ProductsSold::class)
            ->name('products-sold')
            ->middleware('permission:reports.products_sold');
        
        Route::get('/products-sold/pdf', [App\Http\Controllers\ReportExportController::class, 'productsSoldPdf'])
            ->name('products-sold.pdf')
            ->middleware('permission:reports.export');
        
        Route::get('/products-sold/excel', [App\Http\Controllers\ReportExportController::class, 'productsSoldExcel'])
            ->name('products-sold.excel')
            ->middleware('permission:reports.export');
        
        Route::get('/commissions', App\Livewire\Reports\Commissions::class)
            ->name('commissions')
            ->middleware('permission:reports.commissions');
        
        Route::get('/commissions/pdf', [App\Http\Controllers\ReportExportController::class, 'commissionsPdf'])
            ->name('commissions.pdf')
            ->middleware('permission:reports.export');

        Route::get('/kardex', App\Livewire\Reports\Kardex::class)
            ->name('kardex')
            ->middleware('permission:reports.kardex');
    });
});
