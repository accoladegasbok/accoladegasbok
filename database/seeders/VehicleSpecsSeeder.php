<?php
// FILE: database/seeders/VehicleSpecsSeeder.php
// Focused seeder — only fetches models actually stocked by Auto Zenith.
// Uses fuzzy model-name matching to handle EPA's inconsistent naming
// (e.g. "ES350" matches EPA's "ES 350", "RX350" matches both
// "RX 350 2WD" and "RX 350 AWD", "Sierra" matches all GMC Sierra trims).

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Data\OemDatabase;

class VehicleSpecsSeeder extends Seeder
{
    const EPA_BASE = 'https://fueleconomy.gov/ws/rest';

    // Only the make/model combinations Auto Zenith actually stocks
    const VEHICLES = [
        'Toyota'       => ['Corolla','Camry','RAV4','Highlander','Avalon','Sienna','Venza','Matrix','Yaris','4Runner','Land Cruiser','Tundra','Sequoia','Hilux','Fortuner','HiAce','Innova','FJ Cruiser','Prius','Tacoma'],
        'Lexus'        => ['ES300','ES330','ES350','RX300','RX330','RX350','GS300','GS350','IS250','IS350','LS430','LS460','LX470','LX570','GX470','GX460'],
        'Honda'        => ['Accord','Civic','CR-V','Odyssey','Pilot','Element','Ridgeline'],
        'Acura'        => ['MDX','TL','TSX','RDX'],
        'Nissan'       => ['Altima','Sentra','Maxima','Murano','Pathfinder','Armada','Tiida','Almera','X-Trail','350Z','370Z','Frontier'],
        'Infiniti'     => ['G35','G37','FX35','FX37','QX56','M35'],
        'Hyundai'      => ['Elantra','Sonata','Tucson','Santa Fe','Accent','Azera','Genesis'],
        'Kia'          => ['Cerato','Forte','Optima','Sportage','Sorento','Rio','Soul'],
        'Mercedes-Benz'=> ['C180','C200','C250','C280','C300','C350','E200','E320','E350','E500','ML320','ML350','ML500','S320','S350','S500','GLK350','GL350'],
        'Ford'         => ['Focus','Fusion','Escape','Explorer','F-150','Edge','Expedition','Mustang'],
        'Chevrolet'    => ['Silverado','Tahoe','Suburban','Malibu','Equinox','Cruze','Camaro'],
        'GMC'          => ['Sierra','Yukon','Terrain','Acadia','Canyon'],
        'Pontiac'      => ['Vibe','Grand Prix'],
        'Mazda'        => ['3','6','CX-5','CX-7','CX-9','5','MX-5'],
        'BMW'          => ['3 Series','5 Series','X3','X5'],
        'Mitsubishi'   => ['Lancer','Galant','Outlander','Eclipse'],
        'Subaru'       => ['Impreza','Outback','Forester'],
    ];

    const YEAR_FROM = 1995;
    const YEAR_TO   = 2025;

    private array $modelCache = [];

    public function run(): void
    {
        $this->command->info('🚗 Vehicle Engine Specs Seeder (Focused + Fuzzy Match)');
        $total = 0; $errors = 0;

        foreach (self::VEHICLES as $make => $models) {
            $this->command->info("⏳ {$make} (" . count($models) . " models)...");
            $makeCount = 0;

            foreach ($models as $model) {
                for ($year = self::YEAR_FROM; $year <= self::YEAR_TO; $year++) {

                    $options = $this->getOptions($year, $make, $model);
                    if (empty($options)) { usleep(50000); continue; }

                    foreach ($options as $vehicleId) {
                        try {
                            $spec = $this->getSpec($vehicleId);
                            if (!$spec) continue;

                            $row = $this->buildRow($make, $model, $year, $vehicleId, $spec);
                            if ($row) {
                                DB::table('vehicle_engine_specs')->upsert(
                                    [$row],
                                    ['epa_vehicle_id'],
                                    array_keys($row)
                                );
                                $makeCount++;
                                $total++;
                            }
                        } catch (\Exception $e) {
                            $errors++;
                            if ($this->command->isVerbose()) {
                                $this->command->warn("  ⚠ {$make} {$model} {$year}: " . $e->getMessage());
                            }
                        }
                        usleep(60000);
                    }
                    usleep(80000);
                }
            }
            $this->command->info("  ✓ {$make}: {$makeCount} specs");
        }

        $this->command->newLine();
        $this->command->info("✅ Done! {$total} specs. {$errors} errors.");
        $this->command->info('   Now run: php artisan oem:enrich');
    }

    // ── Fuzzy model matching ────────────────────────────────────────
    // EPA's model naming is inconsistent (spaces, drivetrain suffixes
    // like "2WD"/"AWD", generation codes). Instead of hardcoding every
    // exact variant, we fetch ALL models for this make+year, then match
    // any that start with our simplified keyword (case/space-insensitive).
    private function getOptions(int $year, string $make, string $model): array
    {
        $allModels = $this->getAllModelsForMakeYear($year, $make);
        if (empty($allModels)) return [];

        $modelKey = strtolower(preg_replace('/[\s\-]/', '', $model));
        $matched  = [];

        foreach ($allModels as $epaModel) {
            $epaKey = strtolower(preg_replace('/[\s\-]/', '', $epaModel));
            if (str_starts_with($epaKey, $modelKey)) {
                $matched[] = $epaModel;
            }
        }

        if (empty($matched)) return [];

        $ids = [];
        foreach ($matched as $epaModel) {
            try {
                $res = Http::timeout(8)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->get(self::EPA_BASE . '/vehicle/menu/options', [
                        'year'  => $year,
                        'make'  => $make,
                        'model' => $epaModel,
                    ]);
                if ($res->failed()) continue;
                $items = $res->json('menuItem') ?? [];
                if (!is_array($items)) continue;
                if (isset($items['value'])) { $ids[] = (string)$items['value']; continue; }
                foreach (array_column($items, 'value') as $v) $ids[] = (string)$v;
            } catch (\Exception $e) { continue; }
            usleep(40000);
        }

        return array_unique($ids);
    }

    private function getAllModelsForMakeYear(int $year, string $make): array
    {
        $cacheKey = $year . '|' . $make;
        if (isset($this->modelCache[$cacheKey])) return $this->modelCache[$cacheKey];

        try {
            $res = Http::timeout(8)
                ->withHeaders(['Accept' => 'application/json'])
                ->get(self::EPA_BASE . '/vehicle/menu/model', [
                    'year' => $year,
                    'make' => $make,
                ]);
            if ($res->failed()) return $this->modelCache[$cacheKey] = [];
            $items = $res->json('menuItem') ?? [];
            if (!is_array($items)) return $this->modelCache[$cacheKey] = [];
            if (isset($items['value'])) return $this->modelCache[$cacheKey] = [(string)$items['value']];
            return $this->modelCache[$cacheKey] = array_column($items, 'value');
        } catch (\Exception $e) {
            return $this->modelCache[$cacheKey] = [];
        }
    }

    private function getSpec(string $vehicleId): ?array
    {
        try {
            $res = Http::timeout(8)
                ->withHeaders(['Accept' => 'application/json'])
                ->get(self::EPA_BASE . '/vehicle/' . $vehicleId);
            if ($res->failed()) return null;
            return $res->json();
        } catch (\Exception $e) { return null; }
    }

    private function buildRow(string $make, string $model, int $year, string $epaId, array $v): ?array
    {
        $cylinders = (int)   ($v['cylinders'] ?? 0);
        $displ     = (float) ($v['displ']     ?? 0);
        $trany     = $v['trany']     ?? null;
        $fuelType  = $v['fuelType1'] ?? null;
        $drive     = $v['drive']     ?? null;
        $vClass    = $v['VClass']    ?? null;

        $transType = null; $transSpeed = null;
        if ($trany) {
            if (stripos($trany,'variable') !== false || stripos($trany,'CVT') !== false) $transType = 'CVT';
            elseif (stripos($trany,'Automatic') !== false) $transType = 'AT';
            elseif (stripos($trany,'Manual') !== false) $transType = 'MT';
            else $transType = substr($trany, 0, 40);
            if (preg_match('/(\d+)\s*[-\s]?spd/i', $trany, $m)) $transSpeed = (int)$m[1];
        }

        $driveNorm = null;
        if ($drive) {
            $driveNorm = match(true) {
                str_contains($drive,'4-Wheel or All-Wheel') => '4WD/AWD',
                str_contains($drive,'All-Wheel')            => 'AWD',
                str_contains($drive,'4-Wheel')               => '4WD',
                str_contains($drive,'Front-Wheel')          => 'FWD',
                str_contains($drive,'Rear-Wheel')           => 'RWD',
                str_contains($drive,'2-Wheel')               => '2WD',
                default                                       => substr($drive, 0, 50),
            };
        }

        // Use the ORIGINAL requested $model (e.g. "ES350") not the EPA
        // variant (e.g. "ES 350 AWD") for OemDatabase lookup + storage,
        // so downstream matching stays consistent with the rest of the app.
        $oem = OemDatabase::lookup(strtoupper($make), strtoupper($model), $year, $cylinders, $displ);

        return [
            'make'                 => $make,
            'model'                => $model,
            'year'                 => $year,
            'trim'                 => isset($v['trim']) ? substr($v['trim'], 0, 120) : null,
            'body_style'           => $vClass ? substr($vClass, 0, 40) : null,
            'fuel_type'            => $fuelType ? substr($fuelType, 0, 30) : null,
            'drive_type'           => $driveNorm ?: ($oem['drive_type'] ?? null),
            'cylinders'            => $cylinders ?: null,
            'engine_l'             => $displ ?: null,
            'transmission_type'    => $transType,
            'transmission_speeds'  => $transSpeed,
            'engine_code_oem'      => $oem['engine_code'],
            'transmission_code_oem'=> $oem['transmission_code'],
            'pin_count'            => $oem['pin_count'],
            'gear_alias'           => $oem['gear_alias'],
            'source'               => 'epa',
            'epa_vehicle_id'       => $epaId,
            'created_at'           => now(),
            'updated_at'           => now(),
        ];
    }
}
