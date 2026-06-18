<?php
// FILE: routes/parts_detail.php
// Add to routes/web.php:
//   require __DIR__.'/parts_detail.php';

use App\Http\Controllers\PartController;
use Illuminate\Support\Facades\Route;

// Part detail page — must come AFTER /parts search route
// so /parts/search is not captured as an ID
Route::get('/parts/{id}', [PartController::class, 'show'])
    ->where('id', '[0-9]+')
    ->name('parts.show');
