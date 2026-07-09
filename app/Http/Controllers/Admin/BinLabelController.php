<?php
// FILE: app/Http/Controllers/Admin/BinLabelController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bin & Room Labels — 12×4 inches landscape
 *
 * Routes:
 *   GET /admin/storage/bin-label/{shelfId}   → single bin
 *   GET /admin/storage/room-labels/{roomId}  → all bins in room + room label
 *   GET /admin/storage/bin-labels?ids=1,2,3  → batch bins
 */
class BinLabelController extends Controller
{
    // ── Single bin ────────────────────────────────────────────────
    public function single(int $shelfId)
    {
        $shelf = $this->enrichShelf(
            DB::table('storage_shelves as ss')
                ->leftJoin('storage_rooms as sr', 'sr.id', '=', 'ss.room_id')
                ->where('ss.id', $shelfId)
                ->select('ss.*', 'sr.name as room_name', 'sr.location', 'sr.code as room_code')
                ->first()
        );
        if (!$shelf) abort(404);
        return view('admin.storage.bin-label', [
            'shelves' => collect([$shelf]),
            'rooms'   => collect(),
        ]);
    }

    // ── All bins in a room + the room label itself ─────────────────
    public function room(int $roomId)
    {
        $room = DB::table('storage_rooms')->where('id', $roomId)->first();
        if (!$room) abort(404);

        $shelves = DB::table('storage_shelves as ss')
            ->leftJoin('storage_rooms as sr', 'sr.id', '=', 'ss.room_id')
            ->where('ss.room_id', $roomId)
            ->select('ss.*', 'sr.name as room_name', 'sr.location', 'sr.code as room_code')
            ->orderBy('ss.full_bin_code')
            ->get()
            ->map(fn($s) => $this->enrichShelf($s));

        return view('admin.storage.bin-label', [
            'shelves' => $shelves,
            'rooms'   => collect([$room]),
        ]);
    }

    // ── Batch bins (?ids=1,2,3) ───────────────────────────────────
    public function batch(Request $request)
    {
        $ids = array_filter(array_map('intval', explode(',', $request->get('ids', ''))));
        if (empty($ids)) abort(400, 'No bin IDs provided.');

        $shelves = DB::table('storage_shelves as ss')
            ->leftJoin('storage_rooms as sr', 'sr.id', '=', 'ss.room_id')
            ->whereIn('ss.id', $ids)
            ->select('ss.*', 'sr.name as room_name', 'sr.location', 'sr.code as room_code')
            ->orderByRaw('FIELD(ss.id, ' . implode(',', $ids) . ')')
            ->get()
            ->map(fn($s) => $this->enrichShelf($s));

        return view('admin.storage.bin-label', [
            'shelves' => $shelves,
            'rooms'   => collect(),
        ]);
    }

    // ── All rooms in a location (?location=Ile-Ife Nigeria) ────────
    public function allRooms(Request $request)
    {
        $location = $request->get('location', '');
        $query    = DB::table('storage_rooms');
        if ($location) $query->where('location', $location);
        $rooms = $query->orderBy('name')->get();

        $shelves = collect();
        if ($request->boolean('include_bins')) {
            $shelves = DB::table('storage_shelves as ss')
                ->leftJoin('storage_rooms as sr', 'sr.id', '=', 'ss.room_id')
                ->when($location, fn($q) => $q->where('sr.location', $location))
                ->select('ss.*', 'sr.name as room_name', 'sr.location', 'sr.code as room_code')
                ->orderBy('sr.name')->orderBy('ss.full_bin_code')
                ->get()
                ->map(fn($s) => $this->enrichShelf($s));
        }

        return view('admin.storage.bin-label', compact('rooms', 'shelves'));
    }

    private function enrichShelf(?object $shelf): ?object
    {
        if (!$shelf) return null;
        $shelf->bin_code    = $shelf->bin_code    ?? $shelf->full_bin_code;
        $shelf->shelf_label = $shelf->shelf_label ?? null;
        $shelf->row_label   = $shelf->row_label   ?? null;
        return $shelf;
    }
}
