<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            // Base commission % for sales_rep role. Admin-only to set —
            // same pattern as discount_cap_fixed/discount_cap_percent.
            $table->decimal('commission_base_percent', 5, 2)->nullable()->after('discount_cap_percent');
            // Optional volume tiers: JSON array of {min_volume, percent},
            // e.g. [{"min_volume":0,"percent":2},{"min_volume":500000,"percent":3}]
            // Volumes are in the staff member's own location currency.
            $table->json('commission_tiers')->nullable()->after('commission_base_percent');
        });

        // ── Commission ledger — one row per invoice a sales_rep is
        // credited for. Returns (Phase B) will insert a negative
        // adjustment row referencing the same invoice, rather than
        // editing the original entry, so commission history stays
        // auditable.
        Schema::create('sales_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('currency_code', 3);
            $table->decimal('sale_amount_local', 12, 2);      // net invoice amount this entry is based on
            $table->decimal('commission_percent', 5, 2);
            $table->decimal('commission_amount_local', 12, 2);
            $table->string('type', 20)->default('sale');      // 'sale' | 'return_adjustment'
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('staff_id');
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_commissions');
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['commission_base_percent', 'commission_tiers']);
        });
    }
};
