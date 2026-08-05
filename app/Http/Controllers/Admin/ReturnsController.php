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
    // Prefills a sale when arriving via ?sale_type=invoice|order&sale_id=X
    // (the "Log Return" link on a receipt/order page). Kept backward
    // compatible with the older ?invoice_id=X form too.
    // =========================================================
    public function create(Request $request)
    {
        $saleType = $request->get('sale_type');
        $saleId   = $request->get('sale_id') ?? $request->get('invoice_id');
        if (!$saleType && $request->get('invoice_id')) $saleType = 'invoice';

        $prefillSale  = null;
        $prefillItems = collect();
        $prefillCurrency = 'NGN';

        if ($saleType && $saleId) {
            $detail = $this->loadSaleDetail($saleType, (int) $saleId);
            if ($detail) {
                $prefillSale     = $detail['sale'];
                $prefillItems    = $detail['items'];
                $prefillCurrency = $detail['currency_code'];
            }
        }

        return view('admin.returns.create', compact('saleType', 'prefillSale', 'prefillItems', 'prefillCurrency'));
    }

    // =========================================================
    // AJAX: GET /admin/returns/search-invoices?q=...
    // FIXED: previously only searched `invoices` — a sale placed
    // through Place Order (orders/order_items) was completely
    // invisible here. Now searches both and tags each result with
    // its type so the rest of the flow knows which table to read from.
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
            ->whereNull('deleted_at')
            ->select('id', 'invoice_no as ref', 'customer_name', 'customer_phone', 'created_at')
            ->selectRaw("'invoice' as sale_type")
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $orders = DB::table('orders')
            ->where(function ($query) use ($q) {
                $query->where('order_ref', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%")
                    ->orWhere('customer_phone', 'like', "%{$q}%");
            })
            ->whereNull('deleted_at')
            // Only sales that actually happened — no point offering a
            // return against an order still awaiting payment/fulfillment.
            ->whereIn('order_status', ['completed'])
            ->select('id', 'order_ref as ref', 'customer_name', 'customer_phone', 'created_at')
            ->selectRaw("'order' as sale_type")
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $results = $invoices->concat($orders)
            ->sortByDesc('created_at')
            ->take(15)
            ->values();

        return response()->json(['invoices' => $results]);
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
    // AJAX: GET /admin/returns/invoice-items?sale_type=invoice|order&sale_id=X
    // FIXED: previously only read invoice_items (?invoice_id=X). Now
    // branches on sale_type so it works identically for an order's
    // order_items — same response shape either way, so the existing
    // frontend rendering logic (and the prefill path in create()) don't
    // need to know or care which table the data actually came from.
    // Old ?invoice_id=X calls still work (defaults sale_type to 'invoice').
    // =========================================================
    public function invoiceItems(Request $request)
    {
        $saleType = $request->get('sale_type', 'invoice');
        $saleId   = (int) ($request->get('sale_id') ?? $request->get('invoice_id'));

        $detail = $this->loadSaleDetail($saleType, $saleId);
        if (!$detail) {
            return response()->json(['items' => [], 'currency_code' => 'NGN']);
        }

        return response()->json([
            'items'         => $detail['items'],
            'currency_code' => $detail['currency_code'],
        ]);
    }

    // =========================================================
    // Shared sale-loading logic — used by create() (server-side
    // prefill) and invoiceItems() (AJAX) so both paths always return
    // items in the exact same shape regardless of source table.
    // =========================================================
    private function loadSaleDetail(string $saleType, int $saleId): ?array
    {
        if ($saleType === 'order') {
            $order = DB::table('orders')->where('id', $saleId)->first();
            if (!$order) return null;

            $items = DB::table('order_items')
                ->where('order_id', $saleId)
                ->select('id', 'part_id', 'part_name', 'part_code', 'quantity',
                         'unit_price_local', 'subtotal_local')
                ->get()
                ->map(function ($item) {
                    // order_items already stores a real subtotal_local
                    // (unlike invoice_items, which needs qty*price-discount
                    // computed here) — use it directly.
                    $item->qty              = $item->quantity;
                    $item->line_total_local = $item->subtotal_local;
                    return $item;
                });

            return [
                'sale'          => (object) [
                    'id' => $order->id, 'ref' => $order->order_ref,
                    'customer_name' => $order->customer_name, 'customer_phone' => $order->customer_phone,
                    'sale_type' => 'order',
                ],
                'items'         => $items,
                'currency_code' => $order->currency_code ?? 'NGN',
            ];
        }

        // Default: invoice
        $invoice = DB::table('invoices')->where('id', $saleId)->first();
        if (!$invoice) return null;

        $items = DB::table('invoice_items')
            ->where('invoice_id', $saleId)
            ->select('id', 'part_id', 'part_name', 'part_code', 'qty',
                     'unit_price_local', 'discount_amount_local')
            ->get()
            ->map(function ($item) {
                $item->line_total_local = ($item->unit_price_local * $item->qty) - ($item->discount_amount_local ?? 0);
                return $item;
            });

        return [
            'sale'          => (object) [
                'id' => $invoice->id, 'ref' => $invoice->invoice_no,
                'customer_name' => $invoice->customer_name, 'customer_phone' => $invoice->customer_phone,
                'sale_type' => 'invoice',
            ],
            'items'         => $items,
            'currency_code' => $invoice->currency_code ?? 'NGN',
        ];
    }

    // =========================================================
    // AJAX: GET /admin/returns/customer-credits?phone=...
    // FIXED: previously joined ONLY to `invoices` to resolve the
    // customer's phone number — a credit from an order-sourced return
    // (invoice_id null) could never match, since the join produced a
    // null phone for those rows. Now coalesces phone from whichever
    // sale type the return actually came from.
    // =========================================================
    public function searchCustomerCredits(Request $request)
    {
        $phone = preg_replace('/\D/', '', $request->get('phone', ''));
        if ($phone === '') return response()->json(['credits' => []]);

        $credits = DB::table('returns as r')
            ->join('parts_inventory as p', 'p.id', '=', 'r.part_id')
            ->leftJoin('invoices as i', 'i.id', '=', 'r.invoice_id')
            ->leftJoin('orders as o', 'o.id', '=', 'r.order_id')
            ->whereRaw("REPLACE(REPLACE(REPLACE(COALESCE(i.customer_phone, o.customer_phone, ''), '+', ''), ' ', ''), '-', '') = ?", [$phone])
            ->where('r.status', 'resolved')
            ->where('r.refund_amount_local', '>', 0)
            ->where('r.refund_method', 'store_credit')
            ->whereNull('r.credit_applied_at')
            ->select(
                'r.id', 'r.refund_amount_local', 'r.created_at', 'p.part_name', 'p.part_code',
                DB::raw('COALESCE(i.invoice_no, o.order_ref) as invoice_no'),
                DB::raw('COALESCE(i.customer_name, o.customer_name) as customer_name')
            )
            ->orderByDesc('r.created_at')
            ->get();

        return response()->json(['credits' => $credits]);
    }

    // =========================================================
    // POST /admin/returns — log new return(s), puts each part on Hold
    // FIXED: now accepts MULTIPLE items in one submission (item C/I —
    // "1 or 2 items on a particular invoice" without splitting into
    // separate form submissions), and records whichever sale type
    // (invoice or order) the return actually came from.
    // Backward compatible: a single part_id/invoice_item_id submission
    // (old form shape) still works, treated as a 1-item array.
    // =========================================================
    public function store(Request $request)
    {
        $request->validate([
            'return_type'   => 'required|in:customer,internal',
            'reason'        => 'required|string|max:1000',
            'sale_type'     => 'nullable|in:invoice,order',
            'sale_id'       => 'nullable|integer',
            // New multi-item shape
            'items'                 => 'nullable|array|min:1',
            'items.*.part_id'       => 'required_with:items|exists:parts_inventory,id',
            'items.*.sale_item_id'  => 'nullable|integer',
            'items.*.refund_amount_local' => 'nullable|numeric|min:0',
            // Legacy single-item shape — still accepted
            'part_id'              => 'nullable|exists:parts_inventory,id',
            'invoice_item_id'      => 'nullable|exists:invoice_items,id',
            'refund_amount_local'  => 'nullable|numeric|min:0',
        ]);

        // Normalize to one shape: an array of items to log.
        $items = $request->input('items');
        if (empty($items)) {
            if (!$request->part_id) {
                return back()->with('error', 'Select at least one part to return.')->withInput();
            }
            $items = [[
                'part_id'              => $request->part_id,
                'sale_item_id'         => $request->invoice_item_id,
                'refund_amount_local'  => $request->refund_amount_local,
            ]];
        }

        $saleType = $request->sale_type ?? ($request->invoice_id ? 'invoice' : null);
        $saleId   = $request->sale_id ?? $request->invoice_id;

        $createdIds = [];
        $partLabels = [];

        DB::beginTransaction();
        try {
            foreach ($items as $row) {
                $part = DB::table('parts_inventory')->where('id', $row['part_id'])->first();
                if (!$part) continue;

                $insertData = [
                    'part_id'             => $row['part_id'],
                    'return_type'         => $request->return_type,
                    'reason'              => $request->reason,
                    'refund_amount_local' => $row['refund_amount_local'] ?? null,
                    'status'              => 'pending_inspection',
                    'created_by_staff_id' => Session::get('staff_id'),
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];

                if ($saleType === 'order' && $saleId) {
                    $insertData['order_id']      = $saleId;
                    $insertData['order_item_id'] = $row['sale_item_id'] ?? null;
                } elseif ($saleId) {
                    $insertData['invoice_id']      = $saleId;
                    $insertData['invoice_item_id'] = $row['sale_item_id'] ?? null;
                }

                $createdIds[] = DB::table('returns')->insertGetId($insertData);
                $partLabels[] = $part->part_code;

                // Part goes on Hold immediately — pulled from sale until
                // inspection resolves where it ends up.
                DB::table('parts_inventory')->where('id', $row['part_id'])->update([
                    'status'     => 'Hold',
                    'updated_at' => now(),
                ]);
            }

            if (empty($createdIds)) {
                DB::rollBack();
                return back()->with('error', 'No valid parts were found to return.')->withInput();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Could not log return: ' . $e->getMessage())->withInput();
        }

        $message = count($createdIds) > 1
            ? count($createdIds) . ' returns logged (' . implode(', ', $partLabels) . ') — parts placed on Hold pending inspection.'
            : "Return logged for {$partLabels[0]} — part placed on Hold pending inspection.";

        // Multiple items were logged as separate return rows — send
        // staff to the list (filtered to pending) rather than picking
        // one arbitrarily to redirect to.
        if (count($createdIds) > 1) {
            return redirect()->route('admin.returns.index')->with('success', $message);
        }

        return redirect()->route('admin.returns.show', $createdIds[0])->with('success', $message);
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

        // FIXED: previously only ever looked up an invoice — an
        // order-sourced return would silently show no linked-sale info
        // at all. Now resolves whichever type this return came from.
        $invoice = null;
        if ($return->invoice_id) {
            $invoice = DB::table('invoices')->where('id', $return->invoice_id)->first();
        } elseif ($return->order_id) {
            $invoice = DB::table('orders')->where('id', $return->order_id)->first();
            if ($invoice) {
                // Normalize field names so show.blade.php can keep using
                // $invoice->invoice_no / $invoice->customer_name either way.
                $invoice->invoice_no = $invoice->order_ref;
            }
        }

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
    // NEW: refund_method (cash/transfer/store_credit) now captured for
    // customer returns — previously had nowhere to be recorded at all.
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
            // Only meaningful (and required) for customer returns with
            // a real refund amount — an internal reject or a $0 return
            // has nothing to settle.
            'refund_method'    => $return->return_type === 'customer' && $return->refund_amount_local > 0
                                    ? 'required|in:cash,transfer,store_credit'
                                    : 'nullable|in:cash,transfer,store_credit',
        ]);

        $statusMap = [
            'restock_good' => 'Available',
            'core'         => 'Core',
            'scrapped'     => 'Scrapped',
        ];

        DB::beginTransaction();
        try {
            $part = DB::table('parts_inventory')->where('id', $return->part_id)->first();

            $partUpdate = [
                'status'     => $statusMap[$request->resolution],
                'updated_at' => now(),
            ];

            // FIXED: this used to only flip status to 'Available' —
            // stock_qty was never touched, so a returned part showed
            // as "Available" but with whatever stock_qty it already
            // had (often 0, if this was the last/only unit sold). No
            // qty field exists on the returns table itself, so this
            // assumes one physical unit per return record, matching
            // how returns are logged (one row per item selected).
            if ($request->resolution === 'restock_good') {
                $partUpdate['stock_qty'] = ($part->stock_qty ?? 0) + 1;
            }

            if ($request->resolution !== 'scrapped' && $request->storage_shelf_id) {
                $shelf = DB::table('storage_shelves')->where('id', $request->storage_shelf_id)->first();
                $partUpdate['storage_shelf_id'] = $request->storage_shelf_id;
                $partUpdate['bin_location']     = $shelf->full_bin_code ?? null;
            }

            DB::table('parts_inventory')->where('id', $return->part_id)->update($partUpdate);

            DB::table('returns')->where('id', $id)->update([
                'status'               => 'resolved',
                'resolution'           => $request->resolution,
                'refund_method'        => $request->refund_method,
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

        $refundNote = $request->refund_method
            ? ' Refund method: ' . ucfirst(str_replace('_', ' ', $request->refund_method)) . '.'
            : '';

        return redirect()->route('admin.returns.index')
            ->with('success', 'Return resolved — part status updated to ' . $statusMap[$request->resolution] . '.' . $refundNote);
    }
}
