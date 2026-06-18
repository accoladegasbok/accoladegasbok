<?php

use App\Http\Controllers\PartsSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/parts', [PartsSearchController::class, 'index'])->name('parts.search');
Route::post('/parts/vin-decode', [PartsSearchController::class, 'vinDecode'])->name('parts.vin-decode');
Route::get('/parts/models', [PartsSearchController::class, 'modelsByMake'])->name('parts.models');
Route::get('/parts/search', [PartsSearchController::class, 'ajaxSearch'])->name('parts.ajax-search');