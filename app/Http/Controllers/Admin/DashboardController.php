<?php
// FILE: app/Http/Controllers/Admin/DashboardController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    public function index()
    {
        $location = Session::get('staff_location');
        $role     = Session::get('staff_role');

        // Base query — location-scoped for non-admins
        $invQuery = DB::table('parts_inventory');
        if ($location !== 'All' && !in_array($role, ['admin','manager'])) {
            $invQuery->where('location', $location);
        }

        // ── KPI cards ─────────────────────────────────────────────
        $totalParts    = (clone $invQuery)->count();
        $available     = (clone $invQuery)->where('status','Available')->count();
        $reserved      = (clone $invQuery)->where('status','Reserved')->count();
        $sold          = (clone $invQuery)->where('status','Sold')->count();

        $totalValueUsd = (clone $invQuery)->where('status','Available')->sum('price_usd');

        $lowStock = (clone $invQuery)
            ->where('status','Available')
            ->where('stock_qty','<=',1)
            ->count();

        // ── Orders today ──────────────────────────────────────────
        $ordersToday = DB::table('orders')
            ->whereDate('created_at', today())
            ->count();

        $revenueToday = DB::table('orders')
            ->whereDate('created_at', today())
            ->where('payment_status','confirmed')
            ->sum('total_amount_ngn');

        $pendingPayments = DB::table('orders')
            ->whereIn('payment_status', ['pending','transfer_sent'])
            ->count();

        // ── Recent orders ─────────────────────────────────────────
        $recentOrders = DB::table('orders')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        // ── Recent harvests ───────────────────────────────────────
        $recentHarvests = DB::table('harvest_sessions as hs')
            ->join('donor_vehicles as dv', 'dv.id', '=', 'hs.donor_vehicle_id')
            ->join('staff as s', 's.id', '=', 'hs.staff_id')
            ->select('hs.*','dv.make','dv.model','dv.year','dv.vin','s.name as staff_name')
            ->orderByDesc('hs.created_at')
            ->limit(5)
            ->get();

        // ── Inventory by brand ────────────────────────────────────
        $byBrand = (clone $invQuery)
            ->where('status','Available')
            ->select('brand', DB::raw('count(*) as total'))
            ->groupBy('brand')
            ->orderByDesc('total')
            ->get();

        // ── Donor vehicles ────────────────────────────────────────
        $donorCount = DB::table('donor_vehicles')->count();

        return view('admin.dashboard', compact(
            'totalParts','available','reserved','sold',
            'totalValueUsd','lowStock','ordersToday','revenueToday',
            'pendingPayments','recentOrders','recentHarvests','byBrand','donorCount'
        ));
    }
}
