<?php
// FILE: database/migrations/2024_01_06_improvements.php
// Adds: engine_code, transmission_code, pin_count, fitment_notes, origin_market,
//       gear_alias, extended year range, Nigerian market fields

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Extend parts_inventory ────────────────────────────────
        Schema::table('parts_inventory', function (Blueprint $table) {

            // Engine/transmission codes (e.g. 2ZRFE, U341E, 2ARFE, U760E)
            if (!Schema::hasColumn('parts_inventory', 'engine_code_oem'))
                $table->string('engine_code_oem', 30)->nullable()->after('engine_code')
                      ->comment('OEM engine code e.g. 2ZRFE, 2ARFE, 2GRFE');

            if (!Schema::hasColumn('parts_inventory', 'transmission_code_oem'))
                $table->string('transmission_code_oem', 30)->nullable()->after('engine_code_oem')
                      ->comment('OEM transmission code e.g. U341E, U760E, A750E');

            // Nigerian market: pin count on transmission/gear
            if (!Schema::hasColumn('parts_inventory', 'pin_count'))
                $table->unsignedTinyInteger('pin_count')->nullable()->after('transmission_code_oem')
                      ->comment('Number of pins on transmission connector e.g. 13, 22');

            // "Gear" alias for transmission (Nigerian market term)
            if (!Schema::hasColumn('parts_inventory', 'gear_alias'))
                $table->string('gear_alias', 50)->nullable()->after('pin_count')
                      ->comment('Nigerian market alias e.g. 13-pin gear, 22-pin gear');

            // Origin market
            if (!Schema::hasColumn('parts_inventory', 'origin_market'))
                $table->enum('origin_market', ['JDM','USDM','EDM','Nigerian Used','N/A'])
                      ->default('N/A')->after('origin')
                      ->comment('JDM=Japanese market, USDM=US market etc.');

            // Vehicle fitment notes (like AllStarJDM style)
            if (!Schema::hasColumn('parts_inventory', 'fitment_notes'))
                $table->text('fitment_notes')->nullable()->after('description')
                      ->comment('Full fitment description e.g. Fits 2013-2017 Camry 2.5L L,LE,SE,XLE');

            // Compatibility years extended
            if (!Schema::hasColumn('parts_inventory', 'compat_year_from'))
                $table->smallInteger('compat_year_from')->nullable()->after('year_to')
                      ->comment('Extended compatibility start year (wider than donor year)');

            if (!Schema::hasColumn('parts_inventory', 'compat_year_to'))
                $table->smallInteger('compat_year_to')->nullable()->after('compat_year_from')
                      ->comment('Extended compatibility end year');

            // Trim compatibility
            if (!Schema::hasColumn('parts_inventory', 'compatible_trims'))
                $table->string('compatible_trims', 200)->nullable()->after('trim_level')
                      ->comment('CSV of compatible trims e.g. L,LE,SE,XLE');

            // Not compatible note
            if (!Schema::hasColumn('parts_inventory', 'not_compatible_note'))
                $table->string('not_compatible_note', 200)->nullable()->after('fitment_notes')
                      ->comment('e.g. Not compatible with V6 or Hybrid models');
        });

        // ── Extend donor_vehicles — year range from 1986 ─────────
        // (already a year field, just expand validation in code)

        // ── Payment methods table ─────────────────────────────────
        if (!Schema::hasTable('payment_methods_config')) {
            Schema::create('payment_methods_config', function (Blueprint $table) {
                $table->id();
                $table->string('region', 20); // Nigeria, USA
                $table->string('method_key', 40); // bank_transfer, paystack, zelle etc.
                $table->string('method_label', 80);
                $table->string('account_name', 150)->nullable();
                $table->string('account_number', 80)->nullable();
                $table->string('bank_name', 100)->nullable();
                $table->string('handle', 100)->nullable(); // for Zelle/CashApp/Venmo
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });

            // Seed Nigerian payment methods
            DB::table('payment_methods_config')->insert([
                [
                    'region'        => 'Nigeria',
                    'method_key'    => 'bank_transfer',
                    'method_label'  => 'Bank Transfer (Moniepoint)',
                    'account_name'  => 'GASBOK ENGINEERING NIGERIA LIMITED',
                    'account_number'=> '5085726530',
                    'bank_name'     => 'Moniepoint MFB',
                    'handle'        => null,
                    'is_active'     => true,
                    'sort_order'    => 1,
                    'created_at'    => now(), 'updated_at' => now(),
                ],
                [
                    'region'        => 'Nigeria',
                    'method_key'    => 'pos_instore',
                    'method_label'  => 'Credit/Debit Card POS at Office',
                    'account_name'  => null,
                    'account_number'=> null,
                    'bank_name'     => null,
                    'handle'        => null,
                    'is_active'     => true,
                    'sort_order'    => 2,
                    'created_at'    => now(), 'updated_at' => now(),
                ],
                [
                    'region'        => 'Nigeria',
                    'method_key'    => 'paystack',
                    'method_label'  => 'Pay Online via Paystack',
                    'account_name'  => null,
                    'account_number'=> null,
                    'bank_name'     => null,
                    'handle'        => null,
                    'is_active'     => true,
                    'sort_order'    => 3,
                    'created_at'    => now(), 'updated_at' => now(),
                ],
                // USA payment methods
                [
                    'region'        => 'USA',
                    'method_key'    => 'zelle',
                    'method_label'  => 'Zelle',
                    'account_name'  => 'ACCOLADE AUTOS AND GENERAL LLC',
                    'account_number'=> null,
                    'bank_name'     => null,
                    'handle'        => '5125873425',
                    'is_active'     => true,
                    'sort_order'    => 1,
                    'created_at'    => now(), 'updated_at' => now(),
                ],
                [
                    'region'        => 'USA',
                    'method_key'    => 'cashapp',
                    'method_label'  => 'CashApp',
                    'account_name'  => 'GASBOK AKOLADE',
                    'account_number'=> null,
                    'bank_name'     => null,
                    'handle'        => '$GASBOK',
                    'is_active'     => true,
                    'sort_order'    => 2,
                    'created_at'    => now(), 'updated_at' => now(),
                ],
                [
                    'region'        => 'USA',
                    'method_key'    => 'venmo',
                    'method_label'  => 'Venmo',
                    'account_name'  => null,
                    'account_number'=> null,
                    'bank_name'     => null,
                    'handle'        => '5125873425',
                    'is_active'     => true,
                    'sort_order'    => 3,
                    'created_at'    => now(), 'updated_at' => now(),
                ],
                [
                    'region'        => 'USA',
                    'method_key'    => 'cash',
                    'method_label'  => 'Cash (USD) at Office',
                    'account_name'  => null,
                    'account_number'=> null,
                    'bank_name'     => null,
                    'handle'        => null,
                    'is_active'     => true,
                    'sort_order'    => 4,
                    'created_at'    => now(), 'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods_config');
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->dropColumn([
                'engine_code_oem','transmission_code_oem','pin_count','gear_alias',
                'origin_market','fitment_notes','compat_year_from','compat_year_to',
                'compatible_trims','not_compatible_note'
            ]);
        });
    }
};
