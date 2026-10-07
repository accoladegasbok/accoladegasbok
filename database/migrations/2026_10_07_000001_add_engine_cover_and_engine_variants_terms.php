<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Engine Cover, plus the two engine variants staff asked for:
 *  - Complete Engine (Bare / Borale)   — no add-ons ("Borale" is the Ladipo word for bare)
 *  - Complete Engine (With Add-ons)    — add-ons ticked per engine
 */
return new class extends Migration
{
    private array $terms = [
        ['Engine', 'Engine Cover'],
        ['Engine', 'Complete Engine (Bare / Borale)'],
        ['Engine', 'Complete Engine (With Add-ons)'],
    ];

    public function up(): void
    {
        $now = now();
        foreach ($this->terms as [$category, $name]) {
            $exists = DB::table('part_terminology')->whereRaw('LOWER(standard_name) = ?', [mb_strtolower($name)])->exists();
            if (!$exists) {
                DB::table('part_terminology')->insert([
                    'category'       => $category,
                    'standard_name'  => $name,
                    'aces_pies_note' => null,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->terms as [$category, $name]) {
            DB::table('part_terminology')->where('category', $category)->where('standard_name', $name)->delete();
        }
    }
};
