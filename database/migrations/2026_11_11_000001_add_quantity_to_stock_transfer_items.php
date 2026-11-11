<?php
// FILE: database/migrations/2026_11_11_000001_add_quantity_to_stock_transfer_items.php
// Lets a transfer move SOME of the units of a bulk item (consumables, electronics, computers, other new items)
// and leave the rest where they are. Used car parts stay one row per transfer line (qty = 1).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stock_transfer_items')) return;

        Schema::table('stock_transfer_items', function (Blueprint $t) {
            if (!Schema::hasColumn('stock_transfer_items', 'qty'))           $t->unsignedInteger('qty')->default(1)->after('part_code');
            if (!Schema::hasColumn('stock_transfer_items', 'is_bulk'))       $t->boolean('is_bulk')->default(false)->after('qty');
            if (!Schema::hasColumn('stock_transfer_items', 'part_category')) $t->string('part_category', 40)->nullable()->after('is_bulk');
            if (!Schema::hasColumn('stock_transfer_items', 'unit_size'))     $t->string('unit_size', 40)->nullable()->after('part_category');
            if (!Schema::hasColumn('stock_transfer_items', 'dest_part_id'))  $t->unsignedBigInteger('dest_part_id')->nullable()->after('unit_size');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('stock_transfer_items')) return;
        Schema::table('stock_transfer_items', function (Blueprint $t) {
            foreach (['qty', 'is_bulk', 'part_category', 'unit_size', 'dest_part_id'] as $c) {
                if (Schema::hasColumn('stock_transfer_items', $c)) $t->dropColumn($c);
            }
        });
    }
};
