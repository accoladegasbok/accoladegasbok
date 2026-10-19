<?php
// FILE: database/migrations/2026_10_19_000001_allow_kaduna_in_location_columns.php
//
// "Kaduna Nigeria" is a new location. Any location column that is still an ENUM would reject it,
// so each one is turned into a plain text column (VARCHAR 60), keeping its NULL / default setting.
// Columns that are already text are left alone, so this is safe to run more than once.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TARGETS = [
        'parts_inventory' => 'location',
        'donor_vehicles'  => 'location',
        'staff'           => 'location',
        'invoices'        => 'location',
        'orders'          => 'location',
    ];

    public function up(): void
    {
        foreach (self::TARGETS as $table => $column) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) continue;

            $info = DB::selectOne(
                'SELECT DATA_TYPE AS t, IS_NULLABLE AS n, COLUMN_DEFAULT AS d FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$table, $column]
            );
            if (!$info || strtolower($info->t) !== 'enum') continue;

            $null    = $info->n === 'YES' ? 'NULL' : 'NOT NULL';
            $default = $info->d !== null ? "DEFAULT " . DB::getPdo()->quote($info->d) : ($info->n === 'YES' ? 'DEFAULT NULL' : '');
            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` VARCHAR(60) {$null} {$default}");
        }
    }

    public function down(): void
    {
        // Not reversed: turning text back into a fixed list could lose locations already saved.
    }
};
