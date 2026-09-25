<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FarmRegistrationController;
use App\Http\Controllers\HarvestController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SuperAdmin\FarmController as SuperAdminFarmController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');

    // Farm registration (public sign-up)
    Route::get('/register/farm', [FarmRegistrationController::class, 'create'])->name('farm.register');
    Route::post('/register/farm', [FarmRegistrationController::class, 'store'])->middleware('throttle:register');

    // Password reset
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email')->middleware('throttle:password-reset');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update')->middleware('throttle:password-reset');
});

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    // Batches
    Route::get('/batches', [BatchController::class, 'index'])->name('batches.index');
    Route::post('/batches', [BatchController::class, 'store'])->name('batches.store');
    Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('batches.show');
    Route::put('/batches/{batch}', [BatchController::class, 'update'])->name('batches.update');
    Route::delete('/batches/{batch}', [BatchController::class, 'destroy'])->name('batches.destroy');

    // Harvest
    Route::get('/harvest', [HarvestController::class, 'index'])->name('harvest.index');
    Route::post('/harvest', [HarvestController::class, 'store'])->name('harvest.store');
    Route::put('/harvest/{harvest}', [HarvestController::class, 'update'])->name('harvest.update');
    Route::delete('/harvest/{harvest}', [HarvestController::class, 'destroy'])->name('harvest.destroy');

    // Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/{inventory}', [InventoryController::class, 'show'])->name('inventory.show');
    Route::put('/inventory/{inventory}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::post('/inventory/{inventory}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/print', [OrderController::class, 'printReceipt'])->name('orders.print');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::patch('/orders/{order}/delivery', [OrderController::class, 'updateDelivery'])->name('orders.delivery');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

    // Sales / Payments (nested under orders)
    Route::post('/orders/{order}/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::delete('/orders/{order}/sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');

    // Reports (feature-gated)
    Route::middleware('feature:reports')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    });

    // ── Farm admin area ───────────────────────────────────────────────────
    Route::middleware('admin')->group(function () {
        Route::middleware('feature:activity_logs')->group(function () {
            Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        });

        Route::middleware('feature:export')->group(function () {
            Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        });

        // Settings
        Route::get('/settings', [SettingController::class, 'index'])->name('settings');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

        // User Management
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // ── Super admin master dashboard ─────────────────────────────────────
    Route::middleware('can:super-admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/farms', [SuperAdminFarmController::class, 'index'])->name('farms.index');
        Route::get('/farms/{farm}', [SuperAdminFarmController::class, 'show'])->name('farms.show');
        Route::patch('/farms/{farm}/status', [SuperAdminFarmController::class, 'updateStatus'])->name('farms.status');
        Route::get('/farms/{farm}/features', [SuperAdminFarmController::class, 'features'])->name('farms.features');
        Route::patch('/farms/{farm}/features', [SuperAdminFarmController::class, 'updateFeatures'])->name('farms.features.update');
    });
});
