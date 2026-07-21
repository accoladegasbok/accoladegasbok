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
            'address'     => 'nullable|string|max:255',
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
            'address'     => $request->address,
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
    // PUT /admin/storage/shelves/{shelfId} — edit a bin's own
    // identity in place (shelf code, column, space, notes). Stays in
    // its own room — this does NOT move the bin or its contents
    // anywhere. Previously bins could only be Added or Deleted —
    // correcting a typo or restructuring a layout meant deleting and
    // recreating, which would have orphaned any parts still assigned
    // there. If you need to move what's IN a bin to a different bin
    // elsewhere, use relocateItems() below instead — that's a
    // deliberately separate action from renaming this bin.
    // =========================================================
    public function updateShelf(Request $request, int $shelfId)
    {
        $shelf = DB::table('storage_shelves')->where('id', $shelfId)->first();
        abort_if(!$shelf, 404);

        $request->validate([
            'shelf_code'      => 'required|string|max:20',
            'column_number'   => 'nullable|integer|min:0|max:999',
            'space_number'    => 'nullable|integer|min:0|max:999',
            'capacity'        => 'nullable|integer|min:1',
            'notes'           => 'nullable|string|max:300',
        ]);

        $room = DB::table('storage_rooms')->where('id', $shelf->storage_room_id)->first();
        abort_if(!$room, 404);

        $fullCode = $this->buildFullBinCode($room->code, $request->shelf_code, $request->column_number, $request->space_number);

        // Uniqueness check excludes this shelf's own current row —
        // otherwise saving without changing anything would falsely
        // collide with itself.
        if (DB::table('storage_shelves')->where('full_bin_code', $fullCode)->where('id', '!=', $shelfId)->exists()) {
            return back()->withErrors(['shelf_code' => "Bin code {$fullCode} already exists."])->withInput();
        }

        DB::beginTransaction();
        try {
            DB::table('storage_shelves')->where('id', $shelfId)->update([
                'shelf_code'      => strtoupper(trim($request->shelf_code)),
                'column_number'   => $request->column_number,
                'space_number'    => $request->space_number,
                'full_bin_code'   => $fullCode,
                'capacity'        => $request->capacity,
                'notes'           => $request->notes,
                'updated_at'      => now(),
            ]);

            // Keep every part currently sitting in this bin showing the
            // correct bin_location text after a rename — room/location
            // are untouched since this bin isn't moving anywhere.
            DB::table('parts_inventory')->where('storage_shelf_id', $shelfId)->update([
                'bin_location' => $fullCode,
                'updated_at'   => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Could not update bin: ' . $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.storage.show', $shelf->storage_room_id)->with('success', "Bin updated to {$fullCode}.");
    }

    // =========================================================
    // POST /admin/storage/shelves/{shelfId}/relocate-items — moves
    // every part CURRENTLY sitting in this bin to a different,
    // already-existing target bin (same room, another shelf, or a
    // different room entirely at the same location). The source bin
    // itself is completely untouched — same code, same room, same
    // position — it just ends up with zero parts assigned, ready for
    // new stock. This is deliberately separate from updateShelf()
    // above: renaming a bin and relocating its contents are two
    // different real-world actions and shouldn't be conflated.
    // =========================================================
    public function relocateItems(Request $request, int $shelfId)
    {
        $sourceShelf = DB::table('storage_shelves')->where('id', $shelfId)->first();
        abort_if(!$sourceShelf, 404);

        $request->validate([
            'target_shelf_id' => 'required|exists:storage_shelves,id|different:' . $shelfId,
        ]);

        $targetShelf = DB::table('storage_shelves')->where('id', $request->target_shelf_id)->first();
        abort_if(!$targetShelf, 404);

        $targetRoom = DB::table('storage_rooms')->where('id', $targetShelf->storage_room_id)->first();
        abort_if(!$targetRoom, 404);

        $partsToMove = DB::table('parts_inventory')->where('storage_shelf_id', $shelfId)->get();
        if ($partsToMove->isEmpty()) {
            return back()->with('error', "Bin {$sourceShelf->full_bin_code} has no parts assigned to it — nothing to relocate.");
        }

        DB::table('parts_inventory')->where('storage_shelf_id', $shelfId)->update([
            'storage_shelf_id' => $targetShelf->id,
            'bin_location'     => $targetShelf->full_bin_code,
            'location'         => $targetRoom->location,
            'updated_at'       => now(),
        ]);

        return redirect()->route('admin.storage.show', $sourceShelf->storage_room_id)
            ->with('success', "{$partsToMove->count()} part(s) relocated from {$sourceShelf->full_bin_code} to {$targetShelf->full_bin_code}. {$sourceShelf->full_bin_code} is now empty and ready for new stock.");
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
        // FIXED: was a plain query with no join to storage_rooms — the
        // barcode view references $shelf->room_name, which didn't
        // exist on the raw row and would have thrown an undefined-
        // property error the moment that field was actually used.
        $shelf = DB::table('storage_shelves as ss')
            ->leftJoin('storage_rooms as sr', 'sr.id', '=', 'ss.storage_room_id')
            ->where('ss.id', $shelfId)
            ->select('ss.*', 'sr.name as room_name', 'sr.location', 'sr.code as room_code')
            ->first();
        abort_if(!$shelf, 404);
        return view('admin.storage.bin-barcode', compact('shelf'));
    }

    // =========================================================
    // GET /admin/storage/{roomId}/barcode — a NEW room-level barcode,
    // separate from a bin barcode. Encodes the room's own code
    // (e.g. "FE-LE1"), reusing the exact same 200×90mm/Top-Middle-
    // Bottom-position/checksummed-barcode template as bin labels, so
    // it prints and scans exactly the same way — just for the whole
    // room instead of one bin.
    // =========================================================
    public function roomBarcode(int $roomId)
    {
        $room = DB::table('storage_rooms')->where('id', $roomId)->first();
        abort_if(!$room, 404);

        // Reuses the bin-barcode view/template — it only needs
        // full_bin_code and room_name, so a lightweight stdClass
        // standing in for a "shelf" works without duplicating the
        // whole barcode-rendering template for rooms specifically.
        $shelf = (object) [
            'full_bin_code' => $room->code,
            'room_name'     => $room->name,
        ];

        return view('admin.storage.bin-barcode', compact('shelf'));
    }

    // =========================================================
    // GET /admin/storage/scan?code=X — unified scan-lookup, "just as
    // in bin": type or scan EITHER a room code or a bin code here, and
    // land on the right page — a room code shows every item across
    // every bin in that room; a bin code goes straight to that bin's
    // room page. One lookup box works for both, matching how staff
    // already scan bin codes elsewhere in the app.
    // =========================================================
    public function scanLookup(Request $request)
    {
        $code = trim($request->get('code', ''));
        if ($code === '') {
            return view('admin.storage.scan-lookup', ['error' => null]);
        }

        // Try as a room code first (shorter, e.g. "FE-LE1")
        $room = DB::table('storage_rooms')->where('code', $code)->first();
        if ($room) {
            return redirect()->route('admin.storage.room-contents', $room->id);
        }

        // Fall back to a bin code (e.g. "FE-LE1-A-01-02")
        $shelf = DB::table('storage_shelves')->where('full_bin_code', $code)->first();
        if ($shelf) {
            return redirect()->route('admin.storage.shelves.contents', $shelf->id);
        }

        return view('admin.storage.scan-lookup', ['error' => "Code \"{$code}\" doesn't match any room or bin."]);
    }

    // =========================================================
    // GET /admin/storage/shelves/{shelfId}/contents — every item
    // actually IN this one bin. FIXED: a bin scan previously only
    // landed on the room's bin LIST (showing a count per bin, not
    // the actual items) — this is the real per-bin equivalent of
    // roomContents(), so scanning a bin code now shows exactly what's
    // in it, the same way a room code shows everything in the room.
    // =========================================================
    public function shelfContents(int $shelfId)
    {
        $shelf = DB::table('storage_shelves as ss')
            ->leftJoin('storage_rooms as sr', 'sr.id', '=', 'ss.storage_room_id')
            ->where('ss.id', $shelfId)
            ->select('ss.*', 'sr.name as room_name', 'sr.location')
            ->first();
        abort_if(!$shelf, 404);

        $items = DB::table('parts_inventory')
            ->where('storage_shelf_id', $shelfId)
            ->whereNull('deleted_at')
            ->select('id', 'part_code', 'part_name', 'part_category', 'brand', 'model',
                     'condition_grade', 'stock_qty', 'status')
            ->orderBy('part_name')
            ->get();

        return view('admin.storage.bin-contents', compact('shelf', 'items'));
    }

    // =========================================================
    // GET /admin/storage/{roomId}/contents — every item across every
    // bin in this room, flattened into one list. This is what a room
    // barcode scan actually resolves to.
    // =========================================================
    public function roomContents(int $roomId)
    {
        $room = DB::table('storage_rooms')->where('id', $roomId)->first();
        abort_if(!$room, 404);

        $items = DB::table('parts_inventory as p')
            ->join('storage_shelves as ss', 'ss.id', '=', 'p.storage_shelf_id')
            ->where('ss.storage_room_id', $roomId)
            ->whereNull('p.deleted_at')
            ->select('p.id', 'p.part_code', 'p.part_name', 'p.part_category', 'p.brand', 'p.model',
                     'p.condition_grade', 'p.stock_qty', 'p.status', 'ss.full_bin_code')
            ->orderBy('ss.full_bin_code')
            ->orderBy('p.part_name')
            ->get();

        return view('admin.storage.room-contents', compact('room', 'items'));
    }

    // =========================================================
    // AJAX: GET /admin/storage/all-bins-for-location?location=X
    // Returns every bin across every room at one location, with the
    // room name included — used for per-item bin pickers (e.g. the
    // harvest checklist) where a two-step room-then-bin cascade per
    // row would be too slow/cluttered for dozens of items at once.
    // =========================================================
    public function allBinsForLocation(Request $request)
    {
        $location = $request->get('location', '');
        $keepPartId = $request->get('keep_part_id'); // edit pages: still show this part's own current bin

        $occupants = DB::table('parts_inventory')
            ->whereIn('status', ['Available', 'Reserved', 'Hold'])
            ->whereNotNull('storage_shelf_id')
            ->when($keepPartId, fn($q) => $q->where('id', '!=', $keepPartId))
            ->select('storage_shelf_id', 'part_name', 'part_code')
            ->get()->keyBy('storage_shelf_id');

        $bins = DB::table('storage_shelves as s')
            ->join('storage_rooms as r', 'r.id', '=', 's.storage_room_id')
            ->where('r.location', $location)
            ->select('s.id', 's.full_bin_code', 'r.name as room_name', 'r.code as room_code')
            ->orderBy('r.name')->orderBy('s.full_bin_code')
            ->get()
            ->map(function ($b) use ($occupants) {
                // ── Bins are no longer hidden when occupied — they're
                // marked instead. A small group of related/grouped
                // items CAN deliberately share one bin (e.g. a set of
                // small sensors or fasteners from the same harvest),
                // but the UI must explicitly confirm with staff before
                // allowing it, rather than silently disabling the
                // option or silently allowing the conflict.
                $occ = $occupants[$b->id] ?? null;
                $b->occupied_by = $occ ? "{$occ->part_name} ({$occ->part_code})" : null;
                return $b;
            });

        return response()->json(['bins' => $bins]);
    }

    // =========================================================
    // AJAX: GET /admin/storage/shelves-for-room?room_id=X
    // Used by inventory forms to populate the bin dropdown once a
    // store room is selected. Bins are no longer hidden when
    // occupied — they're marked with the current occupant instead,
    // and the frontend asks staff to explicitly confirm before
    // allowing two parts to deliberately share one bin (e.g. a
    // small group of related items from the same harvest). For
    // truly separate items, create more granular bins via the
    // bulk-generate tool on the room page instead.
    // =========================================================
    public function shelvesForRoom(Request $request)
    {
        $roomId = (int) $request->get('room_id');
        $keepPartId = $request->get('keep_part_id');

        $occupants = DB::table('parts_inventory')
            ->whereIn('status', ['Available', 'Reserved', 'Hold'])
            ->whereNotNull('storage_shelf_id')
            ->when($keepPartId, fn($q) => $q->where('id', '!=', $keepPartId))
            ->select('storage_shelf_id', 'part_name', 'part_code')
            ->get()->keyBy('storage_shelf_id');

        $shelves = DB::table('storage_shelves')
            ->where('storage_room_id', $roomId)
            ->orderBy('shelf_code')->orderBy('column_number')->orderBy('space_number')
            ->get(['id', 'full_bin_code', 'shelf_code', 'column_number', 'space_number'])
            ->map(function ($s) use ($occupants) {
                $occ = $occupants[$s->id] ?? null;
                $s->occupied_by = $occ ? "{$occ->part_name} ({$occ->part_code})" : null;
                return $s;
            });

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
