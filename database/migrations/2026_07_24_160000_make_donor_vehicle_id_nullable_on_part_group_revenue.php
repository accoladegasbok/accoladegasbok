<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('part_group_revenue', function (Blueprint $table) {
            $table->dropForeign(['donor_vehicle_id']);
        });

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
        Schema::table('part_group_revenue', function (Blueprint $table) {
            $table->dropForeign(['donor_vehicle_id']);
        });

        Schema::table('part_group_revenue', function (Blueprint $table) {
            $table->unsignedBigInteger('donor_vehicle_id')->nullable(false)->change();
        });

        Schema::table('part_group_revenue', function (Blueprint $table) {
            $table->foreign('donor_vehicle_id')
                ->references('id')->on('donor_vehicles')
                ->onDelete('cascade');
        });
    }
};
