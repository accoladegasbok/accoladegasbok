<?php
// FILE: database/migrations/2026_07_20_000006_add_credit_tracking_to_returns.php
//
// Tracks whether a return's refund_amount_local has already been
// applied as credit toward a new invoice — prevents the same credit
// being used twice, and records exactly which invoice it went to.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('returns')) {
            Schema::table('returns', function (Blueprint $table) {
                if (!Schema::hasColumn('returns', 'credit_applied_at')) {
                    $table->timestamp('credit_applied_at')->nullable()->after('refund_amount_local');
                }
                if (!Schema::hasColumn('returns', 'applied_to_invoice_id')) {
                    $table->unsignedBigInteger('applied_to_invoice_id')->nullable()->after('credit_applied_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('returns')) {
            Schema::table('returns', function (Blueprint $table) {
                foreach (['credit_applied_at', 'applied_to_invoice_id'] as $col) {
                    if (Schema::hasColumn('returns', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
