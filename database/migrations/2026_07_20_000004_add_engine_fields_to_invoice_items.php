<?php
// FILE: database/migrations/2026_07_20_000004_add_engine_fields_to_invoice_items.php
//
// Vehicle sales (Car Sale Receipt) previously had no way to record
// engine displacement, engine code/name, or cylinder layout (I4/V6/V8)
// at all — this adds dedicated columns alongside the existing vin/
// vehicle_year/mileage/colour columns already used for vehicle sales.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                if (!Schema::hasColumn('invoice_items', 'engine_code')) {
                    $table->string('engine_code', 60)->nullable()->after('colour');
                }
                if (!Schema::hasColumn('invoice_items', 'engine_l')) {
                    $table->decimal('engine_l', 3, 1)->nullable()->after('engine_code');
                }
                if (!Schema::hasColumn('invoice_items', 'cylinders')) {
                    $table->string('cylinders', 10)->nullable()->after('engine_l'); // text: "I4","V6","V8" etc, not just a count
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                foreach (['engine_code', 'engine_l', 'cylinders'] as $col) {
                    if (Schema::hasColumn('invoice_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
