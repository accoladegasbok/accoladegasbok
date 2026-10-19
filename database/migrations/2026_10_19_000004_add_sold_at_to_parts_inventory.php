<?php
// FILE: database/migrations/2026_10_19_000004_add_sold_at_to_parts_inventory.php
//
// A real "sold on" date for every part, so the website can show items sold in the last year.
// Existing sold parts are given their last-changed date as the best available sale date.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('parts_inventory', 'sold_at')) {
            Schema::table('parts_inventory', function (Blueprint $table) {
                $table->timestamp('sold_at')->nullable()->index();
            });
        }
        DB::table('parts_inventory')->where('status', 'Sold')->whereNull('sold_at')->update(['sold_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('parts_inventory', 'sold_at')) {
            Schema::table('parts_inventory', function (Blueprint $table) { $table->dropColumn('sold_at'); });
        }
    }
};
