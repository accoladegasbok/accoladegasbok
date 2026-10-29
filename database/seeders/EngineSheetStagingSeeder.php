<?php
// FILE: database/seeders/EngineSheetStagingSeeder.php
//
// Loads the owner's engine sheet (Google Sheet exported as CSV) into fitment_staging ONLY.
// Columns, in order: Engine Name | Price | Size (e.g. "2.4L 4cyl") | Year/Make/Model (many lines in one cell).
// Every row becomes "engine_family_match" - a lead for staff, never shown to customers as verified.
// Safe to run twice: it replaces only the rows from this source.
//
// Put the file at: database/seeders/data/engine_sheet.csv   then:  php artisan db:seed --class=EngineSheetStagingSeeder
//
// Reading the application lines: "2002-2011 Toyota Camry in Australia. ACV40R", "Toyota Allion (ZRT260/265) 2007-2009",
// "1993-2003 Lexus ES 300 & Toyota Windom (Japanese market)", "1997-Present Toyota Prius". A line like "2ZR-FE" or
// "Applications with VVT-i:" is a sub-heading: it sets the engine variant for the lines below it.

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EngineSheetStagingSeeder extends Seeder
{
    private const SOURCE = 'engine_sheet_csv';
    private const MAKES  = ['Toyota', 'Lexus', 'Scion', 'Pontiac', 'Chevrolet', 'Suzuki', 'Hino', 'Daihatsu', 'Subaru', 'Lotus', 'Mazda', 'Honda', 'Nissan'];

    public function run(): void
    {
        if (!Schema::hasTable('fitment_staging')) {
            $this->command?->error('fitment_staging is missing. Run: php artisan migrate --force');
            return;
        }
        $path = database_path('seeders/data/engine_sheet.csv');
        if (!is_file($path)) {
            $this->command?->error("CSV not found: {$path}");
            return;
        }

        $hasStatus = Schema::hasColumn('fitment_staging', 'fitment_status');
        DB::table('fitment_staging')->where('source', self::SOURCE)->delete();

        $fh = fopen($path, 'r');
        $rowNo = 0; $inserted = 0; $skipped = 0;
        while (($c = fgetcsv($fh)) !== false) {
            $rowNo++;
            $c[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($c[0] ?? ''));
            $family = strtoupper(trim((string) $c[0]));
            if ($family === '' || $family === 'ENGINE NAME') continue;

            $price = trim((string) ($c[1] ?? ''));
            $size  = trim((string) ($c[2] ?? ''));
            $text  = (string) ($c[3] ?? '');

            foreach (self::parseApplications($text, $family) as $a) {
                DB::table('fitment_staging')->insert(array_filter([
                    'source'          => self::SOURCE,
                    'source_row'      => $rowNo,
                    'part_name'       => 'Engine Assembly',
                    'part_category'   => 'Engine',
                    'make'            => $a['make'],
                    'model'           => $a['model'],
                    'donor_years'     => $a['years'],
                    'engine_ref'      => $a['engine'],
                    'candidate_group' => 'ENG-' . $family . ' (provisional, not approved)',
                    'status'          => 'staged',
                    'fitment_status'  => $hasStatus ? 'engine_family_match' : null,
                    'notes'           => trim('Same engine family only - verify the gearbox and fit by VIN. '
                                        . ($size !== '' ? "Size: {$size}. " : '')
                                        . ($a['note'] !== '' ? $a['note'] . '. ' : '')
                                        . ($price !== '' ? 'Owner price guide: ' . preg_replace('/\s+/', ' ', $price) : '')),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ], fn ($v) => $v !== null));
                $inserted++;
            }
            if (trim($text) === '') $skipped++;
        }
        fclose($fh);
        $this->command?->info("Engine sheet: {$inserted} application rows staged, {$skipped} engine rows had no applications.");
    }

    /** @return array<int,array{make:string,model:string,years:?string,engine:string,note:string}> */
    public static function parseApplications(string $text, string $family): array
    {
        $out = [];
        $engine = $family;          // current engine variant (changes at a sub-heading)
        $lastMake = null; $lastModel = null;
        $nowYear = (int) date('Y');

        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line));
            if ($line === '') continue;

            // Sub-headings: "2ZR-FE", "A25A-FKS", "Applications with VVT-i:", "Applications with Dual VVT-i:"
            if (preg_match('/^[A-Z0-9]{2,5}-[A-Z]{1,4}$/', $line)) { $engine = $line; continue; }
            if (preg_match('/^applications?\b.*:$/i', $line)) { $engine = $family . ' (' . rtrim(preg_replace('/^applications?\s+with\s+/i', '', $line), ':') . ')'; continue; }

            // years: first range or single year anywhere in the line
            $years = null;
            $clean = $line;
            if (preg_match('/\(?\b((?:19|20)\d{2})\s*[-–—]\s*((?:19|20)\d{2}|present)\b\)?/iu', $line, $m)) {
                $to = strtolower($m[2]) === 'present' ? $nowYear : (int) $m[2];
                $years = $m[1] . '-' . $to;
                $clean = trim(str_replace($m[0], ' ', $line));
            } elseif (preg_match('/\(?\b((?:19|20)\d{2})\b\)?/u', $line, $m)) {
                $years = $m[1];
                $clean = trim(str_replace($m[0], ' ', $line));
            }

            // a model/make may share a line: "ES 300 & Toyota Windom"
            foreach (preg_split('/\s+&\s+/u', $clean) as $part) {
                $part = trim($part, " ,;");
                if ($part === '') continue;

                $make = null;
                foreach (self::MAKES as $mk) {
                    if (stripos($part, $mk . ' ') === 0 || strcasecmp($part, $mk) === 0) { $make = $mk; $part = trim(substr($part, strlen($mk))); break; }
                }
                if ($make === null) {
                    // "Lexus ES 300 & Windom": no make on this part, so reuse the make of the line it came from
                    if ($lastMake === null) continue;
                    if ($part !== '' && !preg_match('/^[A-Za-z0-9]/', $part)) continue;
                    // a bare generation line ("XW30 (Third generation)") belongs to the model above it
                    if ($years !== null && preg_match('/^[A-Z]{1,3}\d{1,3}\b/', $part) && $lastModel !== null) {
                        $out[] = ['make' => $lastMake, 'model' => $lastModel, 'years' => $years, 'engine' => $engine, 'note' => $part];
                        continue;
                    }
                    $make = $lastMake;
                }

                // chassis codes and market notes
                $note = [];
                if (preg_match_all('/\(([^)]*)\)/u', $part, $mm)) { foreach ($mm[1] as $n) $note[] = $n; $part = trim(preg_replace('/\([^)]*\)/u', ' ', $part)); }
                if (preg_match('/^(.*?)(?:\s+in\s+|\.\s+)(.+)$/u', $part, $mm)) { $part = trim($mm[1]); $note[] = trim($mm[2]); }
                $model = trim(preg_replace('/\s+/u', ' ', $part), " .,-");
                if ($model === '') { continue; }

                $lastMake = $make; $lastModel = $model;
                if ($years === null) continue;   // a model heading with no years yet
                $out[] = ['make' => $make, 'model' => $model, 'years' => $years, 'engine' => $engine, 'note' => implode('; ', array_filter($note))];
            }
        }
        return $out;
    }
}
