<?php

use App\Http\Controllers\PartsSearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auto Zenith Parts — Customer Search Routes
|--------------------------------------------------------------------------
*/

// Main parts search page
Route::get('/parts', [PartsSearchController::class, 'index'])->name('parts.search');

// VIN decode (AJAX — called by the VIN input box)
Route::post('/parts/vin-decode', [PartsSearchController::class, 'vinDecode'])->name('parts.vin-decode');

// Models dropdown (AJAX — populates when Make is selected)
Route::get('/parts/models', [PartsSearchController::class, 'modelsByMake'])->name('parts.models');

// AJAX search endpoint (live filtering without page reload)
Route::get('/parts/search', [PartsSearchController::class, 'ajaxSearch'])->name('parts.ajax-search');

// Part detail page (to be built in Phase 2)
Route::get('/parts/{id}', [PartsSearchController::class, 'show'])->name('parts.show');

// Homepage redirect
Route::get('/', fn() => redirect()->route('parts.search'));
