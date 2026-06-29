<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->decimal('amount_ngn', 14, 2);
            $table->string('payment_method', 50); // Cash, Bank Transfer, Card, POS, etc — independent per payment
            $table->string('proof_path', 255)->nullable(); // uploaded receipt/screenshot
            $table->string('status', 20)->default('pending'); // pending | confirmed | rejected
            $table->unsignedBigInteger('confirmed_by_staff_id')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });

        // Track reminder history so we don't spam the same customer repeatedly
        Schema::create('payment_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('channel', 10); // sms | email
            $table->unsignedBigInteger('sent_by_staff_id')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reminders');
        Schema::dropIfExists('order_payments');
    }
};
