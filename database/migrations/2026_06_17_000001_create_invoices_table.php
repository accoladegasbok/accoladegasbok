<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_address')->nullable();
            $table->string('location');
            $table->string('currency_code', 5)->default('USD');
            $table->decimal('subtotal_usd', 12, 2)->default(0);
            $table->string('payment_method')->default('Cash');
            $table->string('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('part_id')->nullable();
            $table->string('part_name');
            $table->string('part_code')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('condition_grade', 5)->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('unit_price_usd', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
