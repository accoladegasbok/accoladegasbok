<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expand part_category enum to include Consumable
        DB::statement("ALTER TABLE parts_inventory MODIFY part_category ENUM(
            'Engine','Transmission','Body','Suspension','Electrical','Interior',
            'Cooling','Brakes','Airbag','Fuel','Exhaust','Seat','Wheels','Consumable'
        ) NOT NULL");

        Schema::table('parts_inventory', function (Blueprint $table) {
            // Size/volume for consumables, e.g. "5L", "1 Quart", "4-pack"
            $table->string('unit_size', 30)->nullable()->after('part_name');
            // Free-text compatibility note for consumables, e.g.
            // "Suitable for most 4-cylinder Toyota/Honda engines"
            $table->string('compatibility_note', 200)->nullable()->after('unit_size');
        });
    }

    public function down(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->dropColumn(['unit_size', 'compatibility_note']);
        });

        DB::statement("ALTER TABLE parts_inventory MODIFY part_category ENUM(
            'Engine','Transmission','Body','Suspension','Electrical','Interior',
            'Cooling','Brakes','Airbag','Fuel','Exhaust','Seat','Wheels'
        ) NOT NULL");
    }
};
