<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FIXED: same pattern as the earlier parts_inventory.brand bug —
 * staff.location is an ENUM that doesn't include "Lagos Nigeria" (and
 * likely other real locations you operate in). MySQL warning 1265
 * "Data truncated for column 'location'" gets promoted to a hard
 * QueryException under Laravel's strict mode.
 *
 * Widening to VARCHAR rather than patching the enum list — staff get
 * assigned to real business locations, and hardcoding that list at the
 * DB layer means every new location (or Nigerian city/state you expand
 * into) breaks staff editing again. Validation for allowed locations
 * belongs in the Form Request / a shared Locations list, not the
 * column definition.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `staff` MODIFY COLUMN `location` VARCHAR(60) NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * NOTE: rolling back to the original enum will fail (or truncate
     * data) for any staff member saved with a location outside that
     * original list after this migration ran — e.g. "Lagos Nigeria".
     * Reassign or confirm no such rows exist before rolling back.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `staff` MODIFY COLUMN `location` ENUM(
            'Waxahachie TX','Kennedale TX','Elkhorn WI',
            'Ile-Ife Nigeria','Ibadan Nigeria','Abuja Nigeria','Akure Nigeria','Accra Ghana'
        ) NOT NULL");
    }
};
