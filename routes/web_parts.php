<?php

use App\Http\Controllers\PartsSearchController;
use App\Http\Controllers\PartRequestController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\Admin\SubscriberAdminController;
use App\Http\Controllers\Admin\PartRequestAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/parts', [PartsSearchController::class, 'index'])->name('parts.search');
Route::post('/parts/vin-decode', [PartsSearchController::class, 'vinDecode'])->name('parts.vin-decode');
Route::get('/parts/models', [PartsSearchController::class, 'modelsByMake'])->name('parts.models');
Route::get('/parts/search', [PartsSearchController::class, 'ajaxSearch'])->name('parts.ajax-search');

// NEW: Consumables/Electronics/Computers/Other — grouped separately
// from automobile parts, since /parts should read as a used-auto-
// parts site, not a general goods marketplace.
Route::get('/other-items', [PartsSearchController::class, 'otherItems'])->name('other-items');

// NEW: "Request this part" — a customer who cannot find a part (or looked at a sold one) leaves
// their details. Limited to 6 submissions per 10 minutes per visitor to stop spam.
Route::post('/parts/request', [PartRequestController::class, 'store'])
    ->middleware('throttle:6,10')
    ->name('parts.request.store');

// NEW: the staff inbox for those requests. Staff login required (same guards as the rest of /admin).
Route::middleware(['admin.auth', 'stocking-clerk'])->prefix('admin/part-requests')->name('admin.part-requests.')->group(function () {
    Route::get('/',      [PartRequestAdminController::class, 'index'])->name('index');
    Route::post('/{id}', [PartRequestAdminController::class, 'update'])->name('update')->whereNumber('id');
});

// NEW: "Subscribe to our emails" (footer box) and the one-click unsubscribe link.
Route::post('/subscribe', [SubscribeController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('subscribe.store');
Route::get('/unsubscribe/{token}', [SubscribeController::class, 'unsubscribe'])->name('subscribe.unsubscribe');

// NEW: staff view of the email list + CSV download (the page itself limits it to supervisor and above).
Route::middleware(['admin.auth', 'stocking-clerk'])->prefix('admin/subscribers')->name('admin.subscribers.')->group(function () {
    Route::get('/',       [SubscriberAdminController::class, 'index'])->name('index');
    Route::get('/export', [SubscriberAdminController::class, 'export'])->name('export');
});
