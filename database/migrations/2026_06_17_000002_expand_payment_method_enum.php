<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY payment_method ENUM(
            'bank_transfer',
            'pos_instore',
            'paystack',
            'zelle',
            'cashapp',
            'venmo',
            'cash'
        ) NOT NULL DEFAULT 'bank_transfer'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY payment_method ENUM(
            'bank_transfer',
            'pos_instore'
        ) NOT NULL DEFAULT 'bank_transfer'");
    }
};
