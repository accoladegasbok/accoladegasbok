<?php
// FILE: app/Http/Controllers/Admin/PartApprovalController.php
//
// The "Awaiting Approval" page. Only a manager or admin may open it or act on it.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PartApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartApprovalController extends Controller
{
    private function guard(): void
    {
        abort_unless(PartApprovalService::canApprove(), 403, 'Only a manager or admin can review new inventory.');
    }

    // GET /admin/part-approvals?tab=pending|approved|rejected
    public function index(Request $request)
    {
        $this->guard();

        $tab = in_array($request->get('tab'), ['pending', 'approved', 'rejected'], true) ? $request->get('tab') : 'pending';

        $rows = DB::table('part_approvals as a')
            ->leftJoin('parts_inventory as p', 'p.id', '=', 'a.part_id')
            ->where('a.status', $tab)
            ->orderByDesc('a.created_at')
            ->select('a.*', 'p.part_code', 'p.part_name', 'p.brand', 'p.model', 'p.year_from', 'p.year_to', 'p.condition_grade',
                     'p.price_local', 'p.currency_code', 'p.location', 'p.bin_location', 'p.stock_qty', 'p.photos', 'p.part_category', 'p.status as part_status')
            ->paginate(40)->withQueryString();

        $counts = DB::table('part_approvals')->select('status', DB::raw('count(*) as n'))->groupBy('status')->pluck('n', 'status');

        return view('admin.part-approvals.index', [
            'rows'   => $rows,
            'tab'    => $tab,
            'counts' => ['pending' => (int) ($counts['pending'] ?? 0), 'approved' => (int) ($counts['approved'] ?? 0), 'rejected' => (int) ($counts['rejected'] ?? 0)],
        ]);
    }

    // POST /admin/part-approvals/approve  (ids[] — one or many)
    public function approve(Request $request)
    {
        $this->guard();
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer']);

        $messages = [];
        foreach ($request->ids as $id) {
            $messages[] = PartApprovalService::approve((int) $id);
        }
        $n = count($messages);
        return back()->with('success', $n === 1 ? $messages[0] : "{$n} items approved and live.");
    }

    // POST /admin/part-approvals/{id}/reject
    public function reject(Request $request, int $id)
    {
        $this->guard();
        $request->validate(['reason' => 'required|string|max:255']);
        PartApprovalService::reject($id, $request->reason);
        return back()->with('success', 'Rejected. The item stays off the website.');
    }
}
