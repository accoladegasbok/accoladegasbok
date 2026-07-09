<?php
// FILE: database/migrations/2026_07_06_000000_add_created_by_to_orders.php
//
// Fixes improvement #3: the Invoices/Receipts list hardcoded the string
// 'Staff' for any walk-in/phone order, since orders had no column to
// pull a real name from. invoices.created_by already does this
// correctly (set to Session::get('staff_name') at creation time) —
// this brings orders in line with the same pattern.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('created_by', 80)->nullable()->after('channel');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });
    }
};
