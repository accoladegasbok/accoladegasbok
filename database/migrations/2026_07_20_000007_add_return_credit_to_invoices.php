<?php
// FILE: database/migrations/2026_07_20_000007_add_return_credit_to_invoices.php
//
// Tracks a return credit applied to THIS invoice — kept separate from
// discount_amount_local so the printed invoice can show "Return Credit
// Applied" distinctly from an ordinary discount, matching the same
// transparency principle used for discount percentage display.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('invoices', 'return_credit_id')) {
                    $table->unsignedBigInteger('return_credit_id')->nullable()->after('discount_value');
                }
                if (!Schema::hasColumn('invoices', 'return_credit_applied_local')) {
                    $table->decimal('return_credit_applied_local', 14, 2)->default(0)->after('return_credit_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                foreach (['return_credit_id', 'return_credit_applied_local'] as $col) {
                    if (Schema::hasColumn('invoices', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
