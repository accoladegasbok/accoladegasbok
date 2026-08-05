<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NEW: staff self-service password reset. Previously only an admin
 * could change another staff member's password (via
 * StaffController::update()) — no way for a staff member to reset
 * their own if forgotten, without going through an admin every time.
 *
 * Token stored directly on `staff` rather than a separate table —
 * simpler for a single-purpose reset flow with no need to track
 * history of past reset attempts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('password_reset_token', 64)->nullable()->after('password');
            $table->timestamp('password_reset_expires_at')->nullable()->after('password_reset_token');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['password_reset_token', 'password_reset_expires_at']);
        });
    }
};
