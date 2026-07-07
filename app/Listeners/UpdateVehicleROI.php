<?php

namespace App\Listeners;

use App\Events\PartSold;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Listens for PartSold events and updates the ROI tracking tables:
 *   - part_group_revenue: logs this specific sale against its donor vehicle
 *   - vehicle_revenue_projections: updates actual_total running sum
 *     and sets break_even_reached_at if total_cost is now covered
 *
 * Register in EventServiceProvider:
 *   PartSold::class => [UpdateVehicleROI::class]
 */
class UpdateVehicleROI
{
    public function handle(PartSold $event): void
    {
        try {
            // ── Look up the part and its donor vehicle ────────────────────
            $part = DB::table('parts_inventory')
                ->where('id', $event->partsInventoryId)
                ->select('donor_vin', 'part_category', 'part_name', 'part_code')
                ->first();

            if (!$part || !$part->donor_vin) {
                // Part has no donor vehicle — consumable/non-harvested part, skip
                return;
            }

            $donor = DB::table('donor_vehicles')
                ->where('vin', $part->donor_vin)
                ->select('id', 'total_cost')
                ->first();

            if (!$donor) return;

            // ── 1. Log this sale in part_group_revenue ────────────────────
            DB::table('part_group_revenue')->insert([
                'donor_vehicle_id'   => $donor->id,
                'parts_inventory_id' => $event->partsInventoryId,
                'invoice_id'         => $event->invoiceId,
                'part_category'      => $part->part_category,
                'part_name'          => $part->part_name,
                'revenue_amount'     => $event->amountReceived,
                'currency_code'      => $event->currencyCode,
                'sale_date'          => now()->toDateString(),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            // ── 2. Recalculate actual_total on vehicle_revenue_projections ─
            $actualTotal = DB::table('part_group_revenue')
                ->where('donor_vehicle_id', $donor->id)
                ->sum('revenue_amount');

            $projection = DB::table('vehicle_revenue_projections')
                ->where('donor_vehicle_id', $donor->id)
                ->first();

            if (!$projection) {
                // Create projection row if somehow missing (e.g. legacy vehicles)
                DB::table('vehicle_revenue_projections')->insert([
                    'donor_vehicle_id' => $donor->id,
                    'currency_code'    => $event->currencyCode,
                    'actual_total'     => $actualTotal,
                    'proj_total'       => 0,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
                return;
            }

            $updateData = [
                'actual_total' => $actualTotal,
                'updated_at'   => now(),
            ];

            // ── 3. Set break_even_reached_at if cost now recovered ────────
            $totalCost = (float) ($donor->total_cost ?? 0);
            if ($totalCost > 0
                && $actualTotal >= $totalCost
                && !$projection->break_even_reached_at) {
                $updateData['break_even_reached_at'] = now();
                Log::info('Vehicle break-even reached', [
                    'donor_vehicle_id' => $donor->id,
                    'donor_vin'        => $part->donor_vin,
                    'total_cost'       => $totalCost,
                    'actual_total'     => $actualTotal,
                ]);
            }

            DB::table('vehicle_revenue_projections')
                ->where('donor_vehicle_id', $donor->id)
                ->update($updateData);

        } catch (\Exception $e) {
            // Never block a sale because of ROI tracking failure
            Log::error('UpdateVehicleROI listener failed', [
                'error'              => $e->getMessage(),
                'parts_inventory_id' => $event->partsInventoryId,
                'invoice_id'         => $event->invoiceId,
            ]);
        }
    }
}
