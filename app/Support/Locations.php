<?php
// FILE: app/Support/Locations.php
//
// SINGLE SOURCE OF TRUTH for every physical location across AutoZenith
// Parts. Every controller, view, and dropdown that needs a location
// list should call Locations::all() (or one of the helpers below)
// instead of hardcoding the array locally.
//
// This exists because the list was previously duplicated across at
// least five files (HarvestController, InventoryController,
// checkout/index.blade.php, search.blade.php, and the donor_vehicles
// DB enum) — and they drifted out of sync (checkout used "Oshodi Lagos"
// while everywhere else used "Lagos Nigeria"), causing a 500 error on
// every Lagos harvest. Adding or renaming a location now only requires
// editing THIS file plus the two DB enum columns (parts_inventory.location
// and donor_vehicles.location) — nowhere else.

namespace App\Support;

class Locations
{
    /**
     * Canonical list of every location, in display order.
     * This is the ONLY place this list should be typed out.
     */
    const ALL = [
        'Waxahachie TX',
        'Kennedale TX',
        'Elkhorn WI',
        'Ile-Ife Nigeria',
        'Ibadan Nigeria',
        'Lagos Nigeria',
        'Abuja Nigeria',
        'Akure Nigeria',
        'Accra Ghana',
    ];

    /**
     * Full list, for dropdowns / @foreach loops.
     */
    public static function all(): array
    {
        return self::ALL;
    }

    /**
     * Locations in Nigeria only (used by checkout's in-store POS list,
     * and anywhere else that needs "Nigeria offices" specifically).
     */
    public static function nigeria(): array
    {
        return array_values(array_filter(self::ALL, fn($l) => str_contains($l, 'Nigeria')));
    }

    /**
     * Locations in the USA only.
     */
    public static function usa(): array
    {
        return array_values(array_filter(self::ALL, fn($l) => str_ends_with($l, ' TX') || str_ends_with($l, ' WI')));
    }

    /**
     * Ghana locations only.
     */
    public static function ghana(): array
    {
        return array_values(array_filter(self::ALL, fn($l) => str_contains($l, 'Ghana')));
    }

    /**
     * The flag emoji for a given location string — matches on country
     * name embedded in the location string itself. Use this instead of
     * re-implementing the same str_contains() match() block in every
     * blade file (search.blade.php, show.blade.php, ai-search.blade.php
     * all had their own copy of this logic).
     */
    public static function flag(string $location): string
    {
        return match (true) {
            str_contains($location, 'Nigeria') => '🇳🇬',
            str_contains($location, 'Ghana')   => '🇬🇭',
            default                             => '🇺🇸',
        };
    }

    /**
     * WhatsApp number to route enquiries to, based on location.
     * Centralizes the same lookup that PartController::waNumber()
     * and several blade files independently re-implement.
     */
    public static function whatsappNumber(string $location): string
    {
        return match (true) {
            str_contains($location, 'Nigeria') || str_contains($location, 'Ghana') => '2349155688804',
            default => '16822563201',
        };
    }

    /**
     * True if the given string is a valid, known location. Useful for
     * validating request input before it ever reaches the database —
     * catching a typo/mismatch here with a clear error message beats
     * finding out via a cryptic MySQL "Data truncated" exception.
     */
    public static function isValid(string $location): bool
    {
        return in_array($location, self::ALL, true);
    }
}
