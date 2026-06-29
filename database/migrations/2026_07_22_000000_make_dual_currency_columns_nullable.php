<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // These two are now intentionally left null whenever they
            // don't match the order's real currency (e.g. a USD order
            // has no total_amount_ngn at all — total_amount_local +
            // currency_code is the source of truth now).
            $table->decimal('total_amount_ngn', 14, 2)->nullable()->change();
            $table->decimal('total_amount_usd', 14, 2)->nullable()->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_price_ngn', 14, 2)->nullable()->change();
            $table->decimal('unit_price_usd', 14, 2)->nullable()->change();
            $table->decimal('subtotal_ngn', 14, 2)->nullable()->change();
        });

        Schema::table('order_payments', function (Blueprint $table) {
            $table->decimal('amount_ngn', 14, 2)->nullable()->change();
        });
    }

    public function down(): void {}
};
