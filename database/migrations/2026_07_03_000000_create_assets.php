<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag', 30)->unique(); // e.g. AST-2026-0001
            $table->string('name', 150);
            $table->string('category', 50); // Office Equipment | Machinery | Vehicle | Tool | IT Equipment | Furniture | Other
            $table->string('location', 60);
            $table->string('status', 30)->default('In Service'); // In Service | Serviceable | Needs Repair | Out of Service | Retired
            $table->string('serial_number', 100)->nullable();
            $table->string('assigned_to', 100)->nullable(); // staff name / department holding it
            $table->date('acquired_date')->nullable();
            $table->decimal('acquired_value', 12, 2)->nullable();
            $table->string('acquired_currency', 5)->nullable();
            $table->date('last_serviced_date')->nullable();
            $table->date('next_service_due')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by_staff_id')->nullable();
            $table->timestamps();
        });

        // Audit trail of status/location changes over time
        Schema::create('asset_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('action', 30); // status_change | location_change | serviced | note
            $table->string('from_value', 100)->nullable();
            $table->string('to_value', 100)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_logs');
        Schema::dropIfExists('assets');
    }
};
