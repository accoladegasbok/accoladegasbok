<?php
// FILE: app/Data/OemDatabase.php
// OEM engine and transmission code database built from Ladipo Auto Market
// data covering Toyota/Lexus/Honda/Nissan/Hyundai/Kia/Mercedes/Ford
// with verified pin counts from stock records.

namespace App\Data;

class OemDatabase
{
    // =========================================================
    // lookup() — returns OEM codes for a given vehicle
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
            'gear_alias'        => null,
            'engine_l'          => $engineL ?: null,
            'drive_type'        => null,
            'market_note'       => null,
            'multiple_engines'  => false,
        ];

        // ── TOYOTA ───────────────────────────────────────────────
        if ($make === 'TOYOTA') {

            // Corolla 1.6L (2003-2008) — 3ZZ-FE
            if ($model === 'COROLLA' && $year >= 2003 && $year <= 2008 && ($engineL < 1.7 && $engineL > 0)) {
                return array_merge($default, ['engine_code'=>'3ZZ-FE','transmission_code'=>'U341E','pin_count'=>5,'gear_alias'=>'5-pin gear (Corolla 1.6L)']);
            }
            // Corolla 1.8L (2000-2013) — 1ZZ-FE / 2ZR-FE
            if ($model === 'COROLLA') {
                if ($year >= 2009 && $year <= 2013) return array_merge($default, ['engine_code'=>'2ZR-FE','transmission_code'=>'K310','pin_count'=>12,'gear_alias'=>'12-pin CVT (Corolla 09-13)','market_note'=>'K310/K311 CVT common in Nigerian market']);
                if ($year >= 2000 && $year <= 2008) return array_merge($default, ['engine_code'=>'1ZZ-FE','transmission_code'=>'U341E','pin_count'=>5,'gear_alias'=>'5-pin gear (Corolla 1.8L)']);
                if ($year >= 2014) return array_merge($default, ['engine_code'=>'2ZR-FE','transmission_code'=>'CVT','pin_count'=>null,'gear_alias'=>'CVT (Corolla 2014+)']);
            }

            // Camry 2.4L (2002-2011) — 2AZ-FE
            if ($model === 'CAMRY') {
                if ($year >= 2002 && $year <= 2011 && ($engineL < 2.6 || $engineL == 0) && $cylinders <= 4) {
                    return array_merge($default, ['engine_code'=>'2AZ-FE','transmission_code'=>'U241E','pin_count'=>13,'gear_alias'=>'13-pin gear (Camry 2.4L)','market_note'=>'Most common Camry in Nigerian/Ghanaian market']);
                }
                // Camry 2.5L (2012+) — 2AR-FE
                if ($year >= 2012 && ($engineL < 2.7 || $engineL == 0) && $cylinders <= 4) {
                    return array_merge($default, ['engine_code'=>'2AR-FE','transmission_code'=>'U760E','pin_count'=>22,'gear_alias'=>'22-pin gear (Camry 2.5L 2012+)']);
                }
                // Camry V6 3.5L — 2GR-FE
                if ($cylinders == 6 || $engineL >= 3.0) {
                    return array_merge($default, ['engine_code'=>'2GR-FE','transmission_code'=>'A750E','pin_count'=>null,'gear_alias'=>'V6 6-speed AT (Camry V6)']);
                }
                // Default Camry
                return array_merge($default, ['engine_code'=>'2AZ-FE','transmission_code'=>'U241E','pin_count'=>13,'gear_alias'=>'13-pin gear','multiple_engines'=>true]);
            }

            // RAV4 2.4L — 2AZ-FE
            if ($model === 'RAV4' && $year >= 2001 && $year <= 2012) {
                return array_merge($default, ['engine_code'=>'2AZ-FE','transmission_code'=>'U241E','pin_count'=>13,'gear_alias'=>'13-pin gear (RAV4 2.4L)']);
            }
            if ($model === 'RAV4' && $year >= 2013) {
                return array_merge($default, ['engine_code'=>'2AR-FE','transmission_code'=>'U760E','pin_count'=>22,'gear_alias'=>'22-pin (RAV4 2013+)']);
            }

            // Highlander 2.4L (2001-2007) / 3.5L (2008+)
            if ($model === 'HIGHLANDER') {
                if ($year >= 2001 && $year <= 2007) return array_merge($default, ['engine_code'=>'2AZ-FE','transmission_code'=>'U241E','pin_count'=>13,'gear_alias'=>'13-pin gear (Highlander 2.4L)']);
                if ($year >= 2008) return array_merge($default, ['engine_code'=>'2GR-FE','transmission_code'=>'A750E','pin_count'=>null,'gear_alias'=>'V6 (Highlander 3.5L)']);
            }

            // Avalon 3.5L — 2GR-FE
            if ($model === 'AVALON') {
                return array_merge($default, ['engine_code'=>'2GR-FE','transmission_code'=>'A750E','pin_count'=>null,'gear_alias'=>'V6 6AT (Avalon)']);
            }

            // Venza 2.7L (2009-2015)
            if ($model === 'VENZA') {
                if ($cylinders <= 4 || $engineL < 3.0) return array_merge($default, ['engine_code'=>'2AR-FE','transmission_code'=>'U760E','pin_count'=>22,'gear_alias'=>'22-pin (Venza 2.7L)']);
                return array_merge($default, ['engine_code'=>'2GR-FE','transmission_code'=>'A750E','pin_count'=>null,'gear_alias'=>'V6 (Venza 3.5L)']);
            }

            // Yaris / Vios 1.3L / 1.5L — 1NZ-FE / 2NZ-FE
            if (in_array($model, ['YARIS','VIOS','PLATZ'])) {
                if ($engineL < 1.4) return array_merge($default, ['engine_code'=>'2NZ-FE','transmission_code'=>'U340E','pin_count'=>null,'gear_alias'=>'4AT (Yaris 1.3L)']);
                return array_merge($default, ['engine_code'=>'1NZ-FE','transmission_code'=>'U340E','pin_count'=>null,'gear_alias'=>'4AT (Vios 1.5L)']);
            }

            // Matrix (same as Corolla)
            if ($model === 'MATRIX') {
                if ($year >= 2009) return array_merge($default, ['engine_code'=>'2ZR-FE','transmission_code'=>'K310','pin_count'=>12,'gear_alias'=>'12-pin CVT (Matrix 09+)']);
                return array_merge($default, ['engine_code'=>'1ZZ-FE','transmission_code'=>'U341E','pin_count'=>5,'gear_alias'=>'5-pin (Matrix 2003-2008)']);
            }

            // Solara 2.4L / 3.3L
            if ($model === 'SOLARA') {
                if ($cylinders <= 4) return array_merge($default, ['engine_code'=>'2AZ-FE','transmission_code'=>'U241E','pin_count'=>13,'gear_alias'=>'13-pin (Solara 2.4L)']);
                return array_merge($default, ['engine_code'=>'3MZ-FE','transmission_code'=>'A750E','pin_count'=>null,'gear_alias'=>'V6 (Solara 3.3L)']);
            }

            // Sienna 3.5L — 2GR-FE
            if ($model === 'SIENNA') {
                return array_merge($default, ['engine_code'=>'2GR-FE','transmission_code'=>'A750E','pin_count'=>null,'gear_alias'=>'V6 (Sienna 3.5L)']);
            }

            // Sequoia / Tundra 5.7L — 3UR-FE
            if (in_array($model, ['SEQUOIA','TUNDRA']) && ($engineL >= 5.0 || $cylinders == 8)) {
                return array_merge($default, ['engine_code'=>'3UR-FE','transmission_code'=>'AB60F','pin_count'=>null,'gear_alias'=>'6AT (Tundra/Sequoia 5.7L)']);
            }

            // Land Cruiser 4.7L V8 — 2UZ-FE
            if (in_array($model, ['LAND CRUISER','LANDCRUISER','LAND-CRUISER','LC'])) {
                if ($year >= 2008) return array_merge($default, ['engine_code'=>'1UR-FE','transmission_code'=>'AB60F','pin_count'=>null,'gear_alias'=>'V8 (Land Cruiser 200 4.6L)']);
                return array_merge($default, ['engine_code'=>'2UZ-FE','transmission_code'=>'A750F','pin_count'=>null,'gear_alias'=>'V8 (Land Cruiser 4.7L)']);
            }

            // Prado 3.0L Diesel — 1KD-FTV
            if (str_contains($model, 'PRADO')) {
                if ($engineL >= 2.9 || str_contains(strtolower($model),'diesel')) return array_merge($default, ['engine_code'=>'1KD-FTV','transmission_code'=>'A750F','pin_count'=>null,'gear_alias'=>'Diesel AT (Prado 3.0L D4D)']);
                return array_merge($default, ['engine_code'=>'1GR-FE','transmission_code'=>'A750F','pin_count'=>null,'gear_alias'=>'V6 (Prado 4.0L)']);
            }

            // Hilux Diesel
            if ($model === 'HILUX') {
                if ($engineL >= 2.9 || $engineL == 0) return array_merge($default, ['engine_code'=>'1KD-FTV','transmission_code'=>'R151F','pin_count'=>null,'gear_alias'=>'Diesel (Hilux 3.0L D4D)']);
                return array_merge($default, ['engine_code'=>'2KD-FTV','transmission_code'=>'R151F','pin_count'=>null,'gear_alias'=>'Diesel (Hilux 2.5L)']);
            }

            // FJ Cruiser 4.0L — 1GR-FE
            if (str_contains($model,'FJ')) {
                return array_merge($default, ['engine_code'=>'1GR-FE','transmission_code'=>'A750F','pin_count'=>null,'gear_alias'=>'V6 (FJ Cruiser 4.0L)']);
            }

            // Celica GT 1.8L — 1ZZ-FE / 2ZZ-GE
            if ($model === 'CELICA') {
                if ($engineL >= 1.9) return array_merge($default, ['engine_code'=>'2ZZ-GE','transmission_code'=>'C60','pin_count'=>null,'gear_alias'=>'6MT (Celica GT-S)']);
                return array_merge($default, ['engine_code'=>'1ZZ-FE','transmission_code'=>'U341E','pin_count'=>5,'gear_alias'=>'5-pin (Celica GT 1.8L)']);
            }

            // Fortuner
            if ($model === 'FORTUNER') {
                return array_merge($default, ['engine_code'=>'1KD-FTV','transmission_code'=>'A750F','pin_count'=>null,'gear_alias'=>'Diesel AT (Fortuner 3.0L)']);
            }

            // Innova
            if ($model === 'INNOVA') {
                return array_merge($default, ['engine_code'=>'2KD-FTV','transmission_code'=>'A340F','pin_count'=>null,'gear_alias'=>'Diesel AT (Innova 2.5L)']);
            }
        }

        // ── LEXUS ─────────────────────────────────────────────────
        if ($make === 'LEXUS') {

            // ES300 / ES330 — 1MZ-FE / 3MZ-FE
            if (str_starts_with($model,'ES3')) {
                if ($year >= 2004) return array_merge($default, ['engine_code'=>'3MZ-FE','transmission_code'=>'A750E','pin_count'=>null,'gear_alias'=>'V6 (ES330)']);
                return array_merge($default, ['engine_code'=>'1MZ-FE','transmission_code'=>'A541E','pin_count'=>null,'gear_alias'=>'V6 (ES300)']);
            }

            // ES350 (2007+) — 2GR-FE
            if (str_starts_with($model,'ES35')) {
                return array_merge($default, ['engine_code'=>'2GR-FE','transmission_code'=>'A750E','pin_count'=>null,'gear_alias'=>'V6 (ES350 2.GR-FE)']);
            }

            // RX300 / RX330 / RX350
            if (str_starts_with($model,'RX')) {
                if ($year >= 2007) return array_merge($default, ['engine_code'=>'2GR-FE','transmission_code'=>'U660E','pin_count'=>null,'gear_alias'=>'V6 (RX350 2007+)']);
                if ($year >= 2004) return array_merge($default, ['engine_code'=>'3MZ-FE','transmission_code'=>'A750E','pin_count'=>null,'gear_alias'=>'V6 (RX330)']);
                return array_merge($default, ['engine_code'=>'1MZ-FE','transmission_code'=>'A541E','pin_count'=>null,'gear_alias'=>'V6 (RX300)']);
            }

            // GS300 / IS300 — 2JZ-GE
            if (str_starts_with($model,'GS3') || str_starts_with($model,'IS3')) {
                return array_merge($default, ['engine_code'=>'2JZ-GE','transmission_code'=>'A650E','pin_count'=>null,'gear_alias'=>'3.0L 6AT (GS300/IS300)']);
            }

            // LS430 — 3UZ-FE
            if (str_starts_with($model,'LS4')) {
                return array_merge($default, ['engine_code'=>'3UZ-FE','transmission_code'=>'A650E','pin_count'=>null,'gear_alias'=>'V8 (LS430)']);
            }

            // LX470 / GX470
            if (str_starts_with($model,'LX')) {
                return array_merge($default, ['engine_code'=>'2UZ-FE','transmission_code'=>'A750F','pin_count'=>null,'gear_alias'=>'V8 (LX470)']);
            }
            if (str_starts_with($model,'GX')) {
                if ($year >= 2010) return array_merge($default, ['engine_code'=>'1GR-FE','transmission_code'=>'A750F','pin_count'=>null,'gear_alias'=>'V6 (GX460)']);
                return array_merge($default, ['engine_code'=>'2UZ-FE','transmission_code'=>'A750F','pin_count'=>null,'gear_alias'=>'V8 (GX470)']);
            }
        }

        // ── HONDA / ACURA ─────────────────────────────────────────
        if (in_array($make, ['HONDA','ACURA'])) {

            if ($model === 'ACCORD') {
                if ($year >= 2013 && ($cylinders <= 4 || $engineL < 2.6)) return array_merge($default, ['engine_code'=>'K24W','transmission_code'=>'CVT7','pin_count'=>null,'gear_alias'=>'CVT (Accord 2013+)']);
                if ($year >= 2008 && $year <= 2012 && ($cylinders <= 4 || $engineL < 2.6)) return array_merge($default, ['engine_code'=>'K24Z3','transmission_code'=>'BGRA','pin_count'=>null,'gear_alias'=>'AT (Accord 2008-2012 2.4L)']);
                if ($year >= 2003 && $year <= 2007 && ($cylinders <= 4 || $engineL < 2.6)) return array_merge($default, ['engine_code'=>'K24A','transmission_code'=>'MCTA','pin_count'=>null,'gear_alias'=>'AT (Accord 2003-2007 2.4L)']);
                if ($cylinders == 6 || $engineL >= 3.0) return array_merge($default, ['engine_code'=>'J30A','transmission_code'=>'BAXA','pin_count'=>null,'gear_alias'=>'V6 AT (Accord V6)','multiple_engines'=>true]);
            }

            if ($model === 'CIVIC') {
                if ($year >= 2012) return array_merge($default, ['engine_code'=>'R18Z','transmission_code'=>'CVT','pin_count'=>null,'gear_alias'=>'CVT (Civic 2012+)']);
                if ($year >= 2006) return array_merge($default, ['engine_code'=>'R18A','transmission_code'=>'SPYA','pin_count'=>null,'gear_alias'=>'AT (Civic 2006-2011 1.8L)']);
                if ($year >= 2001 && $year <= 2005) return array_merge($default, ['engine_code'=>'D17A','transmission_code'=>'SLXA','pin_count'=>null,'gear_alias'=>'AT (Civic 2001-2005 1.7L)']);
                return array_merge($default, ['engine_code'=>'D16','transmission_code'=>'MP7A','pin_count'=>null,'gear_alias'=>'AT (Civic 1.6L)']);
            }

            if ($model === 'CR-V') {
                if ($year >= 2012) return array_merge($default, ['engine_code'=>'K24Z7','transmission_code'=>'BGRA','pin_count'=>null,'gear_alias'=>'AT (CR-V 2012+ 2.4L)']);
                if ($year >= 2007) return array_merge($default, ['engine_code'=>'K24Z1','transmission_code'=>'BGRA','pin_count'=>null,'gear_alias'=>'AT (CR-V 2007-2011 2.4L)']);
                return array_merge($default, ['engine_code'=>'K24A','transmission_code'=>'MCTA','pin_count'=>null,'gear_alias'=>'AT (CR-V 2002-2006)']);
            }

            if ($model === 'ODYSSEY') {
                if ($year >= 2005) return array_merge($default, ['engine_code'=>'J35A','transmission_code'=>'BDKA','pin_count'=>null,'gear_alias'=>'V6 AT (Odyssey 2005+)']);
                return array_merge($default, ['engine_code'=>'J30A','transmission_code'=>'BAXA','pin_count'=>null,'gear_alias'=>'V6 AT (Odyssey 1999-2004)']);
            }

            if ($model === 'PILOT') {
                return array_merge($default, ['engine_code'=>'J35A','transmission_code'=>'BDKA','pin_count'=>null,'gear_alias'=>'V6 AT (Pilot 3.5L)']);
            }

            if (in_array($model, ['ELEMENT'])) {
                return array_merge($default, ['engine_code'=>'K24A','transmission_code'=>'MCTA','pin_count'=>null,'gear_alias'=>'AT (Element 2.4L)']);
            }

            if (in_array($model, ['RSX']) || ($make === 'ACURA' && $model === 'RSX')) {
                return array_merge($default, ['engine_code'=>'K20A','transmission_code'=>'MRYA','pin_count'=>null,'gear_alias'=>'AT (RSX 2.0L)']);
            }

            if ($make === 'ACURA' && str_starts_with($model,'MDX')) {
                return array_merge($default, ['engine_code'=>'J35A','transmission_code'=>'BDKA','pin_count'=>null,'gear_alias'=>'V6 AT (MDX 3.5L)']);
            }

            if ($make === 'ACURA' && str_starts_with($model,'TL')) {
                return array_merge($default, ['engine_code'=>'J30A','transmission_code'=>'BAXA','pin_count'=>null,'gear_alias'=>'V6 AT (TL 3.2L)']);
            }
        }

        // ── NISSAN / INFINITI ─────────────────────────────────────
        if (in_array($make, ['NISSAN','INFINITI'])) {

            if ($model === 'ALTIMA') {
                if ($year >= 2013) return array_merge($default, ['engine_code'=>'QR25DE','transmission_code'=>'CVT8','pin_count'=>null,'gear_alias'=>'CVT (Altima 2013+)']);
                if ($year >= 2002) return array_merge($default, ['engine_code'=>'QR25DE','transmission_code'=>'RE4F04B','pin_count'=>null,'gear_alias'=>'AT (Altima 2.5L)','multiple_engines'=>$cylinders==6]);
                if ($cylinders == 6 || $engineL >= 3.0) return array_merge($default, ['engine_code'=>'VQ35DE','transmission_code'=>'RE5R05A','pin_count'=>null,'gear_alias'=>'V6 AT (Altima V6)']);
            }

            if ($model === 'SENTRA') {
                if ($year >= 2013) return array_merge($default, ['engine_code'=>'MR20DE','transmission_code'=>'CVT','pin_count'=>null,'gear_alias'=>'CVT (Sentra 2.0L)']);
                if ($year >= 2007) return array_merge($default, ['engine_code'=>'MR20DE','transmission_code'=>'RE4F03B','pin_count'=>null,'gear_alias'=>'AT (Sentra 2007-2012)']);
                return array_merge($default, ['engine_code'=>'QG18DE','transmission_code'=>'RE4F03A','pin_count'=>null,'gear_alias'=>'AT (Sentra 1.8L)']);
            }

            if ($model === 'MAXIMA') {
                return array_merge($default, ['engine_code'=>'VQ35DE','transmission_code'=>'RE5R05A','pin_count'=>null,'gear_alias'=>'V6 AT (Maxima 3.5L)']);
            }

            if ($model === 'MURANO') {
                return array_merge($default, ['engine_code'=>'VQ35DE','transmission_code'=>'RE5R05A','pin_count'=>null,'gear_alias'=>'V6 CVT (Murano)']);
            }

            if (in_array($model, ['PATHFINDER','ARMADA'])) {
                return array_merge($default, ['engine_code'=>'VQ40DE','transmission_code'=>'RE5R05A','pin_count'=>null,'gear_alias'=>'V6/V8 AT (Pathfinder/Armada)']);
            }

            if ($model === 'TIIDA') {
                return array_merge($default, ['engine_code'=>'HR16DE','transmission_code'=>'RE4F03B','pin_count'=>null,'gear_alias'=>'AT (Tiida 1.6L)']);
            }

            if ($model === 'ALMERA') {
                if ($year >= 2012) return array_merge($default, ['engine_code'=>'HR16DE','transmission_code'=>'CVT','pin_count'=>null,'gear_alias'=>'CVT (Almera 2012+)']);
                return array_merge($default, ['engine_code'=>'GA16DE','transmission_code'=>'RE4F03A','pin_count'=>null,'gear_alias'=>'AT (Almera 1.6L)']);
            }

            if (in_array($model, ['X-TRAIL','XTRAIL','X TRAIL'])) {
                if ($year >= 2008) return array_merge($default, ['engine_code'=>'MR20DE','transmission_code'=>'CVT','pin_count'=>null,'gear_alias'=>'CVT (X-Trail 2.0L)']);
                return array_merge($default, ['engine_code'=>'QR25DE','transmission_code'=>'RE4F04B','pin_count'=>null,'gear_alias'=>'AT (X-Trail 2.5L)']);
            }

            if ($model === '350Z' || $model === 'G35') {
                return array_merge($default, ['engine_code'=>'VQ35DE','transmission_code'=>'RE5R05A','pin_count'=>null,'gear_alias'=>'V6 (350Z/G35)']);
            }

            if ($make === 'INFINITI' && str_starts_with($model,'FX')) {
                return array_merge($default, ['engine_code'=>'VQ35DE','transmission_code'=>'RE5R05A','pin_count'=>null,'gear_alias'=>'V6 (Infiniti FX35)']);
            }

            if ($make === 'INFINITI' && str_starts_with($model,'QX')) {
                return array_merge($default, ['engine_code'=>'VK56DE','transmission_code'=>'RE5R05A','pin_count'=>null,'gear_alias'=>'V8 (QX56)']);
            }
        }

        // ── HYUNDAI / KIA ─────────────────────────────────────────
        if (in_array($make, ['HYUNDAI','KIA'])) {

            if ($model === 'ELANTRA' || ($make === 'KIA' && $model === 'CERATO')) {
                if ($year >= 2011) return array_merge($default, ['engine_code'=>'G4FD','transmission_code'=>'A6GF1','pin_count'=>null,'gear_alias'=>'6AT (Elantra 1.8L 2011+)']);
                if ($year >= 2006) return array_merge($default, ['engine_code'=>'G4FC','transmission_code'=>'A4CF1','pin_count'=>null,'gear_alias'=>'4AT (Elantra 1.6L)']);
                return array_merge($default, ['engine_code'=>'G4ED','transmission_code'=>'F4A42','pin_count'=>null,'gear_alias'=>'4AT (Elantra 2000-2006)']);
            }

            if ($model === 'SONATA' || ($make === 'KIA' && $model === 'OPTIMA')) {
                if ($year >= 2010) return array_merge($default, ['engine_code'=>'G4KD','transmission_code'=>'A6MF1','pin_count'=>null,'gear_alias'=>'6AT (Sonata/Optima 2.0L)']);
                if ($year >= 2005) return array_merge($default, ['engine_code'=>'G4KC','transmission_code'=>'F5A51','pin_count'=>null,'gear_alias'=>'5AT (Sonata 2.4L)']);
                return array_merge($default, ['engine_code'=>'G4JP','transmission_code'=>'F4A42','pin_count'=>null,'gear_alias'=>'4AT (Sonata 2.7L)','multiple_engines'=>true]);
            }

            if (in_array($model, ['SANTA FE']) || ($make === 'KIA' && $model === 'SORENTO')) {
                if ($year >= 2010) return array_merge($default, ['engine_code'=>'G4KD','transmission_code'=>'A6MF1','pin_count'=>null,'gear_alias'=>'6AT (Santa Fe 2.0L)']);
                return array_merge($default, ['engine_code'=>'G6BA','transmission_code'=>'A5HF1','pin_count'=>null,'gear_alias'=>'5AT (Santa Fe V6 2.7L)']);
            }

            if (in_array($model, ['TUCSON']) || ($make === 'KIA' && $model === 'SPORTAGE')) {
                if ($year >= 2010) return array_merge($default, ['engine_code'=>'G4KD','transmission_code'=>'A6MF1','pin_count'=>null,'gear_alias'=>'6AT (Tucson/Sportage 2.0L)']);
                return array_merge($default, ['engine_code'=>'G4GC','transmission_code'=>'F4A42','pin_count'=>null,'gear_alias'=>'4AT (Tucson/Sportage 2.0L)']);
            }

            if ($model === 'ACCENT' || ($make === 'KIA' && $model === 'RIO')) {
                if ($year >= 2012) return array_merge($default, ['engine_code'=>'G4FC','transmission_code'=>'A6GF1','pin_count'=>null,'gear_alias'=>'6AT (Accent/Rio 1.6L 2012+)']);
                return array_merge($default, ['engine_code'=>'G4ED','transmission_code'=>'A4CF1','pin_count'=>null,'gear_alias'=>'4AT (Accent 1.5L)']);
            }

            if ($make === 'KIA' && in_array($model, ['SOUL','FORTE'])) {
                return array_merge($default, ['engine_code'=>'G4KD','transmission_code'=>'A6MF1','pin_count'=>null,'gear_alias'=>'6AT (Soul/Forte 2.0L)']);
            }

            if (str_starts_with($model,'GENESIS') || ($make === 'HYUNDAI' && $model === 'GENESIS')) {
                return array_merge($default, ['engine_code'=>'G6DC','transmission_code'=>'A6MF2','pin_count'=>null,'gear_alias'=>'6AT (Genesis 3.8L V6)']);
            }
        }

        // ── MERCEDES-BENZ ─────────────────────────────────────────
        if (in_array($make, ['MERCEDES','MERCEDES-BENZ','MB'])) {

            // C-Class
            if (str_starts_with($model,'C')) {
                if (str_contains($model,'180') || str_contains($model,'200')) {
                    if ($year >= 2007) return array_merge($default, ['engine_code'=>'M271','transmission_code'=>'722.9','pin_count'=>null,'gear_alias'=>'7G-Tronic (C180/C200 2007+)']);
                    return array_merge($default, ['engine_code'=>'M271','transmission_code'=>'722.6','pin_count'=>null,'gear_alias'=>'5G-Tronic (C180/C200 Kompressor)']);
                }
                if (str_contains($model,'280') || str_contains($model,'300')) {
                    return array_merge($default, ['engine_code'=>'M272','transmission_code'=>'722.9','pin_count'=>null,'gear_alias'=>'7G-Tronic (C280/C300 V6)']);
                }
                if (str_contains($model,'350')) {
                    return array_merge($default, ['engine_code'=>'M276','transmission_code'=>'722.9','pin_count'=>null,'gear_alias'=>'7G-Tronic (C350)']);
                }
            }

            // E-Class
            if (str_starts_with($model,'E')) {
                if (str_contains($model,'200') || str_contains($model,'230')) {
                    return array_merge($default, ['engine_code'=>'M271','transmission_code'=>'722.9','pin_count'=>null,'gear_alias'=>'7G-Tronic (E200/E230)']);
                }
                if (str_contains($model,'320')) {
                    return array_merge($default, ['engine_code'=>'M112','transmission_code'=>'722.6','pin_count'=>null,'gear_alias'=>'5G-Tronic (E320 V6)']);
                }
                if (str_contains($model,'350')) {
                    if ($year >= 2009) return array_merge($default, ['engine_code'=>'M276','transmission_code'=>'722.9','pin_count'=>null,'gear_alias'=>'7G-Tronic (E350 2009+)']);
                    return array_merge($default, ['engine_code'=>'M272','transmission_code'=>'722.9','pin_count'=>null,'gear_alias'=>'7G-Tronic (E350 2006-2008)']);
                }
                if (str_contains($model,'500') || str_contains($model,'550')) {
                    return array_merge($default, ['engine_code'=>'M113','transmission_code'=>'722.9','pin_count'=>null,'gear_alias'=>'7G-Tronic (E500/E550 V8)']);
                }
            }

            // ML-Class
            if (str_starts_with($model,'ML') || str_starts_with($model,'GLE')) {
                if (str_contains($model,'350')) {
                    if ($year >= 2012) return array_merge($default, ['engine_code'=>'M276','transmission_code'=>'722.9','pin_count'=>null,'gear_alias'=>'7G-Tronic (ML350 2012+)']);
                    return array_merge($default, ['engine_code'=>'M272','transmission_code'=>'722.9','pin_count'=>null,'gear_alias'=>'7G-Tronic (ML350 V6)']);
                }
                if (str_contains($model,'500') || str_contains($model,'550')) {
                    return array_merge($default, ['engine_code'=>'M113','transmission_code'=>'722.9','pin_count'=>null,'gear_alias'=>'7G-Tronic (ML500 V8)']);
                }
            }

            // S-Class
            if (str_starts_with($model,'S')) {
                if (str_contains($model,'320') || str_contains($model,'350')) {
                    return array_merge($default, ['engine_code'=>'M112','transmission_code'=>'722.6','pin_count'=>null,'gear_alias'=>'5G-Tronic (S320/S350)']);
                }
                if (str_contains($model,'500') || str_contains($model,'550')) {
                    return array_merge($default, ['engine_code'=>'M113','transmission_code'=>'722.9','pin_count'=>null,'gear_alias'=>'7G-Tronic (S500/S550 V8)']);
                }
            }
        }

        // ── FORD ─────────────────────────────────────────────────
        if ($make === 'FORD') {

            if ($model === 'FOCUS') {
                if ($year >= 2012) return array_merge($default, ['engine_code'=>'Fox2.0','transmission_code'=>'PowerShift','pin_count'=>null,'gear_alias'=>'DCT (Focus 2012+)']);
                return array_merge($default, ['engine_code'=>'Duratec20','transmission_code'=>'4F27E','pin_count'=>null,'gear_alias'=>'AT (Focus 2.0L)']);
            }

            if ($model === 'FUSION') {
                if ($cylinders <= 4) return array_merge($default, ['engine_code'=>'Duratec25','transmission_code'=>'6F35','pin_count'=>null,'gear_alias'=>'6AT (Fusion 2.5L 4-cyl)']);
                return array_merge($default, ['engine_code'=>'Duratec30','transmission_code'=>'6F50','pin_count'=>null,'gear_alias'=>'6AT (Fusion 3.0L V6)']);
            }

            if ($model === 'ESCAPE') {
                if ($year >= 2009 && $cylinders <= 4) return array_merge($default, ['engine_code'=>'Duratec25','transmission_code'=>'6F35','pin_count'=>null,'gear_alias'=>'6AT (Escape 2.5L)']);
                if ($cylinders == 6) return array_merge($default, ['engine_code'=>'Duratec30','transmission_code'=>'4F27E','pin_count'=>null,'gear_alias'=>'AT (Escape 3.0L V6)']);
                return array_merge($default, ['engine_code'=>'Duratec20','transmission_code'=>'4F27E','pin_count'=>null,'gear_alias'=>'AT (Escape 2.0L)']);
            }

            if ($model === 'EXPLORER') {
                if ($year >= 2011) return array_merge($default, ['engine_code'=>'Cyclone35','transmission_code'=>'6F35','pin_count'=>null,'gear_alias'=>'6AT (Explorer 3.5L EcoBoost)']);
                return array_merge($default, ['engine_code'=>'Cologne46','transmission_code'=>'5R55S','pin_count'=>null,'gear_alias'=>'5AT (Explorer 4.6L V8)']);
            }

            if (in_array($model, ['F-150','F150','F 150'])) {
                if ($cylinders == 8 || $engineL >= 5.0) return array_merge($default, ['engine_code'=>'Modular46','transmission_code'=>'6R80','pin_count'=>null,'gear_alias'=>'6AT (F-150 5.0L V8)']);
                return array_merge($default, ['engine_code'=>'EcoBoost35','transmission_code'=>'6R80','pin_count'=>null,'gear_alias'=>'6AT (F-150 3.5L EcoBoost)']);
            }

            if (in_array($model, ['EXPEDITION'])) {
                return array_merge($default, ['engine_code'=>'Modular54','transmission_code'=>'6R75','pin_count'=>null,'gear_alias'=>'6AT (Expedition 5.4L V8)']);
            }

            if ($model === 'EDGE') {
                return array_merge($default, ['engine_code'=>'Cyclone35','transmission_code'=>'6F50','pin_count'=>null,'gear_alias'=>'6AT (Edge 3.5L)']);
            }
        }

        // ── CHEVROLET / GM ────────────────────────────────────────
        if (in_array($make, ['CHEVROLET','CHEVY','GMC'])) {
            if (in_array($model, ['CAMARO','CORVETTE']) && ($cylinders == 8 || $engineL >= 5.0)) {
                return array_merge($default, ['engine_code'=>'LS3','transmission_code'=>'6L80','pin_count'=>null,'gear_alias'=>'6AT (LS3 6.2L)']);
            }
            if (in_array($model, ['SILVERADO','SIERRA','TAHOE','YUKON','SUBURBAN'])) {
                return array_merge($default, ['engine_code'=>'Vortec53','transmission_code'=>'6L80','pin_count'=>null,'gear_alias'=>'6AT (Vortec 5.3L)']);
            }
            if ($model === 'MALIBU') {
                return array_merge($default, ['engine_code'=>'Ecotec25','transmission_code'=>'6T40','pin_count'=>null,'gear_alias'=>'6AT (Malibu 2.5L)']);
            }
            if (in_array($model, ['TRAX','SONIC','CRUZE'])) {
                return array_merge($default, ['engine_code'=>'Ecotec14','transmission_code'=>'6T30','pin_count'=>null,'gear_alias'=>'6AT (Cruze/Sonic 1.4L Turbo)']);
            }
        }

        return $default;
    }

    // =========================================================
    // engineOptions() — returns engine options for Year/Make/Model picker
    // =========================================================
    public static function engineOptions(string $make, string $model, int $year): array
    {
        $make  = strtoupper(trim($make));
        $model = strtoupper(trim($model));
        $options = [];

        // Toyota Camry — multiple engine options by year
        if ($make === 'TOYOTA' && $model === 'CAMRY') {
            if ($year >= 2012) {
                $options[] = ['label' => '2.5L 4-cyl (2AR-FE) — Most common', 'engine_code' => '2AR-FE', 'cylinders' => 4, 'engine_l' => 2.5];
                $options[] = ['label' => '3.5L V6 (2GR-FE)', 'engine_code' => '2GR-FE', 'cylinders' => 6, 'engine_l' => 3.5];
            } elseif ($year >= 2002) {
                $options[] = ['label' => '2.4L 4-cyl (2AZ-FE) — Most common', 'engine_code' => '2AZ-FE', 'cylinders' => 4, 'engine_l' => 2.4];
                $options[] = ['label' => '3.0L V6 (1MZ-FE)', 'engine_code' => '1MZ-FE', 'cylinders' => 6, 'engine_l' => 3.0];
            }
        }

        // Toyota Highlander
        if ($make === 'TOYOTA' && $model === 'HIGHLANDER') {
            if ($year >= 2008) {
                $options[] = ['label' => '3.5L V6 (2GR-FE)', 'engine_code' => '2GR-FE', 'cylinders' => 6, 'engine_l' => 3.5];
                $options[] = ['label' => '2.7L 4-cyl (1AR-FE)', 'engine_code' => '1AR-FE', 'cylinders' => 4, 'engine_l' => 2.7];
            } else {
                $options[] = ['label' => '2.4L 4-cyl (2AZ-FE)', 'engine_code' => '2AZ-FE', 'cylinders' => 4, 'engine_l' => 2.4];
                $options[] = ['label' => '3.3L V6 (3MZ-FE)', 'engine_code' => '3MZ-FE', 'cylinders' => 6, 'engine_l' => 3.3];
            }
        }

        return $options;
    }

    // =========================================================
    // pinCounts() — lookup table for pin counts
    // =========================================================
    public static function pinCounts(): array
    {
        return [
            // Toyota/Lexus
            'U341E'  => 5,
            'U241E'  => 13,
            'U760E'  => 22,
            'U660E'  => 22,
            'K310'   => 12,
            'K311'   => 12,
            'K313'   => 12,
            'A750E'  => null,
            'A750F'  => null,
            'A541E'  => null,
            'A650E'  => null,
            'AB60F'  => null,
            // Honda
            'MCTA'   => null,
            'BGRA'   => null,
            'BAXA'   => null,
            'BDKA'   => null,
            // Nissan
            'RE4F03A'=> null,
            'RE4F04B'=> null,
            'RE5R05A'=> null,
            // Hyundai/Kia
            'A6MF1'  => null,
            'A4CF1'  => null,
            'A6GF1'  => null,
            // Mercedes
            '722.6'  => null,
            '722.9'  => null,
        ];
    }

    // =========================================================
    // interchange() — vehicles sharing the same powertrain
    // Key = engine_code OR transmission_code
    // Value = array of vehicle strings for customer advisory
    // =========================================================
    public static function interchange(): array
    {
        return [
            // ── TOYOTA / LEXUS ────────────────────────────────────

            // 1ZZ-FE — Corolla/Matrix/Celica/Vibe (2000-2008)
            '1ZZ-FE' => [
                '2000-2008 Toyota Corolla 1.8L (1ZZ-FE)',
                '2003-2008 Toyota Matrix 1.8L (1ZZ-FE)',
                '2000-2005 Toyota Celica GT 1.8L (1ZZ-FE)',
                '2003-2008 Pontiac Vibe 1.8L (same 1ZZ-FE)',
            ],
            // 5-pin gear U341E
            'U341E' => [
                '2003-2008 Toyota Corolla 1.8L (5-pin U341E)',
                '2003-2008 Toyota Matrix 1.8L (5-pin U341E)',
                '2000-2005 Toyota Celica GT (5-pin U341E)',
                '2003-2008 Pontiac Vibe (5-pin U341E)',
            ],
            'U341E-5pin' => [
                '2003-2008 Toyota Corolla 1.8L',
                '2003-2008 Toyota Matrix 1.8L',
                '2000-2005 Toyota Celica GT',
                '2003-2008 Pontiac Vibe',
            ],

            // 3ZZ-FE — Corolla 1.6L
            '3ZZ-FE' => [
                '2003-2008 Toyota Corolla 1.6L (3ZZ-FE)',
                '2003-2008 Toyota Auris 1.6L (3ZZ-FE)',
            ],

            // 2ZR-FE — Corolla/Matrix/Auris (2009-2013)
            '2ZR-FE' => [
                '2009-2013 Toyota Corolla 1.8L (2ZR-FE)',
                '2009-2013 Toyota Matrix 1.8L (2ZR-FE)',
                '2007-2012 Toyota Auris 1.8L (2ZR-FE)',
                '2009-2013 Pontiac Vibe 1.8L (2ZR-FE)',
            ],
            'K310' => [
                '2009-2013 Toyota Corolla 1.8L CVT',
                '2009-2013 Toyota Matrix 1.8L CVT',
                '2009-2013 Toyota Auris 1.8L CVT',
            ],
            'K311' => [
                '2009-2013 Toyota Corolla 1.8L CVT (K311)',
                '2009-2013 Toyota Matrix 1.8L CVT',
            ],

            // 2AZ-FE — Camry/RAV4/Highlander/Solara/Venza (2.4L)
            '2AZ-FE' => [
                '2002-2011 Toyota Camry 2.4L (2AZ-FE)',
                '2001-2012 Toyota RAV4 2.4L (2AZ-FE)',
                '2001-2007 Toyota Highlander 2.4L (2AZ-FE)',
                '2002-2008 Toyota Solara 2.4L (2AZ-FE)',
                '2009-2012 Toyota Venza 2.7L (2AZ-FE)',
                '2004-2006 Lexus ES330 (2AZ-FE, some)',
                '2006-2012 Toyota Alphard 2.4L (2AZ-FE)',
            ],
            'U241E' => [
                '2002-2011 Toyota Camry 2.4L (13-pin U241E)',
                '2001-2012 Toyota RAV4 2.4L (13-pin U241E)',
                '2001-2007 Toyota Highlander 2.4L (13-pin U241E)',
                '2002-2008 Toyota Solara 2.4L (13-pin U241E)',
            ],

            // 2AR-FE — Camry/RAV4/Highlander (2.5L 2012+)
            '2AR-FE' => [
                '2012-2018 Toyota Camry 2.5L (2AR-FE)',
                '2013-2018 Toyota RAV4 2.5L (2AR-FE)',
                '2014-2019 Toyota Highlander 2.7L (2AR-FE)',
                '2009-2015 Toyota Venza 2.7L (2AR-FE)',
                '2012-2017 Toyota Aurion 2.5L (2AR-FE)',
            ],
            'U760E' => [
                '2012-2018 Toyota Camry 2.5L (22-pin U760E)',
                '2013-2018 Toyota RAV4 2.5L (22-pin U760E)',
                '2009-2015 Toyota Venza 2.7L (22-pin U760E)',
            ],

            // 2GR-FE — Camry V6/Highlander/Avalon/Sienna/Lexus RX/ES
            '2GR-FE' => [
                '2007-2018 Toyota Camry 3.5L V6 (2GR-FE)',
                '2008-2019 Toyota Highlander 3.5L V6 (2GR-FE)',
                '2005-2018 Toyota Avalon 3.5L V6 (2GR-FE)',
                '2011-2020 Toyota Sienna 3.5L V6 (2GR-FE)',
                '2006-2012 Lexus GS350 3.5L (2GR-FE)',
                '2007-2015 Lexus ES350 3.5L (2GR-FE)',
                '2007-2015 Lexus RX350 3.5L (2GR-FE)',
                '2010-2017 Lexus RX350 3.5L (2GR-FE)',
            ],
            'A750E' => [
                '2007-2018 Toyota Camry V6 (A750E 6AT)',
                '2008-2013 Toyota Highlander V6 (A750E)',
                '2005-2018 Toyota Avalon V6 (A750E)',
                '2007-2015 Lexus ES350 (A750E)',
                '2007-2015 Lexus RX350 (A750E)',
            ],

            // 1MZ-FE / 3MZ-FE — Camry V6/ES300/RX300 (3.0L/3.3L)
            '1MZ-FE' => [
                '1997-2001 Toyota Camry 3.0L V6 (1MZ-FE)',
                '1999-2003 Lexus ES300 3.0L (1MZ-FE)',
                '1999-2003 Lexus RX300 3.0L (1MZ-FE)',
                '2001-2003 Toyota Highlander 3.0L (1MZ-FE)',
                '2002-2003 Toyota Solara 3.0L (1MZ-FE)',
            ],
            '3MZ-FE' => [
                '2004-2006 Toyota Camry 3.3L V6 (3MZ-FE)',
                '2004-2006 Lexus ES330 3.3L (3MZ-FE)',
                '2004-2006 Lexus RX330 3.3L (3MZ-FE)',
                '2004-2007 Toyota Highlander 3.3L (3MZ-FE)',
                '2004-2008 Toyota Solara 3.3L (3MZ-FE)',
            ],

            // 1NZ-FE — Yaris/Vios/Platz
            '1NZ-FE' => [
                '2002-2012 Toyota Vios 1.5L (1NZ-FE)',
                '2005-2012 Toyota Yaris 1.5L (1NZ-FE)',
                '2002-2007 Toyota Platz 1.5L (1NZ-FE)',
                '2001-2006 Toyota Echo 1.5L (1NZ-FE)',
            ],

            // 2JZ-GE — GS300/IS300/Supra
            '2JZ-GE' => [
                '1993-2005 Toyota Supra 3.0L (2JZ-GE/GTE)',
                '1998-2005 Lexus GS300 3.0L (2JZ-GE)',
                '2001-2005 Lexus IS300 3.0L (2JZ-GE)',
            ],

            // 2UZ-FE — Land Cruiser/Tundra/Sequoia/LX470
            '2UZ-FE' => [
                '1998-2007 Toyota Land Cruiser 4.7L V8',
                '1999-2007 Toyota Tundra 4.7L V8',
                '2001-2007 Toyota Sequoia 4.7L V8',
                '2003-2007 Lexus LX470 4.7L V8',
                '2003-2009 Lexus GX470 4.7L V8',
            ],
            '3UZ-FE' => [
                '2001-2007 Lexus LS430 4.3L V8',
                '2001-2007 Lexus GS430 4.3L V8',
                '2002-2009 Lexus SC430 4.3L V8',
            ],

            // 1GR-FE — Prado/FJ/4Runner/Tacoma
            '1GR-FE' => [
                '2003-2009 Toyota Land Cruiser Prado 4.0L V6',
                '2006-2014 Toyota FJ Cruiser 4.0L V6',
                '2005-2015 Toyota Tacoma 4.0L V6',
                '2003-2009 Toyota 4Runner 4.0L V6',
                '2010-2019 Toyota 4Runner 4.0L V6',
                '2010-2017 Lexus GX460 (related platform)',
            ],

            // Diesel — 1KD/2KD
            '1KD-FTV' => [
                '2005-2015 Toyota Hilux 3.0L D4D Diesel',
                '2009-2018 Toyota Fortuner 3.0L Diesel',
                '2003-2009 Toyota Land Cruiser Prado 3.0L D',
                '2005-2015 Toyota HiAce 3.0L Diesel',
                '2003-2009 Toyota 4Runner 3.0L Diesel',
            ],
            '2KD-FTV' => [
                '2005-2015 Toyota Hilux 2.5L Diesel',
                '2004-2015 Toyota Innova 2.5L Diesel',
                '2005-2015 Toyota HiAce 2.5L Diesel',
                '2004-2010 Toyota Land Cruiser Prado 2.7L',
            ],

            // ── HONDA ─────────────────────────────────────────────

            'K24A' => [
                '2003-2007 Honda Accord 2.4L (K24A)',
                '2002-2006 Honda CR-V 2.4L (K24A)',
                '2003-2011 Honda Element 2.4L (K24A)',
                '2004-2008 Acura TSX 2.4L (K24A)',
            ],
            'K24Z1' => [
                '2007-2011 Honda CR-V 2.4L (K24Z1)',
                '2008-2012 Honda Accord 2.4L (K24Z3)',
            ],
            'MCTA' => [
                '2003-2007 Honda Accord 2.4L AT (MCTA)',
                '2002-2006 Honda CR-V AT',
                '2003-2011 Honda Element AT',
            ],
            'BGRA' => [
                '2008-2014 Honda Accord 2.4L AT (BGRA)',
                '2007-2011 Honda CR-V AT',
                '2009-2015 Honda Pilot 3.5L AT',
            ],
            'R18A' => [
                '2006-2011 Honda Civic 1.8L (R18A)',
                '2006-2011 Honda Civic EX/LX/SI 1.8L',
                '2009-2015 Honda Civic 1.8L (various markets)',
            ],
            'D17A' => [
                '2001-2005 Honda Civic 1.7L (D17A)',
                '2001-2005 Honda Civic LX/EX 1.7L',
            ],
            'J35A' => [
                '2003-2007 Honda Accord V6 3.5L (J35A)',
                '2005-2010 Honda Odyssey 3.5L (J35A)',
                '2003-2008 Honda Pilot 3.5L (J35A)',
                '2003-2007 Acura MDX 3.5L (J35A)',
                '2004-2008 Honda Ridgeline 3.5L (J35A)',
            ],
            'J30A' => [
                '1998-2002 Honda Accord V6 3.0L (J30A)',
                '1999-2004 Honda Odyssey 3.5L (J30A related)',
                '2001-2003 Acura TL 3.2L (related)',
            ],
            'BAXA' => [
                '1998-2002 Honda Accord 2.3L/3.0L AT',
                '1999-2004 Honda Odyssey AT',
                '2001-2003 Acura TL AT',
            ],

            // ── NISSAN ────────────────────────────────────────────

            'QR25DE' => [
                '2002-2018 Nissan Altima 2.5L (QR25DE)',
                '2000-2006 Nissan X-Trail 2.5L (QR25DE)',
                '2002-2006 Nissan Sentra SE-R 2.5L (QR25DE)',
                '2005-2012 Nissan Frontier 2.5L (QR25DE)',
            ],
            'VQ35DE' => [
                '2002-2008 Nissan Altima 3.5L V6 (VQ35DE)',
                '2004-2008 Nissan Maxima 3.5L (VQ35DE)',
                '2003-2008 Nissan Murano 3.5L (VQ35DE)',
                '2003-2009 Nissan 350Z 3.5L (VQ35DE)',
                '2003-2007 Infiniti G35 3.5L (VQ35DE)',
                '2003-2007 Infiniti FX35 3.5L (VQ35DE)',
                '2003-2008 Infiniti M35 3.5L (VQ35DE)',
            ],
            'MR20DE' => [
                '2007-2012 Nissan Sentra 2.0L (MR20DE)',
                '2004-2012 Nissan Tiida 2.0L (MR20DE)',
                '2006-2012 Nissan Livina 2.0L (MR20DE)',
                '2007-2013 Nissan Sylphy 2.0L (MR20DE)',
                '2007-2012 Nissan X-Trail 2.0L (MR20DE)',
            ],
            'HR16DE' => [
                '2004-2012 Nissan Tiida 1.6L (HR16DE)',
                '2012-2019 Nissan Almera 1.5L (HR16DE)',
                '2006-2013 Nissan Note 1.6L (HR16DE)',
                '2012-2019 Nissan Versa 1.6L (HR16DE)',
            ],
            'GA16DE' => [
                '1995-2006 Nissan Almera 1.6L (GA16DE)',
                '1995-2006 Nissan Sunny 1.6L (GA16DE)',
                '1995-2006 Nissan Sentra 1.6L (GA16DE)',
            ],
            'RE5R05A' => [
                '2004-2013 Nissan Armada 5.6L AT',
                '2004-2012 Nissan Pathfinder AT',
                '2003-2007 Nissan Murano AT',
                '2004-2010 Infiniti QX56 AT',
                '2003-2008 Infiniti FX45/FX35 AT',
                '2002-2008 Nissan Maxima 3.5L AT',
                '2003-2009 Nissan 350Z AT',
            ],

            // ── HYUNDAI / KIA ──────────────────────────────────────

            'G4KD' => [
                '2011-2016 Hyundai Elantra 2.0L (G4KD)',
                '2010-2015 Hyundai Tucson 2.0L (G4KD)',
                '2010-2015 Kia Sportage 2.0L (G4KD)',
                '2011-2015 Kia Optima 2.0L (G4KD)',
                '2012-2017 Kia Soul 2.0L (G4KD)',
            ],
            'G4KC' => [
                '2006-2010 Hyundai Sonata 2.4L (G4KC)',
                '2006-2010 Hyundai Santa Fe 2.4L (G4KC)',
                '2006-2010 Kia Optima 2.4L (G4KC)',
                '2007-2010 Kia Rondo 2.4L (G4KC)',
            ],
            'G4FC' => [
                '2006-2017 Hyundai Accent 1.6L (G4FC)',
                '2006-2017 Kia Rio 1.6L (G4FC)',
                '2006-2011 Hyundai Verna 1.6L (G4FC)',
                '2012-2017 Hyundai i20 1.6L (G4FC)',
            ],
            'A6MF1' => [
                '2010-2015 Hyundai Sonata 2.4L 6AT',
                '2010-2015 Kia Optima 2.4L 6AT',
                '2010-2015 Hyundai Santa Fe 6AT',
                '2010-2015 Kia Sportage 6AT',
                '2011-2016 Hyundai Elantra 6AT',
            ],
            'A4CF1' => [
                '2006-2011 Hyundai Accent 1.6L 4AT',
                '2006-2011 Kia Rio 1.6L 4AT',
                '2006-2011 Hyundai Verna 4AT',
            ],

            // ── MERCEDES ──────────────────────────────────────────

            'M271' => [
                '2002-2014 Mercedes C180 Kompressor',
                '2002-2014 Mercedes C200 Kompressor',
                '2002-2009 Mercedes E200 Kompressor',
                '2004-2011 Mercedes SLK200',
                '2004-2009 Mercedes CLK200',
            ],
            'M272' => [
                '2005-2011 Mercedes C280/C300 3.0L V6',
                '2005-2009 Mercedes E350 3.5L V6',
                '2005-2011 Mercedes ML350 3.5L V6',
                '2005-2013 Mercedes GL350 3.5L V6',
                '2005-2011 Mercedes S350 V6',
                '2005-2009 Mercedes CLK350 V6',
            ],
            'M276' => [
                '2011-2018 Mercedes C350 V6',
                '2009-2016 Mercedes E350 3.5L V6',
                '2011-2016 Mercedes ML350 3.5L V6',
                '2012-2018 Mercedes GL350 3.0L V6',
            ],
            'M112' => [
                '1997-2005 Mercedes E320 3.2L V6',
                '1997-2005 Mercedes ML320 3.2L V6',
                '1997-2003 Mercedes CLK320 3.2L V6',
                '2000-2006 Mercedes S320 V6',
                '1999-2006 Mercedes G320 V6',
            ],
            'M113' => [
                '1998-2006 Mercedes E500 5.0L V8',
                '1998-2006 Mercedes S500 5.0L V8',
                '1998-2005 Mercedes ML500 5.0L V8',
                '1999-2006 Mercedes CL500 V8',
                '1997-2003 Mercedes CLK430 4.3L V8',
            ],
            '722.6' => [
                '1996-2005 Mercedes E-Class W210 5G-Tronic',
                '1998-2005 Mercedes ML-Class W163 5G-Tronic',
                '1999-2006 Mercedes S-Class W220 5G-Tronic',
                '2000-2005 Mercedes CLK W208 5G-Tronic',
                '2002-2007 Mercedes C-Class W203 5G-Tronic',
            ],
            '722.9' => [
                '2003-2013 Mercedes E-Class W211/W212 7G-Tronic',
                '2005-2012 Mercedes ML-Class W164 7G-Tronic',
                '2005-2013 Mercedes GL-Class X164 7G-Tronic',
                '2006-2013 Mercedes C-Class W204 7G-Tronic',
                '2006-2009 Mercedes CLS-Class 7G-Tronic',
                '2006-2013 Mercedes SLK/SL 7G-Tronic',
            ],

            // ── FORD ──────────────────────────────────────────────

            'Duratec20' => [
                '2000-2011 Ford Focus 2.0L Duratec',
                '2005-2011 Ford Mondeo 2.0L',
                '2004-2009 Mazda 3 2.0L (shared platform)',
                '2004-2009 Mazda 6 2.0L (shared platform)',
            ],
            'Duratec30' => [
                '1996-2007 Ford Taurus 3.0L V6',
                '2006-2009 Ford Fusion V6 3.0L',
                '2001-2007 Ford Escape V6 3.0L',
                '2000-2007 Mercury Sable 3.0L V6',
            ],
            '4F27E' => [
                '2000-2011 Ford Focus AT (4F27E)',
                '2006-2009 Ford Fusion 2.3L AT',
                '2006-2012 Mercury Milan AT',
                '2004-2009 Mazda 3 AT (shared)',
                '2003-2008 Mazda 6 AT (shared)',
            ],
            '6F35' => [
                '2009-2012 Ford Escape 2.5L AT (6F35)',
                '2010-2012 Ford Fusion 2.5L AT',
                '2010-2013 Ford Edge 2.0L AT',
                '2010-2012 Lincoln MKZ AT',
            ],
            'Modular46' => [
                '1997-2010 Ford F-150 4.6L/5.0L V8',
                '1997-2006 Ford Expedition 4.6L V8',
                '2002-2011 Ford Explorer 4.6L V8',
                '2003-2006 Lincoln Aviator 4.6L V8',
            ],
        ];
    }

    // =========================================================
    // nigerianMarketNames() — local Ladipo/Lagos market names
    // =========================================================
    public static function nigerianMarketNames(): array
    {
        return [
            // Transmission aliases common in Nigerian/Ghanaian market
            'U341E'   => '5-pin gear',
            'U241E'   => '13-pin gear',
            'U760E'   => '22-pin gear (direct)',
            'K310'    => '12-pin CVT',
            'K311'    => '12-pin CVT',
            'A750E'   => 'V6 automatic (Toyota)',
            '722.6'   => '5-speed Mercedes gear',
            '722.9'   => '7-speed Mercedes gear',
            '2AZ-FE'  => 'Camry engine 2.4',
            '2AR-FE'  => 'Camry engine 2.5',
            '2GR-FE'  => 'Camry V6 / Avalon engine',
            '1MZ-FE'  => 'Camry V6 3.0 (old)',
            '1ZZ-FE'  => 'Corolla 1.8 engine',
            '2ZR-FE'  => 'Corolla engine 09-13',
            'QR25DE'  => 'Altima 2.5 engine',
            'VQ35DE'  => 'Maxima/Altima V6 engine',
            'K24A'    => 'Accord 2.4 engine (Honda)',
            'J35A'    => 'Accord V6 / Odyssey engine',
            'G4KC'    => 'Sonata 2.4 engine (Hyundai)',
            'G4KD'    => 'Sonata/Tucson 2.0 engine',
            'M271'    => 'C180/C200 Mercedes engine',
            'M272'    => 'C280/E350 Mercedes V6 engine',
        ];
    }
}
