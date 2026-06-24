<?php
// UPDATED: Fixed-currency reporting. Revenue is now reported PER CURRENCY
// separately (e.g. "₦4,500,000 in Nigeria" and "$3,200 in Texas" shown as
// distinct totals) — no blended/converted grand total across currencies,
// per business decision to avoid FX-loss distortion in reporting.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialReportController extends Controller
{
    // =========================================================
    // GET /admin/reports/financial
    // =========================================================
    public function index(Request $request)
    {
        $period = $request->get('period', 'monthly'); // daily | weekly | monthly
        [$from, $to] = $this->resolveDateRange($request, $period);

        // ── Confirmed online orders within range ──────────────────
        $orders = DB::table('orders')
            ->where('payment_status', 'confirmed')
            ->where('order_status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->get(['id', 'order_ref', 'total_amount_usd', 'currency_code', 'customer_country', 'confirmed_by', 'created_at']);

        // ── Manual / walk-in invoices within range ─────────────────
        $invoices = DB::table('invoices')
            ->whereBetween('created_at', [$from, $to])
            ->get(['id', 'invoice_no', 'subtotal_local', 'subtotal_usd', 'currency_code', 'location', 'created_by', 'created_at']);

        // Normalize both sources into one shape: amount + currency_code
        $allTransactions = $orders->map(fn($o) => (object)[
                'source'   => 'order',
                'amount'   => $o->total_amount_usd, // orders predate fixed-currency change; treat as their stored currency
                'currency' => $o->currency_code ?? 'USD',
                'location' => $o->customer_country . ' (Online)',
                'staff'    => $o->confirmed_by,
                'date'     => $o->created_at,
            ])
            ->concat($invoices->map(fn($i) => (object)[
                'source'   => 'invoice',
                'amount'   => $i->subtotal_local ?? $i->subtotal_usd,
                'currency' => $i->currency_code ?? 'USD',
                'location' => $i->location,
                'staff'    => $i->created_by,
                'date'     => $i->created_at,
            ]));

        $totalTransactions = $allTransactions->count();

        // ── Revenue PER CURRENCY — no blending across currencies ───
        $revenueByCurrency = $allTransactions->groupBy('currency')
            ->map(fn($group, $code) => (object)[
                'currency_code' => $code,
                'symbol'        => InvoiceController::currencyMeta($code)['symbol'],
                'total'         => $group->sum('amount'),
                'count'         => $group->count(),
            ])
            ->values();

        // ── Revenue by location, WITHIN each currency (no cross-currency sum) ──
        $byLocation = $allTransactions->groupBy(fn($t) => $t->location . '|' . $t->currency)
            ->map(function ($group) {
                $first = $group->first();
                return (object)[
                    'location'      => $first->location,
                    'currency_code' => $first->currency,
                    'symbol'        => InvoiceController::currencyMeta($first->currency)['symbol'],
                    'revenue'       => $group->sum('amount'),
                    'count'         => $group->count(),
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        // ── Revenue by staff, WITHIN each currency (no cross-currency sum) ──
        $byStaff = $allTransactions->groupBy(fn($t) => ($t->staff ?: 'Unattributed') . '|' . $t->currency)
            ->map(function ($group) {
                $first = $group->first();
                return (object)[
                    'staff'         => $first->staff ?: 'Unattributed',
                    'currency_code' => $first->currency,
                    'symbol'        => InvoiceController::currencyMeta($first->currency)['symbol'],
                    'revenue'       => $group->sum('amount'),
                    'count'         => $group->count(),
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        // ── Revenue trend, grouped by day AND currency (separate lines
        // per currency on the chart — never summed together) ──
        $trend = $allTransactions->groupBy(fn($t) => Carbon::parse($t->date)->format('Y-m-d') . '|' . $t->currency)
            ->map(function ($group) {
                $first = $group->first();
                return (object)[
                    'date'          => Carbon::parse($first->date)->format('Y-m-d'),
                    'currency_code' => $first->currency,
                    'revenue'       => $group->sum('amount'),
                ];
            })
            ->sortBy('date')
            ->values();

        return view('admin.reports.financial', [
            'period'             => $period,
            'from'               => $from,
            'to'                 => $to,
            'revenueByCurrency'  => $revenueByCurrency, // e.g. [{NGN, ₦4.5M, 12 txns}, {USD, $3.2K, 8 txns}]
            'totalTransactions'  => $totalTransactions,
            'byLocation'         => $byLocation,
            'byStaff'            => $byStaff,
            'trend'              => $trend,
        ]);
    }

    private function resolveDateRange(Request $request, string $period): array
    {
        if ($request->get('from') && $request->get('to')) {
            return [
                Carbon::parse($request->get('from'))->startOfDay(),
                Carbon::parse($request->get('to'))->endOfDay(),
            ];
        }

        return match ($period) {
            'daily'   => [now()->startOfDay(), now()->endOfDay()],
            'weekly'  => [now()->startOfWeek(), now()->endOfWeek()],
            default   => [now()->startOfMonth(), now()->endOfMonth()], // monthly
        };
    }
}
