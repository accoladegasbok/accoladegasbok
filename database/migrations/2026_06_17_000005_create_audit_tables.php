<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('location', 60);
            $table->string('category', 60); // part_category, or 'All'
            $table->string('started_by', 80);
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->integer('total_items')->default(0);
            $table->integer('matched_items')->default(0);
            $table->integer('discrepancy_items')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('part_id');
            $table->string('part_code', 20);
            $table->string('part_name', 150);
            $table->integer('expected_qty');
            $table->integer('counted_qty')->nullable(); // null = not yet counted
            $table->integer('discrepancy')->default(0); // counted - expected
            $table->string('reason', 255)->nullable();
            $table->boolean('adjusted')->default(false); // whether stock_qty was updated to match
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_items');
        Schema::dropIfExists('audit_sessions');
    }
};
