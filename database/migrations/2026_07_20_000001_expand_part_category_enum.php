<?php
// FILE: database/migrations/2026_07_20_000001_expand_part_category_enum.php
//
// Fixes: "Data truncated for column 'part_category'" when saving
// Computers/Electronics/Other consumables. The PHP-side
// InventoryController::CATEGORIES constant was updated to include
// these values a while ago, but the actual database ENUM column was
// never migrated to match — same root pattern as the earlier brand
// ENUM truncation bug, just a different column this time.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE parts_inventory MODIFY part_category ENUM(
            'Engine','Transmission','Body','Suspension','Electrical',
            'Interior','Cooling','Brakes','Airbag','Fuel','Exhaust','Seat','Wheels',
            'Consumable','Electronics','Computers','Other'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE parts_inventory MODIFY part_category ENUM(
            'Engine','Transmission','Body','Suspension','Electrical',
            'Interior','Cooling','Brakes','Airbag','Fuel','Exhaust','Seat','Wheels',
            'Consumable'
        ) NOT NULL");
    }
};
