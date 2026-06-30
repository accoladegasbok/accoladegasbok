<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Resolves how many of a given part should be saved per harvested
 * vehicle. Most parts are always qty 1 — but some (ignition coils,
 * spark plugs) physically occur once per cylinder, so their quantity
 * needs to scale with the donor vehicle's engine rather than being
 * hardcoded, to avoid overstocking on a 4-cyl car or understocking
 * on a V6/V8.
 *
 * Resolution order:
 *   1. Manual override on harvest_parts.qty_per_vehicle, if staff set one
 *   2. cylinder_count looked up from oem_database via engine_code_oem,
 *      for parts in $cylinderScaledParts
 *   3. Falls back to 1 for everything else, and to 1 (flagged as
 *      'unknown_engine_needs_review') if the engine code isn't in
 *      oem_database yet — never guesses, so you don't silently
 *      overstock on an engine we don't actually have data for.
 */
class HarvestQuantityResolver
{
    // Parts whose quantity should scale with cylinder count.
    // Add more here later if needed (e.g. fuel injectors).
    protected static array $cylinderScaledParts = [
        'Ignition Coil',
        'Spark Plug',
    ];

    /**
     * @param string      $partName     The part's label, e.g. "Ignition Coil"
     * @param string|null $engineCodeOem The donor vehicle's OEM engine code, e.g. "2AZ-FE"
     * @return array{qty: int, source: string}
     */
    public static function resolve(string $partName, ?string $engineCodeOem): array
    {
        // 1. Manual override always wins, if your harvest_parts table
        //    has a qty_per_vehicle column with a value already set for
        //    this part name (e.g. staff hand-tuned it for a known case).
        if (DB::getSchemaBuilder()->hasTable('harvest_parts')
            && DB::getSchemaBuilder()->hasColumn('harvest_parts', 'qty_per_vehicle')) {
            $override = DB::table('harvest_parts')
                ->where('part_name', $partName)
                ->value('qty_per_vehicle');

            if (!is_null($override)) {
                return ['qty' => (int) $override, 'source' => 'manual_override'];
            }
        }

        // 2. Cylinder-scaled parts — look up cylinder_count from oem_database
        if (in_array($partName, self::$cylinderScaledParts, true) && $engineCodeOem) {
            $cylinders = DB::table('oem_database')
                ->where('engine_code_oem', $engineCodeOem)
                ->value('cylinder_count');

            if ($cylinders) {
                return ['qty' => (int) $cylinders, 'source' => 'cylinder_count'];
            }

            // Unknown engine code — don't guess, default to 1 and flag it
            // so staff can confirm/update qty manually on this row, and
            // ideally someone adds the engine's cylinder_count to
            // oem_database so future harvests resolve automatically.
            return ['qty' => 1, 'source' => 'unknown_engine_needs_review'];
        }

        // 3. Default for everything else
        return ['qty' => 1, 'source' => 'default'];
    }
}
