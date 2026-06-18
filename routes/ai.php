<?php

use App\Http\Controllers\AiSearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auto Zenith — AI Search Routes
|--------------------------------------------------------------------------
| Add these to your existing routes/web.php or routes/api.php
|
| If using web.php (with CSRF):  use Route::post()  — CSRF token required
| If using api.php (stateless):  use Route::post()  — use Bearer token auth
|
| Recommended: use web.php for the customer website (CSRF protected)
|--------------------------------------------------------------------------
*/

Route::prefix('ai')->middleware(['web', 'throttle:60,1'])->group(function () {

    // Natural language parts search
    // POST /ai/search
    // Body: { "q": "left tail light 2019 Toyota Camry", "currency": "USD" }
    Route::post('/search', [AiSearchController::class, 'search'])
        ->name('ai.search');

    // Customer chatbot
    // POST /ai/chat
    // Body: { "message": "...", "history": [...], "page_context": {...} }
    Route::post('/chat', [AiSearchController::class, 'chat'])
        ->name('ai.chat');

    // Autocomplete suggestions (no Claude — pure DB)
    // GET /ai/suggest?q=tail+lamp
    Route::get('/suggest', [AiSearchController::class, 'suggest'])
        ->name('ai.suggest');

});
