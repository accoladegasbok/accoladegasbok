<?php
// FILE: routes/admin.php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HarvestController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\FinancialReportController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\InvoiceController;

// ── Auth (public) ─────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/login',  [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

    // ── Protected admin routes ────────────────────────────────────────────────
    Route::middleware('admin.auth')->group(function () {

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Harvesting
        Route::prefix('harvest')->name('harvest.')->group(function () {
            Route::get('/',                     [HarvestController::class, 'index'])->name('index');
            Route::get('/create',               [HarvestController::class, 'create'])->name('create');
            Route::post('/vin-decode',          [HarvestController::class, 'vinDecode'])->name('vin-decode');
            Route::post('/',                    [HarvestController::class, 'store'])->name('store');
            Route::get('/{session}/checklist',  [HarvestController::class, 'checklist'])->name('checklist');
            Route::post('/{session}/parts',     [HarvestController::class, 'saveParts'])->name('saveParts');
            Route::get('/{session}/complete',   [HarvestController::class, 'complete'])->name('complete');
        });

        // Inventory management
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/',             [InventoryController::class, 'index'])->name('index');
            Route::get('/create',       [InventoryController::class, 'create'])->name('create');
            Route::get('/manual-add', [InventoryController::class, 'manualAdd'])->name('manual-add');
            Route::get('/consumable/create', [InventoryController::class, 'consumableCreate'])->name('consumable.create');
            Route::post('/consumable',       [InventoryController::class, 'consumableStore'])->name('consumable.store');
            Route::post('/',            [InventoryController::class, 'store'])->name('store');
            Route::get('/{id}/edit',    [InventoryController::class, 'edit'])->name('edit');
            Route::put('/{id}',         [InventoryController::class, 'update'])->name('update');
            Route::post('/{id}/status', [InventoryController::class, 'updateStatus'])->name('status');
            Route::delete('/{id}',      [InventoryController::class, 'destroy'])->name('destroy');
            
        });

        // Orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/',                      [OrderAdminController::class, 'index'])->name('index');
            Route::get('/{id}',                  [OrderAdminController::class, 'show'])->name('show');
            Route::post('/{id}/confirm-payment', [OrderAdminController::class, 'confirmPayment'])->name('confirm-payment');
            Route::post('/{id}/cancel',          [OrderAdminController::class, 'cancel'])->name('cancel');
            Route::post('/{id}/status',          [OrderAdminController::class, 'updateStatus'])->name('status');
        });

        // Customers (auto-aggregated from orders + invoices)
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/',        [CustomerController::class, 'index'])->name('index');
            Route::get('/{phone}', [CustomerController::class, 'show'])->name('show');
        });
        // Inventory audit sessions
        Route::prefix('audit')->name('audit.')->group(function () {
            Route::get('/',                 [AuditController::class, 'index'])->name('index');
            Route::get('/create',           [AuditController::class, 'create'])->name('create');
            Route::post('/',                [AuditController::class, 'store'])->name('store');
            Route::get('/{id}',             [AuditController::class, 'show'])->name('show');
            Route::post('/{id}/count',      [AuditController::class, 'recordCount'])->name('count');
            Route::post('/{id}/complete',   [AuditController::class, 'complete'])->name('complete');
        });
        // Financial reporting
        Route::get('/reports/financial', [FinancialReportController::class, 'index'])->name('reports.financial');
        // Staff management (admin/manager only)
        Route::middleware('admin.auth:admin,manager')
            ->prefix('staff')->name('staff.')->group(function () {
            Route::get('/',              [StaffController::class, 'index'])->name('index');
            Route::get('/create',        [StaffController::class, 'create'])->name('create');
            Route::post('/',             [StaffController::class, 'store'])->name('store');
            Route::get('/{id}/edit',     [StaffController::class, 'edit'])->name('edit');
            Route::put('/{id}',          [StaffController::class, 'update'])->name('update');
            Route::post('/{id}/toggle',  [StaffController::class, 'toggle'])->name('toggle');
       });

        // Invoices
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [InvoiceController::class, 'index'])->name('index');
            Route::get('/manual/create', [InvoiceController::class, 'createManual'])->name('manual.create');
            Route::post('/manual', [InvoiceController::class, 'storeManual'])->name('manual.store');
            Route::get('/manual/{id}', [InvoiceController::class, 'showManual'])->name('show.manual');
            Route::get('/manual/{id}/edit', [InvoiceController::class, 'editManual'])->name('manual.edit');
            Route::put('/manual/{id}', [InvoiceController::class, 'updateManual'])->name('manual.update');
            Route::get('/order/{id}', [InvoiceController::class, 'show'])->name('show');
        });
    }); // end admin.auth middleware

}); // end admin prefix
