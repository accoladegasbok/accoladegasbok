<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired whenever a parts_inventory item is sold.
 * Listeners update part_group_revenue and vehicle_revenue_projections.
 *
 * Dispatched from:
 *   - InvoiceController (manual invoices with parts)
 *   - OrderAdminController (POS / online orders)
 *
 * Usage:
 *   PartSold::dispatch($partId, $invoiceId, $amountReceived, $currencyCode, $invoiceType);
 */
class PartSold
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int    $partsInventoryId,
        public int    $invoiceId,
        public float  $amountReceived,
        public string $currencyCode  = 'NGN',
        public string $invoiceType   = 'invoice', // 'invoice' | 'order'
    ) {}
}
