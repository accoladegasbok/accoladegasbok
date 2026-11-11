<?php
// FILE: app/Services/AttendanceStats.php
//
// Month summary per staff member. An "absent" day is a working day with no clock-in
// (including days the person was blocked for overdue training). Used by the attendance
// report and, later, by Employee of the Month scoring.

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendanceStats
{
    public static function ready(): bool
    {
        static $ok = null;
        if ($ok === null) {
            try { $ok = Schema::hasTable('attendance_records') && Schema::hasTable('attendance_sites'); } catch (\Throwable $e) { $ok = false; }
        }
        return $ok;
    }

    public static function site(?string $location): object
    {
        $row = ($location && self::ready()) ? DB::table('attendance_sites')->where('location', $location)->first() : null;
        return $row ?: (object) [
            'location' => $location, 'lat' => null, 'lng' => null, 'radius_m' => 150, 'enforce_geofence' => 0,
            'require_biometric' => 0, 'timezone' => 'Africa/Lagos', 'shift_start' => '08:30',
            'grace_minutes' => 15, 'working_days' => '1,2,3,4,5,6',
        ];
    }

    /** Is this calendar date a working day for the site? */
    public static function isWorkingDay(object $site, Carbon $date): bool
    {
        return in_array((string) $date->dayOfWeekIso, explode(',', (string) $site->working_days), true);
    }

    /**
     * @return array{working_days:int, present:int, late:int, absent:int, excused:int, punctuality:float, attendance:float}
     */
    public static function forMonth(int $staffId, ?string $location = null, ?string $ym = null): array
    {
        $start = Carbon::parse(($ym ?: now()->format('Y-m')) . '-01')->startOfMonth();
        $end   = $start->copy()->endOfMonth();
        if ($end->gt(now())) $end = now()->startOfDay();
        $site  = self::site($location);

        $recs = self::ready()
            ? DB::table('attendance_records')->where('staff_id', $staffId)
                ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])->get()->keyBy('work_date')
            : collect();

        $working = $present = $late = $excused = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $rec = $recs->get($d->toDateString());
            if (!self::isWorkingDay($site, $d)) {
                if ($rec && $rec->clock_in_at) { $present++; }     // worked on a rest day: counts as a bonus present day only
                continue;
            }
            $working++;
            if (!$rec) continue;
            if ($rec->status === 'excused') { $excused++; continue; }
            if ($rec->clock_in_at) { $present++; if ($rec->status === 'late') $late++; }
        }
        $due    = max(0, $working - $excused);
        $absent = max(0, $due - min($present, $due));
        return [
            'working_days' => $working, 'present' => $present, 'late' => $late, 'absent' => $absent, 'excused' => $excused,
            'attendance'  => $due ? round(100 * min($present, $due) / $due, 1) : 100.0,
            'punctuality' => $present ? round(100 * ($present - $late) / $present, 1) : ($due ? 0.0 : 100.0),
        ];
    }
}
