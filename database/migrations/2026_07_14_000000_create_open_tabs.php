<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('open_tabs', function (Blueprint $table) {
            $table->id();
            $table->string('tab_no', 20)->unique(); // e.g. TAB-2026-0001
            $table->string('customer_name', 150);
            $table->string('customer_phone', 30);
            $table->string('customer_email', 150)->nullable();
            $table->string('location', 60);
            $table->string('status', 20)->default('open'); // open | closed | cancelled
            $table->unsignedBigInteger('opened_by_staff_id')->nullable();
            $table->unsignedBigInteger('closed_by_staff_id')->nullable();
            $table->unsignedBigInteger('closed_invoice_id')->nullable(); // links to invoices.id once closed
            $table->text('notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('open_tab_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tab_id');
            $table->string('item_type', 10); // 'part' | 'service'
            $table->unsignedBigInteger('part_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('item_name', 200);
            $table->string('item_code', 50)->nullable();
            $table->integer('qty')->default(1);
            $table->decimal('unit_price_local', 14, 2);
            $table->string('currency_code', 5);
            $table->unsignedBigInteger('added_by_staff_id')->nullable();
            $table->timestamps();

            $table->index('tab_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('open_tab_items');
        Schema::dropIfExists('open_tabs');
    }
};
