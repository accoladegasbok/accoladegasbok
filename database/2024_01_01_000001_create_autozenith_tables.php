<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parts_inventory', function (Blueprint $table) {
            $table->id();
            $table->string('part_code', 20)->unique();

            // Vehicle identification
            $table->enum('brand', [
                'Toyota','Lexus','Kia','Hyundai','Nissan','Mercedes-Benz',
                'Infiniti','Ford','GM','Chevrolet','Acura','VW','Honda'
            ]);
            $table->string('model', 80);
            $table->year('year_from');
            $table->year('year_to');
            $table->string('trim_level', 50)->nullable();
            $table->enum('body_style', ['Sedan','Coupe','SUV','Truck','Wagon','Hatchback','Van','Convertible'])->nullable();
            $table->string('engine_code', 40)->nullable();
            $table->enum('drive_type', ['FWD','RWD','AWD','4WD'])->nullable();
            $table->string('donor_vin', 17)->nullable()->index();

            // Part details
            $table->string('part_name', 150);
            $table->enum('part_category', [
                'Engine','Transmission','Body','Suspension','Electrical',
                'Interior','Cooling','Brakes','Airbag','Fuel','Exhaust','Seat'
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
            $table->string('bin_location', 20)->nullable(); // e.g. A-01-B1

            // Media + notes
            $table->json('photos')->nullable();
            $table->text('description')->nullable();

            // Hollander (future Phase 3)
            $table->string('hollander_number', 20)->nullable();

            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['brand','model','year_from','year_to'], 'idx_vehicle');
            $table->index(['part_category','status'], 'idx_cat_status');
            $table->index(['location','status'], 'idx_loc_status');
        });

        // Compatibility / interchange table
        Schema::create('parts_compatibility', function (Blueprint $table) {
            $table->id();
            $table->string('part_category', 80);
            $table->string('brand', 40);
            $table->string('model', 80);
            $table->year('year_from');
            $table->year('year_to');
            $table->string('body_style_match', 50)->default('All');
            $table->json('also_fits')->nullable();     // [{brand, model, years}]
            $table->json('does_not_fit')->nullable();  // [{body_style}]
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['brand','model','part_category'], 'idx_compat_lookup');
        });

        // Donor vehicles registry
        Schema::create('donor_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vin', 17)->unique();
            $table->year('year');
            $table->string('make', 40);
            $table->string('model', 80);
            $table->string('trim', 50)->nullable();
            $table->string('colour', 50)->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->date('date_acquired')->nullable();
            $table->enum('source', ['Auction','Insurance','Private Sale','Dealer','Other'])->default('Auction');
            $table->enum('condition', ['Good','Fair','Poor'])->default('Good');
            $table->unsignedInteger('parts_harvested')->default(0);
            $table->enum('location', ['Waxahachie TX','Elkhorn WI','Ile-Ife Nigeria','Ibadan Nigeria','Oshodi Lagos','Accra Ghana']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Customer enquiries
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('part_id')->nullable()->index();
            $table->string('customer_name', 100);
            $table->string('customer_phone', 30)->nullable();
            $table->string('customer_email', 150)->nullable();
            $table->string('vehicle_vin', 17)->nullable();
            $table->string('vehicle_desc', 150)->nullable();
            $table->enum('channel', ['WhatsApp','Phone','Email','Website'])->default('WhatsApp');
            $table->text('message')->nullable();
            $table->enum('status', ['New','In Progress','Quoted','Closed'])->default('New')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('donor_vehicles');
        Schema::dropIfExists('parts_compatibility');
        Schema::dropIfExists('parts_inventory');
    }
};
