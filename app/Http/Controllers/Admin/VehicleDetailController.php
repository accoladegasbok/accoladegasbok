<?php
// FILE: app/Http/Controllers/Admin/VehicleDetailController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Donor Vehicle detail page.
 *
 * Add to routes/web.php inside admin middleware group:
 *   Route::get('/vehicles/{id}',
 *       [\App\Http\Controllers\Admin\VehicleDetailController::class, 'show'])
 *       ->name('admin.vehicles.show');
 *
 * Also add a link in harvest history index (admin.harvest.index) and
 * the ROI dashboard (admin.vehicles.roi) pointing to this route.
 */
class VehicleDetailController extends Controller
{
    public function show(int $id)
    {
        $vehicle = DB::table('donor_vehicles')->where('id', $id)->first();
        if (!$vehicle) abort(404);

        $projection = DB::table('vehicle_revenue_projections')
            ->where('donor_vehicle_id', $id)
            ->first();

        $parts = DB::table('parts_inventory')
            ->where('donor_vin', $vehicle->vin)
            ->orderBy('part_category')
            ->orderBy('part_name')
            ->get([
                'id', 'part_code', 'part_name', 'part_category',
                'condition_grade', 'conditions_and_options',
                'price_local', 'price_wholesale', 'currency_code',
                'bin_location', 'status', 'stock_qty',
                'is_major_component', 'legal_trace_required',
            ]);

        $sessions = DB::table('harvest_sessions as hs')
            ->leftJoin('staff as s', 's.id', '=', 'hs.staff_id')
            ->where('hs.donor_vehicle_id', $id)
            ->select('hs.*', 's.name as staff_name')
            ->orderByDesc('hs.created_at')
            ->get();

        return view('admin.vehicles.detail', compact('vehicle', 'projection', 'parts', 'sessions'));
    }
}
