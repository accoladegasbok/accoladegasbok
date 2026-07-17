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

            // ── Added: Phase 1 brand expansion ──────────────────────
            'ram' => [
                '1500','2500','3500','ProMaster','ProMaster City','Dakota',
            ],
            'gmc' => [
                'Acadia','Canyon','Envoy','Jimmy','Safari','Savana','Sierra 1500',
                'Sierra 2500HD','Sierra 3500HD','Sonoma','Terrain','Yukon','Yukon XL',
            ],
            'subaru' => [
                'Ascent','BRZ','Crosstrek','Forester','Impreza','Legacy',
                'Outback','WRX','WRX STI','XV',
            ],
            'mazda' => [
                'CX-3','CX-30','CX-5','CX-50','CX-7','CX-9','CX-90','Mazda2',
                'Mazda3','Mazda5','Mazda6','MX-5 Miata','RX-8','Tribute',
            ],
            'dodge' => [
                'Avenger','Caliber','Caravan','Challenger','Charger','Dakota',
                'Durango','Grand Caravan','Journey','Magnum','Neon','Nitro',
                'Ram 1500','Stratus','Viper',
            ],
            'buick' => [
                'Cascada','Century','Enclave','Encore','Encore GX','Envision',
                'LaCrosse','Lucerne','Rainier','Regal','Rendezvous','Verano',
            ],
            'cadillac' => [
                'ATS','CT4','CT5','CT6','CTS','DTS','Escalade','Escalade ESV',
                'SRX','STS','XT4','XT5','XT6','XTS',
            ],
            'volvo' => [
                'C30','C40','C70','S40','S60','S70','S80','S90','V40','V50',
                'V60','V70','V90','XC40','XC60','XC70','XC90',
            ],
            'chrysler' => [
                '200','300','300M','Aspen','Cirrus','Concorde','Crossfire',
                'Pacifica','PT Cruiser','Sebring','Town & Country','Voyager',
            ],
            'mitsubishi' => [
                'Eclipse','Eclipse Cross','Endeavor','Galant','Lancer',
                'Lancer Evolution','Mirage','Montero','Outlander',
                'Outlander Sport','RVR',
            ],
            'land rover' => [
                'Defender','Discovery','Discovery Sport','Freelander',
                'Range Rover','Range Rover Evoque','Range Rover Sport',
                'Range Rover Velar',
            ],
            'lincoln' => [
                'Aviator','Continental','Corsair','MKC','MKS','MKT','MKX',
                'MKZ','Nautilus','Navigator','Town Car','Zephyr',
            ],
            'porsche' => [
                '911','718 Boxster','718 Cayman','Boxster','Cayenne',
                'Cayman','Macan','Panamera','Taycan',
            ],
            'genesis' => [
                'G70','G80','G90','GV60','GV70','GV80',
            ],
            'mini' => [
                'Clubman','Convertible','Cooper','Countryman','Hardtop',
                'Paceman',
            ],
            'jaguar' => [
                'E-Pace','F-Pace','F-Type','I-Pace','S-Type','X-Type',
                'XE','XF','XJ','XK',
            ],
            'fiat' => [
                '124 Spider','500','500L','500X',
            ],
            'rolls-royce' => [
                'Cullinan','Dawn','Ghost','Phantom','Wraith',
            ],
            'pontiac' => [
                'Aztek','Bonneville','G3','G5','G6','G8','Grand Am',
                'Grand Prix','GTO','Solstice','Sunfire','Torrent','Vibe',
            ],
            'datsun' => [
                '200SX','280Z','810','GO','GO+','Redi-GO',
            ],
            'isuzu' => [
                'Ascender','Axiom','D-Max','Hombre','Rodeo','Trooper','VehiCROSS',
            ],
            'jeep' => [
                'Cherokee','Commander','Compass','Gladiator','Grand Cherokee',
                'Grand Wagoneer','Liberty','Patriot','Renegade','Wrangler',
            ],
            'peugeot' => [
                '206','207','208','2008','301','307','308','3008','406',
                '407','5008','508',
            ],
        ];
    }
}
