<?php
// FILE: routes/web.php

use Illuminate\Support\Facades\Route;

// ── Root — redirect to admin ──────────────────────────────────────
Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

// ═══════════════════════════════════════════════════════════════════
// PUBLIC ROUTES — no auth required
// ═══════════════════════════════════════════════════════════════════

Route::get('/receipt/{orderRef}',
    [\App\Http\Controllers\Admin\OrderAdminController::class, 'receiptPublic'])
    ->name('orders.receipt.public');

Route::get('/manifest.json', function () {
    return response()->file(public_path('manifest.json'), ['Content-Type' => 'application/manifest+json']);
});
Route::get('/sw.js', function () {
    return response()->file(public_path('sw.js'), ['Content-Type' => 'application/javascript']);
});

Route::get('/health', fn() => response()->json(['status' => 'ok', 'ts' => now()]));

// ── Public AJAX — vehicle models dropdown ────────────────────────────
Route::get('/parts/models', [\App\Http\Controllers\Admin\HarvestController::class, 'models'])->name('parts.models');
Route::get('/parts/engine-options', [\App\Http\Controllers\Admin\HarvestController::class, 'engineOptions'])->name('parts.engine-options');

// ═══════════════════════════════════════════════════════════════════
// STAFF AUTH — login / logout (outside middleware group)
// ═══════════════════════════════════════════════════════════════════
Route::get('/admin/login',  [\App\Http\Controllers\Admin\AuthController::class, 'loginForm'])->name('admin.login');
Route::post('/admin/login', [\App\Http\Controllers\Admin\AuthController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [\App\Http\Controllers\Admin\AuthController::class, 'logout'])->name('admin.logout');

// ═══════════════════════════════════════════════════════════════════
// ADMIN — protected by admin.auth middleware
// ═══════════════════════════════════════════════════════════════════
Route::prefix('admin')->middleware(['admin.auth'])->group(function () {

    // ── Dashboard ─────────────────────────────────────────────────
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');

    // ── Profile ───────────────────────────────────────────────────
    Route::get('/profile',  [\App\Http\Controllers\Admin\ProfileController::class, 'show'])->name('admin.profile');
    Route::put('/profile',  [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('admin.profile.update');

    // ═══════════════════════════════════════════════════════════════
    // HARVEST
    // ═══════════════════════════════════════════════════════════════
    Route::get('/harvest',                    [\App\Http\Controllers\Admin\HarvestController::class, 'index'])->name('admin.harvest.index');
    Route::get('/harvest/create',             [\App\Http\Controllers\Admin\HarvestController::class, 'create'])->name('admin.harvest.create');
    Route::get('/harvest/search-donors',      [\App\Http\Controllers\Admin\HarvestController::class, 'searchDonors'])->name('admin.harvest.search-donors');
    Route::post('/harvest/vin-decode',        [\App\Http\Controllers\Admin\HarvestController::class, 'vinDecode'])->name('admin.harvest.vin-decode');
    Route::get('/harvest/engine-options',     [\App\Http\Controllers\Admin\HarvestController::class, 'engineOptions'])->name('admin.harvest.engine-options');
    Route::post('/harvest',                   [\App\Http\Controllers\Admin\HarvestController::class, 'store'])->name('admin.harvest.store');
    Route::get('/harvest/{session}/checklist',[\App\Http\Controllers\Admin\HarvestController::class, 'checklist'])->name('admin.harvest.checklist');
    Route::post('/harvest/{session}/parts',   [\App\Http\Controllers\Admin\HarvestController::class, 'saveParts'])->name('admin.harvest.saveParts');
    Route::get('/harvest/{session}/complete', [\App\Http\Controllers\Admin\HarvestController::class, 'complete'])->name('admin.harvest.complete');
    Route::get('/harvest/{session}/invoice',  [\App\Http\Controllers\Admin\InvoiceController::class, 'harvest'])->name('admin.harvest.invoice');

    // ═══════════════════════════════════════════════════════════════
    // INVENTORY
    // ═══════════════════════════════════════════════════════════════
    // Static routes BEFORE {id} parameter routes
    Route::get('/inventory',                  [\App\Http\Controllers\Admin\InventoryController::class, 'index'])->name('admin.inventory.index');
    Route::get('/inventory/manual-add',       [\App\Http\Controllers\Admin\InventoryController::class, 'manualAdd'])->name('admin.inventory.manual-add');
    Route::post('/inventory/manual-add',      [\App\Http\Controllers\Admin\InventoryController::class, 'storeManual'])->name('admin.inventory.manual-add.store');
    Route::get('/inventory/create',           [\App\Http\Controllers\Admin\InventoryController::class, 'create'])->name('admin.inventory.create');
    Route::post('/inventory',                 [\App\Http\Controllers\Admin\InventoryController::class, 'store'])->name('admin.inventory.store');
    Route::get('/inventory/barcode-label',    [\App\Http\Controllers\Admin\BarcodeController::class, 'show'])->name('admin.inventory.barcode-label');
    Route::get('/inventory/{id}/barcode',     [\App\Http\Controllers\Admin\BarcodeController::class, 'show'])->name('admin.inventory.barcode');
    Route::post('/inventory/bulk-delete',     [\App\Http\Controllers\Admin\InventoryController::class, 'bulkDelete'])->name('admin.inventory.bulk-delete');
    Route::post('/inventory/bulk-restore',    [\App\Http\Controllers\Admin\InventoryController::class, 'bulkRestore'])->name('admin.inventory.bulk-restore');
    Route::get('/inventory/{id}',             [\App\Http\Controllers\Admin\InventoryController::class, 'show'])->name('admin.inventory.show');
    Route::get('/inventory/{id}/edit',        [\App\Http\Controllers\Admin\InventoryController::class, 'edit'])->name('admin.inventory.edit');
    Route::put('/inventory/{id}',             [\App\Http\Controllers\Admin\InventoryController::class, 'update'])->name('admin.inventory.update');
    Route::delete('/inventory/{id}',          [\App\Http\Controllers\Admin\InventoryController::class, 'destroy'])->name('admin.inventory.destroy');
    Route::post('/inventory/{id}/restore',    [\App\Http\Controllers\Admin\InventoryController::class, 'restore'])->name('admin.inventory.restore');
    Route::delete('/inventory/{id}/permanent',[\App\Http\Controllers\Admin\InventoryController::class, 'permanentDelete'])->name('admin.inventory.permanent-delete');
    // Photos & video management on inventory edit page
    Route::post('/inventory/{id}/photos',        [\App\Http\Controllers\Admin\InventoryController::class, 'addPhoto'])->name('admin.inventory.photos.add');
    Route::post('/inventory/{id}/photos/delete', [\App\Http\Controllers\Admin\InventoryController::class, 'deletePhoto'])->name('admin.inventory.photos.delete');
    Route::post('/inventory/{id}/video',         [\App\Http\Controllers\Admin\InventoryController::class, 'addVideo'])->name('admin.inventory.video.add');
    Route::post('/inventory/{id}/video/delete',  [\App\Http\Controllers\Admin\InventoryController::class, 'deleteVideo'])->name('admin.inventory.video.delete');

    // ── Recycle Bin (sidebar: admin.recycle-bin.index) ─────────────
    Route::get('/recycle-bin',                [\App\Http\Controllers\Admin\RecycleBinController::class, 'index'])->name('admin.recycle-bin.index');
    Route::post('/recycle-bin/{id}/restore',  [\App\Http\Controllers\Admin\RecycleBinController::class, 'restore'])->name('admin.recycle-bin.restore');
    Route::delete('/recycle-bin/{id}',        [\App\Http\Controllers\Admin\RecycleBinController::class, 'destroy'])->name('admin.recycle-bin.destroy');

    // ── Consumables (sidebar: admin.inventory.consumable.*) ──────────
    Route::get('/consumables',                [\App\Http\Controllers\Admin\ConsumableController::class, 'index'])->name('admin.inventory.consumable.index');
    Route::get('/consumables/create',         [\App\Http\Controllers\Admin\ConsumableController::class, 'create'])->name('admin.inventory.consumable.create');
    Route::post('/consumables',               [\App\Http\Controllers\Admin\ConsumableController::class, 'store'])->name('admin.inventory.consumable.store');
    Route::get('/consumables/{id}/edit',      [\App\Http\Controllers\Admin\ConsumableController::class, 'edit'])->name('admin.inventory.consumable.edit');
    Route::put('/consumables/{id}',           [\App\Http\Controllers\Admin\ConsumableController::class, 'update'])->name('admin.inventory.consumable.update');
    Route::delete('/consumables/{id}',        [\App\Http\Controllers\Admin\ConsumableController::class, 'destroy'])->name('admin.inventory.consumable.destroy');

    // ── Compatibility Checker ─────────────────────────────────────
    Route::get('/compatibility',              [\App\Http\Controllers\Admin\CompatibilityController::class, 'index'])->name('admin.compatibility.index');
    Route::post('/compatibility/check',       [\App\Http\Controllers\Admin\CompatibilityController::class, 'check'])->name('admin.compatibility.check');
    Route::post('/compatibility/save',        [\App\Http\Controllers\Admin\CompatibilityController::class, 'save'])->name('admin.compatibility.save');
    Route::delete('/compatibility/{id}',      [\App\Http\Controllers\Admin\CompatibilityController::class, 'destroy'])->name('admin.compatibility.destroy');

    // ── Part Name Manager (sidebar: admin.part-names.*) ────────────
    Route::get('/part-names',                 [\App\Http\Controllers\Admin\PartNameManagerController::class, 'index'])->name('admin.part-names.index');
    Route::post('/part-names',                [\App\Http\Controllers\Admin\PartNameManagerController::class, 'store'])->name('admin.part-names.store');
    Route::post('/part-names/merge',          [\App\Http\Controllers\Admin\PartNameManagerController::class, 'merge'])->name('admin.part-names.merge');
    Route::post('/part-names/rename-one',     [\App\Http\Controllers\Admin\PartNameManagerController::class, 'renameOne'])->name('admin.part-names.rename-one');
    Route::put('/part-names/{id}',            [\App\Http\Controllers\Admin\PartNameManagerController::class, 'update'])->name('admin.part-names.update');
    Route::delete('/part-names/{id}',         [\App\Http\Controllers\Admin\PartNameManagerController::class, 'destroy'])->name('admin.part-names.destroy');

    // ═══════════════════════════════════════════════════════════════
    // STORAGE ROOMS & BINS
    // ═══════════════════════════════════════════════════════════════
    Route::get('/storage',                        [\App\Http\Controllers\Admin\StorageController::class, 'index'])->name('admin.storage.index');
    Route::post('/storage',                       [\App\Http\Controllers\Admin\StorageController::class, 'store'])->name('admin.storage.store');
    Route::get('/storage/bin-labels',             [\App\Http\Controllers\Admin\BinLabelController::class, 'batch'])->name('admin.storage.bin-labels');
    Route::get('/storage/bin-label/{shelfId}',    [\App\Http\Controllers\Admin\BinLabelController::class, 'single'])->name('admin.storage.bin-label');
    Route::get('/storage/room-labels/{roomId}',   [\App\Http\Controllers\Admin\BinLabelController::class, 'room'])->name('admin.storage.room-labels');
    Route::get('/storage/{id}',                   [\App\Http\Controllers\Admin\StorageController::class, 'show'])->name('admin.storage.show');
    Route::post('/storage/rooms',                 [\App\Http\Controllers\Admin\StorageController::class, 'storeRoom'])->name('admin.storage.rooms.store');
    Route::put('/storage/rooms/{id}',             [\App\Http\Controllers\Admin\StorageController::class, 'updateRoom'])->name('admin.storage.rooms.update');
    Route::delete('/storage/rooms/{id}',          [\App\Http\Controllers\Admin\StorageController::class, 'destroyRoom'])->name('admin.storage.rooms.destroy');
    Route::post('/storage/shelves',               [\App\Http\Controllers\Admin\StorageController::class, 'storeShelf'])->name('admin.storage.shelves.store');
    Route::put('/storage/shelves/{id}',           [\App\Http\Controllers\Admin\StorageController::class, 'updateShelf'])->name('admin.storage.shelves.update');
    Route::delete('/storage/shelves/{id}',        [\App\Http\Controllers\Admin\StorageController::class, 'destroyShelf'])->name('admin.storage.shelves.destroy');
    Route::get('/storage/all-bins-for-location',  [\App\Http\Controllers\Admin\StorageController::class, 'allBinsForLocation'])->name('admin.storage.all-bins-for-location');
    Route::get('/storage/available-bins',         [\App\Http\Controllers\Admin\StorageController::class, 'availableBins'])->name('admin.storage.available-bins');

    // ═══════════════════════════════════════════════════════════════
    // STOCK TRANSFERS
    // ═══════════════════════════════════════════════════════════════
    Route::get('/transfers',              [\App\Http\Controllers\Admin\StockTransferController::class, 'index'])->name('admin.transfers.index');
    Route::get('/transfers/create',       [\App\Http\Controllers\Admin\StockTransferController::class, 'create'])->name('admin.transfers.create');
    Route::post('/transfers',             [\App\Http\Controllers\Admin\StockTransferController::class, 'store'])->name('admin.transfers.store');
    Route::get('/transfers/{id}',         [\App\Http\Controllers\Admin\StockTransferController::class, 'show'])->name('admin.transfers.show');
    Route::post('/transfers/{id}/approve',[\App\Http\Controllers\Admin\StockTransferController::class, 'approve'])->name('admin.transfers.approve');
    Route::post('/transfers/{id}/reject', [\App\Http\Controllers\Admin\StockTransferController::class, 'reject'])->name('admin.transfers.reject');

    // ═══════════════════════════════════════════════════════════════
    // POS / CHECKOUT
    // ═══════════════════════════════════════════════════════════════
    Route::get('/pos',              [\App\Http\Controllers\Admin\PosController::class, 'index'])->name('admin.pos.index');
    Route::post('/pos/checkout',    [\App\Http\Controllers\Admin\PosController::class, 'checkout'])->name('admin.pos.checkout');
    Route::get('/pos/search',       [\App\Http\Controllers\Admin\PosController::class, 'search'])->name('admin.pos.search');
    Route::get('/pos/lookup',       [\App\Http\Controllers\Admin\PosController::class, 'lookup'])->name('admin.pos.lookup');
    Route::get('/pos/part/{id}',    [\App\Http\Controllers\Admin\PosController::class, 'partDetail'])->name('admin.pos.part-detail');

    // ═══════════════════════════════════════════════════════════════
    // OPEN TABS
    // ═══════════════════════════════════════════════════════════════
    Route::get('/tabs',                       [\App\Http\Controllers\Admin\OpenTabController::class, 'index'])->name('admin.tabs.index');
    Route::get('/tabs/create',                [\App\Http\Controllers\Admin\OpenTabController::class, 'create'])->name('admin.tabs.create');
    Route::post('/tabs',                      [\App\Http\Controllers\Admin\OpenTabController::class, 'store'])->name('admin.tabs.store');
    Route::get('/tabs/{id}',                  [\App\Http\Controllers\Admin\OpenTabController::class, 'show'])->name('admin.tabs.show');
    Route::post('/tabs/{id}/add-item',        [\App\Http\Controllers\Admin\OpenTabController::class, 'addItem'])->name('admin.tabs.add-item');
    Route::delete('/tabs/{id}/items/{itemId}',[\App\Http\Controllers\Admin\OpenTabController::class, 'removeItem'])->name('admin.tabs.remove-item');
    Route::post('/tabs/{id}/checkout',        [\App\Http\Controllers\Admin\OpenTabController::class, 'checkout'])->name('admin.tabs.checkout');
    Route::post('/tabs/{id}/close',           [\App\Http\Controllers\Admin\OpenTabController::class, 'close'])->name('admin.tabs.close');

    // ═══════════════════════════════════════════════════════════════
    // ORDERS
    // ═══════════════════════════════════════════════════════════════
    Route::get('/orders/place/create',            [\App\Http\Controllers\Admin\AdminOrderController::class, 'create'])->name('admin.orders.place.create');
    Route::post('/orders/place',                  [\App\Http\Controllers\Admin\AdminOrderController::class, 'store'])->name('admin.orders.place.store');
    Route::get('/orders/place/search-parts',      [\App\Http\Controllers\Admin\AdminOrderController::class, 'searchParts'])->name('admin.orders.place.search-parts');
    Route::get('/orders',                         [\App\Http\Controllers\Admin\OrderAdminController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/{id}',                    [\App\Http\Controllers\Admin\OrderAdminController::class, 'show'])->name('admin.orders.show');
    Route::get('/orders/{id}/edit',               [\App\Http\Controllers\Admin\OrderAdminController::class, 'edit'])->name('admin.orders.edit');
    Route::put('/orders/{id}',                    [\App\Http\Controllers\Admin\OrderAdminController::class, 'update'])->name('admin.orders.update');
    Route::get('/orders/{id}/print',              [\App\Http\Controllers\Admin\OrderAdminController::class, 'printAdmin'])->name('admin.orders.print');
    Route::get('/orders/{id}/invoice',            [\App\Http\Controllers\Admin\InvoiceController::class, 'show'])->name('admin.invoices.show');
    Route::post('/orders/{id}/email-receipt',     [\App\Http\Controllers\Admin\OrderAdminController::class, 'emailReceipt'])->name('admin.orders.email-receipt');
    Route::post('/orders/{id}/confirm-payment',   [\App\Http\Controllers\Admin\OrderAdminController::class, 'confirmPayment'])->name('admin.orders.confirm-payment');
    Route::post('/orders/{id}/status',            [\App\Http\Controllers\Admin\OrderAdminController::class, 'updateStatus'])->name('admin.orders.update-status');
    Route::post('/orders/{id}/send-reminder',     [\App\Http\Controllers\Admin\OrderAdminController::class, 'sendReminder'])->name('admin.orders.send-reminder');
    Route::post('/orders/{id}/payments',          [\App\Http\Controllers\Admin\OrderAdminController::class, 'addPayment'])->name('admin.orders.payments.add');
    Route::post('/orders/{id}/payments/{pid}/confirm', [\App\Http\Controllers\Admin\OrderAdminController::class, 'confirmOrderPayment'])->name('admin.orders.payments.confirm');
    Route::post('/orders/{id}/payments/{pid}/reject',  [\App\Http\Controllers\Admin\OrderAdminController::class, 'rejectOrderPayment'])->name('admin.orders.payments.reject');
    Route::delete('/orders/{id}',                 [\App\Http\Controllers\Admin\OrderAdminController::class, 'destroy'])->name('admin.orders.destroy');

    // ═══════════════════════════════════════════════════════════════
    // INVOICES
    // ═══════════════════════════════════════════════════════════════
    Route::get('/invoices',                           [\App\Http\Controllers\Admin\InvoiceController::class, 'index'])->name('admin.invoices.index');
    Route::get('/invoices/manual/create',             [\App\Http\Controllers\Admin\InvoiceController::class, 'createManual'])->name('admin.invoices.manual.create');
    Route::post('/invoices/manual',                   [\App\Http\Controllers\Admin\InvoiceController::class, 'storeManual'])->name('admin.invoices.manual.store');
    Route::get('/invoices/manual/{id}',               [\App\Http\Controllers\Admin\InvoiceController::class, 'showManual'])->name('admin.invoices.show.manual');
    Route::get('/invoices/manual/{id}/edit',          [\App\Http\Controllers\Admin\InvoiceController::class, 'editManual'])->name('admin.invoices.manual.edit');
    Route::put('/invoices/manual/{id}',               [\App\Http\Controllers\Admin\InvoiceController::class, 'updateManual'])->name('admin.invoices.manual.update');
    Route::get('/invoices/service/create',            [\App\Http\Controllers\Admin\InvoiceController::class, 'createService'])->name('admin.invoices.service.create');
    Route::post('/invoices/service',                  [\App\Http\Controllers\Admin\InvoiceController::class, 'storeService'])->name('admin.invoices.service.store');
    Route::get('/invoices/service/search-parts',      [\App\Http\Controllers\Admin\InvoiceController::class, 'serviceSearchParts'])->name('admin.invoices.service.search-parts');
    Route::get('/invoices/car-sale/create',           [\App\Http\Controllers\Admin\InvoiceController::class, 'createCarSale'])->name('admin.invoices.car-sale.create');
    Route::post('/invoices/car-sale',                 [\App\Http\Controllers\Admin\InvoiceController::class, 'storeCarSale'])->name('admin.invoices.car-sale.store');
    Route::post('/invoices/{id}/payments',            [\App\Http\Controllers\Admin\InvoiceController::class, 'addPayment'])->name('admin.invoices.payments.add');
    Route::post('/invoices/{id}/payments/{pid}/confirm', [\App\Http\Controllers\Admin\InvoiceController::class, 'confirmPayment'])->name('admin.invoices.payments.confirm');
    Route::post('/invoices/{id}/payments/{pid}/reject',  [\App\Http\Controllers\Admin\InvoiceController::class, 'rejectPayment'])->name('admin.invoices.payments.reject');
    Route::post('/invoices/{id}/send-reminder',       [\App\Http\Controllers\Admin\InvoiceController::class, 'sendReminder'])->name('admin.invoices.send-reminder');
    Route::delete('/invoices/{id}',                   [\App\Http\Controllers\Admin\InvoiceController::class, 'destroy'])->name('admin.invoices.destroy');
    Route::post('/invoices/bulk-destroy',             [\App\Http\Controllers\Admin\InvoiceController::class, 'bulkDestroy'])->name('admin.invoices.bulk-destroy');

    // ═══════════════════════════════════════════════════════════════
    // CUSTOMERS (sidebar: admin.customers.*)
    // ═══════════════════════════════════════════════════════════════
    Route::get('/customers',              [\App\Http\Controllers\Admin\CustomerController::class, 'index'])->name('admin.customers.index');
    Route::get('/customers/create',       [\App\Http\Controllers\Admin\CustomerController::class, 'create'])->name('admin.customers.contacts.create');
    Route::post('/customers',             [\App\Http\Controllers\Admin\CustomerController::class, 'store'])->name('admin.customers.store');
    Route::get('/customers/{id}',         [\App\Http\Controllers\Admin\CustomerController::class, 'show'])->name('admin.customers.show');
    Route::get('/customers/{id}/edit',    [\App\Http\Controllers\Admin\CustomerController::class, 'edit'])->name('admin.customers.contacts.edit');
    Route::put('/customers/{id}',         [\App\Http\Controllers\Admin\CustomerController::class, 'update'])->name('admin.customers.update');
    Route::delete('/customers/{id}',      [\App\Http\Controllers\Admin\CustomerController::class, 'destroy'])->name('admin.customers.destroy');
    // Legacy aliases
    Route::get('/contacts',               [\App\Http\Controllers\Admin\CustomerController::class, 'index'])->name('admin.contacts.index');
    Route::get('/customers/create',       [\App\Http\Controllers\Admin\CustomerController::class, 'create'])->name('admin.customers.create');

    // ── Customers lookup (AJAX) ───────────────────────────────────
    Route::get('/customers/lookup',       [\App\Http\Controllers\Admin\CustomerController::class, 'lookup'])->name('admin.customers.lookup');

    // ═══════════════════════════════════════════════════════════════
    // PAYMENTS LEDGER (sidebar: admin.payments.*)
    // ═══════════════════════════════════════════════════════════════
    Route::get('/payments',               [\App\Http\Controllers\Admin\PaymentsLedgerController::class, 'index'])->name('admin.payments.index');

    // ═══════════════════════════════════════════════════════════════
    // STAFF MANAGEMENT
    // ═══════════════════════════════════════════════════════════════
    Route::get('/staff',                  [\App\Http\Controllers\Admin\StaffController::class, 'index'])->name('admin.staff.index');
    Route::get('/staff/create',           [\App\Http\Controllers\Admin\StaffController::class, 'create'])->name('admin.staff.create');
    Route::post('/staff',                 [\App\Http\Controllers\Admin\StaffController::class, 'store'])->name('admin.staff.store');
    Route::get('/staff/{id}/edit',        [\App\Http\Controllers\Admin\StaffController::class, 'edit'])->name('admin.staff.edit');
    Route::put('/staff/{id}',             [\App\Http\Controllers\Admin\StaffController::class, 'update'])->name('admin.staff.update');
    Route::delete('/staff/{id}',          [\App\Http\Controllers\Admin\StaffController::class, 'destroy'])->name('admin.staff.destroy');
    Route::post('/staff/{id}/reset-pin',  [\App\Http\Controllers\Admin\StaffController::class, 'resetPin'])->name('admin.staff.reset-pin');

    // Staff tickets (sidebar: admin.tickets.*)
    Route::get('/tickets',                [\App\Http\Controllers\Admin\StaffTicketController::class, 'index'])->name('admin.tickets.index');
    Route::post('/tickets',               [\App\Http\Controllers\Admin\StaffTicketController::class, 'store'])->name('admin.tickets.store');
    Route::post('/tickets/{id}/resolve',  [\App\Http\Controllers\Admin\StaffTicketController::class, 'resolve'])->name('admin.tickets.resolve');
    // Legacy aliases
    Route::get('/staff/tickets',          [\App\Http\Controllers\Admin\StaffTicketController::class, 'index'])->name('admin.staff.tickets.index');

    // ── Supervisor PIN override ────────────────────────────────────
    Route::post('/override/verify',           [\App\Http\Controllers\Admin\OverrideController::class, 'verify'])->name('admin.override.verify');
    Route::get('/override/set-pin',           [\App\Http\Controllers\Admin\OverrideController::class, 'setOwnPinPage'])->name('admin.override.set-pin-page');
    Route::post('/override/set-pin',          [\App\Http\Controllers\Admin\OverrideController::class, 'setOwnPin'])->name('admin.override.set-pin');
    Route::post('/override/clear-pin/{staffId}', [\App\Http\Controllers\Admin\OverrideController::class, 'clearPin'])->name('admin.override.clear-pin');
    Route::get('/override/logs',              [\App\Http\Controllers\Admin\OverrideController::class, 'logs'])->name('admin.override.logs');

    // ═══════════════════════════════════════════════════════════════
    // ASSETS
    // ═══════════════════════════════════════════════════════════════
    Route::get('/assets',             [\App\Http\Controllers\Admin\AssetController::class, 'index'])->name('admin.assets.index');
    Route::get('/assets/create',      [\App\Http\Controllers\Admin\AssetController::class, 'create'])->name('admin.assets.create');
    Route::post('/assets',            [\App\Http\Controllers\Admin\AssetController::class, 'store'])->name('admin.assets.store');
    Route::get('/assets/{id}',        [\App\Http\Controllers\Admin\AssetController::class, 'show'])->name('admin.assets.show');
    Route::get('/assets/{id}/edit',   [\App\Http\Controllers\Admin\AssetController::class, 'edit'])->name('admin.assets.edit');
    Route::put('/assets/{id}',        [\App\Http\Controllers\Admin\AssetController::class, 'update'])->name('admin.assets.update');
    Route::delete('/assets/{id}',     [\App\Http\Controllers\Admin\AssetController::class, 'destroy'])->name('admin.assets.destroy');
    Route::post('/assets/{id}/log',   [\App\Http\Controllers\Admin\AssetController::class, 'log'])->name('admin.assets.log');

    // ═══════════════════════════════════════════════════════════════
    // INVENTORY AUDIT
    // ═══════════════════════════════════════════════════════════════
    Route::get('/audit',                  [\App\Http\Controllers\Admin\AuditController::class, 'index'])->name('admin.audit.index');
    Route::get('/audit/create',           [\App\Http\Controllers\Admin\AuditController::class, 'create'])->name('admin.audit.create');
    Route::post('/audit',                 [\App\Http\Controllers\Admin\AuditController::class, 'store'])->name('admin.audit.store');
    Route::get('/audit/{id}',             [\App\Http\Controllers\Admin\AuditController::class, 'show'])->name('admin.audit.show');
    Route::post('/audit/{id}/items',      [\App\Http\Controllers\Admin\AuditController::class, 'saveItems'])->name('admin.audit.save-items');
    Route::post('/audit/{id}/complete',   [\App\Http\Controllers\Admin\AuditController::class, 'complete'])->name('admin.audit.complete');

    // ═══════════════════════════════════════════════════════════════
    // RETURNS
    // ═══════════════════════════════════════════════════════════════
    Route::get('/returns',                [\App\Http\Controllers\Admin\ReturnsController::class, 'index'])->name('admin.returns.index');
    Route::get('/returns/create',         [\App\Http\Controllers\Admin\ReturnsController::class, 'create'])->name('admin.returns.create');
    Route::post('/returns',               [\App\Http\Controllers\Admin\ReturnsController::class, 'store'])->name('admin.returns.store');
    Route::get('/returns/{id}',           [\App\Http\Controllers\Admin\ReturnsController::class, 'show'])->name('admin.returns.show');
    Route::post('/returns/{id}/approve',  [\App\Http\Controllers\Admin\ReturnsController::class, 'approve'])->name('admin.returns.approve');
    Route::post('/returns/{id}/reject',   [\App\Http\Controllers\Admin\ReturnsController::class, 'reject'])->name('admin.returns.reject');

    // ═══════════════════════════════════════════════════════════════
    // SERVICE RATES
    // ═══════════════════════════════════════════════════════════════
    Route::get('/service-rates',          [\App\Http\Controllers\Admin\ServiceRateController::class, 'index'])->name('admin.service-rates.index');
    Route::post('/service-rates',         [\App\Http\Controllers\Admin\ServiceRateController::class, 'store'])->name('admin.service-rates.store');
    Route::put('/service-rates/{id}',     [\App\Http\Controllers\Admin\ServiceRateController::class, 'update'])->name('admin.service-rates.update');
    Route::delete('/service-rates/{id}',  [\App\Http\Controllers\Admin\ServiceRateController::class, 'destroy'])->name('admin.service-rates.destroy');

    // ═══════════════════════════════════════════════════════════════
    // VEHICLE ROI — Phase 4
    // Static routes BEFORE {id} parameter routes
    // ═══════════════════════════════════════════════════════════════
    Route::get('/vehicles/roi-summary',   [\App\Http\Controllers\Admin\VehicleROIController::class, 'summary'])->name('admin.vehicles.roi-summary');
    Route::get('/vehicles/{id}/roi',      [\App\Http\Controllers\Admin\VehicleROIController::class, 'show'])->name('admin.vehicles.roi');
    Route::get('/vehicles/{id}',          [\App\Http\Controllers\Admin\VehicleDetailController::class, 'show'])->name('admin.vehicles.show');

    // ═══════════════════════════════════════════════════════════════
    // INTERCHANGE GROUPS
    // ═══════════════════════════════════════════════════════════════
    Route::get('/interchange',                        [\App\Http\Controllers\Admin\InterchangeController::class, 'index'])->name('admin.interchange.index');
    Route::post('/interchange',                       [\App\Http\Controllers\Admin\InterchangeController::class, 'store'])->name('admin.interchange.store');
    Route::post('/interchange/groups/create',         [\App\Http\Controllers\Admin\InterchangeController::class, 'createGroup'])->name('admin.interchange.groups.create');
    Route::post('/interchange/promote-heuristic',     [\App\Http\Controllers\Admin\InterchangeController::class, 'promoteHeuristic'])->name('admin.interchange.promote-heuristic');
    Route::post('/interchange/ai-suggest',            [\App\Http\Controllers\Admin\InterchangeAiController::class, 'suggest'])->name('admin.interchange.ai-suggest');
    Route::get('/interchange/{id}',                   [\App\Http\Controllers\Admin\InterchangeController::class, 'show'])->name('admin.interchange.show');
    Route::put('/interchange/{id}',                   [\App\Http\Controllers\Admin\InterchangeController::class, 'update'])->name('admin.interchange.update');
    Route::delete('/interchange/{id}',                [\App\Http\Controllers\Admin\InterchangeController::class, 'destroy'])->name('admin.interchange.destroy');
    Route::post('/interchange/{id}/parts',            [\App\Http\Controllers\Admin\InterchangeController::class, 'addPart'])->name('admin.interchange.add-part');
    Route::post('/interchange/{id}/vehicles',         [\App\Http\Controllers\Admin\InterchangeController::class, 'addVehicle'])->name('admin.interchange.groups.add-vehicle');
    Route::delete('/interchange/{id}/parts/{partId}', [\App\Http\Controllers\Admin\InterchangeController::class, 'removePart'])->name('admin.interchange.parts.remove');

    // ═══════════════════════════════════════════════════════════════
    // REPORTS — Phase 7
    // ═══════════════════════════════════════════════════════════════
    Route::get('/reports/financial',        [\App\Http\Controllers\Admin\FinancialReportController::class, 'index'])->name('admin.reports.financial');
    Route::get('/reports/financial/export', [\App\Http\Controllers\Admin\FinancialReportController::class, 'export'])->name('admin.reports.financial.export');
    Route::get('/reports/inventory',        [\App\Http\Controllers\Admin\ReportController::class, 'inventory'])->name('admin.reports.inventory');
    Route::get('/reports/staff',            [\App\Http\Controllers\Admin\ReportController::class, 'staff'])->name('admin.reports.staff');
    Route::get('/reports/vehicles',         [\App\Http\Controllers\Admin\ReportController::class, 'vehicles'])->name('admin.reports.vehicles');

}); // end admin middleware group

// ═══════════════════════════════════════════════════════════════════
// FALLBACK
// ═══════════════════════════════════════════════════════════════════
Route::fallback(function () {
    if (request()->is('admin/*')) {
        return redirect()->route('admin.login');
    }
    abort(404);
});
