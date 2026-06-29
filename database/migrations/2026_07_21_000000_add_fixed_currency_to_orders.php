<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total_amount_local', 14, 2)->nullable()->after('total_amount_usd');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_price_local', 14, 2)->nullable()->after('unit_price_usd');
            $table->decimal('subtotal_local', 14, 2)->nullable()->after('unit_price_local');
        });

        Schema::table('order_payments', function (Blueprint $table) {
            $table->decimal('amount_local', 14, 2)->nullable()->after('amount_ngn');
            $table->string('currency_code', 5)->nullable()->after('amount_local');
        });
        DB::table('order_payments')->update(['amount_local' => DB::raw('amount_ngn'), 'currency_code' => 'NGN']);

        // Backfill: use whichever of the existing dual-stored figures is
        // the REAL one for that order's currency, not the converted one.
        DB::table('orders')->where('currency_code', 'NGN')->update([
            'total_amount_local' => DB::raw('total_amount_ngn'),
        ]);
        DB::table('orders')->whereIn('currency_code', ['USD', 'GHS'])->orWhereNull('currency_code')->update([
            'total_amount_local' => DB::raw('total_amount_usd'),
        ]);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('unit_price_local');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('total_amount_local');
        });
    }
};
