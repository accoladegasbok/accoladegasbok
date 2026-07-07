<?php
// FILE: app/Console/Commands/ReleaseExpiredHolds.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Runs daily (via Laravel scheduler) to:
 *  1. Expire invoices past their expiration_date (status → Expired)
 *  2. Release any Reserved parts tied to those expired invoices
 *     back to Available so they can be sold to someone else
 *
 * Register in routes/console.php (Laravel 11+/13):
 *   Schedule::command('autozenith:release-holds')->dailyAt('02:00');
 *
 * Or in app/Console/Kernel.php (older):
 *   $schedule->command('autozenith:release-holds')->dailyAt('02:00');
 *
 * Manual run:
 *   php artisan autozenith:release-holds
 *   php artisan autozenith:release-holds --dry-run
 */
class ReleaseExpiredHolds extends Command
{
    protected $signature   = 'autozenith:release-holds {--dry-run : Preview what would be released without making changes}';
    protected $description = 'Expire stale invoice holds and release Reserved parts back to Available';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $now    = now();

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be made.');
        }

        // ── 1. Find expired invoices still marked Open ─────────────────
        $expiredInvoices = DB::table('invoices')
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<', $now)
            ->where('quote_status', 'Open')
            ->whereNull('deleted_at')
            ->select('id', 'invoice_no', 'customer_name', 'expiration_date')
            ->get();

        if ($expiredInvoices->isEmpty()) {
            $this->info('No expired holds to release.');
            return self::SUCCESS;
        }

        $this->info("Found {$expiredInvoices->count()} expired invoice(s).");

        $releasedParts   = 0;
        $expiredInvoices->each(function ($invoice) use ($dryRun, &$releasedParts) {

            $this->line("  → Invoice #{$invoice->invoice_no} ({$invoice->customer_name}) expired at {$invoice->expiration_date}");

            // Find Reserved parts linked to this invoice's line items
            $partIds = DB::table('invoice_items')
                ->where('invoice_id', $invoice->id)
                ->whereNotNull('part_id')
                ->pluck('part_id');

            if ($partIds->isNotEmpty()) {
                $reservedParts = DB::table('parts_inventory')
                    ->whereIn('id', $partIds)
                    ->where('status', 'Reserved')
                    ->pluck('part_code', 'id');

                if ($reservedParts->isNotEmpty()) {
                    $this->line("     Releasing " . $reservedParts->count() . " Reserved part(s): " . $reservedParts->implode(', '));

                    if (!$dryRun) {
                        DB::table('parts_inventory')
                            ->whereIn('id', $reservedParts->keys())
                            ->update(['status' => 'Available', 'updated_at' => now()]);
                    }

                    $releasedParts += $reservedParts->count();
                }
            }

            // Mark invoice as Expired
            if (!$dryRun) {
                DB::table('invoices')->where('id', $invoice->id)->update([
                    'quote_status' => 'Expired',
                    'updated_at'   => now(),
                ]);
            }
        });

        $summary = "Released {$releasedParts} part(s) from {$expiredInvoices->count()} expired hold(s).";
        $this->info($summary . ($dryRun ? ' (DRY RUN)' : ''));

        if (!$dryRun) {
            Log::info("ReleaseExpiredHolds: {$summary}");
        }

        return self::SUCCESS;
    }
}
