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
    // Bump this whenever the raw NHTSA field-extraction logic in
    // vinDecode() below changes — automatically invalidates every
    // previously-cached VIN instead of waiting up to 30 days.
    // Namespaced as "harvest" so this NEVER collides with
    // PartsSearchController's separate customer-facing VIN cache —
    // they used to share the exact same unqualified "nhtsa_{$vin}"
    // key, which meant whichever controller decoded a VIN first
    // silently determined the data shape the OTHER controller got
    // back for that same VIN.
    private const NHTSA_CACHE_VERSION = 1;

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

        $cached = Cache::remember("nhtsa_harvest_v" . self::NHTSA_CACHE_VERSION . "_{$vin}", now()->addDays(30), function () use ($vin) {
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
                    // Renamed from 'engine'/'cylinders' to 'engine_l'/'engine_cyl' —
                    // matches what create.blade.php's JS actually reads (v.engine_l,
                    // v.engine_cyl), and matches PartsSearchController's naming so
                    // both VIN-decode endpoints share one consistent data contract.
                    'engine_l'   => $r->firstWhere('Variable', 'Displacement (L)')['Value']              ?? null,
                    'drive_type' => $r->firstWhere('Variable', 'Drive Type')['Value']                    ?? null,
                    'origin'     => $r->firstWhere('Variable', 'Plant Country')['Value']                 ?? null,
                    'engine_cyl' => $r->firstWhere('Variable', 'Engine Number of Cylinders')['Value']    ?? null,
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
            (int) ($cached['year']       ?? 0),
            (int) ($cached['engine_cyl'] ?? 0),
            (float) ($cached['engine_l'] ?? 0)
        );

        // Flattened to top-level (not nested under 'oem_suggestion') to
        // match PartsSearchController's contract — some blades (manual-add)
        // already read these as flat data.oem_engine_code / data.pin_count.
        $cached['oem_engine_code']       = $oem['engine_code'];
        $cached['oem_transmission_code'] = $oem['transmission_code'];
        $cached['pin_count']             = $oem['pin_count'];
        $cached['gear_alias']            = $oem['gear_alias'];
        $cached['market_note']           = $oem['market_note'] ?? null;

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

        // ── Vehicle acquisition cost breakdown (Powerlink: VEHICLE_COST_PAYMENT) ─
        $salvageCost    = (float) ($request->salvage_cost    ?? 0);
        $towingCost     = (float) ($request->towing_cost     ?? 0);
        $processingCost = (float) ($request->processing_cost ?? 0);
        $otherCost      = (float) ($request->other_cost      ?? 0);
        $totalCost      = $salvageCost + $towingCost + $processingCost + $otherCost;
        $currency       = InvoiceController::currencyForLocation($request->location);

        $dvId = DB::table('donor_vehicles')->insertGetId([
            'vin'                   => $vin,
            'year'                  => $request->year,
            'make'                  => $request->make,
            'model'                 => $request->model,
            'trim'                  => $request->trim,
            'colour'                => $request->colour,
            'engine'                => $request->engine,
            'mileage'               => $request->mileage,
            'date_acquired'         => today(),
            'source'                => $request->source,
            'condition'             => $request->condition,
            'location'              => $request->location,
            'parts_harvested'       => 0,
            'notes'                 => $request->notes,
            // ── Phase 1 Powerlink cost fields ──
            'salvage_cost'          => $salvageCost,
            'towing_cost'           => $towingCost,
            'processing_cost'       => $processingCost,
            'other_cost'            => $otherCost,
            'total_cost'            => $totalCost,
            'currency_code'         => $currency['code'],
            'break_even_days'       => $request->break_even_days ?: null,
            'vehicle_status'        => $request->vehicle_status ?? 'Parts',
            'primary_damage_code'   => $request->primary_damage_code   ? strtoupper(substr($request->primary_damage_code, 0, 3))   : null,
            'secondary_damage_code' => $request->secondary_damage_code ? strtoupper(substr($request->secondary_damage_code, 0, 3)) : null,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        // ── Create blank ROI projection row for this vehicle ──────────────
        // Staff can fill in projected revenue per category on the checklist.
        // actual_total updates automatically as parts sell (Phase 4B listener).
        DB::table('vehicle_revenue_projections')->insert([
            'donor_vehicle_id' => $dvId,
            'location'         => $request->location,
            'currency_code'    => $currency['code'],
            'proj_total'       => 0,
            'actual_total'     => 0,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $sessionId = DB::table('harvest_sessions')->insertGetId([
            'donor_vehicle_id' => $dvId,
            'staff_id'         => Session::get('staff_id'),
            'location'         => $request->location,
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

        // ── Load part_type_rules keyed by part_name for O(1) blade lookup ─
        $partTypeRules = DB::table('part_type_rules')->get()->keyBy('part_name');

        return view('admin.harvest.checklist', [
            'session'         => $session,
            'partsByCategory' => $partsByCategory,
            'existing'        => $existing,
            'ngnRate'         => 1600,
            'currency'        => $currency,
            'harvestLocation' => $harvestLocation,
            'oemSuggest'      => $oemSuggest,
            'partTypeRules'   => $partTypeRules,
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

        $parts      = $request->input('parts', []);
        $prices     = $request->input('prices', []);
        $grades     = $request->input('grades', []);
        $notes      = $request->input('part_notes', []);
        $oemEng     = $request->input('oem_engine', []);
        $oemTrns    = $request->input('oem_transmission', []);
        $pins       = $request->input('pin_count', []);
        // ── Phase 3 new fields ────────────────────────────────────────────
        $conditions = $request->input('conditions_and_options', []);   // short condition detail per part
        $wholesale  = $request->input('price_wholesale', []);          // trade/dealer price per part

        if (empty($parts)) {
            return back()->with('error', 'Please tick at least one part before saving.');
        }

        // ── Custom parts are admin-only, to keep naming uniform ──────────
        $customPartsInput = $request->input('custom_parts', []);
        if (!empty($customPartsInput) && !in_array(Session::get('staff_role'), ['admin', 'manager'])) {
            return back()->with('error', 'Only admin or manager can add custom part names. Please select a part from the standard list, or ask them to add it.');
        }
        // Custom parts also each need their own bin (#A) — photo is
        // optional here too now, same reasoning as the ticked-parts
        // fix above.
        foreach ($customPartsInput as $idx => $cp) {
            if (empty($cp['bin_id'])) {
                return back()->with('error', 'Every custom part needs a bin selected before saving.');
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

        // ── A bin can only go to ONE item per submission — UNLESS staff
        // explicitly confirmed grouping via the UI prompt, in which case
        // the front-end sends grouped_bins[] listing the bin IDs that
        // were deliberately shared. Those are excluded from the duplicate
        // check below.
        // "room:<name>" placeholder values are also excluded — many parts
        // CAN share the same room-only placeholder since none of them is
        // actually claiming a real physical bin.
        $confirmedGroupedBins = $request->input('grouped_bins', []);
        $allBinIdsThisBatch   = array_values(array_filter($binsInput, fn($v) =>
            !empty($v) &&
            !str_starts_with($v, 'room:') &&
            !in_array($v, $confirmedGroupedBins)  // exclude confirmed grouped bins
        ));
        foreach ($customPartsInput as $cp) {
            if (!empty($cp['bin_id']) &&
                !str_starts_with($cp['bin_id'], 'room:') &&
                !in_array($cp['bin_id'], $confirmedGroupedBins)) {
                $allBinIdsThisBatch[] = $cp['bin_id'];
            }
        }
        $duplicateBins = array_diff_assoc($allBinIdsThisBatch, array_unique($allBinIdsThisBatch));
        if (!empty($duplicateBins)) {
            $dupeBinCodes = DB::table('storage_shelves')->whereIn('id', array_unique($duplicateBins))->pluck('full_bin_code')->implode(', ');
            return back()->with('error', "The same bin was selected for more than one item in this batch ({$dupeBinCodes}) — each bin can only hold one part unless you confirmed grouping in the checklist. Please choose a different bin or go back and confirm grouping.");
        }

        // FIXED: this used to hard-block the entire save if ANY ticked
        // part had zero photos — inconsistent with manual add, which
        // has always allowed saving with no photo and relying on the
        // "photo coming soon" placeholder at display time (see the
        // comment in InventoryController::store()). Harvest should
        // behave the same way — staff can add photos later without
        // being blocked from saving the part at all right now.

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

        // ── Load part_type_rules for auto-flagging at save time ───────────
        $partTypeRules = DB::table('part_type_rules')->get()->keyBy('part_name');

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

                if ($tpl['category'] === 'Engine') {
                    $engineCode = strtoupper(trim($oemEng[$key] ?? $oemSuggest['engine_code'] ?? '')) ?: null;
                }
                if ($tpl['category'] === 'Transmission') {
                    $transCode = strtoupper(trim($oemTrns[$key] ?? $oemSuggest['transmission_code'] ?? '')) ?: null;
                    $pinCount  = (int) ($pins[$key] ?? $oemSuggest['pin_count'] ?? 0) ?: null;
                    if ($pinCount) {
                        $gearAlias = "{$pinCount}-pin gear";
                        if ($session->model) $gearAlias .= " ({$session->make} {$session->model})";
                    }
                }

                // ── Quantity per vehicle — most parts are qty 1, but
                // ignition coils and spark plugs physically occur
                // multiple times per vehicle (varies by cylinder
                // count). Staff enters the actual count directly on
                // the checklist row via a "qty" input — simpler and
                // more reliable than auto-deriving it from an OEM
                // engine-code lookup, since it works even when the
                // donor vehicle's engine code is unknown or unset.
                $stockQty = 1;
                if (in_array($key, ['ignition_coil', 'spark_plug'])) {
                    $qtyInput = $request->input('qty', []);
                    $stockQty = max(1, (int) ($qtyInput[$key] ?? 1));
                }

                // ── Powerlink Phase 3: auto-set flags from part_type_rules ──
                $rule                = $partTypeRules->get($tpl['label']);
                $legalTraceRequired  = $rule ? (int) $rule->legal_trace_required  : 0;
                $isMajorComponent    = $rule ? (int) $rule->is_major_component    : 0;

                // Wholesale price — from form input if staff entered one,
                // otherwise auto-calc from part_type_rules.wholesale_margin_pct.
                // Falls back to null if no margin is defined for this part.
                $rawWholesale = isset($wholesale[$key]) && $wholesale[$key] !== ''
                    ? (float) $wholesale[$key]
                    : null;

                if ($rawWholesale === null && $rule && $rule->wholesale_margin_pct > 0 && $rawPrice > 0) {
                    $rawWholesale = round($rawPrice * (1 - ($rule->wholesale_margin_pct / 100)), 2);
                }

                // Conditions & options — max 36 chars matching Powerlink spec
                $condAndOpts = isset($conditions[$key])
                    ? substr(trim($conditions[$key]), 0, 36)
                    : null;

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

                // NEW: this template's own label/category IS the
                // standardized taxonomy now (see the migration that
                // folded getPartsList() into part_terminology) — so
                // every harvest-templated part automatically links to
                // its terminology entry, no staff action needed.
                $terminologyId = DB::table('part_terminology')
                    ->where('category', $tpl['category'])
                    ->where('standard_name', $tpl['label'])
                    ->value('id');

                $newPartId = DB::table('parts_inventory')->insertGetId([
                    'part_code'             => $partCode,
                    'brand'                 => $session->make,
                    'model'                 => $session->model,
                    'year_from'             => $session->year,
                    'year_to'               => $session->year,
                    'compat_year_from'      => $compatFrom,
                    'compat_year_to'        => $compatTo,
                    // FIXED: both of these were already being collected
                    // (trim on the harvest session, displacement via
                    // parseEngineSize()) and used for OEM code matching
                    // elsewhere in this file — but never actually
                    // written onto the saved part itself, so every
                    // harvested part silently lost this data at save
                    // time regardless of what staff entered on Step 2.
                    'donor_trim'            => $session->trim ?: null,
                    'engine_displacement'   => ($dispL = $this->parseEngineSize($session->engine ?? null)) > 0 ? $dispL . 'L' : null,
                    'part_name'             => $tpl['label'],
                    'part_terminology_id'   => $terminologyId,
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
                    'origin_market'         => 'N/A',
                    'price_usd'             => $priceUsd,          // ← frozen snapshot only, never recalculated
                    'price_local'           => $rawPrice,         // ← authoritative price, fixed currency
                    'currency_code'         => $currency['code'],
                    'location'              => $session->location,
                    'storage_shelf_id'        => $itemShelfId,
                    'bin_location'            => $itemBinCode,
                    'stock_qty'               => $stockQty,
                    'status'                  => 'Available',
                    'description'             => $partNote,
                    'photos'                  => '[]',
                    // ── Powerlink Phase 3 fields ──────────────────────────
                    'price_wholesale'         => $rawWholesale,
                    'conditions_and_options'  => $condAndOpts,
                    'legal_trace_required'    => $legalTraceRequired,
                    'is_major_component'      => $isMajorComponent,
                    // ─────────────────────────────────────────────────────
                    'created_at'              => now(),
                    'updated_at'              => now(),
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

                // NEW: custom parts are free-typed (this is the
                // escape hatch for anything not on the standard
                // checklist), so this is a best-effort match only —
                // stays null if the typed name doesn't line up with
                // an existing standardized term, which is expected
                // and fine for genuinely one-off items.
                $cpTerminologyId = DB::table('part_terminology')
                    ->where('category', $cp['category'] ?? 'Other')
                    ->where('standard_name', $cp['name'])
                    ->value('id');

                $newCpId = DB::table('parts_inventory')->insertGetId([
                    'part_code'             => $cpCode,
                    'brand'                 => $session->make,
                    'model'                 => $session->model,
                    'year_from'             => $session->year,
                    'year_to'               => $session->year,
                    'compat_year_from'      => $session->year,
                    'compat_year_to'        => $session->year,
                    'donor_trim'            => $session->trim ?: null,
                    'engine_displacement'   => ($dispL = $this->parseEngineSize($session->engine ?? null)) > 0 ? $dispL . 'L' : null,
                    'part_name'             => $cp['name'],
                    'part_terminology_id'   => $cpTerminologyId,
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
    private function getPartsList(): array
    {
        return [
            'Engine & Powertrain' => [
                ['key'=>'engine',              'label'=>'Complete Engine Assembly',         'category'=>'Engine'],
                ['key'=>'engine_gear_complete','label'=>'Complete Engine And Gear With Accessories', 'category'=>'Engine'],
                ['key'=>'engine_block',        'label'=>'Engine Block',                     'category'=>'Engine'],
                ['key'=>'cylinder_head',       'label'=>'Cylinder Head',                    'category'=>'Engine'],
                ['key'=>'intake_manifold',     'label'=>'Intake Manifold',                  'category'=>'Engine'],
                ['key'=>'throttle_body',       'label'=>'Throttle Body',                    'category'=>'Engine'],
                ['key'=>'fuel_injectors',      'label'=>'Fuel Injectors (Set)',             'category'=>'Engine'],
                ['key'=>'alternator',          'label'=>'Alternator',                       'category'=>'Engine'],
                ['key'=>'starter',             'label'=>'Starter Motor',                    'category'=>'Engine'],
                ['key'=>'power_steering_pump', 'label'=>'Power Steering Pump',              'category'=>'Engine'],
                ['key'=>'ac_compressor',       'label'=>'A/C Compressor',                   'category'=>'Engine'],
                ['key'=>'turbocharger',        'label'=>'Turbocharger / Supercharger',      'category'=>'Engine'],
                ['key'=>'engine_harness',      'label'=>'Engine Wiring Harness',            'category'=>'Electrical'],
                ['key'=>'ecm',                 'label'=>'Engine Control Module (ECM/PCM)',  'category'=>'Electrical'],
                ['key'=>'transmission',        'label'=>'Transmission / Gearbox (Auto)',    'category'=>'Transmission'],
                ['key'=>'transmission_manual', 'label'=>'Transmission / Gearbox (Manual)', 'category'=>'Transmission'],
                ['key'=>'transfer_case',       'label'=>'Transfer Case',                    'category'=>'Transmission'],
                ['key'=>'differential_front',  'label'=>'Front Differential',               'category'=>'Transmission'],
                ['key'=>'differential_rear',   'label'=>'Rear Differential',                'category'=>'Transmission'],
                ['key'=>'driveshaft_front',    'label'=>'Driveshaft — Front',               'category'=>'Transmission'],
                ['key'=>'driveshaft_rear',     'label'=>'Driveshaft — Rear',                'category'=>'Transmission'],
                ['key'=>'axle_fl',             'label'=>'Axle / CV Shaft — Front Left',     'category'=>'Transmission'],
                ['key'=>'axle_fr',             'label'=>'Axle / CV Shaft — Front Right',    'category'=>'Transmission'],
                ['key'=>'axle_rl',             'label'=>'Axle / CV Shaft — Rear Left',      'category'=>'Transmission'],
                ['key'=>'axle_rr',             'label'=>'Axle / CV Shaft — Rear Right',     'category'=>'Transmission'],
                ['key'=>'oil_pan',             'label'=>'Oil Pan',                          'category'=>'Engine'],
                ['key'=>'valve_cover',         'label'=>'Valve Cover / Cam Cover',          'category'=>'Engine'],
                ['key'=>'timing_chain_kit',    'label'=>'Timing Chain / Belt Kit',          'category'=>'Engine'],
                ['key'=>'flywheel',            'label'=>'Flywheel / Flexplate',             'category'=>'Engine'],
                ['key'=>'ignition_coil',       'label'=>'Ignition Coil',                    'category'=>'Engine'],
                ['key'=>'spark_plug',          'label'=>'Spark Plug',                       'category'=>'Engine'],
            ],
            'Cooling System' => [
                ['key'=>'radiator',           'label'=>'Radiator',                          'category'=>'Cooling'],
                ['key'=>'cooling_fan',        'label'=>'Cooling Fan Assembly',              'category'=>'Cooling'],
                ['key'=>'fan_clutch',         'label'=>'Fan Clutch',                        'category'=>'Cooling'],
                ['key'=>'intercooler',        'label'=>'Intercooler',                       'category'=>'Cooling'],
                ['key'=>'coolant_reservoir',  'label'=>'Coolant Reservoir / Overflow Tank', 'category'=>'Cooling'],
                ['key'=>'thermostat_housing', 'label'=>'Thermostat Housing',                'category'=>'Cooling'],
                ['key'=>'water_pump',         'label'=>'Water Pump',                        'category'=>'Cooling'],
                ['key'=>'ac_condenser',       'label'=>'A/C Condenser',                    'category'=>'Cooling'],
                ['key'=>'ac_evaporator',      'label'=>'A/C Evaporator',                   'category'=>'Cooling'],
                ['key'=>'heater_core',        'label'=>'Heater Core',                       'category'=>'Cooling'],
                ['key'=>'blower_motor',       'label'=>'Blower Motor',                      'category'=>'Cooling'],
            ],
            'Suspension & Steering' => [
                ['key'=>'steering_rack',      'label'=>'Steering Rack and Pinion',          'category'=>'Suspension'],
                ['key'=>'steering_column',    'label'=>'Steering Column',                   'category'=>'Suspension'],
                ['key'=>'steering_wheel',     'label'=>'Steering Wheel',                    'category'=>'Interior'],
                ['key'=>'control_arm_fl',     'label'=>'Control Arm — Front Left',          'category'=>'Suspension'],
                ['key'=>'control_arm_fr',     'label'=>'Control Arm — Front Right',         'category'=>'Suspension'],
                ['key'=>'control_arm_rl',     'label'=>'Control Arm — Rear Left',           'category'=>'Suspension'],
                ['key'=>'control_arm_rr',     'label'=>'Control Arm — Rear Right',          'category'=>'Suspension'],
                ['key'=>'knuckle_fl',         'label'=>'Spindle / Knuckle — Front Left',    'category'=>'Suspension'],
                ['key'=>'knuckle_fr',         'label'=>'Spindle / Knuckle — Front Right',   'category'=>'Suspension'],
                ['key'=>'strut_fl',           'label'=>'Strut Assembly — Front Left',       'category'=>'Suspension'],
                ['key'=>'strut_fr',           'label'=>'Strut Assembly — Front Right',      'category'=>'Suspension'],
                ['key'=>'strut_rl',           'label'=>'Strut Assembly — Rear Left',        'category'=>'Suspension'],
                ['key'=>'strut_rr',           'label'=>'Strut Assembly — Rear Right',       'category'=>'Suspension'],
                ['key'=>'shock_fl',           'label'=>'Shock Absorber — Front Left',       'category'=>'Suspension'],
                ['key'=>'shock_fr',           'label'=>'Shock Absorber — Front Right',      'category'=>'Suspension'],
                ['key'=>'shock_rl',           'label'=>'Shock Absorber — Rear Left',        'category'=>'Suspension'],
                ['key'=>'shock_rr',           'label'=>'Shock Absorber — Rear Right',       'category'=>'Suspension'],
                ['key'=>'coil_spring_front',  'label'=>'Coil Spring — Front',               'category'=>'Suspension'],
                ['key'=>'coil_spring_rear',   'label'=>'Coil Spring — Rear',                'category'=>'Suspension'],
                ['key'=>'sway_bar_front',     'label'=>'Sway Bar — Front',                  'category'=>'Suspension'],
                ['key'=>'sway_bar_rear',      'label'=>'Sway Bar — Rear',                   'category'=>'Suspension'],
                ['key'=>'hub_bearing_fl',     'label'=>'Wheel Hub & Bearing — Front Left',  'category'=>'Suspension'],
                ['key'=>'hub_bearing_fr',     'label'=>'Wheel Hub & Bearing — Front Right', 'category'=>'Suspension'],
                ['key'=>'hub_bearing_rl',     'label'=>'Wheel Hub & Bearing — Rear Left',   'category'=>'Suspension'],
                ['key'=>'hub_bearing_rr',     'label'=>'Wheel Hub & Bearing — Rear Right',  'category'=>'Suspension'],
                ['key'=>'subframe_front',     'label'=>'Subframe — Front',                  'category'=>'Suspension'],
                ['key'=>'subframe_rear',      'label'=>'Subframe — Rear',                   'category'=>'Suspension'],
            ],
            'Brake System' => [
                ['key'=>'caliper_fl',      'label'=>'Brake Caliper — Front Left',   'category'=>'Brakes'],
                ['key'=>'caliper_fr',      'label'=>'Brake Caliper — Front Right',  'category'=>'Brakes'],
                ['key'=>'caliper_rl',      'label'=>'Brake Caliper — Rear Left',    'category'=>'Brakes'],
                ['key'=>'caliper_rr',      'label'=>'Brake Caliper — Rear Right',   'category'=>'Brakes'],
                ['key'=>'master_cylinder', 'label'=>'Brake Master Cylinder',        'category'=>'Brakes'],
                ['key'=>'abs_module',      'label'=>'ABS Module / Pump',            'category'=>'Brakes'],
                ['key'=>'brake_booster',   'label'=>'Brake Booster / Servo',        'category'=>'Brakes'],
                ['key'=>'rotor_fl',        'label'=>'Brake Rotor — Front Left',     'category'=>'Brakes'],
                ['key'=>'rotor_fr',        'label'=>'Brake Rotor — Front Right',    'category'=>'Brakes'],
                ['key'=>'rotor_rl',        'label'=>'Brake Rotor — Rear Left',      'category'=>'Brakes'],
                ['key'=>'rotor_rr',        'label'=>'Brake Rotor — Rear Right',     'category'=>'Brakes'],
            ],
            'Electrical Components' => [
                ['key'=>'fuse_box_engine',   'label'=>'Fuse Box — Engine Bay',                    'category'=>'Electrical'],
                ['key'=>'fuse_box_cabin',    'label'=>'Fuse Box — Cabin / Interior',              'category'=>'Electrical'],
                ['key'=>'bcm',               'label'=>'Body Control Module (BCM)',                 'category'=>'Electrical'],
                ['key'=>'cluster',           'label'=>'Instrument Cluster / Speedometer',          'category'=>'Electrical'],
                ['key'=>'ignition_switch',   'label'=>'Ignition Switch',                          'category'=>'Electrical'],
                ['key'=>'window_motor_fl',   'label'=>'Window Motor — Front Left',                'category'=>'Electrical'],
                ['key'=>'window_motor_fr',   'label'=>'Window Motor — Front Right',               'category'=>'Electrical'],
                ['key'=>'window_motor_rl',   'label'=>'Window Motor — Rear Left',                 'category'=>'Electrical'],
                ['key'=>'window_motor_rr',   'label'=>'Window Motor — Rear Right',                'category'=>'Electrical'],
                ['key'=>'wiper_motor_front', 'label'=>'Wiper Motor — Front',                      'category'=>'Electrical'],
                ['key'=>'sensor_maf',        'label'=>'Mass Air Flow Sensor (MAF)',                'category'=>'Electrical'],
                ['key'=>'sensor_map',        'label'=>'MAP Sensor',                               'category'=>'Electrical'],
                ['key'=>'sensor_crank',      'label'=>'Crankshaft Position Sensor',               'category'=>'Electrical'],
                ['key'=>'sensor_cam',        'label'=>'Camshaft Position Sensor',                 'category'=>'Electrical'],
                ['key'=>'sensor_o2_up',      'label'=>'Oxygen Sensor — Upstream',                 'category'=>'Electrical'],
                ['key'=>'sensor_o2_dn',      'label'=>'Oxygen Sensor — Downstream',               'category'=>'Electrical'],
                ['key'=>'radio',             'label'=>'Radio / Infotainment / Navigation',         'category'=>'Electrical'],
                ['key'=>'climate_module',    'label'=>'Climate Control Module',                   'category'=>'Electrical'],
                ['key'=>'reverse_camera',    'label'=>'Reverse / Backup Camera',                  'category'=>'Electrical'],
                ['key'=>'battery',           'label'=>'Battery',                                  'category'=>'Electrical'],
            ],
            'Body Parts' => [
                ['key'=>'hood',              'label'=>'Hood / Bonnet',             'category'=>'Body'],
                ['key'=>'front_bumper',      'label'=>'Front Bumper Cover',        'category'=>'Body'],
                ['key'=>'rear_bumper',       'label'=>'Rear Bumper Cover',         'category'=>'Body'],
                ['key'=>'fender_l',          'label'=>'Front Fender — Left',       'category'=>'Body'],
                ['key'=>'fender_r',          'label'=>'Front Fender — Right',      'category'=>'Body'],
                ['key'=>'door_fl',           'label'=>'Door Shell — Front Left',   'category'=>'Body'],
                ['key'=>'door_fr',           'label'=>'Door Shell — Front Right',  'category'=>'Body'],
                ['key'=>'door_rl',           'label'=>'Door Shell — Rear Left',    'category'=>'Body'],
                ['key'=>'door_rr',           'label'=>'Door Shell — Rear Right',   'category'=>'Body'],
                ['key'=>'tailgate',          'label'=>'Tailgate',                  'category'=>'Body'],
                ['key'=>'trunk_lid',         'label'=>'Trunk Lid / Boot Lid',      'category'=>'Body'],
                ['key'=>'roof_panel',        'label'=>'Roof Panel',                'category'=>'Body'],
                ['key'=>'quarter_panel_l',   'label'=>'Quarter Panel — Left',      'category'=>'Body'],
                ['key'=>'quarter_panel_r',   'label'=>'Quarter Panel — Right',     'category'=>'Body'],
                ['key'=>'grille',            'label'=>'Grille',                    'category'=>'Body'],
                ['key'=>'mirror_l',          'label'=>'Side Mirror — Left',        'category'=>'Body'],
                ['key'=>'mirror_r',          'label'=>'Side Mirror — Right',       'category'=>'Body'],
            ],
            'Lighting' => [
                ['key'=>'headlight_l',      'label'=>'Headlight Assembly — Left',   'category'=>'Body'],
                ['key'=>'headlight_r',      'label'=>'Headlight Assembly — Right',  'category'=>'Body'],
                ['key'=>'tail_light_l',     'label'=>'Tail Light Assembly — Left',  'category'=>'Body'],
                ['key'=>'tail_light_r',     'label'=>'Tail Light Assembly — Right', 'category'=>'Body'],
                ['key'=>'fog_light_l',      'label'=>'Fog Light — Left',            'category'=>'Body'],
                ['key'=>'fog_light_r',      'label'=>'Fog Light — Right',           'category'=>'Body'],
                ['key'=>'third_brake_light','label'=>'Third Brake Light (CHMSL)',   'category'=>'Body'],
            ],
            'Glass' => [
                ['key'=>'windshield',      'label'=>'Windshield / Front Glass',  'category'=>'Body'],
                ['key'=>'door_glass_fl',   'label'=>'Door Glass — Front Left',   'category'=>'Body'],
                ['key'=>'door_glass_fr',   'label'=>'Door Glass — Front Right',  'category'=>'Body'],
                ['key'=>'door_glass_rl',   'label'=>'Door Glass — Rear Left',    'category'=>'Body'],
                ['key'=>'door_glass_rr',   'label'=>'Door Glass — Rear Right',   'category'=>'Body'],
                ['key'=>'rear_glass',      'label'=>'Rear Window Glass',         'category'=>'Body'],
                ['key'=>'sunroof_glass',   'label'=>'Sunroof / Moonroof Glass',  'category'=>'Body'],
            ],
            'Interior' => [
                ['key'=>'seat_driver',    'label'=>'Seat — Front Driver',           'category'=>'Seat'],
                ['key'=>'seat_passenger', 'label'=>'Seat — Front Passenger',        'category'=>'Seat'],
                ['key'=>'seat_rear_l',    'label'=>'Seat — Rear Left',              'category'=>'Seat'],
                ['key'=>'seat_rear_r',    'label'=>'Seat — Rear Right',             'category'=>'Seat'],
                ['key'=>'seatbelt_fl',    'label'=>'Seat Belt — Front Left',        'category'=>'Interior'],
                ['key'=>'seatbelt_fr',    'label'=>'Seat Belt — Front Right',       'category'=>'Interior'],
                ['key'=>'seatbelt_rear',  'label'=>'Seat Belt — Rear',              'category'=>'Interior'],
                ['key'=>'dashboard',      'label'=>'Dashboard / Instrument Panel',  'category'=>'Interior'],
                ['key'=>'center_console', 'label'=>'Center Console',                'category'=>'Interior'],
                ['key'=>'door_panel_fl',  'label'=>'Door Panel — Front Left',       'category'=>'Interior'],
                ['key'=>'door_panel_fr',  'label'=>'Door Panel — Front Right',      'category'=>'Interior'],
                ['key'=>'door_panel_rl',  'label'=>'Door Panel — Rear Left',        'category'=>'Interior'],
                ['key'=>'door_panel_rr',  'label'=>'Door Panel — Rear Right',       'category'=>'Interior'],
                ['key'=>'carpet',         'label'=>'Carpet / Floor Mat Set',        'category'=>'Interior'],
                ['key'=>'headliner',      'label'=>'Headliner / Roof Lining',       'category'=>'Interior'],
                ['key'=>'glove_box',      'label'=>'Glove Box',                     'category'=>'Interior'],
                ['key'=>'rearview_mirror','label'=>'Rearview Mirror (Interior)',    'category'=>'Interior'],
                ['key'=>'gear_selector',  'label'=>'Gear Shift / Selector Assembly','category'=>'Interior'],
            ],
            'Airbags & Safety' => [
                ['key'=>'airbag_driver',    'label'=>'Airbag — Driver (Steering Wheel)',  'category'=>'Airbag'],
                ['key'=>'airbag_passenger', 'label'=>'Airbag — Passenger (Dashboard)',    'category'=>'Airbag'],
                ['key'=>'airbag_curtain_l', 'label'=>'Airbag — Side Curtain Left',        'category'=>'Airbag'],
                ['key'=>'airbag_curtain_r', 'label'=>'Airbag — Side Curtain Right',       'category'=>'Airbag'],
                ['key'=>'airbag_knee',      'label'=>'Airbag — Knee',                     'category'=>'Airbag'],
                ['key'=>'airbag_module',    'label'=>'Airbag Control Module (ACM)',        'category'=>'Airbag'],
            ],
            'Wheels & Tyres' => [
                ['key'=>'wheels_set',   'label'=>'Alloy Wheel Rims (Set of 4)',  'category'=>'Wheels'],
                ['key'=>'wheel_single', 'label'=>'Alloy Wheel Rim (Single)',     'category'=>'Wheels'],
                ['key'=>'spare_wheel',  'label'=>'Spare Wheel / Spare Tyre',    'category'=>'Wheels'],
                ['key'=>'tyres_set',    'label'=>'Tyres (Set of 4)',             'category'=>'Wheels'],
            ],
            'Fuel & Exhaust' => [
                ['key'=>'fuel_tank',           'label'=>'Fuel Tank',             'category'=>'Fuel'],
                ['key'=>'fuel_pump',           'label'=>'Fuel Pump',             'category'=>'Fuel'],
                ['key'=>'catalytic_converter', 'label'=>'Catalytic Converter',   'category'=>'Exhaust'],
                ['key'=>'exhaust_manifold',    'label'=>'Exhaust Manifold',      'category'=>'Exhaust'],
                ['key'=>'muffler',             'label'=>'Muffler / Silencer',    'category'=>'Exhaust'],
            ],
            'Hybrid & EV' => [
                ['key'=>'hv_battery',    'label'=>'High-Voltage Battery Pack',     'category'=>'Electrical'],
                ['key'=>'inverter',      'label'=>'Inverter / Power Control Unit', 'category'=>'Electrical'],
                ['key'=>'electric_motor','label'=>'Electric Motor',                'category'=>'Engine'],
                ['key'=>'charging_port', 'label'=>'Charging Port Assembly',        'category'=>'Electrical'],
            ],
        ];
    }

    // =========================================================
    // GET /parts/models?make=TOYOTA
    // AJAX — returns model list for a given make.
    // Used by harvest create form, manual-add form, and
    // compatibility checker make/model dropdowns.
    // =========================================================
    public function models(Request $request)
    {
        $make   = strtoupper(trim($request->get('make', '')));
        if (empty($make)) {
            return response()->json(['models' => []]);
        }
        $models = \App\Data\VehicleDatabase::modelsForMake($make);
        return response()->json(['models' => $models]);
    }
}
