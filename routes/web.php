<?php
// FILE: routes/web.php
// ═══════════════════════════════════════════════════════════════════
// NEW POWERLINK ADOPTION ROUTES — Phases 1-7
// These are additions not yet in routes/admin.php
// All other admin routes are in routes/admin.php
// ═══════════════════════════════════════════════════════════════════

use Illuminate\Support\Facades\Route;

// ── Root ──────────────────────────────────────────────────────────
// Production (autozenithparts.com) → public parts page
// Local development → admin login
Route::get('/', function () {
    return app()->environment('production')
        ? redirect('/parts', 301)
        : redirect()->route('admin.login');
});

// ── Public auth aliases (used in public layout nav) ───────────────
// AccountIX / customer portal — redirect to accounts subdomain
Route::get('/login',    function () { return redirect('https://accounts.autozenithparts.com/login'); })->name('login');
Route::get('/register', function () { return redirect('https://accounts.autozenithparts.com/register'); })->name('register');

// ── Public receipt ─────────────────────────────────────────────────
Route::get('/receipt/{orderRef}',  [\App\Http\Controllers\Admin\OrderAdminController::class, 'receiptPublic'])->name('orders.receipt.public');

// ── Public compatibility checker page ─────────────────────────────
Route::get('/parts/compatibility', [\App\Http\Controllers\PartsSearchController::class, 'compatibility'])->name('parts.compatibility');

Route::prefix('admin')->middleware(['admin.auth'])->group(function () {

    // ── Vehicle ROI Dashboard — Phase 4 ───────────────────────────
    Route::get('/vehicles/roi-summary',
        [\App\Http\Controllers\Admin\VehicleROIController::class, 'summary'])
        ->name('admin.vehicles.roi-summary');
    Route::get('/vehicles/{id}/roi',
        [\App\Http\Controllers\Admin\VehicleROIController::class, 'show'])
        ->name('admin.vehicles.roi');
    Route::get('/vehicles/{id}',
        [\App\Http\Controllers\Admin\VehicleDetailController::class, 'show'])
        ->name('admin.vehicles.show');

    // ── Barcode Labels — Phase 4 ───────────────────────────────────
    Route::get('/inventory/barcode-label',
        [\App\Http\Controllers\Admin\BarcodeController::class, 'show'])
        ->name('admin.inventory.barcode-label');
    Route::get('/inventory/{id}/barcode',
        [\App\Http\Controllers\Admin\BarcodeController::class, 'show'])
        ->name('admin.inventory.barcode');

    // ── Bin / Shelf Labels ─────────────────────────────────────────
    Route::get('/storage/bin-labels',
        [\App\Http\Controllers\Admin\BinLabelController::class, 'batch'])
        ->name('admin.storage.bin-labels');
    Route::get('/storage/bin-label/{shelfId}',
        [\App\Http\Controllers\Admin\BinLabelController::class, 'single'])
        ->name('admin.storage.bin-label');
    Route::get('/storage/room-labels/{roomId}',
        [\App\Http\Controllers\Admin\BinLabelController::class, 'room'])
        ->name('admin.storage.room-labels');

    // ── Compatibility Checker — Powerlink style ────────────────────
    Route::get('/compatibility',
        [\App\Http\Controllers\Admin\CompatibilityController::class, 'index'])
        ->name('admin.compatibility.index');
    Route::post('/compatibility/check',
        [\App\Http\Controllers\Admin\CompatibilityController::class, 'check'])
        ->name('admin.compatibility.check');
    Route::post('/compatibility/save',
        [\App\Http\Controllers\Admin\CompatibilityController::class, 'save'])
        ->name('admin.compatibility.save');
    Route::delete('/compatibility/{id}',
        [\App\Http\Controllers\Admin\CompatibilityController::class, 'destroy'])
        ->name('admin.compatibility.destroy');

    // ── Consumables ────────────────────────────────────────────────
    Route::get('/consumables',
        [\App\Http\Controllers\Admin\ConsumableController::class, 'index'])
        ->name('admin.inventory.consumable.index');
    Route::get('/consumables/create',
        [\App\Http\Controllers\Admin\ConsumableController::class, 'create'])
        ->name('admin.inventory.consumable.create');
    Route::post('/consumables',
        [\App\Http\Controllers\Admin\ConsumableController::class, 'store'])
        ->name('admin.inventory.consumable.store');
    Route::get('/consumables/{id}/edit',
        [\App\Http\Controllers\Admin\ConsumableController::class, 'edit'])
        ->name('admin.inventory.consumable.edit');
    Route::put('/consumables/{id}',
        [\App\Http\Controllers\Admin\ConsumableController::class, 'update'])
        ->name('admin.inventory.consumable.update');
    Route::delete('/consumables/{id}',
        [\App\Http\Controllers\Admin\ConsumableController::class, 'destroy'])
        ->name('admin.inventory.consumable.destroy');

    // ── Reports — Phase 7 ──────────────────────────────────────────
    Route::get('/reports/financial',
        [\App\Http\Controllers\Admin\FinancialReportController::class, 'index'])
        ->name('admin.reports.financial');
    Route::get('/reports/financial/export',
        [\App\Http\Controllers\Admin\FinancialReportController::class, 'export'])
        ->name('admin.reports.financial.export');
    Route::get('/reports/inventory',
        [\App\Http\Controllers\Admin\ReportController::class, 'inventory'])
        ->name('admin.reports.inventory');
    Route::get('/reports/staff',
        [\App\Http\Controllers\Admin\ReportController::class, 'staff'])
        ->name('admin.reports.staff');
    Route::get('/reports/vehicles',
        [\App\Http\Controllers\Admin\ReportController::class, 'vehicles'])
        ->name('admin.reports.vehicles');

    // ── Recycle Bin ────────────────────────────────────────────────
    Route::get('/recycle-bin',
        [\App\Http\Controllers\Admin\RecycleBinController::class, 'index'])
        ->name('admin.recycle-bin.index');
    Route::post('/recycle-bin/{type}/{id}/restore',
        [\App\Http\Controllers\Admin\RecycleBinController::class, 'restore'])
        ->name('admin.recycle-bin.restore');
    Route::delete('/recycle-bin/{type}/{id}',
        [\App\Http\Controllers\Admin\RecycleBinController::class, 'forceDelete'])
        ->name('admin.recycle-bin.destroy');
    Route::post('/recycle-bin/bulk-restore',
        [\App\Http\Controllers\Admin\RecycleBinController::class, 'bulkRestore'])
        ->name('admin.recycle-bin.bulk-restore');
    Route::post('/recycle-bin/bulk-force-delete',
        [\App\Http\Controllers\Admin\RecycleBinController::class, 'bulkForceDelete'])
        ->name('admin.recycle-bin.bulk-force-delete');

    // ── Staff Tickets ──────────────────────────────────────────────
    Route::get('/tickets',
        [\App\Http\Controllers\Admin\StaffTicketController::class, 'index'])
        ->name('admin.tickets.index');
    Route::get('/tickets/create',
        [\App\Http\Controllers\Admin\StaffTicketController::class, 'create'])
        ->name('admin.tickets.create');
    Route::post('/tickets',
        [\App\Http\Controllers\Admin\StaffTicketController::class, 'store'])
        ->name('admin.tickets.store');
    Route::get('/tickets/{id}',
        [\App\Http\Controllers\Admin\StaffTicketController::class, 'show'])
        ->name('admin.tickets.show');
    Route::post('/tickets/{id}/resolve',
        [\App\Http\Controllers\Admin\StaffTicketController::class, 'resolve'])
        ->name('admin.tickets.resolve');

    // ── Supervisor PIN override ────────────────────────────────────
    Route::get('/override/set-pin',
        [\App\Http\Controllers\Admin\OverrideController::class, 'setOwnPinPage'])
        ->name('admin.override.set-pin-page');
    Route::post('/override/set-pin',
        [\App\Http\Controllers\Admin\OverrideController::class, 'setOwnPin'])
        ->name('admin.override.set-pin');
    Route::post('/override/verify',
        [\App\Http\Controllers\Admin\OverrideController::class, 'verify'])
        ->name('admin.override.verify');
    Route::post('/override/clear-pin/{staffId}',
        [\App\Http\Controllers\Admin\OverrideController::class, 'clearPin'])
        ->name('admin.override.clear-pin');
    Route::get('/override/logs',
        [\App\Http\Controllers\Admin\OverrideController::class, 'logs'])
        ->name('admin.override.logs');

    // ── Part Name Manager ──────────────────────────────────────────
    Route::get('/part-names',
        [\App\Http\Controllers\Admin\PartNameManagerController::class, 'index'])
        ->name('admin.part-names.index');
    Route::post('/part-names/merge',
        [\App\Http\Controllers\Admin\PartNameManagerController::class, 'merge'])
        ->name('admin.part-names.merge');
    Route::post('/part-names/rename-one',
        [\App\Http\Controllers\Admin\PartNameManagerController::class, 'renameOne'])
        ->name('admin.part-names.rename-one');
    Route::post('/part-names',
        [\App\Http\Controllers\Admin\PartNameManagerController::class, 'store'])
        ->name('admin.part-names.store');
    Route::put('/part-names/{id}',
        [\App\Http\Controllers\Admin\PartNameManagerController::class, 'update'])
        ->name('admin.part-names.update');
    Route::delete('/part-names/{id}',
        [\App\Http\Controllers\Admin\PartNameManagerController::class, 'destroy'])
        ->name('admin.part-names.destroy');

    // ── Interchange (extended) ─────────────────────────────────────
    Route::post('/interchange/groups/create',
        [\App\Http\Controllers\Admin\InterchangeController::class, 'createGroup'])
        ->name('admin.interchange.groups.create');
    Route::post('/interchange/promote-heuristic',
        [\App\Http\Controllers\Admin\InterchangeController::class, 'promoteHeuristic'])
        ->name('admin.interchange.promote-heuristic');
    Route::post('/interchange/ai-suggest',
        [\App\Http\Controllers\Admin\InterchangeAiController::class, 'suggest'])
        ->name('admin.interchange.ai-suggest');
    Route::post('/interchange/{id}/vehicles',
        [\App\Http\Controllers\Admin\InterchangeController::class, 'addVehicle'])
        ->name('admin.interchange.groups.add-vehicle');
    Route::delete('/interchange/{id}/parts/{partId}',
        [\App\Http\Controllers\Admin\InterchangeController::class, 'removePart'])
        ->name('admin.interchange.parts.remove');

    // ── Admin order placement ──────────────────────────────────────
    Route::get('/orders/place/create',
        [\App\Http\Controllers\Admin\AdminOrderController::class, 'create'])
        ->name('admin.orders.place.create');
    Route::post('/orders/place',
        [\App\Http\Controllers\Admin\AdminOrderController::class, 'store'])
        ->name('admin.orders.place.store');
    Route::get('/orders/place/search-parts',
        [\App\Http\Controllers\Admin\AdminOrderController::class, 'searchParts'])
        ->name('admin.orders.place.search-parts');

    // ── Payments ledger ────────────────────────────────────────────
    Route::get('/payments',
        [\App\Http\Controllers\Admin\PaymentsLedgerController::class, 'index'])
        ->name('admin.payments.index');

    // ── Commissions ────────────────────────────────────────────────
    Route::get('/commissions',
        [\App\Http\Controllers\Admin\PaymentsLedgerController::class, 'commissions'])
        ->name('admin.commissions.index');

    // ── Inventory manual add ───────────────────────────────────────
    Route::get('/inventory/manual-add',
        [\App\Http\Controllers\Admin\InventoryController::class, 'manualAdd'])
        ->name('admin.inventory.manual-add');
    Route::post('/inventory/manual-add',
        [\App\Http\Controllers\Admin\InventoryController::class, 'storeManual'])
        ->name('admin.inventory.manual-add.store');

    // ── Admin vehicle models/engine AJAX (for harvest create form) ─
    Route::get('/harvest/models',
        [\App\Http\Controllers\Admin\HarvestController::class, 'models'])
        ->name('admin.harvest.models');
    Route::get('/harvest/engine-options',
        [\App\Http\Controllers\Admin\HarvestController::class, 'engineOptions'])
        ->name('admin.harvest.engine-options');

    // ── OEM lookup AJAX — for manual-add form ─────────────────────
    // Returns engine code, transmission code, pin count for a vehicle
    Route::get('/inventory/oem-lookup', function(\Illuminate\Http\Request $request) {
        $make    = strtoupper(trim($request->get('make', '')));
        $model   = strtoupper(trim($request->get('model', '')));
        $year    = (int) $request->get('year', 0);
        $engineL = (float) $request->get('engine_l', 0);
        if (!$make || !$model || !$year) return response()->json(['source' => null]);

        // Check existing inventory first (most accurate — real stock data)
        $fromStock = \Illuminate\Support\Facades\DB::table('parts_inventory')
            ->where('brand', $make)->where('model', $model)
            ->where('year_from', '<=', $year)->where('year_to', '>=', $year)
            ->whereNotNull('engine_code_oem')
            ->select('engine_code_oem','transmission_code_oem','pin_count','gear_alias')
            ->first();

        if ($fromStock) {
            return response()->json([
                'engine_code'       => $fromStock->engine_code_oem,
                'transmission_code' => $fromStock->transmission_code_oem,
                'pin_count'         => $fromStock->pin_count,
                'gear_alias'        => $fromStock->gear_alias,
                'source'            => 'inventory',
                'match_count'       => \Illuminate\Support\Facades\DB::table('parts_inventory')
                    ->where('brand',$make)->where('model',$model)
                    ->whereNotNull('engine_code_oem')->count(),
            ]);
        }

        // Fall back to OemDatabase (Ladipo algorithm)
        $cyl = $engineL >= 2.9 ? 6 : 4;
        $oem = \App\Data\OemDatabase::lookup($make, $model, $year, $cyl, $engineL);

        if (!$oem['engine_code'] && !$oem['transmission_code']) {
            return response()->json(['source' => null]);
        }

        return response()->json([
            'engine_code'       => $oem['engine_code'],
            'transmission_code' => $oem['transmission_code'],
            'pin_count'         => $oem['pin_count'],
            'gear_alias'        => $oem['gear_alias'],
            'source'            => 'oem_database',
            'multiple_engines'  => $oem['multiple_engines'] ?? false,
            'market_note'       => $oem['market_note'] ?? null,
        ]);
    })->name('admin.inventory.oem-lookup');

    // ── Inventory photos & video ───────────────────────────────────
    Route::get('/inventory/{id}/photos',
        [\App\Http\Controllers\Admin\InventoryController::class, 'photos'])
        ->name('admin.inventory.photos');
    Route::post('/inventory/{id}/photos',
        [\App\Http\Controllers\Admin\InventoryController::class, 'addPhoto'])
        ->name('admin.inventory.photos.add');
    Route::post('/inventory/{id}/photos/delete',
        [\App\Http\Controllers\Admin\InventoryController::class, 'deletePhoto'])
        ->name('admin.inventory.photos.delete');
    Route::post('/inventory/{id}/video',
        [\App\Http\Controllers\Admin\InventoryController::class, 'addVideo'])
        ->name('admin.inventory.video.add');
    Route::post('/inventory/{id}/video/delete',
        [\App\Http\Controllers\Admin\InventoryController::class, 'deleteVideo'])
        ->name('admin.inventory.video.delete');

    // ── Customers additional routes ────────────────────────────────
    Route::get('/customers/lookup',
        [\App\Http\Controllers\Admin\CustomerController::class, 'lookup'])
        ->name('admin.customers.lookup');

    // ── Invoice show for order ─────────────────────────────────────
    Route::get('/orders/{id}/invoice',
        [\App\Http\Controllers\Admin\InvoiceController::class, 'show'])
        ->name('admin.orders.invoice');

    // ── Service invoice search parts ───────────────────────────────
    Route::get('/invoices/service/search-parts',
        [\App\Http\Controllers\Admin\InvoiceController::class, 'serviceSearchParts'])
        ->name('admin.invoices.service.search-parts');

}); // end web.php admin group

// ── Fallback ──────────────────────────────────────────────────────
Route::fallback(function () {
    if (request()->is('admin/*')) {
        return redirect()->route('admin.login');
    }
    abort(404);
});
