<?php
// FILE: database/migrations/2024_01_04_create_staff_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->enum('role', ['admin','manager','staff','viewer'])->default('staff');
            $table->enum('location', [
                'Waxahachie TX','Elkhorn WI',
                'Ile-Ife Nigeria','Ibadan Nigeria','Oshodi Lagos','Accra Ghana','All'
            ])->default('All');
            $table->boolean('is_active')->default(true);
            $table->string('phone', 30)->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        // Harvesting sessions — one per donor vehicle strip-down event
        Schema::create('harvest_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_vehicle_id')->constrained('donor_vehicles')->onDelete('cascade');
            $table->unsignedBigInteger('staff_id');
            $table->enum('status', ['in_progress','completed','cancelled'])->default('in_progress');
            $table->integer('parts_harvested')->default(0);
            $table->integer('parts_listed')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // Seed default admin account
        DB::table('staff')->insert([
            'name'       => 'Admin',
            'email'      => 'admin@autozenithparts.com',
            'password'   => Hash::make('ChangeMe2024!'),
            'role'       => 'admin',
            'location'   => 'All',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('harvest_sessions');
        Schema::dropIfExists('staff');
    }
};
