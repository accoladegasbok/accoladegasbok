<?php
// FILE: database/migrations/2024_01_07_add_product_info_to_parts.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            // Stores the structured product info bullets as JSON
            // e.g. {"fitment":"2009-2015 Toyota Corolla","type":"Automatic 4Speed",
            //        "origin":"JDM","warranty":"90 Days","included":"Complete Transmission",
            //        "notes":"Reuse original sensors for installation"}
            if (!Schema::hasColumn('parts_inventory', 'product_info')) {
                $table->text('product_info')->nullable()
                      ->after('fitment_notes')
                      ->comment('Structured product info bullets as JSON');
            }
        });
    }

    public function down(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->dropColumn('product_info');
        });
    }
};
