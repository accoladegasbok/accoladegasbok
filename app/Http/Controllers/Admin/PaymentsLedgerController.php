<?php
// FILE: app/Http/Controllers/Admin/PaymentsLedgerController.php
//
// One central place to see every payment ever recorded — across both
// Orders (Place Order/online checkout) and Invoices (Manual Invoice/
// Quick Receipt/Open Tab) — with proof links, confirmation status,
// and who confirmed it. Without this, payments were only visible by
// opening each order/invoice individually.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentsLedgerController extends Controller
{
    public function index(Request $request)
    {
        $status   = $request->get('status', '');
        $currency = $request->get('currency', ''); // '' = all, or NGN/USD/GHS
        $q        = trim($request->get('q', ''));
        $from     = $request->get('date_from');
        $to       = $request->get('date_to');

        // ── Order payments — real fixed currency, not hardcoded NGN ──
        $orderPayments = DB::table('order_payments as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->leftJoin('staff as s', 's.id', '=', 'op.confirmed_by_staff_id')
            ->select(
                'op.id', 'op.order_id as ref_id', 'o.order_ref as ref_no',
                'o.customer_name', 'o.customer_phone',
                'op.amount_local as amount', 'op.payment_method', 'op.proof_path',
                'op.status', 's.name as confirmed_by_name', 'op.confirmed_at',
                'op.notes', 'op.created_at',
                DB::raw("COALESCE(op.currency_code, o.currency_code, 'NGN') as currency_code")
            )
            ->selectRaw("'order' as source_type");

        // ── Invoice payments ─────────────────────────────────────────
        $invoicePayments = DB::table('invoice_payments as ip')
            ->join('invoices as i', 'i.id', '=', 'ip.invoice_id')
            ->leftJoin('staff as s', 's.id', '=', 'ip.confirmed_by_staff_id')
            ->select(
                'ip.id', 'ip.invoice_id as ref_id', 'i.invoice_no as ref_no',
                'i.customer_name', 'i.customer_phone',
                'ip.amount_local as amount', 'ip.payment_method', 'ip.proof_path',
                'ip.status', 's.name as confirmed_by_name', 'ip.confirmed_at',
                'ip.notes', 'ip.created_at', 'i.currency_code'
            )
            ->selectRaw("'invoice' as source_type");

        // Merge both via union, then apply filters/search on the combined set
        $all = $orderPayments->unionAll($invoicePayments);

        $query = DB::query()->fromSub($all, 'payments');

        if ($status) {
            $query->where('status', $status);
        }
        if ($currency) {
            $query->where('currency_code', $currency);
        }
        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('customer_name', 'like', "%{$q}%")
                  ->orWhere('customer_phone', 'like', "%{$q}%")
                  ->orWhere('ref_no', 'like', "%{$q}%");
            });
        }
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $payments = $query->orderByDesc('created_at')->paginate(40)->withQueryString();

        // ── Summary counts — per currency, never blended. This is
        // what lets the Naira tab and Dollar tab show genuinely
        // separate totals instead of one mixed number.
        $orderSummaryBase = DB::table('order_payments as op')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->selectRaw("op.status, op.amount_local as amount, COALESCE(op.currency_code, o.currency_code, 'NGN') as currency_code");
        $invoiceSummaryBase = DB::table('invoice_payments as ip')
            ->join('invoices as i', 'i.id', '=', 'ip.invoice_id')
            ->selectRaw('ip.status, ip.amount_local as amount, i.currency_code');

        $summaryRows = DB::query()->fromSub($orderSummaryBase->unionAll($invoiceSummaryBase), 's')->get();

        // Counts per currency (for the currency tabs)
        $currencyCounts = $summaryRows->groupBy('currency_code')->map->count();

        // Pending/Confirmed/Rejected counts, scoped to the currently
        // selected currency tab (so switching tabs shows that
        // currency's own breakdown, not the global one).
        $scopedRows = $currency ? $summaryRows->where('currency_code', $currency) : $summaryRows;
        $summary = $scopedRows->groupBy('status')->map->count();

        // Confirmed total for the currently selected currency — only
        // meaningful when one currency is selected (summing across
        // mixed currencies would be the exact bug we're fixing).
        $confirmedTotal = $currency
            ? $scopedRows->where('status', 'confirmed')->sum('amount')
            : null;

        return view('admin.payments.index', compact('payments', 'status', 'currency', 'summary', 'currencyCounts', 'confirmedTotal'));
    }
}
