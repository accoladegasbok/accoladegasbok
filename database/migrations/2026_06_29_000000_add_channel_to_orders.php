<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Default 'online' so every existing order (placed through
            // the real customer-facing checkout) is correctly labeled
            // without needing a backfill. Orders placed by staff via
            // the admin "Place Order" tool explicitly set this to
            // 'walk-in' or 'phone' at creation time.
            $table->string('channel', 20)->default('online')->after('order_ref');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('channel');
        });
    }
};
