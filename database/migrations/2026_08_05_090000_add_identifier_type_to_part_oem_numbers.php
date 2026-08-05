<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * NEW: extends part_oem_numbers to cover every identifier type, not
 * just OEM numbers. Existing rows are backfilled as identifier_type
 * = 'OEM' (matching what the table has only ever stored so far) —
 * purely additive, nothing existing changes behavior.
 *
 * normalized_value strips dashes/spaces and uppercases, so a search
 * for "270600T230" can still match a stored "27060-0T230" — fuzzy
 * matching without needing a smarter search algorithm.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('part_oem_numbers', function (Blueprint $table) {
            $table->string('identifier_type', 30)->default('OEM')->after('oem_number');
            $table->string('normalized_value', 60)->nullable()->after('identifier_type');
        });

        // Backfill normalized_value for every existing row.
        DB::table('part_oem_numbers')->orderBy('id')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('part_oem_numbers')->where('id', $row->id)->update([
                    'normalized_value' => strtoupper(preg_replace('/[\s\-]/', '', $row->oem_number)),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('part_oem_numbers', function (Blueprint $table) {
            $table->dropColumn(['identifier_type', 'normalized_value']);
        });
    }
};
