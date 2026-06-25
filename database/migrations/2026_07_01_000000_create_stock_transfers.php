<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no', 30)->unique();
            $table->string('from_location', 60);
            $table->string('to_location', 60);
            $table->string('status', 20)->default('pending'); // pending | in_transit | received | cancelled
            $table->unsignedBigInteger('created_by_staff_id')->nullable();
            $table->unsignedBigInteger('received_by_staff_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('part_id')->constrained('parts_inventory')->cascadeOnDelete();
            $table->string('part_name', 150);
            $table->string('part_code', 30);
            $table->string('brand', 60)->nullable();
            $table->string('model', 60)->nullable();
            $table->string('condition_grade', 10)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
