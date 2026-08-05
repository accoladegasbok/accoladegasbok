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
    /**
     * All known OEM numbers for a part, primary first.
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
     * Add another known OEM number for a part. If this is the part's
     * first entry, it's automatically marked primary.
     */
    public function add(int $partId, string $oemNumber, ?string $manufacturer = null, ?string $notes = null): int
    {
        $isFirst = !DB::table('part_oem_numbers')->where('parts_inventory_id', $partId)->exists();

        $id = DB::table('part_oem_numbers')->insertGetId([
            'parts_inventory_id' => $partId,
            'oem_number'         => trim($oemNumber),
            'manufacturer'       => $manufacturer,
            'is_primary'         => $isFirst,
            'notes'              => $notes,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        // Keep the legacy single field in sync when this is the
        // primary — so anything still reading oem_part_number directly
        // (existing forms, the barcode tag, etc.) stays accurate
        // without needing to be rewritten.
        if ($isFirst) {
            DB::table('parts_inventory')->where('id', $partId)->update([
                'oem_part_number' => trim($oemNumber),
                'updated_at'      => now(),
            ]);
        }

        return $id;
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

        DB::table('parts_inventory')->where('id', $partId)->update([
            'oem_part_number' => $target->oem_number,
            'updated_at'      => now(),
        ]);
    }

    public function remove(int $oemNumberId): void
    {
        DB::table('part_oem_numbers')->where('id', $oemNumberId)->delete();
    }
}
