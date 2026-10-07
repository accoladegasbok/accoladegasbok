<?php
// FILE: app/Services/PartCodeService.php
//
// Stock numbers (part_code): one place to build the next number, map a
// category to its prefix, and remember old numbers after a category change
// so tags already printed and stuck on parts keep working.

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PartCodeService
{
    /** Same map the harvest checklist has always used. */
    public const CATEGORY_PREFIX = [
        'Engine'       => 'ENG',
        'Transmission' => 'TRN',
        'Electrical'   => 'ELC',
        'Fuel'         => 'FUL',
        'Cooling'      => 'CLG',
        'Body'         => 'BDY',
        'Suspension'   => 'SUS',
        'Wheels'       => 'WHL',
        'Seat'         => 'INT',
        'Airbag'       => 'AIR',
        'Interior'     => 'INT',
        'Brakes'       => 'BRK',
        'Exhaust'      => 'EXH',
    ];

    /** Non-vehicle categories — all share the CON series. */
    public const CONSUMABLE_CATEGORIES = ['Consumable', 'Electronics', 'Computers', 'Other'];

    public static function isConsumableCategory(?string $category): bool
    {
        return in_array($category, self::CONSUMABLE_CATEGORIES, true);
    }

    public static function prefixForCategory(string $category): string
    {
        if (self::isConsumableCategory($category)) return 'CON';
        return self::CATEGORY_PREFIX[$category] ?? 'PRT';
    }

    /**
     * Next number in a prefix series, taken from the HIGHEST number in use
     * (not "the last row inserted"). The old approach could hand out a
     * number that already existed once any part had been re-numbered, and
     * harvest custom parts even used row-count + 1.
     */
    public static function next(string $prefix): string
    {
        $start = strlen($prefix) + 2; // SUBSTRING is 1-based: "ENG-" is prefix + dash
        $max = DB::table('parts_inventory')
            ->where('part_code', 'like', $prefix . '-%')
            ->selectRaw('MAX(CAST(SUBSTRING(part_code, ?) AS UNSIGNED)) as m', [$start])
            ->value('m');

        return $prefix . '-' . str_pad(((int) $max) + 1, 5, '0', STR_PAD_LEFT);
    }

    /** Remember a number that was replaced. */
    public static function recordChange(int $partId, string $oldCode, string $newCode, ?string $oldCategory, ?string $newCategory, ?int $staffId): void
    {
        if (!Schema::hasTable('part_code_history')) return;

        DB::table('part_code_history')->insert([
            'parts_inventory_id' => $partId,
            'old_code'           => $oldCode,
            'new_code'           => $newCode,
            'old_category'       => $oldCategory,
            'new_category'       => $newCategory,
            'changed_by_staff_id'=> $staffId,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    /** A part id from a current OR an old stock number — for scan/search endpoints. */
    public static function findPartId(string $code): ?int
    {
        $code = trim($code);
        if ($code === '') return null;

        $id = DB::table('parts_inventory')->where('part_code', $code)->value('id');
        if ($id) return (int) $id;

        if (!Schema::hasTable('part_code_history')) return null;

        $id = DB::table('part_code_history')->where('old_code', $code)->orderByDesc('id')->value('parts_inventory_id');
        return $id ? (int) $id : null;
    }

    /** Part ids whose OLD number contains the text — for the admin search box. */
    public static function idsMatchingOldCode(string $needle): array
    {
        if ($needle === '' || !Schema::hasTable('part_code_history')) return [];

        return DB::table('part_code_history')
            ->where('old_code', 'like', "%{$needle}%")
            ->pluck('parts_inventory_id')
            ->unique()->values()->all();
    }
}
