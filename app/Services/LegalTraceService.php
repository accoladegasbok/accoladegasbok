<?php
// FILE: app/Services/LegalTraceService.php
namespace App\Services;
use Illuminate\Support\Facades\DB;
/**
 * Legal Trace Service
 *
 * Handles buyer documentation requirements for legal-trace-flagged
 * parts (engines, transmissions, catalytic converters, airbags, etc.)
 * where regulations require recording buyer ID/documentation at time
 * of sale.
 */
class LegalTraceService
{
    /**
     * Determine if a part requires legal trace documentation based on
     * its category and major-component flag.
     */
    public static function requiresTrace(string $partCategory, bool $isMajorComponent = false): bool
    {
        $traceCategories = ['Engine', 'Transmission', 'Airbag', 'Catalytic Converter'];
        return $isMajorComponent || in_array($partCategory, $traceCategories);
    }

    /**
     * Check a cart of line items for legal-trace requirements before
     * the sale is finalized. Returns [bool $ok, array $errors].
     *
     * If any item requires legal trace documentation and no buyer doc
     * reference was provided, the sale is blocked.
     *
     * @param \Illuminate\Support\Collection $lineItems  Collection of line-item objects with a part_id + part_name
     * @param string|null                    $buyerDoc   Buyer document reference entered by staff (optional)
     */
    public static function checkCart($lineItems, ?string $buyerDoc): array
    {
        $flaggedParts = [];

        foreach ($lineItems as $li) {
            if (empty($li->part_id)) continue;

            $part = DB::table('parts_inventory')->where('id', $li->part_id)->first();
            if (!$part) continue;

            $requiresTrace = ($part->legal_trace_required ?? false) || ($part->is_major_component ?? false);

            if ($requiresTrace) {
                $flaggedParts[] = $li->part_name ?: ($part->part_code ?? 'Part #' . $li->part_id);
            }
        }

        if (empty($flaggedParts)) {
            return [true, []];
        }

        if (empty(trim($buyerDoc ?? ''))) {
            return [false, [
                'Buyer documentation is required for legal-trace item(s) in this sale: '
                    . implode(', ', $flaggedParts) . '. Please enter a buyer document reference to proceed.'
            ]];
        }

        return [true, []];
    }

    /**
     * Save buyer documentation to invoice_items and order_items
     * for all legal-trace parts in a completed sale.
     *
     * Call AFTER the invoice/order has been saved to DB.
     *
     * @param int         $invoiceId  Invoice or order ID
     * @param string      $table      'invoice_items' or 'order_items'
     * @param string|null $buyerDoc   Document reference entered by staff (optional)
     */
    public static function recordBuyerDoc(int $invoiceId, string $table, ?string $buyerDoc): void
    {
        if (empty(trim($buyerDoc ?? ''))) return;
        // Get all item part_ids for this invoice/order
        $fkColumn = $table === 'invoice_items' ? 'invoice_id' : 'order_id';
        $itemIds  = DB::table($table)
            ->where($fkColumn, $invoiceId)
            ->whereNotNull('part_id')
            ->pluck('part_id', 'id');
        if ($itemIds->isEmpty()) return;
        // Check which of these parts actually require legal trace
        $legalTraceParts = DB::table('parts_inventory')
            ->whereIn('id', $itemIds->values())
            ->where(function ($q) {
                $q->where('legal_trace_required', true)
                  ->orWhere('is_major_component', true);
            })
            ->pluck('id');
        if ($legalTraceParts->isEmpty()) return;
        // Update the item rows for legal-trace parts with the buyer doc reference
        $itemIdsToUpdate = $itemIds->filter(fn($partId) => $legalTraceParts->contains($partId))->keys();
        if ($itemIdsToUpdate->isEmpty()) return;
        DB::table($table)
            ->whereIn('id', $itemIdsToUpdate)
            ->update([
                'buyer_doc_ref' => trim($buyerDoc),
                'updated_at'    => now(),
            ]);
    }
    /**
     * Get all legal-trace parts in a given sale that are still missing
     * buyer documentation — used to warn staff before finalizing.
     */
    public static function missingDocsFor(int $invoiceId, string $table): \Illuminate\Support\Collection
    {
        $fkColumn = $table === 'invoice_items' ? 'invoice_id' : 'order_id';
        return DB::table($table . ' as i')
            ->leftJoin('parts_inventory as p', 'p.id', '=', 'i.part_id')
            ->where('i.' . $fkColumn, $invoiceId)
            ->where(function ($q) {
                $q->where('p.legal_trace_required', true)
                  ->orWhere('p.is_major_component', true);
            })
            ->whereNull('i.buyer_doc_ref')
            ->select('i.id', 'p.part_name', 'p.part_code')
            ->get();
    }
}
