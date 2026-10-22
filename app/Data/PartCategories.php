<?php
// FILE: app/Data/PartCategories.php
//
// Customer-facing "shop by category" tiles, in the style of autopartsearch.com
// (Engine and Engine Parts, Transmission and Drivetrain Parts, Wheels, ...).
// Each tile maps to one or more stored part_category values, so no data has to
// change. The home page, the /parts category drop-down and /parts/categories
// all read this one list.
//
// 'group' is the fitment group the owner described:
//   mechanical = engine, transmission, electrical, cooling, fuel/exhaust
//   chassis    = suspension, brakes, wheels
//   body       = body, interior, seats, airbags

namespace App\Data;

class PartCategories
{
    public static function tiles(): array
    {
        $t = [
            ['slug' => 'engines',      'label' => 'Engines & Engine Parts',        'icon' => '⚙️', 'group' => 'mechanical', 'categories' => ['Engine'],
             'blurb' => 'Engine code, I4 / V6 layout and litres shown on every listing.'],
            ['slug' => 'transmissions','label' => 'Transmission & Drivetrain',     'icon' => '🔧', 'group' => 'mechanical', 'categories' => ['Transmission'],
             'blurb' => 'Gearbox code, speeds, pin count and drive type (FWD / AWD / 4WD).'],
            ['slug' => 'suspension',   'label' => 'Suspension & Steering',         'icon' => '🛞', 'group' => 'chassis',    'categories' => ['Suspension'],
             'blurb' => 'Struts, arms, knuckles and racks, with the drive type they fit.'],
            ['slug' => 'brakes',       'label' => 'Brakes',                        'icon' => '🛑', 'group' => 'chassis',    'categories' => ['Brakes'],
             'blurb' => 'Calipers, rotors, ABS parts, side and drive type noted.'],
            ['slug' => 'wheels',       'label' => 'Wheels',                        'icon' => '🔘', 'group' => 'chassis',    'categories' => ['Wheels'],
             'blurb' => 'Alloy and steel wheels, single or set.'],
            ['slug' => 'body',         'label' => 'Body & Exterior',               'icon' => '🚘', 'group' => 'body',       'categories' => ['Body'],
             'blurb' => 'Doors, bumpers, fenders, lights and glass. Left / right and body style noted.'],
            ['slug' => 'interior',     'label' => 'Interior & Seats',              'icon' => '💺', 'group' => 'body',       'categories' => ['Interior', 'Seat'],
             'blurb' => 'Dashboards, seats, consoles, trim and door panels.'],
            ['slug' => 'electrical',   'label' => 'Electrical & Electronics',      'icon' => '🔌', 'group' => 'mechanical', 'categories' => ['Electrical'],
             'blurb' => 'Alternators, starters, sensors, ECUs, tested before listing.'],
            ['slug' => 'cooling',      'label' => 'Cooling & A/C',                 'icon' => '❄️', 'group' => 'mechanical', 'categories' => ['Cooling'],
             'blurb' => 'Radiators, fans, condensers and compressors.'],
            ['slug' => 'fuel-exhaust', 'label' => 'Fuel & Exhaust',                'icon' => '⛽', 'group' => 'mechanical', 'categories' => ['Fuel', 'Exhaust'],
             'blurb' => 'Fuel pumps, tanks, catalytic converters and exhaust parts.'],
            ['slug' => 'airbags',      'label' => 'Airbags & Safety',              'icon' => '🛡️', 'group' => 'body',       'categories' => ['Airbag'],
             'blurb' => 'Exact-vehicle match only. Never cross-fitted.'],
        ];

        foreach ($t as &$tile) {
            $tile['param'] = implode(',', $tile['categories']);
            $tile['url']   = '/parts?category=' . rawurlencode($tile['param']);
        }
        unset($tile);

        // Not a vehicle-parts tile, but the tile row is where customers look for it.
        $t[] = ['slug' => 'other-items', 'label' => 'Oils, Filters & Other Items', 'icon' => '🛢️', 'group' => 'other', 'categories' => [],
                'param' => '', 'url' => '/other-items', 'blurb' => 'Engine oil, filters, fluids, plugs and everyday consumables.'];

        return $t;
    }

    /** Only the vehicle-part tiles (what the /parts category drop-down lists). */
    public static function vehicleTiles(): array
    {
        return array_values(array_filter(self::tiles(), fn($t) => $t['group'] !== 'other'));
    }

    /** The tile a stored part_category value belongs to, or null. */
    public static function tileForCategory(?string $category): ?array
    {
        foreach (self::vehicleTiles() as $t) {
            if (in_array($category, $t['categories'], true)) return $t;
        }
        return null;
    }

    /** Every stored part_category value that the tiles cover (used to keep filters inside the auto scope). */
    public static function allVehicleCategories(): array
    {
        $out = [];
        foreach (self::vehicleTiles() as $t) $out = array_merge($out, $t['categories']);
        return array_values(array_unique($out));
    }
}
