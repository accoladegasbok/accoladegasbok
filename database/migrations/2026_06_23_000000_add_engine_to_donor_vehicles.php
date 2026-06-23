<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donor_vehicles', function (Blueprint $table) {
            $table->string('engine', 40)->nullable()->after('body_style');
        });
    }

    public function down(): void
    {
        Schema::table('donor_vehicles', function (Blueprint $table) {
            $table->dropColumn('engine');
        });
    }
};
