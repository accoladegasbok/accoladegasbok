<?php
// FILE: app/Data/FitmentStatus.php
//
// The vocabulary for how sure we are that two vehicles share a part. Engine and
// gearbox are judged separately: sharing a 2AZ-FE does not mean the gearbox is
// shared, and neither means the whole assembly bolts in without changes.
//
// Only DIRECT_FIT_VERIFIED, ENGINE_ASSEMBLY_VERIFIED and GEARBOX_ASSEMBLY_VERIFIED
// may show customers "Verified fit". The three FAMILY statuses are leads for staff
// and, at most, a "Probable fit - confirm with us" label.

namespace App\Data;

class FitmentStatus
{
    public const ENGINE_FAMILY_MATCH       = 'engine_family_match';       // both vehicles have the same engine code
    public const GEARBOX_FAMILY_MATCH      = 'gearbox_family_match';      // both have the same gearbox code
    public const BOTH_FAMILY_MATCH         = 'both_family_match';         // engine and gearbox codes both match
    public const ENGINE_ASSEMBLY_VERIFIED  = 'engine_assembly_verified';  // OEM evidence confirms the engine assembly swaps
    public const GEARBOX_ASSEMBLY_VERIFIED = 'gearbox_assembly_verified'; // OEM evidence confirms the gearbox assembly swaps
    public const DIRECT_FIT_VERIFIED       = 'direct_fit_verified';       // one specific donor/recipient pair checked

    private const LABELS = [
        self::ENGINE_FAMILY_MATCH       => 'Same engine family',
        self::GEARBOX_FAMILY_MATCH      => 'Same gearbox family',
        self::BOTH_FAMILY_MATCH         => 'Same engine and gearbox family',
        self::ENGINE_ASSEMBLY_VERIFIED  => 'Engine assembly verified',
        self::GEARBOX_ASSEMBLY_VERIFIED => 'Gearbox assembly verified',
        self::DIRECT_FIT_VERIFIED       => 'Direct fit verified',
    ];

    public static function all(): array { return self::LABELS; }

    public static function label(?string $status): string
    {
        return self::LABELS[$status] ?? 'Not assessed';
    }

    public static function isVerified(?string $status): bool
    {
        return in_array($status, [self::ENGINE_ASSEMBLY_VERIFIED, self::GEARBOX_ASSEMBLY_VERIFIED, self::DIRECT_FIT_VERIFIED], true);
    }

    /** What a customer is allowed to read. Family-only matches are never called "verified". */
    public static function customerLabel(?string $status): ?string
    {
        if (self::isVerified($status)) return 'Verified fit';
        if (in_array($status, [self::ENGINE_FAMILY_MATCH, self::GEARBOX_FAMILY_MATCH, self::BOTH_FAMILY_MATCH], true)) {
            return 'Probable fit - confirm with us';
        }
        return null; // not assessed: not shown
    }
}
