<?php
// FILE: app/Http/Controllers/Admin/StorageController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class StorageController extends Controller
{
    const LOCATIONS = [
        'Waxahachie TX', 'Kennedale TX', 'Elkhorn WI',
        'Ile-Ife Nigeria', 'Ibadan Nigeria', 'Lagos Nigeria', 'Abuja Nigeria', 'Akure Nigeria', 'Accra Ghana',
    ];

    // =========================================================
    // GET /admin/storage — list all rooms, grouped by location
    // =========================================================
    public function index()
    {
        $rooms = DB::table('storage_rooms')
            ->orderBy('location')->orderBy('name')
            ->get()
            ->groupBy('location');

        // Shelf counts per room
        $shelfCounts = DB::table('storage_shelves')
            ->select('storage_room_id', DB::raw('count(*) as n'))
            ->groupBy('storage_room_id')
            ->pluck('n', 'storage_room_id');

        return view('admin.storage.index', [
            'rooms'       => $rooms,
            'shelfCounts' => $shelfCounts,
            'locations'   => self::LOCATIONS,
        ]);
    }

    // =========================================================
    // POST /admin/storage — create a new store room
    // =========================================================
    public function store(Request $request)
    {
        $request->validate([
            'location'    => 'required|string|max:60',
            'name'        => 'required|string|max:80',
            'code'        => 'required|string|max:30|unique:storage_rooms,code',
            'description' => 'nullable|string|max:500',
        ]);

        DB::table('storage_rooms')->insert([
            'location'    => $request->location,
            'name'        => $request->name,
            'code'        => strtoupper(trim($request->code)),
            'description' => $request->description,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return redirect()->route('admin.storage.index')
            ->with('success', "Store room \"{$request->name}\" created.");
    }

    // =========================================================
    // PUT /admin/storage/{id} — rename a room. Admin only.
    // =========================================================
    public function update(Request $request, int $roomId)
    {
        if (Session::get('staff_role') !== 'admin') {
            return back()->with('error', 'Only an admin can rename a storage room.');
        }

        $room = DB::table('storage_rooms')->where('id', $roomId)->first();
        abort_if(!$room, 404);

        $request->validate([
            'name'        => 'required|string|max:80',
            'code'        => 'required|string|max:20',
            'description' => 'nullable|string|max:500',
        ]);

        // Changing the code does NOT retroactively rename existing bin
        // codes (they already have the old room code baked in) — warn
        // if there are bins, but still allow it since the admin may be
        // correcting a typo intentionally.
        $shelfCount = DB::table('storage_shelves')->where('storage_room_id', $roomId)->count();

        DB::table('storage_rooms')->where('id', $roomId)->update([
            'name'        => $request->name,
            'code'        => $request->code,
            'description' => $request->description,
            'updated_at'  => now(),
        ]);

        $msg = 'Room updated.';
        if ($shelfCount > 0 && $request->code !== $room->code) {
            $msg .= " Note: {$shelfCount} existing bin code(s) still use the OLD room code ({$room->code}) — they won't be renamed automatically.";
        }

        return redirect()->route('admin.storage.show', $roomId)->with('success', $msg);
    }

    // =========================================================
    // GET /admin/storage/{id} — view one room's shelves/bins
    // =========================================================
    public function show(int $id)
    {
        $room = DB::table('storage_rooms')->where('id', $id)->first();
        abort_if(!$room, 404);

        $shelves = DB::table('storage_shelves')
            ->where('storage_room_id', $id)
            ->orderBy('shelf_code')->orderBy('column_number')->orderBy('space_number')
            ->get();

        // How many parts currently sit on each shelf (uses the new FK link)
        $partsCount = DB::table('parts_inventory')
            ->whereIn('storage_shelf_id', $shelves->pluck('id'))
            ->select('storage_shelf_id', DB::raw('count(*) as n'))
            ->groupBy('storage_shelf_id')
            ->pluck('n', 'storage_shelf_id');

        return view('admin.storage.show', compact('room', 'shelves', 'partsCount'));
    }

    // =========================================================
    // POST /admin/storage/{id}/shelves — add a single shelf/bin
    // =========================================================
    public function addShelf(Request $request, int $roomId)
    {
        $room = DB::table('storage_rooms')->where('id', $roomId)->first();
        abort_if(!$room, 404);

        $request->validate([
            'shelf_code'     => 'required|string|max:20',
            'column_number'  => 'nullable|integer|min:0|max:999',
            'space_number'   => 'nullable|integer|min:0|max:999',
            'capacity'       => 'nullable|integer|min:1',
            'notes'          => 'nullable|string|max:300',
        ]);

        $fullCode = $this->buildFullBinCode($room->code, $request->shelf_code, $request->column_number, $request->space_number);

        if (DB::table('storage_shelves')->where('full_bin_code', $fullCode)->exists()) {
            return back()->withErrors(['shelf_code' => "Bin code {$fullCode} already exists in this room."])->withInput();
        }

        DB::table('storage_shelves')->insert([
            'storage_room_id' => $roomId,
            'shelf_code'      => strtoupper(trim($request->shelf_code)),
            'column_number'   => $request->column_number,
            'space_number'    => $request->space_number,
            'full_bin_code'   => $fullCode,
            'capacity'        => $request->capacity,
            'notes'           => $request->notes,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return redirect()->route('admin.storage.show', $roomId)
            ->with('success', "Bin {$fullCode} added.");
    }

    // =========================================================
    // POST /admin/storage/{id}/shelves/bulk — bulk-generate a grid
    // e.g. shelves A-F, columns 1-10, spaces 1-4 each = 240 bins at once
    // =========================================================
    public function bulkGenerateShelves(Request $request, int $roomId)
    {
        $room = DB::table('storage_rooms')->where('id', $roomId)->first();
        abort_if(!$room, 404);

        $request->validate([
            'shelf_codes'    => 'required|string|max:200', // comma-separated, e.g. "A,B,C,D"
            'columns'        => 'required|integer|min:1|max:99',
            'spaces'         => 'required|integer|min:1|max:99',
        ]);

        $shelfCodes = array_filter(array_map('trim', explode(',', strtoupper($request->shelf_codes))));
        $created = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($shelfCodes as $shelfCode) {
                for ($col = 1; $col <= $request->columns; $col++) {
                    for ($space = 1; $space <= $request->spaces; $space++) {
                        $fullCode = $this->buildFullBinCode($room->code, $shelfCode, $col, $space);

                        if (DB::table('storage_shelves')->where('full_bin_code', $fullCode)->exists()) {
                            $skipped++;
                            continue;
                        }

                        DB::table('storage_shelves')->insert([
                            'storage_room_id' => $roomId,
                            'shelf_code'      => $shelfCode,
                            'column_number'   => $col,
                            'space_number'    => $space,
                            'full_bin_code'   => $fullCode,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                        $created++;
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Bulk generation failed: ' . $e->getMessage());
        }

        $msg = "{$created} bins created.";
        if ($skipped > 0) $msg .= " {$skipped} already existed and were skipped.";

        return redirect()->route('admin.storage.show', $roomId)->with('success', $msg);
    }

    // =========================================================
    // DELETE /admin/storage/shelves/{id}
    // =========================================================
    public function destroyShelf(int $shelfId)
    {
        $shelf = DB::table('storage_shelves')->where('id', $shelfId)->first();
        abort_if(!$shelf, 404);

        $inUse = DB::table('parts_inventory')->where('storage_shelf_id', $shelfId)->count();
        if ($inUse > 0) {
            return back()->with('error', "Cannot delete {$shelf->full_bin_code} — {$inUse} part(s) are currently assigned to it.");
        }

        DB::table('storage_shelves')->where('id', $shelfId)->delete();
        return back()->with('success', "Bin {$shelf->full_bin_code} deleted.");
    }

    // =========================================================
    // DELETE /admin/storage/{id} — delete an empty room
    // =========================================================
    public function destroyRoom(int $roomId)
    {
        $shelfCount = DB::table('storage_shelves')->where('storage_room_id', $roomId)->count();
        if ($shelfCount > 0) {
            return back()->with('error', 'Cannot delete a room that still has bins — delete all bins first.');
        }

        DB::table('storage_rooms')->where('id', $roomId)->delete();
        return redirect()->route('admin.storage.index')->with('success', 'Store room deleted.');
    }

    // =========================================================
    // AJAX: GET /admin/storage/rooms-for-location?location=X
    // Used by inventory forms to populate the Store Room dropdown
    // once a Location is selected.
    // =========================================================
    public function roomsForLocation(Request $request)
    {
        $location = trim($request->get('location', ''));
        $rooms = DB::table('storage_rooms')
            ->where('location', $location)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json(['rooms' => $rooms]);
    }

    // =========================================================
    // GET /admin/storage/shelves/{id}/barcode — printable bin label
    // =========================================================
    public function shelfBarcode(int $shelfId)
    {
        $shelf = DB::table('storage_shelves')->where('id', $shelfId)->first();
        abort_if(!$shelf, 404);
        return view('admin.storage.bin-barcode', compact('shelf'));
    }

    // =========================================================
    // AJAX: GET /admin/storage/shelves-for-room?room_id=X
    // Used by inventory forms to populate the bin dropdown once a
    // store room is selected.
    // =========================================================
    public function shelvesForRoom(Request $request)
    {
        $roomId = (int) $request->get('room_id');
        $shelves = DB::table('storage_shelves')
            ->where('storage_room_id', $roomId)
            ->orderBy('shelf_code')->orderBy('column_number')->orderBy('space_number')
            ->get(['id', 'full_bin_code', 'shelf_code', 'column_number', 'space_number']);

        return response()->json(['shelves' => $shelves]);
    }

    private function buildFullBinCode(string $roomCode, string $shelfCode, ?int $col, ?int $space): string
    {
        $parts = [strtoupper($roomCode), strtoupper(trim($shelfCode))];
        if ($col !== null)   $parts[] = str_pad((string) $col, 2, '0', STR_PAD_LEFT);
        if ($space !== null) $parts[] = str_pad((string) $space, 2, '0', STR_PAD_LEFT);
        return implode('-', $parts);
    }
}
