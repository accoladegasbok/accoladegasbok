<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FIXED: same recurring bug as brand, staff.location, gear_alias, and
 * customer_otps.purpose before it — drive_type was an ENUM with a
 * fixed value list that didn't include '2WD' (the app's own backfill
 * tool offers 2WD/4WD/AWD/RWD/FWD/4x2/4x4, so the column was already
 * out of sync with what the app lets staff select). Caused a hard
 * SQLSTATE 1265 crash on save rather than a validation message.
 * Widened to VARCHAR so no future drive-type value needs a migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `parts_inventory` MODIFY COLUMN `drive_type` VARCHAR(20) NULL");
    }

    public function down(): void
    {
        // Not reverting to the old ENUM — it's what caused the bug.
        // If a true rollback is ever needed, restore the exact original
        // ENUM value list from a schema backup instead of guessing here.
    }
};
