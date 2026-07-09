<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 5b — Fix for AutoZenith schema
 * Targets the ACTUAL tables: invoices, invoice_items, order_items
 * (Migration 5 targeted 'quotes'/'quote_items' which don't exist here)
 *
 * Adds:
 * - invoices: expiration_date, core_price_total, quote_status
 * - invoice_items: price_wholesale, core_price, conditions_and_options, legal_trace_doc
 * - order_items: price_wholesale, core_price, conditions_and_options, legal_trace_doc
 * - manual_invoices: core_price_total (for service receipts)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── invoices table ────────────────────────────────────────────────
        Schema::table('invoices', function (Blueprint $table) {

            if (!Schema::hasColumn('invoices', 'quote_status')) {
                $table->enum('quote_status', ['Open', 'Closed', 'Expired', 'Converted'])
                    ->default('Open')
                    ->after('id')
                    ->comment('Open = active; Converted = paid/fulfilled');
            }

            if (!Schema::hasColumn('invoices', 'expiration_date')) {
                $table->timestamp('expiration_date')->nullable()
                    ->comment('For quoted invoices — after this date holds release automatically');
            }

            if (!Schema::hasColumn('invoices', 'core_price_total')) {
                $table->decimal('core_price_total', 12, 2)->default(0)
                    ->comment('Total core charges on this invoice');
            }

            if (!Schema::hasColumn('invoices', 'is_trade_sale')) {
                $table->tinyInteger('is_trade_sale')->default(0)
                    ->comment('1 = wholesale/trade customer — wholesale prices applied');
            }
        });

        // ── invoice_items table ───────────────────────────────────────────
        Schema::table('invoice_items', function (Blueprint $table) {

            if (!Schema::hasColumn('invoice_items', 'price_wholesale')) {
                $table->decimal('price_wholesale', 12, 2)->nullable()
                    ->comment('Wholesale/trade price — for margin reporting');
            }

            if (!Schema::hasColumn('invoice_items', 'core_price')) {
                $table->decimal('core_price', 12, 2)->default(0)
                    ->comment('Core charge on this line item — refunded on core return');
            }

            if (!Schema::hasColumn('invoice_items', 'conditions_and_options')) {
                $table->string('conditions_and_options', 36)->nullable()
                    ->comment('Condition detail copied from parts_inventory at sale time');
            }

            if (!Schema::hasColumn('invoice_items', 'legal_trace_doc')) {
                $table->string('legal_trace_doc', 191)->nullable()
                    ->comment('Buyer ID / document ref for legal trace parts');
            }
        });

        // ── order_items table ─────────────────────────────────────────────
        Schema::table('order_items', function (Blueprint $table) {

            if (!Schema::hasColumn('order_items', 'price_wholesale')) {
                $table->decimal('price_wholesale', 12, 2)->nullable()
                    ->comment('Wholesale price if trade customer');
            }

            if (!Schema::hasColumn('order_items', 'core_price')) {
                $table->decimal('core_price', 12, 2)->default(0)
                    ->comment('Core charge on this order line');
            }

            if (!Schema::hasColumn('order_items', 'conditions_and_options')) {
                $table->string('conditions_and_options', 36)->nullable()
                    ->comment('Condition detail from parts_inventory');
            }

            if (!Schema::hasColumn('order_items', 'legal_trace_doc')) {
                $table->string('legal_trace_doc', 191)->nullable()
                    ->comment('Buyer document ref for legal trace parts');
            }
        });

        // ── manual_invoices (service receipts) ────────────────────────────
        if (Schema::hasTable('manual_invoices')) {
            Schema::table('manual_invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('manual_invoices', 'core_price_total')) {
                    $table->decimal('core_price_total', 12, 2)->default(0)
                        ->comment('Core charges on service invoice');
                }
                if (!Schema::hasColumn('manual_invoices', 'is_trade_sale')) {
                    $table->tinyInteger('is_trade_sale')->default(0);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            foreach (['quote_status', 'expiration_date', 'core_price_total', 'is_trade_sale'] as $col) {
                if (Schema::hasColumn('invoices', $col)) $table->dropColumn($col);
            }
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            foreach (['price_wholesale', 'core_price', 'conditions_and_options', 'legal_trace_doc'] as $col) {
                if (Schema::hasColumn('invoice_items', $col)) $table->dropColumn($col);
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            foreach (['price_wholesale', 'core_price', 'conditions_and_options', 'legal_trace_doc'] as $col) {
                if (Schema::hasColumn('order_items', $col)) $table->dropColumn($col);
            }
        });

        if (Schema::hasTable('manual_invoices')) {
            Schema::table('manual_invoices', function (Blueprint $table) {
                foreach (['core_price_total', 'is_trade_sale'] as $col) {
                    if (Schema::hasColumn('manual_invoices', $col)) $table->dropColumn($col);
                }
            });
        }
    }
};
