<?php
// FILE: database/migrations/2026_11_05_000001_create_consumable_batches.php
//
// A receipt ledger for consumables and other generic items: every time units are added to an item
// (first stocking, a restock, a new location, a manual correction) one row is written here with a
// generated batch number. parts_inventory.stock_qty stays the live count per location; this table is the
// history that tells batches apart. Nothing is deleted. Safe to run twice.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('consumable_batches')) return;

        Schema::create('consumable_batches', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('item_id');            // parts_inventory row (one per product per location)
            $t->string('part_code', 30)->nullable();      // CON-00036 (shared by every location)
            $t->string('location', 60)->nullable();
            $t->string('batch_no', 40);                   // CON-00036-B261105-01
            $t->integer('qty');                           // units received (negative for a manual reduction)
            $t->integer('balance_after')->nullable();     // stock at that location after this entry
            $t->string('reason', 20)->default('restock'); // initial | restock | new_location | manual | approved
            $t->string('supplier_ref', 80)->nullable();   // supplier's own lot / invoice number, if staff typed one
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('staff_id')->nullable();
            $t->string('staff_name', 80)->nullable();
            $t->timestamp('created_at')->nullable();
            $t->index('item_id');
            $t->index('part_code');
            $t->index('batch_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumable_batches');
    }
};
