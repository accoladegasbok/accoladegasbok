<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            // Personal 4-digit override PIN, separate from login
            // password. Only Supervisor/Manager/Admin roles get one
            // assigned. Hashed, never stored or shown in plaintext
            // after creation.
            $table->string('override_pin_hash', 255)->nullable()->after('role');
        });

        Schema::create('override_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approved_by_staff_id');
            $table->string('approved_by_role', 30);
            $table->string('action', 100); // e.g. "remove_cart_item", "edit_price", "delete_invoice"
            $table->string('context', 255)->nullable(); // free text: what was overridden
            $table->unsignedBigInteger('requested_by_staff_id')->nullable(); // who needed the override (if different)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('override_logs');
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('override_pin_hash');
        });
    }
};
