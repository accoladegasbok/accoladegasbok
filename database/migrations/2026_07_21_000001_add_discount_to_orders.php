<?php
// FILE: database/migrations/2026_07_21_000001_add_discount_to_orders.php
//
// The "Place Order" staff form already had a fully working discount UI
// (live preview, cap checking, override reason) — but orders had no
// columns to save it to at all, and store() never read the submitted
// fields either. This adds the storage side.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'discount_amount_local')) {
                    $table->decimal('discount_amount_local', 14, 2)->default(0)->after('total_amount_local');
                }
                if (!Schema::hasColumn('orders', 'discount_type')) {
                    $table->string('discount_type', 10)->nullable()->after('discount_amount_local');
                }
                if (!Schema::hasColumn('orders', 'discount_value')) {
                    $table->decimal('discount_value', 10, 2)->nullable()->after('discount_type');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                foreach (['discount_amount_local', 'discount_type', 'discount_value'] as $col) {
                    if (Schema::hasColumn('orders', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
