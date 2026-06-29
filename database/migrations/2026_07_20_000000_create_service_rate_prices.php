<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_rate_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_rate_id');
            $table->string('location', 60);
            $table->decimal('price_local', 14, 2);
            $table->string('currency_code', 5);
            $table->timestamps();

            $table->unique(['service_rate_id', 'location']);
        });

        // Backfill: existing service_rates.default_price was a single
        // global number (almost always entered thinking in USD). Seed
        // one row per existing service for EVERY known location, using
        // that same number as a starting point — admin should review
        // and correct these per location afterward, since a USD labor
        // rate isn't the same number in Naira.
        $locations = ['Waxahachie TX','Kennedale TX','Elkhorn WI','Ile-Ife Nigeria','Ibadan Nigeria','Lagos Nigeria','Abuja Nigeria','Akure Nigeria','Accra Ghana'];
        $services = DB::table('service_rates')->get();

        foreach ($services as $service) {
            foreach ($locations as $location) {
                $currencyCode = str_contains($location, 'Nigeria') ? 'NGN' : (str_contains($location, 'Ghana') ? 'GHS' : 'USD');
                DB::table('service_rate_prices')->insert([
                    'service_rate_id' => $service->id,
                    'location'        => $location,
                    'price_local'     => $service->default_price ?? 0,
                    'currency_code'   => $currencyCode,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_rate_prices');
    }
};
