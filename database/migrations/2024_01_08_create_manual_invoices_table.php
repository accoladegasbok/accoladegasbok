<?php
// FILE: database/migrations/2024_01_08_create_manual_invoices_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 20)->unique();
            $table->string('customer_name', 150);
            $table->string('customer_phone', 30)->nullable();
            $table->string('customer_email', 100)->nullable();
            $table->string('location', 100);
            $table->string('currency_code', 5)->default('USD');
            $table->decimal('subtotal_usd', 12, 2)->default(0);
            $table->string('payment_method', 50)->default('Cash');
            $table->text('notes')->nullable();
            $table->longText('items_json');
            $table->string('created_by', 100)->default('Staff');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_invoices');
    }
};
