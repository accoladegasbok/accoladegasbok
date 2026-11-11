<?php
// FILE: database/migrations/2026_11_12_000001_create_attendance_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendance_sites')) {
            Schema::create('attendance_sites', function (Blueprint $t) {
                $t->id();
                $t->string('location', 60)->unique();           // same text as staff.location
                $t->decimal('lat', 10, 7)->nullable();
                $t->decimal('lng', 10, 7)->nullable();
                $t->unsignedInteger('radius_m')->default(150);   // how close staff must be to clock in
                $t->boolean('enforce_geofence')->default(true);
                $t->boolean('require_biometric')->default(false); // true = staff must unlock their registered phone (fingerprint / face)
                $t->string('timezone', 40)->default('Africa/Lagos');
                $t->string('shift_start', 5)->default('08:30');
                $t->unsignedSmallInteger('grace_minutes')->default(15);
                $t->string('working_days', 20)->default('1,2,3,4,5,6'); // ISO: 1 = Monday ... 7 = Sunday
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('attendance_records')) {
            Schema::create('attendance_records', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('staff_id');
                $t->date('work_date');
                $t->string('location', 60)->nullable();
                $t->timestamp('clock_in_at')->nullable();        // server time (UTC), never the phone's clock
                $t->timestamp('clock_out_at')->nullable();
                $t->string('in_photo')->nullable();
                $t->string('out_photo')->nullable();
                $t->decimal('in_lat', 10, 7)->nullable();
                $t->decimal('in_lng', 10, 7)->nullable();
                $t->unsignedInteger('in_accuracy_m')->nullable();
                $t->unsignedInteger('in_distance_m')->nullable();
                $t->decimal('out_lat', 10, 7)->nullable();
                $t->decimal('out_lng', 10, 7)->nullable();
                $t->string('verified_by', 12)->default('selfie'); // passkey (phone fingerprint/face) | selfie
                $t->string('status', 12)->default('present');    // present | late | blocked | excused
                $t->unsignedInteger('late_minutes')->default(0);
                $t->string('note', 255)->nullable();
                $t->string('ip', 45)->nullable();
                $t->timestamps();
                $t->unique(['staff_id', 'work_date']);
                $t->index('work_date');
            });
        }

        if (!Schema::hasTable('staff_passkeys')) {
            Schema::create('staff_passkeys', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('staff_id')->index();
                $t->string('credential_id', 255)->unique();      // base64url
                $t->text('public_key');                           // PEM
                $t->unsignedBigInteger('sign_count')->default(0);
                $t->string('label', 80)->nullable();              // e.g. "Tecno Spark - Ade"
                $t->string('user_agent', 255)->nullable();
                $t->boolean('approved')->default(false);          // manager/admin approves each phone
                $t->unsignedBigInteger('approved_by')->nullable();
                $t->timestamp('last_used_at')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_passkeys');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_sites');
    }
};
