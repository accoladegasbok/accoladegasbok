<?php
// FILE: database/migrations/2026_10_22_000002_create_pin_records.php
//
// One row per physically checked gearbox connector: the donor VIN, a photo of the
// connector, and the terminal count. A pin count is "verified" only after a manager or
// admin approves the record. Verified records override the built-in OemDatabase guess.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pin_records')) return;

        Schema::create('pin_records', function (Blueprint $t) {
            $t->id();
            $t->string('make', 60);
            $t->string('model', 80);
            $t->unsignedSmallInteger('year_from');
            $t->unsignedSmallInteger('year_to');
            $t->string('engine_code', 20)->nullable();
            $t->string('transmission_code', 20)->nullable();
            $t->unsignedTinyInteger('pin_count');
            $t->string('donor_vin', 17);
            $t->string('photo_path', 255);                    // photo of the connector, public disk
            $t->unsignedBigInteger('part_id')->nullable();    // the stock item it was checked on, if any
            $t->text('notes')->nullable();
            $t->string('status', 12)->default('pending');     // pending | verified | rejected
            $t->unsignedBigInteger('submitted_by_staff_id')->nullable();
            $t->string('submitted_by_name', 100)->nullable();
            $t->unsignedBigInteger('verified_by_staff_id')->nullable();
            $t->string('verified_by_name', 100)->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->string('reject_reason', 255)->nullable();
            $t->timestamps();

            $t->index(['make', 'model', 'year_from', 'year_to']);
            $t->index(['transmission_code', 'status']);
            $t->index('donor_vin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pin_records');
    }
};
