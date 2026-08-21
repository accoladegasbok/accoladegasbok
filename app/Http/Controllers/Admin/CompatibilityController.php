<?php
// FILE: app/Http/Controllers/Admin/CompatibilityController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InterchangeService;
use App\Data\PlatformDatabase;
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
            'make'       => 'required|string|max:60',
            'model'      => 'required|string|max:80',
            'year'       => 'required|integer|min:1980|max:2030',
            'cylinders'  => 'nullable|integer|min:0|max:16',
            'engine_l'   => 'nullable|numeric|min:0|max:10',
            // NEW: optional trim/sub-model narrowing — matches RAPID's
            // own pattern (Sub Model selector, "Don't Know" fallback).
            // Never required — most searches won't have it, and
            // omitting it just means "any trim."
            'trim'       => 'nullable|string|max:60',
        ]);

        $make      = strtoupper(trim($request->make));
        $model     = strtoupper(trim($request->model));
        $year      = (int) $request->year;
        $cylinders = (int) $request->get('cylinders', 0);
        $engineL   = (float) $request->get('engine_l', 0.0);
        $partName  = trim($request->get('part_name', ''));
        $category  = trim($request->get('category', ''));
        // NEW: optional donor trim narrowing.
        $trim      = trim($request->get('trim', ''));

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
        // NEW: trim narrowing — SOFT, not exclusionary. Since almost no
        // existing inventory has donor_trim populated yet, a strict
        // filter would hide nearly everything. Shows exact-trim matches
        // PLUS anything with no trim recorded (unknown, not excluded) —
        // matches RAPID's own "Don't Know" fallback philosophy.
        if ($trim) {
            $directQuery->where(function ($q) use ($trim) {
                $q->where('donor_trim', $trim)->orWhereNull('donor_trim');
            });
        }
        foreach ($directQuery->get() as $part) {
            $results->push($this->formatResult($part, 'direct', null, collect(), null));
        }

        // ── Tier 2b: Platform-based match (chassis-shared parts) ──
        // Some parts don't care what engine is under the hood — but
        // "shares a platform" does NOT mean "shares body panels."
        // PlatformDatabase deliberately scopes each cross-model entry
        // to only the categories genuinely safe to claim (typically
        // Suspension/Brakes) — see PlatformDatabase.php header comment.
        $platform = \App\Data\PlatformDatabase::lookup($make, $model, $year);

        if (!empty($platform['shared_vehicles'])) {
            $existingIds = $results->pluck('id')->toArray();
            foreach ($platform['shared_vehicles'] as $sv) {
                $entryCategories = $sv['categories'] ?? \App\Data\PlatformDatabase::CROSS_MODEL_SAFE_CATEGORIES;
                if ($category && !in_array($category, $entryCategories)) continue;

                $pQuery = DB::table('parts_inventory')
                    ->where('brand', strtoupper($sv['make']))
                    ->where('model', strtoupper($sv['model']))
                    ->where('year_from', '<=', $sv['year_to'])
                    ->where('year_to',   '>=', $sv['year_from'])
                    ->where('status', 'Available')
                    ->whereIn('part_category', $entryCategories)
                    ->whereNull('interchange_group_id')
                    ->whereNotIn('id', $existingIds);
                if ($partName) $pQuery->where('part_name', 'like', "%{$partName}%");
                if ($category) $pQuery->where('part_category', $category);
                foreach ($pQuery->get() as $part) {
                    $results->push($this->formatResult($part, 'platform', null, collect(), null));
                    $existingIds[] = $part->id;
                }
            }
        }

        // Moved earlier (was after Tier 3) — Tier 3's drive-type
        // narrowing below needs the SEARCHED vehicle's own drive_type,
        // which this lookup provides.
        $oem = \App\Data\OemDatabase::lookup($make, $model, $year, $cylinders, $engineL);

        // ── Tier 3: OEM-code heuristic (Ladipo algorithm) ──────────
        $oemQuery = DB::table('parts_inventory')
            ->where('brand', $make)->where('model', $model)
            ->where('year_from', '<=', $year)->where('year_to', '>=', $year)
            ->whereNotNull('engine_code_oem')
            ->whereNull('interchange_group_id')
            ->where('status', 'Available');
        if ($partName) $oemQuery->where('part_name', 'like', "%{$partName}%");
        $oemParts = $oemQuery->select('part_name', 'engine_code_oem', 'transmission_code_oem', 'part_category')
            ->distinct()->limit(5)->get();

        $heuristicSuggestions = collect();
        foreach ($oemParts as $oemPart) {
            $heuristic = $this->interchange->interchangeFor(
                $oemPart->part_name,
                $oemPart->engine_code_oem,
                $oemPart->transmission_code_oem
            );
            if ($heuristic['found'] && $heuristic['vehicles']->isNotEmpty()) {
                // NEW: drive-type awareness. Transmission (which, per
                // your own terminology taxonomy, also covers transfer
                // case / CV axles / driveshafts) genuinely does NOT
                // interchange across drive types — an AWD transmission
                // and FWD transmission sharing the same engine code are
                // physically different units. Engine itself is treated
                // as informational only, since engines more often DO
                // cross drive-types with just mount/accessory
                // differences — no hard evidence either way to justify
                // excluding, so it's shown, not hidden.
                $isDrivetrainSensitive = $oemPart->part_category === 'Transmission';
                $searchedDriveType = $oem['drive_type'] ?? null;

                $vehiclesWithDriveFlag = $heuristic['vehicles']->take(8)->map(function ($v) use ($searchedDriveType, $isDrivetrainSensitive) {
                    $driveType = $v->drive_type ?? null;
                    $mismatch  = $isDrivetrainSensitive && $searchedDriveType && $driveType && $driveType !== $searchedDriveType;
                    return [
                        'label'          => "{$v->make} {$v->model} ({$v->year_from}-{$v->year_to})",
                        'drive_type'     => $driveType,
                        'drive_mismatch' => $mismatch,
                    ];
                });

                // For Transmission specifically, drop confirmed drive-type
                // mismatches from the suggestion entirely rather than
                // just flagging them — showing "here's a match" for a
                // physically incompatible transmission is worse than not
                // suggesting it at all. Unknown drive_type (null) stays
                // shown, same soft-narrowing philosophy as trim above.
                if ($isDrivetrainSensitive) {
                    $vehiclesWithDriveFlag = $vehiclesWithDriveFlag->reject(fn($v) => $v['drive_mismatch'])->values();
                }

                if ($vehiclesWithDriveFlag->isEmpty()) continue;

                $heuristicSuggestions->push([
                    'part_name'    => $oemPart->part_name,
                    'part_category'=> $oemPart->part_category,
                    'engine_code'  => $oemPart->engine_code_oem,
                    'drive_type'   => $searchedDriveType,
                    'vehicles'     => $vehiclesWithDriveFlag->take(5),
                    'source' => 'auto_heuristic',
                ]);
            }
        }

        // ── OemDatabase interchange reference — check both trans + engine ──
        // Pass through cylinders/engine_l if the staff already picked a
        // specific engine (via the picker below); otherwise this falls
        // back to whichever branch OemDatabase treats as the default —
        // which is why engineOptions() below matters: without it, an
        // ambiguous vehicle (e.g. Camry with both 4-cyl and V6 versions)
        // would silently guess one and never show the other.
        // (NOTE: $oem itself is computed earlier now, before Tier 3,
        // since that block needs the searched vehicle's drive_type.)
        $allInterchange = \App\Data\OemDatabase::interchange();
        $interchangeReference = collect();

        // Check transmission code first, then engine code — merge both
        if ($oem['transmission_code'] && isset($allInterchange[$oem['transmission_code']])) {
            $interchangeReference = $interchangeReference->merge($allInterchange[$oem['transmission_code']]);
        }
        if ($oem['engine_code'] && isset($allInterchange[$oem['engine_code']])) {
            $interchangeReference = $interchangeReference->merge($allInterchange[$oem['engine_code']]);
        }

        // Deduplicate — strip OEM codes in brackets for comparison, keep first occurrence
        $seen = [];
        $interchangeReference = $interchangeReference->filter(function($item) use (&$seen) {
            // Extract core vehicle string: "2002-2011 Toyota Camry 2.4L" from "2002-2011 Toyota Camry 2.4L (2AZ-FE)"
            $core = trim(preg_replace('/\s*\([^)]+\)/', '', $item));
            if (isset($seen[$core])) return false;
            $seen[$core] = true;
            return true;
        })->values()->toArray();

        // ── Engine options — surfaced whenever this vehicle has more than
        // one known engine and staff hasn't specified which one yet
        // (via cylinders/engine_l). The front-end shows a picker; once
        // the staff selects one, the search re-runs with that value so
        // $oem above resolves to the CORRECT branch instead of a guess.
        $engineOptions = \App\Data\OemDatabase::engineOptions($make, $model, $year);
        $engineDisambiguated = ($cylinders > 0 || $engineL > 0);

        // FIXED: previously exposed the raw, unfiltered shared_models
        // list regardless of what category was searched — meaning a
        // Body search would show "Suspension/Brakes only" chassis-mates
        // as if relevant, AND (in an earlier attempted fix) risked the
        // opposite mistake of hiding legitimate "own generation" entries
        // (which DO cover Body) just because the category wasn't
        // Suspension/Brakes. Now filters shared_vehicles by whether the
        // searched category is actually in THAT entry's own categories
        // list, and tags each surviving entry as own_generation vs
        // cross_model so the frontend can phrase them correctly instead
        // of generic "Chassis Platform" language.
        $platformEntries = collect($platform['shared_vehicles'] ?? [])
            ->filter(function ($sv) use ($category) {
                $entryCategories = $sv['categories'] ?? \App\Data\PlatformDatabase::CROSS_MODEL_SAFE_CATEGORIES;
                return !$category || in_array($category, $entryCategories, true);
            })
            ->map(function ($sv) use ($make, $model) {
                $isOwnGeneration = strtoupper($sv['make']) === $make && strtoupper($sv['model']) === $model;
                return [
                    'make'      => $sv['make'],
                    'model'     => $sv['model'],
                    'year_from' => $sv['year_from'],
                    'year_to'   => $sv['year_to'],
                    'type'      => $isOwnGeneration ? 'own_generation' : 'cross_model',
                    'label'     => "{$sv['make']} {$sv['model']} ({$sv['year_from']}-{$sv['year_to']})",
                ];
            })
            ->values();

        return response()->json([
            'count'                 => $results->count(),
            'results'               => $results->values(),
            'suggestions'           => $heuristicSuggestions->values(),
            'interchange_reference' => $interchangeReference,
            'oem'                   => $oem,
            'engine_options'        => $engineOptions,
            'engine_disambiguated'  => $engineDisambiguated,
            'platform'              => [
                'generation'    => $platform['generation'] ?? null,
                'body_style'    => $platform['body_style'] ?? null,
                'shared_models' => $platform['shared_models'] ?? [],
                // NEW: category-filtered, type-tagged entries — this is
                // what the frontend should actually render now instead
                // of the raw shared_models strings above (kept for
                // backward compat / debugging only).
                'entries'       => $platformEntries,
            ],
            'search'                => "{$year} {$make} {$model}" . ($trim ? " {$trim}" : '') . ($partName ? " · {$partName}" : ''),
            // NEW: explicit echo of what was actually searched — lets
            // the frontend label results/panels by the real part
            // searched ("Door Shell") instead of generic platform talk,
            // and decide whether a cross-model platform panel is even
            // relevant to what was asked for.
            'searched_part_name' => $partName,
            'searched_category'  => $category,
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

        // FIXED: same fragmentation fix as the barcode tag — merge
        // genuinely contiguous years into one range, never bridge a
        // real gap.
        $vehicles = $this->interchange->mergeContiguousYearRanges($vehicles);
        $fitsVehicles = $vehicles->map(
            fn($v) => "{$v->make} {$v->model} ({$v->year_from}-{$v->year_to})"
        )->implode(', ');

        // NEW: staff-added free-text "Extra Compatibility Note" —
        // surfaces on the checker results too, not just the barcode
        // tag, so staff searching a vehicle see the same caveats.
        $compatibilityNotes = $this->interchange->notesForPart($part->id);

        // NEW: explicit count — "Fits 3 models" — so staff/customers
        // don't have to manually count the comma-separated list to
        // know the scope. Simple logic, exactly what was asked for:
        // the raw count of vehicle rows this group/part covers.
        $fitsVehicleCount = $vehicles->count();

        // Real evidence-based confidence — only meaningful for Tier 1
        // (confirmed interchange group) results, since that's the only
        // tier with a group_id to attach evidence to. Falls back to
        // null for direct/platform/heuristic tiers, which already show
        // their own source badge instead.
        $confidenceScore  = null;
        $verificationStatus = null;
        $sourceCount = null;
        if ($group) {
            $confidenceScore     = \App\Services\ConfidenceScorer::scoreForGroup($group->id);
            $sourceCount         = \App\Services\ConfidenceScorer::sourceCountForGroup($group->id);
            $verificationStatus  = $sourceCount > 0
                ? \App\Services\ConfidenceScorer::suggestStatus($confidenceScore)
                : null; // zero evidence recorded yet — don't claim a status with nothing behind it
        }

        return [
            'id'              => $part->id,
            'part_code'       => $part->part_code,
            'part_name'       => $part->part_name,
            'part_category'   => $part->part_category,
            'donor_trim'      => $part->donor_trim ?? null,
            // NEW: staff-added free-text compatibility caveats/notes —
            // each entry attributed to who wrote it and when.
            'compatibility_notes' => $compatibilityNotes->map(fn($n) => [
                'note'       => $n->note,
                'added_by'   => $n->added_by_name,
                'added_role' => $n->added_by_role,
                'added_at'   => $n->created_at,
            ]),
            // NEW: drive type shown alongside every result — e.g. "2GR,
            // 3.5L, AWD" — so staff can visually verify before treating
            // a Transmission/axle match as interchangeable, without a
            // hard exclusion at this display layer (the hard exclusion
            // already happened upstream in Tier 3 for Transmission).
            'drive_type'      => $part->drive_type ?? null,
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
            'fits_vehicle_count' => $fitsVehicleCount,
            'combined_stock'  => $stockBreakdown ? $stockBreakdown['total'] : $part->stock_qty,
            'major_component' => (bool) ($part->is_major_component ?? false),
            'legal_trace'     => (bool) ($part->legal_trace_required ?? false),
            'confidence_score'     => $confidenceScore,
            'verification_status'  => $verificationStatus,
            'evidence_count'       => $sourceCount,
        ];
    }
}
