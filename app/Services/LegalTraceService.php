<?php
// FILE: app/Services/LegalTraceService.php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * LegalTraceService — Phase 6 Powerlink Adoption
 *
 * Centralises legal trace and major component enforcement logic
 * used at two points in the workflow:
 *
 *   1. Harvest — checked in HarvestController::saveParts()
 *      (already saves legal_trace_required + is_major_component flags
 *       from part_type_rules — see Phase 3)
 *
 *   2. Point of Sale — checked in InvoiceController::storeManual()
 *      and OrderAdminController before confirming payment
 *
 * Usage:
 *   [$ok, $errors] = LegalTraceService::checkCart($lineItems, $buyerDoc);
 *   if (!$ok) return back()->with('error', implode(' | ', $errors));
 */
class LegalTraceService
{
    /**
     * Validate that all legal-trace parts in the cart have buyer
     * documentation, and collect which parts are major components.
     *
     * @param  iterable  $lineItems   Each item must have: part_id, part_name
     * @param  string|null $buyerDoc  Buyer ID / document reference from the form
     * @return array{bool, string[]}  [passed, errorMessages]
     */
    public static function checkCart(iterable $lineItems, ?string $buyerDoc): array
    {
        $errors          = [];
        $legalTraceParts = [];
        $majorParts      = [];

        foreach ($lineItems as $item) {
            if (empty($item->part_id ?? $item['part_id'] ?? null)) continue;

            $partId = $item->part_id ?? $item['part_id'];
            $part   = DB::table('parts_inventory')
                ->where('id', $partId)
                ->select('part_name', 'part_code', 'legal_trace_required', 'is_major_component')
                ->first();

            if (!$part) continue;

            if ($part->legal_trace_required) {
                $legalTraceParts[] = "{$part->part_code} — {$part->part_name}";
            }
            if ($part->is_major_component) {
                $majorParts[] = "{$part->part_code} — {$part->part_name}";
            }
        }

        // Legal trace: buyer doc is required
        if (!empty($legalTraceParts) && empty(trim($buyerDoc ?? ''))) {
            $errors[] = 'The following parts require buyer documentation (government ID, receipt, or title number) before sale can be completed: '
                . implode(', ', $legalTraceParts)
                . '. Please enter the buyer document reference in the Legal Trace field.';
        }

        return [empty($errors), $errors];
    }

    /**
     * Save buyer documentation to invoice_items and order_items
     * for all legal-trace parts in a completed sale.
     *
     * Call AFTER the invoice/order has been saved to DB.
     *
     * @param int    $invoiceId  Invoice or order ID
     * @param string $table      'invoice_items' or 'order_items'
     * @param string $buyerDoc   Document reference entered by staff
     */
    public static function recordBuyerDoc(int $invoiceId, string $table, string $buyerDoc): void
    {
        if (empty(trim($buyerDoc))) return;

        // Get all item part_ids for this invoice/order
        $fkColumn = $table === 'invoice_items' ? 'invoice_id' : 'order_id';
        $itemIds  = DB::table($table)
            ->where($fkColumn, $invoiceId)
            ->whereNotNull('part_id')
            ->pluck('part_id', 'id');

        if ($itemIds->isEmpty()) return;

        // Find which of those parts have legal_trace_required = 1
        $legalPartIds = DB::table('parts_inventory')
            ->whereIn('id', $itemIds->values())
            ->where('legal_trace_required', 1)
            ->pluck('id')
            ->flip(); // flip for O(1) lookup

        // Update the matching line items with the buyer doc reference
        foreach ($itemIds as $lineItemId => $partId) {
            if ($legalPartIds->has($partId)) {
                DB::table($table)
                    ->where('id', $lineItemId)
                    ->update([
                        'legal_trace_doc' => $buyerDoc,
                        'updated_at'      => now(),
                    ]);

                // Also stamp the parts_inventory row itself
                DB::table('parts_inventory')
                    ->where('id', $partId)
                    ->whereNull('legal_trace_doc')
                    ->update([
                        'legal_trace_doc' => $buyerDoc,
                        'updated_at'      => now(),
                    ]);
            }
        }
    }

    /**
     * Check if any part in a cart is a major component.
     * Used to decide whether to require supervisor PIN at POS.
     *
     * Returns array of major component descriptions, or empty array.
     */
    public static function getMajorComponents(iterable $lineItems): array
    {
        $majorParts = [];

        foreach ($lineItems as $item) {
            $partId = $item->part_id ?? $item['part_id'] ?? null;
            if (!$partId) continue;

            $part = DB::table('parts_inventory')
                ->where('id', $partId)
                ->where('is_major_component', 1)
                ->select('part_name', 'part_code')
                ->first();

            if ($part) {
                $majorParts[] = "{$part->part_code} — {$part->part_name}";
            }
        }

        return $majorParts;
    }

    /**
     * Quick check: does this single part need legal trace?
     */
    public static function partNeedsLegalTrace(int $partId): bool
    {
        return DB::table('parts_inventory')
            ->where('id', $partId)
            ->where('legal_trace_required', 1)
            ->exists();
    }

    /**
     * Quick check: is this part a major component?
     */
    public static function partIsMajorComponent(int $partId): bool
    {
        return DB::table('parts_inventory')
            ->where('id', $partId)
            ->where('is_major_component', 1)
            ->exists();
    }
}
