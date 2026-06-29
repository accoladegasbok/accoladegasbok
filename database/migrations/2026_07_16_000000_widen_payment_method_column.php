<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Same recurring problem as payment_status/order_status —
            // payment_method was still a restrictive ENUM that didn't
            // include values like "Bank Transfer". Widened to VARCHAR
            // permanently so any future payment method name works
            // without another migration.
            $table->string('payment_method', 50)->change();
        });
    }

    public function down(): void {}
};
