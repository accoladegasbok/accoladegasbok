<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AuditController extends Controller
{
    // =========================================================
    // GET /admin/audit — list of past + in-progress audit sessions
    // =========================================================
    public function index()
    {
        $sessions = DB::table('audit_sessions')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.audit.index', compact('sessions'));
    }

    // =========================================================
    // GET /admin/audit/create — choose location + category to start
    // =========================================================
    public function create()
    {
        $locations = [
            'Waxahachie TX', 'Kennedale TX', 'Elkhorn WI',
            'Ile-Ife Nigeria', 'Ibadan Nigeria', 'Oshodi Lagos', 'Accra Ghana',
        ];
        $categories = [
            'Engine','Transmission','Body','Suspension','Electrical',
            'Interior','Cooling','Brakes','Airbag','Fuel','Exhaust','Seat','Wheels','Consumable',
        ];

        return view('admin.audit.create', compact('locations', 'categories'));
    }

    // =========================================================
    // POST /admin/audit — start a new audit session
    // Snapshots every part_id + expected_qty at this moment
    // =========================================================
    public function store(Request $request)
    {
        $request->validate([
            'location' => 'required|string',
            'category' => 'required|string',
        ]);

        $parts = DB::table('parts_inventory')
            ->where('location', $request->location)
            ->where('part_category', $request->category)
            ->where('status', 'Available')
            ->get(['id', 'part_code', 'part_name', 'stock_qty']);

        if ($parts->isEmpty()) {
            return back()->with('error', 'No available parts found for this location and category.');
        }

        $sessionId = DB::table('audit_sessions')->insertGetId([
            'location'    => $request->location,
            'category'    => $request->category,
            'started_by'  => Session::get('staff_name', Session::get('staff_role', 'Staff')),
            'status'      => 'in_progress',
            'total_items' => $parts->count(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $rows = $parts->map(fn($p) => [
            'audit_session_id' => $sessionId,
            'part_id'           => $p->id,
            'part_code'         => $p->part_code,
            'part_name'         => $p->part_name,
            'expected_qty'      => $p->stock_qty,
            'counted_qty'       => null,
            'discrepancy'       => 0,
            'adjusted'          => false,
            'created_at'        => now(),
            'updated_at'        => now(),
        ])->toArray();

        DB::table('audit_items')->insert($rows);

        return redirect()->route('admin.audit.show', $sessionId)
            ->with('success', 'Audit session started — ' . $parts->count() . ' items to count.');
    }

    // =========================================================
    // GET /admin/audit/{id} — the counting screen
    // =========================================================
    public function show(int $id)
    {
        $session = DB::table('audit_sessions')->where('id', $id)->first();
        abort_if(!$session, 404);

        $items = DB::table('audit_items')
            ->where('audit_session_id', $id)
            ->orderBy('part_name')
            ->get();

        return view('admin.audit.show', compact('session', 'items'));
    }

    // =========================================================
    // POST /admin/audit/{id}/count — submit a count for one item
    // =========================================================
    public function recordCount(Request $request, int $id)
    {
        $request->validate([
            'item_id'     => 'required|integer',
            'counted_qty' => 'required|integer|min:0',
            'reason'      => 'nullable|string|max:255',
        ]);

        $item = DB::table('audit_items')
            ->where('id', $request->item_id)
            ->where('audit_session_id', $id)
            ->first();

        abort_if(!$item, 404);

        $discrepancy = $request->counted_qty - $item->expected_qty;

        if ($discrepancy !== 0 && !$request->reason) {
            return response()->json([
                'error' => 'A reason is required when the count does not match the expected quantity.',
            ], 422);
        }

        DB::table('audit_items')->where('id', $item->id)->update([
            'counted_qty' => $request->counted_qty,
            'discrepancy' => $discrepancy,
            'reason'      => $request->reason,
            'updated_at'  => now(),
        ]);

        return response()->json(['success' => true, 'discrepancy' => $discrepancy]);
    }

    // =========================================================
    // POST /admin/audit/{id}/complete — close out the session,
    // optionally adjusting stock_qty to match counted amounts
    // =========================================================
    public function complete(Request $request, int $id)
    {
        $session = DB::table('audit_sessions')->where('id', $id)->first();
        abort_if(!$session, 404);

        $items = DB::table('audit_items')->where('audit_session_id', $id)->get();

        $uncounted = $items->where('counted_qty', null)->count();
        if ($uncounted > 0) {
            return back()->with('error', "Cannot complete — {$uncounted} item(s) still need a count.");
        }

        $matched      = $items->where('discrepancy', 0)->count();
        $discrepancies = $items->where('discrepancy', '!=', 0)->count();

        // If requested, adjust stock_qty in parts_inventory to match the count
        if ($request->boolean('apply_adjustments')) {
            foreach ($items->where('discrepancy', '!=', 0) as $item) {
                DB::table('parts_inventory')->where('id', $item->part_id)->update([
                    'stock_qty'  => $item->counted_qty,
                    'updated_at' => now(),
                ]);
                DB::table('audit_items')->where('id', $item->id)->update(['adjusted' => true]);
            }
        }

        DB::table('audit_sessions')->where('id', $id)->update([
            'status'             => 'completed',
            'matched_items'      => $matched,
            'discrepancy_items'  => $discrepancies,
            'completed_at'       => now(),
            'updated_at'         => now(),
        ]);

        return redirect()->route('admin.audit.show', $id)
            ->with('success', 'Audit completed.' . ($request->boolean('apply_adjustments') ? ' Stock levels adjusted to match counts.' : ''));
    }
}
