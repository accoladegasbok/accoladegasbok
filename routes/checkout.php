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

// ── Admin order management ────────────────────────────────────────────────────
Route::prefix('admin/orders')->middleware(['auth', 'role:admin,staff'])->name('admin.orders.')->group(function () {
    Route::post('/{id}/confirm-payment',    [CheckoutController::class, 'adminConfirmPayment'])->name('confirm-payment');
    Route::post('/{id}/cancel',             [CheckoutController::class, 'adminCancelOrder'])->name('cancel');
});
