<?php

use Illuminate\Support\Facades\Route;

// ── Auto Zenith Parts — all routes ───────────────────────────────────────────

require __DIR__.'/admin.php';
require __DIR__.'/checkout.php';
require __DIR__.'/ai.php';
require __DIR__.'/parts_detail.php';

// Customer parts search (main page)
Route::get('/', function () {
    return redirect()->route('parts.search');
});

Route::get('/parts/compatibility', [\App\Http\Controllers\PartsSearchController::class, 'compatibility'])->name('parts.compatibility');

// Parts search page
require __DIR__.'/web_parts.php';