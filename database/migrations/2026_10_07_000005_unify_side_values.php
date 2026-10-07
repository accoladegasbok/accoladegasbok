<?php

use App\Data\PartSides;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bring every stored `side` value onto the one standard list
 * (see App\Data\PartSides): D/S -> Left, P/S -> Right, LH/RH, FL/FR and
 * similar spellings -> the standard wording.
 *
 * Values that are not recognised are LEFT AS THEY ARE — nothing is blanked
 * or guessed. Run after 2026_10_07_000004_widen_side_column.
 */
return new class extends Migration
{
    public function up(): void
    {
        $values = DB::table('parts_inventory')->whereNotNull('side')->distinct()->pluck('side');

        foreach ($values as $old) {
            $new = PartSides::canonical($old);
            if ($new === null || $new === $old) continue;

            // Compare the exact text so a value that only differs by case is also fixed.
            DB::table('parts_inventory')
                ->whereRaw('BINARY side = ?', [$old])
                ->update(['side' => $new]);
        }
    }

    public function down(): void
    {
        // Original spellings cannot be restored, and nothing depends on them.
    }
};
