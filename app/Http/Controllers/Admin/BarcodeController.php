<?php
// FILE: app/Http/Controllers/Admin/BarcodeController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InterchangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Barcode Label Controller
 *
 * Two label sizes:
 *   2x1 inches  — barcode only (shelf tag, bin label, gate scan)
 *   4x6 inches  — full Powerlink-style label with:
 *                  business info, part description, grade,
 *                  Stock#, IC# (interchange code), bin location,
 *                  compatibility / "also fits" vehicles, price, barcode
 *
 * Routes:
 *   GET /admin/inventory/barcode-label?ids=1,2,3&size=large
 *   GET /admin/inventory/{id}/barcode?size=small
 */
class BarcodeController extends Controller
{
    public function __construct(private InterchangeService $interchange) {}

    public function show(Request $request)
    {
        // Support both ?ids=1,2,3 and route /{id}/barcode
        $rawIds = $request->get('ids', $request->route('id'));
        $ids    = array_filter(array_map('intval', explode(',', (string) $rawIds)));
        $size   = in_array($request->get('size', 'large'), ['small', 'large']) ? $request->get('size', 'large') : 'large';

        if (empty($ids)) abort(400, 'No part IDs provided.');

        $parts = DB::table('parts_inventory')
            ->whereIn('id', $ids)
            ->select(
                'id', 'part_code', 'part_name', 'part_category',
                'brand', 'model', 'year_from', 'year_to',
                'compat_year_from', 'compat_year_to',
                'engine_code_oem', 'engine_displacement', 'transmission_code_oem', 'pin_count', 'gear_alias', 'drive_type',
                'condition_grade', 'conditions_and_options',
                'price_local', 'price_wholesale', 'currency_code',
                'bin_location', 'location', 'donor_vin',
                'description', 'photos', 'stock_qty',
                'is_major_component', 'legal_trace_required',
                'interchange_group_id', 'mileage', 'side',
                'created_at'
            )
            ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
            ->get();

        if ($parts->isEmpty()) abort(404, 'No matching parts found.');

        // Enrich each part with interchange group + compatible vehicles
        $parts = $parts->map(function ($part) {
            // Interchange group (IC# in Powerlink)
            $group    = null;
            $vehicles = collect();

            if ($part->interchange_group_id) {
                $group    = DB::table('part_interchange_groups')
                    ->where('id', $part->interchange_group_id)
                    ->first();
                // FIXED: previously listed every individual vehicle row
                // exactly as stored — "TOYOTA COROLLA (2014-2014) ·
                // TOYOTA COROLLA (2016-2016)" instead of a clean
                // "2014-2016" range when those years are genuinely
                // contiguous. Merges only truly adjacent years — a real
                // gap (e.g. 2009 sitting alone, disconnected from
                // 2014-2016) stays its own separate entry rather than
                // being falsely bridged into a range that overclaims
                // years nobody actually confirmed.
                $vehicles = $this->interchange->mergeContiguousYearRanges(
                    $this->interchange->vehiclesForGroup($part->interchange_group_id)
                );
            } else {
                // FIXED: this used to jump straight to the OEM-code
                // heuristic for EVERY category — wrong tool for
                // Suspension/Brakes (steering rack, control arms,
                // calipers etc.), whose real fitment is driven by
                // chassis platform, not which engine happens to be
                // under the hood. Now checks PlatformDatabase FIRST,
                // same tier order the Compatibility Checker page
                // already uses (own-generation + confirmed cross-model
                // platform-mates), and only falls back to the OEM-code
                // heuristic when no platform data exists for this
                // make/model at all.
                $platform = \App\Data\PlatformDatabase::lookup($part->brand, $part->model, (int) $part->year_from);
                $platformVehicles = collect($platform['shared_vehicles'] ?? [])
                    ->filter(function ($sv) use ($part) {
                        $entryCategories = $sv['categories'] ?? \App\Data\PlatformDatabase::CROSS_MODEL_SAFE_CATEGORIES;
                        return in_array($part->part_category, $entryCategories, true);
                    })
                    ->map(fn($sv) => (object) [
                        'make' => $sv['make'], 'model' => $sv['model'],
                        'year_from' => $sv['year_from'], 'year_to' => $sv['year_to'],
                    ]);

                if ($platformVehicles->isNotEmpty()) {
                    $vehicles = $this->interchange->mergeContiguousYearRanges($platformVehicles);
                    $group = (object) [
                        'group_code' => $platform['platform_code'] ?? ($platform['generation'] ?? 'PLATFORM'),
                        'source'     => 'platform',
                        'generation' => $platform['generation'] ?? null,
                    ];
                } else {
                    // Try heuristic for "also fits" suggestion
                    $heuristic = $this->interchange->interchangeFor(
                        $part->part_name,
                        $part->engine_code_oem,
                        $part->transmission_code_oem
                    );
                    if ($heuristic['found']) {
                        $vehicles = $this->interchange->mergeContiguousYearRanges($heuristic['vehicles'])->take(4);
                    }
                }
            }

            $part->interchange_group    = $group;
            $part->interchange_vehicles = $vehicles;

            // Business info for label header
            $part->business = app(\App\Http\Controllers\Admin\InvoiceController::class)
                ->getBusinessInfo($part->location ?? 'Waxahachie TX');

            // Currency
            $syms           = ['NGN' => '₦', 'GHS' => 'GH₵', 'USD' => '$'];
            $part->sym      = $syms[$part->currency_code ?? 'NGN'] ?? '₦';
            $part->price_fmt = $part->sym . ($part->currency_code === 'NGN'
                ? number_format(round($part->price_local))
                : number_format($part->price_local, 2));
            $part->wholesale_fmt = $part->price_wholesale
                ? $part->sym . ($part->currency_code === 'NGN'
                    ? number_format(round($part->price_wholesale))
                    : number_format($part->price_wholesale, 2))
                : null;

            // Photo
            $photos       = json_decode($part->photos ?? '[]', true);
            $part->photo  = !empty($photos) ? asset('storage/' . $photos[0]) : null;

            // Vehicle string
            $part->vehicle_str = trim(
                ($part->brand ?? '') . ' ' .
                ($part->model ?? '') . ' ' .
                ($part->year_from ?? '') .
                ($part->year_to && $part->year_to != $part->year_from ? '-' . $part->year_to : '')
            );

            return $part;
        });

        // Get business info for page title (use first part's location)
        $businessInfo = $parts->first()?->business ?? [];

        return view('admin.inventory.barcode-label', compact('parts', 'size', 'businessInfo'));
    }
}
