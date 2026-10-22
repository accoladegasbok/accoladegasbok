<?php
// FILE: app/Data/EngineSpecs.php
//
// Engine code -> litres, cylinders and layout (I4, V6 ...). These are plain
// published factory specs, so showing them to customers is safe. A code that is
// not listed here simply shows no litres/layout: nothing is ever guessed.
// Add a line when you stock a new engine family.

namespace App\Data;

class EngineSpecs
{
    /** code => [litres, cylinders, layout letter(s)] */
    private const MAP = [
        // Toyota / Lexus
        '2AZ-FE' => [2.4, 4, 'I'], '2AR-FE' => [2.5, 4, 'I'], '1AR-FE' => [2.7, 4, 'I'], '2TR-FE' => [2.7, 4, 'I'],
        '1ZZ-FE' => [1.8, 4, 'I'], '2ZZ-GE' => [1.8, 4, 'I'], '3ZZ-FE' => [1.6, 4, 'I'], '2ZR-FE' => [1.8, 4, 'I'],
        '2ZR-FAE' => [1.8, 4, 'I'], '1NZ-FE' => [1.5, 4, 'I'], '5S-FE' => [2.2, 4, 'I'], '4A-FE' => [1.6, 4, 'I'],
        '7A-FE' => [1.8, 4, 'I'], 'A25A-FKS' => [2.5, 4, 'I'], 'A25A-FXS' => [2.5, 4, 'I'], 'M20A-FKS' => [2.0, 4, 'I'],
        '1MZ-FE' => [3.0, 6, 'V'], '3MZ-FE' => [3.3, 6, 'V'], '2GR-FE' => [3.5, 6, 'V'], '2GR-FKS' => [3.5, 6, 'V'],
        '1GR-FE' => [4.0, 6, 'V'], '1UZ-FE' => [4.0, 8, 'V'], '2UZ-FE' => [4.7, 8, 'V'], '1UR-FE' => [4.6, 8, 'V'],
        '3UR-FE' => [5.7, 8, 'V'],
        // Honda
        'K24Z1' => [2.4, 4, 'I'], 'K24Z2' => [2.4, 4, 'I'], 'K24Z3' => [2.4, 4, 'I'], 'K24A' => [2.4, 4, 'I'], 'K24W' => [2.4, 4, 'I'],
        'F23A' => [2.3, 4, 'I'], 'R18A1' => [1.8, 4, 'I'], 'L15B7' => [1.5, 4, 'I'],
        'J30A' => [3.0, 6, 'V'], 'J35A' => [3.5, 6, 'V'], 'J35Z2' => [3.5, 6, 'V'], 'J35Z4' => [3.5, 6, 'V'],
        // Hyundai / Kia
        'G4NB' => [1.8, 4, 'I'], 'G4KD' => [2.0, 4, 'I'], 'G4KE' => [2.4, 4, 'I'], 'G4KJ' => [2.4, 4, 'I'], 'G4JS' => [2.4, 4, 'I'],
        'G6BA' => [2.7, 6, 'V'], 'G6DC' => [3.8, 6, 'V'],
        // Nissan / Infiniti
        'QR25DE' => [2.5, 4, 'I'], 'MR20DE' => [2.0, 4, 'I'], 'MRA8DE' => [1.6, 4, 'I'], 'VQ35DE' => [3.5, 6, 'V'],
    ];

    /** @return array{litres:float,cylinders:int,layout:string}|null */
    public static function forCode(?string $code): ?array
    {
        if (!$code) return null;
        $key = strtoupper(trim(preg_split('/[\s(]/', trim($code))[0] ?? ''));
        if (!isset(self::MAP[$key])) return null;
        [$l, $c, $v] = self::MAP[$key];
        return ['litres' => $l, 'cylinders' => $c, 'layout' => $v . $c];
    }

    /** I4 / V6 / V8 from a cylinder count when the code is not in the list (flat for Subaru, inline-6 for BMW). */
    public static function layoutFromCylinders(?int $cylinders, ?string $make = null): ?string
    {
        if (!$cylinders) return null;
        $make = strtoupper((string) $make);
        if (in_array($make, ['SUBARU', 'PORSCHE'], true) && in_array($cylinders, [4, 6], true)) return 'H' . $cylinders;
        if ($make === 'BMW' && $cylinders === 6) return 'I6';
        return match (true) {
            $cylinders <= 5 => 'I' . $cylinders,
            default         => 'V' . $cylinders,
        };
    }
}
