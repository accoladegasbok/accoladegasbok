<?php
// FILE: database/migrations/2026_07_06_000002_add_source_ref_to_parts_inventory.php
//
// Improvement #15 — internal/source reference, up to 6 characters,
// optional at creation time (harvest or manual add), fillable later
// via Edit. E.g. TRA-0009 / MVE791, where "MVE791" is this field.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->string('source_ref', 6)->nullable()->after('oem_part_number');
        });
    }

    public function down(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->dropColumn('source_ref');
        });
    }
};
