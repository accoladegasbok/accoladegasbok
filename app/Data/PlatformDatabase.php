<?php
// FILE: app/Data/PlatformDatabase.php
//
// Body/chassis PLATFORM / GENERATION lookup — the missing half of
// interchange that OemDatabase.php (engine/transmission) doesn't cover.
//
// IMPORTANT SCOPE NOTE — read before extending this file:
// "Shares a platform" does NOT mean "shares body panels." Two models
// built on the same chassis (e.g. Camry/Avalon/ES, Sonata/Optima,
// G35/350Z) almost always have completely unique, brand-specific
// exterior sheet metal, lighting, and bumpers. Manufacturers deliberately
// differentiate sibling models' styling.
//
// What genuinely IS shared across a platform, safely and generally:
//   - Suspension geometry and mounting points
//   - Brake system architecture
// What is NOT safely assumed shared across different models/brands
// without direct physical/market confirmation:
//   - Body panels, bumpers, lighting, glass
//   - Interior trim, seats, dashboards
//   - Electrical modules, airbags, wheels
//
// So: cross-model entries in 'shared_vehicles' below are scoped to
// ['Suspension','Brakes'] by default — NOT the full part-category list.
// The one exception is the vehicle's OWN model across its own
// production year range (e.g. 2007 Camry to 2010 Camry) — that isn't
// really a cross-model "interchange" claim, it's normal generation
// continuity, and gets the full category list.
//
// If you have real market/verified knowledge that a specific body,
// interior, or electrical part DOES interchange between two platform
// siblings (the same way pin counts get confirmed), tell Claude and
// it'll be added as a specific override — same pattern as OemDatabase.
//
namespace App\Data;

class PlatformDatabase
{
    // Categories considered safe to claim for CROSS-MODEL platform mates
    // without further confirmation. Deliberately narrow — see note above.
    public const CROSS_MODEL_SAFE_CATEGORIES = ['Suspension', 'Brakes'];

    // Categories available for the vehicle's OWN generation continuity
    // (same model, same generation, different model years) — this is
    // NOT a cross-model claim, so the full list applies.
    public const OWN_GENERATION_CATEGORIES = ['Body', 'Suspension', 'Electrical', 'Interior', 'Cooling', 'Brakes', 'Airbag', 'Seat', 'Wheels'];

    // =========================================================
    // lookup() — returns the platform/generation for a given vehicle,
    // plus every other make/model/year-range sharing that chassis.
    // Each shared_vehicles entry carries its OWN 'categories' list —
    // narrow (Suspension/Brakes) for a different model, full for the
    // vehicle's own generation continuity. Never assume wider sharing
    // than what's listed.
    // =========================================================
    public static function lookup(string $make, string $model, int $year): array
    {
        $make  = strtoupper(trim($make));
        $model = strtoupper(trim($model));

        $default = [
            'platform_code'    => null,
            'generation'       => null,
            'facelift'         => null,
            'body_style'       => null,
            'compat_year_from' => null,
            'compat_year_to'   => null,
            'shared_models'    => [],   // display strings
            'shared_vehicles'  => [],   // structured: ['make','model','year_from','year_to','categories']
        ];

        // ══════════════════════════════════════════════════════
        // TOYOTA CAMRY / AVALON / LEXUS ES — same chassis, but each
        // has fully unique exterior sheet metal. Cross-model entries
        // are Suspension/Brakes ONLY.
        // ══════════════════════════════════════════════════════
        if (($make === 'TOYOTA' && in_array($model, ['CAMRY','AVALON'])) ||
            ($make === 'LEXUS' && $model === 'ES')) {

            if ($year >= 2018) return array_merge($default, [
                'platform_code' => 'TNGA_K', 'generation' => 'XV70/XX50',
                'body_style' => 'Sedan', 'compat_year_from' => 2018, 'compat_year_to' => 2024,
                'shared_models' => ['Toyota Camry (2018-2024) - own generation', 'Toyota Avalon (2019-2022) - chassis-mate only', 'Lexus ES (2019-2024) - chassis-mate only'],
                'shared_vehicles' => [
                    ['make'=>'TOYOTA','model'=>'CAMRY','year_from'=>2018,'year_to'=>2024,'categories'=>self::OWN_GENERATION_CATEGORIES],
                    ['make'=>'TOYOTA','model'=>'AVALON','year_from'=>2019,'year_to'=>2022,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ['make'=>'LEXUS','model'=>'ES','year_from'=>2019,'year_to'=>2024,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                ],
            ]);
            if ($year >= 2012) return array_merge($default, [
                'platform_code' => 'K_PLATFORM', 'generation' => 'XV50/XX40',
                'body_style' => 'Sedan', 'compat_year_from' => 2012, 'compat_year_to' => 2017,
                'shared_models' => ['Toyota Camry (2012-2017) - own generation', 'Toyota Avalon (2013-2018) - chassis-mate only', 'Lexus ES (2013-2018) - chassis-mate only'],
                'shared_vehicles' => [
                    ['make'=>'TOYOTA','model'=>'CAMRY','year_from'=>2012,'year_to'=>2017,'categories'=>self::OWN_GENERATION_CATEGORIES],
                    ['make'=>'TOYOTA','model'=>'AVALON','year_from'=>2013,'year_to'=>2018,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ['make'=>'LEXUS','model'=>'ES','year_from'=>2013,'year_to'=>2018,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                ],
            ]);
            if ($year >= 2007) return array_merge($default, [
                'platform_code' => 'K_PLATFORM', 'generation' => 'XV40',
                'facelift' => $year >= 2010 ? 'post-2010 refresh' : 'pre-2010',
                'body_style' => 'Sedan', 'compat_year_from' => 2007, 'compat_year_to' => 2011,
                'shared_models' => ['Toyota Camry (2007-2011) - own generation'],
                'shared_vehicles' => [
                    ['make'=>'TOYOTA','model'=>'CAMRY','year_from'=>2007,'year_to'=>2011,'categories'=>self::OWN_GENERATION_CATEGORIES],
                ],
            ]);
            if ($year >= 2002) return array_merge($default, [
                'platform_code' => 'K_PLATFORM', 'generation' => 'XV30',
                'body_style' => 'Sedan', 'compat_year_from' => 2002, 'compat_year_to' => 2006,
                'shared_models' => ['Toyota Camry (2002-2006) - own generation', 'Lexus ES300/ES330 (2002-2006) - chassis-mate only'],
                'shared_vehicles' => [
                    ['make'=>'TOYOTA','model'=>'CAMRY','year_from'=>2002,'year_to'=>2006,'categories'=>self::OWN_GENERATION_CATEGORIES],
                    ['make'=>'LEXUS','model'=>'ES','year_from'=>2002,'year_to'=>2006,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                ],
            ]);
            if ($year >= 1997) return array_merge($default, [
                'platform_code' => 'K_PLATFORM', 'generation' => 'XV20',
                'body_style' => 'Sedan', 'compat_year_from' => 1997, 'compat_year_to' => 2001,
                'shared_models' => ['Toyota Camry (1997-2001) - own generation'],
                'shared_vehicles' => [
                    ['make'=>'TOYOTA','model'=>'CAMRY','year_from'=>1997,'year_to'=>2001,'categories'=>self::OWN_GENERATION_CATEGORIES],
                ],
            ]);
        }

        if ($make === 'TOYOTA' && $model === 'SOLARA') {
            if ($year >= 2004) return array_merge($default, [
                'platform_code' => 'K_PLATFORM_COUPE', 'generation' => 'XV30-based coupe',
                'body_style' => 'Coupe/Convertible', 'compat_year_from' => 2004, 'compat_year_to' => 2008,
                'shared_models' => ['Toyota Solara (2004-2008) - own generation'],
                'shared_vehicles' => [['make'=>'TOYOTA','model'=>'SOLARA','year_from'=>2004,'year_to'=>2008,'categories'=>self::OWN_GENERATION_CATEGORIES]],
            ]);
            return array_merge($default, [
                'platform_code' => 'K_PLATFORM_COUPE', 'generation' => 'XV20-based coupe',
                'body_style' => 'Coupe/Convertible', 'compat_year_from' => 1999, 'compat_year_to' => 2003,
                'shared_models' => ['Toyota Solara (1999-2003) - own generation'],
                'shared_vehicles' => [['make'=>'TOYOTA','model'=>'SOLARA','year_from'=>1999,'year_to'=>2003,'categories'=>self::OWN_GENERATION_CATEGORIES]],
            ]);
        }

        // ══════════════════════════════════════════════════════
        // TOYOTA COROLLA / MATRIX — same platform, but Corolla is a
        // sedan while Matrix is a 5-door hatch/wagon — DIFFERENT body
        // style, so Suspension/Brakes only, not body panels, even
        // though Matrix/Vibe (identical body, twin-built) are unusually
        // close — that closeness still needs physical confirmation
        // before treating as auto-approved.
        // ══════════════════════════════════════════════════════
        if ($make === 'TOYOTA' && in_array($model, ['COROLLA','MATRIX'])) {
            if ($year >= 2020) return array_merge($default, [
                'platform_code' => 'TNGA_C', 'generation' => 'E210',
                'body_style' => 'Sedan/Hatchback', 'compat_year_from' => 2020, 'compat_year_to' => 2024,
                'shared_models' => ['Toyota Corolla (2020-2024) - own generation'],
                'shared_vehicles' => [['make'=>'TOYOTA','model'=>'COROLLA','year_from'=>2020,'year_to'=>2024,'categories'=>self::OWN_GENERATION_CATEGORIES]],
            ]);
            if ($year >= 2014) return array_merge($default, [
                'platform_code' => 'E_PLATFORM', 'generation' => 'E170',
                'body_style' => 'Sedan', 'compat_year_from' => 2014, 'compat_year_to' => 2019,
                'shared_models' => ['Toyota Corolla (2014-2019) - own generation'],
                'shared_vehicles' => [['make'=>'TOYOTA','model'=>'COROLLA','year_from'=>2014,'year_to'=>2019,'categories'=>self::OWN_GENERATION_CATEGORIES]],
            ]);
            if ($year >= 2009) return array_merge($default, [
                'platform_code' => 'E_PLATFORM', 'generation' => 'E140/E150',
                'body_style' => 'Sedan/Hatchback', 'compat_year_from' => 2009, 'compat_year_to' => 2013,
                'shared_models' => ['Toyota Corolla (2009-2013) - own generation', 'Toyota Matrix (2009-2013) - different body style, chassis-mate only', 'Pontiac Vibe (2009-2010) - different body style, chassis-mate only'],
                'shared_vehicles' => [
                    ['make'=>'TOYOTA','model'=>'COROLLA','year_from'=>2009,'year_to'=>2013,'categories'=>self::OWN_GENERATION_CATEGORIES],
                    ['make'=>'TOYOTA','model'=>'MATRIX','year_from'=>2009,'year_to'=>2013,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ['make'=>'PONTIAC','model'=>'VIBE','year_from'=>2009,'year_to'=>2010,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                ],
            ]);
            if ($year >= 2003) return array_merge($default, [
                'platform_code' => 'E_PLATFORM', 'generation' => 'E120/E130',
                'body_style' => 'Sedan/Hatchback', 'compat_year_from' => 2003, 'compat_year_to' => 2008,
                'shared_models' => ['Toyota Corolla (2003-2008) - own generation', 'Toyota Matrix (2003-2008) - different body style, chassis-mate only', 'Pontiac Vibe (2003-2008) - different body style, chassis-mate only'],
                'shared_vehicles' => [
                    ['make'=>'TOYOTA','model'=>'COROLLA','year_from'=>2003,'year_to'=>2008,'categories'=>self::OWN_GENERATION_CATEGORIES],
                    ['make'=>'TOYOTA','model'=>'MATRIX','year_from'=>2003,'year_to'=>2008,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ['make'=>'PONTIAC','model'=>'VIBE','year_from'=>2003,'year_to'=>2008,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                ],
            ]);
            if ($year >= 1998) return array_merge($default, [
                'platform_code' => 'E_PLATFORM', 'generation' => 'E110',
                'body_style' => 'Sedan', 'compat_year_from' => 1998, 'compat_year_to' => 2002,
                'shared_models' => ['Toyota Corolla (1998-2002) - own generation'],
                'shared_vehicles' => [['make'=>'TOYOTA','model'=>'COROLLA','year_from'=>1998,'year_to'=>2002,'categories'=>self::OWN_GENERATION_CATEGORIES]],
            ]);
            return array_merge($default, [
                'platform_code' => 'E_PLATFORM', 'generation' => 'E100',
                'body_style' => 'Sedan', 'compat_year_from' => 1993, 'compat_year_to' => 1997,
                'shared_models' => ['Toyota Corolla (1993-1997) - own generation'],
                'shared_vehicles' => [['make'=>'TOYOTA','model'=>'COROLLA','year_from'=>1993,'year_to'=>1997,'categories'=>self::OWN_GENERATION_CATEGORIES]],
            ]);
        }

        // ══════════════════════════════════════════════════════
        // TOYOTA RAV4 — no significant cross-model twin, own
        // generation continuity only.
        // ══════════════════════════════════════════════════════
        if ($make === 'TOYOTA' && $model === 'RAV4') {
            if ($year >= 2019) return array_merge($default, ['platform_code'=>'TNGA_K_SUV','generation'=>'XA50','body_style'=>'SUV','compat_year_from'=>2019,'compat_year_to'=>2024,
                'shared_models'=>['Toyota RAV4 (2019-2024) - own generation'],
                'shared_vehicles'=>[['make'=>'TOYOTA','model'=>'RAV4','year_from'=>2019,'year_to'=>2024,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2013) return array_merge($default, ['platform_code'=>'RAV4_XA40','generation'=>'XA40','body_style'=>'SUV','compat_year_from'=>2013,'compat_year_to'=>2018,
                'shared_models'=>['Toyota RAV4 (2013-2018) - own generation'],
                'shared_vehicles'=>[['make'=>'TOYOTA','model'=>'RAV4','year_from'=>2013,'year_to'=>2018,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2006) return array_merge($default, ['platform_code'=>'RAV4_XA30','generation'=>'XA30','body_style'=>'SUV','compat_year_from'=>2006,'compat_year_to'=>2012,
                'shared_models'=>['Toyota RAV4 (2006-2012) - own generation'],
                'shared_vehicles'=>[['make'=>'TOYOTA','model'=>'RAV4','year_from'=>2006,'year_to'=>2012,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2001) return array_merge($default, ['platform_code'=>'RAV4_XA20','generation'=>'XA20','body_style'=>'SUV','compat_year_from'=>2001,'compat_year_to'=>2005,
                'shared_models'=>['Toyota RAV4 (2001-2005) - own generation'],
                'shared_vehicles'=>[['make'=>'TOYOTA','model'=>'RAV4','year_from'=>2001,'year_to'=>2005,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'RAV4_XA10','generation'=>'XA10','body_style'=>'SUV','compat_year_from'=>1994,'compat_year_to'=>2000,
                'shared_models'=>['Toyota RAV4 (1994-2000) - own generation'],
                'shared_vehicles'=>[['make'=>'TOYOTA','model'=>'RAV4','year_from'=>1994,'year_to'=>2000,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        if ($make === 'LEXUS' && $model === 'RX') {
            if ($year >= 2016) return array_merge($default, ['platform_code'=>'GA_K','generation'=>'AL20','body_style'=>'SUV','compat_year_from'=>2016,'compat_year_to'=>2022,
                'shared_models'=>['Lexus RX (2016-2022) - own generation'],'shared_vehicles'=>[['make'=>'LEXUS','model'=>'RX','year_from'=>2016,'year_to'=>2022,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2010) return array_merge($default, ['platform_code'=>'K_PLATFORM_SUV','generation'=>'AL10','body_style'=>'SUV','compat_year_from'=>2010,'compat_year_to'=>2015,
                'shared_models'=>['Lexus RX (2010-2015) - own generation'],'shared_vehicles'=>[['make'=>'LEXUS','model'=>'RX','year_from'=>2010,'year_to'=>2015,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2004) return array_merge($default, ['platform_code'=>'K_PLATFORM_SUV','generation'=>'XU30','body_style'=>'SUV','compat_year_from'=>2004,'compat_year_to'=>2009,
                'shared_models'=>['Lexus RX (2004-2009) - own generation'],'shared_vehicles'=>[['make'=>'LEXUS','model'=>'RX','year_from'=>2004,'year_to'=>2009,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'K_PLATFORM_SUV','generation'=>'XU10','body_style'=>'SUV','compat_year_from'=>1999,'compat_year_to'=>2003,
                'shared_models'=>['Lexus RX (1999-2003) - own generation'],'shared_vehicles'=>[['make'=>'LEXUS','model'=>'RX','year_from'=>1999,'year_to'=>2003,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        if ($make === 'TOYOTA' && $model === 'HIGHLANDER') {
            if ($year >= 2020) return array_merge($default, ['platform_code'=>'TNGA_K_3ROW','generation'=>'XU70','body_style'=>'SUV','compat_year_from'=>2020,'compat_year_to'=>2024,
                'shared_models'=>['Toyota Highlander (2020-2024) - own generation'],'shared_vehicles'=>[['make'=>'TOYOTA','model'=>'HIGHLANDER','year_from'=>2020,'year_to'=>2024,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2014) return array_merge($default, ['platform_code'=>'K_PLATFORM_3ROW','generation'=>'XU50','body_style'=>'SUV','compat_year_from'=>2014,'compat_year_to'=>2019,
                'shared_models'=>['Toyota Highlander (2014-2019) - own generation'],'shared_vehicles'=>[['make'=>'TOYOTA','model'=>'HIGHLANDER','year_from'=>2014,'year_to'=>2019,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2008) return array_merge($default, ['platform_code'=>'K_PLATFORM_3ROW','generation'=>'XU40','body_style'=>'SUV','compat_year_from'=>2008,'compat_year_to'=>2013,
                'shared_models'=>['Toyota Highlander (2008-2013) - own generation'],'shared_vehicles'=>[['make'=>'TOYOTA','model'=>'HIGHLANDER','year_from'=>2008,'year_to'=>2013,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'K_PLATFORM_3ROW','generation'=>'XU20','body_style'=>'SUV','compat_year_from'=>2001,'compat_year_to'=>2007,
                'shared_models'=>['Toyota Highlander (2001-2007) - own generation'],'shared_vehicles'=>[['make'=>'TOYOTA','model'=>'HIGHLANDER','year_from'=>2001,'year_to'=>2007,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        // ══════════════════════════════════════════════════════
        // TOYOTA LAND CRUISER / LEXUS LX — Suspension/Brakes cross-model
        // ══════════════════════════════════════════════════════
        if (($make === 'TOYOTA' && str_contains($model, 'LAND')) ||
            ($make === 'LEXUS' && str_starts_with($model, 'LX'))) {
            if ($year >= 2008) return array_merge($default, ['platform_code'=>'J200','generation'=>'LC200/LX570','body_style'=>'SUV','compat_year_from'=>2008,'compat_year_to'=>2021,
                'shared_models'=>['Toyota Land Cruiser 200 (2008-2021) - own generation', 'Lexus LX570 (2008-2021) - chassis-mate only'],
                'shared_vehicles'=>[
                    ['make'=>'TOYOTA','model'=>'LAND CRUISER','year_from'=>2008,'year_to'=>2021,'categories'=>self::OWN_GENERATION_CATEGORIES],
                    ['make'=>'LEXUS','model'=>'LX','year_from'=>2008,'year_to'=>2021,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                ]]);
            if ($year >= 1998) return array_merge($default, ['platform_code'=>'J100','generation'=>'LC100/LX470','body_style'=>'SUV','compat_year_from'=>1998,'compat_year_to'=>2007,
                'shared_models'=>['Toyota Land Cruiser 100 (1998-2007) - own generation', 'Lexus LX470 (1998-2007) - chassis-mate only'],
                'shared_vehicles'=>[
                    ['make'=>'TOYOTA','model'=>'LAND CRUISER','year_from'=>1998,'year_to'=>2007,'categories'=>self::OWN_GENERATION_CATEGORIES],
                    ['make'=>'LEXUS','model'=>'LX','year_from'=>1998,'year_to'=>2007,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                ]]);
        }

        // ══════════════════════════════════════════════════════
        // TOYOTA PRADO / LEXUS GX — Suspension/Brakes cross-model
        // ══════════════════════════════════════════════════════
        if ((str_contains($model, 'PRADO')) || ($make === 'LEXUS' && str_starts_with($model, 'GX'))) {
            if ($year >= 2010) return array_merge($default, ['platform_code'=>'J150','generation'=>'Prado 150/GX460','body_style'=>'SUV','compat_year_from'=>2010,'compat_year_to'=>2024,
                'shared_models'=>['Toyota Land Cruiser Prado 150 (2010-2024) - own generation', 'Lexus GX460 (2010-2024) - chassis-mate only'],
                'shared_vehicles'=>[
                    ['make'=>'TOYOTA','model'=>'PRADO','year_from'=>2010,'year_to'=>2024,'categories'=>self::OWN_GENERATION_CATEGORIES],
                    ['make'=>'LEXUS','model'=>'GX','year_from'=>2010,'year_to'=>2024,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                ]]);
            if ($year >= 2003) return array_merge($default, ['platform_code'=>'J120','generation'=>'Prado 120/GX470','body_style'=>'SUV','compat_year_from'=>2003,'compat_year_to'=>2009,
                'shared_models'=>['Toyota Land Cruiser Prado 120 (2003-2009) - own generation', 'Lexus GX470 (2003-2009) - chassis-mate only'],
                'shared_vehicles'=>[
                    ['make'=>'TOYOTA','model'=>'PRADO','year_from'=>2003,'year_to'=>2009,'categories'=>self::OWN_GENERATION_CATEGORIES],
                    ['make'=>'LEXUS','model'=>'GX','year_from'=>2003,'year_to'=>2009,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                ]]);
        }

        // ══════════════════════════════════════════════════════
        // TOYOTA 4RUNNER / FJ CRUISER / TACOMA — shared frame family,
        // Suspension/Brakes cross-model
        // ══════════════════════════════════════════════════════
        if (str_contains($model, '4RUNNER') || $model === '4-RUNNER' || str_contains($model, 'FJ') || $model === 'TACOMA') {
            if ($year >= 2010) return array_merge($default, ['platform_code'=>'IMV_N','generation'=>'N280 (4Runner) / J150-era Tacoma','body_style'=>'SUV/Pickup','compat_year_from'=>2010,'compat_year_to'=>2024,
                'shared_models'=>['Toyota 4Runner (2010-2024) - own generation', 'Toyota Tacoma (2016-2023) - related frame, chassis-mate only'],
                'shared_vehicles'=>[
                    ['make'=>'TOYOTA','model'=>'4RUNNER','year_from'=>2010,'year_to'=>2024,'categories'=>self::OWN_GENERATION_CATEGORIES],
                    ['make'=>'TOYOTA','model'=>'TACOMA','year_from'=>2016,'year_to'=>2023,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                ]]);
            if ($year >= 2003) return array_merge($default, ['platform_code'=>'IMV_N','generation'=>'N210','body_style'=>'SUV/Pickup','compat_year_from'=>2003,'compat_year_to'=>2009,
                'shared_models'=>['Toyota 4Runner (2003-2009) - own generation', 'Toyota FJ Cruiser (2006-2014) - related frame, chassis-mate only', 'Toyota Tacoma (2005-2015) - related frame, chassis-mate only'],
                'shared_vehicles'=>[
                    ['make'=>'TOYOTA','model'=>'4RUNNER','year_from'=>2003,'year_to'=>2009,'categories'=>self::OWN_GENERATION_CATEGORIES],
                    ['make'=>'TOYOTA','model'=>'FJ CRUISER','year_from'=>2006,'year_to'=>2014,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ['make'=>'TOYOTA','model'=>'TACOMA','year_from'=>2005,'year_to'=>2015,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                ]]);
        }

        // ══════════════════════════════════════════════════════
        // HYUNDAI / KIA — platform siblings almost always have fully
        // distinct exterior/interior design between the two brands.
        // Suspension/Brakes cross-model ONLY.
        // ══════════════════════════════════════════════════════
        if (in_array($make, ['HYUNDAI','KIA'])) {

            // Elantra / Forte / Cerato
            if (($make === 'HYUNDAI' && in_array($model, ['ELANTRA','AVANTE'])) ||
                ($make === 'KIA' && in_array($model, ['FORTE','CERATO']))) {
                if ($year >= 2021) return array_merge($default, ['platform_code'=>'HYUNDAI_K3_CD','generation'=>'CN7 (Elantra) / BD3 (Forte)','body_style'=>'Sedan','compat_year_from'=>2021,'compat_year_to'=>2024,
                    'shared_models'=>['Hyundai Elantra (2021-2024) - own generation', 'Kia Forte (2019-2024) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'HYUNDAI','model'=>'ELANTRA','year_from'=>2021,'year_to'=>2024,'categories'=>self::OWN_GENERATION_CATEGORIES],
                        ['make'=>'KIA','model'=>'FORTE','year_from'=>2019,'year_to'=>2024,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
                if ($year >= 2017) return array_merge($default, ['platform_code'=>'HYUNDAI_K3_AD','generation'=>'AD (Elantra) / BD2 (Forte)','body_style'=>'Sedan','compat_year_from'=>2017,'compat_year_to'=>2020,
                    'shared_models'=>['Hyundai Elantra (2017-2020) - own generation', 'Kia Forte (2017-2018) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'HYUNDAI','model'=>'ELANTRA','year_from'=>2017,'year_to'=>2020,'categories'=>self::OWN_GENERATION_CATEGORIES],
                        ['make'=>'KIA','model'=>'FORTE','year_from'=>2017,'year_to'=>2018,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
                if ($year >= 2011) return array_merge($default, ['platform_code'=>'HYUNDAI_K3_MD','generation'=>'MD (Elantra) / YD (Forte)','body_style'=>'Sedan','compat_year_from'=>2011,'compat_year_to'=>2016,
                    'shared_models'=>['Hyundai Elantra (2011-2016) - own generation', 'Kia Forte (2014-2016) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'HYUNDAI','model'=>'ELANTRA','year_from'=>2011,'year_to'=>2016,'categories'=>self::OWN_GENERATION_CATEGORIES],
                        ['make'=>'KIA','model'=>'FORTE','year_from'=>2014,'year_to'=>2016,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
                return array_merge($default, ['platform_code'=>'HYUNDAI_K3_LEGACY','generation'=>'pre-2011 Elantra','body_style'=>'Sedan','compat_year_from'=>2000,'compat_year_to'=>2010,
                    'shared_models'=>['Hyundai Elantra (2000-2010) - own generation'],
                    'shared_vehicles'=>[['make'=>'HYUNDAI','model'=>'ELANTRA','year_from'=>2000,'year_to'=>2010,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            }

            // Sonata / Optima
            if ($model === 'SONATA' || ($make === 'KIA' && $model === 'OPTIMA')) {
                if ($year >= 2020) return array_merge($default, ['platform_code'=>'HYUNDAI_K5_DN8','generation'=>'DN8 (Sonata) / DL3 (K5, successor to Optima)','body_style'=>'Sedan','compat_year_from'=>2020,'compat_year_to'=>2024,
                    'shared_models'=>['Hyundai Sonata (2020-2024) - own generation', 'Kia K5 (2021-2024) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'HYUNDAI','model'=>'SONATA','year_from'=>2020,'year_to'=>2024,'categories'=>self::OWN_GENERATION_CATEGORIES],
                        ['make'=>'KIA','model'=>'K5','year_from'=>2021,'year_to'=>2024,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
                if ($year >= 2015) return array_merge($default, ['platform_code'=>'HYUNDAI_K5_LF','generation'=>'LF (Sonata) / JF (Optima)','body_style'=>'Sedan','compat_year_from'=>2015,'compat_year_to'=>2019,
                    'shared_models'=>['Hyundai Sonata (2015-2019) - own generation', 'Kia Optima (2016-2020) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'HYUNDAI','model'=>'SONATA','year_from'=>2015,'year_to'=>2019,'categories'=>self::OWN_GENERATION_CATEGORIES],
                        ['make'=>'KIA','model'=>'OPTIMA','year_from'=>2016,'year_to'=>2020,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
                if ($year >= 2010) return array_merge($default, ['platform_code'=>'HYUNDAI_K5_YF','generation'=>'YF (Sonata) / TF (Optima)','body_style'=>'Sedan','compat_year_from'=>2010,'compat_year_to'=>2014,
                    'shared_models'=>['Hyundai Sonata (2010-2014) - own generation', 'Kia Optima (2011-2015) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'HYUNDAI','model'=>'SONATA','year_from'=>2010,'year_to'=>2014,'categories'=>self::OWN_GENERATION_CATEGORIES],
                        ['make'=>'KIA','model'=>'OPTIMA','year_from'=>2011,'year_to'=>2015,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
                return array_merge($default, ['platform_code'=>'HYUNDAI_K5_LEGACY','generation'=>'pre-2010 Sonata/Optima','body_style'=>'Sedan','compat_year_from'=>2006,'compat_year_to'=>2009,
                    'shared_models'=>['Hyundai Sonata (2006-2009) - own generation', 'Kia Optima (2006-2010) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'HYUNDAI','model'=>'SONATA','year_from'=>2006,'year_to'=>2009,'categories'=>self::OWN_GENERATION_CATEGORIES],
                        ['make'=>'KIA','model'=>'OPTIMA','year_from'=>2006,'year_to'=>2010,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
            }

            // Tucson / Sportage
            if (in_array($model, ['TUCSON']) || ($make === 'KIA' && $model === 'SPORTAGE')) {
                if ($year >= 2022) return array_merge($default, ['platform_code'=>'HYUNDAI_NU3_NX4','generation'=>'NX4 (Tucson) / NQ5 (Sportage)','body_style'=>'SUV','compat_year_from'=>2022,'compat_year_to'=>2024,
                    'shared_models'=>['Hyundai Tucson (2022-2024) - own generation', 'Kia Sportage (2023-2024) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'HYUNDAI','model'=>'TUCSON','year_from'=>2022,'year_to'=>2024,'categories'=>self::OWN_GENERATION_CATEGORIES],
                        ['make'=>'KIA','model'=>'SPORTAGE','year_from'=>2023,'year_to'=>2024,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
                if ($year >= 2016) return array_merge($default, ['platform_code'=>'HYUNDAI_NU3_TL','generation'=>'TL (Tucson) / QL (Sportage)','body_style'=>'SUV','compat_year_from'=>2016,'compat_year_to'=>2021,
                    'shared_models'=>['Hyundai Tucson (2016-2021) - own generation', 'Kia Sportage (2017-2022) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'HYUNDAI','model'=>'TUCSON','year_from'=>2016,'year_to'=>2021,'categories'=>self::OWN_GENERATION_CATEGORIES],
                        ['make'=>'KIA','model'=>'SPORTAGE','year_from'=>2017,'year_to'=>2022,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
                if ($year >= 2010) return array_merge($default, ['platform_code'=>'HYUNDAI_NU2_LM','generation'=>'LM (Tucson) / SL (Sportage)','body_style'=>'SUV','compat_year_from'=>2010,'compat_year_to'=>2015,
                    'shared_models'=>['Hyundai Tucson (2010-2015) - own generation', 'Kia Sportage (2011-2016) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'HYUNDAI','model'=>'TUCSON','year_from'=>2010,'year_to'=>2015,'categories'=>self::OWN_GENERATION_CATEGORIES],
                        ['make'=>'KIA','model'=>'SPORTAGE','year_from'=>2011,'year_to'=>2016,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
                return array_merge($default, ['platform_code'=>'HYUNDAI_TUCSON_LEGACY','generation'=>'pre-2010 Tucson/Sportage','body_style'=>'SUV','compat_year_from'=>2004,'compat_year_to'=>2009,
                    'shared_models'=>['Hyundai Tucson (2004-2009) - own generation', 'Kia Sportage (2004-2010) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'HYUNDAI','model'=>'TUCSON','year_from'=>2004,'year_to'=>2009,'categories'=>self::OWN_GENERATION_CATEGORIES],
                        ['make'=>'KIA','model'=>'SPORTAGE','year_from'=>2004,'year_to'=>2010,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
            }

            // Santa Fe / Sorento — NOTE: these have shared platform in
            // some but not all generations; only including generations
            // where the platform relationship is well-documented.
            if ($model === 'SANTA FE' || ($make === 'KIA' && $model === 'SORENTO')) {
                if ($year >= 2021) return array_merge($default, ['platform_code'=>'HYUNDAI_N3_TM','generation'=>'TM (Santa Fe) / MQ4 (Sorento)','body_style'=>'SUV','compat_year_from'=>2021,'compat_year_to'=>2024,
                    'shared_models'=>['Hyundai Santa Fe (2021-2024) - own generation', 'Kia Sorento (2021-2024) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'HYUNDAI','model'=>'SANTA FE','year_from'=>2021,'year_to'=>2024,'categories'=>self::OWN_GENERATION_CATEGORIES],
                        ['make'=>'KIA','model'=>'SORENTO','year_from'=>2021,'year_to'=>2024,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
                return array_merge($default, ['platform_code'=>'HYUNDAI_SANTAFE_OWN','generation'=>'pre-2021 Santa Fe','body_style'=>'SUV','compat_year_from'=>2001,'compat_year_to'=>2020,
                    'shared_models'=>['Hyundai Santa Fe (2001-2020) - own generation continuity (not split by exact gen boundaries here)'],
                    'shared_vehicles'=>[['make'=>'HYUNDAI','model'=>'SANTA FE','year_from'=>2001,'year_to'=>2020,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            }

            // Accent / Rio
            if (in_array($model, ['ACCENT','VERNA']) || ($make === 'KIA' && $model === 'RIO')) {
                if ($year >= 2018) return array_merge($default, ['platform_code'=>'HYUNDAI_ACCENT_RIO_MODERN','generation'=>'HC (Accent) / YB (Rio)','body_style'=>'Sedan/Hatchback','compat_year_from'=>2018,'compat_year_to'=>2024,
                    'shared_models'=>['Hyundai Accent (2018-2024) - own generation', 'Kia Rio (2018-2024) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'HYUNDAI','model'=>'ACCENT','year_from'=>2018,'year_to'=>2024,'categories'=>self::OWN_GENERATION_CATEGORIES],
                        ['make'=>'KIA','model'=>'RIO','year_from'=>2018,'year_to'=>2024,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
                return array_merge($default, ['platform_code'=>'HYUNDAI_ACCENT_RIO_LEGACY','generation'=>'pre-2018 Accent/Rio','body_style'=>'Sedan/Hatchback','compat_year_from'=>2006,'compat_year_to'=>2017,
                    'shared_models'=>['Hyundai Accent (2006-2017) - own generation', 'Kia Rio (2006-2017) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'HYUNDAI','model'=>'ACCENT','year_from'=>2006,'year_to'=>2017,'categories'=>self::OWN_GENERATION_CATEGORIES],
                        ['make'=>'KIA','model'=>'RIO','year_from'=>2006,'year_to'=>2017,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
            }
        }

        // ══════════════════════════════════════════════════════
        // NISSAN / INFINITI — FM platform family (350Z/370Z/G35/G37/
        // M35/M45). Different body styles per model (coupe vs sedan),
        // so Suspension/Brakes cross-model ONLY, even within the same
        // FM chassis family.
        // ══════════════════════════════════════════════════════
        if (in_array($make, ['NISSAN','INFINITI'])) {

            if (in_array($model, ['350Z','370Z']) ||
                ($make === 'INFINITI' && (str_starts_with($model,'G') || $model === 'Q40' || $model === 'Q60'))) {

                if ($year >= 2014) return array_merge($default, ['platform_code'=>'FM_PLATFORM','generation'=>'FM (370Z / Q60)','body_style'=>'Coupe','compat_year_from'=>2014,'compat_year_to'=>2020,
                    'shared_models'=>['Nissan 370Z (2009-2020) - chassis-mate only, distinct styling', 'Infiniti Q60 (2014-2020) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'NISSAN','model'=>'370Z','year_from'=>2009,'year_to'=>2020,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                        ['make'=>'INFINITI','model'=>'Q60','year_from'=>2014,'year_to'=>2020,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
                if ($year >= 2003) return array_merge($default, ['platform_code'=>'FM_PLATFORM','generation'=>'FM (350Z / G35/G37)','body_style'=>'Coupe/Sedan','compat_year_from'=>2003,'compat_year_to'=>2013,
                    'shared_models'=>['Nissan 350Z (2003-2009) - chassis-mate only, distinct styling', 'Infiniti G35/G37 (2003-2013) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'NISSAN','model'=>'350Z','year_from'=>2003,'year_to'=>2009,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                        ['make'=>'INFINITI','model'=>'G35','year_from'=>2003,'year_to'=>2008,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                        ['make'=>'INFINITI','model'=>'G37','year_from'=>2008,'year_to'=>2013,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
            }

            // Armada / Titan / QX56 / QX80 — body-on-frame F-Alpha
            // platform family. Suspension/Brakes/frame components only.
            if (in_array($model, ['ARMADA','TITAN']) ||
                ($make === 'INFINITI' && in_array($model, ['QX56','QX80']))) {

                if ($year >= 2017) return array_merge($default, ['platform_code'=>'F_ALPHA','generation'=>'Titan/Armada/QX80 (2017+)','body_style'=>'SUV/Pickup','compat_year_from'=>2017,'compat_year_to'=>2024,
                    'shared_models'=>['Nissan Armada (2017-2024) - own generation', 'Nissan Titan (2017-2024) - related frame, chassis-mate only', 'Infiniti QX80 (2017-2024) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'NISSAN','model'=>'ARMADA','year_from'=>2017,'year_to'=>2024,'categories'=>self::OWN_GENERATION_CATEGORIES],
                        ['make'=>'NISSAN','model'=>'TITAN','year_from'=>2017,'year_to'=>2024,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                        ['make'=>'INFINITI','model'=>'QX80','year_from'=>2017,'year_to'=>2024,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
                if ($year >= 2004) return array_merge($default, ['platform_code'=>'F_ALPHA','generation'=>'Titan/Armada/QX56 (2004-2015)','body_style'=>'SUV/Pickup','compat_year_from'=>2004,'compat_year_to'=>2015,
                    'shared_models'=>['Nissan Armada (2004-2015) - own generation', 'Nissan Titan (2004-2015) - related frame, chassis-mate only', 'Infiniti QX56 (2004-2015) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'NISSAN','model'=>'ARMADA','year_from'=>2004,'year_to'=>2015,'categories'=>self::OWN_GENERATION_CATEGORIES],
                        ['make'=>'NISSAN','model'=>'TITAN','year_from'=>2004,'year_to'=>2015,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                        ['make'=>'INFINITI','model'=>'QX56','year_from'=>2004,'year_to'=>2015,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
            }

            // Pathfinder / Murano / Infiniti QX60 (JX35) — D-platform
            // unibody crossover family, current-gen only where confirmed.
            if (in_array($model, ['PATHFINDER']) || ($make === 'INFINITI' && in_array($model, ['JX','QX60']))) {
                if ($year >= 2013) return array_merge($default, ['platform_code'=>'D_PLATFORM','generation'=>'D-platform (Pathfinder/QX60, 2013+)','body_style'=>'SUV','compat_year_from'=>2013,'compat_year_to'=>2024,
                    'shared_models'=>['Nissan Pathfinder (2013-2024) - own generation', 'Infiniti QX60/JX35 (2013-2024) - chassis-mate only, distinct styling'],
                    'shared_vehicles'=>[
                        ['make'=>'NISSAN','model'=>'PATHFINDER','year_from'=>2013,'year_to'=>2024,'categories'=>self::OWN_GENERATION_CATEGORIES],
                        ['make'=>'INFINITI','model'=>'QX60','year_from'=>2013,'year_to'=>2024,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                    ]]);
            }
        }

        // ══════════════════════════════════════════════════════
        // CHEVROLET CRUZE / BUICK VERANO — GM Delta II platform.
        // Verano and Cruze share the same platform/chassis; Suspension
        // and Brakes (steering rack, control arms, calipers etc.) are
        // confirmed cross-model shares. Body panels are NOT — Verano
        // has fully distinct Buick-specific exterior sheet metal.
        // ══════════════════════════════════════════════════════
        if ($make === 'CHEVROLET' && $model === 'CRUZE') {
            if ($year >= 2016) return array_merge($default, [
                'platform_code' => 'D2XX', 'generation' => 'Cruze Gen 2 (D2XX)',
                'body_style' => 'Sedan/Hatchback', 'compat_year_from' => 2016, 'compat_year_to' => 2019,
                'shared_models' => ['Chevrolet Cruze (2016-2019) - own generation'],
                'shared_vehicles' => [
                    ['make'=>'CHEVROLET','model'=>'CRUZE','year_from'=>2016,'year_to'=>2019,'categories'=>self::OWN_GENERATION_CATEGORIES],
                ],
            ]);
            if ($year >= 2011) return array_merge($default, [
                'platform_code' => 'DELTA_II', 'generation' => 'Cruze Gen 1 (J300, Delta II)',
                'body_style' => 'Sedan', 'compat_year_from' => 2011, 'compat_year_to' => 2015,
                'shared_models' => ['Chevrolet Cruze (2011-2015) - own generation', 'Buick Verano (2012-2017) - Delta II platform-mate, chassis-mate only'],
                'shared_vehicles' => [
                    ['make'=>'CHEVROLET','model'=>'CRUZE','year_from'=>2011,'year_to'=>2015,'categories'=>self::OWN_GENERATION_CATEGORIES],
                    ['make'=>'BUICK','model'=>'VERANO','year_from'=>2012,'year_to'=>2017,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                ],
            ]);
        }

        if ($make === 'BUICK' && $model === 'VERANO') {
            return array_merge($default, [
                'platform_code' => 'DELTA_II', 'generation' => 'Verano (Delta II)',
                'body_style' => 'Sedan', 'compat_year_from' => 2012, 'compat_year_to' => 2017,
                'shared_models' => ['Buick Verano (2012-2017) - own generation', 'Chevrolet Cruze (2011-2015) - Delta II platform-mate, chassis-mate only'],
                'shared_vehicles' => [
                    ['make'=>'BUICK','model'=>'VERANO','year_from'=>2012,'year_to'=>2017,'categories'=>self::OWN_GENERATION_CATEGORIES],
                    ['make'=>'CHEVROLET','model'=>'CRUZE','year_from'=>2011,'year_to'=>2015,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES],
                ],
            ]);
        }

        // ══════════════════════════════════════════════════════
        // LAND ROVER RANGE ROVER (L322, 3rd gen) — 2002-2012 overall,
        // but deliberately split into THREE sub-generations rather
        // than one broad range. This isn't a styling facelift split —
        // it tracks real powertrain/electronics changes:
        //   2002-2005: BMW ownership era — BMW-sourced V8/diesel
        //              engines, BMW electronics architecture.
        //   2006-2009: Sold to Ford in 2006 — Jaguar engines replace
        //              the BMW units, electronics/infotainment
        //              upgraded. A DIFFERENT electrical architecture
        //              from the BMW-era cars despite being the same
        //              generation chassis.
        //   2010-2012: Facelift — supercharged 5.0L V8 introduced,
        //              8-speed ZF transmission (up from 6-speed).
        // An ECU/engine-electrical part from a 2009 car is NOT
        // interchangeable with a 2003 car even though both are
        // "L322" — this split prevents that overclaim. Body/exterior
        // sheet metal is likely broader than this split allows, but
        // narrower-than-necessary is the safe direction to err when
        // uncertain; widen later if confirmed.
        // ══════════════════════════════════════════════════════
        if ($make === 'LAND ROVER' && $model === 'RANGE ROVER') {
            if ($year >= 2010) return array_merge($default, [
                'platform_code' => 'L322', 'generation' => 'Range Rover L322 — facelift (5.0L SC V8, 8-spd)',
                'body_style' => 'SUV', 'compat_year_from' => 2010, 'compat_year_to' => 2012,
                'shared_models' => ['Land Rover Range Rover (2010-2012) - own generation'],
                'shared_vehicles' => [
                    ['make'=>'LAND ROVER','model'=>'RANGE ROVER','year_from'=>2010,'year_to'=>2012,'categories'=>self::OWN_GENERATION_CATEGORIES],
                ],
            ]);
            if ($year >= 2006) return array_merge($default, [
                'platform_code' => 'L322', 'generation' => 'Range Rover L322 — Ford/Jaguar era (post-2006)',
                'body_style' => 'SUV', 'compat_year_from' => 2006, 'compat_year_to' => 2009,
                'shared_models' => ['Land Rover Range Rover (2006-2009) - own generation'],
                'shared_vehicles' => [
                    ['make'=>'LAND ROVER','model'=>'RANGE ROVER','year_from'=>2006,'year_to'=>2009,'categories'=>self::OWN_GENERATION_CATEGORIES],
                ],
            ]);
            return array_merge($default, [
                'platform_code' => 'L322', 'generation' => 'Range Rover L322 — BMW era (2002-2005)',
                'body_style' => 'SUV', 'compat_year_from' => 2002, 'compat_year_to' => 2005,
                'shared_models' => ['Land Rover Range Rover (2002-2005) - own generation'],
                'shared_vehicles' => [
                    ['make'=>'LAND ROVER','model'=>'RANGE ROVER','year_from'=>2002,'year_to'=>2005,'categories'=>self::OWN_GENERATION_CATEGORIES],
                ],
            ]);
        }

        // ══════════════════════════════════════════════════════
        // TOYOTA SIENNA (XL10/XL20) — search-verified generation
        // boundaries. XL30 (2011+) not yet added since current stock
        // only goes to 2009; add on request when needed.
        // ══════════════════════════════════════════════════════
        if ($make === 'TOYOTA' && $model === 'SIENNA') {
            if ($year >= 2011) return array_merge($default, [
                'platform_code' => 'XL30', 'generation' => 'Sienna Gen 3 (XL30)',
                'body_style' => 'Minivan', 'compat_year_from' => 2011, 'compat_year_to' => 2017,
                'shared_models' => ['Toyota Sienna (2011-2017) - own generation'],
                'shared_vehicles' => [['make'=>'TOYOTA','model'=>'SIENNA','year_from'=>2011,'year_to'=>2017,'categories'=>self::OWN_GENERATION_CATEGORIES]],
            ]);
            if ($year >= 2004) return array_merge($default, [
                'platform_code' => 'XL20', 'generation' => 'Sienna Gen 2 (XL20)',
                'body_style' => 'Minivan', 'compat_year_from' => 2004, 'compat_year_to' => 2010,
                'shared_models' => ['Toyota Sienna (2004-2010) - own generation'],
                'shared_vehicles' => [['make'=>'TOYOTA','model'=>'SIENNA','year_from'=>2004,'year_to'=>2010,'categories'=>self::OWN_GENERATION_CATEGORIES]],
            ]);
            return array_merge($default, [
                'platform_code' => 'XL10', 'generation' => 'Sienna Gen 1 (XL10)',
                'body_style' => 'Minivan', 'compat_year_from' => 1998, 'compat_year_to' => 2003,
                'shared_models' => ['Toyota Sienna (1998-2003) - own generation'],
                'shared_vehicles' => [['make'=>'TOYOTA','model'=>'SIENNA','year_from'=>1998,'year_to'=>2003,'categories'=>self::OWN_GENERATION_CATEGORIES]],
            ]);
        }

        // ══════════════════════════════════════════════════════
        // TOYOTA HIGHLANDER (XU20/XU40) — search-verified.
        // ══════════════════════════════════════════════════════
        if ($make === 'TOYOTA' && $model === 'HIGHLANDER') {
            if ($year >= 2008) return array_merge($default, [
                'platform_code' => 'XU40', 'generation' => 'Highlander Gen 2 (XU40)',
                'body_style' => 'SUV', 'compat_year_from' => 2008, 'compat_year_to' => 2013,
                'shared_models' => ['Toyota Highlander (2008-2013) - own generation'],
                'shared_vehicles' => [['make'=>'TOYOTA','model'=>'HIGHLANDER','year_from'=>2008,'year_to'=>2013,'categories'=>self::OWN_GENERATION_CATEGORIES]],
            ]);
            return array_merge($default, [
                'platform_code' => 'XU20', 'generation' => 'Highlander Gen 1 (XU20)',
                'body_style' => 'SUV', 'compat_year_from' => 2001, 'compat_year_to' => 2007,
                'shared_models' => ['Toyota Highlander (2001-2007) - own generation'],
                'shared_vehicles' => [['make'=>'TOYOTA','model'=>'HIGHLANDER','year_from'=>2001,'year_to'=>2007,'categories'=>self::OWN_GENERATION_CATEGORIES]],
            ]);
        }

        // ══════════════════════════════════════════════════════
        // TOYOTA CAMRY XV10 (1992-1996) — extends existing Camry
        // coverage back before the 1997-2001 bucket already on file.
        // ══════════════════════════════════════════════════════
        if ($make === 'TOYOTA' && $model === 'CAMRY' && $year < 1997) {
            return array_merge($default, [
                'platform_code' => 'XV10', 'generation' => 'Camry Gen 3 (XV10)',
                'body_style' => 'Sedan', 'compat_year_from' => 1992, 'compat_year_to' => 1996,
                'shared_models' => ['Toyota Camry (1992-1996) - own generation'],
                'shared_vehicles' => [['make'=>'TOYOTA','model'=>'CAMRY','year_from'=>1992,'year_to'=>1996,'categories'=>self::OWN_GENERATION_CATEGORIES]],
            ]);
        }

        // ══════════════════════════════════════════════════════
        // TOYOTA PRIUS — hybrid platform. Battery/inverter/HV system
        // are category-specific concerns beyond this generation split;
        // OWN_GENERATION_CATEGORIES here covers body/suspension/
        // interior only, same as every other entry — it does NOT
        // imply hybrid-system parts (battery, inverter, HV cables)
        // are safe across even this range without separate hybrid-
        // specific verification.
        // ══════════════════════════════════════════════════════
        if ($make === 'TOYOTA' && $model === 'PRIUS') {
            if ($year >= 2010) return array_merge($default, [
                'platform_code' => 'ZVW30', 'generation' => 'Prius Gen 3 (ZVW30)',
                'body_style' => 'Hatchback', 'compat_year_from' => 2010, 'compat_year_to' => 2015,
                'shared_models' => ['Toyota Prius (2010-2015) - own generation'],
                'shared_vehicles' => [['make'=>'TOYOTA','model'=>'PRIUS','year_from'=>2010,'year_to'=>2015,'categories'=>self::OWN_GENERATION_CATEGORIES]],
            ]);
            return array_merge($default, [
                'platform_code' => 'NHW20', 'generation' => 'Prius Gen 2 (NHW20)',
                'body_style' => 'Hatchback', 'compat_year_from' => 2004, 'compat_year_to' => 2009,
                'shared_models' => ['Toyota Prius (2004-2009) - own generation'],
                'shared_vehicles' => [['make'=>'TOYOTA','model'=>'PRIUS','year_from'=>2004,'year_to'=>2009,'categories'=>self::OWN_GENERATION_CATEGORIES]],
            ]);
        }

        // ══════════════════════════════════════════════════════
        // HYUNDAI SONATA — general-knowledge boundaries, high
        // confidence (mainstream, well-documented), not individually
        // search-verified this session. Spot-check before treating
        // as fully confirmed the way Sienna/Highlander/Altima are.
        // ══════════════════════════════════════════════════════
        if ($make === 'HYUNDAI' && $model === 'SONATA') {
            if ($year >= 2015) return array_merge($default, ['platform_code'=>'LF','generation'=>'Sonata LF','body_style'=>'Sedan','compat_year_from'=>2015,'compat_year_to'=>2019,'shared_models'=>['Hyundai Sonata (2015-2019) - own generation'],'shared_vehicles'=>[['make'=>'HYUNDAI','model'=>'SONATA','year_from'=>2015,'year_to'=>2019,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2011) return array_merge($default, ['platform_code'=>'YF','generation'=>'Sonata YF','body_style'=>'Sedan','compat_year_from'=>2011,'compat_year_to'=>2014,'shared_models'=>['Hyundai Sonata (2011-2014) - own generation'],'shared_vehicles'=>[['make'=>'HYUNDAI','model'=>'SONATA','year_from'=>2011,'year_to'=>2014,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2006) return array_merge($default, ['platform_code'=>'NF','generation'=>'Sonata NF','body_style'=>'Sedan','compat_year_from'=>2006,'compat_year_to'=>2010,'shared_models'=>['Hyundai Sonata (2006-2010) - own generation'],'shared_vehicles'=>[['make'=>'HYUNDAI','model'=>'SONATA','year_from'=>2006,'year_to'=>2010,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'EF','generation'=>'Sonata EF','body_style'=>'Sedan','compat_year_from'=>1999,'compat_year_to'=>2005,'shared_models'=>['Hyundai Sonata (1999-2005) - own generation'],'shared_vehicles'=>[['make'=>'HYUNDAI','model'=>'SONATA','year_from'=>1999,'year_to'=>2005,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        // ══════════════════════════════════════════════════════
        // HYUNDAI ELANTRA — general-knowledge boundaries, spot-check
        // recommended.
        // ══════════════════════════════════════════════════════
        if ($make === 'HYUNDAI' && $model === 'ELANTRA') {
            if ($year >= 2017) return array_merge($default, ['platform_code'=>'AD','generation'=>'Elantra AD','body_style'=>'Sedan','compat_year_from'=>2017,'compat_year_to'=>2020,'shared_models'=>['Hyundai Elantra (2017-2020) - own generation'],'shared_vehicles'=>[['make'=>'HYUNDAI','model'=>'ELANTRA','year_from'=>2017,'year_to'=>2020,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2011) return array_merge($default, ['platform_code'=>'MD','generation'=>'Elantra MD','body_style'=>'Sedan','compat_year_from'=>2011,'compat_year_to'=>2016,'shared_models'=>['Hyundai Elantra (2011-2016) - own generation'],'shared_vehicles'=>[['make'=>'HYUNDAI','model'=>'ELANTRA','year_from'=>2011,'year_to'=>2016,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2007) return array_merge($default, ['platform_code'=>'HD','generation'=>'Elantra HD','body_style'=>'Sedan','compat_year_from'=>2007,'compat_year_to'=>2010,'shared_models'=>['Hyundai Elantra (2007-2010) - own generation'],'shared_vehicles'=>[['make'=>'HYUNDAI','model'=>'ELANTRA','year_from'=>2007,'year_to'=>2010,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'XD','generation'=>'Elantra XD','body_style'=>'Sedan','compat_year_from'=>2001,'compat_year_to'=>2006,'shared_models'=>['Hyundai Elantra (2001-2006) - own generation'],'shared_vehicles'=>[['make'=>'HYUNDAI','model'=>'ELANTRA','year_from'=>2001,'year_to'=>2006,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        // ══════════════════════════════════════════════════════
        // HYUNDAI PALISADE — single generation covers all current
        // stock (2022 only).
        // ══════════════════════════════════════════════════════
        if ($make === 'HYUNDAI' && $model === 'PALISADE') {
            return array_merge($default, ['platform_code'=>'LX2','generation'=>'Palisade LX2 Gen 1','body_style'=>'SUV','compat_year_from'=>2020,'compat_year_to'=>2022,'shared_models'=>['Hyundai Palisade (2020-2022) - own generation'],'shared_vehicles'=>[['make'=>'HYUNDAI','model'=>'PALISADE','year_from'=>2020,'year_to'=>2022,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        // ══════════════════════════════════════════════════════
        // HONDA ACCORD — general-knowledge boundaries for the range
        // actually in stock (1994-2017). Pre-1994 (1986-1993, 3rd/4th
        // gen) not added — send a specific part if you need those
        // covered, rather than guessing at boundaries for cars this old.
        // ══════════════════════════════════════════════════════
        if ($make === 'HONDA' && $model === 'ACCORD') {
            if ($year >= 2013) return array_merge($default, ['platform_code'=>'CR','generation'=>'Accord Gen 9 (CR)','body_style'=>'Sedan/Coupe','compat_year_from'=>2013,'compat_year_to'=>2017,'shared_models'=>['Honda Accord (2013-2017) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'ACCORD','year_from'=>2013,'year_to'=>2017,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2008) return array_merge($default, ['platform_code'=>'CU','generation'=>'Accord Gen 8 (CU)','body_style'=>'Sedan/Coupe','compat_year_from'=>2008,'compat_year_to'=>2012,'shared_models'=>['Honda Accord (2008-2012) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'ACCORD','year_from'=>2008,'year_to'=>2012,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2003) return array_merge($default, ['platform_code'=>'CL/CM','generation'=>'Accord Gen 7 (CL/CM)','body_style'=>'Sedan/Coupe','compat_year_from'=>2003,'compat_year_to'=>2007,'shared_models'=>['Honda Accord (2003-2007) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'ACCORD','year_from'=>2003,'year_to'=>2007,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 1998) return array_merge($default, ['platform_code'=>'CF/CG','generation'=>'Accord Gen 6 (CF/CG)','body_style'=>'Sedan/Coupe','compat_year_from'=>1998,'compat_year_to'=>2002,'shared_models'=>['Honda Accord (1998-2002) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'ACCORD','year_from'=>1998,'year_to'=>2002,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'CD','generation'=>'Accord Gen 5 (CD)','body_style'=>'Sedan/Coupe','compat_year_from'=>1994,'compat_year_to'=>1997,'shared_models'=>['Honda Accord (1994-1997) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'ACCORD','year_from'=>1994,'year_to'=>1997,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        // ══════════════════════════════════════════════════════
        // NISSAN ALTIMA — search-verified.
        // ══════════════════════════════════════════════════════
        if ($make === 'NISSAN' && $model === 'ALTIMA') {
            if ($year >= 2013) return array_merge($default, ['platform_code'=>'L33','generation'=>'Altima Gen 5 (L33)','body_style'=>'Sedan','compat_year_from'=>2013,'compat_year_to'=>2018,'shared_models'=>['Nissan Altima (2013-2018) - own generation'],'shared_vehicles'=>[['make'=>'NISSAN','model'=>'ALTIMA','year_from'=>2013,'year_to'=>2018,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2007) return array_merge($default, ['platform_code'=>'L32','generation'=>'Altima Gen 4 (L32)','body_style'=>'Sedan','compat_year_from'=>2007,'compat_year_to'=>2012,'shared_models'=>['Nissan Altima (2007-2012) - own generation'],'shared_vehicles'=>[['make'=>'NISSAN','model'=>'ALTIMA','year_from'=>2007,'year_to'=>2012,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'L31','generation'=>'Altima Gen 3 (L31)','body_style'=>'Sedan','compat_year_from'=>2002,'compat_year_to'=>2006,'shared_models'=>['Nissan Altima (2002-2006) - own generation'],'shared_vehicles'=>[['make'=>'NISSAN','model'=>'ALTIMA','year_from'=>2002,'year_to'=>2006,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        // ══════════════════════════════════════════════════════
        // NISSAN SENTRA — general-knowledge boundaries, spot-check
        // recommended.
        // ══════════════════════════════════════════════════════
        if ($make === 'NISSAN' && $model === 'SENTRA') {
            if ($year >= 2013) return array_merge($default, ['platform_code'=>'B17','generation'=>'Sentra B17','body_style'=>'Sedan','compat_year_from'=>2013,'compat_year_to'=>2019,'shared_models'=>['Nissan Sentra (2013-2019) - own generation'],'shared_vehicles'=>[['make'=>'NISSAN','model'=>'SENTRA','year_from'=>2013,'year_to'=>2019,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2007) return array_merge($default, ['platform_code'=>'B16','generation'=>'Sentra B16','body_style'=>'Sedan','compat_year_from'=>2007,'compat_year_to'=>2012,'shared_models'=>['Nissan Sentra (2007-2012) - own generation'],'shared_vehicles'=>[['make'=>'NISSAN','model'=>'SENTRA','year_from'=>2007,'year_to'=>2012,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'B15','generation'=>'Sentra B15','body_style'=>'Sedan','compat_year_from'=>2000,'compat_year_to'=>2006,'shared_models'=>['Nissan Sentra (2000-2006) - own generation'],'shared_vehicles'=>[['make'=>'NISSAN','model'=>'SENTRA','year_from'=>2000,'year_to'=>2006,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        // ══════════════════════════════════════════════════════
        // KIA OPTIMA — general-knowledge boundaries, spot-check
        // recommended.
        // ══════════════════════════════════════════════════════
        if ($make === 'KIA' && $model === 'OPTIMA') {
            if ($year >= 2011) return array_merge($default, ['platform_code'=>'TF','generation'=>'Optima TF','body_style'=>'Sedan','compat_year_from'=>2011,'compat_year_to'=>2015,'shared_models'=>['Kia Optima (2011-2015) - own generation'],'shared_vehicles'=>[['make'=>'KIA','model'=>'OPTIMA','year_from'=>2011,'year_to'=>2015,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'MS','generation'=>'Optima MS','body_style'=>'Sedan','compat_year_from'=>2006,'compat_year_to'=>2010,'shared_models'=>['Kia Optima (2006-2010) - own generation'],'shared_vehicles'=>[['make'=>'KIA','model'=>'OPTIMA','year_from'=>2006,'year_to'=>2010,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        // ══════════════════════════════════════════════════════
        // TOYOTA MATRIX — search-verified. Cross-references the
        // existing Corolla/Matrix/Vibe block for 2003-2008 and
        // 2009-2013 — this entry exists so a direct Matrix lookup
        // (not via Corolla) still resolves correctly.
        // ══════════════════════════════════════════════════════
        if ($make === 'TOYOTA' && $model === 'MATRIX') {
            if ($year >= 2009) return array_merge($default, ['platform_code'=>'E140','generation'=>'Matrix Gen 2','body_style'=>'Hatchback','compat_year_from'=>2009,'compat_year_to'=>2013,'shared_models'=>['Toyota Matrix (2009-2013) - own generation'],'shared_vehicles'=>[['make'=>'TOYOTA','model'=>'MATRIX','year_from'=>2009,'year_to'=>2013,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'E130','generation'=>'Matrix Gen 1','body_style'=>'Hatchback','compat_year_from'=>2003,'compat_year_to'=>2008,'shared_models'=>['Toyota Matrix (2003-2008) - own generation', 'Pontiac Vibe (2003-2008) - chassis-mate only'],'shared_vehicles'=>[['make'=>'TOYOTA','model'=>'MATRIX','year_from'=>2003,'year_to'=>2008,'categories'=>self::OWN_GENERATION_CATEGORIES],['make'=>'PONTIAC','model'=>'VIBE','year_from'=>2003,'year_to'=>2008,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES]]]);
        }

        // ══════════════════════════════════════════════════════
        // TOYOTA YARIS — search-verified US generation boundaries.
        // Note: your 2002 stock entry predates US Yaris (2007+) —
        // may be an earlier-market (Vitz/Nigeria) import; not covered
        // by this US-market split, flag for review if it recurs.
        // ══════════════════════════════════════════════════════
        if ($make === 'TOYOTA' && $model === 'YARIS') {
            if ($year >= 2012) return array_merge($default, ['platform_code'=>'XP150','generation'=>'Yaris Gen 2 (US)','body_style'=>'Hatchback/Sedan','compat_year_from'=>2012,'compat_year_to'=>2018,'shared_models'=>['Toyota Yaris (2012-2018) - own generation'],'shared_vehicles'=>[['make'=>'TOYOTA','model'=>'YARIS','year_from'=>2012,'year_to'=>2018,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'XP90','generation'=>'Yaris Gen 1 (US)','body_style'=>'Hatchback/Sedan','compat_year_from'=>2007,'compat_year_to'=>2011,'shared_models'=>['Toyota Yaris (2007-2011) - own generation'],'shared_vehicles'=>[['make'=>'TOYOTA','model'=>'YARIS','year_from'=>2007,'year_to'=>2011,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        // ══════════════════════════════════════════════════════
        // TOYOTA TACOMA — search-verified.
        // ══════════════════════════════════════════════════════
        if ($make === 'TOYOTA' && $model === 'TACOMA') {
            if ($year >= 2016) return array_merge($default, ['platform_code'=>'Gen3','generation'=>'Tacoma Gen 3','body_style'=>'Pickup','compat_year_from'=>2016,'compat_year_to'=>2023,'shared_models'=>['Toyota Tacoma (2016-2023) - own generation'],'shared_vehicles'=>[['make'=>'TOYOTA','model'=>'TACOMA','year_from'=>2016,'year_to'=>2023,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2005) return array_merge($default, ['platform_code'=>'Gen2','generation'=>'Tacoma Gen 2','body_style'=>'Pickup','compat_year_from'=>2005,'compat_year_to'=>2015,'shared_models'=>['Toyota Tacoma (2005-2015) - own generation'],'shared_vehicles'=>[['make'=>'TOYOTA','model'=>'TACOMA','year_from'=>2005,'year_to'=>2015,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'Gen1','generation'=>'Tacoma Gen 1','body_style'=>'Pickup','compat_year_from'=>1995,'compat_year_to'=>2004,'shared_models'=>['Toyota Tacoma (1995-2004) - own generation'],'shared_vehicles'=>[['make'=>'TOYOTA','model'=>'TACOMA','year_from'=>1995,'year_to'=>2004,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        // ══════════════════════════════════════════════════════
        // HONDA ODYSSEY — search-verified.
        // ══════════════════════════════════════════════════════
        if ($make === 'HONDA' && $model === 'ODYSSEY') {
            if ($year >= 2011) return array_merge($default, ['platform_code'=>'RL5','generation'=>'Odyssey Gen 4','body_style'=>'Minivan','compat_year_from'=>2011,'compat_year_to'=>2017,'shared_models'=>['Honda Odyssey (2011-2017) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'ODYSSEY','year_from'=>2011,'year_to'=>2017,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2005) return array_merge($default, ['platform_code'=>'RL3','generation'=>'Odyssey Gen 3','body_style'=>'Minivan','compat_year_from'=>2005,'compat_year_to'=>2010,'shared_models'=>['Honda Odyssey (2005-2010) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'ODYSSEY','year_from'=>2005,'year_to'=>2010,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 1999) return array_merge($default, ['platform_code'=>'RL1','generation'=>'Odyssey Gen 2','body_style'=>'Minivan','compat_year_from'=>1999,'compat_year_to'=>2004,'shared_models'=>['Honda Odyssey (1999-2004) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'ODYSSEY','year_from'=>1999,'year_to'=>2004,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'RA1','generation'=>'Odyssey Gen 1','body_style'=>'Minivan','compat_year_from'=>1995,'compat_year_to'=>1998,'shared_models'=>['Honda Odyssey (1995-1998) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'ODYSSEY','year_from'=>1995,'year_to'=>1998,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        // ══════════════════════════════════════════════════════
        // HONDA PILOT — general-knowledge boundaries, spot-check
        // recommended.
        // ══════════════════════════════════════════════════════
        if ($make === 'HONDA' && $model === 'PILOT') {
            if ($year >= 2016) return array_merge($default, ['platform_code'=>'YF5','generation'=>'Pilot Gen 3','body_style'=>'SUV','compat_year_from'=>2016,'compat_year_to'=>2022,'shared_models'=>['Honda Pilot (2016-2022) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'PILOT','year_from'=>2016,'year_to'=>2022,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2009) return array_merge($default, ['platform_code'=>'YF3','generation'=>'Pilot Gen 2','body_style'=>'SUV','compat_year_from'=>2009,'compat_year_to'=>2015,'shared_models'=>['Honda Pilot (2009-2015) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'PILOT','year_from'=>2009,'year_to'=>2015,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'YF1','generation'=>'Pilot Gen 1','body_style'=>'SUV','compat_year_from'=>2003,'compat_year_to'=>2008,'shared_models'=>['Honda Pilot (2003-2008) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'PILOT','year_from'=>2003,'year_to'=>2008,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        // ══════════════════════════════════════════════════════
        // HONDA CIVIC — general-knowledge boundaries for the range
        // in stock (2001-2012), spot-check recommended.
        // ══════════════════════════════════════════════════════
        if ($make === 'HONDA' && $model === 'CIVIC') {
            if ($year >= 2012) return array_merge($default, ['platform_code'=>'FB','generation'=>'Civic Gen 9 (FB)','body_style'=>'Sedan/Coupe','compat_year_from'=>2012,'compat_year_to'=>2015,'shared_models'=>['Honda Civic (2012-2015) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'CIVIC','year_from'=>2012,'year_to'=>2015,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2006) return array_merge($default, ['platform_code'=>'FA/FG','generation'=>'Civic Gen 8 (FA/FG)','body_style'=>'Sedan/Coupe','compat_year_from'=>2006,'compat_year_to'=>2011,'shared_models'=>['Honda Civic (2006-2011) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'CIVIC','year_from'=>2006,'year_to'=>2011,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'EM/ES','generation'=>'Civic Gen 7 (EM/ES)','body_style'=>'Sedan/Coupe','compat_year_from'=>2001,'compat_year_to'=>2005,'shared_models'=>['Honda Civic (2001-2005) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'CIVIC','year_from'=>2001,'year_to'=>2005,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        // ══════════════════════════════════════════════════════
        // HONDA CR-V — search-verified for Gen 3; Gen 1/2 boundaries
        // from general knowledge, spot-check recommended.
        // ══════════════════════════════════════════════════════
        if ($make === 'HONDA' && $model === 'CR-V') {
            if ($year >= 2007) return array_merge($default, ['platform_code'=>'RE','generation'=>'CR-V Gen 3','body_style'=>'SUV','compat_year_from'=>2007,'compat_year_to'=>2011,'shared_models'=>['Honda CR-V (2007-2011) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'CR-V','year_from'=>2007,'year_to'=>2011,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2002) return array_merge($default, ['platform_code'=>'RD4-7','generation'=>'CR-V Gen 2','body_style'=>'SUV','compat_year_from'=>2002,'compat_year_to'=>2006,'shared_models'=>['Honda CR-V (2002-2006) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'CR-V','year_from'=>2002,'year_to'=>2006,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'RD1-3','generation'=>'CR-V Gen 1','body_style'=>'SUV','compat_year_from'=>1997,'compat_year_to'=>2001,'shared_models'=>['Honda CR-V (1997-2001) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'CR-V','year_from'=>1997,'year_to'=>2001,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        // ══════════════════════════════════════════════════════
        // HONDA FIT — search-verified.
        // ══════════════════════════════════════════════════════
        if ($make === 'HONDA' && $model === 'FIT') {
            if ($year >= 2015) return array_merge($default, ['platform_code'=>'GK','generation'=>'Fit Gen 3','body_style'=>'Hatchback','compat_year_from'=>2015,'compat_year_to'=>2020,'shared_models'=>['Honda Fit (2015-2020) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'FIT','year_from'=>2015,'year_to'=>2020,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2009) return array_merge($default, ['platform_code'=>'GE','generation'=>'Fit Gen 2','body_style'=>'Hatchback','compat_year_from'=>2009,'compat_year_to'=>2014,'shared_models'=>['Honda Fit (2009-2014) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'FIT','year_from'=>2009,'year_to'=>2014,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'GD','generation'=>'Fit Gen 1','body_style'=>'Hatchback','compat_year_from'=>2007,'compat_year_to'=>2008,'shared_models'=>['Honda Fit (2007-2008) - own generation'],'shared_vehicles'=>[['make'=>'HONDA','model'=>'FIT','year_from'=>2007,'year_to'=>2008,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        // ══════════════════════════════════════════════════════
        // SUBARU FORESTER — search-verified.
        // ══════════════════════════════════════════════════════
        if ($make === 'SUBARU' && $model === 'FORESTER') {
            if ($year >= 2019) return array_merge($default, ['platform_code'=>'SK','generation'=>'Forester SK','body_style'=>'SUV','compat_year_from'=>2019,'compat_year_to'=>2024,'shared_models'=>['Subaru Forester (2019-2024) - own generation'],'shared_vehicles'=>[['make'=>'SUBARU','model'=>'FORESTER','year_from'=>2019,'year_to'=>2024,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2014) return array_merge($default, ['platform_code'=>'SJ','generation'=>'Forester SJ','body_style'=>'SUV','compat_year_from'=>2014,'compat_year_to'=>2018,'shared_models'=>['Subaru Forester (2014-2018) - own generation'],'shared_vehicles'=>[['make'=>'SUBARU','model'=>'FORESTER','year_from'=>2014,'year_to'=>2018,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            if ($year >= 2009) return array_merge($default, ['platform_code'=>'SH','generation'=>'Forester SH','body_style'=>'SUV','compat_year_from'=>2009,'compat_year_to'=>2013,'shared_models'=>['Subaru Forester (2009-2013) - own generation'],'shared_vehicles'=>[['make'=>'SUBARU','model'=>'FORESTER','year_from'=>2009,'year_to'=>2013,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'SG','generation'=>'Forester SG','body_style'=>'SUV/Wagon','compat_year_from'=>2003,'compat_year_to'=>2008,'shared_models'=>['Subaru Forester (2003-2008) - own generation'],'shared_vehicles'=>[['make'=>'SUBARU','model'=>'FORESTER','year_from'=>2003,'year_to'=>2008,'categories'=>self::OWN_GENERATION_CATEGORIES]]]);
        }

        // ══════════════════════════════════════════════════════
        // PONTIAC VIBE — direct lookup entry (previously only existed
        // as a cross-model reference inside the Matrix entries, so a
        // Vibe part searched directly wouldn't have resolved). Gen 2
        // deliberately ends 2010, NOT 2013 like the Matrix Gen 2 —
        // Pontiac was discontinued in 2010, so the Vibe stopped two
        // years before its Matrix twin did. Don't assume identical
        // end dates just because they're chassis-mates.
        // ══════════════════════════════════════════════════════
        if ($make === 'PONTIAC' && $model === 'VIBE') {
            if ($year >= 2009) return array_merge($default, ['platform_code'=>'E140','generation'=>'Vibe Gen 2','body_style'=>'Hatchback','compat_year_from'=>2009,'compat_year_to'=>2010,'shared_models'=>['Pontiac Vibe (2009-2010) - own generation', 'Toyota Matrix (2009-2013) - chassis-mate only'],'shared_vehicles'=>[['make'=>'PONTIAC','model'=>'VIBE','year_from'=>2009,'year_to'=>2010,'categories'=>self::OWN_GENERATION_CATEGORIES],['make'=>'TOYOTA','model'=>'MATRIX','year_from'=>2009,'year_to'=>2013,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES]]]);
            return array_merge($default, ['platform_code'=>'E130','generation'=>'Vibe Gen 1','body_style'=>'Hatchback','compat_year_from'=>2003,'compat_year_to'=>2008,'shared_models'=>['Pontiac Vibe (2003-2008) - own generation', 'Toyota Matrix (2003-2008) - chassis-mate only'],'shared_vehicles'=>[['make'=>'PONTIAC','model'=>'VIBE','year_from'=>2003,'year_to'=>2008,'categories'=>self::OWN_GENERATION_CATEGORIES],['make'=>'TOYOTA','model'=>'MATRIX','year_from'=>2003,'year_to'=>2008,'categories'=>self::CROSS_MODEL_SAFE_CATEGORIES]]]);
        }

        return $default;
    }
}
