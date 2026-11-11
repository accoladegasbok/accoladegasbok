<?php
// FILE: app/Http/Controllers/Admin/TrainingController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TrainingCompliance;
use App\Support\StaffRole;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class TrainingController extends Controller
{
    private function me(): int { return (int) session('staff_id'); }
    private function isManager(): bool { return in_array(session('staff_role'), ['admin', 'manager'], true); }

    private function ready(): bool { return TrainingCompliance::ready() && Schema::hasTable('training_courses'); }

    private function notReady()
    {
        return view('admin.training.not-ready');
    }

    // ───────────────────────── Supervisor+: compliance board and course list ─────────────────────────

    public function index(Request $r)
    {
        abort_unless(StaffRole::isSupervisorOrAbove(), 403);
        if (!$this->ready()) return $this->notReady();

        $today = now()->toDateString();
        $staffQ = DB::table('staff')->where('is_active', true)->where('role', '!=', 'admin');
        if (!$this->isManager()) $staffQ->where('location', session('staff_location'));
        $staff = $staffQ->orderBy('location')->orderBy('name')->get();

        $open = DB::table('training_assignments as a')->join('training_courses as c', 'c.id', '=', 'a.course_id')
            ->whereIn('a.staff_id', $staff->pluck('id'))->where('a.status', '!=', 'completed')->where('c.is_active', true)
            ->select('a.*', 'c.title')->orderBy('a.due_date')->get()->groupBy('staff_id');
        $done = DB::table('training_assignments')->whereIn('staff_id', $staff->pluck('id'))->where('status', 'completed')
            ->selectRaw('staff_id, COUNT(*) n, MAX(completed_at) last_at')->groupBy('staff_id')->get()->keyBy('staff_id');

        $board = $staff->map(function ($s) use ($open, $done, $today) {
            $mine = $open->get($s->id, collect());
            $over = $mine->filter(fn($a) => $a->due_date < $today);
            $state = $over->isNotEmpty() ? 'red' : ($mine->contains(fn($a) => $a->due_date <= now()->addDays(7)->toDateString()) ? 'amber' : 'green');
            return (object) ['staff' => $s, 'open' => $mine, 'overdue' => $over, 'state' => $state, 'done' => $done->get($s->id)];
        })->sortBy(fn($x) => ['red' => 0, 'amber' => 1, 'green' => 2][$x->state])->values();

        // Counts as sub-selects (no GROUP BY), so it runs on servers with strict ONLY_FULL_GROUP_BY.
        $courses = DB::table('training_courses as c')
            ->select('c.*')
            ->selectSub(DB::table('training_assignments')->selectRaw('COUNT(*)')->whereColumn('course_id', 'c.id'), 'assigned')
            ->selectSub(DB::table('training_assignments')->selectRaw('COUNT(*)')->whereColumn('course_id', 'c.id')->where('status', 'completed'), 'completed')
            ->orderByDesc('c.is_active')->orderBy('c.title')->get();

        return view('admin.training.index', ['board' => $board, 'courses' => $courses, 'tab' => $r->query('tab', 'board'), 'isMgr' => $this->isManager()]);
    }

    // ───────────────────────── Manager+: courses ─────────────────────────

    public function create()
    {
        abort_unless($this->isManager(), 403);
        if (!$this->ready()) return $this->notReady();
        return view('admin.training.course-form', ['course' => null, 'questions' => collect()]);
    }

    public function edit(int $id)
    {
        abort_unless($this->isManager(), 403);
        $course = DB::table('training_courses')->where('id', $id)->first();
        abort_unless($course, 404);
        $questions = DB::table('training_questions')->where('course_id', $id)->orderBy('sort')->get()
            ->map(function ($q) { $q->options = json_decode($q->options, true) ?: []; return $q; });
        return view('admin.training.course-form', compact('course', 'questions'));
    }

    private static function youtubeId(?string $url): ?string
    {
        if (!$url) return null;
        if (preg_match('~(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{11})~', $url, $m)) return $m[1];
        return preg_match('/^[A-Za-z0-9_-]{11}$/', trim($url)) ? trim($url) : null;
    }

    public function save(Request $r, ?int $id = null)
    {
        abort_unless($this->isManager(), 403);
        $d = $r->validate([
            'title' => 'required|string|max:150', 'area' => 'nullable|string|max:60', 'description' => 'nullable|string|max:3000',
            'provider_type' => 'required|in:internal,external', 'provider_name' => 'nullable|string|max:120',
            'video_source' => 'required|in:upload,youtube,none', 'youtube_url' => 'nullable|string|max:200',
            'video' => 'nullable|file|mimes:mp4,webm,mov,m4v|max:512000',
            'duration_minutes' => 'nullable|integer|min:1|max:1000', 'pass_mark' => 'required|integer|min:0|max:100',
            'recurrence_days' => 'nullable|integer|min:1|max:3650', 'policy_text' => 'nullable|string|max:8000',
        ]);
        if ($d['provider_type'] === 'external' && empty($d['provider_name'])) {
            return back()->withInput()->withErrors(['provider_name' => 'Name the external provider (company or trainer).']);
        }
        $existing = $id ? DB::table('training_courses')->where('id', $id)->first() : null;
        if ($id && !$existing) abort(404);

        $row = [
            'title' => $d['title'], 'area' => $d['area'] ?? null, 'description' => $d['description'] ?? null,
            'provider_type' => $d['provider_type'], 'provider_name' => $d['provider_name'] ?? null,
            'video_source' => $d['video_source'], 'duration_minutes' => $d['duration_minutes'] ?? null,
            'pass_mark' => $d['pass_mark'], 'recurrence_days' => $d['recurrence_days'] ?? null,
            'policy_text' => $d['policy_text'] ?? null, 'updated_at' => now(),
        ];
        if ($d['video_source'] !== 'none' && empty($d['duration_minutes'])) {
            return back()->withInput()->withErrors(['duration_minutes' => 'Enter the video length in minutes (used to stop staff skipping through).']);
        }
        $row['youtube_id'] = $d['video_source'] === 'youtube' ? self::youtubeId($d['youtube_url'] ?? null) : null;
        if ($d['video_source'] === 'youtube' && !$row['youtube_id']) {
            return back()->withInput()->withErrors(['youtube_url' => 'That does not look like a YouTube link.']);
        }
        $row['video_path'] = $existing->video_path ?? null;
        if ($d['video_source'] === 'upload') {
            if ($r->hasFile('video')) $row['video_path'] = Storage::disk('local')->putFile('training', $r->file('video'));
            if (empty($row['video_path'])) return back()->withInput()->withErrors(['video' => 'Upload the video file.']);
        }

        if ($existing) {
            DB::table('training_courses')->where('id', $id)->update($row);
        } else {
            $id = DB::table('training_courses')->insertGetId($row + ['is_active' => true, 'created_by' => $this->me(), 'created_at' => now()]);
        }

        // Quiz questions: replace the set
        $qs = (array) $r->input('q', []);
        DB::table('training_questions')->where('course_id', $id)->delete();
        $sort = 0;
        foreach ($qs as $i => $text) {
            $text = trim((string) $text);
            $opts = array_values(array_filter(array_map('trim', (array) ($r->input('opt')[$i] ?? [])), fn($o) => $o !== ''));
            if ($text === '' || count($opts) < 2) continue;
            $correct = (int) ($r->input('correct')[$i] ?? 0);
            DB::table('training_questions')->insert([
                'course_id' => $id, 'question' => $text, 'options' => json_encode($opts, JSON_UNESCAPED_UNICODE),
                'correct_index' => min(max($correct, 0), count($opts) - 1), 'sort' => $sort++, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        return redirect()->route('admin.training.index', ['tab' => 'courses'])->with('success', 'Course saved.');
    }

    public function toggle(int $id)
    {
        abort_unless($this->isManager(), 403);
        DB::table('training_courses')->where('id', $id)->update(['is_active' => DB::raw('NOT is_active'), 'updated_at' => now()]);
        return back()->with('success', 'Course updated.');
    }

    // ───────────────────────── Supervisor+: assign ─────────────────────────

    public function assignForm(Request $r)
    {
        abort_unless(StaffRole::isSupervisorOrAbove(), 403);
        if (!$this->ready()) return $this->notReady();
        $q = DB::table('staff')->where('is_active', true)->where('role', '!=', 'admin');
        if (!$this->isManager()) $q->where('location', session('staff_location'));
        return view('admin.training.assign', [
            'courses' => DB::table('training_courses')->where('is_active', true)->orderBy('title')->get(),
            'staff'   => $q->orderBy('location')->orderBy('name')->get(['id', 'name', 'location', 'role'])->groupBy('location'),
            'pick'    => (int) $r->query('course'),
        ]);
    }

    public function assign(Request $r)
    {
        abort_unless(StaffRole::isSupervisorOrAbove(), 403);
        $d = $r->validate(['course_id' => 'required|integer', 'staff_ids' => 'required|array|min:1', 'staff_ids.*' => 'integer', 'due_date' => 'required|date|after_or_equal:today']);
        $course = DB::table('training_courses')->where('id', $d['course_id'])->where('is_active', true)->first();
        abort_unless($course, 404);

        $q = DB::table('staff')->whereIn('id', $d['staff_ids'])->where('is_active', true);
        if (!$this->isManager()) $q->where('location', session('staff_location'));
        $n = 0; $skipped = 0;
        foreach ($q->get() as $s) {
            if (DB::table('training_assignments')->where('course_id', $course->id)->where('staff_id', $s->id)->where('status', '!=', 'completed')->exists()) { $skipped++; continue; }
            $again = DB::table('training_assignments')->where('course_id', $course->id)->where('staff_id', $s->id)->where('status', 'completed')->exists();
            DB::table('training_assignments')->insert([
                'course_id' => $course->id, 'staff_id' => $s->id, 'assigned_by' => $this->me(), 'due_date' => $d['due_date'],
                'is_retraining' => $again, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $n++;
        }
        return redirect()->route('admin.training.index')->with('success', "Assigned to {$n} staff" . ($skipped ? " ({$skipped} already had it open)." : '.'));
    }

    public function destroyAssignment(int $id)
    {
        abort_unless(StaffRole::isSupervisorOrAbove(), 403);
        DB::table('training_assignments')->where('id', $id)->where('status', '!=', 'completed')->delete();
        return back()->with('success', 'Assignment removed.');
    }

    /** External / classroom training: a manager records that the person completed it. */
    public function markComplete(Request $r, int $id)
    {
        abort_unless($this->isManager(), 403);
        $a = DB::table('training_assignments')->where('id', $id)->first();
        abort_unless($a, 404);
        $this->complete($a, (int) $r->input('score', 100));
        return back()->with('success', 'Marked complete.');
    }

    private function complete(object $a, int $score): void
    {
        DB::table('training_assignments')->where('id', $a->id)->update([
            'status' => 'completed', 'score' => max(0, min(100, $score)), 'completed_at' => now(), 'video_done' => true, 'updated_at' => now(),
        ]);
        $course = DB::table('training_courses')->where('id', $a->course_id)->first();
        if ($course && $course->recurrence_days && $course->is_active
            && !DB::table('training_assignments')->where('course_id', $a->course_id)->where('staff_id', $a->staff_id)->where('status', '!=', 'completed')->exists()) {
            DB::table('training_assignments')->insert([
                'course_id' => $a->course_id, 'staff_id' => $a->staff_id, 'assigned_by' => $a->assigned_by,
                'due_date' => now()->addDays((int) $course->recurrence_days)->toDateString(), 'is_retraining' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    // ───────────────────────── Everyone: my training ─────────────────────────

    public function mine()
    {
        if (!$this->ready()) return $this->notReady();
        $rows = DB::table('training_assignments as a')->join('training_courses as c', 'c.id', '=', 'a.course_id')
            ->where('a.staff_id', $this->me())->select('a.*', 'c.title', 'c.area', 'c.provider_type', 'c.provider_name', 'c.duration_minutes', 'c.recurrence_days', 'c.is_active')
            ->orderByRaw("a.status = 'completed'")->orderBy('a.due_date')->get();
        return view('admin.training.mine', ['rows' => $rows, 'state' => TrainingCompliance::stateFor($this->me())]);
    }

    private function myAssignment(int $id): object
    {
        $a = DB::table('training_assignments')->where('id', $id)->first();
        abort_unless($a && ((int) $a->staff_id === $this->me() || $this->isManager()), 404);
        return $a;
    }

    public function watch(int $id)
    {
        $a = $this->myAssignment($id);
        if ($a->status === 'completed') return redirect()->route('admin.training.certificate', $id);
        $course = DB::table('training_courses')->where('id', $a->course_id)->first();
        $qs = DB::table('training_questions')->where('course_id', $a->course_id)->orderBy('sort')->get(['id', 'question', 'options'])
            ->map(function ($q) { $q->options = json_decode($q->options, true) ?: []; return $q; });
        $staff = DB::table('staff')->where('id', $a->staff_id)->first();
        return view('admin.training.watch', compact('a', 'course', 'qs', 'staff'));
    }

    /** Progress beacon. Position can only advance about as fast as real time, so the video cannot be skipped. */
    public function ping(Request $r, int $id)
    {
        $a = $this->myAssignment($id);
        if ($a->status === 'completed') return response()->json(['ok' => true, 'done' => true]);
        $course = DB::table('training_courses')->where('id', $a->course_id)->first();

        $pos = max(0, (int) floor((float) $r->input('position')));
        $dur = max(0, (int) floor((float) $r->input('duration')));
        $max = (int) $a->max_position;
        if ($a->last_ping_at) {
            $elapsed = min(30, max(0, now()->getTimestamp() - Carbon::parse($a->last_ping_at)->getTimestamp()));
            $allowed = $max + (int) floor($elapsed * 1.1 + 0.5);
        } else {
            $allowed = 15;
        }
        $max = max($max, min($pos, $allowed));

        $basis = max($dur, (int) round(($course->duration_minutes ?? 0) * 60 * 0.8));
        $done  = (bool) $a->video_done || ($basis > 0 && $max >= 0.95 * $basis);

        DB::table('training_assignments')->where('id', $id)->update([
            'max_position' => $max, 'video_seconds' => $dur ?: $a->video_seconds, 'video_done' => $done,
            'status' => 'in_progress', 'last_ping_at' => now(), 'updated_at' => now(),
        ]);
        return response()->json(['ok' => true, 'max' => $max, 'done' => $done]);
    }

    public function submit(Request $r, int $id)
    {
        $a = $this->myAssignment($id);
        abort_unless((int) $a->staff_id === $this->me(), 403);
        if ($a->status === 'completed') return redirect()->route('admin.training.certificate', $id);
        $course = DB::table('training_courses')->where('id', $a->course_id)->first();

        if ($course->video_source !== 'none' && !$a->video_done) return back()->withErrors(['x' => 'Finish watching the whole video first.']);
        if ($course->policy_text && !$r->boolean('accept_policy')) return back()->withErrors(['x' => 'Tick the box to confirm you read and accept the policy.']);

        $qs = DB::table('training_questions')->where('course_id', $a->course_id)->get();
        $score = 100;
        if ($qs->isNotEmpty()) {
            $ans = (array) $r->input('ans', []);
            $right = $qs->filter(fn($q) => isset($ans[$q->id]) && (int) $ans[$q->id] === (int) $q->correct_index)->count();
            $score = (int) round(100 * $right / $qs->count());
        }
        DB::table('training_assignments')->where('id', $id)->update([
            'attempts' => $a->attempts + 1, 'policy_accepted' => $course->policy_text ? true : $a->policy_accepted, 'updated_at' => now(),
        ]);
        if ($score < (int) $course->pass_mark) {
            return back()->withErrors(['x' => "You scored {$score}%. The pass mark is {$course->pass_mark}%. Review and try again."]);
        }
        $this->complete($a, $score);
        return redirect()->route('admin.training.certificate', $id)->with('success', "Well done - you scored {$score}%.");
    }

    // ───────────────────────── Video and certificate ─────────────────────────

    public function video(int $courseId)
    {
        $course = DB::table('training_courses')->where('id', $courseId)->first();
        abort_unless($course && $course->video_path && Storage::disk('local')->exists($course->video_path), 404);
        $allowed = $this->isManager() || DB::table('training_assignments')->where('course_id', $courseId)->where('staff_id', $this->me())->exists();
        abort_unless($allowed, 403);
        $path = Storage::disk('local')->path($course->video_path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return response()->file($path, ['Content-Type' => $ext === 'webm' ? 'video/webm' : 'video/mp4', 'Cache-Control' => 'private, max-age=3600']);
    }

    public function certificate(int $id)
    {
        $a = $this->myAssignment($id);
        abort_unless($a->status === 'completed', 404);
        $course = DB::table('training_courses')->where('id', $a->course_id)->first();
        $staff = DB::table('staff')->where('id', $a->staff_id)->first();
        return view('admin.training.certificate', compact('a', 'course', 'staff'));
    }
}
