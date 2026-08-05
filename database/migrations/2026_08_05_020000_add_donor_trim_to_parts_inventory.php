<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NEW: trim/sub-model tracking — per RAPID XLParts reference (Sub
 * Model: LE / LE Eco / LE Eco Plus), nothing in the schema tracked a
 * part's donor trim level at all. Note this is DIFFERENT from the
 * existing `compatible_trims` field (free text describing what trims
 * a part is confirmed to FIT) — `donor_trim` is what trim the part
 * actually CAME FROM.
 *
 * `part_interchange_vehicles.trim` already existed (from the original
 * interchange tables migration) but was never populated by any
 * controller — this gives it a real source to populate from.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->string('donor_trim', 60)->nullable()->after('model');
        });
    }

    public function down(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->dropColumn('donor_trim');
        });
    }
};
