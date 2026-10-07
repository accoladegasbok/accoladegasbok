<?php
// FILE: app/Support/HubLocations.php
//
// The Lagos hub rule for everything the CUSTOMER sees.
//
//  - Every West Africa part (Nigeria + Accra) is findable under "Lagos" — our main hub —
//    whichever yard it is physically in. If it is not in Lagos we transfer it at the company's expense.
//  - It is ALSO findable under the yard it is really in (Ibadan, Ife, Akure, Abuja, Accra, Oshodi).
//  - Customers see "Lagos / Ibadan" style labels. The physical bin location stays on the part
//    exactly as it is, unchanged, and only staff ever see or change it (they use the real location).
//  - USA parts are not part of the hub; they keep their own location name.

namespace App\Support;

class HubLocations
{
    public const HUB = 'Lagos';

    /** Physical location (as stored on the part) => the name shown after "Lagos / ". */
    public const WEST_AFRICA = [
        'Lagos Nigeria'   => 'Oshodi',
        'Ibadan Nigeria'  => 'Ibadan',
        'Ile-Ife Nigeria' => 'Ife',
        'Akure Nigeria'   => 'Akure',
        'Abuja Nigeria'   => 'Abuja',
        'Accra Ghana'     => 'Accra',
    ];

    public const USA = ['Waxahachie TX', 'Kennedale TX', 'Elkhorn WI'];

    public static function isWestAfrica(?string $location): bool
    {
        return $location !== null && array_key_exists($location, self::WEST_AFRICA);
    }

    /** What a customer sees for a part's location: "Lagos / Ibadan", or the USA city unchanged. */
    public static function publicLabel(?string $location): string
    {
        if ($location !== null && isset(self::WEST_AFRICA[$location])) {
            return self::HUB . ' / ' . self::WEST_AFRICA[$location];
        }
        return (string) $location;
    }

    /** The choices in the customer's Location filter (value => label). */
    public static function filterOptions(): array
    {
        return [
            'USA'    => 'USA',
            'Lagos'  => 'Lagos — all West Africa stock',
            'Oshodi' => 'Lagos / Oshodi',
            'Ibadan' => 'Lagos / Ibadan',
            'Ife'    => 'Lagos / Ife',
            'Akure'  => 'Lagos / Akure',
            'Abuja'  => 'Lagos / Abuja',
            'Accra'  => 'Lagos / Accra',
        ];
    }

    /**
     * The physical locations a customer's Location choice should match.
     * Returns null when no location was chosen.
     */
    public static function physicalLocationsFor(?string $filter): ?array
    {
        $filter = trim((string) $filter);
        if ($filter === '') return null;

        // The hub: every West Africa part, wherever it physically is.
        if (strcasecmp($filter, self::HUB) === 0) {
            return array_keys(self::WEST_AFRICA);
        }

        // A single yard: parts that are really there.
        foreach (self::WEST_AFRICA as $physical => $short) {
            if (strcasecmp($filter, $short) === 0) return [$physical];
        }

        // Older links and country groups keep working.
        return match ($filter) {
            'USA'     => self::USA,
            'Nigeria' => ['Ile-Ife Nigeria', 'Ibadan Nigeria', 'Lagos Nigeria', 'Abuja Nigeria', 'Akure Nigeria'],
            'Ghana'   => ['Accra Ghana'],
            default   => [$filter],
        };
    }
}
