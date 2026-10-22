<?php
// FILE: database/seeders/TwoAzFeStagingSeeder.php
//
// Stages the owner's 2AZ-FE and 1ZZ-FE vehicle lists (engine family, gearbox family, supplier
// mounting group E1-E4) for staff review. STAGING ONLY: these are family matches, not
// verified direct fits, so nothing here reaches customers. Safe to run twice.
// Needs both migrations: 2026_10_20_000001 and 2026_10_22_000001.

namespace Database\Seeders;

use App\Data\FitmentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TwoAzFeStagingSeeder extends Seeder
{
    private const SOURCE = 'owner_2azfe_1zzfe_list';

    public function run(): void
    {
        if (!Schema::hasTable('fitment_staging') || !Schema::hasColumn('fitment_staging', 'fitment_status')) {
            $this->command?->error('Run: php artisan migrate --force  (both fitment migrations are needed first)');
            return;
        }

        // make, model, years, engine, gearbox, mount group, status, note
        $B = FitmentStatus::BOTH_FAMILY_MATCH;
        $E = FitmentStatus::ENGINE_FAMILY_MATCH;
        $rows = [
            ['Toyota', 'Camry',   '2002-2009', '2AZ-FE', 'U241E (2002-04, 10-pin) / U250E (2005-09, 13-pin)', 'E1', $B, '2.4L I4 FWD automatic'],
            ['Toyota', 'Solara',  '2002-2008', '2AZ-FE', 'U241E / U250E by year', 'E1', $B, '2.4L I4. Follows the Camry pin split, confirm on the donor.'],
            ['Toyota', 'Highlander', '2001-2007', '2AZ-FE', 'U241E', 'E1', $B, '2.4L FWD only'],
            ['Toyota', 'RAV4',    '2004-2005', '2AZ-FE', 'U241E', 'E2', $B, '2.4L FWD only'],
            ['Toyota', 'RAV4',    '2006-2008', '2AZ-FE', 'U241E', 'E3', $B, '2.4L FWD only'],
            ['Scion',  'tC',      '2005-2010', '2AZ-FE', 'U241E', 'E2', $B, '2.4L automatic'],
            ['Scion',  'xB',      '2008-2015', '2AZ-FE', 'U241E', 'E4', $B, 'Separate timing-cover group. Do not mix with E1-E3 without checking.'],
            ['Toyota', 'Matrix',  '2009-2011', '2AZ-FE', 'U250E', 'E3', $B, '2.4L FWD automatic. Conservative range, the Matrix 2.4L may run later.'],
            ['Toyota', 'Corolla XRS', '2009-2010', '2AZ-FE', 'U250E', 'E3', $B, '2.4L automatic'],
            ['Pontiac', 'Vibe',   '2009-2010', '2AZ-FE', 'U250E', 'E3', $B, '2.4L FWD automatic'],
            // 1ZZ-FE 1.8L family
            ['Toyota', 'Corolla', '2003-2008', '1ZZ-FE', 'U341E (5-pin)', null, $B, '1.8L. Pin count from the existing database.'],
            ['Toyota', 'Matrix',  '2003-2008', '1ZZ-FE', 'U341E', null, $B, '1.8L. Same engine as the 2003-2008 Corolla.'],
            ['Pontiac', 'Vibe',   '2003-2008', '1ZZ-FE', 'U341E', null, $B, '1.8L. Same engine as the 2003-2008 Corolla.'],
        ];

        DB::table('fitment_staging')->where('source', self::SOURCE)->delete();
        $n = 0;
        foreach ($rows as $i => [$make, $model, $years, $eng, $gear, $mount, $status, $note]) {
            [$from, $to] = array_map('intval', explode('-', $years));
            DB::table('fitment_staging')->insert([
                'source'           => self::SOURCE,
                'source_row'       => $i + 1,
                'part_name'        => 'Vehicle application',
                'part_category'    => 'Engine / Transmission',
                'make'             => $make,
                'model'            => $model,
                'donor_years'      => $years,
                'donor_years_to'   => $to,
                'engine_ref'       => $eng,
                'transmission_ref' => $gear,
                'candidate_group'  => $eng . ($mount ? ' ' . $mount : ''),
                'mount_group'      => $mount,
                'fitment_status'   => $status,
                'status'           => 'staged',
                'notes'            => $note . ' Family match only, not a verified direct fit.',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
            $n++;
        }
        $this->command?->info("Staged {$n} vehicle applications (not public).");
    }
}
