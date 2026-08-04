<?php

use App\Http\Controllers\PartsSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/parts', [PartsSearchController::class, 'index'])->name('parts.search');
Route::post('/parts/vin-decode', [PartsSearchController::class, 'vinDecode'])->name('parts.vin-decode');
Route::get('/parts/models', [PartsSearchController::class, 'modelsByMake'])->name('parts.models');
Route::get('/parts/search', [PartsSearchController::class, 'ajaxSearch'])->name('parts.ajax-search');

// NEW: Consumables/Electronics/Computers/Other — grouped separately
// from automobile parts, since /parts should read as a used-auto-
// parts site, not a general goods marketplace.
Route::get('/other-items', [PartsSearchController::class, 'otherItems'])->name('other-items');