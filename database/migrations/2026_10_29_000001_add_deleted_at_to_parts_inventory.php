<?php
// FILE: database/migrations/2026_10_29_000001_add_deleted_at_to_parts_inventory.php
//
// The consumables screen and the "Other items" page filter and set parts_inventory.deleted_at,
// but the live table never had the column, which crashed /other-items. This adds it.
// Nothing is deleted. A removed item just gets a date here and drops out of lists.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parts_inventory') && !Schema::hasColumn('parts_inventory', 'deleted_at')) {
            Schema::table('parts_inventory', function (Blueprint $t) {
                $t->timestamp('deleted_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('parts_inventory') && Schema::hasColumn('parts_inventory', 'deleted_at')) {
            Schema::table('parts_inventory', function (Blueprint $t) { $t->dropColumn('deleted_at'); });
        }
    }
};
