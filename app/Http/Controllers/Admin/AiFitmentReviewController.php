<?php
// FILE: app/Http/Controllers/Admin/AiFitmentReviewController.php
//
// AI fitment review. AI suggestions are never one-click. Staff tick a batch, press "Review selected", and
// then must look at each one again and tick it individually on a second screen before it becomes real
// fitment. Every confirm and reject is logged, and the report follows approved fitments through sales and
// returns for 30 days.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiFitmentService;
use App\Support\StaffRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class AiFitmentReviewController extends Controller
{
    private const MAX_BATCH = 25;

    private function guard(): void
    {
        abort_unless(StaffRole::isSupervisorOrAbove(), 403, 'Supervisor and above only.');
    }

    // GET /admin/ai-fitment
    public function index(Request $request)
    {
        $this->guard();

        $tab  = in_array($request->get('tab'), ['pending', 'vehicle', 'confirmed', 'rejected'], true) ? $request->get('tab') : 'pending';
        $conf = in_array($request->get('confidence'), ['high', 'medium', 'low'], true) ? $request->get('confidence') : '';
        $q    = trim((string) $request->get('q', ''));

        $query = DB::table('ai_suggestions as s')
            ->leftJoin('parts_inventory as p', 'p.id', '=', 's.part_id')
            ->select('s.*', 'p.part_code', 'p.part_name', 'p.brand as part_brand', 'p.model as part_model',
                     'p.year_from as part_year_from', 'p.year_to as part_year_to');

        match ($tab) {
            'vehicle'   => $query->where('s.review_status', 'pending')->whereNull('s.part_id'),
            'confirmed' => $query->where('s.review_status', 'confirmed'),
            'rejected'  => $query->where('s.review_status', 'rejected'),
            default     => $query->where('s.review_status', 'pending')->whereNotNull('s.part_id'),
        };
        if ($conf !== '') $query->where('s.confidence', $conf);
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('p.part_code', 'like', "%{$q}%")->orWhere('p.part_name', 'like', "%{$q}%")
                  ->orWhere('s.suggested_make', 'like', "%{$q}%")->orWhere('s.suggested_model', 'like', "%{$q}%");
            });
        }
        $query->orderByRaw("FIELD(s.confidence, 'high', 'medium', 'low')")->orderByDesc('s.created_at');

        $counts = [
            'pending'   => DB::table('ai_suggestions')->where('review_status', 'pending')->whereNotNull('part_id')->count(),
            'vehicle'   => DB::table('ai_suggestions')->where('review_status', 'pending')->whereNull('part_id')->count(),
            'confirmed' => DB::table('ai_suggestions')->where('review_status', 'confirmed')->count(),
            'rejected'  => DB::table('ai_suggestions')->where('review_status', 'rejected')->count(),
        ];

        return view('admin.ai-fitment.index', [
            'rows' => $query->paginate(40)->withQueryString(),
            'tab' => $tab, 'conf' => $conf, 'q' => $q, 'counts' => $counts, 'maxBatch' => self::MAX_BATCH,
        ]);
    }

    // POST /admin/ai-fitment/review — step 1: remember what was ticked, then go to the review screen
    public function review(Request $request)
    {
        $this->guard();
        $data = $request->validate(['ids' => 'required|array|min:1|max:' . self::MAX_BATCH, 'ids.*' => 'integer']);

        $ids = DB::table('ai_suggestions')->whereIn('id', $data['ids'])
            ->where('review_status', 'pending')->whereNotNull('part_id')->pluck('id')->all();

        if (!$ids) return redirect()->route('admin.ai-fitment.index')->with('error', 'Nothing to review — those suggestions are no longer pending, or are not tied to a part.');

        Session::put('ai_fitment_review', ['token' => Str::random(32), 'ids' => $ids]);
        return redirect()->route('admin.ai-fitment.review.show');
    }

    // GET /admin/ai-fitment/review — step 2: the second look
    public function showReview(AiFitmentService $fitment)
    {
        $this->guard();
        $state = Session::get('ai_fitment_review');
        if (!$state) return redirect()->route('admin.ai-fitment.index')->with('error', 'Tick the suggestions you want to review first.');

        $items = DB::table('ai_suggestions as s')
            ->join('parts_inventory as p', 'p.id', '=', 's.part_id')
            ->whereIn('s.id', $state['ids'])->where('s.review_status', 'pending')
            ->select('s.*', 'p.part_code', 'p.part_name', 'p.brand as part_brand', 'p.model as part_model',
                     'p.year_from as part_year_from', 'p.year_to as part_year_to', 'p.interchange_group_id')
            ->orderByRaw("FIELD(s.confidence, 'low', 'medium', 'high')")->get()
            ->map(function ($s) use ($fitment) { $s->warnings = $fitment->warnings($s); return $s; });

        if ($items->isEmpty()) {
            Session::forget('ai_fitment_review');
            return redirect()->route('admin.ai-fitment.index')->with('error', 'Those suggestions are no longer pending.');
        }
        return view('admin.ai-fitment.review', ['items' => $items, 'token' => $state['token']]);
    }

    // POST /admin/ai-fitment/confirm — step 3: every item must be individually ticked
    public function confirm(Request $request, AiFitmentService $fitment)
    {
        $this->guard();
        $state = Session::get('ai_fitment_review');
        abort_unless($state && hash_equals($state['token'], (string) $request->input('token')), 419, 'This review has expired — start again.');

        // The set to apply comes from the session (what was reviewed), never from the form.
        $suggestions = DB::table('ai_suggestions')->whereIn('id', $state['ids'])->where('review_status', 'pending')->get();
        $verified    = array_map('intval', array_keys((array) $request->input('verified', [])));

        if ($suggestions->isEmpty()) {
            Session::forget('ai_fitment_review');
            return redirect()->route('admin.ai-fitment.index')->with('error', 'Those suggestions were already handled — nothing was changed.');
        }

        foreach ($suggestions as $s) {
            if (!in_array((int) $s->id, $verified, true)) {
                return back()->with('error', 'Tick "I checked this one" on every suggestion — each one has to be looked at.');
            }
        }

        $staffId = Session::get('staff_id'); $staffName = Session::get('staff_name');
        $note    = trim((string) $request->input('note', '')) ?: null;

        try {
            DB::transaction(function () use ($suggestions, $fitment, $staffId, $staffName, $note) {
                $batchId = DB::table('ai_fitment_batches')->insertGetId([
                    'action' => 'confirm', 'staff_id' => $staffId, 'staff_name' => $staffName,
                    'item_count' => $suggestions->count(), 'note' => $note, 'created_at' => now(),
                ]);
                foreach ($suggestions as $s) {
                    $r = $fitment->apply($s, $staffId);
                    DB::table('ai_suggestions')->where('id', $s->id)->update([
                        'group_id' => $r['group_id'], 'review_status' => 'confirmed', 'reviewed_by_staff_id' => $staffId,
                        'reviewed_at' => now(), 'batch_id' => $batchId, 'review_note' => $note, 'updated_at' => now(),
                    ]);
                    $this->log($batchId, $s, 'confirm', $staffId, $staffName, $r['group_id'], $r['vehicle_row_id'], $r['result']);
                }
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Nothing was saved — ' . $e->getMessage());
        }

        Session::forget('ai_fitment_review');
        return redirect()->route('admin.ai-fitment.index', ['tab' => 'confirmed'])
            ->with('success', $suggestions->count() . ' fitment suggestion(s) approved and logged.');
    }

    // POST /admin/ai-fitment/reject — a reason is required
    public function reject(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'ids' => 'required|array|min:1|max:200', 'ids.*' => 'integer',
            'reason' => 'required|string|min:4|max:500',
        ], ['reason.required' => 'Say why you are rejecting them (a few words is enough).']);

        $suggestions = DB::table('ai_suggestions')->whereIn('id', $data['ids'])->where('review_status', 'pending')->get();
        if ($suggestions->isEmpty()) return back()->with('error', 'Nothing to reject — they are no longer pending.');

        $staffId = Session::get('staff_id'); $staffName = Session::get('staff_name');
        DB::transaction(function () use ($suggestions, $data, $staffId, $staffName) {
            $batchId = DB::table('ai_fitment_batches')->insertGetId([
                'action' => 'reject', 'staff_id' => $staffId, 'staff_name' => $staffName,
                'item_count' => $suggestions->count(), 'note' => $data['reason'], 'created_at' => now(),
            ]);
            foreach ($suggestions as $s) {
                DB::table('ai_suggestions')->where('id', $s->id)->update([
                    'review_status' => 'rejected', 'reviewed_by_staff_id' => $staffId, 'reviewed_at' => now(),
                    'batch_id' => $batchId, 'review_note' => $data['reason'], 'updated_at' => now(),
                ]);
                $this->log($batchId, $s, 'reject', $staffId, $staffName, null, null, 'Rejected: ' . $data['reason']);
            }
        });

        return back()->with('success', $suggestions->count() . ' suggestion(s) rejected and logged.');
    }

    // GET /admin/ai-fitment/report?days=90
    public function report(Request $request)
    {
        $this->guard();
        $days  = in_array((int) $request->get('days'), [30, 90, 180, 365], true) ? (int) $request->get('days') : 90;
        $since = now()->subDays($days);

        // ── Acceptance: of the suggestions staff decided on, how many were approved ──
        $byConf = DB::table('ai_suggestions')->where('created_at', '>=', $since)
            ->select('confidence', 'review_status', DB::raw('COUNT(*) as n'))->groupBy('confidence', 'review_status')->get()
            ->groupBy('confidence')->map(fn ($g) => $g->pluck('n', 'review_status'));

        // ── Outcome: approved fitments → parts of that group sold within 30 days → returned within 30 days of the sale ──
        $confirmed = DB::table('ai_suggestions')->where('review_status', 'confirmed')->whereNotNull('group_id')
            ->where('reviewed_at', '>=', $since)->get();
        $groups = $confirmed->groupBy('group_id')->map(fn ($g) => $g->min('reviewed_at'));

        $sales = collect();
        if ($groups->isNotEmpty()) {
            $gids = $groups->keys()->all();
            $inv = DB::table('invoice_items as ii')->join('invoices as i', 'i.id', '=', 'ii.invoice_id')
                ->join('parts_inventory as p', 'p.id', '=', 'ii.part_id')->whereIn('p.interchange_group_id', $gids)
                ->select('ii.id', 'p.interchange_group_id as gid', 'i.created_at as sold_at', DB::raw("'invoice' as kind"))->get();
            $ord = DB::table('order_items as oi')->join('orders as o', 'o.id', '=', 'oi.order_id')
                ->join('parts_inventory as p', 'p.id', '=', 'oi.part_id')->whereIn('p.interchange_group_id', $gids)
                ->select('oi.id', 'p.interchange_group_id as gid', 'o.created_at as sold_at', DB::raw("'order' as kind"))->get();

            // keep only sales in the 30 days AFTER approval
            $sales = $inv->concat($ord)->filter(function ($s) use ($groups) {
                $from = \Carbon\Carbon::parse($groups[$s->gid]); $at = \Carbon\Carbon::parse($s->sold_at);
                return $at->gte($from) && $at->lte($from->copy()->addDays(30));
            })->values();
        }

        $returns = collect();
        if ($sales->isNotEmpty()) {
            $returns = DB::table('returns')->where('return_type', 'customer')
                ->where(function ($w) use ($sales) {
                    $w->whereIn('invoice_item_id', $sales->where('kind', 'invoice')->pluck('id')->all() ?: [0])
                      ->orWhereIn('order_item_id', $sales->where('kind', 'order')->pluck('id')->all() ?: [0]);
                })->get();
        }
        $returnBySale = [];
        foreach ($returns as $r) {
            $key = $r->invoice_item_id ? 'invoice-' . $r->invoice_item_id : 'order-' . $r->order_item_id;
            $returnBySale[$key][] = $r;
        }

        $perGroup = $groups->map(function ($approvedAt, $gid) use ($sales, $returnBySale) {
            $mine = $sales->where('gid', $gid);
            $ret = 0; $fit = 0;
            foreach ($mine as $s) {
                foreach ($returnBySale[$s->kind . '-' . $s->id] ?? [] as $r) {
                    $ret++;
                    if (stripos((string) $r->reason, 'fit') !== false) $fit++;
                }
            }
            return (object) [
                'group_id' => $gid, 'approved_at' => $approvedAt, 'sold' => $mine->count(), 'returned' => $ret, 'fit_returned' => $fit,
                'group' => DB::table('part_interchange_groups')->where('id', $gid)->first(),
            ];
        })->sortByDesc('sold')->values();

        // Everything else, for comparison: all customer returns vs all items sold in the same window
        $allSold = DB::table('invoice_items as ii')->join('invoices as i', 'i.id', '=', 'ii.invoice_id')->where('i.created_at', '>=', $since)->count()
                 + DB::table('order_items as oi')->join('orders as o', 'o.id', '=', 'oi.order_id')->where('o.created_at', '>=', $since)->count();
        $allReturned = DB::table('returns')->where('return_type', 'customer')->where('created_at', '>=', $since)->count();

        return view('admin.ai-fitment.report', [
            'days' => $days, 'byConf' => $byConf, 'perGroup' => $perGroup,
            'totals' => ['sold' => $perGroup->sum('sold'), 'returned' => $perGroup->sum('returned'), 'fit' => $perGroup->sum('fit_returned')],
            'baseline' => ['sold' => $allSold, 'returned' => $allReturned],
            'batches' => DB::table('ai_fitment_batches')->where('created_at', '>=', $since)->orderByDesc('id')->limit(15)->get(),
        ]);
    }

    private function log(int $batchId, object $s, string $action, ?int $staffId, ?string $staffName, ?int $groupId, ?int $vehicleRowId, string $result): void
    {
        DB::table('ai_fitment_log')->insert([
            'batch_id' => $batchId, 'suggestion_id' => $s->id, 'action' => $action, 'part_id' => $s->part_id,
            'group_id' => $groupId, 'vehicle_row_id' => $vehicleRowId,
            'make' => $s->suggested_make, 'model' => $s->suggested_model,
            'year_from' => $s->suggested_year_from, 'year_to' => $s->suggested_year_to,
            'confidence' => $s->confidence, 'reason' => $s->reason, 'result' => Str::limit($result, 118, ''),
            'staff_id' => $staffId, 'staff_name' => $staffName, 'created_at' => now(),
        ]);
    }
}
