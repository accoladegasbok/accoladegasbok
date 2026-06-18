<?php
// FILE: database/migrations/2024_01_01_000003_create_parts_tables.php
// Run BEFORE the staff/harvest migration — creates donor_vehicles and parts_inventory

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Donor vehicles ────────────────────────────────────────
        if (!Schema::hasTable('donor_vehicles')) {
            Schema::create('donor_vehicles', function (Blueprint $table) {
                $table->id();
                $table->string('vin', 17)->unique();
                $table->year('year');
                $table->string('make', 60);
                $table->string('model', 80);
                $table->string('trim', 60)->nullable();
                $table->string('colour', 50)->nullable();
                $table->string('body_style', 60)->nullable();
                $table->unsignedInteger('mileage')->nullable();
                $table->date('date_acquired')->nullable();
                $table->enum('source', ['Auction','Insurance','Private Sale','Dealer','Other'])->default('Auction');
                $table->enum('condition', ['Good','Fair','Poor'])->default('Good');
                $table->unsignedInteger('parts_harvested')->default(0);
                $table->enum('location', [
                    'Waxahachie TX','Elkhorn WI',
                    'Ile-Ife Nigeria','Ibadan Nigeria','Oshodi Lagos','Accra Ghana'
                ]);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // ── Parts inventory ───────────────────────────────────────
        if (!Schema::hasTable('parts_inventory')) {
            Schema::create('parts_inventory', function (Blueprint $table) {
                $table->id();
                $table->string('part_code', 20)->unique();

                // Vehicle identification
                $table->enum('brand', [
                    'Toyota','Lexus','Kia','Hyundai','Nissan','Mercedes-Benz',
                    'Infiniti','Ford','GM','Chevrolet','Acura','VW','Honda'
                ]);
                $table->string('model', 80);
                $table->smallInteger('year_from');
                $table->smallInteger('year_to');
                $table->string('trim_level', 50)->nullable();
                $table->string('body_style', 60)->nullable();
                $table->string('engine_code', 40)->nullable();
                $table->enum('drive_type', ['FWD','RWD','AWD','4WD'])->nullable();
                $table->string('donor_vin', 17)->nullable()->index();

                // Part details
                $table->string('part_name', 150);
                $table->enum('part_category', [
                    'Engine','Transmission','Body','Suspension','Electrical',
                    'Interior','Cooling','Brakes','Airbag','Fuel','Exhaust',
                    'Seat','Wheels'
                ])->index();
                $table->enum('side', ['D/S','P/S','N/A'])->default('N/A');
                $table->string('airbag_position', 80)->nullable();
                $table->string('seat_type', 100)->nullable();
                $table->enum('origin', ['Japan Built','North America Built','N/A'])->default('N/A');
                $table->string('oem_part_number', 80)->nullable()->index();
                $table->string('colour', 50)->nullable();

                // Condition + stock
                $table->enum('condition_grade', ['A','B','C','New'])->index();
                $table->unsignedInteger('mileage')->nullable();
                $table->integer('stock_qty')->default(1);
                $table->enum('status', ['Available','Reserved','Sold'])->default('Available')->index();

                // Pricing
                $table->decimal('price_usd', 10, 2);

                // Location
                $table->enum('location', [
                    'Waxahachie TX','Elkhorn WI',
                    'Ile-Ife Nigeria','Ibadan Nigeria','Oshodi Lagos','Accra Ghana'
                ])->index();
                $table->string('bin_location', 20)->nullable();

                // Media + notes
                $table->json('photos')->nullable();
                $table->text('description')->nullable();
                $table->string('hollander_number', 20)->nullable();

                $table->timestamps();

                // Composite indexes
                $table->index(['brand','model','year_from','year_to'], 'idx_vehicle');
                $table->index(['part_category','status'], 'idx_cat_status');
                $table->index(['location','status'], 'idx_loc_status');
            });
        }

        // ── Parts compatibility ────────────────────────────────────
        if (!Schema::hasTable('parts_compatibility')) {
            Schema::create('parts_compatibility', function (Blueprint $table) {
                $table->id();
                $table->string('part_category', 100)->index();
                $table->string('part_subcategory', 100)->nullable();
                $table->string('brand', 50)->index();
                $table->string('model', 80)->index();
                $table->smallInteger('year_from');
                $table->smallInteger('year_to');
                $table->string('body_style_match', 80)->default('All');
                $table->string('engine_match', 80)->nullable();
                $table->string('side_match', 20)->nullable();
                $table->json('also_fits')->nullable();
                $table->json('does_not_fit')->nullable();
                $table->string('interchange_note', 255)->nullable();
                $table->boolean('verified')->default(true);
                $table->timestamps();
                $table->index(['brand','model','part_category'], 'idx_compat_bmc');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('parts_compatibility');
        Schema::dropIfExists('parts_inventory');
        Schema::dropIfExists('donor_vehicles');
    }
};
