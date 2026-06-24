<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── Expand status options. If `status` is currently an ENUM,
        // convert to a plain string so we don't need a schema change
        // every time a new status is added later.
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->string('status', 20)->default('Available')->change();
        });

        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('parts_inventory')->cascadeOnDelete();
            $table->unsignedBigInteger('invoice_id')->nullable();      // linked sale, if any
            $table->unsignedBigInteger('invoice_item_id')->nullable(); // specific line item, if any
            $table->string('return_type', 20); // 'customer' | 'internal'
            $table->text('reason');
            $table->string('status', 20)->default('pending_inspection'); // 'pending_inspection' | 'resolved'
            $table->string('resolution', 20)->nullable(); // 'restock_good' | 'core' | 'scrapped'
            $table->foreignId('new_storage_shelf_id')->nullable()->constrained('storage_shelves')->nullOnDelete();
            $table->unsignedBigInteger('created_by_staff_id')->nullable();
            $table->unsignedBigInteger('resolved_by_staff_id')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('part_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};
