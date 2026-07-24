<?php

namespace App\Listeners;

use App\Events\PartSold;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Listens for PartSold events and:
 *   1. ALWAYS logs the sale to part_group_revenue (drives the
 *      Financial Report) — regardless of whether a donor vehicle
 *      is involved.
 *   2. If (and only if) the sale is tied to a tracked donor vehicle,
 *      also updates vehicle_revenue_projections' running actual_total
 *      and sets break_even_reached_at if total_cost is now covered.
 *
 * FIXED: this used to `return` immediately — skipping step 1 entirely —
 * for any part with no donor_vin (every consumable, every manually
 * added part without a recorded donor vehicle). That meant those
 * sales never appeared anywhere in the Financial Report even though
 * the sale itself completed normally. Step 1 now always runs; only
 * steps 2-3 (which are genuinely donor-vehicle-specific) stay gated.
 *
 * Register in EventServiceProvider:
 *   PartSold::class => [UpdateVehicleROI::class]
 */
class UpdateVehicleROI
{
    public function handle(PartSold $event): void
    {
        try {
            // ── Vehicle resale (no parts_inventory row at all) ────────────
            // FIXED: PartSold previously couldn't even represent this case
            // (partsInventoryId was required). Now logs straight to
            // part_group_revenue with no part/donor link — there is no
            // ROI/break-even concept for a resold vehicle, so steps 2-3
            // simply don't apply here.
            if ($event->partsInventoryId === null) {
                DB::table('part_group_revenue')->insert([
                    'donor_vehicle_id'   => null,
                    'parts_inventory_id' => null,
                    'invoice_id'         => $event->invoiceId,
                    'part_category'      => $event->overridePartCategory ?? 'Vehicle Sale',
                    'part_name'          => $event->overridePartName ?? 'Vehicle Sale',
                    'revenue_amount'     => $event->amountReceived,
                    'currency_code'      => $event->currencyCode,
                    'sale_date'          => now()->toDateString(),
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
                return;
            }

            // ── Look up the part and (if any) its donor vehicle ───────────
            $part = DB::table('parts_inventory')
                ->where('id', $event->partsInventoryId)
                ->select('donor_vin', 'part_category', 'part_name', 'part_code')
                ->first();

            if (!$part) {
                // Part record genuinely doesn't exist — nothing to log.
                Log::error('UpdateVehicleROI: parts_inventory row not found', [
                    'parts_inventory_id' => $event->partsInventoryId,
                    'invoice_id'         => $event->invoiceId,
                ]);
                return;
            }

            $donor = $part->donor_vin
                ? DB::table('donor_vehicles')->where('vin', $part->donor_vin)->select('id', 'total_cost')->first()
                : null;

            // ── 1. Log this sale in part_group_revenue — ALWAYS ───────────
            // FIXED: this used to only run when a donor vehicle existed.
            // Every real sale (consumables included) now counts toward
            // revenue reporting; donor_vehicle_id is simply null when
            // there's no donor vehicle to attribute it to.
            DB::table('part_group_revenue')->insert([
                'donor_vehicle_id'   => $donor->id ?? null,
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

            // No donor vehicle (consumable / non-harvested part) — nothing
            // further to do. ROI/break-even tracking genuinely doesn't
            // apply here.
            if (!$donor) {
                return;
            }

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
