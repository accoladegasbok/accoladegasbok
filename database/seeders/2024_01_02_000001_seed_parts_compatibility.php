<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * ============================================================================
 * Auto Zenith Parts — Parts Compatibility & Interchange Migration + Seeder
 * ============================================================================
 * Run: php artisan migrate
 * Then: php artisan db:seed --class=PartsCompatibilitySeeder
 *
 * Or run this migration which includes inline seeding:
 *   php artisan migrate --path=database/migrations/2024_01_02_000001_seed_parts_compatibility.php
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        // Ensure table exists (safe to run even if already created)
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
                $table->string('side_match', 20)->nullable();       // D/S, P/S, Both, N/A
                $table->json('also_fits')->nullable();               // [{brand, model, year_from, year_to, notes}]
                $table->json('does_not_fit')->nullable();            // [{brand, model, reason}]
                $table->string('interchange_note', 255)->nullable(); // paint code, trim check, etc.
                $table->boolean('verified')->default(true);
                $table->timestamps();

                $table->index(['brand', 'model', 'part_category'], 'idx_compat_bmc');
                $table->index(['part_category', 'year_from', 'year_to'], 'idx_compat_year');
            });
        }

        // ── SEED ALL COMPATIBILITY DATA ──────────────────────────────────
        $this->seedAll();
    }

    public function down(): void
    {
        Schema::dropIfExists('parts_compatibility');
    }

    // =========================================================================
    // SEED METHOD — inserts all compatibility + interchange records
    // =========================================================================
    private function seedAll(): void
    {
        $now = now();

        // Helper: build a record
        $r = function (
            string $category,
            string $brand,
            string $model,
            int    $yFrom,
            int    $yTo,
            array  $alsoFits     = [],
            string $bodyStyle    = 'All',
            string $note         = '',
            ?string $subcategory = null,
            ?string $engineMatch = null,
            ?string $sideMatch   = null,
            array  $doesNotFit   = []
        ) use ($now): array {
            return [
                'part_category'    => $category,
                'part_subcategory' => $subcategory,
                'brand'            => $brand,
                'model'            => $model,
                'year_from'        => $yFrom,
                'year_to'          => $yTo,
                'body_style_match' => $bodyStyle,
                'engine_match'     => $engineMatch,
                'side_match'       => $sideMatch,
                'also_fits'        => json_encode($alsoFits),
                'does_not_fit'     => json_encode($doesNotFit),
                'interchange_note' => $note,
                'verified'         => true,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        };

        $records = [];

        // =====================================================================
        // TOYOTA
        // =====================================================================

        // ── Camry (XV70 generation 2018–2024) ────────────────────────────────
        $records[] = $r('Body', 'Toyota', 'Camry', 2018, 2024,
            [['brand'=>'Lexus','model'=>'ES350','year_from'=>2019,'year_to'=>2024,'notes'=>'Check paint code — body panels do not swap']],
            'Sedan', 'Body panels: Sedan only. Pre-facelift 2018-2021 differs from 2022-2024 facelift.', 'Bumper/Hood/Doors');

        $records[] = $r('Body', 'Toyota', 'Camry', 2018, 2021,
            [], 'Sedan', 'Front bumper: 2018-2021 pre-facelift only. Does NOT fit 2022+.', 'Front Bumper Cover');

        $records[] = $r('Body', 'Toyota', 'Camry', 2022, 2024,
            [], 'Sedan', 'Front bumper: 2022-2024 facelift only. Does NOT fit pre-2022.', 'Front Bumper Cover');

        $records[] = $r('Body', 'Toyota', 'Camry', 2018, 2024,
            [], 'Sedan', 'Tail lamp: Sedan only — Coupe (if applicable) uses different assembly.', 'Tail Lamp Assembly',
            null, 'Both');

        $records[] = $r('Engine', 'Toyota', 'Camry', 2018, 2024,
            [['brand'=>'Lexus','model'=>'ES350','year_from'=>2019,'year_to'=>2024,'notes'=>'TNGA-K platform shared — engine mounts, accessories compatible'],
             ['brand'=>'Toyota','model'=>'Avalon','year_from'=>2019,'year_to'=>2022,'notes'=>'Same 3.5L V6 engine family components']],
            'All', 'Engine: 2.5L (A25A-FXS) or 3.5L (2GR-FKS). Verify engine code before ordering.', 'Engine Assembly',
            '2.5L I4 / 3.5L V6');

        $records[] = $r('Transmission', 'Toyota', 'Camry', 2018, 2024,
            [['brand'=>'Lexus','model'=>'ES350','year_from'=>2019,'year_to'=>2024,'notes'=>'8-speed auto shared on V6 variants'],
             ['brand'=>'Toyota','model'=>'Avalon','year_from'=>2019,'year_to'=>2022,'notes'=>'Same 8-speed auto on 3.5L']],
            'All', '8-speed automatic (V6) or CVT (4-cyl). Must match engine type.', 'Transmission');

        $records[] = $r('Suspension', 'Toyota', 'Camry', 2018, 2024,
            [['brand'=>'Lexus','model'=>'ES350','year_from'=>2019,'year_to'=>2024,'notes'=>'TNGA-K front struts largely compatible — verify spring rates'],
             ['brand'=>'Toyota','model'=>'Avalon','year_from'=>2019,'year_to'=>2022,'notes'=>'Shared TNGA-K front suspension components']],
            'All', 'Front strut assembly. FWD only — AWD Camry uses different rear diff and suspension.',
            'Front Strut Assembly', null, 'Both');

        $records[] = $r('Suspension', 'Toyota', 'Camry', 2018, 2024,
            [], 'All', 'Lower control arm. Verify FWD vs AWD — different geometry.', 'Lower Control Arm');

        $records[] = $r('Interior', 'Toyota', 'Camry', 2018, 2021,
            [], 'Sedan', 'Dashboard: Pre-facelift only (2018-2021). 2022+ dash redesigned.', 'Dashboard Assembly');

        $records[] = $r('Interior', 'Toyota', 'Camry', 2018, 2024,
            [], 'Sedan', 'Front seats: Match cloth vs leather AND electric vs manual AND Japan vs N.America built. All 4 factors must match.', 'Front Seat Assembly');

        $records[] = $r('Electrical', 'Toyota', 'Camry', 2018, 2024,
            [['brand'=>'Lexus','model'=>'ES350','year_from'=>2019,'year_to'=>2024,'notes'=>'Alternator compatible on same engine variants']],
            'All', 'Alternator: Match engine type (2.5L vs 3.5L) and voltage output.', 'Alternator');

        $records[] = $r('Cooling', 'Toyota', 'Camry', 2018, 2024,
            [['brand'=>'Lexus','model'=>'ES350','year_from'=>2019,'year_to'=>2024,'notes'=>'Radiator compatible on TNGA-K platform']],
            'All', 'Radiator: 2.5L and 3.5L use different sizes. Match engine.', 'Radiator');

        $records[] = $r('Airbag', 'Toyota', 'Camry', 2018, 2024,
            [], 'Sedan', 'CRITICAL: Airbags must match EXACT position, seat type (cloth/leather), body style (Japan/NA built) and model year. Never interchange across brands or generations.', 'Airbag Assembly');

        // ── RAV4 (XA50 generation 2019–2024) ────────────────────────────────
        $records[] = $r('Body', 'Toyota', 'RAV4', 2019, 2024,
            [], 'SUV', 'Body panels: 2019-2021 pre-facelift differs from 2022-2024 facelift. Headlights and front bumper not interchangeable across facelift.', 'Front Bumper/Hood');

        $records[] = $r('Body', 'Toyota', 'RAV4', 2019, 2021,
            [], 'SUV', 'Pre-facelift front headlight assembly. Does NOT fit 2022+ RAV4.', 'Headlight Assembly', null, 'Both');

        $records[] = $r('Body', 'Toyota', 'RAV4', 2022, 2024,
            [], 'SUV', 'Facelift headlight assembly. Does NOT fit 2019-2021.', 'Headlight Assembly', null, 'Both');

        $records[] = $r('Engine', 'Toyota', 'RAV4', 2019, 2024,
            [['brand'=>'Lexus','model'=>'NX350','year_from'=>2022,'year_to'=>2024,'notes'=>'T24A-FTS 2.4T engine family components shared'],
             ['brand'=>'Toyota','model'=>'Venza','year_from'=>2021,'year_to'=>2024,'notes'=>'A25A-FXS hybrid engine accessories shared']],
            'All', 'Engine: 2.5L hybrid (A25A-FXS) or 2.5L gas or 2.4T (T24A-FTS). Must match variant.', 'Engine Assembly');

        $records[] = $r('Suspension', 'Toyota', 'RAV4', 2019, 2024,
            [['brand'=>'Lexus','model'=>'NX250','year_from'=>2022,'year_to'=>2024,'notes'=>'TNGA-K platform front struts may be compatible — verify'],
             ['brand'=>'Toyota','model'=>'Venza','year_from'=>2021,'year_to'=>2024,'notes'=>'Shared TNGA-K platform front struts']],
            'All', 'Front strut. AWD vs FWD uses different rear suspension — verify drivetrain.', 'Front Strut Assembly');

        // ── Corolla (E210 generation 2019–2024) ──────────────────────────────
        $records[] = $r('Body', 'Toyota', 'Corolla', 2019, 2024,
            [], 'Sedan', 'Corolla Sedan body panels: does NOT fit Corolla Hatchback (different body).', 'Body Panels');

        $records[] = $r('Engine', 'Toyota', 'Corolla', 2019, 2024,
            [['brand'=>'Toyota','model'=>'C-HR','year_from'=>2018,'year_to'=>2022,'notes'=>'2ZR-FBE / M20A engine accessories may be shared'],
             ['brand'=>'Toyota','model'=>'Prius','year_from'=>2019,'year_to'=>2022,'notes'=>'Hybrid A25A-FXS engine accessories shared on hybrid Corolla']],
            'All', 'Engine: 2.0L M20A-FKS or 1.8L 2ZR-FBE (hybrid). Match exactly.', 'Engine Assembly');

        $records[] = $r('Suspension', 'Toyota', 'Corolla', 2019, 2024,
            [], 'Sedan', 'Front strut: TNGA-C platform. Does NOT fit pre-2019 Corolla (E160/E170).', 'Front Strut Assembly');

        // ── Tacoma (3rd gen 2016–2023) ───────────────────────────────────────
        $records[] = $r('Body', 'Toyota', 'Tacoma', 2016, 2023,
            [], 'Truck', 'Front bumper: 2016-2019 differs from 2020-2023. Verify year before ordering.', 'Front Bumper Cover');

        $records[] = $r('Engine', 'Toyota', 'Tacoma', 2016, 2023,
            [['brand'=>'Toyota','model'=>'4Runner','year_from'=>2016,'year_to'=>2022,'notes'=>'1GR-FE V6 engine accessories largely shared'],
             ['brand'=>'Toyota','model'=>'Tundra','year_from'=>2014,'year_to'=>2021,'notes'=>'2TR-FE 4cyl some accessories shared']],
            'All', 'Engine: 2.7L 2TR-FE or 3.5L 2GR-FKS. Match engine variant.', 'Engine Assembly');

        $records[] = $r('Body', 'Toyota', 'Tacoma', 2016, 2019,
            [], 'Truck', 'Tail lamp: 2016-2019 pre-facelift only.', 'Tail Lamp Assembly', null, 'Both');

        $records[] = $r('Body', 'Toyota', 'Tacoma', 2020, 2023,
            [], 'Truck', 'Tail lamp: 2020-2023 facelift only.', 'Tail Lamp Assembly', null, 'Both');

        // ── Highlander (XU70 2020–2024) ──────────────────────────────────────
        $records[] = $r('Engine', 'Toyota', 'Highlander', 2020, 2024,
            [['brand'=>'Lexus','model'=>'RX350','year_from'=>2020,'year_to'=>2022,'notes'=>'2GR-FKS 3.5L V6 engine accessories shared on TNGA-K platform'],
             ['brand'=>'Toyota','model'=>'Sienna','year_from'=>2021,'year_to'=>2024,'notes'=>'A25A-FXS hybrid engine accessories shared']],
            'All', 'Engine: 3.5L V6 or 2.5L hybrid. Match variant exactly.', 'Engine Assembly');

        // =====================================================================
        // LEXUS
        // =====================================================================

        // ── ES350 (XZ10 2019–2024) ───────────────────────────────────────────
        $records[] = $r('Engine', 'Lexus', 'ES350', 2019, 2024,
            [['brand'=>'Toyota','model'=>'Camry','year_from'=>2018,'year_to'=>2024,'notes'=>'TNGA-K — 2GR-FKS V6 engine accessories compatible'],
             ['brand'=>'Toyota','model'=>'Avalon','year_from'=>2019,'year_to'=>2022,'notes'=>'Same 2GR-FKS V6 engine family']],
            'Sedan', 'Engine accessories, mounts, alternator: TNGA-K platform shared with Camry V6. Body panels do NOT cross.', 'Engine Assembly',
            '3.5L V6');

        $records[] = $r('Transmission', 'Lexus', 'ES350', 2019, 2024,
            [['brand'=>'Toyota','model'=>'Camry','year_from'=>2018,'year_to'=>2024,'notes'=>'8-speed auto shared on V6 variants'],
             ['brand'=>'Toyota','model'=>'Avalon','year_from'=>2019,'year_to'=>2022,'notes'=>'Same 8-speed automatic']],
            'Sedan', '8-speed automatic shared with Camry V6 and Avalon on TNGA-K.', 'Transmission');

        $records[] = $r('Suspension', 'Lexus', 'ES350', 2019, 2024,
            [['brand'=>'Toyota','model'=>'Camry','year_from'=>2018,'year_to'=>2024,'notes'=>'TNGA-K front struts — verify spring rates differ (Lexus tuned firmer)']],
            'Sedan', 'Front strut: TNGA-K shared with Camry. Lexus uses different spring rates — spring not interchangeable, strut body may be.', 'Front Strut Assembly');

        $records[] = $r('Cooling', 'Lexus', 'ES350', 2019, 2024,
            [['brand'=>'Toyota','model'=>'Camry','year_from'=>2018,'year_to'=>2024,'notes'=>'Radiator compatible on same engine — verify fitment tabs']],
            'Sedan', 'Radiator: TNGA-K platform. Compatible with Camry V6 radiator — verify tab positions.', 'Radiator');

        $records[] = $r('Electrical', 'Lexus', 'ES350', 2019, 2024,
            [['brand'=>'Toyota','model'=>'Camry','year_from'=>2018,'year_to'=>2024,'notes'=>'Alternator: same 2GR-FKS engine — compatible']],
            'Sedan', 'Alternator: same engine family as Camry V6 — compatible unit.', 'Alternator');

        $records[] = $r('Body', 'Lexus', 'ES350', 2019, 2024,
            [], 'Sedan', 'Body panels: Lexus-specific — do NOT interchange with Toyota Camry despite shared platform.', 'Body Panels');

        // ── NX (AZ20 2022–2024) ──────────────────────────────────────────────
        $records[] = $r('Engine', 'Lexus', 'NX350', 2022, 2024,
            [['brand'=>'Toyota','model'=>'RAV4','year_from'=>2022,'year_to'=>2024,'notes'=>'T24A-FTS 2.4T engine accessories shared on TNGA-K'],
             ['brand'=>'Toyota','model'=>'Highlander','year_from'=>2022,'year_to'=>2024,'notes'=>'Similar platform — some engine accessories compatible']],
            'SUV', '2.4T engine: T24A-FTS shared with RAV4 Turbo. Some engine accessories interchangeable.', 'Engine Assembly',
            '2.4L Turbo');

        $records[] = $r('Engine', 'Lexus', 'NX250', 2022, 2024,
            [['brand'=>'Toyota','model'=>'RAV4','year_from'=>2019,'year_to'=>2024,'notes'=>'A25A-FKS 2.5L engine accessories shared']],
            'SUV', '2.5L naturally aspirated: A25A-FKS shared with RAV4 2.5L.', 'Engine Assembly', '2.5L I4');

        $records[] = $r('Suspension', 'Lexus', 'NX350', 2022, 2024,
            [['brand'=>'Toyota','model'=>'RAV4','year_from'=>2019,'year_to'=>2024,'notes'=>'TNGA-K front strut body compatible — springs differ']],
            'SUV', 'Front strut: TNGA-K shared with RAV4. Spring rates differ — springs not interchangeable.', 'Front Strut Assembly');

        // ── RX (AL20 2016–2022) ──────────────────────────────────────────────
        $records[] = $r('Engine', 'Lexus', 'RX350', 2016, 2022,
            [['brand'=>'Toyota','model'=>'Highlander','year_from'=>2014,'year_to'=>2019,'notes'=>'2GR-FKS V6 engine accessories shared'],
             ['brand'=>'Toyota','model'=>'Sienna','year_from'=>2015,'year_to'=>2020,'notes'=>'2GR-FE V6 — some accessories compatible']],
            'SUV', 'V6 engine accessories: compatible with Highlander and Sienna same-generation 3.5L.', 'Engine Assembly',
            '3.5L V6');

        $records[] = $r('Body', 'Lexus', 'RX350', 2016, 2019,
            [], 'SUV', 'Body panels: 2016-2019 vs 2020-2022 are different generations — not interchangeable.', 'Body Panels');

        $records[] = $r('Body', 'Lexus', 'RX350', 2020, 2022,
            [], 'SUV', 'Body panels: 2020-2022 (facelift) only.', 'Body Panels');

        // =====================================================================
        // HONDA
        // =====================================================================

        // ── Accord (10th gen 2018–2022) ──────────────────────────────────────
        $records[] = $r('Body', 'Honda', 'Accord', 2018, 2022,
            [], 'Sedan', 'Body panels: Sedan and Coupe/Hatchback variants are different — verify body style. 2021-2022 Sport facelift has different front end.', 'Body Panels');

        $records[] = $r('Body', 'Honda', 'Accord', 2018, 2020,
            [], 'Sedan', 'Front bumper cover: 2018-2020 only. 2021+ has revised front fascia.', 'Front Bumper Cover');

        $records[] = $r('Body', 'Honda', 'Accord', 2021, 2022,
            [], 'Sedan', 'Front bumper cover: 2021-2022 facelift only. Does not fit 2018-2020.', 'Front Bumper Cover');

        $records[] = $r('Engine', 'Honda', 'Accord', 2018, 2022,
            [['brand'=>'Acura','model'=>'TLX','year_from'=>2021,'year_to'=>2023,'notes'=>'K24C/K20C2 engine — some accessories compatible'],
             ['brand'=>'Honda','model'=>'CR-V','year_from'=>2017,'year_to'=>2022,'notes'=>'1.5T L15B7 — engine accessories largely shared'],
             ['brand'=>'Honda','model'=>'Passport','year_from'=>2019,'year_to'=>2022,'notes'=>'3.5L J35Y6 V6 accessories shared on V6 Accord']],
            'All', 'Engine: 1.5T (L15B7), 2.0T (K20C4), or 3.5L V6. Accessories only cross within same engine family.', 'Engine Assembly');

        $records[] = $r('Transmission', 'Honda', 'Accord', 2018, 2022,
            [['brand'=>'Acura','model'=>'TLX','year_from'=>2021,'year_to'=>2023,'notes'=>'10-speed auto shared on 2.0T variants — verify fitment'],
             ['brand'=>'Honda','model'=>'Passport','year_from'=>2019,'year_to'=>2022,'notes'=>'9-speed auto on V6 shared']],
            'All', 'CVT (1.5T), 10-speed auto (2.0T), or 9-speed auto (V6). Must match engine.', 'Transmission');

        $records[] = $r('Suspension', 'Honda', 'Accord', 2018, 2022,
            [['brand'=>'Acura','model'=>'TLX','year_from'=>2021,'year_to'=>2023,'notes'=>'Front strut assembly — platform shared, spring rates differ']],
            'Sedan', 'Front strut: FWD Accord shares platform geometry with TLX FWD. AWD TLX is different.', 'Front Strut Assembly');

        $records[] = $r('Electrical', 'Honda', 'Accord', 2018, 2022,
            [['brand'=>'Honda','model'=>'CR-V','year_from'=>2017,'year_to'=>2022,'notes'=>'Alternator compatible on L15B7 1.5T variants'],
             ['brand'=>'Acura','model'=>'TLX','year_from'=>2021,'year_to'=>2023,'notes'=>'Alternator on 2.0T K20C may be compatible']],
            'All', 'Alternator: match engine type (1.5T vs 2.0T vs V6).', 'Alternator');

        $records[] = $r('Cooling', 'Honda', 'Accord', 2018, 2022,
            [['brand'=>'Honda','model'=>'CR-V','year_from'=>2017,'year_to'=>2022,'notes'=>'Radiator compatible on 1.5T — verify tank positions']],
            'All', 'Radiator: 1.5T and 2.0T use different sizes. V6 uses larger unit.', 'Radiator');

        // ── Civic (10th gen 2016–2021) ───────────────────────────────────────
        $records[] = $r('Body', 'Honda', 'Civic', 2016, 2021,
            [], 'All', 'Body panels: Sedan, Coupe, and Hatchback all differ — body style must match exactly.', 'Body Panels');

        $records[] = $r('Body', 'Honda', 'Civic', 2016, 2018,
            [], 'Sedan', 'Front bumper: 2016-2018 only. 2019-2021 Sport has revised front.', 'Front Bumper Cover');

        $records[] = $r('Engine', 'Honda', 'Civic', 2016, 2021,
            [['brand'=>'Acura','model'=>'ILX','year_from'=>2016,'year_to'=>2022,'notes'=>'K20C2 engine accessories shared — Acura uses sportier tune'],
             ['brand'=>'Honda','model'=>'CR-V','year_from'=>2017,'year_to'=>2022,'notes'=>'L15B7 1.5T — engine accessories shared across platforms']],
            'All', 'Engine: 2.0L naturally aspirated (R20A) or 1.5T (L15B7) or 1.5T Si (L15B7 Sport). Verify code.', 'Engine Assembly');

        $records[] = $r('Suspension', 'Honda', 'Civic', 2016, 2021,
            [['brand'=>'Acura','model'=>'ILX','year_from'=>2016,'year_to'=>2022,'notes'=>'Front strut assembly — Civic platform shared, ILX sport-tuned']],
            'All', 'Front strut: Sedan/Coupe/Hatchback share same strut assembly.', 'Front Strut Assembly');

        // ── CR-V (5th gen 2017–2022) ─────────────────────────────────────────
        $records[] = $r('Engine', 'Honda', 'CR-V', 2017, 2022,
            [['brand'=>'Honda','model'=>'Accord','year_from'=>2018,'year_to'=>2022,'notes'=>'L15B7 1.5T engine accessories shared'],
             ['brand'=>'Honda','model'=>'Civic','year_from'=>2016,'year_to'=>2021,'notes'=>'L15B7 accessories shared'],
             ['brand'=>'Acura','model'=>'RDX','year_from'=>2019,'year_to'=>2022,'notes'=>'K20C4 2.0T — different engine but some accessories may cross']],
            'SUV', 'Engine: 1.5T L15B7 or 2.4L K24W2. Most accessories cross within 1.5T Honda family.', 'Engine Assembly');

        $records[] = $r('Body', 'Honda', 'CR-V', 2017, 2019,
            [], 'SUV', 'Front bumper: 2017-2019 pre-facelift only.', 'Front Bumper Cover');

        $records[] = $r('Body', 'Honda', 'CR-V', 2020, 2022,
            [], 'SUV', 'Front bumper: 2020-2022 facelift only.', 'Front Bumper Cover');

        // =====================================================================
        // ACURA
        // =====================================================================

        // ── TLX (2nd gen 2021–2023) ──────────────────────────────────────────
        $records[] = $r('Engine', 'Acura', 'TLX', 2021, 2023,
            [['brand'=>'Honda','model'=>'Accord','year_from'=>2018,'year_to'=>2022,'notes'=>'K20C4 2.0T engine accessories may be compatible — verify'],
             ['brand'=>'Acura','model'=>'RDX','year_from'=>2019,'year_to'=>2022,'notes'=>'K20C4 engine shared — engine accessories compatible']],
            'Sedan', '2.0T K20C4 or 3.0T J30A. Accessories cross within same engine family.', 'Engine Assembly',
            '2.0L Turbo / 3.0L Turbo');

        $records[] = $r('Suspension', 'Acura', 'TLX', 2021, 2023,
            [['brand'=>'Honda','model'=>'Accord','year_from'=>2018,'year_to'=>2022,'notes'=>'FWD front strut platform shared — spring rates differ (Acura firmer)']],
            'Sedan', 'Front strut: FWD variant shares platform geometry with Accord. Sport Hybrid (SH-AWD) uses different rear setup.', 'Front Strut Assembly');

        $records[] = $r('Body', 'Acura', 'TLX', 2021, 2023,
            [], 'Sedan', 'Body panels: Acura-specific. Do not cross with Honda Accord.', 'Body Panels');

        // ── ILX (2016–2022) ──────────────────────────────────────────────────
        $records[] = $r('Engine', 'Acura', 'ILX', 2016, 2022,
            [['brand'=>'Honda','model'=>'Civic','year_from'=>2016,'year_to'=>2021,'notes'=>'K20C2 engine accessories shared — Civic Si engine is same unit'],
             ['brand'=>'Honda','model'=>'CR-V','year_from'=>2017,'year_to'=>2022,'notes'=>'Some L15B7 accessories if ILX has 1.5T — verify engine code']],
            'Sedan', 'Engine: K20C2 2.0L or 1.5T — many accessories shared with 10th gen Civic.', 'Engine Assembly');

        $records[] = $r('Suspension', 'Acura', 'ILX', 2016, 2022,
            [['brand'=>'Honda','model'=>'Civic','year_from'=>2016,'year_to'=>2021,'notes'=>'Front strut assembly — Civic platform, sport-tuned springs']],
            'Sedan', 'Front strut assembly shares Civic 10th gen platform. Springs are sport-tuned (stiffer) — do not swap springs.', 'Front Strut Assembly');

        // ── RDX (3rd gen 2019–2022) ──────────────────────────────────────────
        $records[] = $r('Engine', 'Acura', 'RDX', 2019, 2022,
            [['brand'=>'Acura','model'=>'TLX','year_from'=>2021,'year_to'=>2023,'notes'=>'K20C4 2.0T engine accessories shared'],
             ['brand'=>'Honda','model'=>'Accord','year_from'=>2018,'year_to'=>2022,'notes'=>'K20C4 — some engine accessories compatible']],
            'SUV', 'K20C4 2.0T turbocharged. Engine accessories shared with TLX and Accord 2.0T.', 'Engine Assembly',
            '2.0L Turbo');

        // =====================================================================
        // NISSAN
        // =====================================================================

        // ── Altima (6th gen 2019–2024) ───────────────────────────────────────
        $records[] = $r('Body', 'Nissan', 'Altima', 2019, 2024,
            [], 'Sedan', 'Body panels: 2019-2024 6th gen. Does NOT fit 2013-2018 5th gen.', 'Body Panels');

        $records[] = $r('Body', 'Nissan', 'Altima', 2019, 2021,
            [], 'Sedan', 'Front bumper: 2019-2021 pre-facelift only.', 'Front Bumper Cover');

        $records[] = $r('Body', 'Nissan', 'Altima', 2022, 2024,
            [], 'Sedan', 'Front bumper: 2022-2024 facelift — revised front clip.', 'Front Bumper Cover');

        $records[] = $r('Engine', 'Nissan', 'Altima', 2019, 2024,
            [['brand'=>'Infiniti','model'=>'Q50','year_from'=>2014,'year_to'=>2024,'notes'=>'VR30DDTT 3.0T NOT shared — Q50 turbo; Altima is 2.0T or 2.5L. Different engines.'],
             ['brand'=>'Nissan','model'=>'Sentra','year_from'=>2020,'year_to'=>2024,'notes'=>'Some 2.0L MR20DD accessories shared'],
             ['brand'=>'Nissan','model'=>'Rogue','year_from'=>2021,'year_to'=>2024,'notes'=>'KR20DDET 1.5T — some accessories shared on VC-Turbo variants']],
            'All', 'Engine: 2.5L PR25DD or 2.0T KR20DDET VC-Turbo. Accessories only cross within same engine family.', 'Engine Assembly');

        $records[] = $r('Transmission', 'Nissan', 'Altima', 2019, 2024,
            [['brand'=>'Nissan','model'=>'Rogue','year_from'=>2021,'year_to'=>2024,'notes'=>'Xtronic CVT generation compatible — verify TCM programming'],
             ['brand'=>'Infiniti','model'=>'QX50','year_from'=>2019,'year_to'=>2024,'notes'=>'CVT on VC-Turbo — may be compatible but requires verification']],
            'All', 'Xtronic CVT. FWD vs AWD units differ. Must match drivetrain.', 'Transmission');

        $records[] = $r('Suspension', 'Nissan', 'Altima', 2019, 2024,
            [['brand'=>'Infiniti','model'=>'Q50','year_from'=>2014,'year_to'=>2024,'notes'=>'Front struts: platform related but Infiniti tuned different — verify before ordering']],
            'Sedan', 'Front strut assembly. FWD and AWD use different rear suspension systems.', 'Front Strut Assembly');

        $records[] = $r('Electrical', 'Nissan', 'Altima', 2019, 2024,
            [['brand'=>'Infiniti','model'=>'Q50','year_from'=>2014,'year_to'=>2024,'notes'=>'Alternator: if same engine family — verify output rating']],
            'All', 'Alternator: match engine type (2.5L vs 2.0T) and amperage.', 'Alternator');

        // ── Rogue (3rd gen 2021–2024) ────────────────────────────────────────
        $records[] = $r('Engine', 'Nissan', 'Rogue', 2021, 2024,
            [['brand'=>'Infiniti','model'=>'QX50','year_from'=>2019,'year_to'=>2024,'notes'=>'KR20DDET VC-Turbo engine accessories shared'],
             ['brand'=>'Nissan','model'=>'Altima','year_from'=>2019,'year_to'=>2024,'notes'=>'KR20DDET engine accessories shared on turbo variants']],
            'SUV', 'KR20DDET VC-Turbo 1.5T engine accessories shared with QX50 and 2.0T Altima.', 'Engine Assembly',
            '1.5L Turbo');

        $records[] = $r('Body', 'Nissan', 'Rogue', 2021, 2023,
            [], 'SUV', 'Body panels: 2021-2023 3rd gen. Does NOT fit 2014-2020 2nd gen.', 'Front Bumper Cover');

        // ── Pathfinder (R52 2013–2021) ───────────────────────────────────────
        $records[] = $r('Engine', 'Nissan', 'Pathfinder', 2013, 2021,
            [['brand'=>'Infiniti','model'=>'QX60','year_from'=>2013,'year_to'=>2021,'notes'=>'VQ35DE 3.5L V6 engine accessories shared — same platform'],
             ['brand'=>'Nissan','model'=>'Murano','year_from'=>2015,'year_to'=>2021,'notes'=>'VQ35DE V6 accessories shared']],
            'SUV', 'VQ35DE 3.5L V6 accessories widely shared across Nissan/Infiniti D platform vehicles.', 'Engine Assembly',
            '3.5L V6');

        $records[] = $r('Transmission', 'Nissan', 'Pathfinder', 2013, 2021,
            [['brand'=>'Infiniti','model'=>'QX60','year_from'=>2013,'year_to'=>2021,'notes'=>'CVT shared — same unit, same fitment']],
            'SUV', 'CVT: Pathfinder and QX60 share the same CVT unit on the D platform.', 'Transmission');

        // =====================================================================
        // INFINITI
        // =====================================================================

        // ── Q50 (V37 2014–2024) ──────────────────────────────────────────────
        $records[] = $r('Engine', 'Infiniti', 'Q50', 2014, 2024,
            [['brand'=>'Infiniti','model'=>'Q60','year_from'=>2017,'year_to'=>2022,'notes'=>'VR30DDTT 3.0T twin-turbo fully shared — same engine'],
             ['brand'=>'Infiniti','model'=>'QX50','year_from'=>2019,'year_to'=>2024,'notes'=>'Different engine (VC-Turbo) — accessories NOT shared'],
             ['brand'=>'Nissan','model'=>'GT-R','year_from'=>2009,'year_to'=>2022,'notes'=>'Different engine family — no interchange']],
            'Sedan', 'Engine: 2.0T M274 (4cyl) or 3.0T VR30DDTT (twin-turbo V6). VR30DDTT shared fully with Q60.', 'Engine Assembly',
            '2.0L Turbo / 3.0L BiTurbo');

        $records[] = $r('Transmission', 'Infiniti', 'Q50', 2014, 2024,
            [['brand'=>'Infiniti','model'=>'Q60','year_from'=>2017,'year_to'=>2022,'notes'=>'7-speed auto shared on VR30DDTT — same RWD/AWD platform']],
            'Sedan', '7-speed automatic: RWD and AWD units differ. Shared with Q60 on same drivetrain.', 'Transmission');

        $records[] = $r('Suspension', 'Infiniti', 'Q50', 2014, 2024,
            [['brand'=>'Infiniti','model'=>'Q60','year_from'=>2017,'year_to'=>2022,'notes'=>'Front strut assembly shared — same FM platform'],
             ['brand'=>'Nissan','model'=>'Altima','year_from'=>2019,'year_to'=>2024,'notes'=>'Different platform — struts NOT compatible']],
            'Sedan', 'Front strut: FM platform shared with Q60. Sport package uses stiffer springs — verify spring rate.', 'Front Strut Assembly');

        $records[] = $r('Body', 'Infiniti', 'Q50', 2014, 2017,
            [], 'Sedan', 'Body panels: 2014-2017. 2018+ has revised front end — not interchangeable.', 'Body Panels');

        $records[] = $r('Body', 'Infiniti', 'Q50', 2018, 2024,
            [], 'Sedan', 'Body panels: 2018-2024 revised front. Does not fit 2014-2017.', 'Body Panels');

        // ── QX60 (L50 2013–2021) ─────────────────────────────────────────────
        $records[] = $r('Engine', 'Infiniti', 'QX60', 2013, 2021,
            [['brand'=>'Nissan','model'=>'Pathfinder','year_from'=>2013,'year_to'=>2021,'notes'=>'VQ35DE 3.5L V6 — same engine, same platform'],
             ['brand'=>'Nissan','model'=>'Murano','year_from'=>2015,'year_to'=>2021,'notes'=>'VQ35DE engine accessories shared']],
            'SUV', 'VQ35DE 3.5L V6: same engine unit as Nissan Pathfinder on D platform.', 'Engine Assembly',
            '3.5L V6');

        $records[] = $r('Transmission', 'Infiniti', 'QX60', 2013, 2021,
            [['brand'=>'Nissan','model'=>'Pathfinder','year_from'=>2013,'year_to'=>2021,'notes'=>'CVT shared — same unit, same fitment, same failure points']],
            'SUV', 'CVT: Infiniti QX60 and Nissan Pathfinder share the same CVT unit.', 'Transmission');

        // ── Q60 (V37 2017–2022) ──────────────────────────────────────────────
        $records[] = $r('Engine', 'Infiniti', 'Q60', 2017, 2022,
            [['brand'=>'Infiniti','model'=>'Q50','year_from'=>2014,'year_to'=>2024,'notes'=>'VR30DDTT fully shared — identical engine, all accessories compatible']],
            'Coupe', 'VR30DDTT 3.0T twin-turbo: fully shared with Q50. All engine accessories interchangeable.', 'Engine Assembly',
            '3.0L BiTurbo');

        // =====================================================================
        // KIA
        // =====================================================================

        // ── Optima/K5 (4th gen 2021–2024) ────────────────────────────────────
        $records[] = $r('Engine', 'Kia', 'K5', 2021, 2024,
            [['brand'=>'Hyundai','model'=>'Sonata','year_from'=>2020,'year_to'=>2024,'notes'=>'Smartstream G2.5 / T-GDi 1.6T engines shared — same N-Line platform accessories'],
             ['brand'=>'Hyundai','model'=>'Tucson','year_from'=>2022,'year_to'=>2024,'notes'=>'T-GDi 1.6T accessories shared']],
            'Sedan', 'Engine: 2.5L Smartstream (LX25) or 1.6T T-GDi. Accessories cross within same engine family across Hyundai Group.', 'Engine Assembly');

        $records[] = $r('Body', 'Kia', 'K5', 2021, 2024,
            [], 'Sedan', 'Body panels: Kia-specific design. Does NOT interchange with Hyundai Sonata despite shared platform.', 'Body Panels');

        $records[] = $r('Suspension', 'Kia', 'K5', 2021, 2024,
            [['brand'=>'Hyundai','model'=>'Sonata','year_from'=>2020,'year_to'=>2024,'notes'=>'Front strut assembly: N-Line platform shared — verify spring rates (sport vs base differ)']],
            'Sedan', 'Front strut: N-Line platform shared with Sonata. Sport/N-Line springs differ from base.', 'Front Strut Assembly');

        $records[] = $r('Transmission', 'Kia', 'K5', 2021, 2024,
            [['brand'=>'Hyundai','model'=>'Sonata','year_from'=>2020,'year_to'=>2024,'notes'=>'8-speed DCT or 8-speed auto shared across N-Line platform']],
            'Sedan', '8-speed DCT (1.6T) or 8-speed auto (2.5L): shared with Sonata on same platform.', 'Transmission');

        // ── Sportage (5th gen 2023–2024) ─────────────────────────────────────
        $records[] = $r('Engine', 'Kia', 'Sportage', 2023, 2024,
            [['brand'=>'Hyundai','model'=>'Tucson','year_from'=>2022,'year_to'=>2024,'notes'=>'T-GDi 1.6T / 2.5L engine accessories fully shared — same N3 platform']],
            'SUV', '1.6T or 2.5L: engine accessories shared with Tucson on N3 platform.', 'Engine Assembly');

        $records[] = $r('Body', 'Kia', 'Sportage', 2023, 2024,
            [], 'SUV', 'Body panels: Kia-specific. Does NOT fit Hyundai Tucson despite shared platform.', 'Body Panels');

        $records[] = $r('Suspension', 'Kia', 'Sportage', 2023, 2024,
            [['brand'=>'Hyundai','model'=>'Tucson','year_from'=>2022,'year_to'=>2024,'notes'=>'Front strut assembly shared on N3 platform — spring rates match base variants']],
            'SUV', 'Front strut: N3 platform shared with Tucson 2022+.', 'Front Strut Assembly');

        // ── Sorento (4th gen 2021–2024) ──────────────────────────────────────
        $records[] = $r('Engine', 'Kia', 'Sorento', 2021, 2024,
            [['brand'=>'Hyundai','model'=>'Santa Fe','year_from'=>2021,'year_to'=>2024,'notes'=>'T-GDi 2.5T / Lambda3 3.5L V6 engine accessories shared'],
             ['brand'=>'Hyundai','model'=>'Palisade','year_from'=>2020,'year_to'=>2024,'notes'=>'Lambda3 V6 accessories shared']],
            'SUV', 'Engine: 2.5T turbocharged or 3.5L V6. Accessories cross with Santa Fe and Palisade.', 'Engine Assembly');

        // =====================================================================
        // HYUNDAI
        // =====================================================================

        // ── Sonata (8th gen 2020–2024) ───────────────────────────────────────
        $records[] = $r('Engine', 'Hyundai', 'Sonata', 2020, 2024,
            [['brand'=>'Kia','model'=>'K5','year_from'=>2021,'year_to'=>2024,'notes'=>'G2.5 / T-GDi 1.6T engine accessories shared — same N-Line platform'],
             ['brand'=>'Hyundai','model'=>'Tucson','year_from'=>2022,'year_to'=>2024,'notes'=>'1.6T T-GDi accessories shared']],
            'Sedan', 'G2.5 Smartstream naturally aspirated or 1.6T T-GDi. Engine accessories cross within Hyundai Group N-Line platform.', 'Engine Assembly');

        $records[] = $r('Body', 'Hyundai', 'Sonata', 2020, 2023,
            [], 'Sedan', 'Body panels: 2020-2023. 2024 receives facelift — bumpers and headlights changed.', 'Body Panels');

        $records[] = $r('Suspension', 'Hyundai', 'Sonata', 2020, 2024,
            [['brand'=>'Kia','model'=>'K5','year_from'=>2021,'year_to'=>2024,'notes'=>'N-Line platform front strut shared — sport vs base spring rates differ']],
            'Sedan', 'Front strut shared with K5. Base and Sport/N-Line spring rates differ — verify trim level.', 'Front Strut Assembly');

        $records[] = $r('Transmission', 'Hyundai', 'Sonata', 2020, 2024,
            [['brand'=>'Kia','model'=>'K5','year_from'=>2021,'year_to'=>2024,'notes'=>'8-speed DCT (1.6T) or 8-speed auto (2.5L) shared on N-Line platform']],
            'Sedan', '8-speed DCT or 8-speed auto: shared with K5 on same platform.', 'Transmission');

        // ── Tucson (4th gen 2022–2024) ───────────────────────────────────────
        $records[] = $r('Engine', 'Hyundai', 'Tucson', 2022, 2024,
            [['brand'=>'Kia','model'=>'Sportage','year_from'=>2023,'year_to'=>2024,'notes'=>'T-GDi 1.6T / 2.5L engine accessories shared on N3 platform'],
             ['brand'=>'Hyundai','model'=>'Sonata','year_from'=>2020,'year_to'=>2024,'notes'=>'1.6T T-GDi accessories shared across Hyundai Group']],
            'SUV', 'T-GDi 1.6T or 2.5L Smartstream: accessories cross with Sportage and Sonata 1.6T.', 'Engine Assembly');

        $records[] = $r('Suspension', 'Hyundai', 'Tucson', 2022, 2024,
            [['brand'=>'Kia','model'=>'Sportage','year_from'=>2023,'year_to'=>2024,'notes'=>'N3 platform front strut: Tucson and Sportage share same strut assembly']],
            'SUV', 'Front strut: N3 platform shared with Sportage 2023+.', 'Front Strut Assembly');

        $records[] = $r('Body', 'Hyundai', 'Tucson', 2022, 2024,
            [], 'SUV', 'Body panels: Hyundai-specific. Does NOT interchange with Kia Sportage.', 'Body Panels');

        // ── Santa Fe (4th gen 2021–2024) ─────────────────────────────────────
        $records[] = $r('Engine', 'Hyundai', 'Santa Fe', 2021, 2024,
            [['brand'=>'Kia','model'=>'Sorento','year_from'=>2021,'year_to'=>2024,'notes'=>'2.5T / 3.5L V6 engine accessories shared'],
             ['brand'=>'Hyundai','model'=>'Palisade','year_from'=>2020,'year_to'=>2024,'notes'=>'3.8L Lambda3 V6 engine accessories shared']],
            'SUV', '2.5T turbocharged or 3.5L/3.8L V6 engine accessories shared with Sorento and Palisade.', 'Engine Assembly');

        // =====================================================================
        // FORD
        // =====================================================================

        // ── F-150 (14th gen 2021–2024) ───────────────────────────────────────
        $records[] = $r('Body', 'Ford', 'F-150', 2021, 2024,
            [], 'Truck', 'Body panels: 2021-2024 14th gen. Does NOT fit 2015-2020 13th gen. SuperCrew, SuperCab, Regular Cab differ on rear doors/bed.', 'Body Panels');

        $records[] = $r('Body', 'Ford', 'F-150', 2021, 2023,
            [], 'Truck', 'Front bumper: 2021-2023 — 2024 receives revised front end on some trims.', 'Front Bumper Cover');

        $records[] = $r('Engine', 'Ford', 'F-150', 2021, 2024,
            [['brand'=>'Ford','model'=>'Expedition','year_from'=>2022,'year_to'=>2024,'notes'=>'3.5L EcoBoost V6 engine accessories shared'],
             ['brand'=>'Ford','model'=>'Mustang','year_from'=>2024,'year_to'=>2024,'notes'=>'5.0L Coyote V8 accessories — some shared on same engine family'],
             ['brand'=>'Lincoln','model'=>'Navigator','year_from'=>2022,'year_to'=>2024,'notes'=>'3.5L EcoBoost accessories largely shared']],
            'Truck', 'Engine: 2.7L EcoBoost, 3.5L EcoBoost (PowerBoost hybrid also), 5.0L V8. Accessories only cross within same engine family.', 'Engine Assembly');

        $records[] = $r('Transmission', 'Ford', 'F-150', 2021, 2024,
            [['brand'=>'Ford','model'=>'Expedition','year_from'=>2022,'year_to'=>2024,'notes'=>'10-speed auto shared on 3.5L EcoBoost — same unit']],
            'Truck', '10-speed SelectShift auto: shared with Expedition on 3.5L. Must match engine variant.', 'Transmission');

        $records[] = $r('Suspension', 'Ford', 'F-150', 2021, 2024,
            [], 'Truck', 'Front strut assembly: 2WD and 4WD differ significantly. Must specify drivetrain.', 'Front Strut Assembly');

        $records[] = $r('Body', 'Ford', 'F-150', 2015, 2020,
            [['brand'=>'Lincoln','model'=>'Navigator','year_from'=>2018,'year_to'=>2021,'notes'=>'Different body — no panel interchange']],
            'Truck', 'Body panels: 2015-2020 13th gen aluminum body. Does NOT fit 2021+ 14th gen.', 'Body Panels');

        // ── Mustang (7th gen S650 2024–) ─────────────────────────────────────
        $records[] = $r('Engine', 'Ford', 'Mustang', 2024, 2026,
            [], 'Coupe', '2.3L EcoBoost or 5.0L Coyote V8. All-new S650 platform — accessories do NOT cross with S550 (2015-2023).', 'Engine Assembly');

        $records[] = $r('Engine', 'Ford', 'Mustang', 2015, 2023,
            [['brand'=>'Ford','model'=>'F-150','year_from'=>2015,'year_to'=>2020,'notes'=>'5.0L Coyote V8 accessories may be shared — verify generation'],
             ['brand'=>'Ford','model'=>'Explorer','year_from'=>2016,'year_to'=>2019,'notes'=>'3.5L EcoBoost accessories shared on V6 variants']],
            'All', '5.0L Coyote V8 (Gen 3/Gen 4) or 2.3L EcoBoost. S550 platform 2015-2023.', 'Engine Assembly');

        // ── Explorer (7th gen 2020–2024) ─────────────────────────────────────
        $records[] = $r('Engine', 'Ford', 'Explorer', 2020, 2024,
            [['brand'=>'Ford','model'=>'Aviator','year_from'=>2020,'year_to'=>2024,'notes'=>'Lincoln Aviator shares CD6 platform — 3.0T engine accessories compatible'],
             ['brand'=>'Lincoln','model'=>'Aviator','year_from'=>2020,'year_to'=>2024,'notes'=>'CD6 platform — 3.0T engine accessories shared']],
            'SUV', 'RWD-based CD6 platform. 2.3L EcoBoost or 3.0T ST. Engine accessories cross with Aviator.', 'Engine Assembly');

        // =====================================================================
        // GM / CHEVROLET (shared platforms)
        // =====================================================================

        // ── Silverado 1500 (T1 2019–2024) / Sierra 1500 (T1 2019–2024) ──────
        $records[] = $r('Engine', 'Chevrolet', 'Silverado 1500', 2019, 2024,
            [['brand'=>'GM','model'=>'Sierra 1500','year_from'=>2019,'year_to'=>2024,'notes'=>'T1 platform — nearly all engine components shared (different grille/hood/trim)'],
             ['brand'=>'Chevrolet','model'=>'Tahoe','year_from'=>2021,'year_to'=>2024,'notes'=>'EcoTec3 engine family accessories shared'],
             ['brand'=>'GM','model'=>'Yukon','year_from'=>2021,'year_to'=>2024,'notes'=>'EcoTec3 engine family accessories shared'],
             ['brand'=>'Chevrolet','model'=>'Suburban','year_from'=>2021,'year_to'=>2024,'notes'=>'Same EcoTec3 engine — accessories fully compatible']],
            'Truck', 'EcoTec3 V6 or V8 (L84/L87/L8T): engine accessories widely shared across GM full-size truck/SUV family.', 'Engine Assembly',
            '2.7T / 5.3L V8 / 6.2L V8');

        $records[] = $r('Transmission', 'Chevrolet', 'Silverado 1500', 2019, 2024,
            [['brand'=>'GM','model'=>'Sierra 1500','year_from'=>2019,'year_to'=>2024,'notes'=>'10-speed auto fully shared on V8 variants — same unit'],
             ['brand'=>'Chevrolet','model'=>'Tahoe','year_from'=>2021,'year_to'=>2024,'notes'=>'10-speed auto shared — same unit across EcoTec3 V8 family']],
            'Truck', '10-speed Hydra-Matic: fully shared across GM T1 truck family on V8. 8-speed on 5.3L also shared.', 'Transmission');

        $records[] = $r('Body', 'Chevrolet', 'Silverado 1500', 2019, 2024,
            [['brand'=>'GM','model'=>'Sierra 1500','year_from'=>2019,'year_to'=>2024,'notes'=>'CAB structure shared — but front clip (hood, fenders, bumper) is brand-specific and does NOT interchange']],
            'Truck', 'IMPORTANT: Cab structure shared with Sierra but front clip (hood, bumper, grille, fenders) is Silverado-specific and will NOT fit Sierra.', 'Body Panels');

        $records[] = $r('Body', 'GM', 'Sierra 1500', 2019, 2024,
            [['brand'=>'Chevrolet','model'=>'Silverado 1500','year_from'=>2019,'year_to'=>2024,'notes'=>'Cab structure shared only — front clip is Sierra-specific, does NOT fit Silverado']],
            'Truck', 'Cab structure shared with Silverado. Front clip (hood, fenders, bumper, grille) is Sierra-specific.', 'Body Panels');

        $records[] = $r('Suspension', 'Chevrolet', 'Silverado 1500', 2019, 2024,
            [['brand'=>'GM','model'=>'Sierra 1500','year_from'=>2019,'year_to'=>2024,'notes'=>'T1 platform — front strut/torsion bar shared between Silverado and Sierra']],
            'Truck', 'Front suspension: 2WD uses struts, 4WD uses torsion bar. Both shared with Sierra on T1 platform.', 'Front Strut Assembly');

        // ── Equinox (3rd gen 2018–2024) ──────────────────────────────────────
        $records[] = $r('Engine', 'Chevrolet', 'Equinox', 2018, 2024,
            [['brand'=>'GM','model'=>'Terrain','year_from'=>2018,'year_to'=>2024,'notes'=>'D2 platform — 1.5T / 2.0T engine accessories shared between Equinox and Terrain'],
             ['brand'=>'Chevrolet','model'=>'Malibu','year_from'=>2016,'year_to'=>2024,'notes'=>'LTG 2.0T engine accessories shared']],
            'SUV', '1.5T LFV or 2.0T LTG: engine accessories shared with GMC Terrain and Malibu 2.0T on D2 platform.', 'Engine Assembly');

        $records[] = $r('Body', 'Chevrolet', 'Equinox', 2018, 2021,
            [], 'SUV', 'Body panels: 2018-2021 pre-facelift. 2022+ facelift has revised front end.', 'Front Bumper Cover');

        $records[] = $r('Body', 'Chevrolet', 'Equinox', 2022, 2024,
            [], 'SUV', 'Body panels: 2022-2024 facelift. Does not fit 2018-2021.', 'Front Bumper Cover');

        $records[] = $r('Suspension', 'Chevrolet', 'Equinox', 2018, 2024,
            [['brand'=>'GM','model'=>'Terrain','year_from'=>2018,'year_to'=>2024,'notes'=>'D2 platform front strut shared with GMC Terrain']],
            'SUV', 'Front strut: D2 platform shared with GMC Terrain.', 'Front Strut Assembly');

        // ── Malibu (9th gen 2016–2024) ───────────────────────────────────────
        $records[] = $r('Engine', 'Chevrolet', 'Malibu', 2016, 2024,
            [['brand'=>'Chevrolet','model'=>'Equinox','year_from'=>2018,'year_to'=>2024,'notes'=>'LTG 2.0T engine accessories shared — same unit'],
             ['brand'=>'Chevrolet','model'=>'Blazer','year_from'=>2019,'year_to'=>2024,'notes'=>'LTG 2.0T accessories shared across FWD-based GM vehicles']],
            'Sedan', 'Engine: 1.5T LFV or 2.0T LTG. 2.0T accessories cross broadly in GM FWD family.', 'Engine Assembly');

        $records[] = $r('Body', 'Chevrolet', 'Malibu', 2016, 2018,
            [], 'Sedan', 'Body panels: 2016-2018 pre-facelift only.', 'Body Panels');

        $records[] = $r('Body', 'Chevrolet', 'Malibu', 2019, 2024,
            [], 'Sedan', 'Body panels: 2019-2024 facelift. Does not fit 2016-2018.', 'Body Panels');

        // =====================================================================
        // MERCEDES-BENZ
        // =====================================================================

        // ── C-Class (W206 2022–2024) ─────────────────────────────────────────
        $records[] = $r('Engine', 'Mercedes-Benz', 'C-Class', 2022, 2024,
            [['brand'=>'Mercedes-Benz','model'=>'E-Class','year_from'=>2024,'year_to'=>2024,'notes'=>'M254 2.0T engine family — some accessories shared across MRA2 platform'],
             ['brand'=>'Mercedes-Benz','model'=>'GLC','year_from'=>2023,'year_to'=>2024,'notes'=>'M254 / OM654d engine accessories shared on MRA2 platform']],
            'Sedan', 'M254 2.0T petrol or OM654d 2.0 diesel. Engine accessories cross within MRA2 platform (C-Class, GLC, E-Class 2024+).', 'Engine Assembly',
            '2.0L Turbo');

        $records[] = $r('Body', 'Mercedes-Benz', 'C-Class', 2022, 2024,
            [], 'Sedan', 'Body panels: W206 generation only. Does NOT fit W205 (2015-2021). Sedan vs Estate differ on rear body.', 'Body Panels');

        $records[] = $r('Body', 'Mercedes-Benz', 'C-Class', 2015, 2021,
            [], 'Sedan', 'Body panels: W205 generation. 2015-2018 pre-facelift differs from 2019-2021 facelift.', 'Body Panels');

        $records[] = $r('Body', 'Mercedes-Benz', 'C-Class', 2015, 2018,
            [], 'Sedan', 'Front bumper: W205 pre-facelift 2015-2018 only.', 'Front Bumper Cover');

        $records[] = $r('Body', 'Mercedes-Benz', 'C-Class', 2019, 2021,
            [], 'Sedan', 'Front bumper: W205 facelift 2019-2021 only.', 'Front Bumper Cover');

        $records[] = $r('Suspension', 'Mercedes-Benz', 'C-Class', 2022, 2024,
            [['brand'=>'Mercedes-Benz','model'=>'GLC','year_from'=>2023,'year_to'=>2024,'notes'=>'Front strut assembly: MRA2 platform shared — different spring rates for SUV vs sedan']],
            'Sedan', 'Front strut: W206 platform. AMG Line uses stiffer springs — verify trim. MRA2 shared with GLC.', 'Front Strut Assembly');

        $records[] = $r('Electrical', 'Mercedes-Benz', 'C-Class', 2022, 2024,
            [['brand'=>'Mercedes-Benz','model'=>'GLC','year_from'=>2023,'year_to'=>2024,'notes'=>'Alternator: M254 engine — compatible across MRA2 platform']],
            'All', 'Alternator: M254 engine unit shared with GLC on MRA2 platform.', 'Alternator');

        // ── E-Class (W213 2017–2023) ─────────────────────────────────────────
        $records[] = $r('Engine', 'Mercedes-Benz', 'E-Class', 2017, 2023,
            [['brand'=>'Mercedes-Benz','model'=>'GLE','year_from'=>2020,'year_to'=>2023,'notes'=>'M276/M256 engine accessories shared on MRA platform'],
             ['brand'=>'Mercedes-Benz','model'=>'GLC','year_from'=>2016,'year_to'=>2022,'notes'=>'M274/M256 engine family accessories — verify exact engine code']],
            'Sedan', 'M274 2.0T, M276 3.0T V6, or M256 3.0L inline-6. Accessories cross within same engine code on MRA platform.', 'Engine Assembly');

        $records[] = $r('Body', 'Mercedes-Benz', 'E-Class', 2017, 2020,
            [], 'Sedan', 'Body panels: W213 pre-facelift 2017-2020. Sedan vs Wagon vs Coupe all differ.', 'Body Panels');

        $records[] = $r('Body', 'Mercedes-Benz', 'E-Class', 2021, 2023,
            [], 'Sedan', 'Body panels: W213 facelift 2021-2023. Front bumper and headlights changed.', 'Body Panels');

        // ── GLC (X253 2016–2022) ─────────────────────────────────────────────
        $records[] = $r('Engine', 'Mercedes-Benz', 'GLC', 2016, 2022,
            [['brand'=>'Mercedes-Benz','model'=>'C-Class','year_from'=>2015,'year_to'=>2021,'notes'=>'M274 2.0T engine accessories shared on MRA platform'],
             ['brand'=>'Mercedes-Benz','model'=>'E-Class','year_from'=>2017,'year_to'=>2023,'notes'=>'M274/M276 accessories may cross — verify engine code']],
            'SUV', 'M274 2.0T or M276 3.0T: engine accessories shared with C-Class and E-Class on MRA platform.', 'Engine Assembly');

        $records[] = $r('Body', 'Mercedes-Benz', 'GLC', 2016, 2019,
            [], 'SUV', 'Body panels: X253 pre-facelift 2016-2019 only.', 'Body Panels');

        $records[] = $r('Body', 'Mercedes-Benz', 'GLC', 2020, 2022,
            [], 'SUV', 'Body panels: X253 facelift 2020-2022 only.', 'Body Panels');

        // =====================================================================
        // VOLKSWAGEN
        // =====================================================================

        // ── Jetta (7th gen A7 2019–2024) ────────────────────────────────────
        $records[] = $r('Engine', 'VW', 'Jetta', 2019, 2024,
            [['brand'=>'VW','model'=>'Golf','year_from'=>2015,'year_to'=>2021,'notes'=>'EA888 1.4T / 2.0T engine accessories shared on MQB platform'],
             ['brand'=>'VW','model'=>'Tiguan','year_from'=>2018,'year_to'=>2024,'notes'=>'EA888 Gen3 2.0T accessories shared on MQB platform'],
             ['brand'=>'Audi','model'=>'A3','year_from'=>2015,'year_to'=>2020,'notes'=>'MQB platform — EA888 1.4T/2.0T accessories largely shared']],
            'Sedan', 'EA888 1.4T (DKFA) or 2.0T: engine accessories shared across VW/Audi MQB platform.', 'Engine Assembly',
            '1.4L Turbo / 2.0L Turbo');

        $records[] = $r('Transmission', 'VW', 'Jetta', 2019, 2024,
            [['brand'=>'VW','model'=>'Golf','year_from'=>2015,'year_to'=>2021,'notes'=>'6-speed manual or 7-speed DSG shared on MQB FWD'],
             ['brand'=>'VW','model'=>'Tiguan','year_from'=>2018,'year_to'=>2024,'notes'=>'7-speed DSG shared on FWD variants'],
             ['brand'=>'Audi','model'=>'A3','year_from'=>2015,'year_to'=>2020,'notes'=>'7-speed DSG shared on FWD — AWD uses different gearbox']],
            'Sedan', '7-speed DSG (DQ200/DQ381) or 6-speed manual: shared across MQB FWD vehicles.', 'Transmission');

        $records[] = $r('Suspension', 'VW', 'Jetta', 2019, 2024,
            [['brand'=>'VW','model'=>'Golf','year_from'=>2015,'year_to'=>2021,'notes'=>'MQB front strut: Jetta and Golf share strut assembly on FWD variants'],
             ['brand'=>'Audi','model'=>'A3','year_from'=>2015,'year_to'=>2020,'notes'=>'MQB FWD front strut compatible — spring rates may differ']],
            'Sedan', 'Front strut: MQB FWD platform shared with Golf, Audi A3. Sport variants have different spring rates.', 'Front Strut Assembly');

        $records[] = $r('Body', 'VW', 'Jetta', 2019, 2024,
            [], 'Sedan', 'Body panels: A7 Jetta. Does NOT interchange with Golf or Tiguan body panels.', 'Body Panels');

        $records[] = $r('Electrical', 'VW', 'Jetta', 2019, 2024,
            [['brand'=>'VW','model'=>'Golf','year_from'=>2015,'year_to'=>2021,'notes'=>'Alternator: EA888 engine — compatible across MQB'],
             ['brand'=>'VW','model'=>'Tiguan','year_from'=>2018,'year_to'=>2024,'notes'=>'Alternator: EA888 2.0T — compatible across MQB'],
             ['brand'=>'Audi','model'=>'A3','year_from'=>2015,'year_to'=>2020,'notes'=>'Alternator: EA888 engine shared — compatible']],
            'All', 'Alternator: EA888 engine family — compatible across all MQB vehicles (Jetta, Golf, Tiguan, Audi A3).', 'Alternator');

        // ── Tiguan (2nd gen 2018–2024) ────────────────────────────────────────
        $records[] = $r('Engine', 'VW', 'Tiguan', 2018, 2024,
            [['brand'=>'VW','model'=>'Jetta','year_from'=>2019,'year_to'=>2024,'notes'=>'EA888 2.0T accessories shared on MQB platform'],
             ['brand'=>'VW','model'=>'Golf','year_from'=>2015,'year_to'=>2021,'notes'=>'EA888 Gen3 accessories shared'],
             ['brand'=>'Audi','model'=>'Q3','year_from'=>2019,'year_to'=>2024,'notes'=>'MQB-A platform — 2.0T accessories largely shared']],
            'SUV', 'EA888 Gen3 2.0T engine accessories shared across MQB FWD/AWD platform.', 'Engine Assembly',
            '2.0L Turbo');

        $records[] = $r('Body', 'VW', 'Tiguan', 2018, 2020,
            [], 'SUV', 'Body panels: 2018-2020 pre-facelift only.', 'Front Bumper Cover');

        $records[] = $r('Body', 'VW', 'Tiguan', 2021, 2024,
            [], 'SUV', 'Body panels: 2021-2024 facelift only.', 'Front Bumper Cover');

        $records[] = $r('Suspension', 'VW', 'Tiguan', 2018, 2024,
            [['brand'=>'Audi','model'=>'Q3','year_from'=>2019,'year_to'=>2024','notes'=>'MQB front strut: some compatibility — verify spring rates'],
             ['brand'=>'VW','model'=>'Jetta','year_from'=>2019,'year_to'=>2024','notes'=>'MQB front strut shared on FWD variants']],
            'SUV', 'Front strut: MQB platform. 4Motion AWD uses different rear suspension — verify drivetrain.', 'Front Strut Assembly');

        // ── Passat (8th gen B8 2016–2022) ────────────────────────────────────
        $records[] = $r('Engine', 'VW', 'Passat', 2016, 2022,
            [['brand'=>'Audi','model'=>'A4','year_from'=>2017,'year_to'=>2024,'notes'=>'MLB Evo platform — 2.0T TFSI accessories shared between Passat and A4 B9'],
             ['brand'=>'Audi','model'=>'A5','year_from'=>2017,'year_to'=>2024','notes'=>'MLB Evo platform — EA888 2.0T accessories shared']],
            'Sedan', 'EA888 Gen3 2.0T: accessories shared with Audi A4 and A5 on MLB Evo platform.', 'Engine Assembly',
            '2.0L Turbo');

        $records[] = $r('Body', 'VW', 'Passat', 2016, 2019,
            [], 'Sedan', 'Body panels: B8 pre-facelift 2016-2019 only.', 'Body Panels');

        $records[] = $r('Body', 'VW', 'Passat', 2020, 2022,
            [], 'Sedan', 'Body panels: B8 facelift 2020-2022 only.', 'Body Panels');

        // ── Atlas (2018–2024) ─────────────────────────────────────────────────
        $records[] = $r('Engine', 'VW', 'Atlas', 2018, 2024,
            [['brand'=>'VW','model'=>'Tiguan','year_from'=>2018,'year_to'=>2024,'notes'=>'EA888 2.0T accessories may be shared — Atlas also has VR6 variant'],
             ['brand'=>'Audi','model'=>'Q7','year_from'=>2020,'year_to'=>2024,'notes'=>'3.0T TFSI V6 accessories may cross — verify engine code']],
            'SUV', 'EA888 2.0T or VR6 3.6L. 2.0T accessories shared with Tiguan MQB family.', 'Engine Assembly');

        // =====================================================================
        // AIRBAG UNIVERSAL NOTES (applies to all brands)
        // =====================================================================
        $records[] = $r('Airbag', 'Toyota', 'All Models', 2018, 2024,
            [], 'All',
            'CRITICAL SAFETY NOTE: Airbags must NEVER be interchanged across brands, generations, or trim levels. Each airbag is programmed to the specific vehicle\'s SRS module. Always match: (1) Exact year range (2) Exact brand and model (3) Exact position — Front Driver/Passenger, Knee, Roof, Seat, Wheel (4) Body style — Sedan vs Coupe (5) Seat type — cloth vs leather (6) Origin — Japan vs North America built. An incorrect airbag may fail to deploy or deploy incorrectly causing serious injury.',
            'Airbag - ALL POSITIONS');

        $records[] = $r('Airbag', 'Honda', 'All Models', 2016, 2024,
            [], 'All',
            'CRITICAL SAFETY NOTE: Airbags must match exact year, model, position, seat material, body style. Honda/Acura airbags are NOT interchangeable despite shared platforms. Always match SRS module compatibility.',
            'Airbag - ALL POSITIONS');

        $records[] = $r('Airbag', 'Nissan', 'All Models', 2019, 2024,
            [], 'All',
            'CRITICAL SAFETY NOTE: Nissan/Infiniti airbags are platform-matched but NOT interchangeable across brands. Verify exact part number with donor VIN before selling.',
            'Airbag - ALL POSITIONS');

        // Insert all records in chunks of 50
        $chunks = array_chunk($records, 50);
        foreach ($chunks as $chunk) {
            DB::table('parts_compatibility')->insert($chunk);
        }
    }
};
