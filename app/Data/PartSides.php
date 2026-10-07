<?php
// FILE: app/Data/PartSides.php
//
// ONE list of "side" values for every form and every save.
//
// Before this, the two add-part forms disagreed: one offered N/A, D/S, P/S and
// the other offered Left, Right, Front, Rear, Front Left ... — so the same
// physical part could be stored two different ways (and one of them crashed
// the save). Everything now stores the values below.
//
// D/S (driver side) is stored as Left and P/S (passenger side) as Right,
// because every donor vehicle is a left-hand-drive US car. If a right-hand-drive
// vehicle is ever stocked, pick the side by its real position instead.

namespace App\Data;

class PartSides
{
    public const OPTIONS = [
        'N/A', 'Left', 'Right', 'Front', 'Rear',
        'Front Left', 'Front Right', 'Rear Left', 'Rear Right',
    ];

    /** Older / alternative spellings -> the standard value. */
    private const ALIASES = [
        'n/a' => 'N/A', 'na' => 'N/A', 'none' => 'N/A', '-' => 'N/A',
        'left' => 'Left', 'l' => 'Left', 'lh' => 'Left', 'd/s' => 'Left', 'ds' => 'Left',
        'driver' => 'Left', 'driver side' => 'Left',
        'right' => 'Right', 'r' => 'Right', 'rh' => 'Right', 'p/s' => 'Right', 'ps' => 'Right',
        'passenger' => 'Right', 'passenger side' => 'Right',
        'front' => 'Front', 'rear' => 'Rear',
        'front left' => 'Front Left', 'fl' => 'Front Left',
        'front right' => 'Front Right', 'fr' => 'Front Right',
        'rear left' => 'Rear Left', 'rl' => 'Rear Left',
        'rear right' => 'Rear Right', 'rr' => 'Rear Right',
    ];

    /** The standard value for a known spelling, or null if we do not recognise it. */
    public static function canonical(?string $value): ?string
    {
        $key = mb_strtolower(trim((string) $value));
        if ($key === '') return 'N/A';
        return self::ALIASES[$key] ?? null;
    }

    /** For saving: the standard value, or N/A when it is blank or unrecognised. */
    public static function normalize(?string $value): string
    {
        return self::canonical($value) ?? 'N/A';
    }
}
