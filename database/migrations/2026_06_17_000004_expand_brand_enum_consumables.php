<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE parts_inventory MODIFY brand ENUM(
            'Toyota','Lexus','Kia','Hyundai','Nissan','Mercedes-Benz','Infiniti',
            'Ford','GM','Chevrolet','Acura','VW','Honda',
            'Mobil 1','Castrol','Valvoline','Shell','Fram','Bosch','Denso','NGK','ACDelco','Generic'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE parts_inventory MODIFY brand ENUM(
            'Toyota','Lexus','Kia','Hyundai','Nissan','Mercedes-Benz','Infiniti',
            'Ford','GM','Chevrolet','Acura','VW','Honda'
        ) NOT NULL");
    }
};
