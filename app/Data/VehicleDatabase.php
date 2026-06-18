<?php
// FILE: app/Data/VehicleDatabase.php
// Complete vehicle make/model database — 1986 to 2027
// Used for dropdown menus so models show regardless of inventory stock

namespace App\Data;

class VehicleDatabase
{
    // Returns all models for a given make
    public static function modelsForMake(string $make): array
{
    $db = self::all();
    $models = $db[strtolower(trim($make))] ?? [];
    return array_map('strtoupper', $models);
}
    // Returns all makes
    public static function makes(): array
{
    return array_map('strtoupper', array_keys(self::all()));
}

    // Returns years 1986-2027
    public static function years(): array
    {
        return range(2027, 1986);
    }

    public static function all(): array
    {
        return [
            'toyota' => [
                'Avalon','C-HR','Camry','Corolla','Corolla Cross','Corolla Hatchback',
                'Crown','FJ Cruiser','GR86','Highlander','Land Cruiser','Matrix',
                'Mirai','Prius','Prius C','Prius Prime','Prius V','RAV4','RAV4 Prime',
                'Sequoia','Sienna','Solara','Supra','Tacoma','Tundra','Venza',
                'Yaris','4Runner','86',
            ],
            'lexus' => [
                'CT','ES','GS','GX','IS','LC','LM','LS','LX','NX','RC','RX',
                'RZ','TX','UX',
            ],
            'honda' => [
                'Accord','Civic','Civic Type R','Clarity','CR-V','CR-V Hybrid',
                'CR-Z','Element','Fit','HR-V','Insight','Odyssey','Passport',
                'Pilot','Prologue','Ridgeline','S2000',
            ],
            'acura' => [
                'CL','ILX','Integra','MDX','NSX','RDX','RL','RLX','TL','TLX',
                'TSX','ZDX',
            ],
            'nissan' => [
                'Altima','Armada','Frontier','GT-R','Juke','Kicks','Leaf',
                'Maxima','Murano','NV','NV200','Pathfinder','Rogue','Rogue Sport',
                'Sentra','Titan','Versa','Xterra','370Z','350Z',
            ],
            'infiniti' => [
                'EX','FX','G','I','J','JX','M','Q30','Q40','Q45','Q50','Q60',
                'Q70','QX30','QX50','QX55','QX56','QX60','QX70','QX80',
            ],
            'kia' => [
                'Cadenza','Carnival','EV6','EV9','Forte','K5','K8','K900',
                'Niro','Optima','Rio','Seltos','Sorento','Soul','Sportage',
                'Stinger','Stonic','Telluride',
            ],
            'hyundai' => [
                'Accent','Azera','Elantra','Elantra GT','Elantra N','Entourage',
                'Equus','Genesis','Ioniq','Ioniq 5','Ioniq 6','Kona','Nexo',
                'Palisade','Santa Cruz','Santa Fe','Santa Fe Sport','Sonata',
                'Tiburon','Tucson','Tucson Hybrid','Veloster','Venue',
            ],
            'mercedes-benz' => [
                'A-Class','AMG GT','B-Class','C-Class','CLA','CLE','CLK','CLS',
                'E-Class','EQB','EQC','EQE','EQS','G-Class','GL-Class','GLA',
                'GLB','GLC','GLE','GLK','GLS','ML-Class','R-Class','S-Class',
                'SL','SLC','SLK','Sprinter','Vito',
            ],
            'ford' => [
                'Bronco','Bronco Sport','EcoSport','Edge','Escape','Expedition',
                'Explorer','F-150','F-150 Lightning','F-250','F-350','F-450',
                'Fiesta','Five Hundred','Flex','Focus','Fusion','Galaxy',
                'Maverick','Mustang','Mustang Mach-E','Ranger','Taurus','Transit',
            ],
            'gm' => [
                'Sierra 1500','Sierra 2500HD','Sierra 3500HD','Canyon','Terrain',
                'Acadia','Yukon','Yukon XL','Envoy','Jimmy','Sonoma','Safari',
            ],
            'chevrolet' => [
                'Astro','Avalanche','Aveo','Blazer','Bolt EV','Bolt EUV',
                'Camaro','Caprice','Captiva','City Express','Colorado',
                'Corvette','Cruze','Equinox','Express','HHR','Impala',
                'Malibu','Monte Carlo','Orlando','Silverado 1500',
                'Silverado 2500HD','Silverado 3500HD','Sonic','Spark',
                'Suburban','Tahoe','Tracker','TrailBlazer','Traverse',
                'Trax','Uplander','Volt',
            ],
            'vw' => [
                'Arteon','Atlas','Atlas Cross Sport','Beetle','CC','Eos',
                'Golf','Golf Alltrack','Golf GTI','Golf R','Golf SportWagen',
                'ID.4','Jetta','Jetta GLI','Passat','Phaeton','Routan',
                'Taos','Tiguan','Touareg',
            ],
        ];
    }
}
