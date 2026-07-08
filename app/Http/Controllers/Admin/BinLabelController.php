<?php
// FILE: app/Http/Controllers/Admin/BinLabelController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bin / Shelf Location Labels — 12×4 inches landscape
 * Paste on the physical shelf column for warehouse navigation.
 *
 * Routes to add in web.php:
 *   // Print one bin
 *   Route::get('/storage/bin-label/{shelfId}',
 *       [\App\Http\Controllers\Admin\BinLabelController::class, 'single'])
 *       ->name('admin.storage.bin-label');
 *
 *   // Print all bins in a room
 *   Route::get('/storage/room-labels/{roomId}',
 *       [\App\Http\Controllers\Admin\BinLabelController::class, 'room'])
 *       ->name('admin.storage.room-labels');
 *
 *   // Print selected bins by IDs (?ids=1,2,3)
 *   Route::get('/storage/bin-labels',
 *       [\App\Http\Controllers\Admin\BinLabelController::class, 'batch'])
 *       ->name('admin.storage.bin-labels');
 */
class BinLabelController extends Controller
{
    // ── Single bin label ──────────────────────────────────────────
    public function single(int $shelfId)
    {
        $shelf = $this->enrichShelf(
            DB::table('storage_shelves as ss')
                ->leftJoin('storage_rooms as sr', 'sr.id', '=', 'ss.room_id')
                ->where('ss.id', $shelfId)
                ->select('ss.*', 'sr.name as room_name', 'sr.location')
                ->first()
        );

        if (!$shelf) abort(404);

        return view('admin.storage.bin-label', ['shelves' => collect([$shelf])]);
    }

    // ── All bins in a room ────────────────────────────────────────
    public function room(int $roomId)
    {
        $shelves = DB::table('storage_shelves as ss')
            ->leftJoin('storage_rooms as sr', 'sr.id', '=', 'ss.room_id')
            ->where('ss.room_id', $roomId)
            ->select('ss.*', 'sr.name as room_name', 'sr.location')
            ->orderBy('ss.full_bin_code')
            ->get()
            ->map(fn($s) => $this->enrichShelf($s));

        if ($shelves->isEmpty()) abort(404, 'No bins in this room.');

        return view('admin.storage.bin-label', compact('shelves'));
    }

    // ── Batch — ?ids=1,2,3 ───────────────────────────────────────
    public function batch(Request $request)
    {
        $ids = array_filter(array_map('intval', explode(',', $request->get('ids', ''))));
        if (empty($ids)) abort(400, 'No bin IDs provided.');

        $shelves = DB::table('storage_shelves as ss')
            ->leftJoin('storage_rooms as sr', 'sr.id', '=', 'ss.room_id')
            ->whereIn('ss.id', $ids)
            ->select('ss.*', 'sr.name as room_name', 'sr.location')
            ->orderByRaw('FIELD(ss.id, ' . implode(',', $ids) . ')')
            ->get()
            ->map(fn($s) => $this->enrichShelf($s));

        if ($shelves->isEmpty()) abort(404);

        return view('admin.storage.bin-label', compact('shelves'));
    }

    // ── Enrich shelf with extra display fields ────────────────────
    private function enrichShelf(?object $shelf): ?object
    {
        if (!$shelf) return null;

        // Parse bin code into parts for large display
        // full_bin_code format is typically "RM2-B3" or "MODULAR ROOM 2-B3"
        // bin_code is just the shelf part e.g. "B3"
        $shelf->bin_code    = $shelf->bin_code ?? $shelf->full_bin_code;
        $shelf->shelf_label = $shelf->shelf_label ?? null;
        $shelf->row_label   = $shelf->row_label   ?? null;

        return $shelf;
    }
}
