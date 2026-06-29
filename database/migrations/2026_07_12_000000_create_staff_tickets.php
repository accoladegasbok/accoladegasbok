<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no', 20)->unique(); // e.g. TKT-2026-0001
            $table->unsignedBigInteger('raised_by_staff_id');
            $table->string('category', 50); // Delete Invoice/Receipt | Edit Price | Approve Discount | Stock Issue | Other
            $table->string('subject', 200);
            $table->text('description')->nullable();
            $table->string('reference_type', 50)->nullable(); // e.g. 'invoice', 'order', 'part'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('status', 20)->default('pending'); // pending | approved | rejected | completed
            $table->unsignedBigInteger('resolved_by_staff_id')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_tickets');
    }
};
