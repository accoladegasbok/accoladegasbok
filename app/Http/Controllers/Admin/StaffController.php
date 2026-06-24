<?php
// FILE: app/Http/Controllers/Admin/StaffController.php
// UPDATED: New roles — supervisor (manager-level minus staff/discount/financial),
// stocking_clerk (inventory-add only), sales_rep (commission-based).

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class StaffController extends Controller
{
    const LOCATIONS = [
        'All',
        'Waxahachie TX','Elkhorn WI',
        'Ile-Ife Nigeria','Ibadan Nigeria','Oshodi Lagos','Accra Ghana',
    ];

    const ROLES = ['admin','manager','supervisor','staff','stocking_clerk','sales_rep','viewer'];

    // ── List all staff ────────────────────────────────────────────
    public function index()
    {
        $staff = DB::table('staff')
            ->orderByRaw("FIELD(role,'admin','manager','supervisor','staff','sales_rep','stocking_clerk','viewer')")
            ->orderBy('name')
            ->get();

        $counts = [
            'total'    => $staff->count(),
            'active'   => $staff->where('is_active', true)->count(),
            'inactive' => $staff->where('is_active', false)->count(),
        ];

        return view('admin.staff.index', compact('staff','counts'));
    }

    // ── Create form ───────────────────────────────────────────────
    public function create()
    {
        return view('admin.staff.create', [
            'locations' => self::LOCATIONS,
            'roles'     => self::ROLES,
        ]);
    }

    // ── Store new staff member ────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'name'                     => 'required|string|max:100',
            'email'                    => 'required|email|max:150|unique:staff,email',
            'password'                 => 'required|string|min:8|confirmed',
            'role'                     => 'required|in:' . implode(',', self::ROLES),
            'location'                 => 'required|string',
            'phone'                    => 'nullable|string|max:30',
            'commission_base_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $data = [
            'name'       => trim($request->name),
            'email'      => strtolower(trim($request->email)),
            'password'   => Hash::make($request->password),
            'role'       => $request->role,
            'location'   => $request->location,
            'phone'      => $request->phone,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Commission % only meaningful (and only settable) for sales_rep,
        // and only by an admin — same trust boundary as discount caps.
        if ($request->role === 'sales_rep' && Session::get('staff_role') === 'admin') {
            $data['commission_base_percent'] = $request->commission_base_percent;
            $data['commission_tiers'] = $request->commission_tiers
                ? json_encode(json_decode($request->commission_tiers, true))
                : null;
        }

        DB::table('staff')->insert($data);

        return redirect()->route('admin.staff.index')
            ->with('success', "{$request->name} added as {$request->role}.");
    }

   // ── Edit form ─────────────────────────────────────────────────
    public function edit(int $id)
    {
        $member = DB::table('staff')->where('id', $id)->first();
        abort_if(!$member, 404);

        return view('admin.staff.edit', [
            'member'    => $member,
            'locations' => self::LOCATIONS,
            'roles'     => self::ROLES,
        ]);
    }
    // ── Update staff member ──────────────────────────────────────
    public function update(Request $request, int $id)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150|unique:staff,email,'.$id,
            'role'     => 'required|in:' . implode(',', self::ROLES),
            'location' => 'required|string',
            'phone'    => 'nullable|string|max:30',
            'password' => 'nullable|string|min:8|confirmed',
            'discount_cap_fixed'      => 'nullable|numeric|min:0',
            'discount_cap_percent'    => 'nullable|numeric|min:0|max:100',
            'commission_base_percent' => 'nullable|numeric|min:0|max:100',
        ]);
        $data = [
            'name'       => trim($request->name),
            'email'      => strtolower(trim($request->email)),
            'role'       => $request->role,
            'location'   => $request->location,
            'phone'      => $request->phone,
            'updated_at' => now(),
        ];
        // Only update password if a new one was provided
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        // Discount caps AND commission terms are admin-privilege only —
        // a manager/supervisor editing a profile cannot change either,
        // even if they have access to this edit screen.
        if (Session::get('staff_role') === 'admin') {
            $data['discount_cap_fixed']      = $request->discount_cap_fixed;
            $data['discount_cap_percent']    = $request->discount_cap_percent;
            $data['commission_base_percent'] = $request->commission_base_percent;
            $data['commission_tiers'] = $request->commission_tiers
                ? json_encode(json_decode($request->commission_tiers, true))
                : null;
        }
        DB::table('staff')->where('id', $id)->update($data);
        return redirect()->route('admin.staff.index')
            ->with('success', "{$request->name} updated successfully.");
    }

    // ── Toggle active/inactive (AJAX) ─────────────────────────────
    public function toggle(int $id)
    {
        // Cannot deactivate yourself
        if ($id == Session::get('staff_id')) {
            return response()->json(['error' => 'You cannot deactivate your own account.'], 422);
        }

        $member = DB::table('staff')->where('id', $id)->first();
        if (!$member) return response()->json(['error' => 'Not found.'], 404);

        $newStatus = !$member->is_active;
        DB::table('staff')->where('id', $id)->update([
            'is_active'  => $newStatus,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success'   => true,
            'is_active' => $newStatus,
            'message'   => $member->name . ($newStatus ? ' reactivated.' : ' deactivated.'),
        ]);
    }
}
