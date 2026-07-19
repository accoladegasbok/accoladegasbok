<?php
// FILE: database/seeders/ToyotaLexusPowertrainSeeder.php
//
// Seeds vehicle_powertrain_reference and transmission_families from
// toyota_lexus_model_year_transmission_master_2026.xlsx — 68 vehicle-
// year rows and 13 transmission-family rows, transcribed exactly from
// the source workbook (pandas-extracted, not hand-typed, to avoid
// transcription errors). Marked source='toyota_lexus_transmission_master_2026',
// verified=false — these are REFERENCE ranges, not market-confirmed
// facts like the Camry pin corrections already in OemDatabase.php.
// Flip 'verified' to true on a row only once staff confirm it against
// a real physical unit, same discipline as everything else this
// session.
//
// Run: php artisan db:seed --class=ToyotaLexusPowertrainSeeder

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ToyotaLexusPowertrainSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $vehicleRows = [
            ['make'=>'Toyota','model'=>'Camry','year_from'=>1998,'year_to'=>2001,'engine_code'=>'1MZ-FE 3.0','drive_type'=>'FWD','transmission_code'=>'U140E','speeds'=>'4','pin_count_min'=>10,'pin_count_max'=>10,'key_notes'=>'V6 branch'],
            ['make'=>'Toyota','model'=>'Camry','year_from'=>2002,'year_to'=>2005,'engine_code'=>'2AZ-FE 2.4','drive_type'=>'FWD','transmission_code'=>'U241E','speeds'=>'4','pin_count_min'=>10,'pin_count_max'=>13,'key_notes'=>'4-cyl branch'],
            ['make'=>'Toyota','model'=>'Camry','year_from'=>2002,'year_to'=>2004,'engine_code'=>'1MZ-FE 3.0','drive_type'=>'FWD','transmission_code'=>'U140E','speeds'=>'4','pin_count_min'=>10,'pin_count_max'=>10,'key_notes'=>'V6 branch'],
            ['make'=>'Toyota','model'=>'Camry','year_from'=>2005,'year_to'=>2006,'engine_code'=>'2AZ-FE 2.4','drive_type'=>'FWD','transmission_code'=>'U250E','speeds'=>'5','pin_count_min'=>13,'pin_count_max'=>13,'key_notes'=>'Transition into 5-speed 4-cyl'],
            ['make'=>'Toyota','model'=>'Camry','year_from'=>2005,'year_to'=>2006,'engine_code'=>'3MZ-FE 3.3','drive_type'=>'FWD','transmission_code'=>'U151E','speeds'=>'5','pin_count_min'=>13,'pin_count_max'=>16,'key_notes'=>'V6 branch before U660E era'],
            ['make'=>'Toyota','model'=>'Camry','year_from'=>2007,'year_to'=>2011,'engine_code'=>'2AZ-FE 2.4','drive_type'=>'FWD','transmission_code'=>'U250E','speeds'=>'5','pin_count_min'=>13,'pin_count_max'=>13,'key_notes'=>'Common field example'],
            ['make'=>'Toyota','model'=>'Camry','year_from'=>2007,'year_to'=>2011,'engine_code'=>'2GR-FE 3.5','drive_type'=>'FWD','transmission_code'=>'U660E','speeds'=>'6','pin_count_min'=>22,'pin_count_max'=>22,'key_notes'=>'Common field example; year alone is unsafe'],
            ['make'=>'Toyota','model'=>'Camry','year_from'=>2010,'year_to'=>2011,'engine_code'=>'2AR-FE 2.5','drive_type'=>'FWD','transmission_code'=>'U760E','speeds'=>'6','pin_count_min'=>16,'pin_count_max'=>20,'key_notes'=>'Later I4 6-speed'],
            ['make'=>'Toyota','model'=>'Camry','year_from'=>2012,'year_to'=>2017,'engine_code'=>'2AR-FE 2.5','drive_type'=>'FWD','transmission_code'=>'U760E/U761E','speeds'=>'6','pin_count_min'=>16,'pin_count_max'=>20,'key_notes'=>'Verify exact code by VIN'],
            ['make'=>'Toyota','model'=>'Camry','year_from'=>2012,'year_to'=>2017,'engine_code'=>'2GR-FE 3.5','drive_type'=>'FWD','transmission_code'=>'U660E/U760E era','speeds'=>'6','pin_count_min'=>18,'pin_count_max'=>22,'key_notes'=>'Verify by VIN and market'],
            ['make'=>'Toyota','model'=>'Camry','year_from'=>2018,'year_to'=>2026,'engine_code'=>'A25A-FKS 2.5','drive_type'=>'FWD','transmission_code'=>'UB80E/UA80E era','speeds'=>'8','pin_count_min'=>18,'pin_count_max'=>22,'key_notes'=>'Direct Shift 8AT era'],
            ['make'=>'Toyota','model'=>'Camry','year_from'=>2018,'year_to'=>2026,'engine_code'=>'2GR-FKS 3.5','drive_type'=>'FWD','transmission_code'=>'UA80E era','speeds'=>'8','pin_count_min'=>18,'pin_count_max'=>22,'key_notes'=>'V6 applications by market'],
            ['make'=>'Toyota','model'=>'Corolla','year_from'=>1997,'year_to'=>2002,'engine_code'=>'4A-FE / 7A-FE','drive_type'=>'FWD','transmission_code'=>'A245E/A246E era','speeds'=>'4','pin_count_min'=>8,'pin_count_max'=>10,'key_notes'=>'Older small-car automatic family'],
            ['make'=>'Toyota','model'=>'Corolla','year_from'=>2003,'year_to'=>2008,'engine_code'=>'1ZZ-FE 1.8','drive_type'=>'FWD','transmission_code'=>'U341E','speeds'=>'4','pin_count_min'=>10,'pin_count_max'=>12,'key_notes'=>'High-volume Corolla automatic'],
            ['make'=>'Toyota','model'=>'Corolla','year_from'=>2009,'year_to'=>2013,'engine_code'=>'2ZR-FE 1.8','drive_type'=>'FWD','transmission_code'=>'U341E / K311 CVT era','speeds'=>'4/CVT','pin_count_min'=>10,'pin_count_max'=>16,'key_notes'=>'Varies by market and trim'],
            ['make'=>'Toyota','model'=>'Corolla','year_from'=>2014,'year_to'=>2019,'engine_code'=>'1.8 / 2.0','drive_type'=>'FWD','transmission_code'=>'CVT K313/K311 era','speeds'=>'CVT','pin_count_min'=>12,'pin_count_max'=>16,'key_notes'=>'Check market'],
            ['make'=>'Toyota','model'=>'Corolla','year_from'=>2020,'year_to'=>2026,'engine_code'=>'M20A-FKS 2.0','drive_type'=>'FWD','transmission_code'=>'K120 Direct Shift CVT','speeds'=>'CVT','pin_count_min'=>16,'pin_count_max'=>20,'key_notes'=>'Modern Corolla CVT era'],
            ['make'=>'Toyota','model'=>'Yaris','year_from'=>1999,'year_to'=>2005,'engine_code'=>'1NZ-FE 1.5','drive_type'=>'FWD','transmission_code'=>'U340E','speeds'=>'4','pin_count_min'=>10,'pin_count_max'=>12,'key_notes'=>'Common small-car automatic'],
            ['make'=>'Toyota','model'=>'Yaris','year_from'=>2006,'year_to'=>2016,'engine_code'=>'1NZ-FE 1.5','drive_type'=>'FWD','transmission_code'=>'U340E / K210 CVT era','speeds'=>'4/CVT','pin_count_min'=>10,'pin_count_max'=>16,'key_notes'=>'Depends on market and trim'],
            ['make'=>'Toyota','model'=>'Yaris','year_from'=>2017,'year_to'=>2019,'engine_code'=>'1.3 / 1.5','drive_type'=>'FWD','transmission_code'=>'CVT era','speeds'=>'CVT','pin_count_min'=>12,'pin_count_max'=>16,'key_notes'=>'Check exact market spec'],
            ['make'=>'Toyota','model'=>'Yaris','year_from'=>2020,'year_to'=>2026,'engine_code'=>'M15A-FKS 1.5','drive_type'=>'FWD','transmission_code'=>'CVT era','speeds'=>'CVT','pin_count_min'=>16,'pin_count_max'=>20,'key_notes'=>'Modern Yaris line'],
            ['make'=>'Toyota','model'=>'Avalon','year_from'=>1997,'year_to'=>2004,'engine_code'=>'1MZ-FE 3.0','drive_type'=>'FWD','transmission_code'=>'A541E/U140E era','speeds'=>'4','pin_count_min'=>9,'pin_count_max'=>10,'key_notes'=>'Early Avalon V6'],
            ['make'=>'Toyota','model'=>'Avalon','year_from'=>2005,'year_to'=>2012,'engine_code'=>'2GR-FE 3.5','drive_type'=>'FWD','transmission_code'=>'U660E','speeds'=>'6','pin_count_min'=>22,'pin_count_max'=>22,'key_notes'=>'Large-sedan V6 example'],
            ['make'=>'Toyota','model'=>'Avalon','year_from'=>2013,'year_to'=>2018,'engine_code'=>'2AR-FE 2.5 / 2GR-FE 3.5','drive_type'=>'FWD','transmission_code'=>'U760E/U660E era','speeds'=>'6','pin_count_min'=>16,'pin_count_max'=>22,'key_notes'=>'Verify by engine'],
            ['make'=>'Toyota','model'=>'Avalon','year_from'=>2019,'year_to'=>2022,'engine_code'=>'A25A-FKS 2.5 / 3.5','drive_type'=>'FWD','transmission_code'=>'UA80E/UB80E era','speeds'=>'8','pin_count_min'=>18,'pin_count_max'=>22,'key_notes'=>'Current-gen non-hybrid era'],
            ['make'=>'Toyota','model'=>'RAV4','year_from'=>2001,'year_to'=>2005,'engine_code'=>'2.0/2.4','drive_type'=>'FWD','transmission_code'=>'U241E','speeds'=>'4','pin_count_min'=>10,'pin_count_max'=>13,'key_notes'=>'FWD'],
            ['make'=>'Toyota','model'=>'RAV4','year_from'=>2001,'year_to'=>2012,'engine_code'=>'2.0/2.4','drive_type'=>'AWD','transmission_code'=>'U140F/U250F era','speeds'=>'4/5','pin_count_min'=>10,'pin_count_max'=>13,'key_notes'=>'Check AWD suffix'],
            ['make'=>'Toyota','model'=>'RAV4','year_from'=>2006,'year_to'=>2012,'engine_code'=>'3.5','drive_type'=>'FWD/AWD','transmission_code'=>'U151E/U151F','speeds'=>'5','pin_count_min'=>13,'pin_count_max'=>16,'key_notes'=>'V6 branch'],
            ['make'=>'Toyota','model'=>'RAV4','year_from'=>2013,'year_to'=>2018,'engine_code'=>'2.5','drive_type'=>'FWD/AWD','transmission_code'=>'U760E/U760F era','speeds'=>'6','pin_count_min'=>16,'pin_count_max'=>20,'key_notes'=>'Verify drivetrain'],
            ['make'=>'Toyota','model'=>'RAV4','year_from'=>2019,'year_to'=>2026,'engine_code'=>'2.5','drive_type'=>'FWD/AWD','transmission_code'=>'UB80E/UB80F or CVT hybrid mix','speeds'=>'8','pin_count_min'=>18,'pin_count_max'=>22,'key_notes'=>'Non-hybrid 8AT; hybrids differ'],
            ['make'=>'Toyota','model'=>'Highlander','year_from'=>2001,'year_to'=>2003,'engine_code'=>'3.0','drive_type'=>'FWD','transmission_code'=>'U140E','speeds'=>'4','pin_count_min'=>10,'pin_count_max'=>10,'key_notes'=>'V6 FWD'],
            ['make'=>'Toyota','model'=>'Highlander','year_from'=>2001,'year_to'=>2003,'engine_code'=>'3.0','drive_type'=>'AWD','transmission_code'=>'U140F','speeds'=>'4','pin_count_min'=>10,'pin_count_max'=>10,'key_notes'=>'V6 AWD'],
            ['make'=>'Toyota','model'=>'Highlander','year_from'=>2004,'year_to'=>2010,'engine_code'=>'3.3/3.5','drive_type'=>'FWD/AWD','transmission_code'=>'U151E/U151F','speeds'=>'5','pin_count_min'=>13,'pin_count_max'=>16,'key_notes'=>'V6 era'],
            ['make'=>'Toyota','model'=>'Highlander','year_from'=>2011,'year_to'=>2019,'engine_code'=>'2.7/3.5','drive_type'=>'FWD/AWD','transmission_code'=>'U760/U660 family era','speeds'=>'6','pin_count_min'=>16,'pin_count_max'=>22,'key_notes'=>'Verify engine and drivetrain'],
            ['make'=>'Toyota','model'=>'Highlander','year_from'=>2020,'year_to'=>2026,'engine_code'=>'2.4T/2.5','drive_type'=>'FWD/AWD','transmission_code'=>'UA80/UA81 era or hybrid eCVT','speeds'=>'8','pin_count_min'=>18,'pin_count_max'=>22,'key_notes'=>'Non-hybrid vs hybrid split'],
            ['make'=>'Toyota','model'=>'4Runner','year_from'=>1997,'year_to'=>2002,'engine_code'=>'3.4','drive_type'=>'RWD/4WD','transmission_code'=>'A340E/A340F','speeds'=>'4','pin_count_min'=>8,'pin_count_max'=>10,'key_notes'=>'Truck/SUV classic'],
            ['make'=>'Toyota','model'=>'4Runner','year_from'=>2003,'year_to'=>2009,'engine_code'=>'4.0/4.7','drive_type'=>'RWD/4WD','transmission_code'=>'A750E/A750F','speeds'=>'5','pin_count_min'=>11,'pin_count_max'=>13,'key_notes'=>'Body-on-frame SUV'],
            ['make'=>'Toyota','model'=>'4Runner','year_from'=>2010,'year_to'=>2026,'engine_code'=>'4.0','drive_type'=>'RWD/4WD','transmission_code'=>'A750E/A750F/AC60 era by market','speeds'=>'5/6','pin_count_min'=>11,'pin_count_max'=>16,'key_notes'=>'Verify market'],
            ['make'=>'Toyota','model'=>'Tacoma','year_from'=>1997,'year_to'=>2004,'engine_code'=>'2.7/3.4','drive_type'=>'RWD/4WD','transmission_code'=>'A340E/A340F','speeds'=>'4','pin_count_min'=>8,'pin_count_max'=>10,'key_notes'=>'Pickup branch'],
            ['make'=>'Toyota','model'=>'Tacoma','year_from'=>2005,'year_to'=>2015,'engine_code'=>'4.0','drive_type'=>'RWD/4WD','transmission_code'=>'A750E/A750F','speeds'=>'5','pin_count_min'=>11,'pin_count_max'=>13,'key_notes'=>'Common truck transmission'],
            ['make'=>'Toyota','model'=>'Tacoma','year_from'=>2016,'year_to'=>2026,'engine_code'=>'2.7/3.5','drive_type'=>'RWD/4WD','transmission_code'=>'AC60E/AC60F era','speeds'=>'6','pin_count_min'=>13,'pin_count_max'=>16,'key_notes'=>'Later truck automatic'],
            ['make'=>'Toyota','model'=>'Tundra','year_from'=>2000,'year_to'=>2006,'engine_code'=>'4.7','drive_type'=>'RWD/4WD','transmission_code'=>'A340E/A340F/A650 era','speeds'=>'4/5','pin_count_min'=>8,'pin_count_max'=>10,'key_notes'=>'Verify exact code'],
            ['make'=>'Toyota','model'=>'Tundra','year_from'=>2007,'year_to'=>2021,'engine_code'=>'4.6/5.7','drive_type'=>'RWD/4WD','transmission_code'=>'AB60E/AB60F','speeds'=>'6','pin_count_min'=>13,'pin_count_max'=>16,'key_notes'=>'Full-size truck'],
            ['make'=>'Toyota','model'=>'Tundra','year_from'=>2022,'year_to'=>2026,'engine_code'=>'3.4T','drive_type'=>'RWD/4WD','transmission_code'=>'10-speed era','speeds'=>'10','pin_count_min'=>22,'pin_count_max'=>30,'key_notes'=>'Outside classic A/U families'],
            ['make'=>'Toyota','model'=>'Land Cruiser / Prado','year_from'=>1997,'year_to'=>2002,'engine_code'=>'4.5/4.7','drive_type'=>'RWD/4WD','transmission_code'=>'A442/A343/A340 family era','speeds'=>'4','pin_count_min'=>8,'pin_count_max'=>10,'key_notes'=>'Check market and chassis'],
            ['make'=>'Toyota','model'=>'Land Cruiser / Prado','year_from'=>2003,'year_to'=>2015,'engine_code'=>'4.0/4.7','drive_type'=>'4WD','transmission_code'=>'A750F','speeds'=>'5','pin_count_min'=>11,'pin_count_max'=>13,'key_notes'=>'Common Prado/LC100/120 era reference'],
            ['make'=>'Toyota','model'=>'Land Cruiser / Prado','year_from'=>2016,'year_to'=>2026,'engine_code'=>'2.8D / 4.0','drive_type'=>'4WD','transmission_code'=>'AC60F/AB60F era','speeds'=>'6','pin_count_min'=>13,'pin_count_max'=>16,'key_notes'=>'Market-specific'],
            ['make'=>'Lexus','model'=>'ES','year_from'=>1997,'year_to'=>2001,'engine_code'=>'3.0','drive_type'=>'FWD','transmission_code'=>'A541E/U140E era','speeds'=>'4','pin_count_min'=>9,'pin_count_max'=>10,'key_notes'=>'Early ES automatic'],
            ['make'=>'Lexus','model'=>'ES','year_from'=>2002,'year_to'=>2006,'engine_code'=>'3.0/3.3','drive_type'=>'FWD','transmission_code'=>'U151E','speeds'=>'5','pin_count_min'=>13,'pin_count_max'=>16,'key_notes'=>'ES300/330 era'],
            ['make'=>'Lexus','model'=>'ES','year_from'=>2007,'year_to'=>2018,'engine_code'=>'3.5','drive_type'=>'FWD','transmission_code'=>'U660E','speeds'=>'6','pin_count_min'=>22,'pin_count_max'=>22,'key_notes'=>'Common ES350 reference'],
            ['make'=>'Lexus','model'=>'ES','year_from'=>2019,'year_to'=>2026,'engine_code'=>'2.5/3.5','drive_type'=>'FWD','transmission_code'=>'UA80/UB80 era','speeds'=>'8','pin_count_min'=>18,'pin_count_max'=>22,'key_notes'=>'Modern ES non-hybrid'],
            ['make'=>'Lexus','model'=>'RX','year_from'=>1999,'year_to'=>2003,'engine_code'=>'3.0','drive_type'=>'FWD/AWD','transmission_code'=>'U140E/U140F','speeds'=>'4','pin_count_min'=>10,'pin_count_max'=>10,'key_notes'=>'RX300 era'],
            ['make'=>'Lexus','model'=>'RX','year_from'=>2004,'year_to'=>2008,'engine_code'=>'3.3','drive_type'=>'FWD/AWD','transmission_code'=>'U151E/U151F','speeds'=>'5','pin_count_min'=>13,'pin_count_max'=>16,'key_notes'=>'RX330 era'],
            ['make'=>'Lexus','model'=>'RX','year_from'=>2009,'year_to'=>2015,'engine_code'=>'3.5','drive_type'=>'FWD/AWD','transmission_code'=>'U660 family era','speeds'=>'6','pin_count_min'=>18,'pin_count_max'=>22,'key_notes'=>'RX350'],
            ['make'=>'Lexus','model'=>'RX','year_from'=>2016,'year_to'=>2026,'engine_code'=>'3.5 / 2.4T','drive_type'=>'FWD/AWD','transmission_code'=>'UA80 family era','speeds'=>'8','pin_count_min'=>18,'pin_count_max'=>22,'key_notes'=>'Non-hybrid branch'],
            ['make'=>'Lexus','model'=>'GS','year_from'=>1998,'year_to'=>2005,'engine_code'=>'3.0','drive_type'=>'RWD','transmission_code'=>'A340E','speeds'=>'4','pin_count_min'=>8,'pin_count_max'=>10,'key_notes'=>'GS300'],
            ['make'=>'Lexus','model'=>'GS','year_from'=>1998,'year_to'=>2006,'engine_code'=>'4.3','drive_type'=>'RWD','transmission_code'=>'A650E','speeds'=>'5','pin_count_min'=>9,'pin_count_max'=>10,'key_notes'=>'GS430'],
            ['make'=>'Lexus','model'=>'GS','year_from'=>2007,'year_to'=>2020,'engine_code'=>'3.5 / V8','drive_type'=>'RWD','transmission_code'=>'AA80/AA81 era','speeds'=>'8','pin_count_min'=>16,'pin_count_max'=>20,'key_notes'=>'Luxury/performance RWD'],
            ['make'=>'Lexus','model'=>'IS','year_from'=>2006,'year_to'=>2013,'engine_code'=>'2.5 / 3.5','drive_type'=>'RWD/AWD','transmission_code'=>'A760E/AA80 era','speeds'=>'6/8','pin_count_min'=>12,'pin_count_max'=>20,'key_notes'=>'Verify engine'],
            ['make'=>'Lexus','model'=>'IS','year_from'=>2014,'year_to'=>2026,'engine_code'=>'3.5 / V8','drive_type'=>'RWD','transmission_code'=>'AA81/AA80 era','speeds'=>'8','pin_count_min'=>16,'pin_count_max'=>20,'key_notes'=>'Modern IS 8AT'],
            ['make'=>'Lexus','model'=>'LS','year_from'=>1997,'year_to'=>2000,'engine_code'=>'4.0','drive_type'=>'RWD','transmission_code'=>'A341E','speeds'=>'4','pin_count_min'=>8,'pin_count_max'=>10,'key_notes'=>'LS400'],
            ['make'=>'Lexus','model'=>'LS','year_from'=>2001,'year_to'=>2006,'engine_code'=>'4.3','drive_type'=>'RWD','transmission_code'=>'A650E','speeds'=>'5','pin_count_min'=>9,'pin_count_max'=>10,'key_notes'=>'LS430'],
            ['make'=>'Lexus','model'=>'LS','year_from'=>2007,'year_to'=>2020,'engine_code'=>'4.6 / 5.0','drive_type'=>'RWD/AWD','transmission_code'=>'AA80E/AA80F','speeds'=>'8','pin_count_min'=>16,'pin_count_max'=>20,'key_notes'=>'LS460/LS family'],
            ['make'=>'Lexus','model'=>'GX','year_from'=>2003,'year_to'=>2009,'engine_code'=>'4.7','drive_type'=>'4WD','transmission_code'=>'A750F','speeds'=>'5','pin_count_min'=>11,'pin_count_max'=>13,'key_notes'=>'GX470'],
            ['make'=>'Lexus','model'=>'GX','year_from'=>2010,'year_to'=>2026,'engine_code'=>'4.6','drive_type'=>'4WD','transmission_code'=>'AB60/AC60 era','speeds'=>'6','pin_count_min'=>13,'pin_count_max'=>16,'key_notes'=>'GX460/GX550 era differs by generation'],
            ['make'=>'Lexus','model'=>'LX','year_from'=>1998,'year_to'=>2007,'engine_code'=>'4.7','drive_type'=>'4WD','transmission_code'=>'A343/A750F era','speeds'=>'4/5','pin_count_min'=>8,'pin_count_max'=>13,'key_notes'=>'Confirm generation'],
            ['make'=>'Lexus','model'=>'LX','year_from'=>2008,'year_to'=>2021,'engine_code'=>'5.7','drive_type'=>'4WD','transmission_code'=>'AB60F','speeds'=>'6','pin_count_min'=>13,'pin_count_max'=>16,'key_notes'=>'LX570'],
            ['make'=>'Lexus','model'=>'LX','year_from'=>2022,'year_to'=>2026,'engine_code'=>'3.4T','drive_type'=>'4WD','transmission_code'=>'10-speed era','speeds'=>'10','pin_count_min'=>22,'pin_count_max'=>30,'key_notes'=>'New generation'],        ];

        foreach ($vehicleRows as $row) {
            $exists = DB::table('vehicle_powertrain_reference')
                ->where('make', $row['make'])
                ->where('model', $row['model'])
                ->where('year_from', $row['year_from'])
                ->where('year_to', $row['year_to'])
                ->where('transmission_code', $row['transmission_code'])
                ->exists();
            if ($exists) continue; // idempotent — safe to re-run this seeder

            DB::table('vehicle_powertrain_reference')->insert(array_merge($row, [
                'source'     => 'toyota_lexus_transmission_master_2026',
                'verified'   => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $familyRows = [
            ['transmission_codes'=>'A340E / A340F / A341E','family_name'=>'A-Series','layout'=>'RWD / 4WD','typical_era'=>'1990s–mid 2000s','speeds'=>'4','pin_count_min'=>8,'pin_count_max'=>10,'representative_models'=>'4Runner, Tacoma, GS300, LS400','compatibility_notes'=>'Stay inside A340/A341 branch; check 2WD/4WD and bellhousing.'],
            ['transmission_codes'=>'A650E','family_name'=>'A-Series','layout'=>'RWD','typical_era'=>'late 1990s–mid 2000s','speeds'=>'5','pin_count_min'=>9,'pin_count_max'=>10,'representative_models'=>'GS430, LS430','compatibility_notes'=>'Not interchangeable with A750 despite similar era.'],
            ['transmission_codes'=>'A750E / A750F','family_name'=>'A-Series','layout'=>'RWD / 4WD','typical_era'=>'2003–2015+','speeds'=>'5','pin_count_min'=>11,'pin_count_max'=>13,'representative_models'=>'4Runner, Prado, GX470, Tacoma','compatibility_notes'=>'Truck/SUV family; confirm suffix.'],
            ['transmission_codes'=>'AB60E / AB60F','family_name'=>'A-Series','layout'=>'RWD / 4WD','typical_era'=>'2007–present','speeds'=>'6','pin_count_min'=>13,'pin_count_max'=>16,'representative_models'=>'Tundra, Land Cruiser, LX570','compatibility_notes'=>'Heavy-duty truck/SUV family.'],
            ['transmission_codes'=>'U140E / U140F','family_name'=>'U-Series','layout'=>'FWD / AWD','typical_era'=>'late 1990s–early 2000s','speeds'=>'4','pin_count_min'=>10,'pin_count_max'=>10,'representative_models'=>'Camry V6, ES300, RX300','compatibility_notes'=>'V6 branch; do not confuse with U241/U250.'],
            ['transmission_codes'=>'U241E','family_name'=>'U-Series','layout'=>'FWD','typical_era'=>'early 2000s','speeds'=>'4','pin_count_min'=>10,'pin_count_max'=>13,'representative_models'=>'Camry 2.4, Solara, RAV4 FWD','compatibility_notes'=>'4-cylinder family.'],
            ['transmission_codes'=>'U151E / U151F','family_name'=>'U-Series','layout'=>'FWD / AWD','typical_era'=>'2002–2008','speeds'=>'5','pin_count_min'=>13,'pin_count_max'=>16,'representative_models'=>'RX330, Highlander V6','compatibility_notes'=>'V6 5-speed family; verify AWD/FWD.'],
            ['transmission_codes'=>'U250E / U250F','family_name'=>'U-Series','layout'=>'FWD / AWD','typical_era'=>'mid 2000s–early 2010s','speeds'=>'5','pin_count_min'=>13,'pin_count_max'=>13,'representative_models'=>'Camry 2.4, RAV4','compatibility_notes'=>'Common 4-cylinder 5-speed branch.'],
            ['transmission_codes'=>'U660E / U660F','family_name'=>'U-Series','layout'=>'FWD / AWD','typical_era'=>'2006–2019','speeds'=>'6','pin_count_min'=>22,'pin_count_max'=>22,'representative_models'=>'Camry V6, ES350, RX350','compatibility_notes'=>'High-count 6-speed family.'],
            ['transmission_codes'=>'U760E / U760F / U761E','family_name'=>'U-Series','layout'=>'FWD / AWD','typical_era'=>'2009–2021','speeds'=>'6','pin_count_min'=>16,'pin_count_max'=>20,'representative_models'=>'Camry 2.5, Avalon, RAV4','compatibility_notes'=>'Later 6-speed I4 family.'],
            ['transmission_codes'=>'AA80E / AA81E','family_name'=>'AA-Series','layout'=>'RWD','typical_era'=>'2007–present','speeds'=>'8','pin_count_min'=>16,'pin_count_max'=>20,'representative_models'=>'GS, IS, LS, RC','compatibility_notes'=>'Lexus RWD 8-speed family.'],
            ['transmission_codes'=>'UA80E / UB80E / UB80F','family_name'=>'UA/UB-Series','layout'=>'FWD / AWD','typical_era'=>'2018–present','speeds'=>'8','pin_count_min'=>18,'pin_count_max'=>22,'representative_models'=>'Camry, ES, Avalon, RAV4','compatibility_notes'=>'Modern Direct Shift 8AT era; highly VIN-dependent.'],
            ['transmission_codes'=>'K-series CVT','family_name'=>'CVT','layout'=>'FWD','typical_era'=>'2000s–present','speeds'=>'CVT','pin_count_min'=>12,'pin_count_max'=>20,'representative_models'=>'Corolla, Yaris, hybrid-adjacent small cars','compatibility_notes'=>'Use exact CVT code and market spec.'],        ];

        foreach ($familyRows as $row) {
            $exists = DB::table('transmission_families')
                ->where('transmission_codes', $row['transmission_codes'])
                ->exists();
            if ($exists) continue;

            DB::table('transmission_families')->insert(array_merge($row, [
                'source'     => 'toyota_lexus_transmission_master_2026',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $this->command?->info('Seeded ' . count($vehicleRows) . ' vehicle-powertrain rows and ' . count($familyRows) . ' transmission-family rows.');
    }
}
