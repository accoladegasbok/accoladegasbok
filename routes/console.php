<?php
// FILE: routes/console.php
// Laravel 11+ / 13 scheduler — registered here instead of Kernel.php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// ── Built-in example (keep or remove as preferred) ────────────────
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ═══════════════════════════════════════════════════════════════
// AutoZenith Scheduled Tasks
// ═══════════════════════════════════════════════════════════════

// ── Phase 5B: Release expired invoice holds ───────────────────────
// Runs daily at 2am — expires stale Open invoices past their
// expiration_date and releases Reserved parts back to Available.
// Safe to run manually: php artisan autozenith:release-holds --dry-run
Schedule::command('autozenith:release-holds')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('autozenith:release-holds scheduled run failed');
    });

// ── Future scheduled tasks (add here as needed) ───────────────────
//
// Daily inventory snapshot for audit trail:
// Schedule::command('autozenith:snapshot-inventory')->dailyAt('00:30');
//
// Weekly ROI summary email to admin:
// Schedule::command('autozenith:roi-weekly-report')->weeklyOn(1, '08:00');
//
// Monthly commission summary:
// Schedule::command('autozenith:commission-monthly')->monthlyOn(1, '07:00');
