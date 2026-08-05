<?php
// FILE: app/Http/Controllers/Admin/InventoryController.php
// Updated: pin_count, gear_alias, engine_code_oem, transmission_code_oem,
//          origin_market, fitment_notes, compat years, compatible_trims
// Updated: part_name now restricted to App\Data\PartNames::flat() for
//          non-admin staff, to keep naming uniform across the system.
// Updated: LOCATIONS constant replaced with App\Support\Locations::all()
//          — single source of truth shared with HarvestController and
//          checkout/index.blade.php, so adding/renaming a location only
//          needs to happen in one place (see Locations.php for the bug
//          this fixes: Lagos harvest 500 error from a naming mismatch).
// Updated: photos are now OPTIONAL on manual add, matching Harvest —
//          parts saved without a photo show the AutoZenith default
//          "photo coming soon" image on the customer-facing screen.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Data\PartNames;
use App\Support\Locations;

class InventoryController extends Controller
{
    const BRANDS = [
        'Toyota','Lexus','Kia','Hyundai','Nissan','Mercedes-Benz',
        'Infiniti','Ford','GM','Chevrolet','Acura','VW','Honda',
    ];

   const CATEGORIES = [
    'Engine','Transmission','Body','Suspension','Electrical',
    'Interior','Cooling','Brakes','Airbag','Fuel','Exhaust','Seat','Wheels',
    'Consumable','Electronics','Computers','Other',
];

    // Year range 1986–2027 (item 5)
    private function yearRange(): array
    {
        return range(1986, 2027);
    }

    // ── Part name guard — only admin/manager may submit a name not on the
// ── standard list, to keep nomenclature uniform across staff.
    private function assertAllowedPartName(?string $partName): ?string
    {
        if (!$partName) return 'Part name is required.';

        if (in_array(Session::get('staff_role'), ['admin', 'manager'], true)) {
    return null; // admins and managers may use any name
}

        if (!in_array($partName, PartNames::flat(), true)) {
            return 'Only admin can add a part name that is not on the standard list. Please select a name from the list, or ask an admin to add it.';
        }

        return null;
    }

    // ── List ──────────────────────────────────────────────────────
    public function index(Request $request)
    {
        // FIXED/NEW: joins through storage_shelves -> storage_rooms so
        // the inventory list can filter AND display by room (previously
        // only had location, with no room-level breakdown at all).
        // LEFT JOINs so parts with no shelf/room assigned still show up.
        $query = DB::table('parts_inventory as p')
            ->leftJoin('storage_shelves as ss', 'ss.id', '=', 'p.storage_shelf_id')
            ->leftJoin('storage_rooms as sr', 'sr.id', '=', 'ss.storage_room_id')
            ->select('p.*', 'sr.name as room_name', 'sr.id as room_id', 'ss.full_bin_code')
            ->orderByDesc('p.created_at');

        if ($f = $request->get('brand'))    $query->where('p.brand', $f);
        if ($f = $request->get('category')) $query->where('p.part_category', $f);
        if ($f = $request->get('location')) $query->where('p.location', $f);
        if ($f = $request->get('status'))   $query->where('p.status', $f);
        // NEW: filter inventory down to a specific room, not just location.
        if ($f = $request->get('room'))     $query->where('sr.id', $f);

        if ($f = $request->get('q')) {
            $query->where(function($q) use ($f) {
                $q->where('p.part_name',            'like', "%$f%")
                  ->orWhere('p.part_code',           'like', "%$f%")
                  ->orWhere('p.model',                'like', "%$f%")
                  ->orWhere('p.oem_part_number',      'like', "%$f%")
                  ->orWhere('p.engine_code_oem',      'like', "%$f%")  // item 6
                  ->orWhere('p.transmission_code_oem','like', "%$f%")  // item 6
                  ->orWhere('p.gear_alias',           'like', "%$f%")  // item 7
                  ->orWhere('p.pin_count',            'like', "%$f%")  // item 10
                  // NEW — searchable per this request: our reference,
                  // part category, room name, bin location, and engine
                  // displacement.
                  ->orWhere('p.source_ref',           'like', "%$f%")
                  ->orWhere('p.part_category',        'like', "%$f%")
                  ->orWhere('sr.name',                'like', "%$f%")
                  ->orWhere('ss.full_bin_code',       'like', "%$f%")
                  ->orWhere('p.bin_location',         'like', "%$f%")
                  ->orWhere('p.engine_displacement',  'like', "%$f%");
            });
        }

        $parts  = $query->paginate(30)->withQueryString();
        $counts = DB::table('parts_inventory')
            ->select('status', DB::raw('count(*) as n'))
            ->groupBy('status')->pluck('n','status');

        // NEW: room list for the filter dropdown, scoped to whichever
        // location is currently selected (or all rooms if none picked).
        $roomsQuery = DB::table('storage_rooms')->orderBy('location')->orderBy('name');
        if ($loc = $request->get('location')) {
            $roomsQuery->where('location', $loc);
        }
        $rooms = $roomsQuery->get(['id', 'name', 'location']);

        return view('admin.inventory.index', [
            'parts'      => $parts,
            'counts'     => $counts,
            'brands'     => self::BRANDS,
            'categories' => self::CATEGORIES,
            'locations'  => Locations::all(),
            'rooms'      => $rooms,
        ]);
    }

    // =========================================================
    // POST /admin/inventory/barcodes/bulk — print multiple barcode
    // tags at once from a set of selected inventory rows.
    // NEW: previously barcode() only supported one part_id at a time,
    // so printing tags for a batch meant opening each row individually.
    // =========================================================
    public function barcodeBulk(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:parts_inventory,id',
        ]);

        $parts = DB::table('parts_inventory as p')
            ->leftJoin('storage_shelves as ss', 'ss.id', '=', 'p.storage_shelf_id')
            ->select('p.*', 'ss.full_bin_code')
            ->whereIn('p.id', $request->ids)
            ->get();

        return view('admin.inventory.barcode', compact('parts'));
    }

    // ── Edit form ─────────────────────────────────────────────────
    // =========================================================
    // GET /admin/inventory/{id}/barcode — printable barcode label
    // =========================================================
    public function barcode(int $id)
    {
        // FIXED/NEW: now joins storage_shelves for full_bin_code (item D
        // — bin location needs to print on the tag), and passes a
        // single-item $parts collection so this and barcodeBulk() can
        // share one template instead of maintaining two.
        $part = DB::table('parts_inventory as p')
            ->leftJoin('storage_shelves as ss', 'ss.id', '=', 'p.storage_shelf_id')
            ->select('p.*', 'ss.full_bin_code')
            ->where('p.id', $id)
            ->first();
        abort_if(!$part, 404);
        $parts = collect([$part]);
        return view('admin.inventory.barcode', compact('parts'));
    }

    public function edit(int $id)
    {
        $part = DB::table('parts_inventory')->where('id', $id)->first();
        if (!$part) abort(404);

        // If this part already has a structured bin assigned, fetch its
        // room id so the edit form can preselect Store Room → Bin.
        $currentRoomId = null;
        if ($part->storage_shelf_id) {
            $currentRoomId = DB::table('storage_shelves')
                ->where('id', $part->storage_shelf_id)
                ->value('storage_room_id');
        }

        // ── Interchange (Phase B3) ────────────────────────────────────
        $interchange = new \App\Services\InterchangeService();
        $interchangeGroup    = null;
        $interchangeVehicles = collect();
        $aggregatedStock     = null;
        $heuristicSuggestion = null;

        if ($part->interchange_group_id) {
            $interchangeGroup    = DB::table('part_interchange_groups')->where('id', $part->interchange_group_id)->first();
            $interchangeVehicles = $interchange->vehiclesForGroup($part->interchange_group_id);
            $aggregatedStock     = $interchange->aggregatedStockBreakdown($part->interchange_group_id);
        } else {
            // No group yet — show a live heuristic suggestion if one exists
            $result = $interchange->interchangeFor($part->part_name, $part->engine_code_oem, $part->transmission_code_oem);
            if ($result['found'] && $result['source'] === 'auto_heuristic') {
                $heuristicSuggestion = $result['vehicles'];
            }
        }
        // ──────────────────────────────────────────────────────────────

        // NEW: additional known OEM numbers for this part (see
        // PartOemNumberService) — the primary number keeps living on
        // parts_inventory.oem_part_number unchanged; this is anything
        // beyond that single value.
        $additionalOemNumbers = app(\App\Services\PartOemNumberService::class)->forPart($id);

        return view('admin.inventory.edit', [
            'part'                 => $part,
            'brands'               => self::BRANDS,
            'categories'           => self::CATEGORIES,
            'locations'            => Locations::all(),
            'years'                => $this->yearRange(),
            'currentRoomId'        => $currentRoomId,
            'interchangeGroup'     => $interchangeGroup,
            'interchangeVehicles'  => $interchangeVehicles,
            'aggregatedStock'      => $aggregatedStock,
            'heuristicSuggestion'  => $heuristicSuggestion,
            'additionalOemNumbers' => $additionalOemNumbers,
        ]);
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, int $id)
    {
        $request->validate([
            'part_name'           => 'required|string|max:150',
            // NEW: standardized part taxonomy — see part_terminology
            // table. Optional for now (legacy rows / not-yet-catalogued
            // terms still work via free-typed part_name), but when
            // provided, it becomes the authoritative name — see below.
            'part_terminology_id' => 'nullable|exists:part_terminology,id',
            // NEW: category was never editable at all after a part was
            // saved — if something got harvested/created into the wrong
            // category group, the only fix was delete-and-recreate.
            // Kept broad (not a fixed enum) since the app uses many real
            // category names beyond the Consumable-specific list used
            // at creation time (Engine, Transmission, Body, Suspension,
            // Electrical, etc.) — matching what's actually stored today.
            'part_category'       => 'nullable|string|max:50',
            'price_usd'           => 'required|numeric|min:0',
            'condition_grade'     => 'required|in:A,B,C,New',
            'status'              => 'required|in:Available,Reserved,Sold,Missing,Hold,Core,Scrapped',
            'location'            => 'required|string',
            'description'         => 'nullable|string|max:1000',
            'oem_part_number'     => 'nullable|string|max:80',
            'mileage'             => 'nullable|integer|min:0',
            'colour'              => 'nullable|string|max:50',
            // Bin location is now REQUIRED (#13) — every part must
            // have a real physical bin assigned at the moment it's
            // entered into inventory, no exceptions.
            'storage_shelf_id'    => 'nullable|exists:storage_shelves,id',
            'bin_location'        => 'nullable|string|max:20',
            'engine_code_oem'     => 'nullable|string|max:30',
            // NEW: persists engine displacement (e.g. "2.5L", "3.5L V6")
            // so it's visible and searchable on inventory, not just used
            // transiently during harvest-entry lookups.
            'engine_displacement' => 'nullable|string|max:20',
            'transmission_code_oem'=> 'nullable|string|max:30',
            'pin_count'           => 'nullable|integer|min:1|max:99',
            // FIXED: this capped at 50 chars even after the DB column
            // was widened to TEXT to fix the truncation error — editing
            // a part with a long gear alias (e.g. "20-pin gear (Camry
            // 2010-11 — early 2AR-FE, distinct from 2012+ 22-pin)")
            // would fail validation before ever reaching the database.
            'gear_alias'          => 'nullable|string|max:1000',
            'origin_market'       => 'nullable|in:JDM,USDM,EDM,Nigerian Used,N/A',
            'fitment_notes'       => 'nullable|string',
            'compat_year_from'    => 'nullable|integer|min:1986|max:2027',
            'compat_year_to'      => 'nullable|integer|min:1986|max:2027',
            'compatible_trims'    => 'nullable|string|max:200',
            'not_compatible_note' => 'nullable|string|max:200',
            'source_ref'          => 'nullable|string|max:6',
            // FIXED: stock_qty was completely missing from this method
            // — not validated, not saved. Editing a part could never
            // actually change its quantity at all, regardless of what
            // the form submitted.
            'stock_qty'           => 'nullable|integer|min:0|max:999',
        ]);

        // ── Part name guard (admin-only for unlisted names) ──────────
        // FIXED: this used to fire on EVERY save, even when part_name
        // wasn't being changed at all — meaning editing price, condition,
        // photos, etc. on an existing part with a legitimately-saved
        // custom name would incorrectly block the entire save. Now only
        // checked when the submitted name actually differs from what's
        // already in the database for this part.
        $existingPartName = DB::table('parts_inventory')->where('id', $id)->value('part_name');
        if ($request->part_name !== $existingPartName) {
            if ($err = $this->assertAllowedPartName($request->part_name)) {
                return back()->withErrors(['part_name' => $err])->withInput();
            }
        }
        // ──────────────────────────────────────────────────────────────

        // ── Location must be a recognized value from the single
        // source of truth — same validation added to Harvest, to
        // prevent any future mismatch reaching the database.
        if (!Locations::isValid($request->location)) {
            return back()->withInput()->withErrors(['location' => "\"{$request->location}\" is not a recognized location."]);
        }
        // ──────────────────────────────────────────────────────────────

        // ── Bin exclusivity — RE-VERIFIED at save time, not just at
        // dropdown-load time. The dropdown already hides occupied
        // bins when the page loads, but a stale page, browser back
        // button, or another staff member claiming the same bin in
        // the meantime could otherwise still slip an occupied bin
        // through. A bin is normally one-part-only — only the ROOM
        // is allowed to hold multiple parts/bins — UNLESS staff has
        // explicitly confirmed (via the "are you sure?" prompt on
        // selecting an already-occupied bin) that this is a
        // deliberate grouped-items exception.
        // Fetch the part's CURRENT bin before any changes — if the
        // submitted bin is identical to what it already was, this is
        // not a new move and should never trigger a fresh conflict
        // warning, even if another part has legitimately shared that
        // same bin since before this edit (historical grouped items).
        $currentPart = DB::table('parts_inventory')->where('id', $id)->first();
        $binIsUnchanged = $currentPart && (int)($currentPart->storage_shelf_id ?? 0) === (int)($request->storage_shelf_id ?? 0);

        if ($request->storage_shelf_id && !$binIsUnchanged && !$request->boolean('confirm_shared_bin')) {
            $conflictingPart = DB::table('parts_inventory')
                ->where('storage_shelf_id', $request->storage_shelf_id)
                ->where('id', '!=', $id) // a part keeping its OWN current bin is fine
                ->whereIn('status', ['Available', 'Reserved', 'Hold'])
                ->first();

            if ($conflictingPart) {
                return back()->withInput()->withErrors([
                    'storage_shelf_id' => "That bin is already occupied by {$conflictingPart->part_name} ({$conflictingPart->part_code}). Choose a different bin, or move/sell that part first."
                ]);
            }
        }
        // ──────────────────────────────────────────────────────────────

        // ── Fixed currency by location — the price staff types is the
        // authoritative, never-recalculated value in that location's
        // native currency. price_usd is updated only as a fresh
        // snapshot for cross-location reporting, never used for
        // display going forward. (The edit form field is still named
        // price_usd for historical reasons, but is now correctly
        // pre-filled with price_local — this was the bug causing
        // prices to drift on every save.)
        $currency   = \App\Http\Controllers\Admin\InvoiceController::currencyForLocation($request->location);
        $priceLocal = (float) $request->price_usd; // form field name kept for now — see note below
        $priceUsdSnapshot = $priceLocal / $currency['rate'];
        // ──────────────────────────────────────────────────────────────

        // NEW: when a standardized terminology is selected, its
        // standard_name becomes the authoritative part_name — this is
        // the actual point of the taxonomy (prevents "Alternator" /
        // "Alternator Assembly" / "ALT" fragmenting into three
        // different-looking parts). Falls back to whatever was typed
        // if no terminology was selected (legacy behavior, unchanged).
        $resolvedPartName = $request->part_name;
        if ($request->part_terminology_id) {
            $term = DB::table('part_terminology')->where('id', $request->part_terminology_id)->first();
            if ($term) $resolvedPartName = $term->standard_name;
        }

        DB::table('parts_inventory')->where('id', $id)->update([
            'part_name'              => $resolvedPartName,
            'part_terminology_id'    => $request->part_terminology_id,
            // NEW: category can now actually be corrected on edit — was
            // completely absent from this method before, meaning a part
            // harvested into the wrong category group had no fix short
            // of delete-and-recreate. Only updates if the field was
            // actually submitted, so this stays a safe no-op until the
            // edit form itself is updated with a category selector.
            ...($request->has('part_category') && $request->part_category ? ['part_category' => $request->part_category] : []),
            'price_usd'              => $priceUsdSnapshot,
            'price_local'            => $priceLocal,
            'currency_code'          => $currency['code'],
            'condition_grade'        => $request->condition_grade,
            'status'                 => $request->status,
            'location'               => $request->location,
            'description'            => $request->description,
            'oem_part_number'        => $request->oem_part_number,
            'source_ref'             => $request->source_ref ? substr(trim($request->source_ref), 0, 6) : null,
            'mileage'                => $request->mileage,
            'colour'                 => $request->colour,
            'bin_location'           => $request->bin_location,
            'storage_shelf_id'       => $request->storage_shelf_id ?: null,
            'engine_code_oem'        => $request->engine_code_oem
                                          ? strtoupper(trim($request->engine_code_oem)) : null,
            // NEW: persisted engine displacement (item E of this request).
            'engine_displacement'    => $request->engine_displacement
                                          ? trim($request->engine_displacement) : null,
            'transmission_code_oem'  => $request->transmission_code_oem
                                          ? strtoupper(trim($request->transmission_code_oem)) : null,
            'pin_count'              => $request->pin_count,
            'gear_alias'             => $request->gear_alias,
            'drive_type'             => $request->drive_type ?: null,
            'origin_market'          => $request->origin_market ?? 'N/A',
            'fitment_notes'          => $request->fitment_notes,
            'compat_year_from'       => $request->compat_year_from,
            'compat_year_to'         => $request->compat_year_to,
            'compatible_trims'       => $request->compatible_trims,
            'not_compatible_note'    => $request->not_compatible_note,
            // FIXED: was never included, so quantity edits were
            // silently discarded no matter what staff entered.
            // Only update if the field was actually submitted —
            // if the edit form doesn't have this field yet, this
            // stays a no-op rather than accidentally zeroing stock.
            ...($request->has('stock_qty') ? ['stock_qty' => $request->stock_qty] : []),
            'updated_at'             => now(),
        ]);

        // NEW: keep part_oem_numbers' primary row in sync whenever this
        // form edits the primary field directly — otherwise the new
        // table would silently drift from parts_inventory.oem_part_number.
        if ($request->filled('oem_part_number')) {
            $oemService = app(\App\Services\PartOemNumberService::class);
            $existingPrimary = DB::table('part_oem_numbers')->where('parts_inventory_id', $id)->where('is_primary', true)->first();
            if ($existingPrimary) {
                DB::table('part_oem_numbers')->where('id', $existingPrimary->id)->update([
                    'oem_number' => trim($request->oem_part_number), 'updated_at' => now(),
                ]);
            } else {
                $oemService->add($id, $request->oem_part_number);
            }
        }

        return redirect()->route('admin.inventory.index')
            ->with('success', 'Part updated successfully.');
    }

    // =========================================================
    // AJAX: POST /admin/inventory/{id}/oem-numbers — add an
    // additional known OEM number for a part.
    // =========================================================
    public function addOemNumber(Request $request, int $id)
    {
        $request->validate([
            'oem_number'   => 'required|string|max:60',
            'manufacturer' => 'nullable|string|max:60',
        ]);

        $part = DB::table('parts_inventory')->where('id', $id)->first();
        abort_if(!$part, 404);

        $exists = DB::table('part_oem_numbers')
            ->where('parts_inventory_id', $id)
            ->where('oem_number', trim($request->oem_number))
            ->exists();
        if ($exists) {
            return response()->json(['error' => 'This OEM number is already recorded for this part.'], 422);
        }

        $oemId = app(\App\Services\PartOemNumberService::class)->add($id, $request->oem_number, $request->manufacturer);

        return response()->json(['success' => true, 'id' => $oemId]);
    }

    // =========================================================
    // AJAX: DELETE /admin/inventory/oem-numbers/{oemNumberId}
    // =========================================================
    public function removeOemNumber(int $oemNumberId)
    {
        app(\App\Services\PartOemNumberService::class)->remove($oemNumberId);
        return response()->json(['success' => true]);
    }

    // ── Quick status update (AJAX) ────────────────────────────────
    // FIXED (item E): this used to blindly flip the whole row to
    // 'Sold' regardless of stock_qty — so a consumable/multi-qty row
    // (e.g. stock_qty=20) marked "Sold" for 2 units sold showed the
    // ENTIRE batch as Sold, incorrectly zeroing out 18 units that
    // were still physically in stock.
    //
    // Now: selling deducts qty_sold from stock_qty. The row only
    // flips to 'Sold' once stock_qty actually reaches 0. Non-quantity
    // statuses (Reserved, Missing, Hold, Core, Scrapped) behave exactly
    // as before — qty_sold is only relevant when status=Sold.
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status'   => 'required|in:Available,Reserved,Sold,Missing,Hold,Core,Scrapped',
            // Optional — omitted or null means "sell/set the whole
            // remaining quantity", preserving old behavior for single
            // -unit parts (engines, transmissions, etc.) where this
            // question never applies.
            'qty_sold' => 'nullable|integer|min:1',
        ]);

        $part = DB::table('parts_inventory')->where('id', $id)->first();
        abort_if(!$part, 404);

        // Non-Sold status changes are unaffected — no quantity math needed.
        if ($request->status !== 'Sold') {
            DB::table('parts_inventory')->where('id', $id)->update([
                'status' => $request->status, 'updated_at' => now(),
            ]);
            return response()->json(['success' => true]);
        }

        $currentQty = (int) ($part->stock_qty ?? 1);
        $qtySold    = (int) ($request->qty_sold ?? $currentQty); // no qty given = selling all remaining

        if ($qtySold > $currentQty) {
            return response()->json([
                'error' => "Cannot sell {$qtySold} — only {$currentQty} in stock.",
            ], 422);
        }

        $remaining = $currentQty - $qtySold;

        DB::table('parts_inventory')->where('id', $id)->update([
            'stock_qty'  => $remaining,
            // Only mark the row Sold once nothing is left. Partial
            // sales keep the part Available so it still shows up in
            // searches/orders for the remaining quantity.
            'status'     => $remaining === 0 ? 'Sold' : 'Available',
            'updated_at' => now(),
        ]);

        return response()->json([
            'success'   => true,
            'sold_qty'  => $qtySold,
            'remaining' => $remaining,
            'status'    => $remaining === 0 ? 'Sold' : 'Available',
        ]);
    }

    // ── Delete ────────────────────────────────────────────────────
    public function destroy(Request $request, int $id)
    {
        $role = Session::get('staff_role');

        if (!in_array($role, ['admin', 'manager'])) {
            // Staff/Supervisor need a logged Supervisor-or-above PIN
            // approval before a part can be deleted from inventory.
            $request->validate(['override_token' => 'required|string']);
            $validApproval = DB::table('override_logs')
                ->where('action', 'delete_inventory_part')
                ->where('context', 'like', "%part #{$id}%")
                ->where('requested_by_staff_id', Session::get('staff_id'))
                ->where('created_at', '>=', now()->subMinutes(5))
                ->whereNotIn('approved_by_role', ['UNKNOWN'])
                ->exists();

            if (!$validApproval) {
                return back()->with('error', 'A valid Supervisor/Manager/Admin PIN approval is required to delete a part.');
            }
        }

        DB::table('parts_inventory')->where('id', $id)->delete();
        return redirect()->route('admin.inventory.index')->with('success', 'Part deleted.');
    }

    // =========================================================
    // GET /admin/inventory/oem-lookup?make=...&model=...&year=...
    // Auto-populate OEM/Technical Details based on Make/Model/Year
    // for manual entry (no VIN). Checks existing inventory records
    // for this vehicle first (real data already on file), then
    // falls back to App\Data\OemDatabase::lookup() as a suggestion.
    // =========================================================
    public function oemLookup(Request $request)
    {
        $make     = trim($request->get('make', ''));
        $model    = trim($request->get('model', ''));
        $year     = (int) $request->get('year', 0);
        $engineL  = $request->get('engine_l') ? (float) $request->get('engine_l') : 0;

        if ($make === '' || $model === '' || $year === 0) {
            return response()->json(['source' => null]);
        }

        // ── 1. Check existing inventory for this exact vehicle first ──
        $existingQuery = DB::table('parts_inventory')
            ->where('brand', $make)
            ->where('model', $model)
            ->where('year_from', '<=', $year)
            ->where('year_to', '>=', $year)
            ->where(function ($q) {
                $q->whereNotNull('engine_code_oem')
                  ->orWhereNotNull('transmission_code_oem');
            });

        $existing = $existingQuery->select('engine_code_oem', 'transmission_code_oem', 'pin_count', 'gear_alias')
            ->limit(50)
            ->get();

        // Flag if this vehicle has more than one distinct engine code on file —
        // front-end should prompt for Engine Size (L) to disambiguate.
        $distinctEngines = $existing->pluck('engine_code_oem')->filter()->unique()->values();
        $multipleEngines = $distinctEngines->count() > 1;

        if ($existing->isNotEmpty()) {
            // If engine size was given and we have multiple options, narrow
            // to the matching one using App\Data\OemDatabase as the reference
            // for which engine code corresponds to that displacement.
            $filtered = $existing;
            if ($multipleEngines && $engineL > 0) {
                $refOem = \App\Data\OemDatabase::lookup($make, $model, $year, 0, $engineL);
                if (!empty($refOem['engine_code'])) {
                    $narrowed = $existing->where('engine_code_oem', $refOem['engine_code']);
                    if ($narrowed->isNotEmpty()) $filtered = $narrowed;
                }
            }

            $engineCode = $filtered->pluck('engine_code_oem')->filter()->countBy()->sortDesc()->keys()->first();
            $transCode  = $filtered->pluck('transmission_code_oem')->filter()->countBy()->sortDesc()->keys()->first();
            $pinCount   = $filtered->where('transmission_code_oem', $transCode)->pluck('pin_count')->filter()->first();
            $gearAlias  = $filtered->where('transmission_code_oem', $transCode)->pluck('gear_alias')->filter()->first();

            return response()->json([
                'source'           => 'inventory',
                'match_count'      => $filtered->count(),
                'multiple_engines' => $multipleEngines,
                'engine_code'      => $engineCode,
                'transmission_code'=> $transCode,
                'pin_count'        => $pinCount,
                'gear_alias'       => $gearAlias,
            ]);
        }

        // ── 2. Fallback: OemDatabase suggestion (not yet confirmed by stock) ──
        $oem = \App\Data\OemDatabase::lookup($make, $model, $year, 0, $engineL);

        return response()->json([
            'source'            => 'suggestion',
            'match_count'       => 0,
            'multiple_engines'  => false,
            'engine_code'       => $oem['engine_code']       ?? null,
            'transmission_code' => $oem['transmission_code'] ?? null,
            'pin_count'         => $oem['pin_count']         ?? null,
            'gear_alias'        => $oem['gear_alias']        ?? null,
        ]);
    }

    // =========================================================
    // GET /admin/inventory/backfill-drive-type — one-time cleanup
    // tool (admin/manager only). Lists every Transmission /
    // "Complete Engine And Gear With Accessories" part missing
    // drive_type, since that field never had a real form input
    // anywhere until the Harvest/Manual Add fixes — existing parts
    // saved before that simply never had a chance to capture it.
    // No auto-fill possible here (no OEM data source for 2WD vs
    // 4WD), so this is purely a faster way to fill it in by hand
    // than opening 50+ individual Edit pages one at a time.
    // =========================================================
    public function backfillDriveTypeForm()
    {
        if (!in_array(Session::get('staff_role'), ['admin', 'manager'])) {
            abort(403, 'Only admin or manager accounts can use this tool.');
        }

        $parts = DB::table('parts_inventory')
            ->where(function ($q) {
                $q->where('part_category', 'Transmission')
                  ->orWhere('part_name', 'Complete Engine And Gear With Accessories');
            })
            ->whereNull('drive_type')
            ->orderBy('brand')->orderBy('model')->orderBy('year_from')
            ->get(['id', 'part_code', 'part_name', 'brand', 'model', 'year_from', 'year_to',
                   'transmission_code_oem', 'pin_count', 'gear_alias']);

        return view('admin.inventory.backfill-drive-type', compact('parts'));
    }

    // =========================================================
    // POST /admin/inventory/backfill-drive-type — saves only the
    // rows that were actually set; anything left blank is skipped
    // so this can be done across multiple sessions without forcing
    // a value on parts you haven't gotten to yet.
    // =========================================================
    public function backfillDriveTypeSave(Request $request)
    {
        if (!in_array(Session::get('staff_role'), ['admin', 'manager'])) {
            abort(403, 'Only admin or manager accounts can use this tool.');
        }

        $driveTypes = $request->input('drive_type', []); // [part_id => value]
        $updated = 0;

        foreach ($driveTypes as $partId => $value) {
            if (empty($value)) continue; // skip rows left blank — do them later
            DB::table('parts_inventory')->where('id', $partId)->update([
                'drive_type' => $value,
                'updated_at' => now(),
            ]);
            $updated++;
        }

        return back()->with('success', "{$updated} part(s) updated. Reload this page to see what's left.");
    }

    // ── Create form ───────────────────────────────────────────────
    public function create()
    {
        return view('admin.inventory.create', [
            'brands'     => self::BRANDS,
            'categories' => self::CATEGORIES,
            'locations'  => Locations::all(),
            'years'      => $this->yearRange(),
        ]);
    }
    // ── Manual add form ───────────────────────────────────────────
    public function manualAdd()
    {
        return view('admin.inventory.manual-add', [
            'locations' => Locations::all(),
        ]);
    }

    // =========================================================
    // GET /admin/inventory/{id}/photos
    // Redirects to edit page — photos are managed from the edit form
    // =========================================================
    public function photos(int $id)
    {
        $part = DB::table('parts_inventory')->where('id', $id)->first();
        if (!$part) abort(404);
        return redirect()->route('admin.inventory.edit', $id)
            ->with('info', 'Add and manage photos from the edit page below.');
    }

    // =========================================================
    // GET /admin/inventory/consumable/create — simple dedicated form
    // =========================================================
    public function consumableCreate()
    {
        return view('admin.inventory.consumable-create', [
            'locations'     => Locations::all(),
            'customBrands'  => DB::table('custom_brands')->orderBy('name')->pluck('name'),
        ]);
    }

    // =========================================================
    // POST /admin/inventory/consumable — store a consumable item
    // No vehicle fields (model/year/OEM codes) — just product info
    // =========================================================
    public function consumableStore(Request $request)
    {
        $request->validate([
            'brand'              => 'required|string|max:80',
            'other_brand'        => 'nullable|string|max:60',
            'part_name'          => 'required|string|max:150',
            'part_category'      => 'nullable|string|in:Consumable,Electronics,Computers,Other',
            'unit_size'          => 'nullable|string|max:30',
            'compatibility_note' => 'nullable|string|max:200',
            'price_usd'          => 'required|numeric|min:0',
            'condition_grade'    => 'required|in:A,B,C,New',
            'location'           => 'required|string',
            'storage_shelf_id'   => 'nullable|exists:storage_shelves,id', // bin now optional — room-level storage allowed
            'stock_qty'          => 'nullable|integer|min:1',
        ]);

        // ── Part name guard (admin-only for unlisted names) ──────────
        if ($err = $this->assertAllowedPartName($request->part_name)) {
            return back()->withErrors(['part_name' => $err])->withInput();
        }
        // ──────────────────────────────────────────────────────────────

        if (!Locations::isValid($request->location)) {
            return back()->withInput()->withErrors(['location' => "\"{$request->location}\" is not a recognized location."]);
        }

        // ── Bin exclusivity — re-verified at save time (new part, so
        // any active occupant of this bin is a real conflict), unless
        // staff explicitly confirmed sharing it deliberately. ──
        if ($request->storage_shelf_id && !$request->boolean('confirm_shared_bin')) {
            $conflictingConsumablePart = DB::table('parts_inventory')
                ->where('storage_shelf_id', $request->storage_shelf_id)
                ->whereIn('status', ['Available', 'Reserved', 'Hold'])
                ->first();
            if ($conflictingConsumablePart) {
                return back()->withInput()->withErrors([
                    'storage_shelf_id' => "That bin is already occupied by {$conflictingConsumablePart->part_name} ({$conflictingConsumablePart->part_code}). Choose a different bin."
                ]);
            }
        }
        // ──────────────────────────────────────────────────────────────

        // Typed brand not in our known list: JS already sends brand=Generic
        // with the real name in other_brand — fold it into part_name.
        $brand    = $request->brand;
        $partName = $request->part_name;
        if ($request->other_brand) {
            $typedBrand = trim($request->other_brand);
            $partName   = $typedBrand . ' - ' . $partName;

            // Remember this custom brand so it's offered as a suggestion
            // next time, instead of needing to be retyped from scratch.
            if ($typedBrand !== '' && !DB::table('custom_brands')->where('name', $typedBrand)->exists()) {
                DB::table('custom_brands')->insert([
                    'name'       => $typedBrand,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // ── Fixed currency by location (needed early now, for match) ──
        $currency   = \App\Http\Controllers\Admin\InvoiceController::currencyForLocation($request->location);
        $priceLocal = (float) $request->price_usd; // form field name kept for now — see note below

        // ── Duplicate detection — same product already in stock at this
        // location/price gets its quantity bumped instead of creating a
        // duplicate row with its own part code. ──
        $qtyToAdd = (int) ($request->stock_qty ?? 1);
        $existing = DB::table('parts_inventory')
            ->where('brand', $brand)
            ->where('part_name', $partName)
            ->where('unit_size', $request->unit_size)
            ->where('condition_grade', $request->condition_grade)
            ->where('location', $request->location)
            ->where('price_local', $priceLocal)
            ->where('status', 'Available')
            ->first();

        if ($existing) {
            $newQty = $existing->stock_qty + $qtyToAdd;
            DB::table('parts_inventory')->where('id', $existing->id)->update([
                'stock_qty'  => $newQty,
                'updated_at' => now(),
            ]);
            return redirect()->route('admin.inventory.index')
                ->with('success', "Added {$qtyToAdd} more unit(s) to existing stock {$existing->part_code} — now {$newQty} in stock.");
        }

        $prefix   = 'CON';
        $lastCode = DB::table('parts_inventory')
            ->where('part_code', 'like', $prefix.'-%')
            ->orderByDesc('id')->value('part_code');
        $nextNum  = $lastCode ? (int) substr($lastCode, strlen($prefix)+1) + 1 : 1;
        $partCode = $prefix.'-'.str_pad($nextNum, 5, '0', STR_PAD_LEFT);

        $priceUsdSnapshot = $priceLocal / $currency['rate'];
        // ──────────────────────────────────────────────────────────────

        DB::table('parts_inventory')->insert([
            'part_code'           => $partCode,
            'brand'                => $brand,
            'model'                => 'Universal',
            'year_from'            => 1990,
            'year_to'              => 2030,
            'compat_year_from'     => null,
            'compat_year_to'       => null,
            'part_name'            => $partName,
            'unit_size'            => $request->unit_size,
            'compatibility_note'   => $request->compatibility_note,
            'part_category'        => $request->part_category ?? 'Consumable',
            'side'                 => 'N/A',
            'condition_grade'      => $request->condition_grade,
            'price_usd'            => $priceUsdSnapshot,
            'price_local'          => $priceLocal,
            'currency_code'        => $currency['code'],
            'location'             => $request->location,
            'origin_market'        => 'N/A',
            'description'          => $request->description,
            'stock_qty'            => $request->stock_qty ?? 1,
            'status'               => 'Available',
            'photos'               => '[]',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return redirect()->route('admin.inventory.index')
            ->with('success', "Consumable item {$partCode} added to inventory.");
    }
    // ── Store manually entered part ───────────────────────────────
    public function store(Request $request)
    {
        $isConsumable = $request->part_category === 'Consumable';

        // Photos are OPTIONAL now — synchronized with HarvestController's
        // saveParts(). Staff can save a part with zero photos and add
        // them later; the customer-facing screen falls back to the
        // AutoZenith default "photo coming soon" image automatically.
        $rules = [
            'brand'          => 'required|string',
            'part_name'      => 'required|string|max:150',
            // NEW: same standardized taxonomy as update() — optional
            // for now so entry isn't blocked while the terminology list
            // is still growing to cover everything staff encounter.
            'part_terminology_id' => 'nullable|exists:part_terminology,id',
            'part_category'  => 'required|string',
            'price_usd'      => 'required|numeric|min:0',
            'condition_grade'=> 'required|in:A,B,C,New',
            'location'       => 'required|string',
            'storage_shelf_id' => 'nullable|exists:storage_shelves,id',
            'storage_room_id'  => 'nullable|integer',
            'photos'         => 'nullable|array',
            'photos.*'       => 'image|max:8192',
            'video'          => 'nullable|file|mimes:mp4,mov,avi,webm|max:51200', // 50MB
            'source_ref'     => 'nullable|string|max:6',
        ];

        if ($isConsumable) {
            $rules['unit_size'] = 'nullable|string|max:30';
            $rules['stock_qty'] = 'nullable|integer|min:1';
        } else {
            $rules['model']     = 'required|string|max:80';
            // #16 fix — only the ACTUAL year of the vehicle this part was
            // pulled from is required (a single value), matching Harvest's
            // pattern (donor vehicle has one real year). Compatibility
            // RANGE is a separate, optional concept below — previously
            // this form conflated the two by requiring a "Year From" /
            // "Year To" pair here, which had staff typing a compatibility
            // guess into what should have been the actual donor year.
            $rules['year']            = 'required|integer|min:1986|max:2027';
            $rules['compat_year_from'] = 'nullable|integer|min:1986|max:2027';
            $rules['compat_year_to']   = 'nullable|integer|min:1986|max:2027';
        }

        $request->validate($rules);

        // ── Part name guard (admin-only for unlisted names) ──────────
        if ($err = $this->assertAllowedPartName($request->part_name)) {
            return back()->withErrors(['part_name' => $err])->withInput();
        }
        // ──────────────────────────────────────────────────────────────

        if (!Locations::isValid($request->location)) {
            return back()->withInput()->withErrors(['location' => "\"{$request->location}\" is not a recognized location."]);
        }

        // ── Bin exclusivity — re-verified at save time, not just at
        // dropdown-load time. A bin is normally one-part-only — only
        // the ROOM is allowed to hold multiple parts/bins — unless
        // staff explicitly confirmed a deliberate grouped-items
        // exception via the "are you sure?" prompt.
        if ($request->storage_shelf_id && !$request->boolean('confirm_shared_bin')) {
            $conflictingPart = DB::table('parts_inventory')
                ->where('storage_shelf_id', $request->storage_shelf_id)
                ->whereIn('status', ['Available', 'Reserved', 'Hold'])
                ->first();
            if ($conflictingPart) {
                return back()->withInput()->withErrors([
                    'storage_shelf_id' => "That bin is already occupied by {$conflictingPart->part_name} ({$conflictingPart->part_code}). Choose a different bin."
                ]);
            }
        }
        // ──────────────────────────────────────────────────────────────

        // ── Fixed currency by location — the price staff types is now
        // the authoritative, never-recalculated value in that location's
        // native currency. price_usd is stored once as a snapshot only,
        // never used for display going forward.
        $currency   = \App\Http\Controllers\Admin\InvoiceController::currencyForLocation($request->location);
        $priceLocal = (float) $request->price_usd; // form field name kept for now — see note below
        $priceUsdSnapshot = $priceLocal / $currency['rate'];
        // ──────────────────────────────────────────────────────────────

        $prefix   = $isConsumable ? 'CON' : substr(strtoupper($request->part_category), 0, 3);
        $lastCode = DB::table('parts_inventory')
            ->where('part_code','like',$prefix.'-%')
            ->orderByDesc('id')->value('part_code');
        $nextNum  = $lastCode ? (int) substr($lastCode, strlen($prefix)+1) + 1 : 1;
        $partCode = $prefix.'-'.str_pad($nextNum, 5, '0', STR_PAD_LEFT);

        // Consumables don't have a real vehicle year — use a wide placeholder
        // range since year_from/year_to are NOT NULL columns.
        //
        // #16 fix — for real parts, year_from/year_to now both store the
        // SAME single actual donor-vehicle year (matching what Harvest
        // does), not a staff-guessed range. The compatibility range shown
        // to customers is the separate compat_year_from/compat_year_to
        // pair below, defaulting to the actual year if left blank.
        $actualYear = $isConsumable ? null : (int) $request->year;
        $yearFrom = $isConsumable ? 1990 : $actualYear;
        $yearTo   = $isConsumable ? 2030 : $actualYear;

        // NEW: same authoritative-name resolution as update() — a
        // selected terminology's standard_name wins over free-typed
        // text, keeping naming consistent industry-wide rather than
        // per-staff-member.
        $resolvedPartName = $request->part_name;
        if ($request->part_terminology_id) {
            $term = DB::table('part_terminology')->where('id', $request->part_terminology_id)->first();
            if ($term) $resolvedPartName = $term->standard_name;
        }

        $partId = DB::table('parts_inventory')->insertGetId([
            'part_code'              => $partCode,
            'brand'                  => $request->brand,
            'model'                  => $isConsumable ? ($request->model ?: 'Universal') : $request->model,
            'year_from'              => $yearFrom,
            'year_to'                => $yearTo,
            'compat_year_from'       => $isConsumable ? null : ($request->compat_year_from ?? $actualYear),
            'compat_year_to'         => $isConsumable ? null : ($request->compat_year_to   ?? $actualYear),
            'part_name'              => $resolvedPartName,
            'part_terminology_id'    => $request->part_terminology_id,
            'unit_size'              => $request->unit_size,
            'compatibility_note'     => $request->compatibility_note,
            'part_category'          => $request->part_category,
            'side'                   => $request->side ?? 'N/A',
            'condition_grade'        => $request->condition_grade,
            'price_usd'              => $priceUsdSnapshot,
            'price_local'            => $priceLocal,
            'currency_code'          => $currency['code'],
            'location'               => $request->location,
            'storage_shelf_id'       => $request->storage_shelf_id ?: null,
            'bin_location'           => $request->bin_location,
            'oem_part_number'        => $request->oem_part_number,
            'source_ref'             => $request->source_ref ? substr(trim($request->source_ref), 0, 6) : null,
            'engine_code_oem'        => $request->engine_code_oem
                                          ? strtoupper(trim($request->engine_code_oem)) : null,
            // NEW: persisted engine displacement (item E of this request).
            'engine_displacement'    => $request->engine_displacement
                                          ? trim($request->engine_displacement) : null,
            'transmission_code_oem'  => $request->transmission_code_oem
                                          ? strtoupper(trim($request->transmission_code_oem)) : null,
            'pin_count'              => $request->pin_count,
            'gear_alias'             => $request->gear_alias,
            'drive_type'             => $request->drive_type ?: null,
            'origin_market'          => $request->origin_market ?? 'N/A',
            'fitment_notes'          => $request->fitment_notes,
            'compatible_trims'       => $request->compatible_trims,
            'not_compatible_note'    => $request->not_compatible_note,
            'mileage'                => $request->mileage,
            'colour'                 => $request->colour,
            'description'            => $request->description,
            'stock_qty'              => $isConsumable ? ($request->stock_qty ?? 1) : 1,
            'status'                 => 'Available',
            'photos'                 => '[]',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        // ── Photo upload — OPTIONAL. If none uploaded, photos stays
        // '[]' and the customer-facing screen shows the AutoZenith
        // default image instead (see App\Support\PartMedia / the
        // x-part-photo / x-part-gallery Blade components).
        if ($request->hasFile('photos')) {
            $this->storePartPhotos($partId, $request->file('photos'));
        }

        // ── Optional single video per part ──
        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store("parts/{$partId}/video", 'public');
            DB::table('parts_inventory')->where('id', $partId)->update(['video_path' => $videoPath]);
        }

        // NEW: additional OEM numbers submitted alongside creation —
        // e.g. this alternator is known to match both a Denso AND an
        // Aisin OEM number from the start. Optional; most parts only
        // ever have the single primary field above filled in.
        //
        // IMPORTANT ordering: seed the primary row from the main
        // oem_part_number field FIRST, so PartOemNumberService sees an
        // existing primary and correctly marks every row from the loop
        // below as non-primary — otherwise the FIRST additional row
        // would get marked primary instead and silently overwrite the
        // real primary value on parts_inventory.
        $oemService = app(\App\Services\PartOemNumberService::class);
        if ($request->filled('oem_part_number')) {
            $oemService->add($partId, $request->oem_part_number);
        }
        if ($request->has('oem_numbers')) {
            foreach ($request->oem_numbers as $row) {
                $number = trim($row['number'] ?? '');
                if ($number === '') continue;
                $oemService->add($partId, $number, trim($row['manufacturer'] ?? '') ?: null);
            }
        }

        $msg = $isConsumable
            ? "Consumable item {$partCode} added to inventory."
            : "Part {$partCode} added to inventory.";

        return redirect()->route('admin.inventory.index')->with('success', $msg);
    }

    // =========================================================
    // Shared helper — saves uploaded photo files for a part into
    // storage/app/public/parts/{id}/ and appends them to the
    // existing photos JSON column (array of relative paths,
    // first entry treated as primary/display photo).
    // =========================================================
    // =========================================================
    // AJAX: GET /admin/inventory/terminology?category=Engine
    // Returns the standardized part-name options for a category, for
    // whatever dropdown/autocomplete UI ends up wired to the entry
    // forms. Consumable is intentionally excluded — see migration
    // comment for why (branded products, not standardized terms).
    // =========================================================
    public function terminologyOptions(Request $request)
    {
        $category = $request->get('category');

        $query = DB::table('part_terminology')->orderBy('standard_name');
        if ($category) {
            $query->where('category', $category);
        }

        return response()->json(['terms' => $query->get(['id', 'category', 'standard_name'])]);
    }

    private function storePartPhotos(int $partId, array $files): void
    {
        $part = DB::table('parts_inventory')->where('id', $partId)->first();
        $existing = json_decode($part->photos ?? '[]', true) ?: [];

        foreach ($files as $file) {
            if (!$file->isValid()) continue;
            $path = $file->store("parts/{$partId}", 'public');
            $existing[] = $path;
        }

        DB::table('parts_inventory')->where('id', $partId)->update([
            'photos'     => json_encode($existing),
            'updated_at' => now(),
        ]);
    }

    // =========================================================
    // POST /admin/inventory/{id}/photos — add more photos to an
    // already-existing part (used from the Edit page)
    // =========================================================
    public function addPhotos(Request $request, int $id)
    {
        $request->validate([
            'photos'   => 'required|array',
            'photos.*' => 'image|max:8192',
        ]);

        // Enforce 10-photo maximum
        $part      = DB::table('parts_inventory')->where('id', $id)->first();
        $existing  = json_decode($part->photos ?? '[]', true) ?: [];
        $remaining = 10 - count($existing);

        if ($remaining <= 0) {
            return back()->withErrors(['photos' => 'Maximum 10 photos already reached. Delete some first.']);
        }

        $files   = array_slice($request->file('photos'), 0, $remaining);
        $this->storePartPhotos($id, $files);

        $added   = count($files);
        $skipped = count($request->file('photos')) - $added;
        $msg     = "{$added} photo(s) uploaded.";
        if ($skipped > 0) $msg .= " {$skipped} skipped (10-photo limit).";

        return back()->with('success', $msg);
    }

    // POST /admin/inventory/{id}/video — add or replace this part's single video
    public function addVideo(Request $request, int $id)
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi,webm|max:51200', // 50MB
        ]);

        $part = DB::table('parts_inventory')->where('id', $id)->first();
        if (!empty($part->video_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($part->video_path);
        }

        $videoPath = $request->file('video')->store("parts/{$id}/video", 'public');
        DB::table('parts_inventory')->where('id', $id)->update(['video_path' => $videoPath, 'updated_at' => now()]);

        return back()->with('success', 'Video uploaded.');
    }

    public function deleteVideo(int $id)
    {
        $part = DB::table('parts_inventory')->where('id', $id)->first();
        if (!empty($part->video_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($part->video_path);
        }
        DB::table('parts_inventory')->where('id', $id)->update(['video_path' => null, 'updated_at' => now()]);
        return back()->with('success', 'Video removed.');
    }

    // POST /admin/inventory/{id}/photos/delete — remove one photo,
    // and re-promote the next one to primary if the deleted one was first
    public function deletePhoto(Request $request, int $id)
    {
        $request->validate(['path' => 'required|string']);

        $part = DB::table('parts_inventory')->where('id', $id)->first();
        $photos = json_decode($part->photos ?? '[]', true) ?: [];
        $photos = array_values(array_filter($photos, fn($p) => $p !== $request->path));

        \Illuminate\Support\Facades\Storage::disk('public')->delete($request->path);

        DB::table('parts_inventory')->where('id', $id)->update([
            'photos'     => json_encode($photos),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Photo removed.');
    }
}
