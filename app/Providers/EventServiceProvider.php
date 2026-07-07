<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

/**
 * FILE: app/Providers/EventServiceProvider.php
 *
 * AutoZenith Event → Listener registry.
 *
 * All custom events are under App\Events\
 * All custom listeners are under App\Listeners\
 *
 * After any change here, run:
 *   php artisan event:clear
 *   php artisan optimize:clear
 *
 * To auto-discover events (Laravel 13 default), the $shouldDiscoverEvents
 * flag below is set to true — but the $listen array below takes precedence
 * for everything explicitly listed, which is what we want for clarity.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [

        // ── Laravel built-in ──────────────────────────────────────────
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // ── AutoZenith: Parts & Inventory ─────────────────────────────

        /**
         * Fired whenever a parts_inventory item is sold via invoice or order.
         * Dispatched from InvoiceController and OrderAdminController
         * when a sale is confirmed/paid.
         *
         * Usage:
         *   \App\Events\PartSold::dispatch(
         *       $partsInventoryId,
         *       $invoiceId,
         *       $amountReceived,
         *       $currencyCode,
         *       'invoice' // or 'order'
         *   );
         */
        \App\Events\PartSold::class => [
            // Updates part_group_revenue and vehicle_revenue_projections.
            // Sets break_even_reached_at when total cost is fully recovered.
            \App\Listeners\UpdateVehicleROI::class,
        ],

    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     * Set to false so the $listen array above is the single source of truth.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
