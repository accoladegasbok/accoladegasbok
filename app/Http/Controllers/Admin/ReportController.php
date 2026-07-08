<?php
// FILE: app/Http/Controllers/Admin/ReportController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // =========================================================
    // GET /admin/reports/inventory
    // =========================================================
    public function inventory(Request $request)
    {
        $location = $request->get('location', 'all');
        $status   = $request->get('status',   'all');
        $category = $request->get('category', 'all');

        $query = DB::table('parts_inventory')->whereNull('deleted_at');

        if ($location !== 'all') $query->where('location', $location);
        if ($status   !== 'all') $query->where('status',   $status);
        if ($category !== 'all') $query->where('part_category', $category);

        // Summary stats
        $totalParts     = (clone $query)->count();
        $availableParts = (clone $query)->where('status', 'Available')->count();
        $soldParts      = (clone $query)->whereIn('status', ['Sold','sold'])->count();
        $totalValue     = (clone $query)->where('status', 'Available')->sum('price_local');

        // By category
        $byCategory = (clone $query)
            ->selectRaw('part_category, COUNT(*) as count, SUM(price_local) as value, currency_code')
            ->groupBy('part_category', 'currency_code')
            ->orderByDesc('count')
            ->get();

        // By location
        $byLocation = (clone $query)
            ->selectRaw('location, COUNT(*) as count, SUM(price_local) as value')
            ->groupBy('location')
            ->orderByDesc('count')
            ->get();

        // Low stock (qty <= 1 but not sold)
        $lowStock = (clone $query)
            ->where('status', 'Available')
            ->where('stock_qty', '<=', 1)
            ->orderBy('part_name')
            ->limit(50)
            ->get(['id','part_code','part_name','part_category','location','bin_location','stock_qty','price_local','currency_code']);

        // Major components in stock
        $majorComponents = (clone $query)
            ->where('is_major_component', 1)
            ->where('status', 'Available')
            ->orderBy('part_name')
            ->get(['id','part_code','part_name','location','bin_location','price_local','currency_code']);

        // Legal trace parts in stock
        $legalTraceParts = (clone $query)
            ->where('legal_trace_required', 1)
            ->where('status', 'Available')
            ->orderBy('part_name')
            ->get(['id','part_code','part_name','location','bin_location','price_local','currency_code','legal_trace_doc']);

        $categories = DB::table('parts_inventory')
            ->whereNull('deleted_at')
            ->distinct()->pluck('part_category')->filter()->sort()->values();

        $locations = [
            'all','Waxahachie TX','Kennedale TX','Elkhorn WI',
            'Ile-Ife Nigeria','Ibadan Nigeria','Lagos Nigeria',
            'Abuja Nigeria','Akure Nigeria','Accra Ghana',
        ];

        return view('admin.reports.inventory', compact(
            'location','status','category','locations','categories',
            'totalParts','availableParts','soldParts','totalValue',
            'byCategory','byLocation','lowStock','majorComponents','legalTraceParts'
        ));
    }

    // =========================================================
    // GET /admin/reports/staff
    // =========================================================
    public function staff(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to   = $request->get('to',   now()->toDateString());

        // Sales by staff member (from invoices)
        $salesByStaff = DB::table('invoices')
            ->whereNull('deleted_at')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw('created_by as staff_name, COUNT(*) as invoice_count, SUM(subtotal_local) as total_revenue, currency_code')
            ->groupBy('created_by', 'currency_code')
            ->orderByDesc('total_revenue')
            ->get();

        // Commissions
        $commissions = DB::table('sales_commissions as sc')
            ->join('staff as s', 's.id', '=', 'sc.staff_id')
            ->whereBetween(DB::raw('DATE(sc.created_at)'), [$from, $to])
            ->selectRaw('s.name, s.role, sc.currency_code,
                SUM(sc.sale_amount_local) as total_sales,
                SUM(sc.commission_amount_local) as total_commission,
                AVG(sc.commission_percent) as avg_rate,
                COUNT(*) as sale_count')
            ->groupBy('s.name', 's.role', 'sc.currency_code')
            ->orderByDesc('total_commission')
            ->get();

        // Override log summary
        $overrides = DB::table('override_logs')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw('staff_name, action, COUNT(*) as count')
            ->groupBy('staff_name', 'action')
            ->orderByDesc('count')
            ->get();

        return view('admin.reports.staff', compact('from','to','salesByStaff','commissions','overrides'));
    }

    // =========================================================
    // GET /admin/reports/vehicles
    // =========================================================
    public function vehicles(Request $request)
    {
        $location = $request->get('location', 'all');

        $vehicles = DB::table('donor_vehicles as dv')
            ->leftJoin('vehicle_revenue_projections as vrp', 'vrp.donor_vehicle_id', '=', 'dv.id')
            ->when($location !== 'all', fn($q) => $q->where('dv.location', $location))
            ->selectRaw('
                dv.id, dv.year, dv.make, dv.model, dv.vin,
                dv.location, dv.total_cost, dv.currency_code,
                dv.break_even_days, dv.date_acquired,
                dv.salvage_cost, dv.towing_cost, dv.processing_cost, dv.other_cost,
                dv.vehicle_status, dv.primary_damage_code,
                dv.parts_harvested,
                vrp.actual_total, vrp.proj_total, vrp.break_even_reached_at
            ')
            ->orderByDesc('dv.created_at')
            ->get()
            ->map(function ($v) {
                $v->recovery_pct = $v->total_cost > 0
                    ? min(100, round(($v->actual_total / $v->total_cost) * 100, 1))
                    : 0;
                $syms    = ['NGN'=>'₦','GHS'=>'GH₵','USD'=>'$'];
                $v->sym  = $syms[$v->currency_code ?? 'NGN'] ?? '₦';
                return $v;
            });

        $totalVehicles   = $vehicles->count();
        $recovered       = $vehicles->where('break_even_reached_at', '!=', null)->count();
        $totalCostAll    = $vehicles->sum('total_cost');
        $totalRecovered  = $vehicles->sum('actual_total');

        $locations = [
            'all','Waxahachie TX','Kennedale TX','Elkhorn WI',
            'Ile-Ife Nigeria','Ibadan Nigeria','Lagos Nigeria',
            'Abuja Nigeria','Akure Nigeria','Accra Ghana',
        ];

        return view('admin.reports.vehicles', compact(
            'location','locations','vehicles',
            'totalVehicles','recovered','totalCostAll','totalRecovered'
        ));
    }
}
