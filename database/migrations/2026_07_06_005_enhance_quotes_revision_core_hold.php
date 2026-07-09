<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration 5 of 6 — Powerlink Adoption Phase 1
 * Enhance quotes with:
 * - Revision tracking (Powerlink: QUOTE.Revision)
 * - Hold expiration date (Powerlink: QUOTE_LINEITEM.HoldExpirationDate)
 * - Core price (Powerlink: QUOTE_LINEITEM.CorePrice)
 * - Quote status enum (Powerlink: QuoteStatus)
 *
 * NOTE: Adjust table names below if your quotes table
 * is named differently (e.g. 'sales_quotes', 'quotations').
 * Check with: php artisan tinker → Schema::getTableListing()
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Quotes master table ───────────────────────────────────────────
        if (Schema::hasTable('quotes')) {
            Schema::table('quotes', function (Blueprint $table) {

                if (!Schema::hasColumn('quotes', 'revision')) {
                    $table->unsignedTinyInteger('revision')->default(1)
                        ->after('id')
                        ->comment('Revision number — increments each time quote is updated');
                }

                if (!Schema::hasColumn('quotes', 'is_latest_revision')) {
                    $table->tinyInteger('is_latest_revision')->default(1)
                        ->after('revision')
                        ->comment('1 = active revision; 0 = historical, read-only');
                }

                if (!Schema::hasColumn('quotes', 'quote_status')) {
                    $table->enum('quote_status', ['Open', 'Closed', 'Expired', 'Converted'])
                        ->default('Open')
                        ->after('is_latest_revision')
                        ->comment('Converted = turned into an invoice');
                }

                if (!Schema::hasColumn('quotes', 'expiration_date')) {
                    $table->timestamp('expiration_date')->nullable()
                        ->after('quote_status')
                        ->comment('After this date quote auto-expires and holds are released');
                }

                if (!Schema::hasColumn('quotes', 'core_price_total')) {
                    $table->decimal('core_price_total', 12, 2)->default(0)
                        ->after('expiration_date')
                        ->comment('Total core charges on this quote');
                }

                if (!Schema::hasColumn('quotes', 'parent_quote_id')) {
                    $table->unsignedBigInteger('parent_quote_id')->nullable()
                        ->after('core_price_total')
                        ->comment('Points to original quote ID when this is a revision');
                }
            });

            // Set default expiration = 48hrs from now for existing open quotes
            DB::table('quotes')
                ->whereNull('expiration_date')
                ->update(['expiration_date' => now()->addHours(48)]);
        }

        // ── Quote line items table ────────────────────────────────────────
        // Adjust table name if yours differs (e.g. 'quote_items', 'quote_lines')
        if (Schema::hasTable('quote_items')) {
            Schema::table('quote_items', function (Blueprint $table) {

                if (!Schema::hasColumn('quote_items', 'hold_expiration_date')) {
                    $table->timestamp('hold_expiration_date')->nullable()
                        ->comment('Part hold released if quote expires before conversion');
                }

                if (!Schema::hasColumn('quote_items', 'core_price')) {
                    $table->decimal('core_price', 12, 2)->default(0)
                        ->comment('Core charge for this line — refunded on core return');
                }

                if (!Schema::hasColumn('quote_items', 'price_wholesale')) {
                    $table->decimal('price_wholesale', 12, 2)->nullable()
                        ->comment('Wholesale/trade price if different from quoted price');
                }

                if (!Schema::hasColumn('quote_items', 'conditions_and_options')) {
                    $table->string('conditions_and_options', 36)->nullable()
                        ->comment('Short condition detail from parts_inventory');
                }
            });
        }

        // ── Invoice line items — add wholesale + core price ───────────────
        // Adjust table name if yours differs (e.g. 'invoice_lines', 'order_items')
        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {

                if (!Schema::hasColumn('invoice_items', 'price_wholesale')) {
                    $table->decimal('price_wholesale', 12, 2)->nullable()
                        ->comment('Wholesale price if trade customer — for reporting');
                }

                if (!Schema::hasColumn('invoice_items', 'core_price')) {
                    $table->decimal('core_price', 12, 2)->default(0)
                        ->comment('Core charge on this line item');
                }

                if (!Schema::hasColumn('invoice_items', 'conditions_and_options')) {
                    $table->string('conditions_and_options', 36)->nullable()
                        ->comment('Condition detail copied from parts_inventory at time of sale');
                }

                if (!Schema::hasColumn('invoice_items', 'legal_trace_doc')) {
                    $table->string('legal_trace_doc', 191)->nullable()
                        ->comment('Buyer documentation ref for legal trace parts sold');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quotes')) {
            Schema::table('quotes', function (Blueprint $table) {
                $cols = ['revision', 'is_latest_revision', 'quote_status',
                         'expiration_date', 'core_price_total', 'parent_quote_id'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('quotes', $col)) $table->dropColumn($col);
                }
            });
        }

        if (Schema::hasTable('quote_items')) {
            Schema::table('quote_items', function (Blueprint $table) {
                $cols = ['hold_expiration_date', 'core_price', 'price_wholesale', 'conditions_and_options'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('quote_items', $col)) $table->dropColumn($col);
                }
            });
        }

        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $cols = ['price_wholesale', 'core_price', 'conditions_and_options', 'legal_trace_doc'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('invoice_items', $col)) $table->dropColumn($col);
                }
            });
        }
    }
};
