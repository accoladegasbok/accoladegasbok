<?php
// FILE: app/Http/Controllers/Admin/FinancialReportController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * FinancialReportController — Phase 7 Powerlink Adoption
 *
 * Routes to add in web.php (admin auth middleware group):
 *
 *   Route::get('/admin/reports/financial',
 *       [\App\Http\Controllers\Admin\FinancialReportController::class, 'index'])
 *       ->name('admin.reports.financial');
 *
 *   Route::get('/admin/reports/financial/export',
 *       [\App\Http\Controllers\Admin\FinancialReportController::class, 'export'])
 *       ->name('admin.reports.financial.export');
 */
class FinancialReportController extends Controller
{
    /**
     * FIXED: this used to filter by location via a whereExists against
     * parts_inventory ONLY. That's fine for a part sale, but breaks
     * completely for a row with no parts_inventory_id — like a
     * whole-vehicle resale (part_group_revenue.parts_inventory_id is
     * null for those) — the subquery can never match a null column
     * against parts_inventory.id, so vehicle-sale revenue silently
     * disappeared the instant ANY location filter was applied (worked
     * fine under "All Locations" only). This now also matches via the
     * linked invoice's own location as a fallback, covering rows with
     * no parts_inventory link.
     */
    private function applyLocationFilter($query, string $location)
    {
        if ($location === 'all') {
            return $query;
        }

        return $query->where(function ($q) use ($location) {
            $q->whereExists(fn($sub) =>
                $sub->from('parts_inventory')
                    ->whereColumn('parts_inventory.id', 'part_group_revenue.parts_inventory_id')
                    ->where('parts_inventory.location', $location)
            )->orWhereExists(fn($sub) =>
                $sub->from('invoices')
                    ->whereColumn('invoices.id', 'part_group_revenue.invoice_id')
                    ->where('invoices.location', $location)
            );
        });
    }

    public function index(Request $request)
    {
        $from     = $request->get('from', now()->startOfMonth()->toDateString());
        $to       = $request->get('to',   now()->toDateString());
        $location = $request->get('location', 'all');

        // ── 1. Revenue by Part Category ────────────────────────────────
        $revenueByCategory = $this->applyLocationFilter(
                DB::table('part_group_revenue')->whereBetween('sale_date', [$from, $to]),
                $location
            )
            ->selectRaw('part_category, SUM(revenue_amount) as total, COUNT(*) as sales_count')
            ->groupBy('part_category')
            ->orderByDesc('total')
            ->get();

        // ── 2. Daily Revenue Trend ─────────────────────────────────────
        $dailyRevenue = $this->applyLocationFilter(
                DB::table('part_group_revenue')->whereBetween('sale_date', [$from, $to]),
                $location
            )
            ->selectRaw('sale_date, SUM(revenue_amount) as total')
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get()
            ->pluck('total', 'sale_date');

        // ── 3. Wholesale vs Retail Sales Mix ──────────────────────────
        $wholesaleRevenue = DB::table('invoice_items as ii')
            ->join('invoices as i', 'i.id', '=', 'ii.invoice_id')
            ->whereBetween(DB::raw('DATE(i.created_at)'), [$from, $to])
            ->when($location !== 'all', fn($q) => $q->where('i.location', $location))
            ->whereNull('i.deleted_at')
            ->whereNotNull('ii.price_wholesale')
            ->selectRaw('
                SUM(ii.unit_price_local * ii.qty) as retail_total,
                SUM(COALESCE(ii.price_wholesale, ii.unit_price_local) * ii.qty) as wholesale_total,
                COUNT(*) as wholesale_line_count
            ')
            ->first();

        // ── 4. Vehicle ROI / Break-Even Report ────────────────────────
        // Unaffected by the vehicle-resale fix — this table only ever
        // tracks donor/harvest vehicles, which is a separate concept
        // from whole-vehicle resales (storeCarSale).
        $vehicleRoi = DB::table('donor_vehicles as dv')
            ->leftJoin('vehicle_revenue_projections as vrp', 'vrp.donor_vehicle_id', '=', 'dv.id')
            ->when($location !== 'all', fn($q) => $q->where('dv.location', $location))
            ->where('dv.total_cost', '>', 0)
            ->select(
                'dv.id', 'dv.year', 'dv.make', 'dv.model',
                'dv.total_cost', 'dv.currency_code', 'dv.date_acquired',
                'dv.break_even_days', 'dv.vehicle_status',
                'vrp.actual_total', 'vrp.break_even_reached_at'
            )
            ->orderByDesc('dv.created_at')
            ->limit(50)
            ->get()
            ->map(function ($v) {
                $v->recovery_pct  = $v->total_cost > 0
                    ? min(100, round(($v->actual_total / $v->total_cost) * 100, 1))
                    : 0;
                $v->days_acquired = $v->date_acquired
                    ? now()->diffInDays($v->date_acquired)
                    : null;
                $syms             = ['NGN' => '₦', 'GHS' => 'GH₵', 'USD' => '$'];
                $v->sym           = $syms[$v->currency_code ?? 'NGN'] ?? '₦';
                return $v;
            });

        // ── 5. Top 10 Best Selling Parts ──────────────────────────────
        // Vehicle-sale rows will legitimately appear here now too
        // (part_name is the vehicle description, e.g. "2022 Toyota
        // Highlander XLE") — that's expected and useful, not a bug.
        $topParts = $this->applyLocationFilter(
                DB::table('part_group_revenue')->whereBetween('sale_date', [$from, $to]),
                $location
            )
            ->selectRaw('part_name, COUNT(*) as times_sold, SUM(revenue_amount) as total_revenue')
            ->groupBy('part_name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // ── 6. Summary KPIs ───────────────────────────────────────────
        $totalRevenue      = $revenueByCategory->sum('total');
        $totalSales        = $revenueByCategory->sum('sales_count');
        $avgSaleValue      = $totalSales > 0 ? round($totalRevenue / $totalSales, 2) : 0;
        $vehiclesRecovered = $vehicleRoi->where('break_even_reached_at', '!=', null)->count();
        $vehiclesPending   = $vehicleRoi->where('break_even_reached_at', null)->count();

        $locations = [
            'all', 'Waxahachie TX', 'Kennedale TX', 'Elkhorn WI',
            'Ile-Ife Nigeria', 'Ibadan Nigeria', 'Lagos Nigeria',
            'Abuja Nigeria', 'Akure Nigeria', 'Accra Ghana',
        ];

        return view('admin.reports.financial', compact(
            'from', 'to', 'location', 'locations',
            'revenueByCategory', 'dailyRevenue', 'wholesaleRevenue',
            'vehicleRoi', 'topParts',
            'totalRevenue', 'totalSales', 'avgSaleValue',
            'vehiclesRecovered', 'vehiclesPending'
        ));
    }

    public function export(Request $request)
    {
        $from     = $request->get('from', now()->startOfMonth()->toDateString());
        $to       = $request->get('to',   now()->toDateString());
        $location = $request->get('location', 'all');

        // FIXED: same location-filter fix as index() — left/joins to
        // parts_inventory and donor_vehicles now use LEFT joins so
        // vehicle-resale rows (no parts_inventory_id, no donor vehicle)
        // still export instead of being silently dropped by an implicit
        // inner join.
        $rows = DB::table('part_group_revenue as pgr')
            ->leftJoin('parts_inventory as pi', 'pi.id', '=', 'pgr.parts_inventory_id')
            ->leftJoin('donor_vehicles as dv', 'dv.vin', '=', 'pi.donor_vin')
            ->leftJoin('invoices as inv', 'inv.id', '=', 'pgr.invoice_id')
            ->whereBetween('pgr.sale_date', [$from, $to])
            ->when($location !== 'all', fn($q) => $q->where(function ($sub) use ($location) {
                $sub->where('pi.location', $location)
                    ->orWhere('inv.location', $location);
            }))
            ->select(
                'pgr.sale_date', 'pgr.part_name', 'pgr.part_category',
                'pgr.revenue_amount', 'pgr.currency_code',
                'pi.part_code', 'pi.condition_grade',
                DB::raw('COALESCE(pi.location, inv.location) as location'),
                'pi.is_major_component', 'pi.legal_trace_required',
                'dv.year as vehicle_year', 'dv.make', 'dv.model', 'dv.total_cost as vehicle_cost'
            )
            ->orderBy('pgr.sale_date')
            ->get();

        $filename = "autozenith-revenue-{$from}-to-{$to}.csv";
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Sale Date', 'Part Code', 'Part Name', 'Category',
                'Grade', 'Revenue', 'Currency', 'Location',
                'Major Component', 'Legal Trace',
                'Donor Vehicle', 'Vehicle Cost',
            ]);
            foreach ($rows as $r) {
                fputcsv($handle, [
                    $r->sale_date, $r->part_code, $r->part_name, $r->part_category,
                    $r->condition_grade, $r->revenue_amount, $r->currency_code, $r->location,
                    $r->is_major_component ? 'Yes' : 'No',
                    $r->legal_trace_required ? 'Yes' : 'No',
                    trim(($r->vehicle_year ?? '') . ' ' . ($r->make ?? '') . ' ' . ($r->model ?? '')),
                    $r->vehicle_cost ?? '',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
