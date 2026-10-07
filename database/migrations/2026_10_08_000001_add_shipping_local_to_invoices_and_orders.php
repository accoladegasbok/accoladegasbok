<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every invoice/receipt now prints a Shipping line. This gives invoices and orders a place to
 * keep that amount. It defaults to 0, so existing documents and totals are unchanged; the
 * receipt reads it as-is. (Entering a shipping charge on the forms is a later step.)
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['invoices', 'orders'] as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'shipping_local')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->decimal('shipping_local', 12, 2)->default(0);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['invoices', 'orders'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'shipping_local')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('shipping_local'));
            }
        }
    }
};
