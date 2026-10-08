<?php
// FILE: app/Http/Controllers/Admin/PartRequestAdminController.php
//
// Staff inbox for customer "Request this part" submissions.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PartRequestAdminController extends Controller
{
    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'sourced' => 'Sourced', 'closed' => 'Closed'];

    // GET /admin/part-requests
    public function index(Request $request)
    {
        $status = $request->get('status', 'open');
        $q      = trim((string) $request->get('q', ''));

        $query = DB::table('part_requests as r')
            ->leftJoin('parts_inventory as p', 'p.id', '=', 'r.part_id')
            ->select('r.*', 'p.part_code', 'p.part_name as looked_at_part')
            ->orderByRaw("FIELD(r.status, 'new', 'contacted', 'sourced', 'closed')")
            ->orderByDesc('r.created_at');

        if ($status === 'open') {
            $query->whereIn('r.status', ['new', 'contacted']);
        } elseif (isset(self::STATUSES[$status])) {
            $query->where('r.status', $status);
        }

        if ($q !== '') {
            $query->where(function ($sq) use ($q) {
                $sq->where('r.customer_name', 'like', "%{$q}%")
                   ->orWhere('r.customer_phone', 'like', "%{$q}%")
                   ->orWhere('r.part_text', 'like', "%{$q}%")
                   ->orWhere('r.vehicle_text', 'like', "%{$q}%");
            });
        }

        $requests = $query->paginate(25)->withQueryString();

        $counts = DB::table('part_requests')->select('status', DB::raw('COUNT(*) as n'))->groupBy('status')->pluck('n', 'status');

        return view('admin.part-requests.index', [
            'requests' => $requests,
            'counts'   => $counts,
            'status'   => $status,
            'q'        => $q,
            'statuses' => self::STATUSES,
        ]);
    }

    // POST /admin/part-requests/{id}
    public function update(Request $request, int $id)
    {
        $request->validate([
            'status'      => 'required|in:' . implode(',', array_keys(self::STATUSES)),
            'staff_notes' => 'nullable|string|max:2000',
        ]);

        if (!DB::table('part_requests')->where('id', $id)->exists()) abort(404);

        DB::table('part_requests')->where('id', $id)->update([
            'status'              => $request->status,
            'staff_notes'         => $request->staff_notes,
            'handled_by_staff_id' => Session::get('staff_id'),
            'handled_at'          => now(),
            'updated_at'          => now(),
        ]);

        return back()->with('success', "Request #{$id} updated.");
    }
}
