<?php
// FILE: app/Http/Controllers/Admin/HarvestController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Data\OemDatabase;
use App\Services\InterchangeService;

class HarvestController extends Controller
{
    const CODE_PREFIX = [
        'Engine'       => 'ENG',
        'Transmission' => 'TRN',
        'Electrical'   => 'ELC',
        'Fuel'         => 'FUL',
        'Cooling'      => 'CLG',
        'Body'         => 'BDY',
        'Suspension'   => 'SUS',
        'Wheels'       => 'WHL',
        'Seat'         => 'INT',
        'Airbag'       => 'AIR',
        'Interior'     => 'INT',
        'Brakes'       => 'BRK',
        'Exhaust'      => 'EXH',
        'Other'        => 'OTH',
    ];

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    // Parses a free-text engine field like "2.5L I4" or "3.5 V6" into
    // a displacement float (2.5, 3.5). Returns 0 if nothing parseable —
    // OemDatabase::lookup() falls back to its default guess in that case.
    private function parseEngineSize(?string $engineText): float
    {
        if (!$engineText) return 0;
        if (preg_match('/(\d+(\.\d+)?)\s*L/i', $engineText, $m)) {
            return (float) $m[1];
        }
        return 0;
    }

    // =========================================================
    // Guesses the cylinder count from an engine string so that
    // Spark Plug / Ignition Coil qty on the harvest checklist
    // defaults to the right number without staff having to type
    // it manually. Examples: "1.8L 4-cyl 2ZR-FE" → 4,
    // "3.5L V6" → 6, "5.0L V8" → 8, "2.0T" → 4 (inline default).
    // Returns 0 if nothing is recognisable (blade falls back to
    // the part's own qty_default instead).
    // =========================================================
    private function guessCylinderCount(string $engineStr): int
    {
        $s = strtoupper($engineStr);

        // Explicit cylinder patterns: "4-CYL", "V6", "V8", "I4",
        // "W12", "H4" (Subaru boxer), "3CYL" etc.
        if (preg_match('/\b(V|W|H|I|L|R|B)?(\d{1,2})\s*[-\s]?\s*CYL\b/i', $s, $m)) return (int) $m[2];
        if (preg_match('/\b[VI](\d{1,2})\b/', $s, $m)) return (int) $m[1];
        if (preg_match('/\b(W|H|F)(\d{1,2})\b/', $s, $m)) return (int) $m[2];

        // Common shorthand: "4CYL", "V6", "V8", "INLINE-4" etc.
        if (preg_match('/INLINE[-\s]?(\d)/i', $s, $m)) return (int) $m[1];

        // Engine codes that imply cylinder count:
        // 2ZR-FE = 4-cyl, 1GR-FE = 6-cyl, 2UZ-FE = 8-cyl etc.
        // First digit of Toyota/Honda/Nissan codes is cyl count
        if (preg_match('/\b([1-9])(?:ZR|GR|UZ|ZZ|NZ|SZ|GD|GE|VK|VQ|VG|KA|CA|SR|RB|VH|FJ|Z|G|K)\b/i', $s, $m)) {
            return (int) $m[1];
        }

        return 0; // unknown — blade falls back to qty_default
    }

    private function getOemSuggest(string $make, string $model, int $year, float $engineL = 0): array
    {
        $oem = OemDatabase::lookup($make, $model, $year, 0, $engineL);
        return [
            'engine_code'       => $oem['engine_code'],
            'transmission_code' => $oem['transmission_code'],
            'pin_count'         => $oem['pin_count'],
            'gear_alias'        => $oem['gear_alias'],
        ];
    }

    private function suggestOemCodes(string $model, int $year, float $engineL = 0): array
    {
        $oem = OemDatabase::lookup('TOYOTA', $model, $year, 0, $engineL);
        return [
            'engine_code'       => $oem['engine_code'],
            'transmission_code' => $oem['transmission_code'],
            'pin_count'         => $oem['pin_count'],
            'gear_alias'        => $oem['gear_alias'],
        ];
    }

    // =========================================================
    // GET /admin/harvest
    // =========================================================
    public function index()
    {
        $sessions = DB::table('harvest_sessions as hs')
            ->join('donor_vehicles as dv', 'dv.id', '=', 'hs.donor_vehicle_id')
            ->join('staff as s', 's.id', '=', 'hs.staff_id')
            ->select(
                'hs.*',
                'dv.make', 'dv.model', 'dv.year', 'dv.vin',
                'dv.location', 'dv.id as dv_id',
                's.name as staff_name'
            )
            ->orderByDesc('hs.created_at')
            ->paginate(20);

        return view('admin.harvest.index', compact('sessions'));
    }

    // =========================================================
    // GET /admin/harvest/create
    // =========================================================
    public function create()
    {
        return view('admin.harvest.create', [
            'staffLocation' => Session::get('staff_location'),
            'locations'     => [
                'Waxahachie TX',
                'Kennedale TX',
                'Elkhorn WI',
                'Ile-Ife Nigeria',
                'Ibadan Nigeria',
                'Lagos Nigeria',
                'Abuja Nigeria',
                'Akure Nigeria',
                'Accra Ghana',
            ],
        ]);
    }

    // =========================================================
    // GET /admin/harvest/search-donors?q=...
    // Non-VIN search — finds existing donor vehicles by make,
    // model, VIN fragment, or notes. Used by the "No VIN? Search
    // or Enter Manually" box on the New Harvest page.
    //
    // IMPORTANT: register this route ABOVE any
    // /admin/harvest/{session}/... pattern routes in web.php,
    // e.g.:
    //   Route::get('/admin/harvest/search-donors',
    //       [HarvestController::class, 'searchDonors'])
    //       ->name('admin.harvest.search-donors');
    // =========================================================
    public function searchDonors(Request $request)
    {
        $q = trim($request->get('q', ''));

        if ($q === '') {
            return response()->json(['results' => []]);
        }

        $rows = DB::table('donor_vehicles as dv')
            ->leftJoin('harvest_sessions as hs', 'hs.donor_vehicle_id', '=', 'dv.id')
            ->where(function ($query) use ($q) {
                $query->where('dv.make', 'like', "%{$q}%")
                    ->orWhere('dv.model', 'like', "%{$q}%")
                    ->orWhere('dv.vin', 'like', "%{$q}%")
                    ->orWhere('dv.notes', 'like', "%{$q}%")
                    ->orWhere('dv.trim', 'like', "%{$q}%");
            })
            ->select(
                'dv.id as dv_id',
                'dv.year', 'dv.make', 'dv.model', 'dv.trim',
                'dv.vin', 'dv.location',
                'hs.id as session_id', 'hs.status'
            )
            ->orderByDesc('dv.created_at')
            ->limit(15)
            ->get();

        $results = $rows->map(function ($r) {
            return [
                'year'       => $r->year,
                'make'       => $r->make,
                'model'      => $r->model,
                'trim'       => $r->trim,
                'vin'        => $r->vin,
                'location'   => $r->location,
                'status'     => $r->status,
                'session_id' => $r->session_id,
            ];
        });

        return response()->json(['results' => $results]);
    }

    // =========================================================
    // GET /admin/harvest/engine-options?make=&model=&year=
    // Returns the distinct engine configurations available for this
    // vehicle (e.g. "2.5L 4-Cyl (2AR-FE)", "3.5L V6 (2GR-FE)") so
    // staff can pick the correct one on Manual Entry — same data a
    // VIN decode would reveal, just offered as a choice instead of
    // assumed from one specific car's VIN.
    // =========================================================
    public function engineOptions(Request $request)
    {
        $make  = trim($request->get('make', ''));
        $model = trim($request->get('model', ''));
        $year  = (int) $request->get('year', 0);

        if ($make === '' || $model === '' || $year === 0) {
            return response()->json(['options' => []]);
        }

        $options = OemDatabase::engineOptions($make, $model, $year);

        return response()->json(['options' => $options]);
    }

    // =========================================================
    // POST /admin/harvest/vin-decode
    // =========================================================
    public function vinDecode(Request $request)
    {
        $request->validate(['vin' => 'required|string|size:17']);
        $vin = strtoupper(trim($request->vin));

        $cached = Cache::remember("nhtsa_{$vin}", now()->addDays(30), function () use ($vin) {
            try {
                $res = Http::timeout(8)->get(
                    "https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVin/{$vin}?format=json"
                );
                if ($res->failed()) return null;
                $r = collect($res->json('Results', []));
                return [
                    'make'       => $r->firstWhere('Variable', 'Make')['Value']                          ?? null,
                    'model'      => $r->firstWhere('Variable', 'Model')['Value']                         ?? null,
                    'year'       => $r->firstWhere('Variable', 'Model Year')['Value']                    ?? null,
                    'trim'       => $r->firstWhere('Variable', 'Trim')['Value']                          ?? null,
                    'body_style' => $r->firstWhere('Variable', 'Body Class')['Value']                    ?? null,
                    'engine'     => $r->firstWhere('Variable', 'Displacement (L)')['Value']              ?? null,
                    'drive_type' => $r->firstWhere('Variable', 'Drive Type')['Value']                    ?? null,
                    'origin'     => $r->firstWhere('Variable', 'Plant Country')['Value']                 ?? null,
                    'cylinders'  => $r->firstWhere('Variable', 'Engine Number of Cylinders')['Value']    ?? null,
                ];
            } catch (\Exception $e) {
                return null;
            }
        });

        if (!$cached || !$cached['make']) {
            return response()->json(['error' => 'Could not decode this VIN.'], 422);
        }

        $oem = OemDatabase::lookup(
            $cached['make']  ?? '',
            $cached['model'] ?? '',
            (int) ($cached['year']      ?? 0),
            (int) ($cached['cylinders'] ?? 0),
            (float) ($cached['engine']  ?? 0)
        );

        $cached['oem_suggestion'] = [
            'engine_code'       => $oem['engine_code'],
            'transmission_code' => $oem['transmission_code'],
            'pin_count'         => $oem['pin_count'],
            'gear_alias'        => $oem['gear_alias'],
        ];

        return response()->json(['vehicle' => $cached]);
    }

    // =========================================================
    // POST /admin/harvest — register donor vehicle + start session
    // =========================================================
    public function store(Request $request)
    {
        $request->validate([
            'vin'       => 'nullable|string|size:17',
            'make'      => 'required|string|max:60',
            'model'     => 'required|string|max:80',
            'year'      => 'required|integer|min:1986|max:2027',
            'mileage'   => 'nullable|integer|min:0|max:9999999',
            'condition' => 'required|in:Good,Fair,Poor',
            'source'    => 'required|in:Auction,Insurance,Private Sale,Dealer,Other',
            'location'  => 'required|string|max:60',
        ]);

        // Manual entry with no VIN — generate a 17-char placeholder so it
        // fits the vin column constraint, and so downstream donor_vin
        // linking (parts_inventory, harvest_sessions) still works.
        // Format: MAN + 12-digit timestamp (yymmddHHiiss) + 2 random chars = 17 chars
        $vin = strtoupper(trim($request->vin ?? ''));
        if ($vin === '') {
            $vin = 'MAN' . now()->format('ymdHis') . strtoupper(substr(uniqid(), -2));
        }

        // ── Graceful duplicate-VIN handling instead of crashing with
        // a raw DB constraint error. A real VIN should normally only
        // be entered once — if it already exists, send staff straight
        // to that donor's existing (or most recent) harvest session
        // rather than failing.
        $existingDonor = DB::table('donor_vehicles')->where('vin', $vin)->first();
        if ($existingDonor) {
            $existingSession = DB::table('harvest_sessions')
                ->where('donor_vehicle_id', $existingDonor->id)
                ->orderByDesc('created_at')
                ->first();

            if ($existingSession) {
                return redirect()->route('admin.harvest.checklist', $existingSession->id)
                    ->with('success', "This VIN was already registered as a donor vehicle ({$existingDonor->year} {$existingDonor->make} {$existingDonor->model}) — continuing its existing harvest session instead of creating a duplicate.");
            }

            // Donor exists but somehow has no session yet — create one now.
            $sessionId = DB::table('harvest_sessions')->insertGetId([
                'donor_vehicle_id' => $existingDonor->id,
                'staff_id'         => Session::get('staff_id'),
                'location'         => $request->location,
                'status'           => 'in_progress',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
            return redirect()->route('admin.harvest.checklist', $sessionId)
                ->with('success', 'This VIN was already registered — starting a new harvest session for it.');
        }

        $dvId = DB::table('donor_vehicles')->insertGetId([
            'vin'             => $vin,
            'year'            => $request->year,
            'make'            => $request->make,
            'model'           => $request->model,
            'trim'            => $request->trim,
            'colour'          => $request->colour,
            'engine'          => $request->engine,
            'mileage'         => $request->mileage,
            'date_acquired'   => today(),
            'source'          => $request->source,
            'condition'       => $request->condition,
            'location'        => $request->location,
            'parts_harvested' => 0,
            'notes'           => $request->notes,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $sessionId = DB::table('harvest_sessions')->insertGetId([
            'donor_vehicle_id' => $dvId,
            'staff_id'         => Session::get('staff_id'),
            'location'         => $request->location,   // ← store harvest location
            'status'           => 'in_progress',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return redirect()->route('admin.harvest.checklist', $sessionId)
            ->with('success', 'Donor vehicle registered. Tick the parts harvested below.');
    }

    // =========================================================
    // GET /admin/harvest/{session}/checklist
    // =========================================================
    public function checklist(int $sessionId)
    {
        $session = DB::table('harvest_sessions as hs')
            ->join('donor_vehicles as dv', 'dv.id', '=', 'hs.donor_vehicle_id')
            ->where('hs.id', $sessionId)
            ->select(
                'hs.*',
                'dv.make', 'dv.model', 'dv.year', 'dv.vin',
                'dv.mileage', 'dv.colour', 'dv.body_style', 'dv.engine',
                'dv.location', 'dv.id as donor_id'
            )
            ->first();

        if (!$session) abort(404);

        $existing = DB::table('parts_inventory')
            ->where('donor_vin', $session->vin)
            ->pluck('part_name')
            ->toArray();

        $partsByCategory = $this->getPartsList();
        $oemSuggest      = $this->getOemSuggest($session->make, $session->model, (int) $session->year, $this->parseEngineSize($session->engine ?? null));

        // ── CURRENCY based on harvest/vehicle location ────────────
        $harvestLocation = $session->location ?? 'Waxahachie TX';
        $currency        = InvoiceController::currencyForLocation($harvestLocation);
        // ─────────────────────────────────────────────────────────

        // ── Cylinder count — derived from the engine string so that
        // Spark Plug / Ignition Coil qty defaults match the actual
        // vehicle (a 4-cylinder defaults to 4, a V6 to 6, etc.).
        // Staff can still edit the number before saving.
        $cylinderCount = $this->guessCylinderCount($session->engine ?? '');

        return view('admin.harvest.checklist', [
            'session'         => $session,
            'partsByCategory' => $partsByCategory,
            'existing'        => $existing,
            'ngnRate'         => 1600,
            'currency'        => $currency,
            'harvestLocation' => $harvestLocation,
            'oemSuggest'      => $oemSuggest,
            'cylinderCount'   => $cylinderCount,
        ]);
    }

    // =========================================================
    // POST /admin/harvest/{session}/parts — save to inventory
    // =========================================================
    public function saveParts(Request $request, int $sessionId)
    {
        $session = DB::table('harvest_sessions as hs')
            ->join('donor_vehicles as dv', 'dv.id', '=', 'hs.donor_vehicle_id')
            ->where('hs.id', $sessionId)
            ->select(
                'hs.*',
                'dv.make', 'dv.model', 'dv.year', 'dv.vin',
                'dv.mileage', 'dv.colour', 'dv.body_style', 'dv.engine',
                'dv.location', 'dv.id as donor_id'
            )
            ->first();

        if (!$session) abort(404);

        // ── Resolve currency for this harvest location ────────────
        $harvestLocation = $session->location ?? 'Waxahachie TX';
        $currency        = InvoiceController::currencyForLocation($harvestLocation);
        // ─────────────────────────────────────────────────────────

        $parts   = $request->input('parts', []);
        $prices  = $request->input('prices', []);
        $grades  = $request->input('grades', []);
        $notes      = $request->input('part_notes', []);
        $oemEng     = $request->input('oem_engine', []);
        $oemTrns    = $request->input('oem_transmission', []);
        $pins       = $request->input('pin_count', []);
        $driveTypes = $request->input('drive_type', []);
        $qtys       = $request->input('qtys', []);

        if (empty($parts)) {
            return back()->with('error', 'Please tick at least one part before saving.');
        }

        // ── Custom parts are admin-only, to keep naming uniform ──────────
        $customPartsInput = $request->input('custom_parts', []);
        if (!empty($customPartsInput) && !in_array(Session::get('staff_role'), ['admin', 'manager'])) {
            return back()->with('error', 'Only admin or manager can add custom part names. Please select a part from the standard list, or ask them to add it.');
        }
        // Custom parts also each need their own bin (#A) and at least 1 photo
        foreach ($customPartsInput as $idx => $cp) {
            if (empty($cp['bin_id'])) {
                return back()->with('error', 'Every custom part needs a bin selected before saving.');
            }
            $cpPhotoFiles = $request->file("custom_parts.{$idx}.photos") ?? [];
            $cpValidCount = count(array_filter($cpPhotoFiles, fn($f) => $f && $f->isValid()));
            if ($cpValidCount < 1) {
                return back()->with('error', 'Every custom part needs at least 1 photo before saving.');
            }
        }

        // ── Bin location is REQUIRED per individual item (#A — not
        // one shared bin for the whole batch, since two parts off the
        // same car can end up in different bins). Build a lookup of
        // part-key => storage_shelf_id from the per-row selects.
        $binsInput = $request->input('bins', []);
        $missingBins = [];
        foreach ($parts as $partKey) {
            if (empty($binsInput[$partKey])) {
                $missingBins[] = $partKey;
            }
        }
        if (!empty($missingBins)) {
            return back()->with('error', 'Every ticked part needs a bin selected before saving — missing for: ' . implode(', ', $missingBins));
        }

        // ── A bin can only go to ONE item per submission by default —
        // UNLESS staff explicitly confirmed sharing it deliberately
        // (the "are you sure?" prompt on selecting an already-taken
        // bin client-side). Confirmed bin IDs are exempt from both
        // checks below — a small group of genuinely related items CAN
        // share one bin once staff has made that call.
        //
        // Empty selections (unticked rows left blank) must be filtered
        // out first — otherwise multiple blank values look like
        // "duplicates" of each other and falsely trigger this error.
        // "room:<name>" placeholder values (bin not ready yet, room-
        // only assignment) are also excluded — many parts CAN share
        // the same room-only placeholder since none of them is
        // actually claiming a real physical bin.
        $confirmedSharedBins = array_map('strval', $request->input('confirm_shared_bins', []));

        $allBinIdsThisBatch = array_values(array_filter($binsInput, fn($v) => !empty($v) && !str_starts_with($v, 'room:')));
        foreach ($customPartsInput as $cp) {
            if (!empty($cp['bin_id']) && !str_starts_with($cp['bin_id'], 'room:')) $allBinIdsThisBatch[] = $cp['bin_id'];
        }

        // Duplicate-in-batch check — skip any bin ID staff confirmed sharing
        $unconfirmedBinIds = array_diff($allBinIdsThisBatch, $confirmedSharedBins);
        $duplicateBins = array_diff_assoc($unconfirmedBinIds, array_unique($unconfirmedBinIds));
        if (!empty($duplicateBins)) {
            $dupeBinCodes = DB::table('storage_shelves')->whereIn('id', array_unique($duplicateBins))->pluck('full_bin_code')->implode(', ');
            return back()->with('error', "The same bin was selected for more than one item in this batch ({$dupeBinCodes}) — each bin can only hold one part. Please choose a different bin for one of them, or confirm sharing it deliberately.");
        }

        // ── Also re-check against bins already occupied by PREVIOUSLY
        // SAVED parts in the database — the dropdown marks these at
        // page-load time, but if this harvest session has been open a
        // while, another part may have claimed one of these bins since
        // then. Confirmed shared bins are exempt here too.
        $unconfirmedForDbCheck = array_diff($allBinIdsThisBatch, $confirmedSharedBins);
        if (!empty($unconfirmedForDbCheck)) {
            $alreadyOccupied = DB::table('parts_inventory')
                ->whereIn('storage_shelf_id', $unconfirmedForDbCheck)
                ->whereIn('status', ['Available', 'Reserved', 'Hold'])
                ->get();
            if ($alreadyOccupied->count() > 0) {
                $conflicts = $alreadyOccupied->map(fn($p) => "{$p->part_name} ({$p->part_code})")->implode(', ');
                return back()->with('error', "One or more selected bins are already occupied by another part: {$conflicts}. Please choose different bins, or confirm sharing deliberately — someone may have claimed it since this page loaded.");
            }
        }

        // ── At least 1 photo required per ticked part — server-side too.
        $missingPhotos = [];
        foreach ($parts as $partKey) {
            $photoFiles = $request->file("photos.{$partKey}") ?? [];
            $validCount = count(array_filter($photoFiles, fn($f) => $f && $f->isValid()));
            if ($validCount < 1) {
                $missingPhotos[] = $partKey;
            }
        }
        if (!empty($missingPhotos)) {
            return back()->with('error', 'Every ticked part needs at least 1 photo before saving — missing for: ' . implode(', ', $missingPhotos));
        }

        // Preload all referenced bins' full_bin_code in one query
        // (covers both regular ticked parts and custom parts' bins).
        // Room-only placeholders excluded — they're not real bin IDs.
        $allReferencedBinIds = array_values(array_filter($binsInput, fn($v) => !empty($v) && !str_starts_with($v, 'room:')));
        foreach ($customPartsInput as $cp) {
            if (!empty($cp['bin_id']) && !str_starts_with($cp['bin_id'], 'room:')) $allReferencedBinIds[] = $cp['bin_id'];
        }
        $binCodesById = DB::table('storage_shelves')
            ->whereIn('id', array_unique($allReferencedBinIds))
            ->pluck('full_bin_code', 'id');
        // ──────────────────────────────────────────────────────────────────

        $created      = 0;
        $interchange  = new InterchangeService();
        $flatTemplate = collect($this->getPartsList())->flatten(1);
        $oemSuggest   = $this->suggestOemCodes($session->model, (int) $session->year, $this->parseEngineSize($session->engine ?? null));

        DB::beginTransaction();
        try {
            foreach ($parts as $key) {
                $tpl = $flatTemplate->firstWhere('key', $key);
                if (!$tpl) continue;

                // ── Staff enters price in LOCAL currency — that's now the
                // authoritative, never-recalculated value. price_usd is
                // stored once as a historical snapshot only.
                $rawPrice = (float) ($prices[$key] ?? 0);
                if ($rawPrice <= 0) continue;
                $priceUsd = $rawPrice / $currency['rate'];
                // ────────────────────────────────────────────────────────────────────

                $grade    = $grades[$key] ?? 'B';
                $partNote = $notes[$key]  ?? null;

                // Per-item bin location (#A) — may be a real bin ID,
                // or a "room:<name>" placeholder when the physical bin
                // isn't ready yet. In the placeholder case, the part
                // is saved with NO storage_shelf_id (so it doesn't
                // falsely occupy a bin) but its bin_location notes
                // which room it physically sits in, until staff
                // assigns the real bin later via Edit.
                $rawBinValue = $binsInput[$key] ?? null;
                if ($rawBinValue && str_starts_with($rawBinValue, 'room:')) {
                    $itemShelfId = null;
                    $itemBinCode = substr($rawBinValue, 5) . ' — bin not yet assigned';
                } else {
                    $itemShelfId = $rawBinValue ?: null;
                    $itemBinCode = $itemShelfId ? ($binCodesById[$itemShelfId] ?? null) : null;
                }

                $engineCode = null;
                $transCode  = null;
                $pinCount   = null;
                $gearAlias  = null;
                $driveType  = $driveTypes[$key] ?? null;
                // Qty — for countable parts (spark plugs, ignition coils).
                // Defaults to cylinder count if set; staff can override.
                $partQty = isset($tpl['qty']) ? max(1, (int) ($qtys[$key] ?? $tpl['qty_default'] ?? 1)) : 1;

                if ($tpl['category'] === 'Engine') {
                    $engineCode = strtoupper(trim($oemEng[$key] ?? $oemSuggest['engine_code'] ?? '')) ?: null;
                }
                if ($tpl['category'] === 'Transmission' || $tpl['label'] === 'Complete Engine And Gear With Accessories') {
                    $transCode = strtoupper(trim($oemTrns[$key] ?? $oemSuggest['transmission_code'] ?? '')) ?: null;
                    $pinCount  = (int) ($pins[$key] ?? $oemSuggest['pin_count'] ?? 0) ?: null;
                    if ($pinCount) {
                        $gearAlias = "{$pinCount}-pin";
                        if ($driveType) $gearAlias .= " {$driveType}";
                        if ($session->model) $gearAlias .= " ({$session->make} {$session->model})";
                    } elseif ($driveType) {
                        $gearAlias = $driveType;
                    }
                }

                $compatFrom = (int) $session->year;
                $compatTo   = (int) $session->year;
                if (in_array($tpl['category'], ['Body', 'Interior', 'Seat'])) {
                    $compatFrom = max(1986, $compatFrom - 2);
                    $compatTo   = min(2027, $compatTo   + 2);
                }

                $prefix   = self::CODE_PREFIX[$tpl['category']] ?? 'PRT';
                $lastCode = DB::table('parts_inventory')
                    ->where('part_code', 'like', $prefix . '-%')
                    ->orderByDesc('id')
                    ->value('part_code');
                $nextNum  = $lastCode ? (int) substr($lastCode, strlen($prefix) + 1) + 1 : 1;
                $partCode = $prefix . '-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

                $newPartId = DB::table('parts_inventory')->insertGetId([
                    'part_code'             => $partCode,
                    'brand'                 => $session->make,
                    'model'                 => $session->model,
                    'year_from'             => $session->year,
                    'year_to'               => $session->year,
                    'compat_year_from'      => $compatFrom,
                    'compat_year_to'        => $compatTo,
                    'part_name'             => $tpl['label'],
                    'part_category'         => $tpl['category'],
                    'side'                  => $tpl['side']             ?? 'N/A',
                    'airbag_position'       => $tpl['airbag_position']  ?? null,
                    'seat_type'             => $tpl['category'] === 'Seat' ? $tpl['label'] : null,
                    'origin'                => $tpl['origin']           ?? 'N/A',
                    'body_style'            => $session->body_style ? substr($session->body_style, 0, 60) : null,
                    'donor_vin'             => $session->vin,
                    'mileage'               => $session->mileage,
                    'condition_grade'       => $grade,
                    'engine_code_oem'       => $engineCode,
                    'transmission_code_oem' => $transCode,
                    'pin_count'             => $pinCount,
                    'gear_alias'            => $gearAlias,
                    'drive_type'            => $driveType,
                    'origin_market'         => 'N/A',
                    'price_usd'             => $priceUsd,
                    'price_local'           => $rawPrice,
                    'currency_code'         => $currency['code'],
                    'location'              => $session->location,
                    'storage_shelf_id'      => $itemShelfId,
                    'bin_location'          => $itemBinCode,
                    'stock_qty'             => $partQty,
                    'status'                => 'Available',
                    'description'           => $partNote,
                    'photos'                => '[]',
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);

                // ── Auto-join an existing interchange group if this part's
                // vehicle/year already falls within one for this part name.
                // Doesn't create a NEW group automatically — only joins one
                // an admin has already confirmed, so stock aggregates
                // correctly across interchangeable years (e.g. 2009 + 2010
                // Corolla headlight-R counted as one combined "in stock").
                $matchedGroup = $interchange->findGroupByVehicle($tpl['label'], $session->make, $session->model, (int) $session->year);
                if ($matchedGroup) {
                    $interchange->assignPartToGroup($newPartId, $matchedGroup->id);
                }

                // ── Photo upload — required, per item (shown to customers) ──
                if ($request->hasFile("photos.{$key}")) {
                    $uploaded = [];
                    foreach ($request->file("photos.{$key}") as $photoFile) {
                        if ($photoFile->isValid()) {
                            $uploaded[] = $photoFile->store("parts/{$newPartId}", 'public');
                        }
                    }
                    if (!empty($uploaded)) {
                        DB::table('parts_inventory')->where('id', $newPartId)->update(['photos' => json_encode($uploaded)]);
                    }
                }

                // ── Video upload — optional, one per item ──
                if ($request->hasFile("video.{$key}")) {
                    $videoFile = $request->file("video.{$key}");
                    if ($videoFile->isValid()) {
                        $videoPath = $videoFile->store("parts/{$newPartId}/video", 'public');
                        DB::table('parts_inventory')->where('id', $newPartId)->update(['video_path' => $videoPath]);
                    }
                }

                $created++;
            }

            // ── Custom / extra parts ─────────────────────────────
            $customParts = $request->input('custom_parts', []);
            foreach ($customParts as $cpIdx => $cp) {
                if (empty($cp['name']) || empty($cp['price'])) continue;

                // Custom part price is entered in LOCAL currency — that's the
                // authoritative value now. price_usd is a frozen snapshot only.
                $cpUsd = (float) $cp['price'] / $currency['rate'];

                $cpPrefix = strtoupper(substr(
                    preg_replace('/[^A-Z0-9]/', '', strtoupper($cp['category'] ?? 'OTH')), 0, 3
                ));
                $cpCode = $cpPrefix . '-' . str_pad(DB::table('parts_inventory')->count() + 1, 5, '0', STR_PAD_LEFT);

                $newCpId = DB::table('parts_inventory')->insertGetId([
                    'part_code'             => $cpCode,
                    'brand'                 => $session->make,
                    'model'                 => $session->model,
                    'year_from'             => $session->year,
                    'year_to'               => $session->year,
                    'compat_year_from'      => $session->year,
                    'compat_year_to'        => $session->year,
                    'part_name'             => $cp['name'],
                    'part_category'         => $cp['category']    ?? 'Other',
                    'side'                  => 'N/A',
                    'airbag_position'       => null,
                    'seat_type'             => null,
                    'origin'                => 'N/A',
                    'body_style'            => $session->body_style ?? 'N/A',
                    'donor_vin'             => $session->vin,
                    'mileage'               => $session->mileage,
                    'condition_grade'       => $cp['grade']        ?? 'B',
                    'engine_code_oem'       => $cp['oem_engine']   ?? null,
                    'transmission_code_oem' => null,
                    'pin_count'             => null,
                    'gear_alias'            => null,
                    'origin_market'         => 'N/A',
                    'price_usd'             => $cpUsd,             // ← frozen snapshot only, never recalculated
                    'price_local'           => (float) $cp['price'],
                    'currency_code'         => $currency['code'],
                    'location'              => $session->location,
                    'storage_shelf_id'      => (!empty($cp['bin_id']) && !str_starts_with($cp['bin_id'], 'room:')) ? $cp['bin_id'] : null,
                    'bin_location'          => !empty($cp['bin_id'])
                        ? (str_starts_with($cp['bin_id'], 'room:')
                            ? substr($cp['bin_id'], 5) . ' — bin not yet assigned'
                            : ($binCodesById[$cp['bin_id']] ?? null))
                        : null,
                    'stock_qty'             => 1,
                    'status'                => 'Available',
                    'description'           => $cp['note']         ?? null,
                    'photos'                => '[]',
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);

                $matchedGroupCp = $interchange->findGroupByVehicle($cp['name'], $session->make, $session->model, (int) $session->year);
                if ($matchedGroupCp) {
                    $interchange->assignPartToGroup($newCpId, $matchedGroupCp->id);
                }

                // ── Photo upload — required 3-10, per custom item ──
                if ($request->hasFile("custom_parts.{$cpIdx}.photos")) {
                    $cpUploaded = [];
                    foreach ($request->file("custom_parts.{$cpIdx}.photos") as $photoFile) {
                        if ($photoFile->isValid()) {
                            $cpUploaded[] = $photoFile->store("parts/{$newCpId}", 'public');
                        }
                    }
                    if (!empty($cpUploaded)) {
                        DB::table('parts_inventory')->where('id', $newCpId)->update(['photos' => json_encode($cpUploaded)]);
                    }
                }

                // ── Video upload — optional, one per custom item ──
                if ($request->hasFile("custom_parts.{$cpIdx}.video")) {
                    $cpVideoFile = $request->file("custom_parts.{$cpIdx}.video");
                    if ($cpVideoFile->isValid()) {
                        $cpVideoPath = $cpVideoFile->store("parts/{$newCpId}/video", 'public');
                        DB::table('parts_inventory')->where('id', $newCpId)->update(['video_path' => $cpVideoPath]);
                    }
                }

                $created++;
            }

            DB::table('harvest_sessions')->where('id', $sessionId)->update([
                'parts_harvested' => count($parts) + count($customParts),
                'parts_listed'    => $created,
                'status'          => 'completed',
                'completed_at'    => now(),
                'notes'           => $request->input('session_notes'),
                'updated_at'      => now(),
            ]);

            DB::table('donor_vehicles')->where('id', $session->donor_id)->update([
                'parts_harvested' => $created,
                'updated_at'      => now(),
            ]);

            DB::commit();

            return redirect()->route('admin.harvest.complete', $sessionId)
                ->with('success', "{$created} parts listed successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Harvest saveParts failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    // =========================================================
    // GET /admin/harvest/{session}/complete
    // =========================================================
    public function complete(int $sessionId)
    {
        $session = DB::table('harvest_sessions as hs')
            ->join('donor_vehicles as dv', 'dv.id', '=', 'hs.donor_vehicle_id')
            ->join('staff as s', 's.id', '=', 'hs.staff_id')
            ->where('hs.id', $sessionId)
            ->select('hs.*', 'dv.make', 'dv.model', 'dv.year', 'dv.vin', 'dv.location', 's.name as staff_name')
            ->first();

        $newParts = DB::table('parts_inventory')
            ->where('donor_vin', $session->vin)
            ->orderBy('part_category')
            ->get();

        // Pass currency for the complete screen too
        $currency = InvoiceController::currencyForLocation($session->location ?? 'Waxahachie TX');

        return view('admin.harvest.complete', compact('session', 'newParts', 'currency'));
    }

    // =========================================================
    // PARTS LIST
    // =========================================================
    // =========================================================
    // Builds the harvest checklist from App\Data\PartNames::all()
    // — the SINGLE source of truth for all part names across the
    // system. Adding a name to PartNames.php now automatically
    // makes it appear on both Manual Add AND the harvest checklist.
    // No more double-maintenance between two separate lists.
    //
    // Special overrides (qty, category remapping) are handled via
    // the small lookup tables below rather than hardcoding them
    // into every individual array entry.
    // =========================================================
    private function getPartsList(): array
    {
        // ── Category remapping — PartNames.php uses display-friendly
        // category names; harvest needs the short internal category
        // strings used for filtering the checklist sections.
        $categoryMap = [
            'Engine & Drivetrain'    => 'Engine',
            'Transmission & Gearbox' => 'Transmission',
            'Cooling System'         => 'Cooling',
            'Fuel & Exhaust'         => 'Exhaust',
            'Brakes'                 => 'Brakes',
            'Suspension & Steering'  => 'Suspension',
            'Electrical & Electronics' => 'Electrical',
            'Interior'               => 'Interior',
            'Body & Exterior'        => 'Body',
            'Wheels & Tyres'         => 'Wheels',
            'Generic / Consumable'   => 'Consumable',
        ];

        // ── Section display names — how each category group is
        // labelled on the harvest checklist page.
        $sectionNames = [
            'Engine & Drivetrain'    => 'Engine & Powertrain',
            'Transmission & Gearbox' => 'Engine & Powertrain',
            'Fuel & Exhaust'         => 'Fuel & Exhaust',
            'Cooling System'         => 'Cooling System',
            'Brakes'                 => 'Brake System',
            'Suspension & Steering'  => 'Suspension & Steering',
            'Electrical & Electronics' => 'Electrical Components',
            'Interior'               => 'Interior',
            'Body & Exterior'        => 'Body Parts',
            'Wheels & Tyres'         => 'Wheels & Tyres',
            'Generic / Consumable'   => 'Consumables',
        ];

        // ── Per-part overrides — only needed for parts with special
        // harvest behaviour (e.g. qty input for countable small parts).
        // Key is the exact part name string from PartNames.php.
        // ── Per-part overrides — qty defaults to the donor vehicle's
        // cylinder count for parts that are one-per-cylinder.
        // The blade uses CYLINDER_COUNT (injected from the session)
        // as the default value for these entries.
        $overrides = [
            // Spark plug / coil qty = number of cylinders
            'Spark Plug (Single)'   => ['qty' => true, 'qty_source' => 'cylinders', 'qty_max' => 16],
            'Spark Plug (Set of 4)' => ['qty' => true, 'qty_source' => 'cylinders', 'qty_max' => 16],
            'Spark Plug (Set of 6)' => ['qty' => true, 'qty_source' => 'cylinders', 'qty_max' => 16],
            'Ignition Coil'         => ['qty' => true, 'qty_source' => 'cylinders', 'qty_max' => 16],
            'Glow Plug (Diesel)'    => ['qty' => true, 'qty_source' => 'cylinders', 'qty_max' => 16],
            // Mounts — multiple per vehicle
            'Engine Mount'          => ['qty' => true, 'qty_source' => 'count', 'qty_default' => 2, 'qty_max' => 6],
            'Transmission Mount'    => ['qty' => true, 'qty_source' => 'count', 'qty_default' => 1, 'qty_max' => 4],
        ];

        $sections = [];

        foreach (\App\Data\PartNames::all() as $partNamesCategory => $names) {
            // Skip consumables — they're added separately via
            // the Consumables module, not tick-listed at harvest
            if ($partNamesCategory === 'Generic / Consumable') continue;

            $sectionName = $sectionNames[$partNamesCategory] ?? $partNamesCategory;
            $category    = $categoryMap[$partNamesCategory] ?? 'Engine';

            if (!isset($sections[$sectionName])) {
                $sections[$sectionName] = [];
            }

            foreach ($names as $label) {
                // Auto-generate a unique key from the label — snake_case,
                // stripped of punctuation, max 40 chars. Stable as long
                // as the label itself doesn't change.
                $key = substr(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $label)), 0, 40);
                $key = trim($key, '_');

                $entry = ['key' => $key, 'label' => $label, 'category' => $category];

                // Apply any special overrides for this part
                if (isset($overrides[$label])) {
                    $entry = array_merge($entry, $overrides[$label]);
                }

                $sections[$sectionName][] = $entry;
            }
        }

        return $sections;
    }
}

