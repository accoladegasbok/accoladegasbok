<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 6 of 6 — Powerlink Adoption Phase 1
 * Enhance customers with trade/dealer flag for wholesale pricing.
 * When a customer is flagged as trade/dealer, wholesale prices
 * are auto-applied at POS/quote time based on part_type_rules.wholesale_margin_pct.
 * Staff can override to retail on a per-line basis (logged).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Customers table ───────────────────────────────────────────────
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {

                if (!Schema::hasColumn('customers', 'is_trade_customer')) {
                    $table->tinyInteger('is_trade_customer')->default(0)
                        ->comment('1 = dealer/trade — wholesale prices auto-applied at POS');
                }

                if (!Schema::hasColumn('customers', 'trade_discount_pct')) {
                    $table->decimal('trade_discount_pct', 5, 2)->nullable()
                        ->comment('Override trade discount % — if null, uses part_type_rules default');
                }

                if (!Schema::hasColumn('customers', 'credit_limit')) {
                    $table->decimal('credit_limit', 12, 2)->nullable()
                        ->comment('Maximum open tab / credit balance allowed');
                }
            });
        }

        // ── Staff discount cap (already exists per your system) ───────────
        // No changes needed — your existing staff discount cap covers this.

        // ── Parts inventory: add wholesale applied flag for audit ─────────
        if (Schema::hasTable('parts_inventory')) {
            Schema::table('parts_inventory', function (Blueprint $table) {
                // Track which customer type this part was last quoted/sold at
                // Useful for pricing analytics
                if (!Schema::hasColumn('parts_inventory', 'last_quoted_type')) {
                    $table->enum('last_quoted_type', ['retail', 'wholesale', 'none'])
                        ->default('none')
                        ->comment('Whether last quote was at retail or wholesale price');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                $cols = ['is_trade_customer', 'trade_discount_pct', 'credit_limit'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('customers', $col)) $table->dropColumn($col);
                }
            });
        }

        if (Schema::hasTable('parts_inventory')) {
            Schema::table('parts_inventory', function (Blueprint $table) {
                if (Schema::hasColumn('parts_inventory', 'last_quoted_type')) {
                    $table->dropColumn('last_quoted_type');
                }
            });
        }
    }
};
