<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

/**
 * VehicleROIController
 * Handles the ROI / break-even dashboard for individual donor vehicles.
 *
 * Routes to add in web.php (inside admin auth middleware group):
 *   Route::get('/admin/vehicles/{id}/roi', [VehicleROIController::class, 'show'])
 *        ->name('admin.vehicles.roi');
 *   Route::get('/admin/vehicles/roi-summary', [VehicleROIController::class, 'summary'])
 *        ->name('admin.vehicles.roi-summary');
 */
class VehicleROIController extends Controller
{
    // =========================================================
    // GET /admin/vehicles/{id}/roi — single vehicle ROI dashboard
    // =========================================================
    public function show(int $donorVehicleId)
    {
        $vehicle = DB::table('donor_vehicles')->where('id', $donorVehicleId)->first();
        if (!$vehicle) abort(404);

        $projection = DB::table('vehicle_revenue_projections')
            ->where('donor_vehicle_id', $donorVehicleId)
            ->first();

        // ── Parts summary ──────────────────────────────────────────────
        $parts = DB::table('parts_inventory')
            ->where('donor_vin', $vehicle->vin)
            ->select('id', 'part_name', 'part_category', 'part_code',
                     'price_local', 'price_wholesale', 'currency_code',
                     'status', 'condition_grade', 'bin_location', 'created_at')
            ->orderBy('part_category')
            ->get();

        $totalParts    = $parts->count();
        $availableParts = $parts->where('status', 'Available')->count();
        $soldParts     = $parts->whereIn('status', ['Sold', 'sold'])->count();
        $totalListed   = $parts->sum('price_local');

        // ── Revenue by category ────────────────────────────────────────
        $revenueByCategory = DB::table('part_group_revenue')
            ->where('donor_vehicle_id', $donorVehicleId)
            ->selectRaw('part_category, SUM(revenue_amount) as total, COUNT(*) as sales_count')
            ->groupBy('part_category')
            ->orderByDesc('total')
            ->get();

        // ── Recent sales ───────────────────────────────────────────────
        $recentSales = DB::table('part_group_revenue as pgr')
            ->leftJoin('parts_inventory as pi', 'pi.id', '=', 'pgr.parts_inventory_id')
            ->where('pgr.donor_vehicle_id', $donorVehicleId)
            ->select('pgr.*', 'pi.part_code', 'pi.condition_grade')
            ->orderByDesc('pgr.created_at')
            ->limit(20)
            ->get();

        // ── Key metrics ────────────────────────────────────────────────
        $totalCost   = (float) ($vehicle->total_cost ?? 0);
        $actualTotal = (float) ($projection->actual_total ?? 0);
        $projTotal   = (float) ($projection->proj_total  ?? 0);
        $remaining   = max(0, $totalCost - $actualTotal);
        $recoveryPct = $totalCost > 0 ? min(100, round(($actualTotal / $totalCost) * 100, 1)) : 0;

        $daysSinceAcquired = $vehicle->date_acquired
            ? now()->diffInDays($vehicle->date_acquired)
            : null;
        $breakEvenDays  = (int) ($vehicle->break_even_days ?? 90);
        $daysRemaining  = $daysSinceAcquired !== null
            ? max(0, $breakEvenDays - $daysSinceAcquired)
            : null;
        $onTrack = $daysSinceAcquired !== null && $recoveryPct > 0
            ? ($recoveryPct / max(1, $daysSinceAcquired)) >= (100 / max(1, $breakEvenDays))
            : null;

        // ── Currency ───────────────────────────────────────────────────
        $currencyCode = $vehicle->currency_code ?? 'NGN';
        $symbols      = ['NGN' => '₦', 'GHS' => 'GH₵', 'USD' => '$'];
        $sym          = $symbols[$currencyCode] ?? '₦';

        return view('admin.vehicles.roi', compact(
            'vehicle', 'projection', 'parts', 'revenueByCategory', 'recentSales',
            'totalParts', 'availableParts', 'soldParts', 'totalListed',
            'totalCost', 'actualTotal', 'projTotal', 'remaining', 'recoveryPct',
            'daysSinceAcquired', 'breakEvenDays', 'daysRemaining', 'onTrack',
            'currencyCode', 'sym'
        ));
    }

    // =========================================================
    // GET /admin/vehicles/roi-summary — all vehicles ROI table
    // Used on harvest history page ROI column
    // =========================================================
    public function summary()
    {
        $vehicles = DB::table('donor_vehicles as dv')
            ->leftJoin('vehicle_revenue_projections as vrp', 'vrp.donor_vehicle_id', '=', 'dv.id')
            ->leftJoin('harvest_sessions as hs', function ($j) {
                $j->on('hs.donor_vehicle_id', '=', 'dv.id')
                  ->whereRaw('hs.id = (SELECT MAX(id) FROM harvest_sessions WHERE donor_vehicle_id = dv.id)');
            })
            ->select(
                'dv.id', 'dv.year', 'dv.make', 'dv.model', 'dv.vin',
                'dv.location', 'dv.total_cost', 'dv.currency_code',
                'dv.break_even_days', 'dv.date_acquired', 'dv.vehicle_status',
                'dv.primary_damage_code',
                'vrp.actual_total', 'vrp.proj_total', 'vrp.break_even_reached_at',
                'hs.parts_harvested', 'hs.status as session_status', 'hs.completed_at'
            )
            ->orderByDesc('dv.created_at')
            ->paginate(30);

        // Enrich with computed fields
        $vehicles->through(function ($v) {
            $v->total_cost   = (float) ($v->total_cost   ?? 0);
            $v->actual_total = (float) ($v->actual_total ?? 0);
            $v->recovery_pct = $v->total_cost > 0
                ? min(100, round(($v->actual_total / $v->total_cost) * 100, 1))
                : 0;
            $v->roi_status = match(true) {
                $v->break_even_reached_at !== null  => 'recovered',
                $v->recovery_pct >= 75              => 'on_track',
                $v->recovery_pct >= 40              => 'progressing',
                default                             => 'early',
            };
            $symbols = ['NGN' => '₦', 'GHS' => 'GH₵', 'USD' => '$'];
            $sym = $symbols[$v->currency_code ?? 'NGN'] ?? '₦';
            $v->sym = $sym;
            return $v;
        });

        return view('admin.vehicles.roi-summary', compact('vehicles'));
    }
}
