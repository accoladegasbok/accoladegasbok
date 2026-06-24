<?php
// FILE: app/Http/Controllers/Admin/ReturnsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ReturnsController extends Controller
{
    // =========================================================
    // GET /admin/returns — list, filterable by status
    // =========================================================
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending_inspection');

        $query = DB::table('returns as r')
            ->join('parts_inventory as p', 'p.id', '=', 'r.part_id')
            ->select('r.*', 'p.part_name', 'p.part_code', 'p.brand', 'p.model', 'p.location')
            ->orderByDesc('r.created_at');

        if ($status !== 'all') {
            $query->where('r.status', $status);
        }

        $returns = $query->paginate(25)->withQueryString();

        $counts = [
            'pending_inspection' => DB::table('returns')->where('status', 'pending_inspection')->count(),
            'resolved'           => DB::table('returns')->where('status', 'resolved')->count(),
        ];

        return view('admin.returns.index', compact('returns', 'status', 'counts'));
    }

    // =========================================================
    // GET /admin/returns/create
    // =========================================================
    public function create()
    {
        return view('admin.returns.create');
    }

    // =========================================================
    // AJAX: GET /admin/returns/search-parts?q=...
    // =========================================================
    public function searchParts(Request $request)
    {
        $q = trim($request->get('q', ''));
        if ($q === '') return response()->json(['parts' => []]);

        $parts = DB::table('parts_inventory')
            ->where(function ($query) use ($q) {
                $query->where('part_code', 'like', "%{$q}%")
                    ->orWhere('part_name', 'like', "%{$q}%")
                    ->orWhere('donor_vin', 'like', "%{$q}%");
            })
            ->whereIn('status', ['Available', 'Reserved', 'Sold'])
            ->select('id', 'part_code', 'part_name', 'brand', 'model', 'location', 'status')
            ->limit(15)
            ->get();

        return response()->json(['parts' => $parts]);
    }

    // =========================================================
    // AJAX: GET /admin/returns/search-invoices?q=...
    // =========================================================
    public function searchInvoices(Request $request)
    {
        $q = trim($request->get('q', ''));
        if ($q === '') return response()->json(['invoices' => []]);

        $invoices = DB::table('invoices')
            ->where(function ($query) use ($q) {
                $query->where('invoice_no', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%")
                    ->orWhere('customer_phone', 'like', "%{$q}%");
            })
            ->select('id', 'invoice_no', 'customer_name', 'customer_phone', 'created_at')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return response()->json(['invoices' => $invoices]);
    }

    // AJAX: GET /admin/returns/invoice-items?invoice_id=X
    public function invoiceItems(Request $request)
    {
        $invoiceId = (int) $request->get('invoice_id');
        $items = DB::table('invoice_items')
            ->where('invoice_id', $invoiceId)
            ->select('id', 'part_id', 'part_name', 'part_code', 'qty')
            ->get();

        return response()->json(['items' => $items]);
    }

    // =========================================================
    // POST /admin/returns — log a new return, puts the part on Hold
    // =========================================================
    public function store(Request $request)
    {
        $request->validate([
            'part_id'          => 'required|exists:parts_inventory,id',
            'return_type'      => 'required|in:customer,internal',
            'reason'           => 'required|string|max:1000',
            'invoice_id'       => 'nullable|exists:invoices,id',
            'invoice_item_id'  => 'nullable|exists:invoice_items,id',
        ]);

        $part = DB::table('parts_inventory')->where('id', $request->part_id)->first();

        DB::beginTransaction();
        try {
            $returnId = DB::table('returns')->insertGetId([
                'part_id'             => $request->part_id,
                'invoice_id'          => $request->invoice_id,
                'invoice_item_id'     => $request->invoice_item_id,
                'return_type'         => $request->return_type,
                'reason'              => $request->reason,
                'status'              => 'pending_inspection',
                'created_by_staff_id' => Session::get('staff_id'),
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            // Part goes on Hold immediately — pulled from sale/availability
            // until inspection resolves where it ends up.
            DB::table('parts_inventory')->where('id', $request->part_id)->update([
                'status'     => 'Hold',
                'updated_at' => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Could not log return: ' . $e->getMessage())->withInput();
        }

        return redirect()->route('admin.returns.show', $returnId)
            ->with('success', "Return logged for {$part->part_code} — part placed on Hold pending inspection.");
    }

    // =========================================================
    // GET /admin/returns/{id} — detail + resolution form
    // =========================================================
    public function show(int $id)
    {
        $return = DB::table('returns as r')
            ->join('parts_inventory as p', 'p.id', '=', 'r.part_id')
            ->where('r.id', $id)
            ->select('r.*', 'p.part_name', 'p.part_code', 'p.brand', 'p.model', 'p.location', 'p.condition_grade')
            ->first();
        abort_if(!$return, 404);

        $invoice = $return->invoice_id
            ? DB::table('invoices')->where('id', $return->invoice_id)->first()
            : null;

        $createdBy  = $return->created_by_staff_id
            ? DB::table('staff')->where('id', $return->created_by_staff_id)->value('name')
            : null;
        $resolvedBy = $return->resolved_by_staff_id
            ? DB::table('staff')->where('id', $return->resolved_by_staff_id)->value('name')
            : null;

        return view('admin.returns.show', compact('return', 'invoice', 'createdBy', 'resolvedBy'));
    }

    // =========================================================
    // POST /admin/returns/{id}/resolve
    // Resolution moves the part to: Available (good, restocked with a
    // bin), Core (defective core for rebuild, with a bin), or Scrapped
    // (disposed — no bin needed).
    // =========================================================
    public function resolve(Request $request, int $id)
    {
        $return = DB::table('returns')->where('id', $id)->first();
        abort_if(!$return, 404);

        if ($return->status === 'resolved') {
            return back()->with('error', 'This return has already been resolved.');
        }

        $request->validate([
            'resolution'       => 'required|in:restock_good,core,scrapped',
            'storage_shelf_id' => 'nullable|exists:storage_shelves,id',
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        $statusMap = [
            'restock_good' => 'Available',
            'core'         => 'Core',
            'scrapped'     => 'Scrapped',
        ];

        DB::beginTransaction();
        try {
            $partUpdate = [
                'status'     => $statusMap[$request->resolution],
                'updated_at' => now(),
            ];
            if ($request->resolution !== 'scrapped' && $request->storage_shelf_id) {
                $shelf = DB::table('storage_shelves')->where('id', $request->storage_shelf_id)->first();
                $partUpdate['storage_shelf_id'] = $request->storage_shelf_id;
                $partUpdate['bin_location']     = $shelf->full_bin_code ?? null;
            }

            DB::table('parts_inventory')->where('id', $return->part_id)->update($partUpdate);

            DB::table('returns')->where('id', $id)->update([
                'status'               => 'resolved',
                'resolution'           => $request->resolution,
                'new_storage_shelf_id' => $request->storage_shelf_id,
                'resolution_notes'     => $request->resolution_notes,
                'resolved_by_staff_id' => Session::get('staff_id'),
                'resolved_at'          => now(),
                'updated_at'           => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Could not resolve return: ' . $e->getMessage());
        }

        return redirect()->route('admin.returns.index')
            ->with('success', 'Return resolved — part status updated to ' . $statusMap[$request->resolution] . '.');
    }
}
