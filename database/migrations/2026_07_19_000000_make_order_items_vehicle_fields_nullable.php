<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Service line items (added via #16) have no brand/model/year
            // — they're not tied to a vehicle at all, unlike parts. These
            // columns were NOT NULL from when order_items only ever held
            // parts, causing every service-only order to fail on insert.
            $table->string('brand', 100)->nullable()->change();
            $table->string('model', 100)->nullable()->change();
            $table->integer('year_from')->nullable()->change();
            $table->integer('year_to')->nullable()->change();
            $table->string('condition_grade', 20)->nullable()->change();
        });
    }

    public function down(): void {}
};
