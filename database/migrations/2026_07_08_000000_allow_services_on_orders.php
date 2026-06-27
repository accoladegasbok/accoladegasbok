<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // part_id must become nullable so a service-only line item
            // can exist (#16 — Place Order should support services too)
            $table->unsignedBigInteger('part_id')->nullable()->change();
            $table->unsignedBigInteger('service_id')->nullable()->after('part_id');
            $table->string('item_type', 10)->default('part')->after('service_id'); // 'part' | 'service'
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['service_id', 'item_type']);
        });
    }
};
