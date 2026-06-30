<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Add cylinder_count to OEM database + auto-derive
 * Ignition Coil / Spark Plug quantities from it at harvest time.
 *
 * ASSUMPTIONS (adjust to match your real schema):
 * - Your OEM reference table is called `oem_database` with a column
 *   `engine_code_oem` matching the same field used on parts_inventory
 *   (e.g. "2AZ-FE", "2AR-FE", "2GR-FE").
 * - If your table/column names differ, rename below before running —
 *   send me your actual OemDatabase migration/model and I'll match it
 *   exactly instead of guessing.
 *
 * WHAT THIS DOES
 * 1. Adds `cylinder_count` (tinyint) to oem_database.
 * 2. Seeds known engine codes you've already mentioned in past work
 *    (2AZ-FE, 2AR-FE, 2GR-FE) plus common others. Anything not seeded
 *    here will need cylinder_count filled in manually — it stays
 *    NULL rather than guessing, so you don't silently overstock on
 *    an engine we don't actually know.
 * 3. Leaves qty_per_vehicle on harvest_parts as a MANUAL OVERRIDE
 *    field (nullable) — when null, the app should calculate qty from
 *    cylinder_count at harvest checklist generation time; see the
 *    HarvestQuantityResolver class below for that logic.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('oem_database') && !Schema::hasColumn('oem_database', 'cylinder_count')) {
            Schema::table('oem_database', function (Blueprint $table) {
                $table->unsignedTinyInteger('cylinder_count')->nullable()->after('engine_code_oem');
            });
        }

        if (Schema::hasTable('oem_database')) {
            $cylinderCounts = [
                '2AZ-FE' => 4,
                '2AR-FE' => 4,
                '2GR-FE' => 6,
                '1NZ-FE' => 4,
                '2NZ-FE' => 4,
                '1ZZ-FE' => 4,
                '2ZR-FE' => 4,
                '1GR-FE' => 6,
                '3UR-FE' => 8,
            ];

            foreach ($cylinderCounts as $engineCode => $cylinders) {
                DB::table('oem_database')
                    ->where('engine_code_oem', $engineCode)
                    ->update(['cylinder_count' => $cylinders]);
            }
        }

        // qty_per_vehicle on harvest_parts becomes a nullable override
        // instead of a hard default, so the resolver below can compute
        // it dynamically when it's not explicitly set.
        if (Schema::hasColumn('harvest_parts', 'qty_per_vehicle')) {
            Schema::table('harvest_parts', function (Blueprint $table) {
                $table->unsignedTinyInteger('qty_per_vehicle')->nullable()->default(null)->change();
            });

            // Clear the flat default-4 we seeded earlier for coils/plugs
            // so the resolver takes over instead of the static value.
            DB::table('harvest_parts')
                ->whereIn('part_name', ['Ignition Coil', 'Spark Plug'])
                ->update(['qty_per_vehicle' => null]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('oem_database', 'cylinder_count')) {
            Schema::table('oem_database', function (Blueprint $table) {
                $table->dropColumn('cylinder_count');
            });
        }
    }
};

/**
 * app/Services/HarvestQuantityResolver.php
 * -------------------------------------------------------------------
 * Drop this in as a new file. Call it wherever your harvest checklist
 * currently sets quantity for each part row, e.g. in your
 * HarvestController when building the checklist for a donor vehicle.
 *
 * Usage:
 *   $qty = HarvestQuantityResolver::resolve($partName, $engineCodeOem);
 *
 * Resolution order:
 *   1. Manual override on harvest_parts.qty_per_vehicle (if staff set one)
 *   2. cylinder_count from oem_database for parts that scale with
 *      cylinder count (coils, plugs — extendable)
 *   3. Falls back to 1 for everything else, and to 1 (with a flag)
 *      if the engine code isn't in oem_database yet — so you never
 *      silently overstock on an unknown engine.
 * -------------------------------------------------------------------
 *
 * <?php
 *
 * namespace App\Services;
 *
 * use Illuminate\Support\Facades\DB;
 *
 * class HarvestQuantityResolver
 * {
 *     // Parts whose quantity should scale with cylinder count.
 *     // Add more here as needed (e.g. fuel injectors, valve cover gaskets).
 *     protected static array $cylinderScaledParts = [
 *         'Ignition Coil',
 *         'Spark Plug',
 *     ];
 *
 *     public static function resolve(string $partName, ?string $engineCodeOem): array
 *     {
 *         // 1. Manual override always wins
 *         $override = DB::table('harvest_parts')
 *             ->where('part_name', $partName)
 *             ->value('qty_per_vehicle');
 *
 *         if (!is_null($override)) {
 *             return ['qty' => $override, 'source' => 'manual_override'];
 *         }
 *
 *         // 2. Cylinder-scaled parts
 *         if (in_array($partName, self::$cylinderScaledParts) && $engineCodeOem) {
 *             $cylinders = DB::table('oem_database')
 *                 ->where('engine_code_oem', $engineCodeOem)
 *                 ->value('cylinder_count');
 *
 *             if ($cylinders) {
 *                 return ['qty' => $cylinders, 'source' => 'cylinder_count'];
 *             }
 *
 *             // Unknown engine code — don't guess, default to 1 and flag
 *             // it so staff know to confirm/update qty manually and
 *             // ideally add the engine's cylinder_count to oem_database.
 *             return ['qty' => 1, 'source' => 'unknown_engine_needs_review'];
 *         }
 *
 *         // 3. Default for everything else
 *         return ['qty' => 1, 'source' => 'default'];
 *     }
 * }
 *
 * -------------------------------------------------------------------
 * In your harvest checklist Blade view, you could surface the
 * 'unknown_engine_needs_review' source as a small warning badge next
 * to the qty input so staff catch it before saving — e.g.
 * "⚠ qty defaulted to 1 — engine not in OEM database, please confirm."
 */
