<?php
// FILE: app/Services/ConsumableBatches.php
//
// Writes and reads the batch ledger (consumable_batches). Every function is safe if the table does not exist
// yet (before the migration runs): it simply does nothing, so stock changes are never blocked by a missing table.

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

class ConsumableBatches
{
    public static function available(): bool
    {
        static $ok = null;
        if ($ok === null) {
            try { $ok = Schema::hasTable('consumable_batches'); } catch (\Throwable $e) { $ok = false; }
        }
        return $ok;
    }

    /** Next batch number for a product: CON-00036-B261105-01 (code, B + date, running number for that day). */
    public static function nextBatchNo(?string $partCode): string
    {
        $prefix = ($partCode ?: 'ITEM') . '-B' . now()->format('ymd') . '-';
        $n = 1;
        if (self::available()) {
            $n = (int) DB::table('consumable_batches')->where('batch_no', 'like', $prefix . '%')->count() + 1;
        }
        return $prefix . str_pad((string) $n, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Record units added (or removed, if negative) to one stock row.
     * @param object $item  the parts_inventory row (needs id, part_code, location)
     * @return string|null  the batch number, or null if the ledger is not available
     */
    public static function record(object $item, int $qty, ?int $balanceAfter, string $reason = 'restock', ?string $supplierRef = null, ?string $notes = null): ?string
    {
        if (!self::available()) return null;
        try {
            $batch = self::nextBatchNo($item->part_code ?? null);
            DB::table('consumable_batches')->insert([
                'item_id'       => $item->id,
                'part_code'     => $item->part_code ?? null,
                'location'      => $item->location ?? null,
                'batch_no'      => $batch,
                'qty'           => $qty,
                'balance_after' => $balanceAfter,
                'reason'        => $reason,
                'supplier_ref'  => $supplierRef !== null && trim($supplierRef) !== '' ? mb_substr(trim($supplierRef), 0, 80) : null,
                'notes'         => $notes,
                'staff_id'      => Session::get('staff_id'),
                'staff_name'    => Session::get('staff_name') ?? 'System',
                'created_at'    => now(),
            ]);
            return $batch;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Batch record failed for item ' . ($item->id ?? '?') . ': ' . $e->getMessage());
            return null;
        }
    }

    /** Latest entries per stock row, for the list screen: [item_id => [rows...]]. */
    public static function recentFor(array $itemIds, int $perItem = 5): array
    {
        if (!$itemIds || !self::available()) return [];
        $out = [];
        $rows = DB::table('consumable_batches')->whereIn('item_id', $itemIds)->orderByDesc('id')->limit(max(50, count($itemIds) * $perItem * 2))->get();
        foreach ($rows as $r) {
            if (count($out[$r->item_id] ?? []) < $perItem) $out[$r->item_id][] = $r;
        }
        return $out;
    }
}
