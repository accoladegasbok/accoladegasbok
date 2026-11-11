<?php
// FILE: app/Services/TrainingCompliance.php
//
// One place that answers "is this staff member up to date with training?".
// Overdue training blocks clock-in (see AttendanceController) - no salary rule is attached to it.

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TrainingCompliance
{
    public static function ready(): bool
    {
        static $ok = null;
        if ($ok === null) {
            try { $ok = Schema::hasTable('training_assignments'); } catch (\Throwable $e) { $ok = false; }
        }
        return $ok;
    }

    /** Assignments past their due date and not completed, with the course title. */
    public static function overdueFor(int $staffId)
    {
        if (!self::ready()) return collect();
        return DB::table('training_assignments as a')
            ->join('training_courses as c', 'c.id', '=', 'a.course_id')
            ->where('a.staff_id', $staffId)
            ->where('a.status', '!=', 'completed')
            ->whereDate('a.due_date', '<', now()->toDateString())
            ->where('c.is_active', true)
            ->select('a.id', 'a.due_date', 'c.title')
            ->orderBy('a.due_date')->get();
    }

    /** green = nothing open or all on time, amber = due within 7 days, red = something overdue. */
    public static function stateFor(int $staffId): string
    {
        if (!self::ready()) return 'green';
        if (self::overdueFor($staffId)->isNotEmpty()) return 'red';
        $soon = DB::table('training_assignments')->where('staff_id', $staffId)->where('status', '!=', 'completed')
            ->whereDate('due_date', '<=', now()->addDays(7)->toDateString())->exists();
        return $soon ? 'amber' : 'green';
    }
}
