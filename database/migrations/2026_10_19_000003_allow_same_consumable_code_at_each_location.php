<?php
// FILE: database/migrations/2026_10_19_000003_allow_same_consumable_code_at_each_location.php
//
// One consumable = one number, whichever location holds it. Each location still has its own stock row
// (so stock, price and sales stay per location), but they now share the same part_code.
// This removes a UNIQUE index on part_code alone (if the table has one) and puts a normal index in its place.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('parts_inventory') || !Schema::hasColumn('parts_inventory', 'part_code')) return;

        $db = DB::getDatabaseName();
        $rows = DB::select(
            "SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS cols, MAX(NON_UNIQUE) AS non_unique
               FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'parts_inventory'
              GROUP BY INDEX_NAME", [$db]);

        $hasPlain = false;
        foreach ($rows as $r) {
            if ($r->cols !== 'part_code') continue;
            if ((int) $r->non_unique === 0 && $r->INDEX_NAME !== 'PRIMARY') {
                DB::statement('ALTER TABLE `parts_inventory` DROP INDEX `' . $r->INDEX_NAME . '`');
            } else {
                $hasPlain = true;
            }
        }
        if (!$hasPlain) {
            DB::statement('ALTER TABLE `parts_inventory` ADD INDEX `parts_inventory_part_code_idx` (`part_code`)');
        }
    }

    public function down(): void
    {
        // Left as is: putting the unique rule back could fail once shared numbers exist.
    }
};
