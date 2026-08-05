<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NEW: per-item discount tracking on order_items — Place Order
 * previously only had ONE discount for the whole order (orders.
 * discount_amount_local/discount_type/discount_value), while Manual
 * Invoice already supported discounting individual line items
 * (invoice_items presumably carries the equivalent columns already,
 * since that feature already worked there). This is the real
 * structural gap behind "Place Order doesn't have the same features"
 * — not a missing button, a missing column.
 *
 * The whole-order discount on `orders` is NOT removed — both now
 * exist together, exactly like Manual Invoice already does (an
 * invoice-level discount applied on top of any already-item-
 * discounted subtotal).
 *
 * Also adds discount_override/discount_override_reason to `orders`
 * itself — confirmed absent via Schema::getColumnListing (invoices
 * already has both). Needed because Place Order previously had ZERO
 * server-side discount cap enforcement at all; fixing that requires
 * somewhere to record when/why a cap was overridden, same as invoices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('discount_amount_local', 12, 2)->nullable()->after('subtotal_local');
            $table->enum('discount_type', ['fixed', 'percent'])->nullable()->after('discount_amount_local');
            $table->decimal('discount_value', 12, 2)->nullable()->after('discount_type');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('discount_override')->default(false)->after('discount_value');
            $table->string('discount_override_reason', 500)->nullable()->after('discount_override');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['discount_amount_local', 'discount_type', 'discount_value']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['discount_override', 'discount_override_reason']);
        });
    }
};
