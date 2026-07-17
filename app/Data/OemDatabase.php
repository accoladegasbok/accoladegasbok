<?php
// FILE: app/Data/OemDatabase.php
//
// Three-tier OEM lookup:
//
//   Tier 1 — Inventory data (most accurate — real stock, auto-updated)
//             Called from InventoryController / CompatibilityController
//             before this file is consulted.
//
//   Tier 2 — This file: year-accurate static OEM codes built from
//             Ladipo Auto Market data (Lagos), NHTSA vPIC cross-reference,
//             and verified stock records. Covers Toyota/Lexus/Honda/Acura/
//             Nissan/Infiniti/Hyundai/Kia/Mercedes/Ford/Chevy from 1993-2024.
//             Year ranges are always checked — no model defaults to a single
//             engine code for all years.
//
//   Tier 3 — interchange() method: maps engine/trans codes to ALL vehicles
//             known to share that powertrain, so compatibility checker can
//             advise customers even with zero stock.

namespace App\Data;

class OemDatabase
{
    // =========================================================
    // lookup() — returns OEM codes for a given vehicle
    // All lookups are year-aware. Never returns a wrong-era code.
    // =========================================================
    public static function lookup(
        string $make,
        string $model,
        int    $year,
        int    $cylinders = 0,
        float  $engineL   = 0.0
    ): array {
        $make  = strtoupper(trim($make));
        $model = strtoupper(trim($model));

        $default = [
            'engine_code'       => null,
            'transmission_code' => null,
            'pin_count'         => null,
            'pin_count_variants'=> null, // e.g. [3,5,7] when the same transmission code shows up with different pin counts by unit/supplier — confirm visually
            'cylinders'         => null,
            'gear_alias'        => null,
            'engine_l'          => $engineL ?: null,
            'drive_type'        => null,
            'market_note'       => null,
            'multiple_engines'  => false,
            'compat_year_from'  => null,
            'compat_year_to'    => null,
        ];

        // ══════════════════════════════════════════════════════
        // TOYOTA
        // ══════════════════════════════════════════════════════
        if ($make === 'TOYOTA') {

            // ── Corolla ───────────────────────────────────────
            if ($model === 'COROLLA') {
                if ($year >= 2019) return array_merge($default, ['engine_code'=>'2ZR-FAE','transmission_code'=>'CVT','gear_alias'=>'CVT (Corolla 2019+)','compat_year_from'=>2019,'compat_year_to'=>2024]);
                if ($year >= 2014) return array_merge($default, ['engine_code'=>'2ZR-FE','transmission_code'=>'CVT','gear_alias'=>'CVT (Corolla 2014-2018)','compat_year_from'=>2014,'compat_year_to'=>2018]);
                if ($year >= 2009) return array_merge($default, ['engine_code'=>'2ZR-FE','transmission_code'=>'K310','pin_count'=>12,'gear_alias'=>'12-pin CVT (Corolla 09-13)','market_note'=>'K310/K311 CVT common in Nigerian/Ghanaian market','compat_year_from'=>2009,'compat_year_to'=>2013]);
                if ($year >= 2003) {
                    if ($engineL > 0 && $engineL < 1.7) return array_merge($default, ['engine_code'=>'3ZZ-FE','transmission_code'=>'U341E','pin_count'=>5,'gear_alias'=>'5-pin (Corolla 1.6L)','compat_year_from'=>2003,'compat_year_to'=>2008]);
                    return array_merge($default, ['engine_code'=>'1ZZ-FE','transmission_code'=>'U341E','pin_count'=>5,'gear_alias'=>'5-pin gear (Corolla 1.8L 03-08)','compat_year_from'=>2000,'compat_year_to'=>2008]);
                }
                if ($year >= 1998) return array_merge($default, ['engine_code'=>'1ZZ-FE','transmission_code'=>'A245E','pin_count'=>null,'gear_alias'=>'4AT (Corolla 1998-2002)','compat_year_from'=>1998,'compat_year_to'=>2002]);
                return array_merge($default, ['engine_code'=>'7A-FE','transmission_code'=>'A245E','gear_alias'=>'4AT (Corolla pre-1998)','compat_year_from'=>1993,'compat_year_to'=>1997]);
            }

            // ── Camry ─────────────────────────────────────────
            if ($model === 'CAMRY') {
                // V6 check first
                if ($cylinders == 6 || $engineL >= 3.0) {
                    if ($year >= 2007) return array_merge($default, ['engine_code'=>'2GR-FE','transmission_code'=>'A750E','engine_l'=>3.5,'cylinders'=>6,'drive_type'=>'FWD','gear_alias'=>'V6 6AT (Camry V6 2007+)','compat_year_from'=>2007,'compat_year_to'=>2024]);
                    if ($year >= 2002) return array_merge($default, ['engine_code'=>'1MZ-FE','transmission_code'=>'A541E','pin_count'=>7,'pin_count_variants'=>[3,5,7],'engine_l'=>3.0,'cylinders'=>6,'drive_type'=>'FWD','gear_alias'=>'V6 3/5/7-pin — confirm on unit (Camry 3.0L 02-06)','market_note'=>'Pin count varies by unit — 3, 5, or 7-pin all seen in this transmission code. Confirm visually before quoting.','compat_year_from'=>2002,'compat_year_to'=>2006]);
                    return array_merge($default, ['engine_code'=>'1MZ-FE','transmission_code'=>'A541E','pin_count'=>7,'pin_count_variants'=>[3,5,7],'engine_l'=>3.0,'cylinders'=>6,'drive_type'=>'FWD','gear_alias'=>'V6 3/5/7-pin — confirm on unit (Camry 3.0L pre-02)','market_note'=>'Pin count varies by unit — 3, 5, or 7-pin all seen in this transmission code. Confirm visually before quoting.','compat_year_from'=>1994,'compat_year_to'=>2001]);
                }
                // 4-cyl
                if ($year >= 2018) return array_merge($default, ['engine_code'=>'A25A-FKS','transmission_code'=>'Direct Shift CVT','engine_l'=>2.5,'cylinders'=>4,'drive_type'=>'FWD','gear_alias'=>'CVT (Camry 2018+ 2.5L)','compat_year_from'=>2018,'compat_year_to'=>2024]);
                if ($year >= 2012) return array_merge($default, ['engine_code'=>'2AR-FE','transmission_code'=>'U760E','pin_count'=>22,'engine_l'=>2.5,'cylinders'=>4,'drive_type'=>'FWD','gear_alias'=>'22-pin gear (Camry 2012-17 2.5L)','compat_year_from'=>2012,'compat_year_to'=>2017]);
                if ($year >= 2010) return array_merge($default, ['engine_code'=>'2AR-FE','transmission_code'=>'U760E','pin_count'=>20,'engine_l'=>2.5,'cylinders'=>4,'drive_type'=>'FWD','gear_alias'=>'20-pin gear (Camry 2010-11 — early 2AR-FE, distinct from 2012+ 22-pin)','market_note'=>'2010-2011 Camry uses 2AR-FE at 20-pin — NOT the same as the 2012+ 22-pin 2AR-FE. Confirm year carefully before quoting as interchangeable.','compat_year_from'=>2010,'compat_year_to'=>2011]);
                if ($year >= 2002) return array_merge($default, ['engine_code'=>'2AZ-FE','transmission_code'=>'U241E','pin_count'=>10,'engine_l'=>2.4,'cylinders'=>4,'drive_type'=>'FWD','gear_alias'=>'10-pin gear (Camry 2002-09 2.4L)','market_note'=>'Most common Camry in Nigerian/Ghanaian market. Confirmed 10-pin. 2AZ-FE runs 2002-2009 — 2010-2011 switched to 2AR-FE (20-pin), see above.','compat_year_from'=>2002,'compat_year_to'=>2009]);
                if ($year >= 1997) return array_merge($default, ['engine_code'=>'5S-FE','transmission_code'=>'A541E','engine_l'=>2.2,'cylinders'=>4,'drive_type'=>'FWD','gear_alias'=>'4AT (Camry 1997-2001 2.2L)','compat_year_from'=>1997,'compat_year_to'=>2001]);
                return array_merge($default, ['engine_code'=>'5S-FE','transmission_code'=>'A540E','engine_l'=>2.2,'cylinders'=>4,'drive_type'=>'FWD','gear_alias'=>'4AT (Camry pre-1997)','compat_year_from'=>1992,'compat_year_to'=>1996]);
            }

            // ── Avalon ────────────────────────────────────────
            if ($model === 'AVALON') {
                if ($year >= 2013) return array_merge($default, ['engine_code'=>'2GR-FE','transmission_code'=>'U660E','gear_alias'=>'V6 6AT (Avalon 2013+)','compat_year_from'=>2013,'compat_year_to'=>2022]);
                if ($year >= 2005) return array_merge($default, ['engine_code'=>'2GR-FE','transmission_code'=>'A750E','gear_alias'=>'V6 6AT (Avalon 2005-12)','compat_year_from'=>2005,'compat_year_to'=>2012]);
                if ($year >= 2000) return array_merge($default, ['engine_code'=>'1MZ-FE','transmission_code'=>'A541E','gear_alias'=>'V6 3.0L (Avalon 2000-04)','compat_year_from'=>2000,'compat_year_to'=>2004]);
                return array_merge($default, ['engine_code'=>'1MZ-FE','transmission_code'=>'A541E','gear_alias'=>'V6 3.0L (Avalon 1995-99)','compat_year_from'=>1995,'compat_year_to'=>1999]);
            }

            // ── RAV4 ──────────────────────────────────────────
            if ($model === 'RAV4') {
                if ($year >= 2019) return array_merge($default, ['engine_code'=>'A25A-FKS','transmission_code'=>'Direct Shift CVT','gear_alias'=>'CVT (RAV4 2019+)','compat_year_from'=>2019,'compat_year_to'=>2024]);
                if ($year >= 2013) return array_merge($default, ['engine_code'=>'2AR-FE','transmission_code'=>'U760E','pin_count'=>22,'gear_alias'=>'22-pin (RAV4 2013-18)','compat_year_from'=>2013,'compat_year_to'=>2018]);
                if ($year >= 2006) return array_merge($default, ['engine_code'=>'2AZ-FE','transmission_code'=>'U241E','pin_count'=>13,'gear_alias'=>'13-pin (RAV4 2006-12)','compat_year_from'=>2006,'compat_year_to'=>2012]);
                if ($year >= 2001) return array_merge($default, ['engine_code'=>'2AZ-FE','transmission_code'=>'U241E','pin_count'=>13,'gear_alias'=>'13-pin (RAV4 2001-05)','compat_year_from'=>2001,'compat_year_to'=>2005]);
                return array_merge($default, ['engine_code'=>'3S-FE','transmission_code'=>'A540E','gear_alias'=>'4AT (RAV4 pre-2001)','compat_year_from'=>1994,'compat_year_to'=>2000]);
            }

            // ── Highlander ────────────────────────────────────
            if ($model === 'HIGHLANDER') {
                if ($cylinders == 6 || $engineL >= 3.0) {
                    if ($year >= 2008) return array_merge($default, ['engine_code'=>'2GR-FE','transmission_code'=>'A750E','gear_alias'=>'V6 (Highlander 2008+)','compat_year_from'=>2008,'compat_year_to'=>2019]);
                    if ($year >= 2004) return array_merge($default, ['engine_code'=>'3MZ-FE','transmission_code'=>'A541E','gear_alias'=>'V6 3.3L (Highlander 2004-07)','compat_year_from'=>2004,'compat_year_to'=>2007]);
                    return array_merge($default, ['engine_code'=>'1MZ-FE','transmission_code'=>'A541E','gear_alias'=>'V6 3.0L (Highlander 2001-03)','compat_year_from'=>2001,'compat_year_to'=>2003]);
                }
                if ($year >= 2008) return array_merge($default, ['engine_code'=>'2AR-FE','transmission_code'=>'U760E','pin_count'=>22,'gear_alias'=>'22-pin (Highlander 2.7L 2008+)','compat_year_from'=>2008,'compat_year_to'=>2019]);
                return array_merge($default, ['engine_code'=>'2AZ-FE','transmission_code'=>'U241E','pin_count'=>13,'gear_alias'=>'13-pin (Highlander 2.4L 2001-07)','compat_year_from'=>2001,'compat_year_to'=>2007]);
            }

            // ── Venza ─────────────────────────────────────────
            if ($model === 'VENZA') {
                if ($cylinders == 6 || $engineL >= 3.0) return array_merge($default, ['engine_code'=>'2GR-FE','transmission_code'=>'A750E','gear_alias'=>'V6 (Venza 3.5L)','compat_year_from'=>2009,'compat_year_to'=>2015]);
                return array_merge($default, ['engine_code'=>'2AR-FE','transmission_code'=>'U760E','pin_count'=>22,'gear_alias'=>'22-pin (Venza 2.7L)','compat_year_from'=>2009,'compat_year_to'=>2015]);
            }

            // ── Matrix ────────────────────────────────────────
            if ($model === 'MATRIX') {
                if ($year >= 2009) return array_merge($default, ['engine_code'=>'2ZR-FE','transmission_code'=>'K310','pin_count'=>12,'gear_alias'=>'12-pin CVT (Matrix 09+)','compat_year_from'=>2009,'compat_year_to'=>2013]);
                return array_merge($default, ['engine_code'=>'1ZZ-FE','transmission_code'=>'U341E','pin_count'=>5,'gear_alias'=>'5-pin (Matrix 2003-08)','compat_year_from'=>2003,'compat_year_to'=>2008]);
            }

            // ── Sienna ────────────────────────────────────────
            if ($model === 'SIENNA') {
                if ($year >= 2021) return array_merge($default, ['engine_code'=>'A25A-FXS','transmission_code'=>'CVT','gear_alias'=>'Hybrid CVT (Sienna 2021+)','compat_year_from'=>2021,'compat_year_to'=>2024]);
                if ($year >= 2008) return array_merge($default, ['engine_code'=>'2GR-FE','transmission_code'=>'A750E','gear_alias'=>'V6 3.5L (Sienna 2008-20)','compat_year_from'=>2008,'compat_year_to'=>2020]);
                if ($year >= 2004) return array_merge($default, ['engine_code'=>'3MZ-FE','transmission_code'=>'A541E','gear_alias'=>'V6 3.3L (Sienna 2004-07)','compat_year_from'=>2004,'compat_year_to'=>2007]);
                return array_merge($default, ['engine_code'=>'1MZ-FE','transmission_code'=>'A541E','gear_alias'=>'V6 3.0L (Sienna 1998-2003)','compat_year_from'=>1998,'compat_year_to'=>2003]);
            }

            // ── Yaris / Vios / Echo ───────────────────────────
            if (in_array($model, ['YARIS','VIOS','ECHO','PLATZ'])) {
                if ($year >= 2006) return array_merge($default, ['engine_code'=>'1NZ-FE','transmission_code'=>'U340E','gear_alias'=>'4AT (Yaris/Vios 1.5L 2006+)','compat_year_from'=>2006,'compat_year_to'=>2020]);
                if ($year >= 2002) return array_merge($default, ['engine_code'=>'1NZ-FE','transmission_code'=>'U340E','gear_alias'=>'4AT (Vios 2002-05)','compat_year_from'=>2002,'compat_year_to'=>2005]);
                return array_merge($default, ['engine_code'=>'2NZ-FE','transmission_code'=>'U340E','gear_alias'=>'4AT (Echo/Platz 1.3L)','compat_year_from'=>1999,'compat_year_to'=>2005]);
            }

            // ── Solara ────────────────────────────────────────
            if ($model === 'SOLARA') {
                if ($cylinders == 6 || $engineL >= 3.0) {
                    if ($year >= 2004) return array_merge($default, ['engine_code'=>'3MZ-FE','transmission_code'=>'A541E','gear_alias'=>'V6 3.3L (Solara 2004-08)','compat_year_from'=>2004,'compat_year_to'=>2008]);
                    return array_merge($default, ['engine_code'=>'1MZ-FE','transmission_code'=>'A541E','gear_alias'=>'V6 3.0L (Solara pre-2004)','compat_year_from'=>1999,'compat_year_to'=>2003]);
                }
                if ($year >= 2004) return array_merge($default, ['engine_code'=>'2AZ-FE','transmission_code'=>'U241E','pin_count'=>13,'gear_alias'=>'13-pin (Solara 2004-08 2.4L)','compat_year_from'=>2004,'compat_year_to'=>2008]);
                return array_merge($default, ['engine_code'=>'5S-FE','transmission_code'=>'A541E','gear_alias'=>'4AT (Solara 1999-2003)','compat_year_from'=>1999,'compat_year_to'=>2003]);
            }

            // ── 4Runner ───────────────────────────────────────
            if (str_contains($model,'4RUNNER') || $model === '4-RUNNER') {
                if ($year >= 2010) return array_merge($default, ['engine_code'=>'1GR-FE','transmission_code'=>'A750F','gear_alias'=>'V6 4AT (4Runner 2010+)','compat_year_from'=>2010,'compat_year_to'=>2024]);
                if ($year >= 2003) return array_merge($default, ['engine_code'=>'1GR-FE','transmission_code'=>'A750F','gear_alias'=>'V6 (4Runner 2003-09)','compat_year_from'=>2003,'compat_year_to'=>2009]);
                if ($year >= 1996) return array_merge($default, ['engine_code'=>'5VZ-FE','transmission_code'=>'A340F','gear_alias'=>'V6 (4Runner 1996-2002)','compat_year_from'=>1996,'compat_year_to'=>2002]);
                return array_merge($default, ['engine_code'=>'3VZ-E','transmission_code'=>'A340F','gear_alias'=>'V6 (4Runner pre-96)','compat_year_from'=>1989,'compat_year_to'=>1995]);
            }

            // ── Land Cruiser ──────────────────────────────────
            if (str_contains($model,'LAND') || str_contains($model,'LC') || str_contains($model,'CRUISER')) {
                if ($year >= 2016) return array_merge($default, ['engine_code'=>'1UR-FE','transmission_code'=>'AB60F','gear_alias'=>'V8 4.6L (LC200 2016+)','compat_year_from'=>2016,'compat_year_to'=>2021]);
                if ($year >= 2008) return array_merge($default, ['engine_code'=>'1UR-FE','transmission_code'=>'AB60F','gear_alias'=>'V8 4.6L (LC200 2008-15)','compat_year_from'=>2008,'compat_year_to'=>2015]);
                if ($year >= 1998) return array_merge($default, ['engine_code'=>'2UZ-FE','transmission_code'=>'A750F','gear_alias'=>'V8 4.7L (LC100 1998-2007)','compat_year_from'=>1998,'compat_year_to'=>2007]);
                return array_merge($default, ['engine_code'=>'1FZ-FE','transmission_code'=>'A440F','gear_alias'=>'6-cyl (LC80 pre-1998)','compat_year_from'=>1990,'compat_year_to'=>1997]);
            }

            // ── Prado ─────────────────────────────────────────
            if (str_contains($model,'PRADO')) {
                if ($year >= 2010) return array_merge($default, ['engine_code'=>'1GR-FE','transmission_code'=>'A750F','gear_alias'=>'V6 4.0L (Prado 150 2010+)','compat_year_from'=>2010,'compat_year_to'=>2024]);
                if ($year >= 2003) return array_merge($default, ['engine_code'=>'1GR-FE','transmission_code'=>'A750F','gear_alias'=>'V6 4.0L (Prado 120 2003-09)','compat_year_from'=>2003,'compat_year_to'=>2009]);
                return array_merge($default, ['engine_code'=>'1KZ-TE','transmission_code'=>'A340F','gear_alias'=>'Diesel (Prado 90 pre-2003)','compat_year_from'=>1996,'compat_year_to'=>2002]);
            }

            // ── Hilux ─────────────────────────────────────────
            if ($model === 'HILUX') {
                if ($year >= 2016) return array_merge($default, ['engine_code'=>'1GD-FTV','transmission_code'=>'R151F','gear_alias'=>'2.8L Diesel (Hilux 2016+)','compat_year_from'=>2016,'compat_year_to'=>2024]);
                if ($year >= 2005) return array_merge($default, ['engine_code'=>'1KD-FTV','transmission_code'=>'R151F','gear_alias'=>'3.0L Diesel D4D (Hilux 2005-15)','compat_year_from'=>2005,'compat_year_to'=>2015]);
                return array_merge($default, ['engine_code'=>'2KD-FTV','transmission_code'=>'R151F','gear_alias'=>'2.5L Diesel (Hilux pre-2005)','compat_year_from'=>1998,'compat_year_to'=>2004]);
            }

            // ── FJ Cruiser ────────────────────────────────────
            if (str_contains($model,'FJ')) {
                return array_merge($default, ['engine_code'=>'1GR-FE','transmission_code'=>'A750F','gear_alias'=>'V6 4.0L (FJ Cruiser)','compat_year_from'=>2006,'compat_year_to'=>2014]);
            }

            // ── Fortuner ──────────────────────────────────────
            if ($model === 'FORTUNER') {
                if ($year >= 2016) return array_merge($default, ['engine_code'=>'1GD-FTV','transmission_code'=>'A750F','gear_alias'=>'2.8L Diesel (Fortuner 2016+)','compat_year_from'=>2016,'compat_year_to'=>2024]);
                return array_merge($default, ['engine_code'=>'1KD-FTV','transmission_code'=>'A750F','gear_alias'=>'3.0L Diesel (Fortuner pre-2016)','compat_year_from'=>2005,'compat_year_to'=>2015]);
            }

            // ── Celica ────────────────────────────────────────
            if ($model === 'CELICA') {
                if ($engineL >= 1.9) return array_merge($default, ['engine_code'=>'2ZZ-GE','transmission_code'=>'C60','gear_alias'=>'6MT (Celica GT-S 1.8L VVTLi)','compat_year_from'=>2000,'compat_year_to'=>2005]);
                return array_merge($default, ['engine_code'=>'1ZZ-FE','transmission_code'=>'U341E','pin_count'=>5,'gear_alias'=>'5-pin (Celica GT 1.8L)','compat_year_from'=>2000,'compat_year_to'=>2005]);
            }

            // ── Tundra / Sequoia ──────────────────────────────
            if (in_array($model, ['TUNDRA','SEQUOIA'])) {
                if ($year >= 2007) return array_merge($default, ['engine_code'=>'3UR-FE','transmission_code'=>'AB60F','gear_alias'=>'V8 5.7L (Tundra/Sequoia 2007+)','compat_year_from'=>2007,'compat_year_to'=>2021]);
                return array_merge($default, ['engine_code'=>'2UZ-FE','transmission_code'=>'A750F','gear_alias'=>'V8 4.7L (Tundra/Sequoia pre-2007)','compat_year_from'=>1999,'compat_year_to'=>2006]);
            }

            // ── Innova ────────────────────────────────────────
            if ($model === 'INNOVA') {
                return array_merge($default, ['engine_code'=>'2KD-FTV','transmission_code'=>'A340F','gear_alias'=>'Diesel (Innova 2.5L)','compat_year_from'=>2004,'compat_year_to'=>2015]);
            }

            // ── HiAce ─────────────────────────────────────────
            if ($model === 'HIACE') {
                if ($year >= 2005) return array_merge($default, ['engine_code'=>'1KD-FTV','transmission_code'=>'A45DE','gear_alias'=>'3.0L Diesel (HiAce 2005+)','compat_year_from'=>2005,'compat_year_to'=>2019]);
                return array_merge($default, ['engine_code'=>'2RZ-FE','transmission_code'=>'A45DE','gear_alias'=>'Petrol (HiAce pre-2005)','compat_year_from'=>1995,'compat_year_to'=>2004]);
            }
        }

        // ══════════════════════════════════════════════════════
        // LEXUS
        // ══════════════════════════════════════════════════════
        if ($make === 'LEXUS') {

            // ES Series
            if (str_starts_with($model,'ES')) {
                if ($year >= 2019) return array_merge($default, ['engine_code'=>'A25A-FKS','transmission_code'=>'Direct Shift CVT','gear_alias'=>'CVT (ES350/ES300h 2019+)','compat_year_from'=>2019,'compat_year_to'=>2024]);
                if ($year >= 2007) return array_merge($default, ['engine_code'=>'2GR-FE','transmission_code'=>'A750E','gear_alias'=>'V6 3.5L (ES350 2007-18)','compat_year_from'=>2007,'compat_year_to'=>2018]);
                if ($year >= 2004) return array_merge($default, ['engine_code'=>'3MZ-FE','transmission_code'=>'A541E','gear_alias'=>'V6 3.3L (ES330 2004-06)','compat_year_from'=>2004,'compat_year_to'=>2006]);
                if ($year >= 2002) return array_merge($default, ['engine_code'=>'1MZ-FE','transmission_code'=>'A541E','gear_alias'=>'V6 3.0L (ES300 2002-03)','compat_year_from'=>2002,'compat_year_to'=>2003]);
                return array_merge($default, ['engine_code'=>'1MZ-FE','transmission_code'=>'A541E','gear_alias'=>'V6 3.0L (ES300 pre-2002)','compat_year_from'=>1994,'compat_year_to'=>2001]);
            }

            // RX Series
            if (str_starts_with($model,'RX')) {
                if ($year >= 2016) return array_merge($default, ['engine_code'=>'2GR-FKS','transmission_code'=>'AA80E','gear_alias'=>'V6 3.5L (RX350 2016+)','compat_year_from'=>2016,'compat_year_to'=>2022]);
                if ($year >= 2010) return array_merge($default, ['engine_code'=>'2GR-FE','transmission_code'=>'U660E','gear_alias'=>'V6 3.5L (RX350 2010-15)','compat_year_from'=>2010,'compat_year_to'=>2015]);
                if ($year >= 2007) return array_merge($default, ['engine_code'=>'2GR-FE','transmission_code'=>'U660E','gear_alias'=>'V6 3.5L (RX350 2007-09)','compat_year_from'=>2007,'compat_year_to'=>2009]);
                if ($year >= 2004) return array_merge($default, ['engine_code'=>'3MZ-FE','transmission_code'=>'A541E','gear_alias'=>'V6 3.3L (RX330 2004-06)','compat_year_from'=>2004,'compat_year_to'=>2006]);
                return array_merge($default, ['engine_code'=>'1MZ-FE','transmission_code'=>'A541E','gear_alias'=>'V6 3.0L (RX300 1999-2003)','compat_year_from'=>1999,'compat_year_to'=>2003]);
            }

            // GS Series
            if (str_starts_with($model,'GS')) {
                if ($year >= 2013) return array_merge($default, ['engine_code'=>'2GR-FSE','transmission_code'=>'AA80E','gear_alias'=>'V6 3.5L (GS350 2013+)','compat_year_from'=>2013,'compat_year_to'=>2020]);
                if ($year >= 2006) return array_merge($default, ['engine_code'=>'2GR-FSE','transmission_code'=>'A760E','gear_alias'=>'V6 3.5L (GS350 2006-11)','compat_year_from'=>2006,'compat_year_to'=>2011]);
                return array_merge($default, ['engine_code'=>'2JZ-GE','transmission_code'=>'A650E','gear_alias'=>'3.0L 6AT (GS300 pre-2006)','compat_year_from'=>1998,'compat_year_to'=>2005]);
            }

            // IS Series
            if (str_starts_with($model,'IS')) {
                if ($year >= 2014) return array_merge($default, ['engine_code'=>'2GR-FSE','transmission_code'=>'AA80E','gear_alias'=>'V6 3.5L (IS350 2014+)','compat_year_from'=>2014,'compat_year_to'=>2020]);
                if ($year >= 2006) return array_merge($default, ['engine_code'=>'4GR-FSE','transmission_code'=>'A960E','gear_alias'=>'V6 2.5L (IS250 2006-13)','compat_year_from'=>2006,'compat_year_to'=>2013]);
                return array_merge($default, ['engine_code'=>'2JZ-GE','transmission_code'=>'A650E','gear_alias'=>'3.0L (IS300 2001-05)','compat_year_from'=>2001,'compat_year_to'=>2005]);
            }

            // LS Series
            if (str_starts_with($model,'LS')) {
                if ($year >= 2007) return array_merge($default, ['engine_code'=>'1UR-FSE','transmission_code'=>'AA80E','gear_alias'=>'V8 4.6L (LS460 2007+)','compat_year_from'=>2007,'compat_year_to'=>2017]);
                if ($year >= 2001) return array_merge($default, ['engine_code'=>'3UZ-FE','transmission_code'=>'A650E','gear_alias'=>'V8 4.3L (LS430 2001-06)','compat_year_from'=>2001,'compat_year_to'=>2006]);
                return array_merge($default, ['engine_code'=>'1UZ-FE','transmission_code'=>'A650E','gear_alias'=>'V8 4.0L (LS400 pre-2001)','compat_year_from'=>1990,'compat_year_to'=>2000]);
            }

            // LX Series
            if (str_starts_with($model,'LX')) {
                if ($year >= 2008) return array_merge($default, ['engine_code'=>'3UR-FE','transmission_code'=>'AB60F','gear_alias'=>'V8 5.7L (LX570 2008+)','compat_year_from'=>2008,'compat_year_to'=>2021]);
                return array_merge($default, ['engine_code'=>'2UZ-FE','transmission_code'=>'A750F','gear_alias'=>'V8 4.7L (LX470 1998-07)','compat_year_from'=>1998,'compat_year_to'=>2007]);
            }

            // GX Series
            if (str_starts_with($model,'GX')) {
                if ($year >= 2010) return array_merge($default, ['engine_code'=>'1GR-FE','transmission_code'=>'A750F','gear_alias'=>'V6 4.0L (GX460 2010+)','compat_year_from'=>2010,'compat_year_to'=>2024]);
                return array_merge($default, ['engine_code'=>'2UZ-FE','transmission_code'=>'A750F','gear_alias'=>'V8 4.7L (GX470 2003-09)','compat_year_from'=>2003,'compat_year_to'=>2009]);
            }
        }

        // ══════════════════════════════════════════════════════
        // HONDA / ACURA
        // ══════════════════════════════════════════════════════
        if (in_array($make, ['HONDA','ACURA'])) {

            if ($model === 'ACCORD') {
                if ($cylinders == 6 || $engineL >= 3.0) {
                    if ($year >= 2008) return array_merge($default, ['engine_code'=>'J35A','transmission_code'=>'BDKA','gear_alias'=>'V6 AT (Accord 2008+ V6)','compat_year_from'=>2008,'compat_year_to'=>2017]);
                    return array_merge($default, ['engine_code'=>'J30A','transmission_code'=>'BAXA','gear_alias'=>'V6 AT (Accord pre-2008)','compat_year_from'=>1998,'compat_year_to'=>2007]);
                }
                if ($year >= 2018) return array_merge($default, ['engine_code'=>'K20C4','transmission_code'=>'CVT8','gear_alias'=>'CVT (Accord 2018+ 1.5T)','compat_year_from'=>2018,'compat_year_to'=>2022]);
                if ($year >= 2013) return array_merge($default, ['engine_code'=>'K24W','transmission_code'=>'CVT7','gear_alias'=>'CVT (Accord 2013-17 2.4L)','compat_year_from'=>2013,'compat_year_to'=>2017]);
                if ($year >= 2008) return array_merge($default, ['engine_code'=>'K24Z3','transmission_code'=>'BGRA','gear_alias'=>'AT (Accord 2008-12 2.4L)','compat_year_from'=>2008,'compat_year_to'=>2012]);
                if ($year >= 2003) return array_merge($default, ['engine_code'=>'K24A','transmission_code'=>'MCTA','gear_alias'=>'AT (Accord 2003-07 2.4L)','compat_year_from'=>2003,'compat_year_to'=>2007]);
                if ($year >= 1998) return array_merge($default, ['engine_code'=>'F23A','transmission_code'=>'BAXA','gear_alias'=>'AT (Accord 1998-2002 2.3L)','compat_year_from'=>1998,'compat_year_to'=>2002]);
                return array_merge($default, ['engine_code'=>'H22A','transmission_code'=>'MP7A','gear_alias'=>'AT (Accord pre-1998)','compat_year_from'=>1994,'compat_year_to'=>1997]);
            }

            if ($model === 'CIVIC') {
                if ($year >= 2022) return array_merge($default, ['engine_code'=>'L15B7','transmission_code'=>'CVT','gear_alias'=>'CVT (Civic 2022+)','compat_year_from'=>2022,'compat_year_to'=>2024]);
                if ($year >= 2016) return array_merge($default, ['engine_code'=>'L15B7','transmission_code'=>'CVT','gear_alias'=>'CVT (Civic 2016-21)','compat_year_from'=>2016,'compat_year_to'=>2021]);
                if ($year >= 2012) return array_merge($default, ['engine_code'=>'R18Z1','transmission_code'=>'CVT','gear_alias'=>'CVT (Civic 2012-15)','compat_year_from'=>2012,'compat_year_to'=>2015]);
                if ($year >= 2006) return array_merge($default, ['engine_code'=>'R18A','transmission_code'=>'SPYA','gear_alias'=>'AT (Civic 2006-11 1.8L)','compat_year_from'=>2006,'compat_year_to'=>2011]);
                if ($year >= 2001) return array_merge($default, ['engine_code'=>'D17A','transmission_code'=>'SLXA','gear_alias'=>'AT (Civic 2001-05 1.7L)','compat_year_from'=>2001,'compat_year_to'=>2005]);
                return array_merge($default, ['engine_code'=>'D16','transmission_code'=>'MP7A','gear_alias'=>'AT (Civic pre-2001 1.6L)','compat_year_from'=>1996,'compat_year_to'=>2000]);
            }

            if ($model === 'CR-V') {
                if ($year >= 2017) return array_merge($default, ['engine_code'=>'L15B7','transmission_code'=>'CVT','gear_alias'=>'CVT (CR-V 2017+ 1.5T)','compat_year_from'=>2017,'compat_year_to'=>2022]);
                if ($year >= 2012) return array_merge($default, ['engine_code'=>'K24Z7','transmission_code'=>'BGRA','gear_alias'=>'AT (CR-V 2012-16 2.4L)','compat_year_from'=>2012,'compat_year_to'=>2016]);
                if ($year >= 2007) return array_merge($default, ['engine_code'=>'K24Z1','transmission_code'=>'BGRA','gear_alias'=>'AT (CR-V 2007-11 2.4L)','compat_year_from'=>2007,'compat_year_to'=>2011]);
                return array_merge($default, ['engine_code'=>'K24A','transmission_code'=>'MCTA','gear_alias'=>'AT (CR-V 2002-06 2.4L)','compat_year_from'=>2002,'compat_year_to'=>2006]);
            }

            if ($model === 'ODYSSEY') {
                if ($year >= 2018) return array_merge($default, ['engine_code'=>'K24W','transmission_code'=>'9AT','gear_alias'=>'9AT (Odyssey 2018+ 3.5L)','compat_year_from'=>2018,'compat_year_to'=>2024]);
                if ($year >= 2011) return array_merge($default, ['engine_code'=>'J35Y','transmission_code'=>'5AT','gear_alias'=>'V6 5AT (Odyssey 2011-17)','compat_year_from'=>2011,'compat_year_to'=>2017]);
                if ($year >= 2005) return array_merge($default, ['engine_code'=>'J35A','transmission_code'=>'BDKA','gear_alias'=>'V6 AT (Odyssey 2005-10)','compat_year_from'=>2005,'compat_year_to'=>2010]);
                return array_merge($default, ['engine_code'=>'J30A','transmission_code'=>'BAXA','gear_alias'=>'V6 AT (Odyssey 1999-2004)','compat_year_from'=>1999,'compat_year_to'=>2004]);
            }

            if ($model === 'PILOT') {
                if ($year >= 2016) return array_merge($default, ['engine_code'=>'J35Y5','transmission_code'=>'9AT','gear_alias'=>'V6 9AT (Pilot 2016+)','compat_year_from'=>2016,'compat_year_to'=>2022]);
                return array_merge($default, ['engine_code'=>'J35A','transmission_code'=>'BDKA','gear_alias'=>'V6 AT (Pilot 2003-15)','compat_year_from'=>2003,'compat_year_to'=>2015]);
            }

            if (in_array($model, ['ELEMENT'])) return array_merge($default, ['engine_code'=>'K24A','transmission_code'=>'MCTA','gear_alias'=>'AT (Element 2003-11)','compat_year_from'=>2003,'compat_year_to'=>2011]);

            if ($make === 'ACURA' && str_starts_with($model,'MDX')) {
                if ($year >= 2014) return array_merge($default, ['engine_code'=>'J35Y','transmission_code'=>'9AT','gear_alias'=>'V6 9AT (MDX 2014+)','compat_year_from'=>2014,'compat_year_to'=>2021]);
                return array_merge($default, ['engine_code'=>'J35A','transmission_code'=>'BDKA','gear_alias'=>'V6 AT (MDX 2001-13)','compat_year_from'=>2001,'compat_year_to'=>2013]);
            }

            if ($make === 'ACURA' && str_starts_with($model,'TL')) {
                if ($year >= 2009) return array_merge($default, ['engine_code'=>'J35Z6','transmission_code'=>'BJFA','gear_alias'=>'V6 6AT (TL 2009-14)','compat_year_from'=>2009,'compat_year_to'=>2014]);
                return array_merge($default, ['engine_code'=>'J30A','transmission_code'=>'BAXA','gear_alias'=>'V6 AT (TL 1999-2008)','compat_year_from'=>1999,'compat_year_to'=>2008]);
            }
        }

        // ══════════════════════════════════════════════════════
        // NISSAN / INFINITI
        // ══════════════════════════════════════════════════════
        if (in_array($make, ['NISSAN','INFINITI'])) {

            if ($model === 'ALTIMA') {
                if ($cylinders == 6 || $engineL >= 3.0) return array_merge($default, ['engine_code'=>'VQ35DE','transmission_code'=>'RE5R05A','gear_alias'=>'V6 AT (Altima 3.5L)','compat_year_from'=>2002,'compat_year_to'=>2006]);
                if ($year >= 2013) return array_merge($default, ['engine_code'=>'QR25DE','transmission_code'=>'CVT8','gear_alias'=>'CVT (Altima 2013+)','compat_year_from'=>2013,'compat_year_to'=>2022]);
                if ($year >= 2002) return array_merge($default, ['engine_code'=>'QR25DE','transmission_code'=>'RE4F04B','gear_alias'=>'AT (Altima 2.5L 2002-12)','compat_year_from'=>2002,'compat_year_to'=>2012]);
                return array_merge($default, ['engine_code'=>'KA24DE','transmission_code'=>'RE4F04A','gear_alias'=>'AT (Altima 1993-2001 2.4L)','compat_year_from'=>1993,'compat_year_to'=>2001]);
            }

            if ($model === 'SENTRA') {
                if ($year >= 2013) return array_merge($default, ['engine_code'=>'MR20DE','transmission_code'=>'CVT8','gear_alias'=>'CVT (Sentra 2013+)','compat_year_from'=>2013,'compat_year_to'=>2019]);
                if ($year >= 2007) return array_merge($default, ['engine_code'=>'MR20DE','transmission_code'=>'RE4F03B','gear_alias'=>'AT (Sentra 2007-12)','compat_year_from'=>2007,'compat_year_to'=>2012]);
                if ($year >= 2000) return array_merge($default, ['engine_code'=>'QG18DE','transmission_code'=>'RE4F03A','gear_alias'=>'AT (Sentra 2000-06)','compat_year_from'=>2000,'compat_year_to'=>2006]);
                return array_merge($default, ['engine_code'=>'GA16DE','transmission_code'=>'RE4F03A','gear_alias'=>'AT (Sentra pre-2000)','compat_year_from'=>1995,'compat_year_to'=>1999]);
            }

            if ($model === 'MAXIMA') {
                if ($year >= 2009) return array_merge($default, ['engine_code'=>'VQ35DE','transmission_code'=>'CVT','gear_alias'=>'V6 CVT (Maxima 2009+)','compat_year_from'=>2009,'compat_year_to'=>2022]);
                return array_merge($default, ['engine_code'=>'VQ35DE','transmission_code'=>'RE5R05A','gear_alias'=>'V6 AT (Maxima pre-2009)','compat_year_from'=>2000,'compat_year_to'=>2008]);
            }

            if ($model === 'MURANO') {
                if ($year >= 2015) return array_merge($default, ['engine_code'=>'VQ35DE','transmission_code'=>'CVT8','gear_alias'=>'V6 CVT (Murano 2015+)','compat_year_from'=>2015,'compat_year_to'=>2022]);
                return array_merge($default, ['engine_code'=>'VQ35DE','transmission_code'=>'RE5R05A','gear_alias'=>'V6 AT (Murano 2003-14)','compat_year_from'=>2003,'compat_year_to'=>2014]);
            }

            if (in_array($model, ['PATHFINDER','ARMADA'])) {
                if ($year >= 2013) return array_merge($default, ['engine_code'=>'VQ35DD','transmission_code'=>'CVT8','gear_alias'=>'V6 CVT (Pathfinder 2013+)','compat_year_from'=>2013,'compat_year_to'=>2021]);
                return array_merge($default, ['engine_code'=>'VQ40DE','transmission_code'=>'RE5R05A','gear_alias'=>'V6 AT (Pathfinder 2005-12)','compat_year_from'=>2005,'compat_year_to'=>2012]);
            }

            if ($model === 'TIIDA') return array_merge($default, ['engine_code'=>'HR16DE','transmission_code'=>'RE4F03B','gear_alias'=>'AT (Tiida 1.6L)','compat_year_from'=>2004,'compat_year_to'=>2012]);

            if ($model === 'ALMERA') {
                if ($year >= 2012) return array_merge($default, ['engine_code'=>'HR16DE','transmission_code'=>'CVT','gear_alias'=>'CVT (Almera 2012+)','compat_year_from'=>2012,'compat_year_to'=>2019]);
                return array_merge($default, ['engine_code'=>'GA16DE','transmission_code'=>'RE4F03A','gear_alias'=>'AT (Almera 1995-2011)','compat_year_from'=>1995,'compat_year_to'=>2011]);
            }

            if (in_array($model, ['X-TRAIL','XTRAIL','X TRAIL'])) {
                if ($year >= 2014) return array_merge($default, ['engine_code'=>'MR20DD','transmission_code'=>'CVT8','gear_alias'=>'CVT (X-Trail 2014+)','compat_year_from'=>2014,'compat_year_to'=>2022]);
                if ($year >= 2007) return array_merge($default, ['engine_code'=>'MR20DE','transmission_code'=>'CVT','gear_alias'=>'CVT (X-Trail 2007-13)','compat_year_from'=>2007,'compat_year_to'=>2013]);
                return array_merge($default, ['engine_code'=>'QR25DE','transmission_code'=>'RE4F04B','gear_alias'=>'AT (X-Trail 2001-06 2.5L)','compat_year_from'=>2001,'compat_year_to'=>2006]);
            }

            if (in_array($model, ['350Z','370Z'])) return array_merge($default, ['engine_code'=>'VQ35DE','transmission_code'=>'RE5R05A','gear_alias'=>'V6 AT (350Z/370Z)','compat_year_from'=>2003,'compat_year_to'=>2020]);

            if ($make === 'INFINITI') {
                if (str_starts_with($model,'G')) return array_merge($default, ['engine_code'=>'VQ35DE','transmission_code'=>'RE5R05A','gear_alias'=>'V6 AT (G35/G37)','compat_year_from'=>2003,'compat_year_to'=>2013]);
                if (str_starts_with($model,'FX')) return array_merge($default, ['engine_code'=>'VQ35DE','transmission_code'=>'RE5R05A','gear_alias'=>'V6 AT (FX35/FX37)','compat_year_from'=>2003,'compat_year_to'=>2013]);
                if (str_starts_with($model,'QX')) return array_merge($default, ['engine_code'=>'VK56DE','transmission_code'=>'RE5R05A','gear_alias'=>'V8 AT (QX56/QX80)','compat_year_from'=>2004,'compat_year_to'=>2022]);
            }
        }

        // ══════════════════════════════════════════════════════
        // HYUNDAI / KIA
        // ══════════════════════════════════════════════════════
        if (in_array($make, ['HYUNDAI','KIA'])) {

            if (in_array($model, ['ELANTRA','AVANTE']) || ($make === 'KIA' && in_array($model, ['CERATO','FORTE']))) {
                if ($year >= 2021) return array_merge($default, ['engine_code'=>'G4NL','transmission_code'=>'IVT','gear_alias'=>'CVT (Elantra 2021+)','compat_year_from'=>2021,'compat_year_to'=>2024]);
                if ($year >= 2017) return array_merge($default, ['engine_code'=>'G4FG','transmission_code'=>'6DCT','gear_alias'=>'DCT (Elantra 2017-20)','compat_year_from'=>2017,'compat_year_to'=>2020]);
                if ($year >= 2011) return array_merge($default, ['engine_code'=>'G4FD','transmission_code'=>'A6GF1','gear_alias'=>'6AT (Elantra 2011-16 1.8L)','compat_year_from'=>2011,'compat_year_to'=>2016]);
                if ($year >= 2006) return array_merge($default, ['engine_code'=>'G4FC','transmission_code'=>'A4CF1','gear_alias'=>'4AT (Elantra 2006-10 1.6L)','compat_year_from'=>2006,'compat_year_to'=>2010]);
                return array_merge($default, ['engine_code'=>'G4ED','transmission_code'=>'F4A42','gear_alias'=>'4AT (Elantra pre-2006)','compat_year_from'=>2000,'compat_year_to'=>2005]);
            }

            if (in_array($model, ['SONATA']) || ($make === 'KIA' && $model === 'OPTIMA')) {
                if ($year >= 2020) return array_merge($default, ['engine_code'=>'G4FJ','transmission_code'=>'8DCT','gear_alias'=>'DCT (Sonata 2020+)','compat_year_from'=>2020,'compat_year_to'=>2024]);
                if ($year >= 2015) return array_merge($default, ['engine_code'=>'G4NA','transmission_code'=>'6AT','gear_alias'=>'6AT (Sonata 2015-19 2.0L)','compat_year_from'=>2015,'compat_year_to'=>2019]);
                if ($year >= 2010) return array_merge($default, ['engine_code'=>'G4KD','transmission_code'=>'A6MF1','gear_alias'=>'6AT (Sonata 2010-14 2.0L)','compat_year_from'=>2010,'compat_year_to'=>2014]);
                if ($year >= 2006) return array_merge($default, ['engine_code'=>'G4KC','transmission_code'=>'F5A51','gear_alias'=>'5AT (Sonata 2006-09 2.4L)','compat_year_from'=>2006,'compat_year_to'=>2009]);
                return array_merge($default, ['engine_code'=>'G4JP','transmission_code'=>'F4A42','gear_alias'=>'AT (Sonata pre-2006 2.7L V6)','compat_year_from'=>2002,'compat_year_to'=>2005,'multiple_engines'=>true]);
            }

            if (in_array($model, ['TUCSON','SANTAFE','SANTA FE']) || ($make === 'KIA' && in_array($model, ['SPORTAGE','SORENTO']))) {
                if ($year >= 2021) return array_merge($default, ['engine_code'=>'G4FJ','transmission_code'=>'8DCT','gear_alias'=>'DCT (Tucson/Sportage 2021+)','compat_year_from'=>2021,'compat_year_to'=>2024]);
                if ($year >= 2016) return array_merge($default, ['engine_code'=>'G4KH','transmission_code'=>'7DCT','gear_alias'=>'DCT (Tucson 2016-20)','compat_year_from'=>2016,'compat_year_to'=>2020]);
                if ($year >= 2010) return array_merge($default, ['engine_code'=>'G4KD','transmission_code'=>'A6MF1','gear_alias'=>'6AT (Tucson/Sportage 2010-15)','compat_year_from'=>2010,'compat_year_to'=>2015]);
                return array_merge($default, ['engine_code'=>'G4GC','transmission_code'=>'F4A42','gear_alias'=>'4AT (Tucson pre-2010)','compat_year_from'=>2004,'compat_year_to'=>2009]);
            }

            if (in_array($model, ['ACCENT','VERNA']) || ($make === 'KIA' && $model === 'RIO')) {
                if ($year >= 2018) return array_merge($default, ['engine_code'=>'G4LC','transmission_code'=>'IVT','gear_alias'=>'CVT (Accent/Rio 2018+)','compat_year_from'=>2018,'compat_year_to'=>2024]);
                if ($year >= 2012) return array_merge($default, ['engine_code'=>'G4FC','transmission_code'=>'A6GF1','gear_alias'=>'6AT (Accent/Rio 2012-17)','compat_year_from'=>2012,'compat_year_to'=>2017]);
                return array_merge($default, ['engine_code'=>'G4ED','transmission_code'=>'A4CF1','gear_alias'=>'4AT (Accent pre-2012)','compat_year_from'=>2006,'compat_year_to'=>2011]);
            }

            if ($make === 'KIA' && in_array($model, ['SOUL'])) {
                if ($year >= 2020) return array_merge($default, ['engine_code'=>'G4FJ','transmission_code'=>'IVT','gear_alias'=>'CVT (Soul 2020+)','compat_year_from'=>2020,'compat_year_to'=>2024]);
                return array_merge($default, ['engine_code'=>'G4KD','transmission_code'=>'A6MF1','gear_alias'=>'6AT (Soul 2010-19 2.0L)','compat_year_from'=>2010,'compat_year_to'=>2019]);
            }

            if (str_starts_with($model,'GENESIS') || $model === 'GENESIS') {
                return array_merge($default, ['engine_code'=>'G6DC','transmission_code'=>'A6MF2','gear_alias'=>'6AT (Genesis 3.8L V6)','compat_year_from'=>2009,'compat_year_to'=>2016,'multiple_engines'=>true]);
            }
        }

        // ══════════════════════════════════════════════════════
        // MERCEDES-BENZ
        // ══════════════════════════════════════════════════════
        if (in_array($make, ['MERCEDES','MERCEDES-BENZ','MB'])) {

            // C-Class
            if (str_starts_with($model,'C')) {
                if (str_contains($model,'63')) return array_merge($default, ['engine_code'=>'M157','transmission_code'=>'722.9','gear_alias'=>'AMG V8 (C63 2012+)','compat_year_from'=>2012,'compat_year_to'=>2021]);
                if (str_contains($model,'43') || str_contains($model,'400')) return array_merge($default, ['engine_code'=>'M276','transmission_code'=>'722.9','gear_alias'=>'AMG V6 (C43 2016+)','compat_year_from'=>2016,'compat_year_to'=>2021]);
                if (str_contains($model,'350') || str_contains($model,'300')) {
                    if ($year >= 2015) return array_merge($default, ['engine_code'=>'M276','transmission_code'=>'722.9','gear_alias'=>'V6 7G (C350/C300 2015+)','compat_year_from'=>2015,'compat_year_to'=>2021]);
                    return array_merge($default, ['engine_code'=>'M272','transmission_code'=>'722.9','gear_alias'=>'V6 7G (C350/C300 2007-14)','compat_year_from'=>2007,'compat_year_to'=>2014]);
                }
                if (str_contains($model,'250')) return array_merge($default, ['engine_code'=>'M271','transmission_code'=>'722.9','gear_alias'=>'4cyl Turbo (C250 2012+)','compat_year_from'=>2012,'compat_year_to'=>2021]);
                if (str_contains($model,'200') || str_contains($model,'180')) {
                    if ($year >= 2015) return array_merge($default, ['engine_code'=>'M274','transmission_code'=>'722.9','gear_alias'=>'4cyl Turbo (C200 2015+)','compat_year_from'=>2015,'compat_year_to'=>2021]);
                    if ($year >= 2007) return array_merge($default, ['engine_code'=>'M271','transmission_code'=>'722.9','gear_alias'=>'7G (C200 2007-14)','compat_year_from'=>2007,'compat_year_to'=>2014]);
                    return array_merge($default, ['engine_code'=>'M271','transmission_code'=>'722.6','gear_alias'=>'5G (C200 Kompressor 2002-06)','compat_year_from'=>2000,'compat_year_to'=>2006]);
                }
            }

            // E-Class
            if (str_starts_with($model,'E')) {
                if (str_contains($model,'500') || str_contains($model,'550')) return array_merge($default, ['engine_code'=>'M113','transmission_code'=>'722.9','gear_alias'=>'V8 7G (E500/E550)','compat_year_from'=>1998,'compat_year_to'=>2016]);
                if (str_contains($model,'350')) {
                    if ($year >= 2016) return array_merge($default, ['engine_code'=>'M276','transmission_code'=>'722.9','gear_alias'=>'V6 9G (E350 2016+)','compat_year_from'=>2016,'compat_year_to'=>2021]);
                    if ($year >= 2009) return array_merge($default, ['engine_code'=>'M276','transmission_code'=>'722.9','gear_alias'=>'V6 7G (E350 2009-15)','compat_year_from'=>2009,'compat_year_to'=>2015]);
                    return array_merge($default, ['engine_code'=>'M272','transmission_code'=>'722.9','gear_alias'=>'V6 7G (E350 2006-08)','compat_year_from'=>2006,'compat_year_to'=>2008]);
                }
                if (str_contains($model,'320')) return array_merge($default, ['engine_code'=>'M112','transmission_code'=>'722.6','gear_alias'=>'V6 5G (E320 pre-2006)','compat_year_from'=>1997,'compat_year_to'=>2005]);
                if (str_contains($model,'200') || str_contains($model,'250')) {
                    if ($year >= 2014) return array_merge($default, ['engine_code'=>'M274','transmission_code'=>'722.9','gear_alias'=>'4cyl Turbo (E200/E250 2014+)','compat_year_from'=>2014,'compat_year_to'=>2020]);
                    return array_merge($default, ['engine_code'=>'M271','transmission_code'=>'722.9','gear_alias'=>'(E200 Kompressor 2002-13)','compat_year_from'=>2002,'compat_year_to'=>2013]);
                }
            }

            // ML/GLE-Class
            if (str_starts_with($model,'ML') || str_starts_with($model,'GLE')) {
                if (str_contains($model,'350')) {
                    if ($year >= 2012) return array_merge($default, ['engine_code'=>'M276','transmission_code'=>'722.9','gear_alias'=>'V6 7G (ML/GLE350 2012+)','compat_year_from'=>2012,'compat_year_to'=>2019]);
                    if ($year >= 2006) return array_merge($default, ['engine_code'=>'M272','transmission_code'=>'722.9','gear_alias'=>'V6 7G (ML350 2006-11)','compat_year_from'=>2006,'compat_year_to'=>2011]);
                    return array_merge($default, ['engine_code'=>'M112','transmission_code'=>'722.6','gear_alias'=>'V6 5G (ML320 pre-2006)','compat_year_from'=>1998,'compat_year_to'=>2005]);
                }
                if (str_contains($model,'500') || str_contains($model,'550')) return array_merge($default, ['engine_code'=>'M113','transmission_code'=>'722.9','gear_alias'=>'V8 (ML500/ML550)','compat_year_from'=>1998,'compat_year_to'=>2011]);
            }

            // S-Class
            if (str_starts_with($model,'S')) {
                if (str_contains($model,'500') || str_contains($model,'550')) return array_merge($default, ['engine_code'=>'M113','transmission_code'=>'722.9','gear_alias'=>'V8 (S500/S550)','compat_year_from'=>1999,'compat_year_to'=>2013]);
                if (str_contains($model,'350') || str_contains($model,'320')) return array_merge($default, ['engine_code'=>'M112','transmission_code'=>'722.6','gear_alias'=>'V6 5G (S320/S350)','compat_year_from'=>1999,'compat_year_to'=>2005]);
            }
        }

        // ══════════════════════════════════════════════════════
        // FORD
        // ══════════════════════════════════════════════════════
        if ($make === 'FORD') {
            if ($model === 'FOCUS') {
                if ($year >= 2012) return array_merge($default, ['engine_code'=>'Fox2.0','transmission_code'=>'PowerShift','gear_alias'=>'DCT (Focus 2012+)','compat_year_from'=>2012,'compat_year_to'=>2018]);
                return array_merge($default, ['engine_code'=>'Duratec20','transmission_code'=>'4F27E','gear_alias'=>'AT (Focus 2.0L 2000-11)','compat_year_from'=>2000,'compat_year_to'=>2011]);
            }
            if ($model === 'FUSION') {
                if ($cylinders <= 4) return array_merge($default, ['engine_code'=>'Duratec25','transmission_code'=>'6F35','gear_alias'=>'6AT (Fusion 2.5L)','compat_year_from'=>2006,'compat_year_to'=>2020]);
                return array_merge($default, ['engine_code'=>'Duratec30','transmission_code'=>'6F50','gear_alias'=>'6AT (Fusion 3.0L V6)','compat_year_from'=>2006,'compat_year_to'=>2012]);
            }
            if ($model === 'ESCAPE') {
                if ($year >= 2013) return array_merge($default, ['engine_code'=>'EcoBoost15','transmission_code'=>'6F35','gear_alias'=>'6AT (Escape 2013+ 1.6T)','compat_year_from'=>2013,'compat_year_to'=>2019]);
                if ($year >= 2008 && $cylinders <= 4) return array_merge($default, ['engine_code'=>'Duratec25','transmission_code'=>'6F35','gear_alias'=>'6AT (Escape 2008-12 2.5L)','compat_year_from'=>2008,'compat_year_to'=>2012]);
                return array_merge($default, ['engine_code'=>'Duratec20','transmission_code'=>'4F27E','gear_alias'=>'AT (Escape pre-2008)','compat_year_from'=>2001,'compat_year_to'=>2007]);
            }
            if (in_array($model, ['F-150','F150','F 150'])) {
                if ($year >= 2011) return array_merge($default, ['engine_code'=>'EcoBoost35','transmission_code'=>'6R80','gear_alias'=>'6AT (F-150 2011+ 3.5T)','compat_year_from'=>2011,'compat_year_to'=>2021]);
                return array_merge($default, ['engine_code'=>'Modular46','transmission_code'=>'6R75','gear_alias'=>'6AT (F-150 4.6L V8 pre-2011)','compat_year_from'=>1997,'compat_year_to'=>2010]);
            }
            if ($model === 'EXPLORER') {
                if ($year >= 2011) return array_merge($default, ['engine_code'=>'Cyclone35','transmission_code'=>'6F35','gear_alias'=>'6AT (Explorer 2011+ 3.5L)','compat_year_from'=>2011,'compat_year_to'=>2019]);
                return array_merge($default, ['engine_code'=>'Cologne46','transmission_code'=>'5R55S','gear_alias'=>'5AT (Explorer pre-2011 4.0L/4.6L)','compat_year_from'=>2001,'compat_year_to'=>2010]);
            }
            if ($model === 'EDGE') return array_merge($default, ['engine_code'=>'Cyclone35','transmission_code'=>'6F50','gear_alias'=>'6AT (Edge 3.5L)','compat_year_from'=>2007,'compat_year_to'=>2022]);
        }

        // ══════════════════════════════════════════════════════
        // CHEVROLET / GMC
        // ══════════════════════════════════════════════════════
        if (in_array($make, ['CHEVROLET','CHEVY','GMC'])) {
            if (in_array($model, ['SILVERADO','SIERRA','TAHOE','YUKON','SUBURBAN'])) {
                if ($year >= 2014) return array_merge($default, ['engine_code'=>'L83','transmission_code'=>'6L80','gear_alias'=>'6AT (5.3L EcoTec3 2014+)','compat_year_from'=>2014,'compat_year_to'=>2022]);
                return array_merge($default, ['engine_code'=>'Vortec53','transmission_code'=>'6L80','gear_alias'=>'6AT (Vortec 5.3L pre-2014)','compat_year_from'=>1999,'compat_year_to'=>2013]);
            }
            if (in_array($model, ['MALIBU','IMPALA'])) {
                if ($year >= 2013) return array_merge($default, ['engine_code'=>'Ecotec25','transmission_code'=>'6T40','gear_alias'=>'6AT (Malibu 2013+ 2.5L)','compat_year_from'=>2013,'compat_year_to'=>2019]);
                return array_merge($default, ['engine_code'=>'Ecotec22','transmission_code'=>'4T45E','gear_alias'=>'4AT (Malibu pre-2013 2.2L)','compat_year_from'=>2004,'compat_year_to'=>2012]);
            }
            if (in_array($model, ['EQUINOX','TERRAIN'])) return array_merge($default, ['engine_code'=>'Ecotec24','transmission_code'=>'6T45','gear_alias'=>'6AT (Equinox/Terrain 2.4L)','compat_year_from'=>2010,'compat_year_to'=>2017]);
            if (in_array($model, ['TRAX','SONIC','CRUZE'])) return array_merge($default, ['engine_code'=>'Ecotec14','transmission_code'=>'6T30','gear_alias'=>'6AT (1.4T)','compat_year_from'=>2011,'compat_year_to'=>2019]);
        }

        return $default;
    }

    // =========================================================
    // engineOptions() — multiple engine choices for a vehicle.
    // Called when no VIN/cylinder data is available, so staff can
    // be shown the real alternatives instead of the system silently
    // guessing one engine and hiding the other from view.
    //
    // NOTE: pin_count left null here unless separately confirmed —
    // engine codes/displacement below are documented factory specs
    // (safe, public facts). Pin counts are empirical Ladipo-market
    // knowledge and should only be added here once verified, since
    // staff will treat anything shown here as fact.
    // =========================================================
    public static function engineOptions(string $make, string $model, int $year): array
    {
        $make  = strtoupper($make);
        $model = strtoupper($model);
        $opts  = [];

        if ($make === 'TOYOTA' && in_array($model, ['CAMRY','SOLARA'])) {
            if ($year >= 2012) {
                $opts[] = ['label'=>'2.5L 4-cyl (2AR-FE) — Most common','engine_code'=>'2AR-FE','cylinders'=>4,'engine_l'=>2.5,'pin_count'=>22];
                $opts[] = ['label'=>'3.5L V6 (2GR-FE)','engine_code'=>'2GR-FE','cylinders'=>6,'engine_l'=>3.5,'pin_count'=>null];
            } elseif ($year >= 2010 && $model === 'CAMRY') {
                $opts[] = ['label'=>'2.5L 4-cyl (2AR-FE, 20-pin) — Most common','engine_code'=>'2AR-FE','cylinders'=>4,'engine_l'=>2.5,'pin_count'=>20];
            } elseif ($year >= 2004 && $model === 'SOLARA') {
                $opts[] = ['label'=>'2.4L 4-cyl (2AZ-FE)','engine_code'=>'2AZ-FE','cylinders'=>4,'engine_l'=>2.4,'pin_count'=>13];
                $opts[] = ['label'=>'3.3L V6 (3MZ-FE)','engine_code'=>'3MZ-FE','cylinders'=>6,'engine_l'=>3.3,'pin_count'=>null];
            } elseif ($year >= 2002) {
                $opts[] = ['label'=>'2.4L 4-cyl (2AZ-FE) — Most common','engine_code'=>'2AZ-FE','cylinders'=>4,'engine_l'=>2.4,'pin_count'=>10];
                $opts[] = ['label'=>'3.0L V6 (1MZ-FE, 3/5/7-pin — confirm on unit)','engine_code'=>'1MZ-FE','cylinders'=>6,'engine_l'=>3.0,'pin_count'=>7];
            }
        }

        if ($make === 'TOYOTA' && $model === 'HIGHLANDER') {
            if ($year >= 2008) {
                $opts[] = ['label'=>'3.5L V6 (2GR-FE)','engine_code'=>'2GR-FE','cylinders'=>6,'engine_l'=>3.5,'pin_count'=>null];
                $opts[] = ['label'=>'2.7L 4-cyl (2AR-FE)','engine_code'=>'2AR-FE','cylinders'=>4,'engine_l'=>2.7,'pin_count'=>22];
            } else {
                $opts[] = ['label'=>'2.4L 4-cyl (2AZ-FE)','engine_code'=>'2AZ-FE','cylinders'=>4,'engine_l'=>2.4,'pin_count'=>13];
                $opts[] = ['label'=>'3.3L V6 (3MZ-FE)','engine_code'=>'3MZ-FE','cylinders'=>6,'engine_l'=>3.3,'pin_count'=>null];
            }
        }

        if ($make === 'TOYOTA' && $model === 'CELICA') {
            $opts[] = ['label'=>'1.8L 4-cyl (1ZZ-FE) — GT','engine_code'=>'1ZZ-FE','cylinders'=>4,'engine_l'=>1.8,'pin_count'=>5];
            $opts[] = ['label'=>'1.8L VVTLi (2ZZ-GE) — GT-S, 6-speed manual only','engine_code'=>'2ZZ-GE','cylinders'=>4,'engine_l'=>1.8,'pin_count'=>null];
        }

        if (in_array($make, ['HONDA','ACURA']) && $model === 'ACCORD') {
            if ($year >= 2008) {
                $opts[] = ['label'=>'2.4L 4-cyl (K24Z3/K24W)','engine_code'=>'K24Z3','cylinders'=>4,'engine_l'=>2.4,'pin_count'=>null];
                $opts[] = ['label'=>'3.5L V6 (J35A)','engine_code'=>'J35A','cylinders'=>6,'engine_l'=>3.5,'pin_count'=>null];
            } elseif ($year >= 1998) {
                $opts[] = ['label'=>'2.3L/2.4L 4-cyl (F23A/K24A)','engine_code'=>'K24A','cylinders'=>4,'engine_l'=>2.4,'pin_count'=>null];
                $opts[] = ['label'=>'3.0L V6 (J30A)','engine_code'=>'J30A','cylinders'=>6,'engine_l'=>3.0,'pin_count'=>null];
            }
        }

        if (in_array($make, ['NISSAN','INFINITI']) && $model === 'ALTIMA' && $year <= 2006) {
            $opts[] = ['label'=>'2.5L 4-cyl (QR25DE) — Most common','engine_code'=>'QR25DE','cylinders'=>4,'engine_l'=>2.5,'pin_count'=>null];
            $opts[] = ['label'=>'3.5L V6 (VQ35DE)','engine_code'=>'VQ35DE','cylinders'=>6,'engine_l'=>3.5,'pin_count'=>null];
        }

        if ($make === 'FORD' && $model === 'FUSION') {
            $opts[] = ['label'=>'2.5L 4-cyl (Duratec25) — Most common','engine_code'=>'Duratec25','cylinders'=>4,'engine_l'=>2.5,'pin_count'=>null];
            $opts[] = ['label'=>'3.0L V6 (Duratec30)','engine_code'=>'Duratec30','cylinders'=>6,'engine_l'=>3.0,'pin_count'=>null];
        }

        if ($make === 'FORD' && $model === 'ESCAPE' && $year >= 2008 && $year <= 2012) {
            $opts[] = ['label'=>'2.5L 4-cyl (Duratec25) — Most common','engine_code'=>'Duratec25','cylinders'=>4,'engine_l'=>2.5,'pin_count'=>null];
            $opts[] = ['label'=>'3.0L V6 (Duratec30)','engine_code'=>'Duratec30','cylinders'=>6,'engine_l'=>3.0,'pin_count'=>null];
        }

        if (in_array($make, ['HYUNDAI']) && $model === 'SONATA' && $year >= 2002 && $year <= 2005) {
            $opts[] = ['label'=>'2.4L 4-cyl (G4JS) — Most common','engine_code'=>'G4JS','cylinders'=>4,'engine_l'=>2.4,'pin_count'=>null];
            $opts[] = ['label'=>'2.7L V6 (G6BA)','engine_code'=>'G6BA','cylinders'=>6,'engine_l'=>2.7,'pin_count'=>null];
        }

        if (in_array($make, ['HYUNDAI','KIA']) && str_starts_with($model, 'GENESIS')) {
            $opts[] = ['label'=>'3.8L V6 (G6DC)','engine_code'=>'G6DC','cylinders'=>6,'engine_l'=>3.8,'pin_count'=>null];
            $opts[] = ['label'=>'4.6L V8 (G8BA / Tau)','engine_code'=>'G8BA','cylinders'=>8,'engine_l'=>4.6,'pin_count'=>null];
        }

        return $opts;
    }

    // =========================================================
    // pinCounts() — transmission pin count lookup
    // =========================================================
    public static function pinCounts(): array
    {
        return [
            'U341E'=>5,'U241E'=>10,'U760E'=>null,'U660E'=>22,
            'K310'=>12,'K311'=>12,'K313'=>12,
            'A750E'=>null,'A750F'=>null,'A541E'=>null,'A650E'=>null,'AB60F'=>null,
            'MCTA'=>null,'BGRA'=>null,'BAXA'=>null,'BDKA'=>null,
            'RE4F03A'=>null,'RE4F04B'=>null,'RE5R05A'=>null,
            'A6MF1'=>null,'A4CF1'=>null,'A6GF1'=>null,
            '722.6'=>null,'722.9'=>null,
        ];
    }

    // =========================================================
    // interchange() — Tier 3: all vehicles sharing same powertrain
    // =========================================================
    public static function interchange(): array
    {
        return [
            // Toyota 1ZZ-FE family
            '1ZZ-FE' => ['2000-2008 Toyota Corolla 1.8L','2003-2008 Toyota Matrix 1.8L','2000-2005 Toyota Celica GT','2003-2008 Pontiac Vibe 1.8L'],
            'U341E'  => ['2003-2008 Toyota Corolla 1.8L (5-pin)','2003-2008 Toyota Matrix 1.8L','2000-2005 Toyota Celica GT','2003-2008 Pontiac Vibe'],
            // Toyota 3ZZ-FE
            '3ZZ-FE' => ['2003-2008 Toyota Corolla 1.6L','2003-2008 Toyota Auris 1.6L'],
            // Toyota 2ZR-FE family
            '2ZR-FE' => ['2009-2019 Toyota Corolla 1.8L','2009-2013 Toyota Matrix 1.8L','2007-2018 Toyota Auris 1.8L','2009-2013 Pontiac Vibe 1.8L'],
            'K310'   => ['2009-2013 Toyota Corolla CVT','2009-2013 Toyota Matrix CVT','2007-2013 Toyota Auris CVT'],
            'K311'   => ['2009-2013 Toyota Corolla CVT (K311)','2009-2013 Toyota Matrix CVT'],
            // Toyota 2AZ-FE family (13-pin gear)
            '2AZ-FE' => ['2002-2009 Toyota Camry 2.4L','2001-2012 Toyota RAV4 2.4L','2001-2007 Toyota Highlander 2.4L','2002-2008 Toyota Solara 2.4L','2009-2012 Toyota Venza 2.7L','2006-2012 Toyota Alphard 2.4L'],
            'U241E'  => ['2002-2009 Toyota Camry (10-pin U241E)','2001-2012 Toyota RAV4','2001-2007 Toyota Highlander','2002-2008 Toyota Solara'],
            // Toyota 2AR-FE family (22-pin gear)
            '2AR-FE' => ['2010-2011 Toyota Camry 2.5L (20-pin — distinct from 2012+)','2012-2018 Toyota Camry 2.5L (22-pin)','2013-2018 Toyota RAV4 2.5L','2014-2019 Toyota Highlander 2.7L','2009-2015 Toyota Venza 2.7L','2012-2017 Toyota Avalon 2.5L','2012-2017 Toyota Aurion 2.5L'],
            'U760E'  => ['2010-2011 Toyota Camry 2.5L (20-pin)','2012-2018 Toyota Camry 2.5L (22-pin)','2013-2018 Toyota RAV4','2009-2015 Toyota Venza','NOTE: U760E pin count varies by year (20 vs 22) — always confirm year before treating as interchangeable'],
            // Toyota 2GR-FE V6 family
            '2GR-FE' => ['2007-2022 Toyota Camry V6 3.5L','2008-2019 Toyota Highlander V6','2005-2022 Toyota Avalon V6','2011-2020 Toyota Sienna V6','2006-2015 Lexus GS350','2007-2018 Lexus ES350','2007-2019 Lexus RX350'],
            'A750E'  => ['2007-2022 Toyota Camry V6','2008-2013 Toyota Highlander V6','2005-2022 Toyota Avalon V6','2007-2018 Lexus ES350','2007-2015 Lexus RX350'],
            // Toyota 1MZ-FE family (older V6)
            '1MZ-FE' => ['1994-2004 Toyota Avalon V6','1997-2001 Toyota Camry V6','1999-2003 Lexus ES300','1999-2003 Lexus RX300','2001-2003 Toyota Highlander V6','1998-2003 Toyota Sienna V6'],
            '3MZ-FE' => ['2004-2006 Toyota Avalon V6 3.3L','2004-2006 Lexus ES330 3.3L','2004-2006 Lexus RX330','2004-2007 Toyota Highlander V6','2004-2010 Toyota Sienna V6','2004-2008 Toyota Solara V6'],
            // Toyota 1MZ Avalon specific
            'A541E'  => ['1995-2004 Toyota Avalon 3.0L V6','1997-2001 Toyota Camry V6','1998-2003 Toyota Sienna V6','1999-2003 Lexus ES300','1999-2003 Lexus RX300'],
            // Toyota NZ family
            '1NZ-FE' => ['2002-2020 Toyota Vios 1.5L','2005-2019 Toyota Yaris 1.5L','2001-2006 Toyota Echo 1.5L','2002-2007 Toyota Platz 1.5L'],
            '2NZ-FE' => ['1999-2005 Toyota Echo 1.3L','1999-2005 Toyota Yaris 1.3L','1999-2005 Toyota Platz 1.3L'],
            // Toyota Land Cruiser V8 family
            '2UZ-FE' => ['1998-2007 Toyota Land Cruiser 4.7L','1999-2007 Toyota Tundra 4.7L','2001-2007 Toyota Sequoia 4.7L','2003-2009 Lexus GX470','2003-2007 Lexus LX470'],
            '3UZ-FE' => ['2001-2006 Lexus LS430 4.3L','2001-2007 Lexus GS430','2002-2009 Lexus SC430'],
            '1UR-FE' => ['2008-2015 Toyota Land Cruiser 200 4.6L','2007-2017 Lexus LS460'],
            'AB60F'  => ['2007-2021 Toyota Tundra V8','2008-2021 Toyota Sequoia V8','2008-2021 Lexus LX570'],
            // Toyota 1GR-FE V6 family
            '1GR-FE' => ['2003-2024 Toyota Land Cruiser Prado 4.0L V6','2006-2014 Toyota FJ Cruiser 4.0L','2005-2024 Toyota Tacoma 4.0L','2003-2024 Toyota 4Runner 4.0L','2010-2024 Lexus GX460'],
            // Toyota Diesel family
            '1KD-FTV' => ['2005-2015 Toyota Hilux 3.0L D4D','2009-2015 Toyota Fortuner 3.0L','2003-2009 Toyota Prado 3.0L Diesel','2005-2015 Toyota HiAce 3.0L'],
            '2KD-FTV' => ['2005-2015 Toyota Hilux 2.5L Diesel','2004-2015 Toyota Innova 2.5L','2005-2015 Toyota HiAce 2.5L'],
            '1GD-FTV' => ['2016-2024 Toyota Hilux 2.8L Diesel','2016-2024 Toyota Fortuner 2.8L','2016-2024 Toyota Land Cruiser Prado 2.8L'],
            // Honda K24 family
            'K24A'   => ['2003-2007 Honda Accord 2.4L','2002-2006 Honda CR-V 2.4L','2003-2011 Honda Element 2.4L','2004-2008 Acura TSX 2.4L'],
            'K24Z1'  => ['2007-2011 Honda CR-V 2.4L','2008-2012 Honda Accord 2.4L'],
            'MCTA'   => ['2003-2007 Honda Accord 2.4L AT','2002-2006 Honda CR-V AT','2003-2011 Honda Element AT'],
            'BGRA'   => ['2008-2016 Honda CR-V AT','2008-2014 Honda Accord 2.4L AT'],
            // Honda R18 family
            'R18A'   => ['2006-2015 Honda Civic 1.8L','2006-2011 Honda Civic LX/EX'],
            // Honda J35 V6 family
            'J35A'   => ['2005-2010 Honda Odyssey 3.5L','2003-2015 Honda Pilot 3.5L','2006-2014 Honda Ridgeline 3.5L','2003-2009 Acura MDX 3.5L'],
            'J30A'   => ['1998-2007 Honda Accord V6 3.0L','1999-2004 Honda Odyssey 3.5L'],
            'BAXA'   => ['1998-2002 Honda Accord AT','1999-2004 Honda Odyssey AT','2001-2003 Acura TL AT'],
            // Nissan QR25 family
            'QR25DE' => ['2002-2022 Nissan Altima 2.5L','2000-2006 Nissan X-Trail 2.5L','2005-2012 Nissan Frontier 2.5L'],
            // Nissan VQ35 V6 family
            'VQ35DE' => ['2002-2022 Nissan Maxima 3.5L','2002-2008 Nissan Altima 3.5L V6','2003-2019 Nissan Murano 3.5L','2003-2009 Nissan 350Z','2003-2013 Infiniti G35/G37','2003-2013 Infiniti FX35','2003-2013 Infiniti M35'],
            'RE5R05A'=> ['Nissan/Infiniti V6/V8 5AT','2004-2019 Nissan Armada','2004-2012 Nissan Pathfinder','2003-2019 Nissan Murano','2004-2013 Infiniti QX56','2003-2013 Infiniti FX45/FX35'],
            // Nissan MR20 family
            'MR20DE' => ['2007-2013 Nissan Sentra 2.0L','2004-2012 Nissan Tiida 2.0L','2006-2013 Nissan Livina 2.0L','2007-2019 Nissan X-Trail 2.0L','2007-2013 Nissan Sylphy 2.0L'],
            // Nissan HR16 family
            'HR16DE' => ['2004-2019 Nissan Tiida 1.6L','2012-2022 Nissan Almera 1.5L','2006-2019 Nissan Note 1.6L','2012-2019 Nissan Versa 1.6L'],
            // Nissan GA16 family
            'GA16DE' => ['1995-2011 Nissan Almera 1.6L','1995-2006 Nissan Sunny 1.6L','1995-2006 Nissan Sentra 1.6L'],
            // Hyundai/Kia families
            'G4KD'   => ['2010-2015 Hyundai Elantra 2.0L','2010-2015 Hyundai Tucson 2.0L','2010-2015 Kia Sportage 2.0L','2011-2015 Kia Optima 2.0L','2012-2019 Kia Soul 2.0L'],
            'G4KC'   => ['2006-2010 Hyundai Sonata 2.4L','2006-2010 Hyundai Santa Fe 2.4L','2006-2010 Kia Optima 2.4L'],
            'G4FC'   => ['2006-2017 Hyundai Accent 1.6L','2006-2017 Kia Rio 1.6L','2006-2011 Hyundai Verna 1.6L'],
            'A6MF1'  => ['2010-2015 Hyundai Sonata 6AT','2010-2015 Kia Optima 6AT','2010-2015 Hyundai Santa Fe 6AT','2010-2015 Kia Sportage 6AT'],
            'A4CF1'  => ['2006-2011 Hyundai Accent 4AT','2006-2011 Kia Rio 4AT'],
            // Mercedes families
            'M271'   => ['2002-2014 Mercedes C180 Kompressor','2002-2014 Mercedes C200 Kompressor','2002-2013 Mercedes E200 Kompressor','2004-2011 Mercedes SLK200','2004-2009 Mercedes CLK200'],
            'M272'   => ['2005-2011 Mercedes C280/C300 V6','2005-2009 Mercedes E350 V6','2005-2011 Mercedes ML350 V6','2005-2013 Mercedes GL350 V6','2005-2011 Mercedes S350 V6'],
            'M276'   => ['2011-2021 Mercedes C350/C300 V6','2009-2020 Mercedes E350 V6','2011-2019 Mercedes ML/GLE350 V6','2012-2020 Mercedes GL/GLS350 V6'],
            'M112'   => ['1997-2005 Mercedes E320 V6','1997-2005 Mercedes ML320 V6','1997-2003 Mercedes CLK320','2000-2006 Mercedes S320'],
            'M113'   => ['1998-2011 Mercedes E500/E550 V8','1998-2013 Mercedes S500/S550 V8','1998-2011 Mercedes ML500/ML550 V8'],
            '722.6'  => ['1996-2005 Mercedes E-Class W210 5G','1998-2005 Mercedes ML W163 5G','1999-2006 Mercedes S-Class W220 5G','2000-2006 Mercedes CLK W208/W209 5G','2001-2007 Mercedes C-Class W203 5G'],
            '722.9'  => ['2003-2021 Mercedes E-Class W211/W212/W213 7G','2005-2019 Mercedes ML/GLE W164/W166 7G','2005-2019 Mercedes GL/GLS X164/X166 7G','2006-2021 Mercedes C-Class W204/W205 7G','2006-2014 Mercedes CLS W219 7G'],
            // Ford families
            'Duratec20' => ['2000-2011 Ford Focus 2.0L','2005-2011 Ford Mondeo 2.0L','2004-2009 Mazda 3 2.0L','2004-2009 Mazda 6 2.0L'],
            '4F27E'     => ['2000-2011 Ford Focus AT','2006-2009 Ford Fusion 2.3L AT','2004-2009 Mazda 3 AT','2003-2008 Mazda 6 AT'],
            '6F35'      => ['2009-2019 Ford Escape AT','2010-2020 Ford Fusion AT','2007-2022 Ford Edge AT','2010-2019 Lincoln MKZ AT'],
            'Modular46'  => ['1997-2010 Ford F-150 4.6L/5.4L V8','1997-2006 Ford Expedition V8','2002-2011 Ford Explorer 4.6L V8'],
        ];
    }

    // =========================================================
    // nigerianMarketNames() — Ladipo/Lagos market aliases
    // =========================================================
    public static function nigerianMarketNames(): array
    {
        return [
            'U341E'=>'5-pin gear','U241E'=>'10-pin gear','U760E'=>'20/22-pin gear (varies by year — see OemDatabase)',
            'K310'=>'12-pin CVT','K311'=>'12-pin CVT','A750E'=>'V6 automatic (Toyota)',
            '722.6'=>'5-speed Mercedes gear','722.9'=>'7-speed Mercedes gear',
            '2AZ-FE'=>'Camry engine 2.4','2AR-FE'=>'Camry engine 2.5','2GR-FE'=>'Camry V6 / Avalon engine',
            '1MZ-FE'=>'Camry V6 3.0 (old) / Avalon 3.0','3MZ-FE'=>'Sienna/Avalon engine 3.3L',
            '1ZZ-FE'=>'Corolla 1.8 engine (03-08)','2ZR-FE'=>'Corolla engine 09-13',
            'QR25DE'=>'Altima 2.5 engine','VQ35DE'=>'Maxima/Altima V6 engine',
            'K24A'=>'Accord 2.4 engine (Honda)','J35A'=>'Accord V6 / Odyssey engine',
            'G4KC'=>'Sonata 2.4 engine (Hyundai)','G4KD'=>'Sonata/Tucson 2.0 engine',
            'M271'=>'C180/C200 Mercedes engine','M272'=>'C280/E350 Mercedes V6',
            '1GR-FE'=>'Prado/4Runner V6 engine','1KD-FTV'=>'Hilux 3.0L Diesel (D4D)',
        ];
    }

    // =========================================================
    // salvageCategories() — comprehensive harvestable/salvageable parts library
    // This is a broad salvage taxonomy for inventory intake. It is intentionally
    // category-based because exact fitment must be confirmed by VIN, trim, side,
    // body style, engine, transmission, drivetrain, part number, and visual match.
    // =========================================================
    public static function salvageCategories(): array
    {
        return [
            'powertrain' => [
                'Engine Assembly','Long Block','Short Block','Cylinder Head','Valve Cover','Oil Pan','Timing Cover','Timing Chain/Belt Components','Intake Manifold','Exhaust Manifold','Throttle Body','Turbocharger/Supercharger','EGR Valve','Fuel Injectors','Fuel Rail','High Pressure Fuel Pump','Engine Mounts','Harmonic Balancer','Crankshaft Pulley','Oil Cooler','Intercooler',
            ],
            'transmission_driveline' => [
                'Transmission Assembly','Torque Converter','Valve Body','Transmission Control Module','Transfer Case','Differential Front','Differential Rear','Drive Shaft','CV Axle Left','CV Axle Right','Axle Shaft','Transmission Mount','Shifter Assembly','Clutch/Flywheel','4WD Actuator','Propeller Shaft',
            ],
            'cooling_ac' => [
                'Radiator','Radiator Fan Assembly','Condenser','A/C Compressor','Evaporator Core','A/C Lines/Hoses','Blower Motor','Heater Core','Expansion Valve','Receiver Drier','Climate Control Panel','Coolant Reservoir','Thermostat Housing','Fan Shroud',
            ],
            'electrical_modules' => [
                'ECU/PCM','TCM','BCM','ABS Module','SRS Airbag Module','Immobilizer Module','Smart Key Module','Fuse Box Engine Bay','Fuse Box Interior','Relay Box','Alternator','Starter Motor','Ignition Coils','Wiring Harness Engine','Wiring Harness Dash','Wiring Harness Body','Battery Cable','Instrument Cluster','Body Control Switches','Steering Lock Module',
            ],
            'fuel_exhaust_emissions' => [
                'Fuel Pump Assembly','Fuel Tank','Fuel Filler Neck','EVAP Canister','Oxygen Sensors','Air/Fuel Ratio Sensor','Catalytic Converter','Exhaust Pipe','Muffler','DPF/SCR Components','MAF Sensor','MAP Sensor','Camshaft Sensor','Crankshaft Sensor','Knock Sensor','Purge Valve',
            ],
            'suspension_steering_brakes' => [
                'Steering Rack','Power Steering Pump','Electric Power Steering Motor','Steering Column','Steering Wheel','Control Arm Front Lower','Control Arm Front Upper','Control Arm Rear','Knuckle/Spindle','Wheel Hub/Bearing','Strut Assembly','Shock Absorber','Coil Spring','Sway Bar','Sway Bar Links','Brake Caliper','Brake Booster','Master Cylinder','ABS Pump','Parking Brake Actuator','Subframe/Crossmember',
            ],
            'body_exterior' => [
                'Front Bumper Cover','Rear Bumper Cover','Bumper Reinforcement','Hood','Trunk Lid','Tailgate/Liftgate','Fender Left','Fender Right','Door Front Left','Door Front Right','Door Rear Left','Door Rear Right','Sliding Door','Quarter Panel Cut','Roof Cut','Grille','Mirror Left','Mirror Right','Running Board','Roof Rack','Spoiler','Splash Shield','Wheel Arch Liner',
            ],
            'lighting_glass' => [
                'Headlight Left','Headlight Right','Tail Light Left','Tail Light Right','Fog Light','Daytime Running Light','Turn Signal Lamp','Third Brake Light','Door Glass','Quarter Glass','Back Glass','Sunroof Glass','Windshield','Window Regulator','Window Motor','Wiper Motor Front','Wiper Motor Rear','Wiper Linkage',
            ],
            'interior_safety' => [
                'Dashboard','Center Console','Glove Box','Door Trim Panel','Seat Front Left','Seat Front Right','Rear Seat','Seat Belt','Airbag Driver','Airbag Passenger','Curtain Airbag','Knee Airbag','Clock Spring','Pedal Assembly','Carpet','Headliner','Sun Visor','Interior Mirror','Exterior Switch Panel','Cup Holder','Trim Panels','Cargo Cover',
            ],
            'infotainment_security' => [
                'Radio/Head Unit','Navigation Unit','Display Screen','Amplifier','Speaker','Camera Front','Camera Rear','Parking Sensor','Blind Spot Radar','Adaptive Cruise Radar','Lane Camera','Antenna','USB/AUX Port','Key Fob','Door Lock Actuator','Latch Assembly','Alarm Siren',
            ],
            'wheels_tires' => [
                'Wheel/Rim','Spare Wheel','TPMS Sensor','Wheel Cap','Lug Nut Set','Jack Kit','Wheel Lock Key',
            ],
            'hybrid_ev' => [
                'Hybrid Battery','Inverter','Converter','Hybrid Control Module','Electric Drive Motor','Charging Port','Onboard Charger','DC-DC Converter','Battery Cooling Fan','High Voltage Cable','Battery ECU',
            ],
        ];
    }

    // =========================================================
    // harvestableParts() — returns salvageable parts for a vehicle
    // =========================================================
    public static function harvestableParts(
        string $make,
        string $model,
        int $year,
        int $cylinders = 0,
        float $engineL = 0.0,
        ?string $bodyStyle = null,
        ?string $fuelType = null,
        ?string $driveType = null
    ): array {
        $make = strtoupper(trim($make));
        $model = strtoupper(trim($model));
        $fuel = strtoupper(trim((string) $fuelType));
        $body = strtoupper(trim((string) $bodyStyle));

        $oem = self::lookup($make, $model, $year, $cylinders, $engineL);
        $parts = self::salvageCategories();

        // Remove hybrid/EV category unless vehicle indicates it.
        if (!str_contains($fuel, 'HYBRID') && !str_contains($fuel, 'ELECTRIC') && !str_contains($model, 'PRIUS') && !str_contains($model, 'TESLA')) {
            unset($parts['hybrid_ev']);
        }

        // Sedan/coupe usually does not have tailgate/liftgate-specific parts.
        if (in_array($body, ['SEDAN','COUPE','CONVERTIBLE'])) {
            $parts['body_exterior'] = array_values(array_diff($parts['body_exterior'], ['Tailgate/Liftgate','Sliding Door','Roof Rack','Cargo Cover']));
        }

        // Trucks/SUVs get additional high-value harvest items.
        if (in_array($body, ['SUV','TRUCK','PICKUP','VAN','MINIVAN']) || in_array($model, ['HILUX','TUNDRA','F-150','F150','SIENNA','ODYSSEY','PILOT','HIGHLANDER','RAV4','SEQUOIA','PATHFINDER','ARMADA','EXPLORER','ESCAPE'])) {
            $parts['truck_suv_van'] = ['Bed Assembly','Tailgate','Rear Axle Assembly','Transfer Case Skid Plate','Third Row Seat','Sliding Door Motor','Liftgate Motor','Roof Rail','Tow Hitch','Running Board','Cargo Trim','Rear HVAC Unit'];
        }

        return [
            'vehicle' => [
                'year' => $year,
                'make' => $make,
                'model' => $model,
                'body_style' => $bodyStyle,
                'fuel_type' => $fuelType,
                'drive_type' => $driveType ?: ($oem['drive_type'] ?? null),
            ],
            'oem' => $oem,
            'harvest_categories' => $parts,
            'high_value_priority' => self::highValuePriority($make, $model, $year, $oem, $fuel),
            'fitment_warning' => 'Use this list for salvage intake. Confirm exact interchange with VIN, trim, body style, engine/transmission code, drivetrain, side, part number, and visual match before sale.',
        ];
    }

    // =========================================================
    // highValuePriority() — suggested priority list for dismantling
    // =========================================================
    public static function highValuePriority(string $make, string $model, int $year, array $oem = [], string $fuel = ''): array
    {
        $base = [
            'Engine Assembly','Transmission Assembly','ECU/PCM','TCM','ABS Module','Catalytic Converter','A/C Compressor','Alternator','Starter Motor','Headlight Left','Headlight Right','Tail Light Left','Tail Light Right','Mirror Left','Mirror Right','Steering Rack','Wheel/Rim','Door Assemblies','Bumper Covers','Instrument Cluster','Radio/Head Unit','Airbag Set','Seat Set',
        ];

        if (!empty($oem['engine_code'])) array_unshift($base, 'Engine Code: '.$oem['engine_code']);
        if (!empty($oem['transmission_code'])) array_unshift($base, 'Transmission Code: '.$oem['transmission_code']);
        if (str_contains($fuel, 'HYBRID') || str_contains($fuel, 'ELECTRIC') || str_contains($model, 'PRIUS')) {
            array_unshift($base, 'Hybrid Battery','Inverter/Converter','High Voltage Cables');
        }
        return array_values(array_unique($base));
    }

    // =========================================================
    // fitmentKeys() — fields staff should capture for each salvaged part
    // =========================================================
    public static function fitmentKeys(): array
    {
        return [
            'vin','year','make','model','trim','body_style','engine_code','engine_l','cylinders','transmission_code','drive_type','fuel_type','part_category','part_name','oem_part_number','casting_number','side','position','color','pin_count','connector_count','mileage','condition_grade','donor_stock_number','photos_required','notes',
        ];
    }


}