<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIXED: returns could only ever be linked to the `invoices` table —
 * `orders` (Place Order sales) had no equivalent columns at all, so a
 * return for an order-based sale couldn't be traced back to it. This
 * is why searching "AZ-2026-00038" (a real, completed order) in
 * Returns came back "No matches" — it structurally couldn't be found.
 *
 * No foreign key constraints added — `returns` is a MyISAM table
 * (confirmed via SHOW CREATE TABLE) which doesn't support them, and
 * the existing `invoice_id` column already has no real FK constraint
 * for the same reason. This matches that existing pattern rather than
 * attempting something the storage engine can't do.
 *
 * Also adds `refund_method` (cash/transfer/store_credit) — previously
 * had nowhere to be recorded at resolution time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('returns', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->after('invoice_id');
            $table->unsignedBigInteger('order_item_id')->nullable()->after('invoice_item_id');
            $table->string('refund_method', 20)->nullable()->after('refund_amount_local');
            // Mirrors the existing (previously unused) applied_to_invoice_id
            // — lets a store-credit return be traced to an order too, once
            // order-side credit consumption is built.
            $table->unsignedBigInteger('applied_to_order_id')->nullable()->after('applied_to_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('returns', function (Blueprint $table) {
            $table->dropColumn(['order_id', 'order_item_id', 'refund_method', 'applied_to_order_id']);
        });
    }
};
