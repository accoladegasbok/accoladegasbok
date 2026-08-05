<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FIXED: "Door" only had "Door Shell — Front/Rear Left/Right" in the
 * taxonomy (bare metal shell only) — no "Complete Door Assembly"
 * option for a door sold WITH glass, panel, window motor, speaker,
 * lock actuator etc. still installed, which is a genuinely different
 * (and more common) sellable unit than a bare shell.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('part_terminology')->insertOrIgnore([
            ['category' => 'Body', 'standard_name' => 'Complete Door Assembly — Front Left',  'aces_pies_note' => null, 'created_at' => $now, 'updated_at' => $now],
            ['category' => 'Body', 'standard_name' => 'Complete Door Assembly — Front Right', 'aces_pies_note' => null, 'created_at' => $now, 'updated_at' => $now],
            ['category' => 'Body', 'standard_name' => 'Complete Door Assembly — Rear Left',   'aces_pies_note' => null, 'created_at' => $now, 'updated_at' => $now],
            ['category' => 'Body', 'standard_name' => 'Complete Door Assembly — Rear Right',  'aces_pies_note' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('part_terminology')
            ->where('category', 'Body')
            ->where('standard_name', 'like', 'Complete Door Assembly%')
            ->delete();
    }
};
