<?php
// FILE: database/migrations/2026_07_05_000001_fix_donor_vehicles_location_enum.php
//
// Root cause of the Lagos harvest 500 error:
// donor_vehicles.location was an ENUM containing 'Oshodi Lagos', but
// every controller/view in the app (HarvestController, InventoryController,
// most blade views) sends 'Lagos Nigeria' — a mismatched string, which
// MySQL silently truncates to '' under an ENUM column, throwing:
//   SQLSTATE[01000]: Warning: 1265 Data truncated for column 'location'
//
// This also adds 'Abuja Nigeria' and 'Akure Nigeria', which were missing
// from this enum entirely (same bug would hit those locations too, just
// untested until now).
//
// Run with: php artisan migrate --force

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: widen the enum to include BOTH old and new values, so
        // existing rows using 'Oshodi Lagos' don't get silently truncated
        // during the rename step below.
        DB::statement("ALTER TABLE donor_vehicles MODIFY COLUMN location ENUM(
            'Waxahachie TX',
            'Elkhorn WI',
            'Ile-Ife Nigeria',
            'Ibadan Nigeria',
            'Oshodi Lagos',
            'Lagos Nigeria',
            'Abuja Nigeria',
            'Akure Nigeria',
            'Accra Ghana'
        ) NOT NULL");

        // Step 2: migrate any existing 'Oshodi Lagos' rows to the
        // standard 'Lagos Nigeria' naming used everywhere else.
        DB::table('donor_vehicles')
            ->where('location', 'Oshodi Lagos')
            ->update(['location' => 'Lagos Nigeria']);

        // Step 3: now safe to drop the old value entirely.
        DB::statement("ALTER TABLE donor_vehicles MODIFY COLUMN location ENUM(
            'Waxahachie TX',
            'Elkhorn WI',
            'Ile-Ife Nigeria',
            'Ibadan Nigeria',
            'Lagos Nigeria',
            'Abuja Nigeria',
            'Akure Nigeria',
            'Accra Ghana'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE donor_vehicles MODIFY COLUMN location ENUM(
            'Waxahachie TX',
            'Elkhorn WI',
            'Ile-Ife Nigeria',
            'Ibadan Nigeria',
            'Oshodi Lagos',
            'Accra Ghana'
        ) NOT NULL");

        DB::table('donor_vehicles')
            ->where('location', 'Lagos Nigeria')
            ->update(['location' => 'Oshodi Lagos']);
    }
};
