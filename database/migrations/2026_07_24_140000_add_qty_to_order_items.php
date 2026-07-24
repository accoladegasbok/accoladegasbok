<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ROOT CAUSE of the quantity-depletion bug across Place Order /
     * order editing / order completion: `order_items` never persisted
     * its own `qty` column. AdminOrderController::store() validated
     * and used `qty` in memory (to compute subtotal_local), but never
     * saved it — so every downstream method (update, updateStatus,
     * destroy) had no way to know how many units of a part an order
     * line actually represented, and fell back to flipping the WHOLE
     * parts_inventory row's status instead of adjusting stock_qty by
     * the real amount.
     *
     * Backfills existing rows' qty as subtotal_local / unit_price_local
     * (rounded), which is the best reconstruction possible from data
     * already on hand. Rows where unit_price_local is 0/null default to
     * qty=1 (matches the assumption every prior read of this table
     * already made).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('order_items', 'qty')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->unsignedInteger('qty')->default(1)->after('item_type');
            });

            DB::statement("
                UPDATE order_items
                SET qty = CASE
                    WHEN unit_price_local IS NOT NULL AND unit_price_local > 0
                        THEN GREATEST(1, ROUND(subtotal_local / unit_price_local))
                    ELSE 1
                END
                WHERE qty = 1
            ");
        }
    }

    /**
     * Reverse the migrations.
     *
     * NOTE: dropping this column throws away real quantity data for
     * any order placed after this migration ran. Confirm you actually
     * want that before rolling back.
     */
    public function down(): void
    {
        if (Schema::hasColumn('order_items', 'qty')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('qty');
            });
        }
    }
};
