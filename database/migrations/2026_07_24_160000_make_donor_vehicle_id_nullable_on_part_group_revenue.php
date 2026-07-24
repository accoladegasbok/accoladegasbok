<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * FIXED: part_group_revenue.donor_vehicle_id was NOT NULL with a hard
 * foreign key — meaning a revenue row could ONLY be logged for a part
 * that came from a tracked donor/harvest vehicle. This is why the
 * Financial Report was silently missing revenue from:
 *   - consumables and any manually-added part with no donor_vin on file
 *   - whole-vehicle resales (storeCarSale), which have no donor_vehicles
 *     row at all — they're a separate business line from harvest stock
 *
 * This migration relaxes the column so ANY sale can be logged for
 * reporting purposes. Rows with a real donor vehicle still drive ROI /
 * break-even tracking (unchanged); rows without one simply skip that
 * part but still count toward total revenue.
 *
 * FIXED (2nd attempt): the first version assumed Laravel's default FK
 * naming convention (`part_group_revenue_donor_vehicle_id_foreign`) —
 * the actual constraint on the live table has a different name, so
 * that DROP failed outright. This version looks up the REAL constraint
 * name from information_schema first, so it works regardless of what
 * it's actually called.
 */
return new class extends Migration
{
    public function up(): void
    {
        $fkName = $this->findForeignKeyName();

        if ($fkName) {
            DB::statement("ALTER TABLE `part_group_revenue` DROP FOREIGN KEY `{$fkName}`");
        }

        Schema::table('part_group_revenue', function (Blueprint $table) {
            $table->unsignedBigInteger('donor_vehicle_id')->nullable()->change();
        });

        Schema::table('part_group_revenue', function (Blueprint $table) {
            $table->foreign('donor_vehicle_id')
                ->references('id')->on('donor_vehicles')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        // NOTE: rolling back will fail if any row logged after this
        // migration has donor_vehicle_id = null (e.g. a consumable sale
        // or vehicle resale) — those rows would violate the restored
        // NOT NULL constraint. Reassign or delete such rows first if
        // you actually need to roll back.
        $fkName = $this->findForeignKeyName();

        if ($fkName) {
            DB::statement("ALTER TABLE `part_group_revenue` DROP FOREIGN KEY `{$fkName}`");
        }

        Schema::table('part_group_revenue', function (Blueprint $table) {
            $table->unsignedBigInteger('donor_vehicle_id')->nullable(false)->change();
        });

        Schema::table('part_group_revenue', function (Blueprint $table) {
            $table->foreign('donor_vehicle_id')
                ->references('id')->on('donor_vehicles')
                ->onDelete('cascade');
        });
    }

    /**
     * Looks up the real name of the foreign key constraint on
     * part_group_revenue.donor_vehicle_id, whatever it's actually
     * called — instead of assuming Laravel's default convention.
     */
    private function findForeignKeyName(): ?string
    {
        $result = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'part_group_revenue'
              AND COLUMN_NAME = 'donor_vehicle_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ");

        return $result->CONSTRAINT_NAME ?? null;
    }
};
