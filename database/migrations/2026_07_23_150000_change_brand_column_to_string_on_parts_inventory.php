<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Converts `parts_inventory.brand` from ENUM to VARCHAR(50).
     * The ENUM was rejecting values not in its original list (e.g. "RAM"),
     * which MySQL reports as "Data truncated for column 'brand'" (warning 1265),
     * promoted to a hard exception under Laravel's strict mode.
     *
     * Brand validation should now live in the application layer
     * (Form Request / PartNames.php) rather than at the DB column level,
     * since new brands will keep showing up from Ladipo-sourced harvests.
     */
    public function up(): void
    {
        // Native ALTER via raw statement — avoids needing doctrine/dbal,
        // which Laravel 11+ no longer bundles by default.
        DB::statement("ALTER TABLE `parts_inventory` MODIFY COLUMN `brand` VARCHAR(50) NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * NOTE: This rollback restores the ORIGINAL enum list only.
     * If you've inserted rows with brands outside that original list
     * (e.g. RAM) before rolling back, this ALTER will fail or truncate
     * those rows. Update the enum list below to match what was actually
     * in production before running down(), or skip the rollback and
     * re-migrate forward instead.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `parts_inventory` MODIFY COLUMN `brand` ENUM(
            'TOYOTA','LEXUS','HONDA','NISSAN','HYUNDAI','KIA','MERCEDES','FORD'
        ) NOT NULL");
    }
};
