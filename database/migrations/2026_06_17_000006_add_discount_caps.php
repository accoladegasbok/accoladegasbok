<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->decimal('discount_cap_fixed', 10, 2)->nullable()->after('role');
            $table->decimal('discount_cap_percent', 5, 2)->nullable()->after('discount_cap_fixed');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('discount_amount_usd', 10, 2)->default(0)->after('subtotal_usd');
            $table->string('discount_type', 10)->nullable()->after('discount_amount_usd'); // 'fixed' or 'percent'
            $table->decimal('discount_value', 10, 2)->nullable()->after('discount_type'); // the raw entered value
            $table->boolean('discount_override')->default(false)->after('discount_value'); // exceeded cap, staff overrode
            $table->string('discount_override_reason', 255)->nullable()->after('discount_override');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('discount_amount_usd', 10, 2)->default(0)->after('unit_price_usd');
            $table->string('discount_type', 10)->nullable()->after('discount_amount_usd');
            $table->decimal('discount_value', 10, 2)->nullable()->after('discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['discount_cap_fixed', 'discount_cap_percent']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'discount_amount_usd', 'discount_type', 'discount_value',
                'discount_override', 'discount_override_reason',
            ]);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['discount_amount_usd', 'discount_type', 'discount_value']);
        });
    }
};
