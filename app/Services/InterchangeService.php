<?php
// FILE: app/Services/InterchangeService.php
//
// Handles "this part also fits..." logic across ALL part categories.
//
// Two sources of truth, in priority order:
//   1. MANUAL data — admin-entered/refined groups (most trustworthy)
//   2. AUTO HEURISTIC — built from data we already have on file from
//      NHTSA vPIC decodes (engine code, body style, adjacent years) and
//      from OEM codes already stored on parts_inventory. NHTSA has no
//      "interchange" concept itself — this heuristic just clusters
//      vehicles that share enough vPIC-derived traits to *probably*
//      interchange, then surfaces them as a SUGGESTION for admin to
//      confirm/edit, never as a silent fact.

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InterchangeService
{
    // =========================================================
    // NEW: merges contiguous/overlapping single-year vehicle entries
    // into clean ranges for display — e.g. three separate rows for
    // 2014, 2015, 2016 become one "2014-2016" range. Deliberately
    // does NOT bridge real gaps: 2014 + 2016 with no 2015 entry stay
    // as two separate ranges, since collapsing them would silently
    // claim a year nobody ever actually confirmed — that's a real
    // accuracy regression, not a display nicety, given this whole
    // system is built on evidence-weighted confidence.
    //
    // Grouped by make+model first — a 2014 Corolla row and a 2014
    // Matrix row are never merged with each other regardless of year.
    // =========================================================
    public function mergeContiguousYearRanges($vehicles)
    {
        return collect($vehicles)
            ->groupBy(fn($v) => strtoupper($v->make ?? '') . '|' . strtoupper($v->model ?? ''))
            ->flatMap(function ($group) {
                $sorted = $group->sortBy('year_from')->values();
                $merged = collect();

                foreach ($sorted as $v) {
                    $last = $merged->last();
                    // Contiguous or overlapping with the previous range
                    // (gap of 0 or 1 year, e.g. year_to=2014 meeting
                    // year_from=2015) — extend it. Otherwise start a
                    // new, separate range.
                    if ($last && $v->year_from <= $last->year_to + 1) {
                        $last->year_to = max($last->year_to, $v->year_to);
                    } else {
                        $merged->push((object) [
                            'make'      => $v->make,
                            'model'     => $v->model,
                            'year_from' => $v->year_from,
                            'year_to'   => $v->year_to,
                        ]);
                    }
                }

                return $merged;
            })
            ->values();
    }

    // =========================================================
    // Find (or null) the interchange group a part belongs to.
    // =========================================================
    public function groupForPart(int $partId): ?object
    {
        $part = DB::table('parts_inventory')->where('id', $partId)->first();
        if (!$part || !$part->interchange_group_id) return null;

        return DB::table('part_interchange_groups')
            ->where('id', $part->interchange_group_id)
            ->first();
    }

    // =========================================================
    // Full vehicle list for a group — used by the "also fits" dialog.
    // =========================================================
    public function vehiclesForGroup(int $groupId)
    {
        return DB::table('part_interchange_vehicles')
            ->where('group_id', $groupId)
            ->orderBy('make')->orderBy('model')->orderBy('year_from')
            ->get();
    }

    // =========================================================
    // The main lookup used by search / "not in stock" dialog.
    // Given a part_name + the vehicle being searched, returns the
    // full interchange vehicle list (manual data) — falls back to
    // an auto heuristic suggestion if no manual group exists yet.
    // =========================================================
    public function interchangeFor(string $partName, ?string $engineCodeOem, ?string $transCodeOem): array
    {
        // 1. Try matching an existing manual/auto group by OEM code first
        //    (works well for Engine/Transmission, which already key off
        //    these codes naturally).
        $group = null;
        if ($engineCodeOem) {
            $group = DB::table('part_interchange_groups')
                ->where('part_name', $partName)
                ->where('group_code', $engineCodeOem)
                ->first();
        }
        if (!$group && $transCodeOem) {
            $group = DB::table('part_interchange_groups')
                ->where('part_name', $partName)
                ->where('group_code', $transCodeOem)
                ->first();
        }

        if ($group) {
            return [
                'found'    => true,
                'source'   => $group->source,
                'group_id' => $group->id,
                'vehicles' => $this->vehiclesForGroup($group->id),
            ];
        }

        // 2. No manual group — build a live heuristic suggestion from
        //    parts_inventory + whatever vPIC-derived fields we have on
        //    file (engine_code_oem / transmission_code_oem / body_style).
        //    This is NOT saved automatically — admin must confirm it.
        $matchCode = $engineCodeOem ?: $transCodeOem;
        if (!$matchCode) {
            return ['found' => false, 'source' => null, 'group_id' => null, 'vehicles' => collect()];
        }

        $heuristic = DB::table('parts_inventory')
            ->where('part_name', $partName)
            ->where(function ($q) use ($matchCode) {
                $q->where('engine_code_oem', $matchCode)
                  ->orWhere('transmission_code_oem', $matchCode);
            })
            ->select('brand as make', 'model', 'year_from', 'year_to', 'body_style', 'drive_type')
            ->distinct()
            ->get();

        return [
            'found'    => $heuristic->isNotEmpty(),
            'source'   => 'auto_heuristic',
            'group_id' => null, // not yet a saved group — admin can promote it to one
            'vehicles' => $heuristic,
        ];
    }

    // =========================================================
    // Total stock across ALL interchangeable parts in a group —
    // e.g. 2009 Corolla headlight-R (qty 2) + 2010 Corolla
    // headlight-R (qty 4) in the same group = 6 combined available.
    // =========================================================
    public function aggregatedStock(int $groupId): int
    {
        return (int) DB::table('parts_inventory')
            ->where('interchange_group_id', $groupId)
            ->where('status', 'Available')
            ->sum('stock_qty');
    }

    // Breakdown version — same total, plus the line-by-line source
    // so the UI can show "2009 Corolla (2) + 2010 Corolla (4) = 6".
    public function aggregatedStockBreakdown(int $groupId): array
    {
        $rows = DB::table('parts_inventory')
            ->where('interchange_group_id', $groupId)
            ->where('status', 'Available')
            ->select('id', 'part_code', 'brand', 'model', 'year_from', 'year_to', 'side', 'stock_qty')
            ->get();

        return [
            'total' => (int) $rows->sum('stock_qty'),
            'lines' => $rows,
        ];
    }

    // =========================================================
    // Used by harvest: does this part's vehicle already fall within
    // an existing group's year range for this part_name? If so,
    // the newly harvested part should join that group automatically
    // so stock aggregates correctly without admin having to set it
    // up manually every time.
    // =========================================================
    public function findGroupByVehicle(string $partName, string $make, string $model, int $year): ?object
    {
        return DB::table('part_interchange_groups as g')
            ->join('part_interchange_vehicles as v', 'v.group_id', '=', 'g.id')
            ->where('g.part_name', $partName)
            ->where('v.make', $make)
            ->where('v.model', $model)
            ->where('v.year_from', '<=', $year)
            ->where('v.year_to', '>=', $year)
            ->select('g.*')
            ->first();
    }

    // ADMIN ACTIONS — create/edit groups based on research
    // =========================================================
    public function createGroup(string $category, string $partName, string $groupCode, ?string $notes, ?int $staffId): int
    {
        return DB::table('part_interchange_groups')->insertGetId([
            'part_category'       => $category,
            'part_name'           => $partName,
            'group_code'          => $groupCode,
            'source'              => 'manual',
            'notes'               => $notes,
            'created_by_staff_id' => $staffId,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    public function addVehicleToGroup(int $groupId, string $make, string $model, int $yearFrom, int $yearTo, ?string $trim = null, ?string $bodyStyle = null): int
    {
        return DB::table('part_interchange_vehicles')->insertGetId([
            'group_id'   => $groupId,
            'make'       => $make,
            'model'      => $model,
            'year_from'  => $yearFrom,
            'year_to'    => $yearTo,
            'trim'       => $trim,
            'body_style' => $bodyStyle,
            'added_via'  => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function assignPartToGroup(int $partId, int $groupId): void
    {
        DB::table('parts_inventory')->where('id', $partId)->update([
            'interchange_group_id' => $groupId,
            'updated_at'           => now(),
        ]);
    }

    // Promote a heuristic suggestion (from interchangeFor()) into a real,
    // admin-confirmed group — call this from the admin UI's "Confirm &
    // Save This Suggestion" button.
    public function promoteHeuristicToGroup(string $category, string $partName, string $groupCode, $vehicles, ?int $staffId): int
    {
        $groupId = $this->createGroup($category, $partName, $groupCode, 'Promoted from auto-heuristic suggestion.', $staffId);

        foreach ($vehicles as $v) {
            $this->addVehicleToGroup(
                $groupId,
                $v->make,
                $v->model,
                (int) $v->year_from,
                (int) $v->year_to,
                null,
                $v->body_style ?? null
            );
        }

        return $groupId;
    }
}
