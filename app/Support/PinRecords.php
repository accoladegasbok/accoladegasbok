<?php
// FILE: app/Support/PinRecords.php
//
// Looks up verified pin-count records (donor VIN + connector photo, approved by a manager
// or admin) and lays them over the built-in OemDatabase result. A verified record wins.
// Without one, the OemDatabase value stays but is flagged as not verified.
// Never throws: if the table does not exist yet, the OemDatabase result passes through.

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PinRecords
{
    public static function apply(array $oem, string $make, string $model, int $year): array
    {
        $oem['pin_verified']  = false;
        $oem['pin_record_id'] = null;

        try {
            if (!$year || !Schema::hasTable('pin_records')) return $oem;

            $q = DB::table('pin_records')
                ->where('status', 'verified')
                ->whereRaw('UPPER(make) = ?', [strtoupper(trim($make))])
                ->whereRaw('UPPER(model) = ?', [strtoupper(trim($model))])
                ->where('year_from', '<=', $year)
                ->where('year_to', '>=', $year);

            if (!empty($oem['transmission_code'])) {
                $q->where(function ($g) use ($oem) {
                    $g->whereNull('transmission_code')->orWhere('transmission_code', $oem['transmission_code']);
                });
            }
            if (!empty($oem['engine_code'])) {
                $q->where(function ($g) use ($oem) {
                    $g->whereNull('engine_code')->orWhere('engine_code', $oem['engine_code']);
                });
            }

            $rec = $q->orderByDesc('verified_at')->first();
            if ($rec) {
                $oem['pin_count']     = (int) $rec->pin_count;
                $oem['pin_verified']  = true;
                $oem['pin_record_id'] = $rec->id;
                $oem['pin_count_variants'] = null;
            }
        } catch (\Throwable $e) {
            // keep the built-in result
        }
        return $oem;
    }
}
