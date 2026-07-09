<?php
// FILE: database/migrations/2026_07_06_000001_add_vehicle_fields_to_invoice_items.php
//
// Supports improvement #12 (car sales receipt). invoice_items currently
// has no VIN/year/mileage/colour columns — parts invoices never needed
// them (part_name/part_code/brand/model/condition_grade covers a part).
// A whole-vehicle sale needs its own set, kept nullable so existing
// parts invoice rows are completely unaffected.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('vin', 17)->nullable()->after('model');
            $table->integer('vehicle_year')->nullable()->after('vin');
            $table->integer('mileage')->nullable()->after('vehicle_year');
            $table->string('colour', 50)->nullable()->after('mileage');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['vin', 'vehicle_year', 'mileage', 'colour']);
        });
    }
};
