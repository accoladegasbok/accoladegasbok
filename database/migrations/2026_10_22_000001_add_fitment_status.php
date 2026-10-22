<?php
// FILE: database/migrations/2026_10_22_000001_add_fitment_status.php
// Adds the six-level fitment_status (see App\Data\FitmentStatus) and the mounting group
// to the fitment tables. Guarded, safe to run twice. Nothing here changes what customers see.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('part_interchange_vehicles')) {
            Schema::table('part_interchange_vehicles', function (Blueprint $t) {
                if (!Schema::hasColumn('part_interchange_vehicles', 'fitment_status')) $t->string('fitment_status', 30)->nullable();
                if (!Schema::hasColumn('part_interchange_vehicles', 'mount_group'))    $t->string('mount_group', 12)->nullable();
            });
        }
        if (Schema::hasTable('fitment_staging')) {
            Schema::table('fitment_staging', function (Blueprint $t) {
                if (!Schema::hasColumn('fitment_staging', 'fitment_status')) $t->string('fitment_status', 30)->nullable();
                if (!Schema::hasColumn('fitment_staging', 'mount_group'))    $t->string('mount_group', 12)->nullable();
                if (!Schema::hasColumn('fitment_staging', 'donor_years_to')) $t->unsignedSmallInteger('donor_years_to')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['part_interchange_vehicles' => ['fitment_status', 'mount_group'], 'fitment_staging' => ['fitment_status', 'mount_group', 'donor_years_to']] as $table => $cols) {
            if (!Schema::hasTable($table)) continue;
            Schema::table($table, function (Blueprint $t) use ($table, $cols) {
                foreach ($cols as $c) if (Schema::hasColumn($table, $c)) $t->dropColumn($c);
            });
        }
    }
};
