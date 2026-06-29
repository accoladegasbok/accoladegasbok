<?php
// FILE: app/Http/Controllers/Admin/InventoryController.php
// Updated: pin_count, gear_alias, engine_code_oem, transmission_code_oem,
//          origin_market, fitment_notes, compat years, compatible_trims
// Updated: part_name now restricted to App\Data\PartNames::flat() for
//          non-admin staff, to keep naming uniform across the system.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Data\PartNames;

class InventoryController extends Controller
{
    const LOCATIONS = [
    'Waxahachie TX',
    'Elkhorn WI',
    'Ile-Ife Nigeria',
    'Ibadan Nigeria',
    'Lagos Nigeria',
    'Abuja Nigeria',
    'Akure Nigeria',
    'Accra Ghana',
];

    const BRANDS = [
        'Toyota','Lexus','Kia','Hyundai','Nissan','Mercedes-Benz',
        'Infiniti','Ford','GM','Chevrolet','Acura','VW','Honda',
    ];

    const CATEGORIES = [
        'Engine','Transmission','Body','Suspension','Electrical',
        'Interior','Cooling','Brakes','Airbag','Fuel','Exhaust','Seat','Wheels','Consumable',
    ];

    // Year range 1986–2027 (item 5)
    private function yearRange(): array
    {
        return range(1986, 2027);
    }

    // ── Part name guard — only admin may submit a name not on the
    // ── standard list, to keep nomenclature uniform across staff.
    private function assertAllowedPartName(?string $partName): ?string
    {
        if (!$partName) return 'Part name is required.';

        if (Session::get('staff_role') === 'admin') {
            return null; // admins may use any name
        }

        if (!in_array($partName, PartNames::flat(), true)) {
            return 'Only admin can add a part name that is not on the standard list. Please select a name from the list, or ask an admin to add it.';
        }

        return null;
    }

    // ── List ──────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = DB::table('parts_inventory')->orderByDesc('created_at');

        if ($f = $request->get('brand'))    $query->where('brand', $f);
        if ($f = $request->get('category')) $query->where('part_category', $f);
        if ($f = $request->get('location')) $query->where('location', $f);
        if ($f = $request->get('status'))   $query->where('status', $f);
        if ($f = $request->get('q')) {
            $query->where(function($q) use ($f) {
                $q->where('part_name',           'like', "%$f%")
                  ->orWhere('part_code',          'like', "%$f%")
                  ->orWhere('model',              'like', "%$f%")
                  ->orWhere('oem_part_number',    'like', "%$f%")
                  ->orWhere('engine_code_oem',    'like', "%$f%")  // item 6
                  ->orWhere('transmission_code_oem','like',"%$f%") // item 6
                  ->orWhere('gear_alias',         'like', "%$f%")  // item 7
                  ->orWhere('pin_count',          'like', "%$f%"); // item 10
            });
        }

        $parts  = $query->paginate(30)->withQueryString();
        $counts = DB::table('parts_inventory')
            ->select('status', DB::raw('count(*) as n'))
            ->groupBy('status')->pluck('n','status');

        return view('admin.inventory.index', [
            'parts'      => $parts,
            'counts'     => $counts,
            'brands'     => self::BRANDS,
            'categories' => self::CATEGORIES,
            'locations'  => self::LOCATIONS,
        ]);
    }

    // ── Edit form ─────────────────────────────────────────────────
    // =========================================================
    // GET /admin/inventory/{id}/barcode — printable barcode label
    // =========================================================
    public function barcode(int $id)
    {
        $part = DB::table('parts_inventory')->where('id', $id)->first();
        abort_if(!$part, 404);
        return view('admin.inventory.barcode', compact('part'));
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

        return view('admin.inventory.edit', [
            'part'                 => $part,
            'brands'               => self::BRANDS,
            'categories'           => self::CATEGORIES,
            'locations'            => self::LOCATIONS,
            'years'                => $this->yearRange(),
            'currentRoomId'        => $currentRoomId,
            'interchangeGroup'     => $interchangeGroup,
            'interchangeVehicles'  => $interchangeVehicles,
            'aggregatedStock'      => $aggregatedStock,
            'heuristicSuggestion'  => $heuristicSuggestion,
        ]);
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, int $id)
    {
        $request->validate([
            'part_name'           => 'required|string|max:150',
            'price_usd'           => 'required|numeric|min:0',
            'condition_grade'     => 'required|in:A,B,C,New',
            'status'              => 'required|in:Available,Reserved,Sold',
            'location'            => 'required|string',
            'description'         => 'nullable|string|max:1000',
            'oem_part_number'     => 'nullable|string|max:80',
            'mileage'             => 'nullable|integer|min:0',
            'colour'              => 'nullable|string|max:50',
            // Bin location is now REQUIRED (#13) — every part must
            // have a real physical bin assigned at the moment it's
            // entered into inventory, no exceptions.
            'storage_shelf_id'    => 'required|exists:storage_shelves,id',
            'bin_location'        => 'nullable|string|max:20',
            'engine_code_oem'     => 'nullable|string|max:30',
            'transmission_code_oem'=> 'nullable|string|max:30',
            'pin_count'           => 'nullable|integer|min:1|max:99',
            'gear_alias'          => 'nullable|string|max:50',
            'origin_market'       => 'nullable|in:JDM,USDM,EDM,Nigerian Used,N/A',
            'fitment_notes'       => 'nullable|string',
            'compat_year_from'    => 'nullable|integer|min:1986|max:2027',
            'compat_year_to'      => 'nullable|integer|min:1986|max:2027',
            'compatible_trims'    => 'nullable|string|max:200',
            'not_compatible_note' => 'nullable|string|max:200',
        ]);

        // ── Part name guard (admin-only for unlisted names) ──────────
        if ($err = $this->assertAllowedPartName($request->part_name)) {
            return back()->withErrors(['part_name' => $err])->withInput();
        }
        // ──────────────────────────────────────────────────────────────

        // ── Fixed currency by location — the price staff types is the
        // authoritative, never-recalculated value in that location's
        // native currency. price_usd is updated only as a fresh snapshot,
        // not used for display going forward.
        $currency   = \App\Http\Controllers\Admin\InvoiceController::currencyForLocation($request->location);
        $priceLocal = (float) $request->price_usd; // form field name kept for now — see note below
        $priceUsdSnapshot = $priceLocal / $currency['rate'];
        // ──────────────────────────────────────────────────────────────

        DB::table('parts_inventory')->where('id', $id)->update([
            'part_name'              => $request->part_name,
            'price_usd'              => $priceUsdSnapshot,
            'price_local'            => $priceLocal,
            'currency_code'          => $currency['code'],
            'condition_grade'        => $request->condition_grade,
            'status'                 => $request->status,
            'location'               => $request->location,
            'description'            => $request->description,
            'oem_part_number'        => $request->oem_part_number,
            'mileage'                => $request->mileage,
            'colour'                 => $request->colour,
            'bin_location'           => $request->bin_location,
            'storage_shelf_id'       => $request->storage_shelf_id ?: null,
            'engine_code_oem'        => $request->engine_code_oem
                                          ? strtoupper(trim($request->engine_code_oem)) : null,
            'transmission_code_oem'  => $request->transmission_code_oem
                                          ? strtoupper(trim($request->transmission_code_oem)) : null,
            'pin_count'              => $request->pin_count,
            'gear_alias'             => $request->gear_alias,
            'origin_market'          => $request->origin_market ?? 'N/A',
            'fitment_notes'          => $request->fitment_notes,
            'compat_year_from'       => $request->compat_year_from,
            'compat_year_to'         => $request->compat_year_to,
            'compatible_trims'       => $request->compatible_trims,
            'not_compatible_note'    => $request->not_compatible_note,
            'updated_at'             => now(),
        ]);

        return redirect()->route('admin.inventory.index')
            ->with('success', 'Part updated successfully.');
    }

    // ── Quick status update (AJAX) ────────────────────────────────
    public function updateStatus(Request $request, int $id)
    {
        $request->validate(['status' => 'required|in:Available,Reserved,Sold']);
        DB::table('parts_inventory')->where('id', $id)->update([
            'status' => $request->status, 'updated_at' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    // ── Delete ────────────────────────────────────────────────────
    public function destroy(int $id)
    {
        if (!in_array(Session::get('staff_role'), ['admin','manager'])) abort(403);
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

    // ── Create form ───────────────────────────────────────────────
    public function create()
    {
        return view('admin.inventory.create', [
            'brands'     => self::BRANDS,
            'categories' => self::CATEGORIES,
            'locations'  => self::LOCATIONS,
            'years'      => $this->yearRange(),
        ]);
    }
    // ── Manual add form ───────────────────────────────────────────
    // ── Manual add form ───────────────────────────────────────────
    public function manualAdd()
    {
        return view('admin.inventory.manual-add');
    }

    // =========================================================
    // GET /admin/inventory/consumable/create — simple dedicated form
    // =========================================================
    public function consumableCreate()
    {
        return view('admin.inventory.consumable-create', [
            'locations' => self::LOCATIONS,
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
            'unit_size'          => 'nullable|string|max:30',
            'compatibility_note' => 'nullable|string|max:200',
            'price_usd'          => 'required|numeric|min:0',
            'condition_grade'    => 'required|in:A,B,C,New',
            'location'           => 'required|string',
            'storage_shelf_id'   => 'required|exists:storage_shelves,id', // #13 — bin location cannot be empty
            'stock_qty'          => 'nullable|integer|min:1',
        ]);

        // ── Part name guard (admin-only for unlisted names) ──────────
        if ($err = $this->assertAllowedPartName($request->part_name)) {
            return back()->withErrors(['part_name' => $err])->withInput();
        }
        // ──────────────────────────────────────────────────────────────

        // Typed brand not in our known list: JS already sends brand=Generic
        // with the real name in other_brand — fold it into part_name.
        $brand    = $request->brand;
        $partName = $request->part_name;
        if ($request->other_brand) {
            $partName = trim($request->other_brand) . ' - ' . $partName;
        }
        $prefix   = 'CON';
        $lastCode = DB::table('parts_inventory')
            ->where('part_code', 'like', $prefix.'-%')
            ->orderByDesc('id')->value('part_code');
        $nextNum  = $lastCode ? (int) substr($lastCode, strlen($prefix)+1) + 1 : 1;
        $partCode = $prefix.'-'.str_pad($nextNum, 5, '0', STR_PAD_LEFT);

        // ── Fixed currency by location ──────────────────────────────
        $currency   = \App\Http\Controllers\Admin\InvoiceController::currencyForLocation($request->location);
        $priceLocal = (float) $request->price_usd; // form field name kept for now — see note below
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
            'part_category'        => 'Consumable',
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

        $rules = [
            'brand'          => 'required|string',
            'part_name'      => 'required|string|max:150',
            'part_category'  => 'required|string',
            'price_usd'      => 'required|numeric|min:0',
            'condition_grade'=> 'required|in:A,B,C,New',
            'location'       => 'required|string',
            'photos'         => 'required|array|min:1',
            'photos.*'       => 'image|max:8192',
            'video'          => 'nullable|file|mimes:mp4,mov,avi,webm|max:51200', // 50MB
        ];

        if ($isConsumable) {
            $rules['unit_size'] = 'nullable|string|max:30';
            $rules['stock_qty'] = 'nullable|integer|min:1';
        } else {
            $rules['model']     = 'required|string|max:80';
            $rules['year_from'] = 'required|integer|min:1986|max:2027';
            $rules['year_to']   = 'required|integer|min:1986|max:2027';
        }

        $request->validate($rules);

        // ── Part name guard (admin-only for unlisted names) ──────────
        if ($err = $this->assertAllowedPartName($request->part_name)) {
            return back()->withErrors(['part_name' => $err])->withInput();
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
        $yearFrom = $isConsumable ? 1990 : $request->year_from;
        $yearTo   = $isConsumable ? 2030 : $request->year_to;

        $partId = DB::table('parts_inventory')->insertGetId([
            'part_code'              => $partCode,
            'brand'                  => $request->brand,
            'model'                  => $isConsumable ? ($request->model ?: 'Universal') : $request->model,
            'year_from'              => $yearFrom,
            'year_to'                => $yearTo,
            'compat_year_from'       => $isConsumable ? null : ($request->compat_year_from ?? $yearFrom),
            'compat_year_to'         => $isConsumable ? null : ($request->compat_year_to   ?? $yearTo),
            'part_name'              => $request->part_name,
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
            'engine_code_oem'        => $request->engine_code_oem
                                          ? strtoupper(trim($request->engine_code_oem)) : null,
            'transmission_code_oem'  => $request->transmission_code_oem
                                          ? strtoupper(trim($request->transmission_code_oem)) : null,
            'pin_count'              => $request->pin_count,
            'gear_alias'             => $request->gear_alias,
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

        // ── Photo upload — multiple photos allowed, stored in the
        // existing photos JSON column. First photo uploaded becomes
        // primary by default (shown in search/cards).
        if ($request->hasFile('photos')) {
            $this->storePartPhotos($partId, $request->file('photos'));
        }

        // ── Optional single video per part ──
        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store("parts/{$partId}/video", 'public');
            DB::table('parts_inventory')->where('id', $partId)->update(['video_path' => $videoPath]);
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
            'photos.*' => 'image|max:8192', // 8MB per photo
        ]);

        $this->storePartPhotos($id, $request->file('photos'));

        return back()->with('success', 'Photo(s) uploaded.');
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
