<?php
// FILE: database/migrations/2026_07_19_000006_create_transmission_families_table.php
//
// Groups transmission codes into families (A-Series, U-Series, AA-
// Series, UA/UB-Series, K-series CVT) with era, layout, and
// compatibility notes — matches the workbook's "Transmission Families"
// sheet. Used to warn staff away from cross-family swaps that LOOK
// similar (e.g. "Not interchangeable with A750 despite similar era").

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transmission_families')) {
            Schema::create('transmission_families', function (Blueprint $table) {
                $table->id();
                $table->string('transmission_codes', 100); // e.g. "A340E / A340F / A341E"
                $table->string('family_name', 40);          // e.g. "A-Series"
                $table->string('layout', 30)->nullable();   // e.g. "RWD / 4WD"
                $table->string('typical_era', 60)->nullable();
                $table->string('speeds', 10)->nullable();
                $table->unsignedTinyInteger('pin_count_min')->nullable();
                $table->unsignedTinyInteger('pin_count_max')->nullable();
                $table->string('representative_models', 300)->nullable();
                $table->string('compatibility_notes', 400)->nullable();
                $table->string('source', 60)->default('toyota_lexus_transmission_master_2026');
                $table->timestamps();

                $table->index('family_name');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transmission_families');
    }
};
