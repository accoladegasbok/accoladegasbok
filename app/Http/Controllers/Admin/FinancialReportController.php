<?php

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
            ->get(['id', 'order_ref', 'total_amount_usd', 'customer_country', 'confirmed_by', 'created_at']);

        // ── Manual / walk-in invoices within range ─────────────────
        $invoices = DB::table('invoices')
            ->whereBetween('created_at', [$from, $to])
            ->get(['id', 'invoice_no', 'subtotal_usd', 'location', 'created_by', 'created_at']);

        $totalRevenue = $orders->sum('total_amount_usd') + $invoices->sum('subtotal_usd');
        $totalTransactions = $orders->count() + $invoices->count();

        // ── Revenue by location ─────────────────────────────────
        // Orders use customer_country as a rough location proxy;
        // invoices have an explicit warehouse location.
        $byLocation = collect();

        foreach ($orders->groupBy('customer_country') as $country => $group) {
            $byLocation->push((object)[
                'location' => $country . ' (Online)',
                'revenue'  => $group->sum('total_amount_usd'),
                'count'    => $group->count(),
            ]);
        }
        foreach ($invoices->groupBy('location') as $loc => $group) {
            $byLocation->push((object)[
                'location' => $loc,
                'revenue'  => $group->sum('subtotal_usd'),
                'count'    => $group->count(),
            ]);
        }
        $byLocation = $byLocation->sortByDesc('revenue')->values();

        // ── Revenue by staff ─────────────────────────────────────
        // Orders attribute to confirmed_by (the staff who confirmed payment);
        // invoices attribute to created_by (the staff who wrote the invoice).
        $byStaff = collect();

        foreach ($orders->groupBy(fn($o) => $o->confirmed_by ?: 'Unattributed') as $staff => $group) {
            $byStaff->push((object)[
                'staff'   => $staff,
                'revenue' => $group->sum('total_amount_usd'),
                'count'   => $group->count(),
                'source'  => 'Online Orders',
            ]);
        }
        foreach ($invoices->groupBy(fn($i) => $i->created_by ?: 'Unattributed') as $staff => $group) {
            $byStaff->push((object)[
                'staff'   => $staff,
                'revenue' => $group->sum('subtotal_usd'),
                'count'   => $group->count(),
                'source'  => 'In-Store Invoices',
            ]);
        }

        // Merge entries for the same staff member across both sources
        $byStaffMerged = $byStaff->groupBy('staff')->map(function ($group, $staff) {
            return (object)[
                'staff'   => $staff,
                'revenue' => $group->sum('revenue'),
                'count'   => $group->sum('count'),
                'breakdown' => $group->map(fn($g) => "{$g->source}: \${$g->revenue} ({$g->count})")->all(),
            ];
        })->sortByDesc('revenue')->values();

        // ── Revenue trend (grouped by day within range, for the chart) ──
        $trend = collect();
        $allTransactions = $orders->map(fn($o) => (object)['date' => Carbon::parse($o->created_at)->format('Y-m-d'), 'amount' => $o->total_amount_usd])
            ->concat($invoices->map(fn($i) => (object)['date' => Carbon::parse($i->created_at)->format('Y-m-d'), 'amount' => $i->subtotal_usd]));

        $trend = $allTransactions->groupBy('date')
            ->map(fn($g, $date) => (object)['date' => $date, 'revenue' => $g->sum('amount')])
            ->sortBy('date')
            ->values();

        return view('admin.reports.financial', [
            'period'             => $period,
            'from'               => $from,
            'to'                 => $to,
            'totalRevenue'       => $totalRevenue,
            'totalTransactions'  => $totalTransactions,
            'byLocation'         => $byLocation,
            'byStaff'            => $byStaffMerged,
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
