<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount_local', 14, 2);
            $table->string('payment_method', 50);
            $table->string('proof_path', 255)->nullable();
            $table->string('status', 20)->default('pending'); // pending | confirmed | rejected
            $table->unsignedBigInteger('confirmed_by_staff_id')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('invoice_id');
        });

        Schema::create('invoice_payment_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->string('channel', 10); // sms | email
            $table->unsignedBigInteger('sent_by_staff_id')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payment_reminders');
        Schema::dropIfExists('invoice_payments');
    }
};
