<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Fix bin_location truncation + add multi-quantity support
 * for parts that occur more than once per vehicle (e.g. Ignition Coils,
 * Spark Plugs).
 *
 * WHAT THIS DOES
 * 1. Widens `bin_location` on parts_inventory so the
 *    "MODULAR ROOM X — bin not yet assigned" fallback string never
 *    truncates again (was the cause of your SQLSTATE[22001] error).
 * 2. Adds a `qty_per_vehicle` (int, default 1) column to whatever table
 *    drives your harvest checklist part list — adjust the table name
 *    below to match your actual schema (likely `harvest_parts` or
 *    `part_catalog` — rename as needed).
 * 3. Seeds/updates Ignition Coils and Spark Plug with a sensible
 *    default qty_per_vehicle (4 for a 4-cylinder baseline — you'll
 *    want to make this editable per donor vehicle's cylinder count
 *    rather than hardcoded, see notes at bottom).
 *
 * IMPORTANT: Review table/column names against your actual schema
 * before running — I'm inferring names from your screenshot/error,
 * not your live schema. Run `php artisan migrate --pretend` first
 * if you want to preview the SQL without applying it.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Fix bin_location truncation
        if (Schema::hasColumn('parts_inventory', 'bin_location')) {
            Schema::table('parts_inventory', function (Blueprint $table) {
                $table->string('bin_location', 191)->nullable()->change();
            });
        }

        // 2. Add quantity support to your harvest/part catalog table.
        //    CHANGE 'harvest_parts' below to your real table name if different.
        if (Schema::hasTable('harvest_parts') && !Schema::hasColumn('harvest_parts', 'qty_per_vehicle')) {
            Schema::table('harvest_parts', function (Blueprint $table) {
                $table->unsignedTinyInteger('qty_per_vehicle')->default(1)->after('part_name');
            });
        }

        // 3. Seed/update multi-quantity parts.
        //    Adjust table/column names to match your schema.
        if (Schema::hasTable('harvest_parts')) {
            $multiQtyParts = [
                'Ignition Coil' => 4, // adjust per engine cylinder count
                'Spark Plug'    => 4, // adjust per engine cylinder count
            ];

            foreach ($multiQtyParts as $partName => $defaultQty) {
                $exists = DB::table('harvest_parts')->where('part_name', $partName)->exists();

                if ($exists) {
                    DB::table('harvest_parts')
                        ->where('part_name', $partName)
                        ->update(['qty_per_vehicle' => $defaultQty]);
                } else {
                    DB::table('harvest_parts')->insert([
                        'part_name'       => $partName,
                        'qty_per_vehicle' => $defaultQty,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('parts_inventory', 'bin_location')) {
            Schema::table('parts_inventory', function (Blueprint $table) {
                $table->string('bin_location', 50)->nullable()->change();
            });
        }

        if (Schema::hasColumn('harvest_parts', 'qty_per_vehicle')) {
            Schema::table('harvest_parts', function (Blueprint $table) {
                $table->dropColumn('qty_per_vehicle');
            });
        }
    }
};

/**
 * NOTES ON THE QUANTITY LOGIC
 * ----------------------------
 * Ignition coils and spark plugs scale with cylinder count, not a fixed
 * number — a 4-cyl Camry has 4 of each, a V6 has 6. Hardcoding "4" above
 * is just a safe default; you have two real options:
 *
 * Option A (simple): Keep qty_per_vehicle editable on the harvest
 * checklist UI itself, defaulting to 4, so staff can bump it up/down
 * per vehicle at harvest time without touching code.
 *
 * Option B (better): Derive it from engine_code_oem via your existing
 * OemDatabase (you already track 2AZ-FE, 2AR-FE, 2GR-FE etc with pin
 * counts) — add a `cylinder_count` field there and auto-populate
 * qty_per_vehicle = cylinder_count for these two parts specifically when
 * a harvest is created, while still allowing manual override.
 *
 * I went with Option A's default in the seed above since it's
 * non-destructive and editable; let me know if you want Option B wired
 * up and send me the harvest checklist controller/blade so I can match
 * your real table/column names exactly instead of guessing.
 */
