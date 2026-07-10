<?php
// FILE: database/migrations/2026_07_10_create_vehicle_engine_specs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('vehicle_engine_specs');
        Schema::create('vehicle_engine_specs', function (Blueprint $table) {
            $table->id();
            $table->string('make', 60)->index();
            $table->string('model', 80)->index();
            $table->unsignedSmallInteger('year')->index();
            $table->string('trim', 120)->nullable();
            $table->string('body_style', 40)->nullable();
            $table->string('fuel_type', 30)->nullable();
            $table->string('drive_type', 50)->nullable();
            $table->tinyInteger('cylinders')->nullable();
            $table->decimal('engine_l', 3, 1)->nullable();
            $table->string('transmission_type', 40)->nullable();
            $table->tinyInteger('transmission_speeds')->nullable();
            $table->string('engine_code_oem', 30)->nullable();
            $table->string('transmission_code_oem', 30)->nullable();
            $table->tinyInteger('pin_count')->nullable();
            $table->string('gear_alias', 80)->nullable();
            $table->string('source', 20)->default('epa'); // epa|nhtsa|manual|ladipo
            $table->string('epa_vehicle_id', 20)->nullable()->unique();
            $table->timestamps();

            $table->index(['make', 'model', 'year']);
            $table->index(['engine_code_oem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_engine_specs');
    }
};
