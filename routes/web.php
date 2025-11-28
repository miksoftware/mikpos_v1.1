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
});
