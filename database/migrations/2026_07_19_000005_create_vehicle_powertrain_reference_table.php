<?php
// FILE: database/migrations/2026_07_19_000005_create_vehicle_powertrain_reference_table.php
//
// The DB-backed replacement for OemDatabase.php's hardcoded PHP
// if/else branches. Answers "given this vehicle, what engine/
// transmission does it have" — reference data, not interchange-group
// evidence (see vehicle_powertrain_families for the grouping layer).
//
// Seeded initially from toyota_lexus_model_year_transmission_master_2026.xlsx.
// Pin counts are stored as MIN/MAX ranges, matching the workbook's
// honest "10-13" / "16-20" / "~10" style — never collapsed to a single
// false-precision number.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vehicle_powertrain_reference')) {
            Schema::create('vehicle_powertrain_reference', function (Blueprint $table) {
                $table->id();
                $table->string('make', 40);
                $table->string('model', 60);
                $table->smallInteger('year_from');
                $table->smallInteger('year_to');
                $table->string('engine_code', 60)->nullable();   // e.g. "2AZ-FE 2.4" — may include displacement text as given in source
                $table->decimal('engine_l', 3, 1)->nullable();   // parsed displacement where a single clean value exists
                $table->unsignedTinyInteger('cylinders')->nullable();
                $table->string('drive_type', 20)->nullable();    // FWD/RWD/AWD/4WD — or "FWD/AWD" if source lists both without splitting
                $table->string('transmission_code', 60)->nullable(); // e.g. "U241E" or "U760E/U761E" if source gives an era range
                $table->string('transmission_family', 30)->nullable(); // e.g. "U-Series", "A-Series", "AA-Series", "K-series CVT"
                $table->string('speeds', 10)->nullable();        // "4","5","6","8","CVT" — text, not int, since CVT isn't numeric
                $table->unsignedTinyInteger('pin_count_min')->nullable();
                $table->unsignedTinyInteger('pin_count_max')->nullable();
                $table->string('key_notes', 300)->nullable();
                $table->string('source', 60)->default('toyota_lexus_transmission_master_2026'); // provenance — which seed/import this row came from
                $table->boolean('verified')->default(false); // real market-confirmed (like the Camry pin corrections) vs reference-only
                $table->timestamps();

                $table->index(['make', 'model', 'year_from', 'year_to']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_powertrain_reference');
    }
};
