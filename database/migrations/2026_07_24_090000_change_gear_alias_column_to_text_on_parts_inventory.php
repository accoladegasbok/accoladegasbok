<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `gear_alias` was rejecting descriptive text (e.g. "20-pin gear (Camry
     * 2010-11 — early 2AR-FE, distinct from 2012+ 22-pin)") because the
     * column was too narrow (VARCHAR with a small limit). MySQL error 1406
     * "Data too long for column" fired the same way "brand" did with 1265 —
     * the column definition didn't anticipate free-text notes.
     *
     * Widening to TEXT rather than a larger VARCHAR because these alias
     * notes are clearly going to keep growing in length as more nuanced
     * gear/pin distinctions get documented (per your OEM database pattern).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `parts_inventory` MODIFY COLUMN `gear_alias` TEXT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * NOTE: rolling back to VARCHAR(50) will silently truncate any rows
     * that were saved with longer alias text after this migration ran.
     * Confirm no long values exist before rolling back, or skip rollback
     * and re-migrate forward instead.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `parts_inventory` MODIFY COLUMN `gear_alias` VARCHAR(50) NULL");
    }
};
