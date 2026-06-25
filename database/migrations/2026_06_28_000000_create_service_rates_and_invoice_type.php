<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Tag every invoice as parts sale vs. service/misc — keeps
        // reporting and inventory logic cleanly separated.
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('invoice_type', 20)->default('parts')->after('invoice_no'); // 'parts' | 'service'
        });

        // ── Fixed-rate service catalog (admin-managed) — e.g. "Brake Pad
        // Replacement (Labor)", "Oil Change Service", "Diagnostic Fee".
        // default_price is a suggested starting point only — staff can
        // still adjust per transaction since currency varies by location.
        Schema::create('service_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('category', 60)->nullable(); // e.g. "Brakes", "Maintenance", "Diagnostic"
            $table->decimal('default_price', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_rates');
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('invoice_type');
        });
    }
};
