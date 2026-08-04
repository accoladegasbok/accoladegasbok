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
    // FIXED: previously ignored all query parameters — the "Log Return"
    // button on an invoice's receipt page linked here with ?invoice_id=X
    // attached, but nothing read it, so staff landed on a blank form and
    // had to search for the same invoice all over again. Now prefills
    // the invoice + its items server-side when invoice_id is present.
    // =========================================================
    public function create(Request $request)
    {
        $prefillInvoice = null;
        $prefillItems   = collect();
        $prefillCurrency = 'NGN';

        if ($invoiceId = $request->get('invoice_id')) {
            $prefillInvoice = DB::table('invoices')
                ->where('id', $invoiceId)
                ->select('id', 'invoice_no', 'customer_name', 'customer_phone')
                ->first();

            if ($prefillInvoice) {
                // Same shaping as invoiceItems() — kept in sync so the
                // JS's option-building logic (data-part-id, data-label,
                // data-line-total) works identically whether items came
                // from this server-side prefill or the AJAX search path.
                $prefillItems = DB::table('invoice_items')
                    ->where('invoice_id', $invoiceId)
                    ->select('id', 'part_id', 'part_name', 'part_code', 'qty',
                             'unit_price_local', 'discount_amount_local')
                    ->get()
                    ->map(function ($item) {
                        $item->line_total_local = ($item->unit_price_local * $item->qty) - ($item->discount_amount_local ?? 0);
                        return $item;
                    });

                $prefillCurrency = DB::table('invoices')->where('id', $invoiceId)->value('currency_code') ?? 'NGN';
            }
        }

        return view('admin.returns.create', compact('prefillInvoice', 'prefillItems', 'prefillCurrency'));
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
        // FIXED: was only selecting id/part_id/part_name/part_code/qty —
        // no price data at all, so the return form had nothing to
        // autofill cost from even though the invoice already has it.
        $items = DB::table('invoice_items')
            ->where('invoice_id', $invoiceId)
            ->select('id', 'part_id', 'part_name', 'part_code', 'qty',
                     'unit_price_local', 'discount_amount_local')
            ->get()
            ->map(function ($item) {
                $item->line_total_local = ($item->unit_price_local * $item->qty) - ($item->discount_amount_local ?? 0);
                return $item;            });

        $currencyCode = DB::table('invoices')->where('id', $invoiceId)->value('currency_code') ?? 'NGN';

        return response()->json(['items' => $items, 'currency_code' => $currencyCode]);
    }

    // =========================================================
    // AJAX: GET /admin/returns/customer-credits?phone=...
    // Finds resolved returns for this customer that have a real
    // refund_amount_local and haven't already been applied to another
    // invoice — used by the manual invoice form's "Apply Return
    // Credit" search, so a returned part's value can go toward a
    // replacement purchase instead of (or alongside) a cash refund.
    // =========================================================
    public function searchCustomerCredits(Request $request)
    {
        $phone = preg_replace('/\D/', '', $request->get('phone', ''));
        if ($phone === '') return response()->json(['credits' => []]);

        $credits = DB::table('returns as r')
            ->join('parts_inventory as p', 'p.id', '=', 'r.part_id')
            ->leftJoin('invoices as i', 'i.id', '=', 'r.invoice_id')
            ->whereRaw("REPLACE(REPLACE(REPLACE(i.customer_phone, '+', ''), ' ', ''), '-', '') = ?", [$phone])
            ->where('r.status', 'resolved')
            ->where('r.refund_amount_local', '>', 0)
            ->whereNull('r.credit_applied_at')
            ->select('r.id', 'r.refund_amount_local', 'r.created_at', 'p.part_name', 'p.part_code', 'i.invoice_no', 'i.customer_name')
            ->orderByDesc('r.created_at')
            ->get();

        return response()->json(['credits' => $credits]);
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
            // FIXED: nothing here captured what the part/labour actually
            // cost on the original receipt — the returns table had no
            // place to save it at all, so even a correctly-autofilled
            // amount on the form had nowhere to go.
            'refund_amount_local' => 'nullable|numeric|min:0',
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
                'refund_amount_local' => $request->refund_amount_local,
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
