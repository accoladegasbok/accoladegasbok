<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Reads/writes the part_oem_numbers table — see migration
 * 2026_08_05_030000_create_part_oem_numbers.php for the reasoning.
 *
 * parts_inventory.oem_part_number keeps working untouched for any
 * existing code that reads/writes it directly (e.g. it's what
 * displays as the "primary" number today). Use this service anywhere
 * NEW code needs to show or manage the FULL set of known OEM numbers
 * for a part.
 */
class PartOemNumberService
{
    // Recognized identifier types — matches the AutoZenith Interchange
    // Reference Model's Product Identifier Catalog concept. 'OEM' stays
    // the default so every existing call site keeps working unchanged.
    const IDENTIFIER_TYPES = ['OEM', 'Aftermarket', 'Casting Number', 'Engineering Number', 'Barcode', 'Internal Number', 'Supplier Number'];

    /**
     * All known identifiers for a part, primary first.
     */
    public function forPart(int $partId)
    {
        return DB::table('part_oem_numbers')
            ->where('parts_inventory_id', $partId)
            ->orderByDesc('is_primary')
            ->orderBy('manufacturer')
            ->get();
    }

    /**
     * Add another known identifier for a part. If this is the part's
     * first entry, it's automatically marked primary. identifier_type
     * defaults to 'OEM' so existing callers (edit form, harvest,
     * manual/create forms) work exactly as before without changes.
     */
    public function add(int $partId, string $value, ?string $manufacturer = null, ?string $notes = null, string $identifierType = 'OEM'): int
    {
        $isFirst = !DB::table('part_oem_numbers')->where('parts_inventory_id', $partId)->exists();
        $value   = trim($value);

        $id = DB::table('part_oem_numbers')->insertGetId([
            'parts_inventory_id' => $partId,
            'oem_number'         => $value,
            'identifier_type'    => in_array($identifierType, self::IDENTIFIER_TYPES, true) ? $identifierType : 'OEM',
            // Strips dashes/spaces and uppercases — lets a search for
            // "270600T230" still match a stored "27060-0T230" without
            // needing a smarter search algorithm.
            'normalized_value'   => strtoupper(preg_replace('/[\s\-]/', '', $value)),
            'manufacturer'       => $manufacturer,
            'is_primary'         => $isFirst,
            'notes'              => $notes,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        // Keep the legacy single field in sync when this is the
        // primary — so anything still reading oem_part_number directly
        // (existing forms, the barcode tag, etc.) stays accurate
        // without needing to be rewritten. Only for OEM-type primaries
        // — an Aftermarket or Casting Number shouldn't overwrite the
        // part's real OEM number field.
        if ($isFirst && $identifierType === 'OEM') {
            DB::table('parts_inventory')->where('id', $partId)->update([
                'oem_part_number' => $value,
                'updated_at'      => now(),
            ]);
        }

        return $id;
    }

    /**
     * Find parts by ANY known identifier, normalized so formatting
     * differences (dashes, spaces, case) don't cause a miss.
     */
    public function findByIdentifier(string $value)
    {
        $normalized = strtoupper(preg_replace('/[\s\-]/', '', $value));

        return DB::table('part_oem_numbers as pon')
            ->join('parts_inventory as p', 'p.id', '=', 'pon.parts_inventory_id')
            ->where('pon.normalized_value', $normalized)
            ->select('p.*', 'pon.identifier_type', 'pon.oem_number as matched_identifier')
            ->get();
    }

    /**
     * Change which OEM number is primary for a part — keeps the
     * legacy parts_inventory.oem_part_number field in sync.
     */
    public function setPrimary(int $partId, int $oemNumberId): void
    {
        $target = DB::table('part_oem_numbers')->where('id', $oemNumberId)->where('parts_inventory_id', $partId)->first();
        if (!$target) return;

        DB::table('part_oem_numbers')->where('parts_inventory_id', $partId)->update(['is_primary' => false]);
        DB::table('part_oem_numbers')->where('id', $oemNumberId)->update(['is_primary' => true, 'updated_at' => now()]);

        // Only sync the legacy field for an OEM-type identifier — an
        // Aftermarket or Casting Number becoming "primary" within its
        // own type shouldn't overwrite the part's real OEM number.
        if (($target->identifier_type ?? 'OEM') === 'OEM') {
            DB::table('parts_inventory')->where('id', $partId)->update([
                'oem_part_number' => $target->oem_number,
                'updated_at'      => now(),
            ]);
        }
    }

    public function remove(int $oemNumberId): void
    {
        DB::table('part_oem_numbers')->where('id', $oemNumberId)->delete();
    }
}
