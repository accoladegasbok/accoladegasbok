<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 3 of 6 — Powerlink Adoption Phase 1
 * Enhance parts_inventory with:
 * - Wholesale price (Powerlink: WholesalePrice)
 * - Conditions & options short text (Powerlink: ConditionsAndOptions)
 * - Legal trace flag (Powerlink: PART_TYPE.LegalTraceFlag)
 * - Major component flag (Powerlink: PART_TYPE.IsMajorComponent)
 * - Legal trace documentation reference
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {

            // ── Wholesale / trade price ───────────────────────────────────
            // Powerlink stores RetailPrice and WholesalePrice separately.
            // price_local = retail (already exists).
            // price_wholesale = dealer/trade price, typically 15-20% below retail.
            if (!Schema::hasColumn('parts_inventory', 'price_wholesale')) {
                $table->decimal('price_wholesale', 12, 2)->nullable()
                    ->after('price_local')
                    ->comment('Trade/dealer price — lower than price_local (retail)');
            }

            // ── Conditions & options (Powerlink: ConditionsAndOptions) ────
            // Short structured field for condition detail beyond A/B/C/D grade.
            // Examples: "crack on housing", "dent on corner", "missing bracket"
            // Max 36 chars matching Powerlink spec. Separate from description/notes.
            if (!Schema::hasColumn('parts_inventory', 'conditions_and_options')) {
                $table->string('conditions_and_options', 36)->nullable()
                    ->after('condition_grade')
                    ->comment('Short condition detail e.g. crack on housing (max 36 chars)');
            }

            // ── Legal trace required (Powerlink: PART_TYPE.LegalTraceFlag) ─
            // 1 = part requires documentation at harvest AND at point of sale.
            // Applies to: catalytic converters, airbags, engines in some jurisdictions.
            if (!Schema::hasColumn('parts_inventory', 'legal_trace_required')) {
                $table->tinyInteger('legal_trace_required')->default(0)
                    ->after('conditions_and_options')
                    ->comment('1 = requires legal trace documentation at harvest and sale');
            }

            // ── Legal trace documentation reference ───────────────────────
            // Stores the document reference (ID number, title number, receipt ref)
            // entered at harvest or point of sale for traceable parts.
            if (!Schema::hasColumn('parts_inventory', 'legal_trace_doc')) {
                $table->string('legal_trace_doc', 191)->nullable()
                    ->after('legal_trace_required')
                    ->comment('Document ref for legal trace — e.g. govt ID, title, receipt#');
            }

            // ── Major component flag (Powerlink: PART_TYPE.IsMajorComponent) ─
            // 1 = supervisor PIN required regardless of price.
            // Applies to: engines, transmissions, HV battery, inverter.
            if (!Schema::hasColumn('parts_inventory', 'is_major_component')) {
                $table->tinyInteger('is_major_component')->default(0)
                    ->after('legal_trace_doc')
                    ->comment('1 = major component — supervisor PIN required at harvest and sale');
            }
        });
    }

    public function down(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            $cols = [
                'price_wholesale', 'conditions_and_options',
                'legal_trace_required', 'legal_trace_doc', 'is_major_component',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('parts_inventory', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
