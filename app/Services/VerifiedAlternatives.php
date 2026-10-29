<?php
// FILE: app/Services/VerifiedAlternatives.php
//
// "Also fits your vehicle": parts in stock that are not the exact model searched but are in an interchange
// group recorded for it. Customers only ever get VERIFIED fits. Staff can ask for the broader list
// (everything in a group for that vehicle, each with its status) to find a quick substitute.
//
// What counts as verified, in one place:
//   - the group's row for that vehicle has a verified fitment_status (App\Data\FitmentStatus::isVerified), or
//   - the row has no status yet and the group was confirmed by staff by hand (source = 'manual').
//     Flip TRUST_MANUAL_GROUPS to false to require an explicit verified status only.
// A row marked "functional substitute" or "requires modification" is never shown to customers as a fit.

namespace App\Services;

use App\Data\FitmentStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerifiedAlternatives
{
    public const TRUST_MANUAL_GROUPS = true;

    /**
     * @param array $v    ['year' => '2010' or '2010-2012', 'make' => 'TOYOTA', 'model' => 'CAMRY']
     * @param array $opt  part_names[] (like match), categories[], exclude_ids[], verified_only (default true), limit (default 12)
     * @return Collection of parts_inventory rows, each with: fit_status, fit_label, is_verified, group_code
     */
    public static function forVehicle(array $v, array $opt = []): Collection
    {
        $make  = trim((string) ($v['make'] ?? ''));
        $model = trim((string) ($v['model'] ?? ''));
        if ($make === '' || $model === '') return collect();

        $verifiedOnly = $opt['verified_only'] ?? true;
        $limit        = (int) ($opt['limit'] ?? 12);

        $groups = self::groupStatuses($make, $model, $v['year'] ?? null);
        if ($verifiedOnly) $groups = array_filter($groups, fn ($g) => $g['verified']);
        if (!$groups) return collect();

        $p = DB::table('parts_inventory')
            ->whereIn('interchange_group_id', array_keys($groups))
            ->where('status', 'Available');
        if (Schema::hasColumn('parts_inventory', 'deleted_at')) $p->whereNull('deleted_at');
        if (!empty($opt['exclude_ids'])) $p->whereNotIn('id', $opt['exclude_ids']);
        if (!empty($opt['categories']))  $p->whereIn('part_category', $opt['categories']);
        if (!empty($opt['part_names'])) {
            $names = $opt['part_names'];
            $p->where(function ($w) use ($names) { foreach ($names as $n) $w->orWhere('part_name', 'like', '%' . $n . '%'); });
        }

        return $p->orderBy('price_local')->limit($limit)->get()->map(function ($row) use ($groups) {
            $g = $groups[$row->interchange_group_id];
            $row->is_verified = $g['verified'];
            $row->fit_status  = $g['status'];
            $row->group_code  = $g['code'];
            $row->fit_label   = $g['verified']
                ? 'Verified fit'
                : ($g['status'] ? FitmentStatus::label($g['status']) : 'Group not yet reviewed');
            return $row;
        });
    }

    /**
     * For one vehicle: every interchange group that lists it, with the best fitment status on file.
     * @return array<int,array{status:?string,verified:bool,code:?string,direct:bool}> keyed by group id
     */
    public static function groupStatuses(string $make, string $model, $year = null): array
    {
        $hasStatus = Schema::hasColumn('part_interchange_vehicles', 'fitment_status');
        $hasMatch  = Schema::hasColumn('part_interchange_vehicles', 'match_type');

        $q = DB::table('part_interchange_vehicles as v')
            ->join('part_interchange_groups as g', 'g.id', '=', 'v.group_id')
            ->where('v.make', $make)->where('v.model', $model)
            ->select('v.group_id', 'g.group_code', 'g.source',
                $hasStatus ? 'v.fitment_status' : DB::raw('NULL as fitment_status'),
                $hasMatch  ? 'v.match_type'     : DB::raw("'direct_oem' as match_type"));

        if (!empty($year)) {
            [$yf, $yt] = array_pad(explode('-', (string) $year, 2), 2, null);
            $yt = $yt ?: $yf;
            $q->where('v.year_from', '<=', (int) $yt)->where('v.year_to', '>=', (int) $yf);
        }

        // Best row per group wins (a verified row beats an unreviewed one).
        $groups = [];
        foreach ($q->get() as $r) {
            $direct   = in_array($r->match_type ?? 'direct_oem', [null, '', 'direct_oem'], true);
            $verified = $direct && (
                FitmentStatus::isVerified($r->fitment_status)
                || ($r->fitment_status === null && self::TRUST_MANUAL_GROUPS && $r->source === 'manual')
            );
            $cur = $groups[$r->group_id] ?? null;
            if (!$cur || ($verified && !$cur['verified'])) {
                $groups[$r->group_id] = ['status' => $r->fitment_status, 'verified' => $verified, 'code' => $r->group_code, 'direct' => $direct];
            }
        }
        return $groups;
    }

    /** Several vehicles at once (the search chips); each part appears once. */
    public static function forVehicles(array $vehicles, array $opt = []): Collection
    {
        $out = collect();
        foreach ($vehicles as $v) {
            $found = self::forVehicle($v, $opt + ['exclude_ids' => []]);
            foreach ($found as $row) if (!$out->has($row->id)) $out->put($row->id, $row);
        }
        return $out->values()->take((int) ($opt['limit'] ?? 12));
    }
}
