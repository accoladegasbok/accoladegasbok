<?php
// FILE: app/Data/OemDatabase.php
// ================================================================
// AUTHORITATIVE OEM ENGINE + TRANSMISSION + PIN COUNT DATABASE
// Built from:
// - Auto Zenith Parts actual inventory (PDF stock list)
// - Toyota/Lexus Transmission Master Database (Excel)
// - Ladipo Market pricing data (May 2026)
// - Field notes and pin count records (handwritten)
// - Car-Parts.com market research
// ================================================================

namespace App\Data;

class OemDatabase
{
    // ============================================================
    // MAIN LOOKUP — by make + model + year + cylinders
    // Returns: engine_code, transmission_code, pin_count,
    //          gear_alias, engine_l, drive_type, origin
    // ============================================================
    public static function lookup(
        string $make,
        string $model,
        int    $year,
        int    $cyl   = 0,
        float  $engL  = 0.0
    ): array {
        $make  = strtoupper(trim($make));
        $model = strtoupper(trim($model));
        $cyl   = (int) $cyl;

        $result = [
            'engine_code'       => null,
            'transmission_code' => null,
            'pin_count'         => null,
            'gear_alias'        => null,
            'engine_l'          => null,
            'drive_type'        => null,
            'market_note'       => null,
        ];

        // ── TOYOTA ──────────────────────────────────────────────
        if ($make === 'TOYOTA') {

            // CAMRY
            if (str_contains($model, 'CAMRY') || str_contains($model, 'SOLARA')) {
                if ($year >= 2018) {
                    if ($cyl === 6 || $engL >= 3.0) {
                        $result = ['engine_code'=>'2GR-FKS','transmission_code'=>'UA80E','pin_count'=>22,'gear_alias'=>'22-pin V6 (Camry 2018+ V6)','engine_l'=>3.5,'drive_type'=>'FWD','market_note'=>'Less common in Ladipo; confirm before ordering'];
                    } else {
                        $result = ['engine_code'=>'A25A-FKS','transmission_code'=>'UB80E','pin_count'=>30,'gear_alias'=>'30-pin (Camry 2018+ 4cyl)','engine_l'=>2.5,'drive_type'=>'FWD','market_note'=>'₦1,500,000–₦1,800,000 gear in Ladipo'];
                    }
                } elseif ($year >= 2012) {
                    if ($cyl === 6 || $engL >= 3.0) {
                        $result = ['engine_code'=>'2GR-FE','transmission_code'=>'U660E','pin_count'=>22,'gear_alias'=>'22-pin V6 (Camry/ES350/Avalon/Highlander)','engine_l'=>3.5,'drive_type'=>'FWD','market_note'=>'₦1,400,000 gear confirmed Ladipo May 2026'];
                    } else {
                        // 2AR-FE from 2010 onwards — KEY FIX
                        $result = ['engine_code'=>'2AR-FE','transmission_code'=>'U760E','pin_count'=>22,'gear_alias'=>'22-pin 4cyl (Camry 2012–2017 4cyl / RAV4 09–15 / Venza)','engine_l'=>2.5,'drive_type'=>'FWD','market_note'=>'₦1,350,000 gear; ₦1,350,000 engine confirmed Ladipo May 2026'];
                    }
                } elseif ($year >= 2010) {
                    if ($cyl === 6 || $engL >= 3.0) {
                        $result = ['engine_code'=>'2GR-FE','transmission_code'=>'U660E','pin_count'=>22,'gear_alias'=>'22-pin V6 (Camry V6)','engine_l'=>3.5,'drive_type'=>'FWD','market_note'=>'₦1,400,000 engine / ₦1,400,000 gear Ladipo'];
                    } else {
                        // 2010-2011 Camry 4-cyl = 2AR-FE (2.5L) NOT 2AZ-FE
                        $result = ['engine_code'=>'2AR-FE','transmission_code'=>'U760E','pin_count'=>22,'gear_alias'=>'22-pin 4cyl (Camry 2010–2011 4cyl)','engine_l'=>2.5,'drive_type'=>'FWD','market_note'=>'22-pin confirmed (see G74 stock: 2010-011 Camry 2AR 22PIN)'];
                    }
                } elseif ($year >= 2007) {
                    if ($cyl === 6 || $engL >= 3.0) {
                        $result = ['engine_code'=>'2GR-FE','transmission_code'=>'U660E','pin_count'=>22,'gear_alias'=>'22-pin V6 (Camry 2007–2011 V6)','engine_l'=>3.5,'drive_type'=>'FWD','market_note'=>'G65: 2007 Camry 13-pin 13-Pin gear ₦1,300,000 — NOTE: some 07 V6 units show 13-pin; verify by unit'];
                    } else {
                        // 2007-2009 Camry 4-cyl = 2AZ-FE 2.4L
                        $result = ['engine_code'=>'2AZ-FE','transmission_code'=>'U250E','pin_count'=>13,'gear_alias'=>'13-pin (Camry 2007–2009 2.4L)','engine_l'=>2.4,'drive_type'=>'FWD','market_note'=>'G18: 2007 Camry 13-pin ₦1,150,000 — confirmed Ladipo'];
                    }
                } elseif ($year >= 2005) {
                    if ($cyl === 6 || $engL >= 3.0) {
                        $result = ['engine_code'=>'3MZ-FE','transmission_code'=>'U151E','pin_count'=>13,'gear_alias'=>'13-pin V6 (Camry 2005–2006 3.3L / ES330 / Highlander)','engine_l'=>3.3,'drive_type'=>'FWD','market_note'=>'G78: ES330 3.3L 13-pin'];
                    } else {
                        $result = ['engine_code'=>'2AZ-FE','transmission_code'=>'U250E','pin_count'=>13,'gear_alias'=>'13-pin (Camry 2005–2006 2.4L)','engine_l'=>2.4,'drive_type'=>'FWD','market_note'=>'G110: 2005 Camry 13-pin — confirmed Ladipo'];
                    }
                } elseif ($year >= 2002) {
                    if ($cyl === 6 || $engL >= 2.8) {
                        $result = ['engine_code'=>'1MZ-FE','transmission_code'=>'U140E','pin_count'=>10,'gear_alias'=>'10-pin V6 (Camry 2002–2004 3.0L)','engine_l'=>3.0,'drive_type'=>'FWD','market_note'=>'G79: 2003 Camry 2.4L 10-pin — check engine'];
                    } else {
                        $result = ['engine_code'=>'2AZ-FE','transmission_code'=>'U241E','pin_count'=>10,'gear_alias'=>'10-pin (Camry 2002–2004 2.4L / RAV4 / Highlander)','engine_l'=>2.4,'drive_type'=>'FWD','market_note'=>'G68: 2003 Camry 10-pin ₦550,000; G81: 2004 Camry 10-pin'];
                    }
                } elseif ($year >= 1998) {
                    if ($cyl === 6) {
                        $result = ['engine_code'=>'1MZ-FE','transmission_code'=>'U140E','pin_count'=>10,'gear_alias'=>'10-pin V6 (Camry V6 1998–2001)','engine_l'=>3.0,'drive_type'=>'FWD','market_note'=>'E101: 2001 Camry 3.0L 1MZFE'];
                    } else {
                        $result = ['engine_code'=>'5S-FE','transmission_code'=>'A541E','pin_count'=>1,'gear_alias'=>'1-pin (Camry 2.2L 5S-FE)','engine_l'=>2.2,'drive_type'=>'FWD','market_note'=>'E63: 2001 Camry CE 2.2L 5SFE 1-pin'];
                    }
                }
            }

            // COROLLA
            elseif (str_contains($model, 'COROLLA') || str_contains($model, 'MATRIX') || str_contains($model, 'AURIS')) {
                if ($year >= 2019) {
                    $result = ['engine_code'=>'M20A-FKS','transmission_code'=>'K120 CVT','pin_count'=>18,'gear_alias'=>'CVT (Corolla 2019+)','engine_l'=>2.0,'drive_type'=>'FWD','market_note'=>'Less common in Ladipo currently'];
                } elseif ($year >= 2014) {
                    $result = ['engine_code'=>'2ZR-FE','transmission_code'=>'K313 CVT','pin_count'=>12,'gear_alias'=>'12-pin CVT (Corolla 2014–2019)','engine_l'=>1.8,'drive_type'=>'FWD','market_note'=>'G89: 2015 Corolla 2ZR 12-pin CVT ₦1,850,000'];
                } elseif ($year >= 2009) {
                    $result = ['engine_code'=>'2ZR-FE','transmission_code'=>'U341E','pin_count'=>9,'gear_alias'=>'9-pin (Corolla 2009–2013 / U341E)','engine_l'=>1.8,'drive_type'=>'FWD','market_note'=>'G13: 2009 Corolla 9-pin (without CVT) confirmed Ladipo; Image: 2009-2015 Corolla U341E JDM'];
                } elseif ($year >= 2003) {
                    $result = ['engine_code'=>'1ZZ-FE','transmission_code'=>'U341E','pin_count'=>5,'gear_alias'=>'5-pin (Corolla 2003–2008 / 1ZZ-FE)','engine_l'=>1.8,'drive_type'=>'FWD','market_note'=>'G83: 2004-2008 Corolla 5-pin; G88: 2002-2004 Corolla 5-pin'];
                } elseif ($year >= 2000) {
                    $result = ['engine_code'=>'1ZZ-FE','transmission_code'=>'U341E','pin_count'=>3,'gear_alias'=>'3-pin (Corolla 2000–2002)','engine_l'=>1.8,'drive_type'=>'FWD','market_note'=>'G85: 2002 Corolla 3-pin; G103: 1999 Corolla 3-pin'];
                }
            }

            // RAV4
            elseif (str_contains($model, 'RAV4') || str_contains($model, 'RAV-4')) {
                if ($year >= 2019) {
                    $result = ['engine_code'=>'A25A-FXS','transmission_code'=>'UB80E','pin_count'=>30,'gear_alias'=>'30-pin (RAV4 2019+ Hybrid)','engine_l'=>2.5,'drive_type'=>'AWD','market_note'=>'New gen; limited in Ladipo'];
                } elseif ($year >= 2013) {
                    $result = ['engine_code'=>'2AR-FE','transmission_code'=>'U760E','pin_count'=>22,'gear_alias'=>'22-pin 4cyl (RAV4 2013–2018 / Venza / Camry 4cyl)','engine_l'=>2.5,'drive_type'=>'FWD/AWD','market_note'=>'G62: RAV4 2012 13-pin (older); G74 confirms 22-pin 2AR'];
                } elseif ($year >= 2006) {
                    if ($cyl === 6 || $engL >= 3.0) {
                        $result = ['engine_code'=>'2GR-FE','transmission_code'=>'U151E','pin_count'=>13,'gear_alias'=>'13-pin V6 (RAV4 V6)','engine_l'=>3.5,'drive_type'=>'FWD/AWD','market_note'=>'V6 RAV4 less common'];
                    } else {
                        $result = ['engine_code'=>'2AZ-FE','transmission_code'=>'U241E','pin_count'=>10,'gear_alias'=>'10-pin (RAV4 2006–2012 2.4L)','engine_l'=>2.4,'drive_type'=>'FWD','market_note'=>'G102/G108: RAV4 1999 3-pin (older units differ)'];
                    }
                } elseif ($year >= 2001) {
                    $result = ['engine_code'=>'1AZ-FE','transmission_code'=>'U241E','pin_count'=>3,'gear_alias'=>'3-pin (RAV4 2001–2005 2.0L)','engine_l'=>2.0,'drive_type'=>'FWD','market_note'=>'G102: 1999 RAV4 2.0L 3-pin'];
                }
            }

            // HIGHLANDER
            elseif (str_contains($model, 'HIGHLANDER') || str_contains($model, 'KLUGER')) {
                if ($year >= 2014) {
                    if ($cyl === 6 || $engL >= 3.0) {
                        $result = ['engine_code'=>'2GR-FKS','transmission_code'=>'U660E','pin_count'=>22,'gear_alias'=>'22-pin V6 (Highlander 2014+ V6)','engine_l'=>3.5,'drive_type'=>'FWD/AWD','market_note'=>'Confirmed 22-pin family'];
                    } else {
                        $result = ['engine_code'=>'2AR-FE','transmission_code'=>'U760E','pin_count'=>22,'gear_alias'=>'22-pin 4cyl (Highlander 2014+ 4cyl)','engine_l'=>2.7,'drive_type'=>'FWD/AWD','market_note'=>null];
                    }
                } elseif ($year >= 2008) {
                    $result = ['engine_code'=>'2GR-FE','transmission_code'=>'U660E','pin_count'=>22,'gear_alias'=>'22-pin V6 (Highlander 2008–2013 / Sienna / Venza / ES350)','engine_l'=>3.5,'drive_type'=>'FWD/AWD','market_note'=>'G80: 2007 Highlander 3.3L 13-pin; 2008+ = 22-pin V6 ₦1,400,000'];
                } elseif ($year >= 2004) {
                    $result = ['engine_code'=>'3MZ-FE','transmission_code'=>'U151E','pin_count'=>13,'gear_alias'=>'13-pin V6 (Highlander 2004–2007 3.3L)','engine_l'=>3.3,'drive_type'=>'FWD/AWD','market_note'=>'G91: 2005 Highlander 3.3L 13-pin; G80: 2007 13-pin'];
                } elseif ($year >= 2001) {
                    $result = ['engine_code'=>'1MZ-FE','transmission_code'=>'U140E','pin_count'=>10,'gear_alias'=>'10-pin V6 (Highlander 2001–2003 3.0L)','engine_l'=>3.0,'drive_type'=>'FWD/AWD','market_note'=>null];
                }
            }

            // AVALON
            elseif (str_contains($model, 'AVALON')) {
                if ($year >= 2019) {
                    $result = ['engine_code'=>'A25A-FKS','transmission_code'=>'UA80E','pin_count'=>30,'gear_alias'=>'30-pin (Avalon 2019+)','engine_l'=>2.5,'drive_type'=>'FWD','market_note'=>null];
                } elseif ($year >= 2013) {
                    $result = ['engine_code'=>$cyl===6||$engL>=3.0?'2GR-FE':'2AR-FE','transmission_code'=>$cyl===6||$engL>=3.0?'U660E':'U760E','pin_count'=>22,'gear_alias'=>'22-pin (Avalon 2013–2018)','engine_l'=>$cyl===6||$engL>=3.0?3.5:2.5,'drive_type'=>'FWD','market_note'=>null];
                } elseif ($year >= 2005) {
                    $result = ['engine_code'=>'2GR-FE','transmission_code'=>'U660E','pin_count'=>22,'gear_alias'=>'22-pin V6 (Avalon 2005–2012 / ES350)','engine_l'=>3.5,'drive_type'=>'FWD','market_note'=>'G32: Avalon 2006 13-pin (some units 13-pin); G70: 2006 Avalon 2GR 13-pin ₦1,450,000 — VERIFY unit'];
                } elseif ($year >= 2000) {
                    $result = ['engine_code'=>'1MZ-FE','transmission_code'=>'U140E','pin_count'=>5,'gear_alias'=>'5-pin V6 (Avalon 2000–2004 3.0L)','engine_l'=>3.0,'drive_type'=>'FWD','market_note'=>'G106: 2000 Avalon 5-pin; G107: 2003 Avalon 5-pin; G86: 1997-1999 Avalon 5-pin'];
                } elseif ($year >= 1997) {
                    $result = ['engine_code'=>'1MZ-FE','transmission_code'=>'A541E','pin_count'=>5,'gear_alias'=>'5-pin V6 (Avalon 1997–1999)','engine_l'=>3.0,'drive_type'=>'FWD','market_note'=>'G86: 1997-1999 Avalon 5-pin IFE'];
                }
            }

            // SIENNA
            elseif (str_contains($model, 'SIENNA')) {
                if ($year >= 2021) {
                    $result = ['engine_code'=>'A25A-FXS','transmission_code'=>'K120 CVT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>2.5,'drive_type'=>'FWD/AWD','market_note'=>'New gen hybrid Sienna'];
                } elseif ($year >= 2011) {
                    $result = ['engine_code'=>'2GR-FE','transmission_code'=>'U660E','pin_count'=>22,'gear_alias'=>'22-pin V6 (Sienna 2011–2020 / Highlander / Venza)','engine_l'=>3.5,'drive_type'=>'FWD/AWD','market_note'=>'Same 22-pin family as Camry V6 / ES350'];
                } elseif ($year >= 2004) {
                    $result = ['engine_code'=>'3MZ-FE','transmission_code'=>'U151E','pin_count'=>13,'gear_alias'=>'13-pin V6 (Sienna 2004–2010 3.3L)','engine_l'=>3.3,'drive_type'=>'FWD/AWD','market_note'=>'G16: 2008 Sienna 13-pin; E102: 2006 Sienna 3MZ'];
                } elseif ($year >= 1998) {
                    $result = ['engine_code'=>'1MZ-FE','transmission_code'=>'A541E','pin_count'=>3,'gear_alias'=>'3-pin V6 (Sienna 1998–2003 3.0L)','engine_l'=>3.0,'drive_type'=>'FWD','market_note'=>'G87: 1998-2000 Sienna 3-pin; G125: 2002 Sienna 3-pin'];
                }
            }

            // YARIS / ECHO / VITZ
            elseif (str_contains($model, 'YARIS') || str_contains($model, 'ECHO') || str_contains($model, 'VITZ')) {
                if ($year >= 2020) {
                    $result = ['engine_code'=>'M15A-FKS','transmission_code'=>'CVT','pin_count'=>16,'gear_alias'=>'CVT (Yaris 2020+)','engine_l'=>1.5,'drive_type'=>'FWD','market_note'=>null];
                } elseif ($year >= 2006) {
                    $result = ['engine_code'=>'1NZ-FE','transmission_code'=>'K210 CVT','pin_count'=>9,'gear_alias'=>'9-pin (Yaris 2006–2016 / 1NZ-FE)','engine_l'=>1.5,'drive_type'=>'FWD','market_note'=>'G98: 2009 Yaris 1.5L 9-pin IFE'];
                } elseif ($year >= 1999) {
                    $result = ['engine_code'=>'1NZ-FE','transmission_code'=>'U340E','pin_count'=>null,'gear_alias'=>null,'engine_l'=>1.5,'drive_type'=>'FWD','market_note'=>null];
                }
            }

            // VENZA
            elseif (str_contains($model, 'VENZA')) {
                if ($year >= 2021) {
                    $result = ['engine_code'=>'A25A-FXS','transmission_code'=>'K120 CVT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>2.5,'drive_type'=>'AWD','market_note'=>'New gen Hybrid'];
                } else {
                    $result = $cyl === 6 || $engL >= 3.0
                        ? ['engine_code'=>'2GR-FE','transmission_code'=>'U660E','pin_count'=>22,'gear_alias'=>'22-pin V6 (Venza V6 / Camry / Highlander)','engine_l'=>3.5,'drive_type'=>'FWD/AWD','market_note'=>'₦1,400,000 gear Ladipo']
                        : ['engine_code'=>'2AR-FE','transmission_code'=>'U760E','pin_count'=>22,'gear_alias'=>'22-pin 4cyl (Venza 4cyl / Camry 4cyl / RAV4)','engine_l'=>2.7,'drive_type'=>'FWD/AWD','market_note'=>'₦1,350,000 gear Ladipo'];
                }
            }

            // 4RUNNER
            elseif (str_contains($model, '4RUNNER') || str_contains($model, '4 RUNNER')) {
                if ($year >= 2003) {
                    $result = ['engine_code'=>'1GR-FE','transmission_code'=>'A750F','pin_count'=>15,'gear_alias'=>'15-pin (4Runner 2003+ / Land Cruiser Prado)','engine_l'=>4.0,'drive_type'=>'4WD','market_note'=>'G105: 2006 4Runner 15-pin; ₦1,800,000–₦2,200,000 engine Ladipo'];
                } elseif ($year >= 1996) {
                    $result = ['engine_code'=>'5VZ-FE','transmission_code'=>'A340F','pin_count'=>3,'gear_alias'=>'3-pin (4Runner 1996–2002 3.4L)','engine_l'=>3.4,'drive_type'=>'4WD','market_note'=>'EG72: 5VZ-FE 4Runner/Land Cruiser'];
                }
            }

            // TACOMA
            elseif (str_contains($model, 'TACOMA')) {
                if ($year >= 2016) {
                    $result = ['engine_code'=>'2GR-FKS','transmission_code'=>'AC60F','pin_count'=>null,'gear_alias'=>null,'engine_l'=>3.5,'drive_type'=>'4WD','market_note'=>null];
                } elseif ($year >= 2005) {
                    $result = ['engine_code'=>'1GR-FE','transmission_code'=>'A750F','pin_count'=>8,'gear_alias'=>'8-pin (Tacoma 2005–2015 4.0L)','engine_l'=>4.0,'drive_type'=>'4WD','market_note'=>'G99: 2010 Tacoma 2.7L 8-pin IFE'];
                } elseif ($year >= 1997) {
                    $result = ['engine_code'=>'3RZ-FE','transmission_code'=>'A340F','pin_count'=>8,'gear_alias'=>'8-pin (Tacoma 1997–2004)','engine_l'=>2.7,'drive_type'=>'4WD','market_note'=>null];
                }
            }

            // TUNDRA
            elseif (str_contains($model, 'TUNDRA')) {
                if ($year >= 2007) {
                    $result = ['engine_code'=>$cyl===8?'3UR-FE':'2TR-FE','transmission_code'=>'AB60F','pin_count'=>5,'gear_alias'=>'5-pin (Tundra / Sequoia V8)','engine_l'=>$cyl===8?5.7:4.0,'drive_type'=>'4WD','market_note'=>'G100: 2002 Sequoia 5-pin; G113: 2000 Tundra 5-pin'];
                } elseif ($year >= 2000) {
                    $result = ['engine_code'=>'2UZ-FE','transmission_code'=>'A340F','pin_count'=>5,'gear_alias'=>'5-pin (Tundra/Sequoia 2000–2006)','engine_l'=>4.7,'drive_type'=>'4WD','market_note'=>'G100: 2002 Sequoia 5-pin IFE'];
                }
            }

            // SEQUOIA
            elseif (str_contains($model, 'SEQUOIA')) {
                $result = ['engine_code'=>$year>=2007?'3UR-FE':'2UZ-FE','transmission_code'=>$year>=2007?'AB60F':'A340F','pin_count'=>5,'gear_alias'=>'5-pin (Sequoia V8)','engine_l'=>$year>=2007?5.7:4.7,'drive_type'=>'4WD','market_note'=>'G100: 2002 Sequoia 5-pin'];
            }

            // LAND CRUISER / PRADO
            elseif (str_contains($model, 'LAND CRUISER') || str_contains($model, 'PRADO')) {
                if ($year >= 2010) {
                    $result = ['engine_code'=>'1GR-FE','transmission_code'=>'AB60F','pin_count'=>null,'gear_alias'=>null,'engine_l'=>4.0,'drive_type'=>'4WD','market_note'=>null];
                } elseif ($year >= 2003) {
                    $result = ['engine_code'=>'2UZ-FE','transmission_code'=>'A750F','pin_count'=>null,'gear_alias'=>null,'engine_l'=>4.7,'drive_type'=>'4WD','market_note'=>'EG72: 1998-2000 Landcruiser/4Runner 5VZ'];
                }
            }

            // PRIUS
            elseif (str_contains($model, 'PRIUS')) {
                $result = ['engine_code'=>$year>=2016?'2ZR-FXE':'1NZ-FXE','transmission_code'=>'P410 eCVT','pin_count'=>null,'gear_alias'=>'Hybrid eCVT','engine_l'=>1.8,'drive_type'=>'FWD','market_note'=>'EG15: 2006 Prius 1NZ-FXE; EG44: 2006 Prius 1NZ-FE Hybrid'];
            }

            // SCION (shares Toyota platforms)
            elseif (str_contains($model, 'SCION') || str_contains($model, 'XA') || str_contains($model, 'XB') || str_contains($model, 'TC')) {
                $result = ['engine_code'=>'1NZ-FE','transmission_code'=>'U341E','pin_count'=>8,'gear_alias'=>'8-pin (Scion / Yaris 1.5L)','engine_l'=>1.5,'drive_type'=>'FWD','market_note'=>'G4: Manual Scion; E88: 2006 Scion XB 1NZ 8-pin'];
            }
        }

        // ── LEXUS ────────────────────────────────────────────────
        elseif ($make === 'LEXUS') {

            // ES SERIES
            if (str_contains($model, 'ES')) {
                if ($year >= 2019) {
                    $result = ['engine_code'=>'A25A-FKS','transmission_code'=>'UB80E','pin_count'=>30,'gear_alias'=>'30-pin (ES 2019+)','engine_l'=>2.5,'drive_type'=>'FWD','market_note'=>null];
                } elseif ($year >= 2007) {
                    // ES350
                    $result = ['engine_code'=>'2GR-FE','transmission_code'=>'U660E','pin_count'=>22,'gear_alias'=>'22-pin V6 (ES350 / Camry V6 / Avalon / Highlander)','engine_l'=>3.5,'drive_type'=>'FWD','market_note'=>'G128: 2009 Lexus ES350 22-pin ₦1,500,000; E58: 2013 RX350 ₦3.2m engine'];
                } elseif ($year >= 2004) {
                    // ES330
                    $result = ['engine_code'=>'3MZ-FE','transmission_code'=>'U151E','pin_count'=>13,'gear_alias'=>'13-pin V6 (ES330 / Camry 3.3L / Highlander)','engine_l'=>3.3,'drive_type'=>'FWD','market_note'=>'G78: ES330 3.3L 13-pin; E98: ES330 3.3L M18024'];
                } elseif ($year >= 2002) {
                    // ES300
                    $result = ['engine_code'=>'1MZ-FE','transmission_code'=>'U140E','pin_count'=>13,'gear_alias'=>'13-pin V6 (ES300 / Camry V6)','engine_l'=>3.0,'drive_type'=>'FWD','market_note'=>'EG73: 2001 ES300 7-pin; E74: 2001 ES300 10-pin; EG75: 10-pin; E76: 5-pin — PIN VARIES by year/trim'];
                } elseif ($year >= 1997) {
                    $result = ['engine_code'=>'1MZ-FE','transmission_code'=>'U140E','pin_count'=>7,'gear_alias'=>'7-pin V6 (ES300 1997–2001 early units)','engine_l'=>3.0,'drive_type'=>'FWD','market_note'=>'EG73: 2001 ES300 7-pin; E99: 1998 ES300 10-pin — VERIFY unit'];
                }
            }

            // RX SERIES
            elseif (str_contains($model, 'RX')) {
                if ($year >= 2016) {
                    $result = ['engine_code'=>'2GR-FKS','transmission_code'=>'UA80E','pin_count'=>22,'gear_alias'=>'22-pin V6 (RX350 2016+)','engine_l'=>3.5,'drive_type'=>'AWD','market_note'=>null];
                } elseif ($year >= 2009) {
                    $result = ['engine_code'=>'2GR-FE','transmission_code'=>'U660E','pin_count'=>22,'gear_alias'=>'22-pin V6 (RX350 / ES350 / Camry V6)','engine_l'=>3.5,'drive_type'=>'AWD','market_note'=>'E58: 2013 RX350 ₦3.2m engine ₦1.7m gear Ladipo'];
                } elseif ($year >= 2004) {
                    $result = ['engine_code'=>'3MZ-FE','transmission_code'=>'U151F','pin_count'=>13,'gear_alias'=>'13-pin V6 (RX330 / Highlander 2004-07)','engine_l'=>3.3,'drive_type'=>'AWD','market_note'=>null];
                } elseif ($year >= 1999) {
                    $result = ['engine_code'=>'1MZ-FE','transmission_code'=>'U140F','pin_count'=>10,'gear_alias'=>'10-pin V6 (RX300)','engine_l'=>3.0,'drive_type'=>'AWD','market_note'=>null];
                }
            }

            // GS SERIES
            elseif (str_contains($model, 'GS')) {
                if ($year >= 2007) {
                    $result = ['engine_code'=>'2GR-FSE','transmission_code'=>'AA80E','pin_count'=>null,'gear_alias'=>null,'engine_l'=>3.5,'drive_type'=>'RWD','market_note'=>'G1: 2008 Lexus GS460 ₦350,000 gear; EG19: GS350 2GR-FSE'];
                } else {
                    $result = ['engine_code'=>'3UZ-FE','transmission_code'=>'A650E','pin_count'=>null,'gear_alias'=>null,'engine_l'=>4.3,'drive_type'=>'RWD','market_note'=>null];
                }
            }

            // LS SERIES
            elseif (str_contains($model, 'LS')) {
                $result = ['engine_code'=>$year>=2007?'1UR-FE':'3UZ-FE','transmission_code'=>$year>=2007?'AA80E':'A650E','pin_count'=>null,'gear_alias'=>null,'engine_l'=>$year>=2007?4.6:4.3,'drive_type'=>'RWD','market_note'=>null];
            }
        }

        // ── HONDA ────────────────────────────────────────────────
        elseif ($make === 'HONDA') {
            if (str_contains($model, 'ACCORD')) {
                if ($year >= 2018) {
                    $result = ['engine_code'=>$cyl===4?'K20C4':'J35Y6','transmission_code'=>'CVT/10AT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>$cyl===4?1.5:3.5,'drive_type'=>'FWD','market_note'=>null];
                } elseif ($year >= 2013) {
                    $result = ['engine_code'=>'K24W','transmission_code'=>'CVT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>2.4,'drive_type'=>'FWD','market_note'=>'E33: Accord 2.4L K24'];
                } elseif ($year >= 2008) {
                    $result = ['engine_code'=>'K24Z3','transmission_code'=>'5AT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>2.4,'drive_type'=>'FWD','market_note'=>null];
                } elseif ($year >= 2003) {
                    $result = ['engine_code'=>'K24A4','transmission_code'=>'BAXA/MAXA','pin_count'=>null,'gear_alias'=>null,'engine_l'=>2.4,'drive_type'=>'FWD','market_note'=>null];
                } elseif ($year >= 1998) {
                    $result = ['engine_code'=>$cyl===6?'J30A1':'F23A','transmission_code'=>'BAXA','pin_count'=>null,'gear_alias'=>null,'engine_l'=>$cyl===6?3.0:2.3,'drive_type'=>'FWD','market_note'=>'E38: 2000 Accord V6 J30A1'];
                }
            } elseif (str_contains($model, 'CIVIC')) {
                if ($year >= 2016) {
                    $result = ['engine_code'=>'L15B7','transmission_code'=>'CVT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>1.5,'drive_type'=>'FWD','market_note'=>null];
                } elseif ($year >= 2006) {
                    $result = ['engine_code'=>'R18A1','transmission_code'=>'5AT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>1.8,'drive_type'=>'FWD','market_note'=>'EG27: 2002 Civic D17A1 Manual'];
                }
            } elseif (str_contains($model, 'CR-V') || str_contains($model, 'CRV')) {
                $result = ['engine_code'=>$year>=2017?'L15B7':'K24Z','transmission_code'=>'CVT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>$year>=2017?1.5:2.4,'drive_type'=>'AWD','market_note'=>'G54: Honda CRV ₦400,000 gear'];
            } elseif (str_contains($model, 'PILOT')) {
                $result = ['engine_code'=>$year>=2016?'J35Y5':'J35A9','transmission_code'=>'9AT/5AT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>3.5,'drive_type'=>'AWD','market_note'=>'E28: 2006 Pilot J35A9; E57: 2005 Pilot; EG66: 2018 Pilot'];
            } elseif (str_contains($model, 'ODYSSEY')) {
                $result = ['engine_code'=>'J35Y6','transmission_code'=>'9AT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>3.5,'drive_type'=>'FWD','market_note'=>'G39: 2005 Odyssey ₦400,000 gear'];
            } elseif (str_contains($model, 'FIT') || str_contains($model, 'JAZZ')) {
                $result = ['engine_code'=>'L13A/L15A','transmission_code'=>'CVT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>1.3,'drive_type'=>'FWD','market_note'=>'EG22: Honda Fit 2016 ₦350,000 gear'];
            }
        }

        // ── NISSAN ───────────────────────────────────────────────
        elseif ($make === 'NISSAN') {
            if (str_contains($model, 'ALTIMA')) {
                if ($year >= 2019) {
                    $result = ['engine_code'=>'KR20DDET','transmission_code'=>'Xtronic CVT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>2.0,'drive_type'=>'FWD','market_note'=>null];
                } else {
                    $result = ['engine_code'=>'QR25DE','transmission_code'=>'RE0F10D','pin_count'=>10,'gear_alias'=>'10-pin (Altima/Sentra)','engine_l'=>2.5,'drive_type'=>'FWD','market_note'=>'EG71: 2015 Altima 10-pin ₦900,000 gear'];
                }
            } elseif (str_contains($model, 'MAXIMA')) {
                $result = ['engine_code'=>'VQ35DE','transmission_code'=>'RE5F22A','pin_count'=>null,'gear_alias'=>null,'engine_l'=>3.5,'drive_type'=>'FWD','market_note'=>'EG23: 2005 Maxima VQ35DE ₦400,000 gear'];
            } elseif (str_contains($model, 'PATHFINDER') || str_contains($model, 'MURANO') || str_contains($model, 'QUEST')) {
                $result = ['engine_code'=>'VQ35DE','transmission_code'=>'Xtronic CVT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>3.5,'drive_type'=>'AWD','market_note'=>'EG47: Nissan Quest; EG69: 2003 Pathfinder'];
            } elseif (str_contains($model, 'SENTRA')) {
                $result = ['engine_code'=>'MRA8DE','transmission_code'=>'Xtronic CVT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>1.8,'drive_type'=>'FWD','market_note'=>'E11/E18: Sentra 2013-2017 MRA8DE'];
            } elseif (str_contains($model, 'XTERRA')) {
                $result = ['engine_code'=>'VQ40DE','transmission_code'=>'RE5R05A','pin_count'=>null,'gear_alias'=>null,'engine_l'=>4.0,'drive_type'=>'4WD','market_note'=>'G67: 2009 Xterra 4.0L gear ₦500,000'];
            }
        }

        // ── HYUNDAI ──────────────────────────────────────────────
        elseif ($make === 'HYUNDAI') {
            if (str_contains($model, 'ELANTRA')) {
                if ($year >= 2021) {
                    $result = ['engine_code'=>'G4NL','transmission_code'=>'iMT/DCT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>2.0,'drive_type'=>'FWD','market_note'=>null];
                } elseif ($year >= 2017) {
                    $result = ['engine_code'=>'G4FG','transmission_code'=>'6AT','pin_count'=>15,'gear_alias'=>'15-pin (Elantra 2017+ / Hyundai Nu)','engine_l'=>2.0,'drive_type'=>'FWD','market_note'=>'EG85: 2017 Elantra Nu-G4NA 15-pin ₦750,000 gear'];
                } elseif ($year >= 2011) {
                    $result = ['engine_code'=>'G4FG','transmission_code'=>'6AT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>1.8,'drive_type'=>'FWD','market_note'=>'G61: Elantra 2010 ₦400,000 gear'];
                } elseif ($year >= 2007) {
                    $result = ['engine_code'=>'G4GC','transmission_code'=>'4AT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>2.0,'drive_type'=>'FWD','market_note'=>'E16/E17: Elantra 2008-2010 G4GC ₦800,000'];
                }
            } elseif (str_contains($model, 'SONATA')) {
                if ($year >= 2015) {
                    $result = ['engine_code'=>'G4NL','transmission_code'=>'8AT','pin_count'=>16,'gear_alias'=>'16-pin (Sonata 2015+ / Hyundai/Kia)','engine_l'=>2.4,'drive_type'=>'FWD','market_note'=>'G64: 2015 Sonata 16-pin; G3/G20/G31/G38/G40/G50/G52/G53: Sonata gear ₦450,000 Ladipo'];
                } elseif ($year >= 2011) {
                    $result = ['engine_code'=>'G4KJ','transmission_code'=>'6AT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>2.4,'drive_type'=>'FWD','market_note'=>'E39: 2011-2014 Sonata Theta II ₦1,350,000'];
                }
            } elseif (str_contains($model, 'TUCSON')) {
                $result = ['engine_code'=>$year>=2022?'G4NL':'G4KH','transmission_code'=>'8AT/DCT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>2.4,'drive_type'=>'FWD/AWD','market_note'=>'G12: 2014 Tucson ₦700,000 gear'];
            } elseif (str_contains($model, 'SANTA FE')) {
                $result = ['engine_code'=>'G6DM','transmission_code'=>'8AT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>3.5,'drive_type'=>'AWD','market_note'=>null];
            }
        }

        // ── KIA ──────────────────────────────────────────────────
        elseif ($make === 'KIA') {
            if (str_contains($model, 'OPTIMA') || str_contains($model, 'K5')) {
                $result = ['engine_code'=>'G4NL','transmission_code'=>'8AT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>2.4,'drive_type'=>'FWD','market_note'=>'G44/G45: Hyundai/Kia ₦450,000 gear'];
            } elseif (str_contains($model, 'SPORTAGE')) {
                $result = ['engine_code'=>$year>=2023?'G4NL':'G4KH','transmission_code'=>'8AT/DCT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>2.4,'drive_type'=>'AWD','market_note'=>null];
            } elseif (str_contains($model, 'SORENTO')) {
                $result = ['engine_code'=>$cyl===6?'G6DM':'G4FJ','transmission_code'=>'8AT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>$cyl===6?3.5:2.4,'drive_type'=>'AWD','market_note'=>null];
            } elseif (str_contains($model, 'SPECTRA')) {
                $result = ['engine_code'=>'G4GC','transmission_code'=>'4AT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>2.0,'drive_type'=>'FWD','market_note'=>'G7: 2005 Kia Spectra ₦550,000 gear'];
            } elseif (str_contains($model, 'RONDO')) {
                $result = ['engine_code'=>'G6EA','transmission_code'=>'5AT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>2.7,'drive_type'=>'FWD','market_note'=>'EG84: 2005 Kia Rondo V6 ₦450,000 gear'];
            } elseif (str_contains($model, 'SORENTO') && $year <= 2006) {
                $result = ['engine_code'=>'G6CU','transmission_code'=>'5AT','pin_count'=>null,'gear_alias'=>null,'engine_l'=>3.5,'drive_type'=>'4WD','market_note'=>'G127: 2004 KIA Sorento 3.5L 4x4 ₦650,000'];
            }
        }

        // ── MERCEDES-BENZ ────────────────────────────────────────
        elseif (in_array($make, ['MERCEDES-BENZ', 'MERCEDES', 'BENZ', 'MB'])) {
            if ($year >= 2020) {
                $result = ['engine_code'=>'M254','transmission_code'=>'9G-TRONIC','pin_count'=>null,'gear_alias'=>null,'engine_l'=>2.0,'drive_type'=>'RWD/AWD','market_note'=>null];
            } elseif ($year >= 2011) {
                $result = ['engine_code'=>'M274','transmission_code'=>'7G-TRONIC/9G-TRONIC','pin_count'=>null,'gear_alias'=>null,'engine_l'=>2.0,'drive_type'=>'RWD/AWD','market_note'=>null];
            } elseif ($year >= 2007) {
                $result = ['engine_code'=>'M272','transmission_code'=>'7G-TRONIC','pin_count'=>12,'gear_alias'=>'12-pin (Mercedes C/E 2007+)','engine_l'=>3.5,'drive_type'=>'RWD','market_note'=>'EG25/EG26/EG45: 2009 E350 M272; EG100: C280 12-pin ₦600,000 gear'];
            } elseif ($year >= 2000) {
                $result = ['engine_code'=>'M112','transmission_code'=>'5G-TRONIC','pin_count'=>null,'gear_alias'=>null,'engine_l'=>3.2,'drive_type'=>'RWD','market_note'=>'EG68: 2004 CLK320 M112-E32'];
            }
        }

        // ── FORD ─────────────────────────────────────────────────
        elseif ($make === 'FORD') {
            if (str_contains($model, 'FUSION')) {
                $result = ['engine_code'=>'Duratec I4','transmission_code'=>'6F35','pin_count'=>null,'gear_alias'=>null,'engine_l'=>2.5,'drive_type'=>'FWD','market_note'=>'E12: Ford Fusion 2013-2017 Duratec ₦650,000'];
            } elseif (str_contains($model, 'EXPLORER')) {
                $result = ['engine_code'=>'3.0L EcoBoost','transmission_code'=>'10R60','pin_count'=>null,'gear_alias'=>null,'engine_l'=>3.0,'drive_type'=>'AWD','market_note'=>null];
            } elseif (str_contains($model, 'ESCAPE')) {
                $result = ['engine_code'=>'Duratec 2.3L','transmission_code'=>'6F35','pin_count'=>10,'gear_alias'=>'10-pin (Ford Escape)','engine_l'=>2.3,'drive_type'=>'FWD/AWD','market_note'=>'EG70: 2010 Ford Escape 10-pin'];
            } elseif (str_contains($model, 'EXPEDITION')) {
                $result = ['engine_code'=>'5.4L Triton V8','transmission_code'=>'4R75W','pin_count'=>null,'gear_alias'=>null,'engine_l'=>5.4,'drive_type'=>'4WD','market_note'=>'G27: Ford Expedition 2003 ₦600,000 gear'];
            }
        }

        // ── INFINITI ─────────────────────────────────────────────
        elseif ($make === 'INFINITI') {
            $result = ['engine_code'=>'VQ35DE','transmission_code'=>'RE5R05A','pin_count'=>null,'gear_alias'=>null,'engine_l'=>3.5,'drive_type'=>'RWD/AWD','market_note'=>'EG29: G37 VQ35HR; EG55/EG56: M35; E49: QX60 2018; G55: QX60 2018 ₦650,000'];
        }

        // ── BMW ──────────────────────────────────────────────────
        elseif ($make === 'BMW') {
            $result = ['engine_code'=>'N53B30','transmission_code'=>'ZF 6HP','pin_count'=>null,'gear_alias'=>null,'engine_l'=>3.0,'drive_type'=>'RWD','market_note'=>'EG30: 2009 BMW 530 N53B30 ₦1,950,000 engine'];
        }

        return $result;
    }
// ============================================================
    // ENGINE OPTIONS — for a given Make/Model/Year, returns the
    // distinct engine configurations our lookup() logic already
    // knows about (since lookup() branches on cylinder count /
    // displacement). Used to populate a dropdown on manual entry
    // forms, the same way a VIN decode reveals ONE specific engine
    // — this reveals ALL engines that vehicle could have come with.
    // ============================================================
    public static function engineOptions(string $make, string $model, int $year): array
    {
        $probes = [
            ['cyl' => 4, 'engL' => 0],
            ['cyl' => 6, 'engL' => 0],
            ['cyl' => 8, 'engL' => 0],
        ];

        $options = [];
        $seen    = [];

        foreach ($probes as $p) {
            $r = self::lookup($make, $model, $year, $p['cyl'], $p['engL']);
            if (!$r['engine_code']) continue;

            $key = $r['engine_code']; // dedupe by resulting engine code
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            $cylLabel = $p['cyl'] ? "{$p['cyl']}-Cyl" : '';
            $label = trim(($r['engine_l'] ? $r['engine_l'] . 'L ' : '') . $cylLabel . " ({$r['engine_code']})");

            $options[] = [
                'engine_l'    => $r['engine_l'],
                'cyl'         => $p['cyl'],
                'engine_code' => $r['engine_code'],
                'label'       => $label,
            ];
        }

        return $options;
    }

    // ============================================================
    // PIN COUNT REFERENCE TABLE
    // From field notes, Ladipo market, actual stock records
    // ============================================================
    public static function pinCounts(): array
    {
        return [
            // Toyota/Lexus Transmission Pin Counts (Nigerian Market)
            'U341E'   => 9,   // Corolla 2009-2013 (without CVT)
            'K311'    => 9,   // Corolla CVT 2009-2013
            'K313'    => 12,  // Corolla CVT 2014-2019
            'U340E'   => 10,  // Yaris 1NZ old
            'U241E'   => 10,  // Camry 2002-2004 / RAV4 10-pin
            'U250E'   => 13,  // Camry 2005-2009 2AZ 13-pin
            'U151E'   => 13,  // Highlander/Sienna V6 13-pin
            'U151F'   => 13,  // Highlander AWD 13-pin
            'U140E'   => 10,  // Camry/ES300 V6 old
            'U140F'   => 10,  // Highlander/RX300 AWD
            'U660E'   => 22,  // Camry V6 / ES350 / Avalon / Highlander 22-pin
            'U660F'   => 22,  // RX350 AWD 22-pin
            'U760E'   => 22,  // Camry 4cyl 2012+ / Avalon / RAV4 22-pin
            'U760F'   => 22,  // RAV4 AWD
            'A750E'   => 13,  // 4Runner / Tacoma 13-pin truck
            'A750F'   => 13,  // Land Cruiser Prado truck
            'A340E'   => 8,   // 4Runner old / Tacoma old
            'A340F'   => 8,   // 4Runner 4WD old
            'A541E'   => 5,   // Avalon/Camry old
            // Special Nigerian market notes
            '1MZ-FE'  => null, // Pin varies: 3/5/7/10/13 — must verify unit
            '3MZ-FE'  => 13,   // Highlander/Sienna/ES330 13-pin
        ];
    }

    // ============================================================
    // INTERCHANGE / COMPATIBILITY
    // Parts that cross-fit between models
    // ============================================================
    public static function interchange(): array
    {
        return [
            // 22-pin V6 Transmission (U660E) — fits all these
            'U660E' => [
                '2007-2017 Toyota Camry V6',
                '2007-2018 Lexus ES350',
                '2005-2012 Toyota Avalon',
                '2008-2013 Toyota Highlander V6',
                '2011-2017 Toyota Sienna V6',
                '2009-2015 Toyota Venza V6',
                '2010-2015 Lexus RX350',
                '2006-2012 Lexus GS350',
            ],
            // 22-pin 4cyl Transmission (U760E) — fits all these
            'U760E' => [
                '2010-2017 Toyota Camry 4cyl (2AR-FE)',
                '2009-2018 Toyota RAV4 4cyl',
                '2009-2015 Toyota Venza 4cyl',
                '2013-2018 Toyota Avalon 4cyl',
            ],
            // 13-pin Camry (U250E) — fits all these
            'U250E' => [
                '2005-2009 Toyota Camry 4cyl (2AZ-FE)',
                '2006-2008 Toyota Camry (2AZ)',
                '2009 Toyota Camry (some units)',
                '2007-2009 Toyota Corolla 2AZ',
            ],
            // 10-pin 2AZ (U241E) — fits all these
            'U241E' => [
                '2002-2004 Toyota Camry 2.4L',
                '2002-2006 Toyota RAV4 2.4L',
                '2002-2005 Toyota Highlander 2.4L',
                '2000-2006 Toyota Solara',
                '2003-2006 Toyota Camry LE/SE/XLE 2.4L',
            ],
            // 5-pin Corolla (U341E old)
            'U341E-5pin' => [
                '2003-2008 Toyota Corolla 1ZZ-FE',
                '2003-2008 Toyota Matrix 1ZZ-FE',
            ],
            // 9-pin Corolla (U341E new)
            'U341E-9pin' => [
                '2009-2013 Toyota Corolla 2ZR-FE',
                '2009-2013 Toyota Matrix 2ZR-FE',
            ],
            // 13-pin V6 (U151E) — Sienna/Highlander/RX330/ES330
            'U151E' => [
                '2004-2006 Toyota Sienna 3.3L 3MZ-FE',
                '2004-2007 Toyota Highlander 3.3L 3MZ-FE',
                '2004-2006 Lexus RX330',
                '2004-2006 Lexus ES330',
                '2005-2008 Toyota Camry Solara 3.3L',
            ],
            // 1MZ-FE — Camry/Avalon/ES300/Sienna/Highlander 3.0L
            '1MZ-FE' => [
                '1998-2003 Toyota Camry V6 3.0L',
                '1994-2004 Toyota Avalon 3.0L',
                '1997-2001 Lexus ES300',
                '1998-2003 Toyota Sienna 3.0L',
                '2001-2003 Toyota Highlander 3.0L',
                '2002-2003 Lexus ES300',
            ],
            // 2GR-FE — Camry/Avalon/Highlander/Sienna/ES350/RX350 3.5L
            '2GR-FE' => [
                '2007-2011 Toyota Camry V6 3.5L',
                '2012-2017 Toyota Camry V6 3.5L',
                '2005-2012 Toyota Avalon 3.5L',
                '2008-2013 Toyota Highlander V6 3.5L',
                '2011-2020 Toyota Sienna V6 3.5L',
                '2009-2015 Lexus RX350',
                '2007-2018 Lexus ES350',
                '2009-2015 Toyota Venza V6',
            ],
            // 2AR-FE — Camry/RAV4/Venza/Avalon 2.5L
            '2AR-FE' => [
                '2010-2017 Toyota Camry 4cyl 2.5L',
                '2009-2018 Toyota RAV4 4cyl',
                '2009-2015 Toyota Venza 4cyl',
                '2013-2018 Toyota Avalon 4cyl',
                '2009-2018 Lexus ES250',
            ],
            // 2AZ-FE — Camry/RAV4/Highlander 2.4L
            '2AZ-FE' => [
                '2002-2009 Toyota Camry 4cyl 2.4L',
                '2004-2008 Toyota RAV4',
                '2001-2007 Toyota Highlander 4cyl',
                '2005-2010 Scion tC',
                '2008-2015 Scion xB',
                '2000-2007 Toyota Solara 2.4L',
            ],
            // 2ZR-FE — Corolla/Matrix
            '2ZR-FE' => [
                '2009-2019 Toyota Corolla 1.8L',
                '2009-2014 Toyota Matrix 1.8L',
                '2009-2013 Pontiac Vibe',
            ],
            // 1ZZ-FE — Corolla/Matrix/Celica
            '1ZZ-FE' => [
                '2000-2008 Toyota Corolla 1.8L',
                '2003-2008 Toyota Matrix 1.8L',
                '2000-2005 Toyota Celica GT',
                '2003-2008 Pontiac Vibe',
            ],
        ];
    }

    // ============================================================
    // NIGERIAN MARKET PIN COUNT ALIASES
    // Common names used in Ladipo/Oshodi market
    // ============================================================
    public static function nigerianMarketNames(): array
    {
        return [
            '3-pin'  => 'Corolla 2000-2002 / Sienna 1998-2003 / RAV4 1999 / Avalon 1997',
            '5-pin'  => 'Corolla 2003-2008 / Avalon 2000-2004 / Sienna old / Matrix',
            '8-pin'  => 'Tacoma / Scion / Yaris 1NZ / 4Runner old / Sequoia old',
            '9-pin'  => 'Corolla 2009-2013 (2ZR without CVT) / Yaris 2006+',
            '10-pin' => 'Camry 2002-2004 2AZ / RAV4 2006-2012 / Highlander 2001-2003 / Camry V6 old 1MZ',
            '12-pin' => 'Corolla 2014-2019 CVT / Mercedes C280',
            '13-pin' => 'Camry 2005-2009 2AZ / Sienna 2004-2010 3MZ / Highlander 3MZ / RX330 / 4Runner / Avalon 2006-2012 (some units)',
            '15-pin' => '4Runner 2003+ 1GR / Elantra 2017+ Nu',
            '16-pin' => 'Sonata/Optima 2015+ / Hyundai/Kia modern',
            '22-pin' => 'Camry V6 2007+ / ES350 / Avalon V6 / Highlander 2008+ / Sienna 2011+ / RX350 / Camry 4cyl 2012+ (2AR) / RAV4 2013+ / Venza',
            '30-pin' => 'Camry 2018+ 4cyl / Avalon 2019+ / ES 2019+',
        ];
    }
}
