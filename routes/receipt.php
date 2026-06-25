<?php
// FILE: routes/receipt.php
// Add to routes/web.php:  require __DIR__.'/receipt.php';
//
// Public, no-login printable receipt — this is the link sent via
// email and WhatsApp from the admin order detail page.

use App\Http\Controllers\Admin\OrderAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/receipt/{order_ref}', [OrderAdminController::class, 'receiptPublic'])
    ->name('orders.receipt.public');
