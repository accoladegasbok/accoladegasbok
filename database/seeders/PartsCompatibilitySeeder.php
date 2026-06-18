<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PartsCompatibilitySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('parts_compatibility')->truncate();
        $now = now();

        $r = function (
            string  $category,
            string  $brand,
            string  $model,
            int     $yFrom,
            int     $yTo,
            array   $alsoFits   = [],
            string  $bodyStyle  = 'All',
            string  $note       = '',
            ?string $subcat     = null,
            ?string $engineMatch = null,
            ?string $sideMatch  = null
        ) use ($now): array {
            return [
                'part_category'    => $category,
                'part_subcategory' => $subcat,
                'brand'            => $brand,
                'model'            => $model,
                'year_from'        => $yFrom,
                'year_to'          => $yTo,
                'body_style_match' => $bodyStyle,
                'engine_match'     => $engineMatch,
                'side_match'       => $sideMatch,
                'also_fits'        => json_encode($alsoFits),
                'does_not_fit'     => json_encode([]),
                'interchange_note' => mb_substr($note, 0, 250),
                'verified'         => true,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        };

        $af = fn($brand, $model, $yf, $yt, $notes) => [
            'brand' => $brand, 'model' => $model,
            'year_from' => $yf, 'year_to' => $yt, 'notes' => $notes
        ];

        $records = [

            // ── TOYOTA CAMRY ─────────────────────────────────────────────────
            $r('Body','Toyota','Camry',2018,2021,[],'Sedan','Pre-facelift front bumper. Does NOT fit 2022+.','Front Bumper Cover'),
            $r('Body','Toyota','Camry',2022,2024,[],'Sedan','Facelift front bumper. Does NOT fit 2018-2021.','Front Bumper Cover'),
            $r('Body','Toyota','Camry',2018,2024,[],'Sedan','Tail lamp Sedan only. D/S and P/S within same year range.','Tail Lamp Assembly',null,'Both'),
            $r('Engine','Toyota','Camry',2018,2024,[
                $af('Lexus','ES350',2019,2024,'TNGA-K platform — 2GR-FKS V6 accessories compatible'),
                $af('Toyota','Avalon',2019,2022,'Same 3.5L V6 family engine accessories'),
            ],'All','Verify engine: 2.5L (A25A-FXS) vs 3.5L (2GR-FKS). Cross only within same code.','Engine Assembly','2.5L I4 / 3.5L V6'),
            $r('Transmission','Toyota','Camry',2018,2024,[
                $af('Lexus','ES350',2019,2024,'8-speed auto shared on V6 TNGA-K'),
                $af('Toyota','Avalon',2019,2022,'Same 8-speed auto on 3.5L'),
            ],'All','8-speed auto (V6) or CVT (4-cyl). Must match engine type.','Transmission'),
            $r('Suspension','Toyota','Camry',2018,2024,[
                $af('Lexus','ES350',2019,2024,'TNGA-K front struts — springs differ, strut body compatible'),
                $af('Toyota','Avalon',2019,2022,'Shared TNGA-K front suspension'),
            ],'All','FWD only — AWD uses different rear setup.','Front Strut Assembly',null,'Both'),
            $r('Cooling','Toyota','Camry',2018,2024,[
                $af('Lexus','ES350',2019,2024,'Radiator compatible on TNGA-K — verify tank positions'),
            ],'All','Match engine: 2.5L and 3.5L use different radiator sizes.','Radiator'),
            $r('Electrical','Toyota','Camry',2018,2024,[
                $af('Lexus','ES350',2019,2024,'Alternator compatible on same engine variant'),
            ],'All','Match engine type and amperage output.','Alternator'),
            $r('Interior','Toyota','Camry',2018,2024,[],'Sedan','Match all 4: cloth/leather, electric/manual, Japan/NA built, LH/RH.','Front Seat Assembly'),
            $r('Airbag','Toyota','Camry',2018,2024,[],'Sedan','CRITICAL: Match exact position, seat material, body style, origin. Never cross brands.','Airbag Assembly'),

            // ── TOYOTA RAV4 ──────────────────────────────────────────────────
            $r('Body','Toyota','RAV4',2019,2021,[],'SUV','Pre-facelift front clip. Does NOT fit 2022+.','Front Bumper/Headlights'),
            $r('Body','Toyota','RAV4',2022,2024,[],'SUV','Facelift front clip. Does NOT fit 2019-2021.','Front Bumper/Headlights'),
            $r('Engine','Toyota','RAV4',2019,2024,[
                $af('Lexus','NX350',2022,2024,'T24A-FTS 2.4T engine accessories shared on TNGA-K'),
                $af('Toyota','Venza',2021,2024,'A25A-FXS hybrid engine accessories shared'),
            ],'All','Verify variant: 2.5L gas, 2.5L hybrid, or 2.4T.','Engine Assembly'),
            $r('Suspension','Toyota','RAV4',2019,2024,[
                $af('Toyota','Venza',2021,2024,'Shared TNGA-K platform front struts'),
            ],'All','AWD vs FWD rear suspension differs.','Front Strut Assembly'),

            // ── TOYOTA COROLLA ───────────────────────────────────────────────
            $r('Engine','Toyota','Corolla',2019,2024,[
                $af('Toyota','C-HR',2018,2022,'M20A engine accessories may be shared'),
            ],'All','TNGA-C platform. Sedan and Hatchback body panels differ.','Engine Assembly'),

            // ── TOYOTA TACOMA ────────────────────────────────────────────────
            $r('Engine','Toyota','Tacoma',2016,2023,[
                $af('Toyota','4Runner',2016,2022,'1GR-FE V6 engine accessories largely shared'),
                $af('Toyota','Tundra',2014,2021,'2TR-FE 4cyl accessories shared'),
            ],'Truck','2.7L 2TR-FE or 3.5L 2GR-FKS. Match engine variant.','Engine Assembly'),
            $r('Body','Toyota','Tacoma',2016,2019,[],'Truck','Tail lamp pre-facelift. Does NOT fit 2020+.','Tail Lamp Assembly',null,'Both'),
            $r('Body','Toyota','Tacoma',2020,2023,[],'Truck','Tail lamp facelift. Does NOT fit 2016-2019.','Tail Lamp Assembly',null,'Both'),

            // ── TOYOTA HIGHLANDER ─────────────────────────────────────────────
            $r('Engine','Toyota','Highlander',2020,2024,[
                $af('Lexus','RX350',2020,2022,'2GR-FKS 3.5L V6 accessories shared on TNGA-K'),
                $af('Toyota','Sienna',2021,2024,'A25A-FXS hybrid engine accessories shared'),
            ],'All','Verify variant: 3.5L V6 or 2.5L hybrid.','Engine Assembly'),

            // ── LEXUS ES350 ──────────────────────────────────────────────────
            $r('Engine','Lexus','ES350',2019,2024,[
                $af('Toyota','Camry',2018,2024,'TNGA-K — 2GR-FKS V6 engine accessories compatible'),
                $af('Toyota','Avalon',2019,2022,'Same 2GR-FKS V6 engine family'),
            ],'Sedan','Engine accessories share TNGA-K with Camry V6. Body panels do NOT cross.','Engine Assembly','3.5L V6'),
            $r('Transmission','Lexus','ES350',2019,2024,[
                $af('Toyota','Camry',2018,2024,'8-speed auto shared on V6 TNGA-K'),
            ],'Sedan','8-speed auto shared with Camry V6.','Transmission'),
            $r('Suspension','Lexus','ES350',2019,2024,[
                $af('Toyota','Camry',2018,2024,'TNGA-K struts — spring rates differ. Strut body compatible, springs NOT'),
            ],'Sedan','Front strut body compatible with Camry. Springs are sport-tuned — do not swap.','Front Strut Assembly'),
            $r('Electrical','Lexus','ES350',2019,2024,[
                $af('Toyota','Camry',2018,2024,'Alternator: same 2GR-FKS engine — compatible'),
            ],'Sedan','Alternator compatible with Camry V6 on same engine family.','Alternator'),
            $r('Body','Lexus','ES350',2019,2024,[],'Sedan','Lexus-specific body panels. Do NOT cross with Toyota Camry.','Body Panels'),

            // ── LEXUS NX ─────────────────────────────────────────────────────
            $r('Engine','Lexus','NX350',2022,2024,[
                $af('Toyota','RAV4',2022,2024,'T24A-FTS 2.4T accessories shared on TNGA-K'),
            ],'SUV','2.4T T24A-FTS: accessories shared with RAV4 Turbo.','Engine Assembly','2.4L Turbo'),

            // ── LEXUS RX ─────────────────────────────────────────────────────
            $r('Engine','Lexus','RX350',2016,2022,[
                $af('Toyota','Highlander',2014,2019,'2GR-FKS V6 engine accessories shared'),
                $af('Toyota','Sienna',2015,2020,'2GR-FE V6 some accessories compatible'),
            ],'SUV','V6 accessories compatible with Highlander same-generation.','Engine Assembly','3.5L V6'),

            // ── HONDA ACCORD ─────────────────────────────────────────────────
            $r('Engine','Honda','Accord',2018,2022,[
                $af('Acura','TLX',2021,2023,'K20C/K24C engine accessories compatible'),
                $af('Honda','CR-V',2017,2022,'L15B7 1.5T accessories shared'),
                $af('Honda','Passport',2019,2022,'J35Y6 V6 accessories shared on V6 Accord'),
            ],'All','1.5T, 2.0T, 3.5L V6. Accessories cross within same engine family only.','Engine Assembly'),
            $r('Transmission','Honda','Accord',2018,2022,[
                $af('Acura','TLX',2021,2023,'10-speed auto shared on 2.0T variants'),
            ],'All','CVT (1.5T), 10-speed auto (2.0T), 9-speed auto (V6). Match engine.','Transmission'),
            $r('Suspension','Honda','Accord',2018,2022,[
                $af('Acura','TLX',2021,2023,'FWD front strut platform shared — spring rates differ'),
            ],'Sedan','FWD Accord shares platform with TLX FWD. AWD TLX is different.','Front Strut Assembly'),
            $r('Body','Honda','Accord',2018,2020,[],'Sedan','Front bumper pre-facelift 2018-2020. Does NOT fit 2021+.','Front Bumper Cover'),
            $r('Body','Honda','Accord',2021,2022,[],'Sedan','Front bumper facelift 2021-2022. Does NOT fit 2018-2020.','Front Bumper Cover'),

            // ── HONDA CIVIC ──────────────────────────────────────────────────
            $r('Engine','Honda','Civic',2016,2021,[
                $af('Acura','ILX',2016,2022,'K20C2 engine accessories shared'),
                $af('Honda','CR-V',2017,2022,'L15B7 1.5T accessories shared'),
            ],'All','2.0L R20A, 1.5T L15B7, or 1.5T Si. Match engine code.','Engine Assembly'),
            $r('Body','Honda','Civic',2016,2021,[],'All','Sedan, Coupe, Hatchback all differ — body style must match exactly.','Body Panels'),

            // ── HONDA CR-V ───────────────────────────────────────────────────
            $r('Engine','Honda','CR-V',2017,2022,[
                $af('Honda','Accord',2018,2022,'L15B7 1.5T accessories shared'),
                $af('Honda','Civic',2016,2021,'L15B7 accessories shared across Honda family'),
            ],'SUV','1.5T L15B7 or 2.4L K24W2. Accessories cross within 1.5T Honda family.','Engine Assembly'),
            $r('Body','Honda','CR-V',2017,2019,[],'SUV','Front bumper pre-facelift 2017-2019. Does NOT fit 2020+.','Front Bumper Cover'),
            $r('Body','Honda','CR-V',2020,2022,[],'SUV','Front bumper facelift 2020-2022. Does NOT fit 2017-2019.','Front Bumper Cover'),

            // ── ACURA TLX ────────────────────────────────────────────────────
            $r('Engine','Acura','TLX',2021,2023,[
                $af('Honda','Accord',2018,2022,'K20C4 2.0T engine accessories may be compatible'),
                $af('Acura','RDX',2019,2022,'K20C4 shared — engine accessories compatible'),
            ],'Sedan','2.0T K20C4 or 3.0T. Accessories cross within same engine family.','Engine Assembly','2.0L Turbo'),
            $r('Suspension','Acura','TLX',2021,2023,[
                $af('Honda','Accord',2018,2022,'FWD front strut platform shared — Acura spring rates firmer'),
            ],'Sedan','FWD shares platform with Accord. SH-AWD uses different rear.','Front Strut Assembly'),

            // ── ACURA ILX ────────────────────────────────────────────────────
            $r('Engine','Acura','ILX',2016,2022,[
                $af('Honda','Civic',2016,2021,'K20C2 engine accessories shared — Civic Si same engine'),
            ],'Sedan','K20C2 2.0L — accessories shared with 10th gen Civic.','Engine Assembly'),

            // ── ACURA RDX ────────────────────────────────────────────────────
            $r('Engine','Acura','RDX',2019,2022,[
                $af('Acura','TLX',2021,2023,'K20C4 2.0T engine accessories shared'),
                $af('Honda','Accord',2018,2022,'K20C4 some engine accessories compatible'),
            ],'SUV','K20C4 2.0T: accessories shared with TLX and Accord 2.0T.','Engine Assembly','2.0L Turbo'),

            // ── NISSAN ALTIMA ────────────────────────────────────────────────
            $r('Engine','Nissan','Altima',2019,2024,[
                $af('Nissan','Sentra',2020,2024,'Some MR20DD 2.0L accessories shared'),
                $af('Nissan','Rogue',2021,2024,'KR20DDET 1.5T accessories shared on turbo variants'),
            ],'All','2.5L PR25DD or 2.0T KR20DDET. Cross within same engine family only.','Engine Assembly'),
            $r('Transmission','Nissan','Altima',2019,2024,[
                $af('Nissan','Rogue',2021,2024,'Xtronic CVT generation — verify TCM compatibility'),
            ],'All','Xtronic CVT. FWD vs AWD units differ — must match drivetrain.','Transmission'),
            $r('Body','Nissan','Altima',2019,2021,[],'Sedan','Front bumper pre-facelift 2019-2021. Does NOT fit 2022+.','Front Bumper Cover'),
            $r('Body','Nissan','Altima',2022,2024,[],'Sedan','Front bumper facelift 2022-2024.','Front Bumper Cover'),

            // ── NISSAN ROGUE ─────────────────────────────────────────────────
            $r('Engine','Nissan','Rogue',2021,2024,[
                $af('Infiniti','QX50',2019,2024,'KR20DDET VC-Turbo engine accessories shared'),
                $af('Nissan','Altima',2019,2024,'KR20DDET accessories shared on turbo variants'),
            ],'SUV','KR20DDET 1.5T VC-Turbo accessories shared with QX50 and Altima.','Engine Assembly','1.5L Turbo'),

            // ── NISSAN PATHFINDER ─────────────────────────────────────────────
            $r('Engine','Nissan','Pathfinder',2013,2021,[
                $af('Infiniti','QX60',2013,2021,'VQ35DE 3.5L V6 — SAME engine, SAME D platform'),
                $af('Nissan','Murano',2015,2021,'VQ35DE V6 accessories shared'),
            ],'SUV','VQ35DE 3.5L V6: fully shared with QX60 on D platform.','Engine Assembly','3.5L V6'),
            $r('Transmission','Nissan','Pathfinder',2013,2021,[
                $af('Infiniti','QX60',2013,2021,'CVT: same unit as Pathfinder on D platform'),
            ],'SUV','CVT: Pathfinder and QX60 share the SAME CVT unit.','Transmission'),

            // ── INFINITI Q50 ─────────────────────────────────────────────────
            $r('Engine','Infiniti','Q50',2014,2024,[
                $af('Infiniti','Q60',2017,2022,'VR30DDTT 3.0T twin-turbo: SAME engine — ALL accessories interchangeable'),
            ],'Sedan','VR30DDTT 3.0T fully shared with Q60. 2.0T M274 does NOT cross.','Engine Assembly','2.0T / 3.0T BiTurbo'),
            $r('Transmission','Infiniti','Q50',2014,2024,[
                $af('Infiniti','Q60',2017,2022,'7-speed auto shared on VR30DDTT — RWD/AWD from same family'),
            ],'Sedan','7-speed automatic. RWD and AWD units differ — match drivetrain.','Transmission'),
            $r('Suspension','Infiniti','Q50',2014,2024,[
                $af('Infiniti','Q60',2017,2022,'FM platform front strut shared — same assembly'),
            ],'Sedan','FM platform shared with Q60. Sport Package has stiffer springs.','Front Strut Assembly'),
            $r('Body','Infiniti','Q50',2014,2017,[],'Sedan','Body panels 2014-2017. 2018+ has revised front — not interchangeable.','Body Panels'),
            $r('Body','Infiniti','Q50',2018,2024,[],'Sedan','Body panels 2018-2024 revised front. Does not fit 2014-2017.','Body Panels'),

            // ── INFINITI QX60 ────────────────────────────────────────────────
            $r('Engine','Infiniti','QX60',2013,2021,[
                $af('Nissan','Pathfinder',2013,2021,'VQ35DE 3.5L V6 — SAME engine, SAME D platform'),
                $af('Nissan','Murano',2015,2021,'VQ35DE accessories shared'),
            ],'SUV','VQ35DE: same engine as Nissan Pathfinder — all accessories compatible.','Engine Assembly','3.5L V6'),
            $r('Transmission','Infiniti','QX60',2013,2021,[
                $af('Nissan','Pathfinder',2013,2021,'CVT: same unit as Pathfinder on D platform'),
            ],'SUV','CVT: SAME unit as Nissan Pathfinder.','Transmission'),

            // ── INFINITI Q60 ─────────────────────────────────────────────────
            $r('Engine','Infiniti','Q60',2017,2022,[
                $af('Infiniti','Q50',2014,2024,'VR30DDTT: fully shared — identical engine, ALL accessories interchangeable'),
            ],'Coupe','VR30DDTT 3.0T twin-turbo: fully shared with Q50.','Engine Assembly','3.0L BiTurbo'),

            // ── KIA K5 ───────────────────────────────────────────────────────
            $r('Engine','Kia','K5',2021,2024,[
                $af('Hyundai','Sonata',2020,2024,'G2.5 / T-GDi 1.6T engine accessories shared on N-Line'),
                $af('Hyundai','Tucson',2022,2024,'T-GDi 1.6T accessories shared'),
            ],'Sedan','G2.5 or 1.6T T-GDi. Accessories cross within Hyundai Group N-Line.','Engine Assembly'),
            $r('Transmission','Kia','K5',2021,2024,[
                $af('Hyundai','Sonata',2020,2024,'8-speed DCT or 8-speed auto shared on N-Line platform'),
            ],'Sedan','8-speed DCT or 8-speed auto shared with Sonata.','Transmission'),
            $r('Suspension','Kia','K5',2021,2024,[
                $af('Hyundai','Sonata',2020,2024,'N-Line platform front strut shared — sport vs base springs differ'),
            ],'Sedan','N-Line platform shared with Sonata. Sport springs differ from base.','Front Strut Assembly'),
            $r('Body','Kia','K5',2021,2024,[],'Sedan','Kia-specific body panels. Do NOT cross with Hyundai Sonata.','Body Panels'),

            // ── KIA SPORTAGE ─────────────────────────────────────────────────
            $r('Engine','Kia','Sportage',2023,2024,[
                $af('Hyundai','Tucson',2022,2024,'T-GDi 1.6T / 2.5L accessories fully shared on N3 platform'),
            ],'SUV','N3 platform: engine accessories shared with Tucson.','Engine Assembly'),
            $r('Suspension','Kia','Sportage',2023,2024,[
                $af('Hyundai','Tucson',2022,2024,'N3 platform front strut: same assembly'),
            ],'SUV','Front strut shared with Tucson 2022+ on N3 platform.','Front Strut Assembly'),

            // ── KIA SORENTO ──────────────────────────────────────────────────
            $r('Engine','Kia','Sorento',2021,2024,[
                $af('Hyundai','Santa Fe',2021,2024,'2.5T / 3.5L V6 engine accessories shared'),
                $af('Hyundai','Palisade',2020,2024,'3.8L Lambda3 V6 accessories shared'),
            ],'SUV','2.5T or 3.5L V6 accessories cross with Santa Fe and Palisade.','Engine Assembly'),

            // ── HYUNDAI SONATA ───────────────────────────────────────────────
            $r('Engine','Hyundai','Sonata',2020,2024,[
                $af('Kia','K5',2021,2024,'G2.5 / T-GDi 1.6T engine accessories shared on N-Line'),
                $af('Hyundai','Tucson',2022,2024,'1.6T T-GDi accessories shared'),
            ],'Sedan','G2.5 or 1.6T T-GDi: accessories cross with K5 and Tucson 1.6T.','Engine Assembly'),
            $r('Suspension','Hyundai','Sonata',2020,2024,[
                $af('Kia','K5',2021,2024,'N-Line platform front strut shared — sport vs base differ'),
            ],'Sedan','Front strut shared with K5. Sport springs differ from base.','Front Strut Assembly'),
            $r('Transmission','Hyundai','Sonata',2020,2024,[
                $af('Kia','K5',2021,2024,'8-speed DCT or 8-speed auto shared on N-Line platform'),
            ],'Sedan','8-speed DCT (1.6T) or 8-speed auto (2.5L) shared with K5.','Transmission'),

            // ── HYUNDAI TUCSON ───────────────────────────────────────────────
            $r('Engine','Hyundai','Tucson',2022,2024,[
                $af('Kia','Sportage',2023,2024,'T-GDi 1.6T / 2.5L accessories fully shared on N3 platform'),
                $af('Hyundai','Sonata',2020,2024,'1.6T T-GDi accessories shared across Hyundai Group'),
            ],'SUV','N3 platform: accessories cross with Sportage and Sonata 1.6T.','Engine Assembly'),
            $r('Suspension','Hyundai','Tucson',2022,2024,[
                $af('Kia','Sportage',2023,2024,'N3 platform front strut: Tucson and Sportage share same assembly'),
            ],'SUV','Front strut shared with Sportage 2023+.','Front Strut Assembly'),

            // ── HYUNDAI SANTA FE ─────────────────────────────────────────────
            $r('Engine','Hyundai','Santa Fe',2021,2024,[
                $af('Kia','Sorento',2021,2024,'2.5T / 3.5L V6 engine accessories shared'),
                $af('Hyundai','Palisade',2020,2024,'3.8L Lambda3 V6 accessories shared'),
            ],'SUV','2.5T or 3.5L/3.8L V6 accessories shared with Sorento and Palisade.','Engine Assembly'),

            // ── FORD F-150 ───────────────────────────────────────────────────
            $r('Engine','Ford','F-150',2021,2024,[
                $af('Ford','Expedition',2022,2024,'3.5L EcoBoost engine accessories shared'),
                $af('Lincoln','Navigator',2022,2024,'3.5L EcoBoost accessories largely shared'),
            ],'Truck','2.7L EcoBoost, 3.5L EcoBoost, or 5.0L V8. Cross within same engine family.','Engine Assembly'),
            $r('Transmission','Ford','F-150',2021,2024,[
                $af('Ford','Expedition',2022,2024,'10-speed SelectShift shared on 3.5L — same unit'),
            ],'Truck','10-speed auto shared with Expedition on 3.5L.','Transmission'),
            $r('Body','Ford','F-150',2021,2024,[],'Truck','2021-2024 14th gen. Does NOT fit 2015-2020 13th gen.','Body Panels'),
            $r('Body','Ford','F-150',2015,2020,[],'Truck','2015-2020 13th gen aluminum body. Does NOT fit 2021+.','Body Panels'),
            $r('Engine','Ford','Mustang',2015,2023,[
                $af('Ford','F-150',2015,2020,'5.0L Coyote V8 accessories may be shared — verify generation'),
            ],'All','5.0L Coyote V8 or 2.3L EcoBoost. S550 platform 2015-2023.','Engine Assembly'),
            $r('Engine','Ford','Explorer',2020,2024,[
                $af('Lincoln','Aviator',2020,2024,'CD6 RWD platform — 3.0T engine accessories compatible'),
            ],'SUV','RWD-based CD6 platform shared with Aviator.','Engine Assembly'),

            // ── CHEVROLET SILVERADO ──────────────────────────────────────────
            $r('Engine','Chevrolet','Silverado 1500',2019,2024,[
                $af('GM','Sierra 1500',2019,2024,'T1 platform: virtually ALL engine components shared'),
                $af('Chevrolet','Tahoe',2021,2024,'EcoTec3 engine family accessories shared'),
                $af('Chevrolet','Suburban',2021,2024,'Same EcoTec3 engine — accessories fully compatible'),
                $af('GM','Yukon',2021,2024,'EcoTec3 accessories shared'),
            ],'Truck','EcoTec3 V6 or V8 (L84/L87/L8T): accessories shared across GM full-size trucks.','Engine Assembly','2.7T / 5.3L V8 / 6.2L V8'),
            $r('Transmission','Chevrolet','Silverado 1500',2019,2024,[
                $af('GM','Sierra 1500',2019,2024,'10-speed Hydra-Matic: SAME unit on V8 — fully shared'),
                $af('Chevrolet','Tahoe',2021,2024,'10-speed auto shared across EcoTec3 V8 family'),
            ],'Truck','10-speed Hydra-Matic shared across GM T1 full-size truck family.','Transmission'),
            $r('Body','Chevrolet','Silverado 1500',2019,2024,[
                $af('GM','Sierra 1500',2019,2024,'Cab structure shared — front clip is Silverado-only, NOT for Sierra'),
            ],'Truck','Cab structure shared with Sierra. Front clip Silverado-specific.','Body Panels'),
            $r('Suspension','Chevrolet','Silverado 1500',2019,2024,[
                $af('GM','Sierra 1500',2019,2024,'T1 platform front suspension shared between Silverado and Sierra'),
            ],'Truck','2WD uses struts, 4WD uses torsion bar — both shared with Sierra.','Front Strut Assembly'),

            // ── GM SIERRA ────────────────────────────────────────────────────
            $r('Engine','GM','Sierra 1500',2019,2024,[
                $af('Chevrolet','Silverado 1500',2019,2024,'T1 platform: virtually ALL engine components shared'),
            ],'Truck','EcoTec3 V6 or V8: all accessories shared with Silverado on T1 platform.','Engine Assembly','2.7T / 5.3L V8 / 6.2L V8'),
            $r('Body','GM','Sierra 1500',2019,2024,[
                $af('Chevrolet','Silverado 1500',2019,2024,'Cab structure shared — front clip is Sierra-specific, NOT for Silverado'),
            ],'Truck','Cab structure shared with Silverado. Front clip is Sierra-specific.','Body Panels'),

            // ── CHEVROLET EQUINOX ─────────────────────────────────────────────
            $r('Engine','Chevrolet','Equinox',2018,2024,[
                $af('GM','Terrain',2018,2024,'D2 platform: 1.5T / 2.0T accessories shared'),
                $af('Chevrolet','Malibu',2016,2024,'LTG 2.0T engine accessories shared'),
            ],'SUV','1.5T LFV or 2.0T LTG: accessories shared with GMC Terrain and Malibu.','Engine Assembly'),
            $r('Suspension','Chevrolet','Equinox',2018,2024,[
                $af('GM','Terrain',2018,2024,'D2 platform front strut shared with GMC Terrain'),
            ],'SUV','Front strut: D2 platform shared with GMC Terrain.','Front Strut Assembly'),

            // ── MERCEDES-BENZ C-CLASS ─────────────────────────────────────────
            $r('Engine','Mercedes-Benz','C-Class',2022,2024,[
                $af('Mercedes-Benz','GLC',2023,2024,'M254 / OM654d engine accessories shared on MRA2'),
            ],'Sedan','M254 2.0T or OM654d 2.0 diesel. Accessories cross within MRA2 platform.','Engine Assembly','2.0L Turbo'),
            $r('Suspension','Mercedes-Benz','C-Class',2022,2024,[
                $af('Mercedes-Benz','GLC',2023,2024,'MRA2 front strut shared — different spring rates SUV vs sedan'),
            ],'Sedan','W206 platform. AMG Line has stiffer springs — verify trim.','Front Strut Assembly'),
            $r('Body','Mercedes-Benz','C-Class',2015,2018,[],'Sedan','W205 pre-facelift 2015-2018. Does NOT fit 2019-2021 or 2022+ W206.','Front Bumper Cover'),
            $r('Body','Mercedes-Benz','C-Class',2019,2021,[],'Sedan','W205 facelift 2019-2021. Does NOT fit pre-2019 or 2022+ W206.','Front Bumper Cover'),
            $r('Body','Mercedes-Benz','C-Class',2022,2024,[],'Sedan','W206 generation. Completely new body — no parts cross to W205.','Body Panels'),
            $r('Engine','Mercedes-Benz','E-Class',2017,2023,[
                $af('Mercedes-Benz','GLE',2020,2023,'M276/M256 engine accessories shared on MRA platform'),
                $af('Mercedes-Benz','GLC',2016,2022,'M274 engine family accessories — verify engine code'),
            ],'Sedan','M274 2.0T, M276 3.0T V6, or M256 3.0L inline-6. Cross within same engine code.','Engine Assembly'),
            $r('Body','Mercedes-Benz','E-Class',2017,2020,[],'Sedan','W213 pre-facelift 2017-2020. Sedan/Wagon/Coupe differ on rear body.','Body Panels'),
            $r('Body','Mercedes-Benz','E-Class',2021,2023,[],'Sedan','W213 facelift 2021-2023. Front bumper and headlights changed.','Body Panels'),
            $r('Engine','Mercedes-Benz','GLC',2016,2022,[
                $af('Mercedes-Benz','C-Class',2015,2021,'M274 2.0T engine accessories shared on MRA platform'),
            ],'SUV','M274 2.0T or M276 3.0T: accessories shared with C-Class on MRA.','Engine Assembly'),
            $r('Body','Mercedes-Benz','GLC',2016,2019,[],'SUV','X253 pre-facelift 2016-2019. Does NOT fit 2020-2022 facelift.','Body Panels'),
            $r('Body','Mercedes-Benz','GLC',2020,2022,[],'SUV','X253 facelift 2020-2022. Does NOT fit 2016-2019 pre-facelift.','Body Panels'),

            // ── VOLKSWAGEN JETTA ─────────────────────────────────────────────
            $r('Engine','VW','Jetta',2019,2024,[
                $af('VW','Golf',2015,2021,'EA888 1.4T/2.0T accessories shared on MQB platform'),
                $af('VW','Tiguan',2018,2024,'EA888 Gen3 2.0T accessories shared on MQB'),
                $af('Audi','A3',2015,2020,'MQB platform: EA888 accessories largely shared'),
            ],'Sedan','EA888 1.4T or 2.0T: accessories shared across VW/Audi MQB platform.','Engine Assembly','1.4L Turbo / 2.0L Turbo'),
            $r('Transmission','VW','Jetta',2019,2024,[
                $af('VW','Golf',2015,2021,'7-speed DSG DQ200 shared on FWD MQB'),
                $af('VW','Tiguan',2018,2024,'7-speed DSG shared on FWD variants'),
                $af('Audi','A3',2015,2020,'7-speed DSG shared on FWD — AWD uses different gearbox'),
            ],'Sedan','7-speed DSG or 6-speed manual: shared across MQB FWD vehicles.','Transmission'),
            $r('Suspension','VW','Jetta',2019,2024,[
                $af('VW','Golf',2015,2021,'MQB front strut shared on FWD'),
                $af('Audi','A3',2015,2020,'MQB FWD front strut compatible — spring rates may differ'),
            ],'Sedan','MQB FWD front strut shared with Golf and Audi A3.','Front Strut Assembly'),
            $r('Electrical','VW','Jetta',2019,2024,[
                $af('VW','Golf',2015,2021,'Alternator: EA888 engine compatible across MQB'),
                $af('VW','Tiguan',2018,2024,'Alternator: EA888 2.0T compatible across MQB'),
                $af('Audi','A3',2015,2020,'Alternator: EA888 engine shared — compatible'),
            ],'All','Alternator: EA888 family compatible across ALL MQB vehicles.','Alternator'),

            // ── VOLKSWAGEN TIGUAN ─────────────────────────────────────────────
            $r('Engine','VW','Tiguan',2018,2024,[
                $af('VW','Jetta',2019,2024,'EA888 2.0T accessories shared on MQB'),
                $af('VW','Golf',2015,2021,'EA888 Gen3 accessories shared'),
                $af('Audi','Q3',2019,2024,'MQB-A platform: 2.0T accessories largely shared'),
            ],'SUV','EA888 Gen3 2.0T: accessories shared across MQB FWD/AWD platform.','Engine Assembly','2.0L Turbo'),
            $r('Suspension','VW','Tiguan',2018,2024,[
                $af('VW','Jetta',2019,2024,'MQB front strut shared on FWD variants'),
            ],'SUV','MQB platform. 4Motion AWD uses different rear — verify drivetrain.','Front Strut Assembly'),
            $r('Body','VW','Tiguan',2018,2020,[],'SUV','Pre-facelift front bumper 2018-2020. Does NOT fit 2021+.','Front Bumper Cover'),
            $r('Body','VW','Tiguan',2021,2024,[],'SUV','Facelift front bumper 2021-2024. Does NOT fit 2018-2020.','Front Bumper Cover'),

            // ── VOLKSWAGEN PASSAT ─────────────────────────────────────────────
            $r('Engine','VW','Passat',2016,2022,[
                $af('Audi','A4',2017,2024,'MLB Evo: EA888 2.0T TFSI accessories shared'),
                $af('Audi','A5',2017,2024,'MLB Evo: EA888 accessories shared'),
            ],'Sedan','EA888 Gen3 2.0T: accessories shared with Audi A4 and A5 on MLB Evo.','Engine Assembly','2.0L Turbo'),

            // ── AIRBAG SAFETY NOTES ───────────────────────────────────────────
            $r('Airbag','Toyota','All Models',2015,2024,[],'All','CRITICAL: Must match exact year, model, position, seat material, body style, origin. NEVER cross brands or generations. Incorrect airbag risks non-deployment or injury.','Airbag - ALL POSITIONS'),
            $r('Airbag','Honda','All Models',2016,2024,[],'All','CRITICAL: Honda/Acura airbags NOT interchangeable despite shared platforms. Match exact position, year, model, seat type, body style. Verify SRS module compatibility.','Airbag - ALL POSITIONS'),
            $r('Airbag','Nissan','All Models',2019,2024,[],'All','CRITICAL: Nissan/Infiniti airbags NOT cross-brand interchangeable. Always verify OEM part number with donor VIN before sale.','Airbag - ALL POSITIONS'),
            $r('Airbag','Hyundai','All Models',2020,2024,[],'All','CRITICAL: Hyundai/Kia airbags NOT cross-brand interchangeable. Position, year, model, and seat type must all match exactly.','Airbag - ALL POSITIONS'),
            $r('Airbag','Ford','All Models',2019,2024,[],'All','CRITICAL: Ford airbags are vehicle-specific. Generation changes use completely different SRS systems. Never mix generations.','Airbag - ALL POSITIONS'),
            $r('Airbag','Chevrolet','All Models',2019,2024,[],'All','CRITICAL: GM/Chevrolet airbags — always verify by OEM part number and donor VIN even on shared T1 platform.','Airbag - ALL POSITIONS'),
            $r('Airbag','Mercedes-Benz','All Models',2015,2024,[],'All','CRITICAL: Mercedes airbags are generation and model specific. W205 will NOT fit W206. Never cross model lines or generations.','Airbag - ALL POSITIONS'),
            $r('Airbag','VW','All Models',2019,2024,[],'All','CRITICAL: VW/Audi airbags are vehicle-specific despite MQB sharing. Never interchange between Jetta, Golf, Tiguan, or Audi models.','Airbag - ALL POSITIONS'),
        ];

        $this->command->info('Inserting ' . count($records) . ' compatibility records...');

        foreach (array_chunk($records, 50) as $chunk) {
            DB::table('parts_compatibility')->insert($chunk);
        }

        $this->command->info('✅ Done — ' . count($records) . ' records inserted.');
    }
}
