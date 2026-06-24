<?php
// FILE: routes/checkout.php
// Add this to your routes/web.php:  require __DIR__.'/checkout.php';

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

// ── Cart ─────────────────────────────────────────────────────────────────────
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/',         [CartController::class, 'index'])->name('index');
    Route::post('/add',     [CartController::class, 'add'])->name('add');
    Route::post('/remove',  [CartController::class, 'remove'])->name('remove');
    Route::get('/count',    [CartController::class, 'count'])->name('count');
});

// ── Checkout ─────────────────────────────────────────────────────────────────
Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/',                         [CheckoutController::class, 'index'])->name('index');
    Route::post('/',                        [CheckoutController::class, 'store'])->name('store');
    Route::get('/confirmation/{ref}',       [CheckoutController::class, 'confirmation'])->name('confirmation');
    Route::post('/transfer-proof',          [CheckoutController::class, 'submitTransferProof'])->name('transfer-proof');
});

// ── REMOVED: duplicate "admin/orders" block that used to live here ──────────
// It pointed to CheckoutController::adminConfirmPayment / adminCancelOrder
// and used a 'role' middleware that was never registered as an alias,
// which crashed with "Target class [role] does not exist." every time it
// ran. The real, working admin order routes (confirm-payment, cancel,
// status) already exist in routes/admin.php under OrderAdminController,
// protected by the proper 'admin.auth' middleware — this block was a
// leftover duplicate competing with those and is no longer needed here.
