<?php
// FILE: app/Data/EngineAddons.php
//
// The add-ons that can come with "Complete Engine (With Add-ons)".
// Stored on the part as a JSON list of the KEYS below (parts_inventory.inclusions);
// the labels are what staff and customers see.

namespace App\Data;

class EngineAddons
{
    public const OPTIONS = [
        'ignition_coils'      => 'Ignition coils',
        'intake_manifold'     => 'Intake manifold',
        'injectors'           => 'Injectors',
        'power_steering_pump' => 'Power steering pump',
        'alternator'          => 'Alternator',
        'starter'             => 'Starter motor',
        'ac_compressor'       => 'A/C compressor',
    ];

    /** Keep only known keys, in the canonical order. */
    public static function clean(array $picked): array
    {
        return array_values(array_intersect(array_keys(self::OPTIONS), $picked));
    }

    /** Human labels for a stored JSON value (for tags, receipts, exports). */
    public static function labels(?string $json): array
    {
        $keys = json_decode($json ?? '[]', true) ?: [];
        return array_values(array_map(fn($k) => self::OPTIONS[$k] ?? $k, $keys));
    }

    /** The "Excludes" side — useful on tags and listings. */
    public static function excludedLabels(?string $json): array
    {
        $keys = json_decode($json ?? '[]', true) ?: [];
        return array_values(array_map(
            fn($k) => self::OPTIONS[$k],
            array_diff(array_keys(self::OPTIONS), $keys)
        ));
    }
}
