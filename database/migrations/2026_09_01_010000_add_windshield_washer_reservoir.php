<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * NEW: Windshield Washer Reservoir — was missing from the taxonomy
 * entirely. Filed under 'Body' rather than 'Cooling' — it holds
 * washer fluid, not coolant, so it doesn't belong with
 * Radiator/Water Pump/Coolant Reservoir despite the superficial
 * "reservoir" naming similarity.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('part_terminology')->insertOrIgnore([
            'category'       => 'Body',
            'standard_name'  => 'Windshield Washer Reservoir',
            'aces_pies_note' => null,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('part_terminology')
            ->where('category', 'Body')
            ->where('standard_name', 'Windshield Washer Reservoir')
            ->delete();
    }
};
