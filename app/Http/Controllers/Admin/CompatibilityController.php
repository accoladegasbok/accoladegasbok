<?php
// FILE: app/Http/Controllers/Admin/CompatibilityController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InterchangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Compatibility Checker — Powerlink-style vehicle-centric search.
 *
 * Resolution order:
 *   1. INTERCHANGE GROUPS — part_interchange_groups → part_interchange_vehicles
 *   2. DIRECT INVENTORY MATCH — compat_year_from / compat_year_to
 *   3. AUTO HEURISTIC — InterchangeService OEM-code matching (Ladipo algorithm)
 */
class CompatibilityController extends Controller
{
    public function __construct(private InterchangeService $interchange) {}

    // =========================================================
    // GET /admin/compatibility
    // =========================================================
    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));

        $query = DB::table('part_interchange_groups as g')
            ->leftJoin('part_interchange_vehicles as v', 'v.group_id', '=', 'g.id')
            ->leftJoin('parts_inventory as pi', 'pi.interchange_group_id', '=', 'g.id')
            ->select(
                'g.id as group_id',
                'g.group_code',
                'g.part_name',
                'g.part_category',
                'g.source',
                'g.notes',
                DB::raw('COUNT(DISTINCT v.id) as vehicle_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN pi.status = "Available" THEN pi.id END) as parts_available'),
                DB::raw('COUNT(DISTINCT pi.id) as parts_total')
            )
            ->groupBy('g.id', 'g.group_code', 'g.part_name', 'g.part_category', 'g.source', 'g.notes');

        if ($q !== '') {
            $query->where(function ($sq) use ($q) {
                $sq->where('g.part_name',   'like', "%{$q}%")
                   ->orWhere('g.group_code', 'like', "%{$q}%")
                   ->orWhere('v.make',       'like', "%{$q}%")
                   ->orWhere('v.model',      'like', "%{$q}%");
            });
        }

        $groups        = $query->orderBy('g.part_name')->paginate(25)->withQueryString();
        $totalGroups   = DB::table('part_interchange_groups')->count();
        $totalVehicles = DB::table('part_interchange_vehicles')->count();
        $manualGroups  = DB::table('part_interchange_groups')->where('source', 'manual')->count();

        return view('admin.compatibility.index', compact(
            'groups', 'q', 'totalGroups', 'totalVehicles', 'manualGroups'
        ));
    }

    // =========================================================
    // POST /admin/compatibility/check — AJAX vehicle-centric search
    // =========================================================
    public function check(Request $request)
    {
        $request->validate([
            'make'  => 'required|string|max:60',
            'model' => 'required|string|max:80',
            'year'  => 'required|integer|min:1980|max:2030',
        ]);

        $make      = strtoupper(trim($request->make));
        $model     = strtoupper(trim($request->model));
        $year      = (int) $request->year;
        $partName  = trim($request->get('part_name', ''));
        $category  = trim($request->get('category', ''));

        $results = collect();

        // ── Tier 1: Interchange Groups ─────────────────────────────
        $matchingGroups = DB::table('part_interchange_groups as g')
            ->join('part_interchange_vehicles as v', 'v.group_id', '=', 'g.id')
            ->where('v.make',      $make)
            ->where('v.model',     $model)
            ->where('v.year_from', '<=', $year)
            ->where('v.year_to',   '>=', $year)
            ->select('g.*')
            ->distinct()
            ->get();

        foreach ($matchingGroups as $group) {
            $query = DB::table('parts_inventory')
                ->where('interchange_group_id', $group->id)
                ->where('status', 'Available');
            if ($partName) $query->where('part_name', 'like', "%{$partName}%");
            if ($category) $query->where('part_category', $category);
            $parts    = $query->get();
            $vehicles = $this->interchange->vehiclesForGroup($group->id);
            $stock    = $this->interchange->aggregatedStockBreakdown($group->id);
            foreach ($parts as $part) {
                $results->push($this->formatResult($part, 'interchange', $group, $vehicles, $stock));
            }
        }

        // ── Tier 2: Direct inventory year-range match ──────────────
        $directIds = $results->pluck('id')->toArray();
        $directQuery = DB::table('parts_inventory')
            ->where('brand',            $make)
            ->where('model',            $model)
            ->where('compat_year_from', '<=', $year)
            ->where('compat_year_to',   '>=', $year)
            ->where('status',           'Available')
            ->whereNull('interchange_group_id')
            ->whereNotIn('id', $directIds);
        if ($partName) $directQuery->where('part_name', 'like', "%{$partName}%");
        if ($category) $directQuery->where('part_category', $category);
        foreach ($directQuery->get() as $part) {
            $results->push($this->formatResult($part, 'direct', null, collect(), null));
        }

        // ── Tier 3: OEM-code heuristic (Ladipo algorithm) ──────────
        $oemQuery = DB::table('parts_inventory')
            ->where('brand', $make)->where('model', $model)
            ->where('year_from', '<=', $year)->where('year_to', '>=', $year)
            ->whereNotNull('engine_code_oem')
            ->whereNull('interchange_group_id')
            ->where('status', 'Available');
        if ($partName) $oemQuery->where('part_name', 'like', "%{$partName}%");
        $oemParts = $oemQuery->select('part_name', 'engine_code_oem', 'transmission_code_oem')
            ->distinct()->limit(5)->get();

        $heuristicSuggestions = collect();
        foreach ($oemParts as $oemPart) {
            $heuristic = $this->interchange->interchangeFor(
                $oemPart->part_name,
                $oemPart->engine_code_oem,
                $oemPart->transmission_code_oem
            );
            if ($heuristic['found'] && $heuristic['vehicles']->isNotEmpty()) {
                $heuristicSuggestions->push([
                    'part_name'   => $oemPart->part_name,
                    'engine_code' => $oemPart->engine_code_oem,
                    'vehicles'    => $heuristic['vehicles']->take(5)->map(
                        fn($v) => "{$v->make} {$v->model} ({$v->year_from}-{$v->year_to})"
                    ),
                    'source' => 'auto_heuristic',
                ]);
            }
        }

        // ── OemDatabase interchange reference — check both trans + engine ──
        $oem = \App\Data\OemDatabase::lookup($make, $model, $year);
        $allInterchange = \App\Data\OemDatabase::interchange();
        $interchangeReference = collect();

        // Check transmission code first
        if ($oem['transmission_code'] && isset($allInterchange[$oem['transmission_code']])) {
            $interchangeReference = $interchangeReference->merge($allInterchange[$oem['transmission_code']]);
        }
        // Also check engine code — merge to get the full picture
        if ($oem['engine_code'] && isset($allInterchange[$oem['engine_code']])) {
            $interchangeReference = $interchangeReference->merge($allInterchange[$oem['engine_code']]);
        }
        // Deduplicate
        $interchangeReference = $interchangeReference->unique()->values()->toArray();

        return response()->json([
            'count'                 => $results->count(),
            'results'               => $results->values(),
            'suggestions'           => $heuristicSuggestions->values(),
            'interchange_reference' => $interchangeReference,
            'oem'                   => $oem,
            'search'                => "{$year} {$make} {$model}" . ($partName ? " · {$partName}" : ''),
        ]);
    }

    // =========================================================
    // POST /admin/compatibility/save
    // =========================================================
    public function save(Request $request)
    {
        $request->validate([
            'part_id'   => 'required|integer|exists:parts_inventory,id',
            'make'      => 'required|string|max:60',
            'model'     => 'required|string|max:80',
            'year_from' => 'required|integer|min:1980',
            'year_to'   => 'required|integer|min:1980|gte:year_from',
        ]);

        $part = DB::table('parts_inventory')->where('id', $request->part_id)->first();
        if ($part) {
            $existingGroup = $this->interchange->findGroupByVehicle(
                $part->part_name,
                strtoupper($request->make),
                strtoupper($request->model),
                (int) $request->year_from
            );
            if ($existingGroup) {
                $this->interchange->addVehicleToGroup(
                    $existingGroup->id,
                    strtoupper($request->make),
                    strtoupper($request->model),
                    $request->year_from,
                    $request->year_to
                );
                $this->interchange->assignPartToGroup($part->id, $existingGroup->id);
                return response()->json(['success' => true, 'method' => 'interchange_group']);
            }
        }

        $exists = DB::table('parts_compatibility')
            ->where('part_id', $request->part_id)
            ->where('make',    strtoupper($request->make))
            ->where('model',   strtoupper($request->model))
            ->where('year_from', $request->year_from)
            ->where('year_to',   $request->year_to)
            ->exists();

        if (!$exists) {
            DB::table('parts_compatibility')->insert([
                'part_id'    => $request->part_id,
                'make'       => strtoupper($request->make),
                'model'      => strtoupper($request->model),
                'year_from'  => $request->year_from,
                'year_to'    => $request->year_to,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return response()->json(['success' => true, 'method' => 'flat_table']);
    }

    // =========================================================
    // DELETE /admin/compatibility/{id}
    // =========================================================
    public function destroy(int $id)
    {
        DB::table('parts_compatibility')->where('id', $id)->delete();
        return back()->with('success', 'Compatibility record removed.');
    }

    // =========================================================
    // PRIVATE
    // =========================================================
    private function formatResult(object $part, string $source, ?object $group, $vehicles, ?array $stockBreakdown): array
    {
        $sym    = ['NGN'=>'₦','GHS'=>'GH₵','USD'=>'$'][$part->currency_code ?? 'NGN'] ?? '₦';
        $photos = json_decode($part->photos ?? '[]', true);

        $fitsVehicles = $vehicles->map(
            fn($v) => "{$v->make} {$v->model} ({$v->year_from}-{$v->year_to})"
        )->implode(', ');

        return [
            'id'              => $part->id,
            'part_code'       => $part->part_code,
            'part_name'       => $part->part_name,
            'part_category'   => $part->part_category,
            'grade'           => $part->condition_grade,
            'price'           => $sym . number_format($part->price_local),
            'price_wholesale' => $part->price_wholesale ? $sym . number_format($part->price_wholesale) : null,
            'location'        => $part->location,
            'bin'             => $part->bin_location,
            'stock_qty'       => $part->stock_qty,
            'photo'           => !empty($photos) ? asset('storage/' . $photos[0]) : null,
            'source'          => $source,
            'group_code'      => $group?->group_code,
            'group_id'        => $group?->id,
            'fits_vehicles'   => $fitsVehicles,
            'combined_stock'  => $stockBreakdown ? $stockBreakdown['total'] : $part->stock_qty,
            'major_component' => (bool) ($part->is_major_component ?? false),
            'legal_trace'     => (bool) ($part->legal_trace_required ?? false),
        ];
    }
}
