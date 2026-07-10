<?php
// FILE: app/Console/Commands/OemEnrichCommand.php
//
// php artisan oem:enrich
//
// After VehicleSpecsSeeder populates the table from EPA,
// this command fills in any missing OEM engine/transmission codes
// by querying existing parts_inventory (Tier 1 — your actual stock)
// then falling back to OemDatabase static data (Tier 2).

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Data\OemDatabase;

class OemEnrichCommand extends Command
{
    protected $signature   = 'oem:enrich {--dry-run : Preview changes without saving}';
    protected $description = 'Enrich vehicle_engine_specs with OEM codes from inventory + OemDatabase';

    public function handle(): int
    {
        $isDry  = $this->option('dry-run');
        $rows   = DB::table('vehicle_engine_specs')
            ->whereNull('engine_code_oem')
            ->get();

        $this->info("Found {$rows->count()} rows without OEM engine code.");
        $updated = 0;

        foreach ($rows as $row) {
            // Tier 1 — check real inventory for this make/model/year
            $fromStock = DB::table('parts_inventory')
                ->where('brand', 'like', '%' . $row->make . '%')
                ->where('model', 'like', '%' . $row->model . '%')
                ->where('year_from', '<=', $row->year)
                ->where('year_to',   '>=', $row->year)
                ->whereNotNull('engine_code_oem')
                ->select('engine_code_oem','transmission_code_oem','pin_count','gear_alias')
                ->first();

            if ($fromStock) {
                $data = [
                    'engine_code_oem'       => $fromStock->engine_code_oem,
                    'transmission_code_oem' => $fromStock->transmission_code_oem,
                    'pin_count'             => $fromStock->pin_count,
                    'gear_alias'            => $fromStock->gear_alias,
                    'source'                => 'epa+inventory',
                    'updated_at'            => now(),
                ];
            } else {
                // Tier 2 — OemDatabase static
                $oem = OemDatabase::lookup(
                    strtoupper($row->make),
                    strtoupper($row->model),
                    (int)$row->year,
                    (int)($row->cylinders ?? 0),
                    (float)($row->engine_l ?? 0.0)
                );
                if (!$oem['engine_code'] && !$oem['transmission_code']) continue;
                $data = [
                    'engine_code_oem'       => $oem['engine_code'],
                    'transmission_code_oem' => $oem['transmission_code'],
                    'pin_count'             => $oem['pin_count'],
                    'gear_alias'            => $oem['gear_alias'],
                    'source'                => 'epa+oem_db',
                    'updated_at'            => now(),
                ];
            }

            if (!$isDry) {
                DB::table('vehicle_engine_specs')->where('id', $row->id)->update($data);
            } else {
                $this->line("  DRY: {$row->year} {$row->make} {$row->model} → {$data['engine_code_oem']}");
            }
            $updated++;
        }

        $this->info($isDry ? "Would update {$updated} rows." : "✅ Updated {$updated} rows.");
        return 0;
    }
}
