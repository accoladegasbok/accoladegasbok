<?php
// FILE: database/seeders/FitmentStagingSeeder.php
//
// Loads the 50-row sample fitment list into fitment_staging ONLY. Rows are
// "reference only, verify VIN" and are never shown to customers. Safe to run twice:
// it replaces the rows from this source and leaves everything else alone.
// Needs: database/seeders/data/AutoZenith_50_Part_Fitment_Sample.csv

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FitmentStagingSeeder extends Seeder
{
    private const SOURCE = 'sample_50_csv';

    public function run(): void
    {
        if (!Schema::hasTable('fitment_staging')) {
            $this->command?->error('fitment_staging is missing. Run: php artisan migrate --force');
            return;
        }
        $path = database_path('seeders/data/AutoZenith_50_Part_Fitment_Sample.csv');
        if (!is_file($path)) {
            $this->command?->error("CSV not found: {$path}");
            return;
        }

        $fh = fopen($path, 'r');
        $head = fgetcsv($fh);
        if (!$head) return;
        $head[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $head[0]); // strip the BOM
        $head = array_map(fn ($h) => strtolower(trim((string) $h)), $head);

        DB::table('fitment_staging')->where('source', self::SOURCE)->delete();

        $n = 0;
        $rowNo = 1;
        while (($cols = fgetcsv($fh)) !== false) {
            $rowNo++;
            if (count($cols) < 2) continue;
            $r = array_combine($head, array_pad(array_slice($cols, 0, count($head)), count($head), null));
            DB::table('fitment_staging')->insert([
                'source'           => self::SOURCE,
                'source_row'       => $rowNo,
                'part_name'        => $r['part_name'] ?? null,
                'part_category'    => $r['category'] ?? null,
                'make'             => $r['make'] ?? null,
                'model'            => $r['model'] ?? null,
                'donor_years'      => $r['years'] ?? null,
                'engine_ref'       => $this->code($r['engine_code'] ?? null),
                'transmission_ref' => $this->code($r['transmission_code'] ?? null),
                'candidate_group'  => $this->code($r['interchange_group'] ?? null),
                'status'           => 'staged',
                'notes'            => 'Reference only. Verify by VIN before use. Pin count: ' . ($r['pin_count'] ?? 'unknown'),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
            $n++;
        }
        fclose($fh);
        $this->command?->info("Staged {$n} rows from the sample list (not public).");
    }

    /** "2AZ-FE (reference only; verify VIN)" -> "2AZ-FE" */
    private function code(?string $v): ?string
    {
        if ($v === null) return null;
        $v = trim(preg_replace('/\s*\(.*$/', '', $v));
        return $v === '' ? null : $v;
    }
}
