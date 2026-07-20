<?php
// FILE: database/migrations/2026_07_20_000003_add_refund_amount_to_returns.php
//
// The returns table had no column to actually save what a returned
// part/labour item cost on the original receipt — meaning even a
// correctly-autofilled amount on the return form had nowhere to be
// persisted.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('returns')) {
            Schema::table('returns', function (Blueprint $table) {
                if (!Schema::hasColumn('returns', 'refund_amount_local')) {
                    $table->decimal('refund_amount_local', 14, 2)->nullable()->after('reason');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('returns') && Schema::hasColumn('returns', 'refund_amount_local')) {
            Schema::table('returns', function (Blueprint $table) {
                $table->dropColumn('refund_amount_local');
            });
        }
    }
};
