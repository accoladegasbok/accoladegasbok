<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('harvest_sessions', function (Blueprint $table) {
        $table->string('location')->default('Waxahachie TX')->after('staff_id');
    });
}

public function down(): void
{
    Schema::table('harvest_sessions', function (Blueprint $table) {
        $table->dropColumn('location');
    });
    }
};
