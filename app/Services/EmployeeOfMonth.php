<?php
// FILE: app/Services/EmployeeOfMonth.php
//
// Monthly performance score for every staff member (0-100) from the ten Gasbok Engineering criteria.
// Seven are measured from the system; four come from supervisor ratings (1-5).
// A criterion with no data for a person is left out and the remaining weights are scaled up,
// but a person needs at least 40% of the weight measured before they can be shortlisted.

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmployeeOfMonth
{
    /** key => [label, weight, source] - weights add up to 100. */
    public const CRITERIA = [
        'customer_satisfaction' => ['Customer satisfaction',       15, 'Supervisor rating'],
        'sales'                 => ['Successful sales',            15, 'System: sales made this month vs the best at the branch'],
        'accuracy'              => ['Accuracy in parts identification', 12, 'System: few returns on own sales + verified pin counts'],
        'conversion'            => ['Conversion rate',             10, 'System: customer part requests sourced'],
        'response'              => ['Fast response to customers',  10, 'System: hours from request to first reply'],
        'attendance'            => ['Attendance & punctuality',    10, 'System: clock-in records'],
        'followup'              => ['Customer follow-up',           8, 'System: requests handled with notes recorded'],
        'problem_solving'       => ['Problem-solving',              8, 'Supervisor rating'],
        'teamwork'              => ['Teamwork',                     6, 'Supervisor rating'],
        'communication'         => ['Professional communication',   6, 'Supervisor rating'],
    ];

    public const PERFORMANCE_SHARE = 0.70;   // final = 70% performance + 30% staff vote
    public const VOTE_SHARE        = 0.30;
    public const MIN_COVERAGE      = 40;     // % of the weight that must be measurable
    public const RATING_KEYS       = ['customer_satisfaction', 'teamwork', 'communication', 'problem_solving'];

    public static function ready(): bool
    {
        static $ok = null;
        if ($ok === null) {
            try { $ok = Schema::hasTable('eotm_cycles') && Schema::hasTable('eotm_votes'); } catch (\Throwable $e) { $ok = false; }
        }
        return $ok;
    }

    private static function clamp(float $v): float { return max(0, min(100, $v)); }

    /** @return \Illuminate\Support\Collection of objects: staff, scores[key]=>?float, perf, coverage, eligible, reasons[] */
    public static function compute(string $ym)
    {
        $start = Carbon::parse($ym . '-01')->startOfMonth();
        $end   = $start->copy()->endOfMonth();
        $staffAll = DB::table('staff')->where('is_active', true)->where('role', '!=', 'admin')->orderBy('location')->orderBy('name')->get();

        // sales per person, then the best per location for a fair comparison between branches
        $salesCount = [];
        foreach ($staffAll as $s) $salesCount[$s->id] = self::salesCount($s->name, $start, $end);
        $bestAt = [];
        foreach ($staffAll as $s) $bestAt[$s->location] = max($bestAt[$s->location] ?? 0, $salesCount[$s->id]);

        $ratings = Schema::hasTable('eotm_ratings')
            ? DB::table('eotm_ratings')->where('ym', $ym)->get()->groupBy('staff_id') : collect();

        return $staffAll->map(function ($s) use ($ym, $start, $end, $salesCount, $bestAt, $ratings) {
            $sc = array_fill_keys(array_keys(self::CRITERIA), null);

            // supervisor ratings -> 0-100
            foreach (self::RATING_KEYS as $k) {
                $vals = ($ratings->get($s->id) ?? collect())->pluck($k)->filter()->values();
                if ($vals->isNotEmpty()) $sc[$k] = round(($vals->avg() - 1) / 4 * 100, 1);   // 1 -> 0, 5 -> 100
            }

            // attendance & punctuality
            if (AttendanceStats::ready()) {
                $m = AttendanceStats::forMonth($s->id, $s->location, $ym);
                if ($m['working_days'] > 0) $sc['attendance'] = round(0.5 * $m['attendance'] + 0.5 * $m['punctuality'], 1);
            }

            // sales
            $n = $salesCount[$s->id];
            if ($n > 0 && ($bestAt[$s->location] ?? 0) > 0) $sc['sales'] = round(100 * $n / $bestAt[$s->location], 1);
            elseif ($s->role === 'sales_rep') $sc['sales'] = 0.0;

            // accuracy: returns on own sales and verified pin counts
            $parts = [];
            if ($n > 0) {
                $ret = self::returnsOnSales($s->name, $start, $end);
                if ($ret !== null) $parts[] = self::clamp(100 - 500 * ($ret / $n));
            }
            $pin = self::pinAccuracy($s->id, $start, $end);
            if ($pin !== null) $parts[] = $pin;
            if ($parts) $sc['accuracy'] = round(array_sum($parts) / count($parts), 1);

            // customer part requests this person handled
            $req = self::requestStats($s->id, $start, $end);
            if ($req) { $sc['response'] = $req['response']; $sc['followup'] = $req['followup']; $sc['conversion'] = $req['conversion']; }

            // weighted score on what could be measured
            $wAll = array_sum(array_column(self::CRITERIA, 1));
            $wHave = 0; $sum = 0;
            foreach (self::CRITERIA as $k => [$label, $w]) {
                if ($sc[$k] !== null) { $wHave += $w; $sum += $w * $sc[$k]; }
            }
            $perf = $wHave > 0 ? round($sum / $wHave, 1) : 0.0;
            $cov  = round(100 * $wHave / $wAll);

            $reasons = [];
            if ($cov < self::MIN_COVERAGE) $reasons[] = "Not enough data this month ({$cov}% measured, needs " . self::MIN_COVERAGE . '%)';
            if (TrainingCompliance::stateFor($s->id) === 'red') $reasons[] = 'Training overdue';
            if (AttendanceStats::ready()) {
                $m = $m ?? AttendanceStats::forMonth($s->id, $s->location, $ym);
                if ($m['working_days'] > 0 && $m['present'] < 1) $reasons[] = 'No days worked this month';
            }
            return (object) ['staff' => $s, 'scores' => $sc, 'perf' => $perf, 'coverage' => $cov, 'eligible' => !$reasons, 'reasons' => $reasons, 'sales_n' => $n];
        })->values();
    }

    private static function salesCount(string $name, Carbon $a, Carbon $b): int
    {
        $n = 0;
        try {
            $n += DB::table('invoices')->whereNull('deleted_at')->where('created_by', $name)->whereBetween('created_at', [$a, $b])->count();
            $n += DB::table('orders')->whereNull('deleted_at')->where('created_by', $name)
                ->whereIn(DB::raw('LOWER(payment_status)'), ['confirmed', 'paid', 'completed'])->whereBetween('created_at', [$a, $b])->count();
        } catch (\Throwable $e) {}
        return $n;
    }

    private static function returnsOnSales(string $name, Carbon $a, Carbon $b): ?int
    {
        try {
            return (int) DB::table('returns')->whereBetween('created_at', [$a, $b])->where(function ($q) use ($name) {
                $q->whereIn('order_id', DB::table('orders')->where('created_by', $name)->select('id'))
                  ->orWhereIn('invoice_id', DB::table('invoices')->where('created_by', $name)->select('id'));
            })->count();
        } catch (\Throwable $e) { return null; }
    }

    private static function pinAccuracy(int $staffId, Carbon $a, Carbon $b): ?float
    {
        try {
            if (!Schema::hasTable('pin_records')) return null;
            $r = DB::table('pin_records')->where('submitted_by_staff_id', $staffId)->whereIn('status', ['verified', 'rejected'])
                ->whereBetween('created_at', [$a, $b])->selectRaw("SUM(status='verified') v, COUNT(*) n")->first();
            return ($r && $r->n > 0) ? round(100 * $r->v / $r->n, 1) : null;
        } catch (\Throwable $e) { return null; }
    }

    private static function requestStats(int $staffId, Carbon $a, Carbon $b): ?array
    {
        try {
            if (!Schema::hasTable('part_requests')) return null;
            $rows = DB::table('part_requests')->where('handled_by_staff_id', $staffId)->whereBetween('handled_at', [$a, $b])->get();
            if ($rows->isEmpty()) return null;
            $hours = $rows->map(fn($r) => max(0, Carbon::parse($r->created_at)->diffInMinutes(Carbon::parse($r->handled_at)) / 60))->sort()->values();
            $med = $hours->count() % 2 ? $hours[intdiv($hours->count(), 2)] : ($hours[$hours->count() / 2 - 1] + $hours[$hours->count() / 2]) / 2;
            return [
                'response'   => round($med <= 2 ? 100 : ($med >= 48 ? 0 : 100 * (48 - $med) / 46), 1),
                'followup'   => round(100 * $rows->filter(fn($r) => trim((string) $r->staff_notes) !== '')->count() / $rows->count(), 1),
                'conversion' => round(100 * $rows->where('status', 'sourced')->count() / $rows->count(), 1),
            ];
        } catch (\Throwable $e) { return null; }
    }
}
