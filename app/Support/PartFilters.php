<?php
// FILE: app/Support/PartFilters.php
//
// The spec filters shared by the customer search, the admin inventory list and the compatibility
// checker: drive type, number of cylinders and engine size (litres). One place, so all three behave alike.
//
// A part matches a cylinder/size filter when its displacement text says so ("3.5L V6") OR its engine
// code is a known engine with that spec (see App\Data\EngineSpecs). Nothing is guessed: a part with
// neither recorded simply does not match a cylinder/size filter.

namespace App\Support;

use App\Data\EngineSpecs;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PartFilters
{
    public const DRIVES = ['FWD', 'RWD', 'AWD', '4WD'];

    /** Clean the three values out of a request-like array (keys: drive, cyl, size). */
    public static function clean(array $in): array
    {
        $drive = strtoupper(trim((string) ($in['drive'] ?? '')));
        $cyl   = (int) ($in['cyl'] ?? 0);
        $size  = trim((string) ($in['size'] ?? ''));
        return [
            'drive' => in_array($drive, self::DRIVES, true) ? $drive : '',
            'cyl'   => in_array($cyl, [3, 4, 5, 6, 8, 10, 12], true) ? $cyl : 0,
            'size'  => preg_match('/^\d{1,2}(\.\d)?$/', $size) ? $size : '',
        ];
    }

    /**
     * Add the filters to a parts_inventory query. $a is the table alias WITHOUT the dot ('p' -> 'p.drive_type').
     */
    public static function apply($q, array $f, string $a = ''): void
    {
        $c = fn (string $col) => $a === '' ? $col : $a . '.' . $col;
        $f = self::clean($f);

        if ($f['drive'] !== '') {
            // 4WD and AWD are often used for each other on the yard floor, so either one finds both.
            $set = in_array($f['drive'], ['AWD', '4WD'], true) ? ['AWD', '4WD'] : [$f['drive']];
            $q->whereIn($c('drive_type'), $set);
        }

        $cyl  = $f['cyl'] ?: null;
        $size = $f['size'] !== '' ? (float) $f['size'] : null;
        if (!$cyl && $size === null) return;

        $codes = EngineSpecs::codesFor($cyl, $size);
        $q->where(function ($w) use ($c, $cyl, $size, $codes) {
            // displacement text, e.g. "2.4L" / "3.5L V6"
            $w->where(function ($d) use ($c, $cyl, $size) {
                $d->whereNotNull($c('engine_displacement'));
                if ($size !== null) $d->where($c('engine_displacement'), 'like', number_format($size, 1, '.', '') . '%');
                if ($cyl) {
                    $d->where(function ($cc) use ($c, $cyl) {
                        foreach (["%V{$cyl}%", "%I{$cyl}%", "%L{$cyl}%", "%H{$cyl}%", "%{$cyl} cyl%", "%{$cyl}-cyl%", "%{$cyl}cyl%"] as $pat) {
                            $cc->orWhere($c('engine_displacement'), 'like', $pat);
                        }
                    });
                }
            });
            // or a known engine code
            if ($codes) {
                $w->orWhere(function ($e) use ($c, $codes) {
                    foreach ($codes as $code) $e->orWhere($c('engine_code_oem'), 'like', $code . '%');
                });
            }
        });
    }

    /** Column(s) for sorting, shared by admin lists. */
    public static function sortColumns(string $key, string $a = ''): ?array
    {
        $c = fn (string $col) => $a === '' ? $col : $a . '.' . $col;
        return match ($key) {
            'trim'   => [$c('donor_trim')],
            'drive'  => [$c('drive_type')],
            'engine' => [$c('engine_displacement'), $c('engine_code_oem')],
            default  => null,
        };
    }

    /**
     * Litres and cylinders for one part (for showing and for sorting): displacement text first, then the engine code.
     * @return array{litres:?float,cylinders:?int}
     */
    public static function specsFor(object $part): array
    {
        $litres = null; $cyl = null;
        $disp = (string) ($part->engine_displacement ?? '');
        if (preg_match('/(\d{1,2}(?:\.\d)?)/', $disp, $m) && (float) $m[1] > 0 && (float) $m[1] < 12) $litres = (float) $m[1];
        if (preg_match('/[VILH]\s?(\d{1,2})\b/i', $disp, $m)) $cyl = (int) $m[1];
        elseif (preg_match('/(\d{1,2})\s*-?\s*cyl/i', $disp, $m)) $cyl = (int) $m[1];

        $spec = EngineSpecs::forCode($part->engine_code_oem ?? null);
        if ($spec) {
            $litres = $litres ?? $spec['litres'];
            $cyl    = $cyl ?? $spec['cylinders'];
        }
        return ['litres' => $litres, 'cylinders' => $cyl];
    }

    /** Drive types that exist on Available stock (for filter dropdowns), cached for ten minutes. */
    public static function driveOptions(): array
    {
        return Cache::remember('pf_drive_options', 600, function () {
            try {
                $have = DB::table('parts_inventory')->where('status', 'Available')->whereNotNull('drive_type')
                    ->where('drive_type', '!=', '')->distinct()->pluck('drive_type')->map(fn ($d) => strtoupper($d))->all();
            } catch (\Throwable $e) { return self::DRIVES; }
            $list = array_values(array_intersect(self::DRIVES, $have));
            return $list ?: self::DRIVES;
        });
    }

    /** Engine sizes (litres) seen on Available stock, ascending, cached for ten minutes. */
    public static function sizeOptions(): array
    {
        return Cache::remember('pf_size_options', 600, function () {
            $sizes = [];
            try {
                $rows = DB::table('parts_inventory')->where('status', 'Available')
                    ->where(fn ($w) => $w->whereNotNull('engine_displacement')->orWhereNotNull('engine_code_oem'))
                    ->select('engine_displacement', 'engine_code_oem')->distinct()->limit(2000)->get();
                foreach ($rows as $r) {
                    $l = self::specsFor($r)['litres'];
                    if ($l) $sizes[number_format($l, 1, '.', '')] = true;
                }
            } catch (\Throwable $e) {}
            $out = array_keys($sizes);
            sort($out, SORT_NUMERIC);
            return $out;
        });
    }
}
