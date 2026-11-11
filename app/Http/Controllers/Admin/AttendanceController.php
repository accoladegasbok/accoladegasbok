<?php
// FILE: app/Http/Controllers/Admin/AttendanceController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttendanceStats;
use App\Services\TrainingCompliance;
use App\Services\WebAuthnLite;
use App\Support\Locations;
use App\Support\StaffRole;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    private function staffId(): int { return (int) session('staff_id'); }
    private function isManager(): bool { return in_array(session('staff_role'), ['admin', 'manager'], true); }

    private function ready(): bool { return AttendanceStats::ready() && Schema::hasTable('staff_passkeys'); }

    private function notReady()
    {
        return view('admin.attendance.clock', ['notReady' => true, 'rec' => null, 'history' => collect(), 'passkeys' => collect(),
            'site' => AttendanceStats::site(null), 'overdue' => collect(), 'staff' => null, 'today' => now()->toDateString(), 'trainingState' => 'green']);
    }

    // ───────────────────────── Staff: the clock-in screen ─────────────────────────

    public function clock()
    {
        if (!$this->ready()) return $this->notReady();

        $id    = $this->staffId();
        $staff = DB::table('staff')->where('id', $id)->first();
        $site  = AttendanceStats::site($staff->location ?? session('staff_location'));
        $today = Carbon::now($site->timezone)->toDateString();

        return view('admin.attendance.clock', [
            'notReady'      => false,
            'staff'         => $staff,
            'site'          => $site,
            'today'         => $today,
            'rec'           => DB::table('attendance_records')->where('staff_id', $id)->where('work_date', $today)->first(),
            'history'       => DB::table('attendance_records')->where('staff_id', $id)->orderByDesc('work_date')->limit(14)->get(),
            'passkeys'      => DB::table('staff_passkeys')->where('staff_id', $id)->orderBy('id')->get(),
            'overdue'       => TrainingCompliance::overdueFor($id),
            'trainingState' => TrainingCompliance::stateFor($id),
        ]);
    }

    /** One-time challenge for either registering a phone or signing a clock-in. */
    public function challenge(Request $r)
    {
        $purpose = $r->query('purpose') === 'register' ? 'register' : 'assert';
        $id = $this->staffId();
        $out = ['challenge' => WebAuthnLite::newChallenge($purpose), 'rpId' => $r->getHost()];
        if ($purpose === 'assert') {
            $out['credentials'] = DB::table('staff_passkeys')->where('staff_id', $id)->where('approved', true)->pluck('credential_id');
        } else {
            $staff = DB::table('staff')->where('id', $id)->first();
            $out['user'] = ['id' => WebAuthnLite::b64uEncode('staff-' . $id), 'name' => (string) ($staff->email ?? 'staff' . $id), 'displayName' => (string) ($staff->name ?? 'Staff')];
        }
        return response()->json($out);
    }

    public function registerPasskey(Request $r)
    {
        if (!$this->ready()) return response()->json(['ok' => false, 'message' => 'Attendance is not set up yet.'], 503);
        $id = $this->staffId();

        if (DB::table('staff_passkeys')->where('staff_id', $id)->count() >= 3) {
            return response()->json(['ok' => false, 'message' => 'You already have 3 phones registered. Ask a manager to remove one.'], 422);
        }
        $res = WebAuthnLite::verifyRegistration(
            (string) $r->input('client_data'), (string) $r->input('auth_data'), (string) $r->input('public_key'),
            $r->getHost(), $r->getSchemeAndHttpHost()
        );
        if (!$res['ok']) return response()->json(['ok' => false, 'message' => $res['error']], 422);

        $cid = (string) $r->input('credential_id');
        if ($cid === '' || strlen($cid) > 255 || DB::table('staff_passkeys')->where('credential_id', $cid)->exists()) {
            return response()->json(['ok' => false, 'message' => 'This phone is already registered.'], 422);
        }
        $auto = $this->isManager();   // a manager's or admin's own phone needs no second approval
        DB::table('staff_passkeys')->insert([
            'staff_id' => $id, 'credential_id' => $cid, 'public_key' => $res['pem'], 'sign_count' => $res['sign_count'],
            'label' => substr(trim((string) $r->input('label', 'My phone')), 0, 80),
            'user_agent' => substr((string) $r->userAgent(), 0, 255),
            'approved' => $auto, 'approved_by' => $auto ? $id : null, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return response()->json(['ok' => true, 'message' => $auto ? 'Phone registered and ready.' : 'Phone registered. A manager must approve it before you can use it.']);
    }

    // ───────────────────────── Staff: clock in / out ─────────────────────────

    public function clockIn(Request $r)  { return $this->punch($r, 'in'); }
    public function clockOut(Request $r) { return $this->punch($r, 'out'); }

    private function fail(string $msg, int $code = 422)
    {
        return response()->json(['ok' => false, 'message' => $msg], $code);
    }

    private static function distanceM(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $r = 6371000;
        $p1 = deg2rad($lat1); $p2 = deg2rad($lat2);
        $a = sin(($p2 - $p1) / 2) ** 2 + cos($p1) * cos($p2) * sin(deg2rad($lng2 - $lng1) / 2) ** 2;
        return (int) round(2 * $r * asin(min(1, sqrt($a))));
    }

    private function punch(Request $r, string $dir)
    {
        if (!$this->ready()) return $this->fail('Attendance is not set up yet.', 503);

        $id    = $this->staffId();
        $staff = DB::table('staff')->where('id', $id)->first();
        if (!$staff || !$staff->is_active) return $this->fail('Your account is not active.', 403);

        $site   = AttendanceStats::site($staff->location);
        $nowTz  = Carbon::now($site->timezone);
        $today  = $nowTz->toDateString();
        $rec    = DB::table('attendance_records')->where('staff_id', $id)->where('work_date', $today)->first();

        if ($dir === 'in') {
            if ($rec && $rec->clock_in_at) return $this->fail('You are already clocked in today.');
            if ($rec && $rec->status === 'blocked') {
                return $this->fail('You were marked absent today because of overdue training. A manager can excuse it, otherwise you can clock in tomorrow once the training is done.', 423);
            }
            // A manager's "excuse" lets the person clock in for that day even if training is still open.
            $overdue = ($rec && $rec->status === 'excused') ? collect() : TrainingCompliance::overdueFor($id);
            if ($overdue->isNotEmpty()) {
                $names = $overdue->pluck('title')->implode(', ');
                $data = ['staff_id' => $id, 'work_date' => $today, 'location' => $staff->location, 'status' => 'blocked',
                         'note' => substr('Blocked - overdue training: ' . $names, 0, 255), 'ip' => $r->ip(), 'updated_at' => now()];
                if ($rec) DB::table('attendance_records')->where('id', $rec->id)->update($data);
                else DB::table('attendance_records')->insert($data + ['created_at' => now()]);
                return $this->fail('You cannot clock in: overdue training (' . $names . '). You are marked absent for today. Complete the training so you can clock in tomorrow.', 423);
            }
        } else {
            if (!$rec || !$rec->clock_in_at) return $this->fail('You have not clocked in today.');
            if ($rec->clock_out_at) return $this->fail('You already clocked out today.');
        }

        // Location check
        $lat = is_numeric($r->input('lat')) ? (float) $r->input('lat') : null;
        $lng = is_numeric($r->input('lng')) ? (float) $r->input('lng') : null;
        $acc = is_numeric($r->input('accuracy')) ? (int) round((float) $r->input('accuracy')) : null;
        $dist = null;
        if ($site->enforce_geofence && $site->lat !== null && $site->lng !== null) {
            if ($lat === null || $lng === null) return $this->fail('Location is required. Allow location access and try again.');
            $dist = self::distanceM($lat, $lng, (float) $site->lat, (float) $site->lng);
            if ($dist - min((int) $acc, 100) > (int) $site->radius_m) {
                return $this->fail("You are about {$dist} m from {$staff->location}. You must be within {$site->radius_m} m to clock " . $dir . '.');
            }
        } elseif ($lat !== null && $lng !== null && $site->lat !== null && $site->lng !== null) {
            $dist = self::distanceM($lat, $lng, (float) $site->lat, (float) $site->lng);
        }

        // Fingerprint / face unlock through the registered phone
        $verified = 'selfie';
        $hasKey = DB::table('staff_passkeys')->where('staff_id', $id)->where('approved', true)->exists();
        if ($site->require_biometric && !$hasKey) {
            return $this->fail('Register your phone first (Clock screen, "Register this phone") and wait for manager approval.');
        }
        if ($hasKey) {
            $key = DB::table('staff_passkeys')->where('staff_id', $id)->where('approved', true)
                ->where('credential_id', (string) $r->input('credential_id'))->first();
            if (!$key) return $this->fail('Unlock with your registered phone (fingerprint or face) to continue.');
            $v = WebAuthnLite::verifyAssertion($key->public_key, (int) $key->sign_count, (string) $r->input('client_data'),
                (string) $r->input('auth_data'), (string) $r->input('signature'), $r->getHost(), $r->getSchemeAndHttpHost());
            if (!$v['ok']) return $this->fail($v['error']);
            DB::table('staff_passkeys')->where('id', $key->id)->update(['sign_count' => $v['sign_count'], 'last_used_at' => now()]);
            $verified = 'passkey';
        }

        // Selfie
        $path = null;
        if ($r->hasFile('photo')) {
            $r->validate(['photo' => 'image|max:4096']);
            $path = Storage::disk('local')->putFile('attendance/' . $nowTz->format('Y-m'), $r->file('photo'));
        } elseif ($dir === 'in') {
            return $this->fail('A selfie is required. Allow camera access and try again.');
        }

        if ($dir === 'in') {
            $shift = Carbon::parse($today . ' ' . $site->shift_start, $site->timezone);
            $late  = $nowTz->gt($shift->copy()->addMinutes((int) $site->grace_minutes));
            $mins  = $late ? (int) $shift->diffInMinutes($nowTz) : 0;
            $data = [
                'staff_id' => $id, 'work_date' => $today, 'location' => $staff->location, 'clock_in_at' => now(),
                'in_photo' => $path, 'in_lat' => $lat, 'in_lng' => $lng, 'in_accuracy_m' => $acc, 'in_distance_m' => $dist,
                'status' => $late ? 'late' : 'present', 'late_minutes' => $mins, 'verified_by' => $verified,
                'note' => null, 'ip' => $r->ip(), 'updated_at' => now(),
            ];
            if ($rec) DB::table('attendance_records')->where('id', $rec->id)->update($data);   // e.g. an excused block
            else DB::table('attendance_records')->insert($data + ['created_at' => now()]);
            return response()->json(['ok' => true, 'message' => ($late ? "Clocked in at {$nowTz->format('g:i A')} - {$mins} minutes after shift start." : "Clocked in at {$nowTz->format('g:i A')}. Have a great day!")]);
        }

        DB::table('attendance_records')->where('id', $rec->id)->update([
            'clock_out_at' => now(), 'out_photo' => $path, 'out_lat' => $lat, 'out_lng' => $lng, 'updated_at' => now(),
        ]);
        return response()->json(['ok' => true, 'message' => "Clocked out at {$nowTz->format('g:i A')}. Thank you."]);
    }

    // ───────────────────────── Supervisor+: report ─────────────────────────

    public function report(Request $r)
    {
        abort_unless(StaffRole::isSupervisorOrAbove(), 403);
        if (!$this->ready()) return $this->notReady();

        $date = Carbon::parse($r->query('date', now()->toDateString()))->toDateString();
        $ym   = $r->query('ym', Carbon::parse($date)->format('Y-m'));
        $loc  = (string) $r->query('location', '');
        $isMgr = $this->isManager();
        // supervisors only see their own location
        if (!$isMgr) $loc = (string) session('staff_location');

        $staffQ = DB::table('staff')->where('is_active', true)->where('role', '!=', 'admin');
        if ($loc !== '') $staffQ->where('location', $loc);
        $staff = $staffQ->orderBy('location')->orderBy('name')->get();

        $recs = DB::table('attendance_records')->where('work_date', $date)->whereIn('staff_id', $staff->pluck('id'))->get()->keyBy('staff_id');

        $rows = $staff->map(function ($s) use ($recs, $date) {
            $site = AttendanceStats::site($s->location);
            $rec  = $recs->get($s->id);
            $d    = Carbon::parse($date, $site->timezone);
            $working = AttendanceStats::isWorkingDay($site, $d);
            if ($rec && $rec->clock_in_at) $state = $rec->status === 'late' ? 'late' : 'present';
            elseif ($rec && $rec->status === 'excused') $state = 'excused';
            elseif ($rec && $rec->status === 'blocked') $state = 'blocked';
            elseif (!$working) $state = 'rest';
            elseif ($date > Carbon::now($site->timezone)->toDateString()) $state = 'future';
            elseif ($date === Carbon::now($site->timezone)->toDateString()
                    && Carbon::now($site->timezone)->lte(Carbon::parse($date . ' ' . $site->shift_start, $site->timezone)->addMinutes((int) $site->grace_minutes))) $state = 'pending';
            else $state = 'absent';
            return (object) ['staff' => $s, 'rec' => $rec, 'state' => $state, 'site' => $site, 'training' => TrainingCompliance::stateFor($s->id)];
        });

        $summary = $staff->map(fn($s) => (object) ['staff' => $s, 'm' => AttendanceStats::forMonth($s->id, $s->location, $ym)]);

        $locations = $isMgr ? DB::table('staff')->where('is_active', true)->whereNotNull('location')->distinct()->orderBy('location')->pluck('location') : collect([$loc]);

        return view('admin.attendance.report', compact('rows', 'summary', 'date', 'ym', 'loc', 'locations', 'isMgr'));
    }

    /** Excuse a day (sick, sent on errand, system fault). Removes the absence / training block for that day. */
    public function excuse(Request $r)
    {
        abort_unless(StaffRole::isSupervisorOrAbove(), 403);
        $r->validate(['staff_id' => 'required|integer', 'date' => 'required|date', 'note' => 'nullable|string|max:200']);
        $staff = DB::table('staff')->where('id', $r->staff_id)->first();
        abort_unless($staff, 404);
        if (!$this->isManager() && $staff->location !== session('staff_location')) abort(403);

        $date = Carbon::parse($r->date)->toDateString();
        $note = 'Excused by ' . session('staff_name') . ($r->note ? ': ' . $r->note : '');
        $rec = DB::table('attendance_records')->where('staff_id', $staff->id)->where('work_date', $date)->first();
        if ($rec && $rec->clock_in_at) return back()->withErrors(['x' => 'That person did clock in.']);
        $data = ['status' => 'excused', 'note' => substr($note, 0, 255), 'updated_at' => now()];
        if ($rec) DB::table('attendance_records')->where('id', $rec->id)->update($data);
        else DB::table('attendance_records')->insert($data + ['staff_id' => $staff->id, 'work_date' => $date, 'location' => $staff->location, 'created_at' => now()]);
        return back()->with('success', $staff->name . ' excused for ' . $date . '.');
    }

    /** Selfie viewer (files are private, only supervisors and above). */
    public function photo(int $id, string $which)
    {
        abort_unless(StaffRole::isSupervisorOrAbove() || DB::table('attendance_records')->where('id', $id)->where('staff_id', $this->staffId())->exists(), 403);
        $rec = DB::table('attendance_records')->where('id', $id)->first();
        $path = $rec ? ($which === 'out' ? $rec->out_photo : $rec->in_photo) : null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path));
    }

    // ───────────────────────── Manager+: settings & phone approval ─────────────────────────

    public function settings()
    {
        abort_unless($this->isManager(), 403);
        if (!$this->ready()) return $this->notReady();

        $locs = collect(Locations::all())
            ->merge(DB::table('staff')->whereNotNull('location')->where('location', '!=', '')->distinct()->pluck('location'))
            ->merge(DB::table('attendance_sites')->pluck('location'))
            ->unique()->sort()->values();
        $sites = DB::table('attendance_sites')->get()->keyBy('location');

        $passkeys = DB::table('staff_passkeys as p')->join('staff as s', 's.id', '=', 'p.staff_id')
            ->select('p.*', 's.name as staff_name', 's.location as staff_location')->orderBy('p.approved')->orderByDesc('p.id')->get();
        $noPhone = DB::table('staff')->where('is_active', true)->where('role', '!=', 'admin')
            ->whereNotIn('id', DB::table('staff_passkeys')->where('approved', true)->pluck('staff_id'))->orderBy('name')->get(['id', 'name', 'location']);

        return view('admin.attendance.settings', compact('locs', 'sites', 'passkeys', 'noPhone'));
    }

    public function saveSite(Request $r)
    {
        abort_unless($this->isManager(), 403);
        $d = $r->validate([
            'location' => 'required|string|max:60',
            'lat' => 'nullable|numeric|between:-90,90', 'lng' => 'nullable|numeric|between:-180,180',
            'radius_m' => 'required|integer|min:30|max:2000',
            'shift_start' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'grace_minutes' => 'required|integer|min:0|max:120',
            'working_days' => 'nullable|array', 'working_days.*' => 'in:1,2,3,4,5,6,7',
        ]);
        DB::table('attendance_sites')->updateOrInsert(['location' => $d['location']], [
            'lat' => $d['lat'] ?? null, 'lng' => $d['lng'] ?? null, 'radius_m' => $d['radius_m'],
            'enforce_geofence' => $r->boolean('enforce_geofence'), 'require_biometric' => $r->boolean('require_biometric'),
            'shift_start' => $d['shift_start'], 'grace_minutes' => $d['grace_minutes'],
            'working_days' => implode(',', $d['working_days'] ?? []) ?: '1,2,3,4,5,6',
            'timezone' => 'Africa/Lagos', 'updated_at' => now(), 'created_at' => now(),
        ]);
        return back()->with('success', $d['location'] . ' saved.');
    }

    public function approvePasskey(int $id)
    {
        abort_unless($this->isManager(), 403);
        DB::table('staff_passkeys')->where('id', $id)->update(['approved' => true, 'approved_by' => $this->staffId(), 'updated_at' => now()]);
        return back()->with('success', 'Phone approved.');
    }

    public function revokePasskey(int $id)
    {
        abort_unless($this->isManager(), 403);
        DB::table('staff_passkeys')->where('id', $id)->delete();
        return back()->with('success', 'Phone removed.');
    }
}
