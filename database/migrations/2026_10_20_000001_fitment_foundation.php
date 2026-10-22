<?php
// FILE: database/migrations/2026_10_20_000001_fitment_foundation.php
//
// Fitment data foundation. Adds the fields that let a part be matched on the right
// basis (engine, gearbox, drive type, side, lighting) plus a STAGING table where
// imported fitment lists wait for staff review. Nothing here is shown to customers.
// Every change is guarded, so running it twice or on a partly updated database is safe.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // What the part itself is
        if (Schema::hasTable('parts_inventory')) {
            Schema::table('parts_inventory', function (Blueprint $t) {
                if (!Schema::hasColumn('parts_inventory', 'engine_config'))       $t->string('engine_config', 8)->nullable();            // I4, V6, V8, H4
                if (!Schema::hasColumn('parts_inventory', 'transmission_speeds')) $t->unsignedTinyInteger('transmission_speeds')->nullable();
                if (!Schema::hasColumn('parts_inventory', 'lighting_type'))       $t->string('lighting_type', 20)->nullable();           // Halogen, HID, LED
            });
        }

        // What each fitment claim says about the vehicle
        if (Schema::hasTable('part_interchange_vehicles')) {
            Schema::table('part_interchange_vehicles', function (Blueprint $t) {
                if (!Schema::hasColumn('part_interchange_vehicles', 'engine_code'))       $t->string('engine_code', 20)->nullable();
                if (!Schema::hasColumn('part_interchange_vehicles', 'transmission_code')) $t->string('transmission_code', 20)->nullable();
                if (!Schema::hasColumn('part_interchange_vehicles', 'pin_count'))         $t->unsignedTinyInteger('pin_count')->nullable();
                if (!Schema::hasColumn('part_interchange_vehicles', 'drive_type'))        $t->string('drive_type', 12)->nullable();
                if (!Schema::hasColumn('part_interchange_vehicles', 'side'))              $t->string('side', 12)->nullable();
                if (!Schema::hasColumn('part_interchange_vehicles', 'lighting_type'))     $t->string('lighting_type', 20)->nullable();
                // direct_oem | functional_substitute | requires_modification
                if (!Schema::hasColumn('part_interchange_vehicles', 'match_type'))        $t->string('match_type', 30)->default('direct_oem');
            });
            Schema::table('part_interchange_vehicles', function (Blueprint $t) {
                try { $t->index('engine_code', 'piv_engine_code_idx'); } catch (\Throwable $e) {}
                try { $t->index('transmission_code', 'piv_trans_code_idx'); } catch (\Throwable $e) {}
            });
        }

        // Evidence can point at one vehicle row instead of the whole group
        if (Schema::hasTable('interchange_evidence') && !Schema::hasColumn('interchange_evidence', 'vehicle_row_id')) {
            Schema::table('interchange_evidence', function (Blueprint $t) {
                $t->unsignedBigInteger('vehicle_row_id')->nullable();
            });
        }

        // Imported lists wait here. Staff review them; none of it is public.
        if (!Schema::hasTable('fitment_staging')) {
            Schema::create('fitment_staging', function (Blueprint $t) {
                $t->id();
                $t->string('source', 60);
                $t->unsignedInteger('source_row')->nullable();
                $t->string('part_name', 150)->nullable();
                $t->string('part_category', 60)->nullable();
                $t->string('make', 60)->nullable();
                $t->string('model', 80)->nullable();
                $t->string('donor_years', 30)->nullable();
                $t->string('engine_ref', 80)->nullable();
                $t->string('transmission_ref', 80)->nullable();
                $t->string('candidate_group', 120)->nullable();
                $t->string('status', 20)->default('staged');   // staged | approved | rejected
                $t->text('notes')->nullable();
                $t->timestamps();
                $t->index(['make', 'model']);
                $t->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fitment_staging');
        if (Schema::hasTable('interchange_evidence') && Schema::hasColumn('interchange_evidence', 'vehicle_row_id')) {
            Schema::table('interchange_evidence', fn (Blueprint $t) => $t->dropColumn('vehicle_row_id'));
        }
        if (Schema::hasTable('part_interchange_vehicles')) {
            Schema::table('part_interchange_vehicles', function (Blueprint $t) {
                try { $t->dropIndex('piv_engine_code_idx'); } catch (\Throwable $e) {}
                try { $t->dropIndex('piv_trans_code_idx'); } catch (\Throwable $e) {}
                foreach (['engine_code','transmission_code','pin_count','drive_type','side','lighting_type','match_type'] as $c) {
                    if (Schema::hasColumn('part_interchange_vehicles', $c)) $t->dropColumn($c);
                }
            });
        }
        if (Schema::hasTable('parts_inventory')) {
            Schema::table('parts_inventory', function (Blueprint $t) {
                foreach (['engine_config','transmission_speeds','lighting_type'] as $c) {
                    if (Schema::hasColumn('parts_inventory', $c)) $t->dropColumn($c);
                }
            });
        }
    }
};
