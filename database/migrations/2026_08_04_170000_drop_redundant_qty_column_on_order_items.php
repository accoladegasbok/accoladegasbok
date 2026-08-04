<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * FIXED (correcting my own earlier mistake): order_items already had a
 * `quantity` column (int, NOT NULL, default 1) from day one. When the
 * qty-depletion bug was diagnosed earlier, only the INSERT statements
 * were checked — not the full table schema — so a redundant `qty`
 * column was added via migration 2026_07_24_140000, instead of just
 * fixing the code to populate the column that already existed.
 *
 * This backfills `quantity` from `qty` (qty is the one that's been
 * correctly populated by the qty-aware order code since that deploy)
 * and drops the redundant `qty` column. AdminOrderController.php and
 * OrderAdminController.php are updated in the same change to write to
 * `quantity` going forward.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('order_items', 'qty')) {
            DB::statement('UPDATE order_items SET quantity = qty WHERE qty IS NOT NULL');

            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('qty');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Re-adds `qty` and backfills it from `quantity` — restores the
     * (redundant) column structure that existed right before this
     * migration ran, in case anything still depends on it.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('order_items', 'qty')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->unsignedInteger('qty')->default(1)->after('item_type');
            });

            DB::statement('UPDATE order_items SET qty = quantity');
        }
    }
};
