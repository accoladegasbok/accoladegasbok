<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FIXED: manual add crashed with "Data truncated for column 'side'"
 * (SQLSTATE 1265) when a part was saved with side = "Left".
 *
 * `side` was a fixed-list column, but the app sends several different values
 * depending on the form: N/A, D/S, P/S, Left, Right, Front, Rear,
 * Front Left, Front Right, Rear Left, Rear Right.
 * Widened to VARCHAR so any of them saves — same fix already used for
 * brand, drive_type, gear_alias and staff.location.
 * Existing values are kept exactly as they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `parts_inventory` MODIFY COLUMN `side` VARCHAR(30) NULL DEFAULT 'N/A'");
    }

    public function down(): void
    {
        // Not reverting to the old fixed list — it is what caused the crash.
    }
};
