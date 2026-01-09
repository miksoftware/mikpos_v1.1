<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\Login;

// Redirect root to login
Route::get('/', function () {
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
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard')->middleware('permission:dashboard.view');

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
});
