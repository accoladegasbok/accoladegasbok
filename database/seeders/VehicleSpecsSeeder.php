<?php
// FILE: database/seeders/VehicleSpecsSeeder.php
// Focused seeder — only fetches models actually stocked by Auto Zenith.
// Much faster than fetching all models for all years.

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
        'GMC'          => ['Sierra','Yukon','Terrain'],
        'Pontiac'      => ['Vibe','Grand Prix'],
        'Mazda'        => ['Mazda3','Mazda6','CX-5','CX-7'],
        'BMW'          => ['3 Series','5 Series','X3','X5'],
        'Mitsubishi'   => ['Lancer','Galant','Outlander','Eclipse'],
        'Subaru'       => ['Impreza','Outback','Forester'],
    ];

    const YEAR_FROM = 1995;
    const YEAR_TO   = 2025;

    public function run(): void
    {
        $this->command->info('🚗 Vehicle Engine Specs Seeder (Focused)');
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
                        usleep(60000); // 60ms between vehicle detail calls
                    }
                    usleep(80000); // 80ms between years
                }
            }
            $this->command->info("  ✓ {$make}: {$makeCount} specs");
        }

        $this->command->newLine();
        $this->command->info("✅ Done! {$total} specs. {$errors} errors.");
        $this->command->info('   Now run: php artisan oem:enrich');
    }

    private function getOptions(int $year, string $make, string $model): array
    {
        try {
            $res = Http::timeout(8)
                ->withHeaders(['Accept' => 'application/json'])
                ->get(self::EPA_BASE . '/vehicle/menu/options', [
                    'year'  => $year,
                    'make'  => $make,
                    'model' => $model,
                ]);
            if ($res->failed()) return [];
            $items = $res->json('menuItem') ?? [];
            if (!is_array($items)) return [];
            // Single item returns object not array
            if (isset($items['value'])) return [(string)$items['value']];
            return array_column($items, 'value');
        } catch (\Exception $e) { return []; }
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
                str_contains($drive,'4-Wheel')              => '4WD',
                str_contains($drive,'Front-Wheel')          => 'FWD',
                str_contains($drive,'Rear-Wheel')           => 'RWD',
                str_contains($drive,'2-Wheel')              => '2WD',
                default                                     => substr($drive, 0, 50),
            };
        }

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
