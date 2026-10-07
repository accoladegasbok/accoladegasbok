<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One consistent casing (UPPERCASE) for vehicle make/model.
 *
 * Only VEHICLE parts are touched. Consumables, electronics, computers and
 * "other" items keep their product brand exactly as typed ("Dell", "Prime
 * Guard"), because there "brand" is a product brand, not a car make.
 *
 * Invoice and order line items are NOT changed — they are historical
 * snapshots of what was sold.
 *
 * This only fixes casing and stray spaces. It does NOT guess at wrong
 * values (GS30O, "INFINITY" under Nissan, ...) — see the review command
 * in the Step 1 notes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parts_inventory')) {
            DB::statement(
                "UPDATE parts_inventory
                    SET brand = UPPER(TRIM(brand)),
                        model = UPPER(TRIM(model))
                  WHERE part_category NOT IN ('Consumable','Electronics','Computers','Other')"
            );
        }

        if (Schema::hasTable('donor_vehicles')) {
            DB::statement("UPDATE donor_vehicles SET make = UPPER(TRIM(make)), model = UPPER(TRIM(model))");
        }
    }

    public function down(): void
    {
        // Original mixed casing cannot be restored, and nothing depends on it.
    }
};
