<?php
// FILE: app/Http/Controllers/Admin/OverrideController.php
//
// Central checkpoint for the override-PIN system (#2/#14/#15). Any
// action that requires Supervisor-or-above approval (removing a cart
// item mid-sale, deleting an invoice, editing a locked price, etc.)
// calls POST /admin/override/verify with the entered PIN + a
// description of what's being approved. Every attempt — success or
// failure — is logged to override_logs so there's a full audit
// trail of who approved what.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class OverrideController extends Controller
{
    const ELIGIBLE_ROLES = ['supervisor', 'manager', 'admin'];

    // =========================================================
    // POST /admin/override/verify
    // Body: { pin, action, context }
    // Returns: { success, approved_by, role } or { error }
    // =========================================================
    public function verify(Request $request)
    {
        $request->validate([
            'pin'     => 'required|string',
            'action'  => 'required|string|max:100',
            'context' => 'nullable|string|max:255',
        ]);

        $result = self::checkPin($request->pin, $request->action, $request->context);

        if (!$result['ok']) {
            return response()->json(['error' => $result['error']], 403);
        }

        return response()->json([
            'success'     => true,
            'approved_by' => $result['staff']->name,
            'role'        => $result['staff']->role,
        ]);
    }

    // =========================================================
    // The one place a PIN is checked. Used by verify() above AND by server-side actions
    // that must check the PIN themselves (e.g. applying customer credit on an invoice),
    // so approval cannot be faked by the browser.
    // Every attempt is logged. After 5 wrong PINs in 10 minutes, the same signed-in user
    // is locked out of PIN checks for the rest of that window.
    // Returns: ['ok' => bool, 'staff' => row|null, 'error' => string|null]
    // =========================================================
    public static function checkPin(string $pin, string $action, ?string $context = null): array
    {
        $requesterId = Session::get('staff_id');

        $recentFailures = DB::table('override_logs')
            ->where('requested_by_staff_id', $requesterId)
            ->where('approved_by_staff_id', 0)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();
        if ($recentFailures >= 5) {
            return ['ok' => false, 'staff' => null, 'error' => 'Too many wrong PIN attempts. Wait 10 minutes, or ask a supervisor to do this.'];
        }

        $pin = trim($pin);
        if ($pin === '') {
            return ['ok' => false, 'staff' => null, 'error' => 'Enter a supervisor PIN.'];
        }

        $staffList = DB::table('staff')
            ->whereIn('role', self::ELIGIBLE_ROLES)
            ->whereNotNull('override_pin_hash')
            ->where('is_active', true)
            ->get();

        $matched = null;
        foreach ($staffList as $staff) {
            if (Hash::check($pin, $staff->override_pin_hash)) { $matched = $staff; break; }
        }

        if (!$matched) {
            DB::table('override_logs')->insert([
                'approved_by_staff_id'  => 0,
                'approved_by_role'      => 'UNKNOWN',
                'action'                => $action,
                'context'               => ($context ?? '') . ' [FAILED — invalid PIN]',
                'requested_by_staff_id' => $requesterId,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);
            return ['ok' => false, 'staff' => null, 'error' => 'Invalid override PIN.'];
        }

        DB::table('override_logs')->insert([
            'approved_by_staff_id'  => $matched->id,
            'approved_by_role'      => $matched->role,
            'action'                => $action,
            'context'               => $context,
            'requested_by_staff_id' => $requesterId,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        return ['ok' => true, 'staff' => $matched, 'error' => null];
    }

    // =========================================================
    // GET /admin/override/set-pin — show the PIN setup page
    // =========================================================
    public function setOwnPinPage()
    {
        $role = Session::get('staff_role');
        if (!in_array($role, self::ELIGIBLE_ROLES)) {
            abort(403, 'Only admin, manager or supervisor can set an override PIN.');
        }
        return view('admin.override.set-pin');
    }

    // =========================================================
    // POST /admin/override/set-pin — save the PIN
    // Staff (Supervisor/Manager/Admin) set their OWN PIN — never
    // visible to anyone else afterward, including Admin (only a
    // reset/clear is possible, not viewing the existing PIN).
    // =========================================================
    public function setOwnPin(Request $request)
    {
        $request->validate(['pin' => 'required|digits:4']);

        $staffId = Session::get('staff_id');
        $role    = Session::get('staff_role');

        if (!in_array($role, self::ELIGIBLE_ROLES)) {
            return response()->json(['error' => 'Your role is not eligible for an override PIN.'], 403);
        }

        DB::table('staff')->where('id', $staffId)->update([
            'override_pin_hash' => Hash::make($request->pin),
            'updated_at'        => now(),
        ]);

        // If request expects JSON (called via AJAX), return JSON
        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        // Otherwise redirect back with success flash (called from set-pin form)
        return redirect()->route('admin.override.set-pin-page')
            ->with('success', 'Your override PIN has been set successfully.');
    }

    // =========================================================
    // POST /admin/override/clear-pin/{staffId}
    // Admin or Manager can clear (not view) another staff member's
    // PIN, forcing them to set a new one. Supervisor and below
    // cannot — resetting a PIN is itself a higher-trust action than
    // using one.
    // =========================================================
    public function clearPin(int $staffId)
    {
        if (!in_array(Session::get('staff_role'), ['admin', 'manager'])) {
            return response()->json(['error' => 'Admin or Manager only.'], 403);
        }

        DB::table('staff')->where('id', $staffId)->update([
            'override_pin_hash' => null,
            'updated_at'        => now(),
        ]);

        return response()->json(['success' => true]);
    }

    // =========================================================
    // GET /admin/override/logs — audit trail view
    // =========================================================
    public function logs()
    {
        $logs = DB::table('override_logs as ol')
            ->leftJoin('staff as approver',  'approver.id',  '=', 'ol.approved_by_staff_id')
            ->leftJoin('staff as requester', 'requester.id', '=', 'ol.requested_by_staff_id')
            ->select('ol.*', 'approver.name as approver_name', 'requester.name as requester_name')
            ->orderByDesc('ol.created_at')
            ->paginate(50);

        return view('admin.override.logs', compact('logs'));
    }
}
