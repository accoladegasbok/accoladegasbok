<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired whenever a sale happens that should count toward revenue
 * reporting — a parts_inventory item, OR a whole-vehicle resale that
 * has no parts_inventory row at all.
 * Listeners update part_group_revenue and (when a donor vehicle is
 * involved) vehicle_revenue_projections.
 *
 * Dispatched from:
 *   - InvoiceController (manual invoices with parts, service invoices,
 *     and vehicle resales via storeCarSale)
 *   - OrderAdminController (POS / online orders)
 *
 * Usage (part sale — unchanged):
 *   PartSold::dispatch($partId, $invoiceId, $amountReceived, $currencyCode, $invoiceType);
 *
 * Usage (vehicle resale — NEW, no parts_inventory row involved):
 *   PartSold::dispatch(null, $invoiceId, $amountReceived, $currencyCode, 'invoice',
 *       overridePartCategory: 'Vehicle Sale',
 *       overridePartName: "{$year} {$brand} {$model}");
 *
 * FIXED: partsInventoryId was previously a required non-nullable int,
 * which meant this event structurally could not represent a sale with
 * no parts_inventory row — like a whole-vehicle resale. Existing
 * dispatch() calls that always pass a real int are unaffected.
 */
class PartSold
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ?int   $partsInventoryId,
        public int    $invoiceId,
        public float  $amountReceived,
        public string $currencyCode  = 'NGN',
        public string $invoiceType   = 'invoice', // 'invoice' | 'order'
        // Only used when partsInventoryId is null — lets the listener
        // log something meaningful (e.g. "Vehicle Sale" / "2022 Toyota
        // Highlander XLE") instead of having no part row to read from.
        public ?string $overridePartCategory = null,
        public ?string $overridePartName     = null,
    ) {}
}
