<?php
// FILE: routes/admin.php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HarvestController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\FinancialReportController;
use App\Http\Controllers\Admin\RecycleBinController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\InvoiceController;

// ── Auth (public) ─────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/login',  [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

    // ── Protected admin routes ────────────────────────────────────────────────
    Route::middleware(['admin.auth', 'stocking-clerk'])->group(function () {

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Harvesting
        Route::prefix('harvest')->name('harvest.')->group(function () {
            Route::get('/',                     [HarvestController::class, 'index'])->name('index');
            Route::get('/create',               [HarvestController::class, 'create'])->name('create');
            Route::get('/search-donors',        [HarvestController::class, 'searchDonors'])->name('search-donors');
            Route::get('/engine-options',       [HarvestController::class, 'engineOptions'])->name('engine-options');
            Route::post('/vin-decode',          [HarvestController::class, 'vinDecode'])->name('vin-decode');
            Route::post('/',                    [HarvestController::class, 'store'])->name('store');
            Route::get('/{session}/checklist',  [HarvestController::class, 'checklist'])->name('checklist');
            Route::post('/{session}/parts',     [HarvestController::class, 'saveParts'])->name('saveParts');
            Route::get('/{session}/complete',   [HarvestController::class, 'complete'])->name('complete');
        });

        // Inventory management
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/',             [InventoryController::class, 'index'])->name('index');
            Route::get('/create',       [InventoryController::class, 'create'])->name('create');
            Route::get('/manual-add', [InventoryController::class, 'manualAdd'])->name('manual-add');
            Route::get('/oem-lookup', [InventoryController::class, 'oemLookup'])->name('oem-lookup');
            Route::get('/backfill-drive-type', [InventoryController::class, 'backfillDriveTypeForm'])->name('backfill-drive-type');
            Route::post('/backfill-drive-type', [InventoryController::class, 'backfillDriveTypeSave'])->name('backfill-drive-type.save');
            Route::get('/consumable/create', [InventoryController::class, 'consumableCreate'])->name('consumable.create');
            Route::post('/consumable',       [InventoryController::class, 'consumableStore'])->name('consumable.store');
            Route::post('/',            [InventoryController::class, 'store'])->name('store');
            Route::get('/{id}/barcode', [InventoryController::class, 'barcode'])->name('barcode');
            Route::get('/{id}/edit',    [InventoryController::class, 'edit'])->name('edit');
            Route::put('/{id}',         [InventoryController::class, 'update'])->name('update');
            Route::post('/{id}/status', [InventoryController::class, 'updateStatus'])->name('status');
            Route::post('/{id}/photos', [InventoryController::class, 'addPhotos'])->name('photos.add');
            Route::post('/{id}/photos/delete', [InventoryController::class, 'deletePhoto'])->name('photos.delete');
            Route::post('/{id}/video', [InventoryController::class, 'addVideo'])->name('video.add');
            Route::post('/{id}/video/delete', [InventoryController::class, 'deleteVideo'])->name('video.delete');
            Route::delete('/{id}',      [InventoryController::class, 'destroy'])->name('destroy');
            
        });

        // Orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/place/create',          [\App\Http\Controllers\Admin\AdminOrderController::class, 'create'])->name('place.create');
            Route::get('/place/search-parts',    [\App\Http\Controllers\Admin\AdminOrderController::class, 'searchParts'])->name('place.search-parts');
            Route::post('/place',                [\App\Http\Controllers\Admin\AdminOrderController::class, 'store'])->name('place.store');
            Route::get('/',                      [OrderAdminController::class, 'index'])->name('index');
            Route::get('/{id}',                  [OrderAdminController::class, 'show'])->name('show');
            Route::get('/{id}/edit',              [OrderAdminController::class, 'edit'])->name('edit');
            Route::put('/{id}',                   [OrderAdminController::class, 'update'])->name('update');
            Route::get('/{id}/print',             [OrderAdminController::class, 'printAdmin'])->name('print');
            Route::post('/{id}/confirm-payment', [OrderAdminController::class, 'confirmPayment'])->name('confirm-payment');
            Route::post('/{id}/payments', [OrderAdminController::class, 'addPayment'])->name('payments.add');
            Route::post('/{id}/payments/{paymentId}/confirm', [OrderAdminController::class, 'confirmPaymentRecord'])->name('payments.confirm');
            Route::post('/{id}/payments/{paymentId}/reject', [OrderAdminController::class, 'rejectPayment'])->name('payments.reject');
            Route::post('/{id}/send-reminder', [OrderAdminController::class, 'sendReminder'])->name('send-reminder');
            Route::post('/{id}/cancel',          [OrderAdminController::class, 'cancel'])->name('cancel');
            Route::delete('/{id}',               [OrderAdminController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/status',          [OrderAdminController::class, 'updateStatus'])->name('status');
            Route::post('/{id}/email-receipt',   [OrderAdminController::class, 'emailReceipt'])->name('email-receipt');
        });

        // Customers (auto-aggregated from orders + invoices)
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/',                    [CustomerController::class, 'index'])->name('index');
            Route::get('/lookup',              [CustomerController::class, 'lookup'])->name('lookup');
            Route::get('/contacts/create',     [CustomerController::class, 'createContact'])->name('contacts.create');
            Route::post('/contacts',           [CustomerController::class, 'storeContact'])->name('contacts.store');
            Route::get('/contacts/{id}/edit',  [CustomerController::class, 'editContact'])->name('contacts.edit');
            Route::put('/contacts/{id}',       [CustomerController::class, 'updateContact'])->name('contacts.update');
            Route::delete('/contacts/{id}',    [CustomerController::class, 'destroyContact'])->name('contacts.destroy');
            Route::get('/{phone}',             [CustomerController::class, 'show'])->name('show');
        });
        // Inventory audit sessions
        Route::prefix('audit')->name('audit.')->group(function () {
            Route::get('/',                 [AuditController::class, 'index'])->name('index');
            Route::get('/create',           [AuditController::class, 'create'])->name('create');
            Route::post('/',                [AuditController::class, 'store'])->name('store');
            Route::get('/{id}',             [AuditController::class, 'show'])->name('show');
            Route::post('/{id}/count',      [AuditController::class, 'recordCount'])->name('count');
            Route::post('/{id}/scan',       [AuditController::class, 'scanCount'])->name('scan');
            Route::post('/{id}/scan-undo',  [AuditController::class, 'scanUndo'])->name('scan-undo');
            Route::post('/{id}/complete',   [AuditController::class, 'complete'])->name('complete');
        });
        // Financial reporting
        Route::middleware('admin.auth:admin,manager')->get('/reports/financial', [FinancialReportController::class, 'index'])->name('reports.financial');

        // Storage rooms & bin locations (Phase A2)
        Route::prefix('storage')->name('storage.')->group(function () {
            Route::get('/',                          [\App\Http\Controllers\Admin\StorageController::class, 'index'])->name('index');
            Route::get('/rooms-for-location',         [\App\Http\Controllers\Admin\StorageController::class, 'roomsForLocation'])->name('rooms-for-location');
            Route::get('/shelves-for-room',          [\App\Http\Controllers\Admin\StorageController::class, 'shelvesForRoom'])->name('shelves-for-room');
            Route::get('/all-bins-for-location',     [\App\Http\Controllers\Admin\StorageController::class, 'allBinsForLocation'])->name('all-bins-for-location');
            Route::post('/',                         [\App\Http\Controllers\Admin\StorageController::class, 'store'])->name('store');
            Route::put('/{id}',                      [\App\Http\Controllers\Admin\StorageController::class, 'update'])->name('update');
            Route::get('/{id}',                      [\App\Http\Controllers\Admin\StorageController::class, 'show'])->name('show');
            Route::delete('/{id}',                   [\App\Http\Controllers\Admin\StorageController::class, 'destroyRoom'])->name('destroy');
            Route::post('/{id}/shelves',              [\App\Http\Controllers\Admin\StorageController::class, 'addShelf'])->name('shelves.add');
            Route::post('/{id}/shelves/bulk',          [\App\Http\Controllers\Admin\StorageController::class, 'bulkGenerateShelves'])->name('shelves.bulk');
            Route::delete('/shelves/{shelfId}',        [\App\Http\Controllers\Admin\StorageController::class, 'destroyShelf'])->name('shelves.destroy');
            Route::get('/shelves/{shelfId}/barcode',   [\App\Http\Controllers\Admin\StorageController::class, 'shelfBarcode'])->name('shelves.barcode');
        });

        // Returns (Phase B2)
        Route::prefix('returns')->name('returns.')->group(function () {
            Route::get('/',                  [\App\Http\Controllers\Admin\ReturnsController::class, 'index'])->name('index');
            Route::get('/create',            [\App\Http\Controllers\Admin\ReturnsController::class, 'create'])->name('create');
            Route::get('/search-parts',      [\App\Http\Controllers\Admin\ReturnsController::class, 'searchParts'])->name('search-parts');
            Route::get('/search-invoices',   [\App\Http\Controllers\Admin\ReturnsController::class, 'searchInvoices'])->name('search-invoices');
            Route::get('/invoice-items',     [\App\Http\Controllers\Admin\ReturnsController::class, 'invoiceItems'])->name('invoice-items');
            Route::post('/',                 [\App\Http\Controllers\Admin\ReturnsController::class, 'store'])->name('store');
            Route::get('/{id}',              [\App\Http\Controllers\Admin\ReturnsController::class, 'show'])->name('show');
            Route::post('/{id}/resolve',     [\App\Http\Controllers\Admin\ReturnsController::class, 'resolve'])->name('resolve');
        });

        // Part Names Manager (admin only) - merge/clean duplicate names
        Route::prefix('part-names')->name('part-names.')->group(function () {
            Route::get('/',             [\App\Http\Controllers\Admin\PartNameManagerController::class, 'index'])->name('index');
            Route::post('/merge',       [\App\Http\Controllers\Admin\PartNameManagerController::class, 'merge'])->name('merge');
            Route::post('/rename-one',  [\App\Http\Controllers\Admin\PartNameManagerController::class, 'renameOne'])->name('rename-one');
        });

        // Centralized Payments Ledger - all payments across Orders and Invoices
        Route::get('/payments', [\App\Http\Controllers\Admin\PaymentsLedgerController::class, 'index'])->name('payments.index');

        // Open Tab (#17) - running tab per customer, closes into one invoice
        Route::prefix('tabs')->name('tabs.')->group(function () {
            Route::get('/',                [\App\Http\Controllers\Admin\OpenTabController::class, 'index'])->name('index');
            Route::get('/create',          [\App\Http\Controllers\Admin\OpenTabController::class, 'create'])->name('create');
            Route::post('/',               [\App\Http\Controllers\Admin\OpenTabController::class, 'store'])->name('store');
            Route::get('/{id}',            [\App\Http\Controllers\Admin\OpenTabController::class, 'show'])->name('show');
            Route::get('/{id}/search',     [\App\Http\Controllers\Admin\OpenTabController::class, 'searchItems'])->name('search');
            Route::post('/{id}/items',     [\App\Http\Controllers\Admin\OpenTabController::class, 'addItem'])->name('items.add');
            Route::delete('/{id}/items/{itemId}', [\App\Http\Controllers\Admin\OpenTabController::class, 'removeItem'])->name('items.remove');
            Route::post('/{id}/close',     [\App\Http\Controllers\Admin\OpenTabController::class, 'close'])->name('close');
            Route::post('/{id}/cancel',    [\App\Http\Controllers\Admin\OpenTabController::class, 'cancel'])->name('cancel');
        });

        // Staff tickets/requests - raise issues for admin/manager attention
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/',           [\App\Http\Controllers\Admin\StaffTicketController::class, 'index'])->name('index');
            Route::get('/create',     [\App\Http\Controllers\Admin\StaffTicketController::class, 'create'])->name('create');
            Route::post('/',          [\App\Http\Controllers\Admin\StaffTicketController::class, 'store'])->name('store');
            Route::get('/{id}',       [\App\Http\Controllers\Admin\StaffTicketController::class, 'show'])->name('show');
            Route::post('/{id}/resolve', [\App\Http\Controllers\Admin\StaffTicketController::class, 'resolve'])->name('resolve');
        });

        // Override PIN system (#2/#14/#15)
        Route::prefix('override')->name('override.')->group(function () {
            Route::post('/verify',          [\App\Http\Controllers\Admin\OverrideController::class, 'verify'])->name('verify');
            Route::post('/set-own-pin',     [\App\Http\Controllers\Admin\OverrideController::class, 'setOwnPin'])->name('set-own-pin');
            Route::post('/clear-pin/{id}',  [\App\Http\Controllers\Admin\OverrideController::class, 'clearPin'])->name('clear-pin');
            Route::get('/logs',             [\App\Http\Controllers\Admin\OverrideController::class, 'logs'])->name('logs');
            Route::get('/set-pin',          fn() => view('admin.override.set-pin'))->name('set-pin-page');
        });

        // Asset / equipment register (not for sale) - separate from parts_inventory
        Route::prefix('assets')->name('assets.')->group(function () {
            Route::get('/',           [\App\Http\Controllers\Admin\AssetController::class, 'index'])->name('index');
            Route::get('/create',     [\App\Http\Controllers\Admin\AssetController::class, 'create'])->name('create');
            Route::post('/',          [\App\Http\Controllers\Admin\AssetController::class, 'store'])->name('store');
            Route::get('/{id}/barcode', [\App\Http\Controllers\Admin\AssetController::class, 'barcode'])->name('barcode');
            Route::get('/{id}',       [\App\Http\Controllers\Admin\AssetController::class, 'show'])->name('show');
            Route::get('/{id}/edit',  [\App\Http\Controllers\Admin\AssetController::class, 'edit'])->name('edit');
            Route::put('/{id}',       [\App\Http\Controllers\Admin\AssetController::class, 'update'])->name('update');
            Route::delete('/{id}',    [\App\Http\Controllers\Admin\AssetController::class, 'destroy'])->name('destroy');
        });

        // POS — supermarket-style scan checkout (Phase D)
        Route::prefix('pos')->name('pos.')->group(function () {
            Route::get('/',          [\App\Http\Controllers\Admin\PosController::class, 'index'])->name('index');
            Route::get('/lookup',    [\App\Http\Controllers\Admin\PosController::class, 'lookup'])->name('lookup');
            Route::post('/checkout', [\App\Http\Controllers\Admin\PosController::class, 'checkout'])->name('checkout');
        });

        // Stock transfers between locations (Phase C2)
        Route::prefix('transfers')->name('transfers.')->group(function () {
            Route::get('/',                [\App\Http\Controllers\Admin\StockTransferController::class, 'index'])->name('index');
            Route::get('/create',          [\App\Http\Controllers\Admin\StockTransferController::class, 'create'])->name('create');
            Route::get('/search-parts',    [\App\Http\Controllers\Admin\StockTransferController::class, 'searchParts'])->name('search-parts');
            Route::post('/',               [\App\Http\Controllers\Admin\StockTransferController::class, 'store'])->name('store');
            Route::get('/{id}',            [\App\Http\Controllers\Admin\StockTransferController::class, 'show'])->name('show');
            Route::get('/{id}/waybill',    [\App\Http\Controllers\Admin\StockTransferController::class, 'waybill'])->name('waybill');
            Route::post('/{id}/receive',   [\App\Http\Controllers\Admin\StockTransferController::class, 'receive'])->name('receive');
            Route::post('/{id}/cancel',    [\App\Http\Controllers\Admin\StockTransferController::class, 'cancel'])->name('cancel');
        });

        // Interchange groups (Phase B3)
        Route::prefix('interchange')->name('interchange.')->group(function () {
            Route::post('/ai-suggest', [\App\Http\Controllers\Admin\InterchangeAiController::class, 'suggest'])->name('ai-suggest');
            Route::post('/groups',                       [\App\Http\Controllers\Admin\InterchangeController::class, 'createGroup'])->name('groups.create');
            Route::post('/groups/{groupId}/vehicles',    [\App\Http\Controllers\Admin\InterchangeController::class, 'addVehicle'])->name('groups.add-vehicle');
            Route::post('/promote-heuristic',             [\App\Http\Controllers\Admin\InterchangeController::class, 'promoteHeuristic'])->name('promote-heuristic');
            Route::post('/parts/{partId}/remove',         [\App\Http\Controllers\Admin\InterchangeController::class, 'removePart'])->name('parts.remove');
            Route::post('/parts/{partId}/assign',         [\App\Http\Controllers\Admin\InterchangeController::class, 'assignExisting'])->name('parts.assign');
        });
        // Staff management (admin/manager only)
        Route::middleware('admin.auth:admin,manager')
            ->prefix('staff')->name('staff.')->group(function () {
            Route::get('/',              [StaffController::class, 'index'])->name('index');
            Route::get('/create',        [StaffController::class, 'create'])->name('create');
            Route::post('/',             [StaffController::class, 'store'])->name('store');
            Route::get('/{id}/edit',     [StaffController::class, 'edit'])->name('edit');
            Route::put('/{id}',          [StaffController::class, 'update'])->name('update');
            Route::post('/{id}/toggle',  [StaffController::class, 'toggle'])->name('toggle');
       });

        // Invoices
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [InvoiceController::class, 'index'])->name('index');
            Route::get('/manual', fn() => redirect()->route('admin.invoices.manual.create'));
            Route::get('/manual/create', [InvoiceController::class, 'createManual'])->name('manual.create');
            Route::post('/manual', [InvoiceController::class, 'storeManual'])->name('manual.store');
            Route::get('/manual/{id}', [InvoiceController::class, 'showManual'])->name('show.manual');
            Route::get('/manual/{id}/edit', [InvoiceController::class, 'editManual'])->name('manual.edit');
            Route::put('/manual/{id}', [InvoiceController::class, 'updateManual'])->name('manual.update');
            Route::get('/order/{id}', [InvoiceController::class, 'show'])->name('show');

            // #2 fix — bulk delete for the merged Invoices/Receipts list.
            // Registered as a literal path ('/bulk-destroy'), so it's
            // distinct from the '/{id}' wildcard delete below regardless
            // of declaration order — but kept here, above it, for clarity.
            Route::post('/bulk-destroy', [InvoiceController::class, 'bulkDestroy'])->name('bulk-destroy');

            Route::delete('/{id}', [InvoiceController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/payments', [InvoiceController::class, 'addInvoicePayment'])->name('payments.add');
            Route::post('/{id}/payments/{paymentId}/confirm', [InvoiceController::class, 'confirmInvoicePayment'])->name('payments.confirm');
            Route::post('/{id}/payments/{paymentId}/reject', [InvoiceController::class, 'rejectInvoicePayment'])->name('payments.reject');
            Route::post('/{id}/send-reminder', [InvoiceController::class, 'sendInvoiceReminder'])->name('send-reminder');

            // Quick Receipt — services/labor/misc, never touches inventory
            Route::get('/service', fn() => redirect()->route('admin.invoices.service.create'));
            Route::get('/service/create', [InvoiceController::class, 'createService'])->name('service.create');
            Route::get('/service/search-parts', [InvoiceController::class, 'serviceSearchParts'])->name('service.search-parts');
            Route::post('/service', [InvoiceController::class, 'storeService'])->name('service.store');

            // Car Sale Receipt (#12) — complete vehicle sales, sold as-is,
            // no warranty implied or expressed. Shares the same invoices/
            // invoice_items tables (invoice_type='vehicle'), so it appears
            // in this same list/search/delete/recycle-bin automatically.
            Route::get('/car-sale/create', [InvoiceController::class, 'createCarSale'])->name('car-sale.create');
            Route::post('/car-sale', [InvoiceController::class, 'storeCarSale'])->name('car-sale.store');
        });

        // Recycle Bin (#2) — unified restore/permanent-delete for
        // soft-deleted Invoices AND Orders, since both tables share
        // the same deleted_at + deleted_by_staff_id pattern.
        Route::prefix('recycle-bin')->name('recycle-bin.')->group(function () {
            Route::get('/',                     [RecycleBinController::class, 'index'])->name('index');
            Route::post('/bulk-restore',        [RecycleBinController::class, 'bulkRestore'])->name('bulk-restore');
            Route::post('/bulk-force-delete',   [RecycleBinController::class, 'bulkForceDelete'])->name('bulk-force-delete');
            Route::post('/{type}/{id}/restore', [RecycleBinController::class, 'restore'])->name('restore');
            Route::delete('/{type}/{id}',       [RecycleBinController::class, 'forceDelete'])->name('force-delete');
        });

        // Service rate catalog (fixed-price labor/misc items)
        Route::prefix('service-rates')->name('service-rates.')->group(function () {
            Route::get('/',          [\App\Http\Controllers\Admin\ServiceRateController::class, 'index'])->name('index');
            Route::post('/',         [\App\Http\Controllers\Admin\ServiceRateController::class, 'store'])->name('store');
            Route::put('/{id}',      [\App\Http\Controllers\Admin\ServiceRateController::class, 'update'])->name('update');
            Route::delete('/{id}',   [\App\Http\Controllers\Admin\ServiceRateController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/barcode', [\App\Http\Controllers\Admin\ServiceRateController::class, 'barcode'])->name('barcode');
            Route::get('/{id}/prices', [\App\Http\Controllers\Admin\ServiceRateController::class, 'editPrices'])->name('prices.edit');
            Route::post('/{id}/prices', [\App\Http\Controllers\Admin\ServiceRateController::class, 'updatePrices'])->name('prices.update');
        });
    }); // end admin.auth middleware

}); // end admin prefix
