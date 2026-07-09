<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 4 of 6 — Powerlink Adoption Phase 1
 * Create part_type_rules (Powerlink: PART_TYPE table equivalent)
 *
 * This is the database-backed version of PartNames.php — it adds
 * rules on top of the names:
 * - expected_qty: how many of this part per vehicle
 * - legal_trace_required: needs documentation
 * - is_major_component: needs supervisor PIN
 * - wholesale_margin_pct: default trade discount from retail
 *
 * Seeded by PartTypeRulesSeeder (Phase 2).
 * Used by HarvestController to auto-set flags on parts_inventory rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('part_type_rules')) {
            Schema::create('part_type_rules', function (Blueprint $table) {
                $table->id();

                // Matches part_name values in parts_inventory and PartNames.php
                $table->string('part_name', 191)->unique()
                    ->comment('Must match exactly the label used in HarvestController getPartsList()');

                $table->string('part_category', 60)->nullable()
                    ->comment('Category this part belongs to — for grouping in reports');

                // ── Expected quantity per vehicle ─────────────────────────
                // NULL = user must enter manually (e.g. coils/plugs vary by cylinders)
                // 1 = default (most parts)
                // 4 = e.g. ABS sensors, brake calipers, brake rotors
                $table->unsignedTinyInteger('expected_qty')->nullable()
                    ->comment('NULL = staff enters qty; 1 = default; 4+ = multi-per-vehicle');

                // ── Legal trace (Powerlink: LegalTraceFlag) ───────────────
                $table->tinyInteger('legal_trace_required')->default(0)
                    ->comment('1 = documentation required at harvest and point of sale');

                // ── Major component (Powerlink: IsMajorComponent) ─────────
                $table->tinyInteger('is_major_component')->default(0)
                    ->comment('1 = supervisor PIN required regardless of price');

                // ── Wholesale margin ──────────────────────────────────────
                // Default discount percentage from retail when selling to trade/dealer.
                // NULL = no default (use retail price only).
                // e.g. 20.00 = 20% below retail.
                $table->decimal('wholesale_margin_pct', 5, 2)->nullable()
                    ->comment('Default trade discount % from retail e.g. 20.00 = 20% off');

                // ── Notes for staff ───────────────────────────────────────
                $table->string('notes', 255)->nullable()
                    ->comment('Internal notes e.g. which jurisdictions require legal trace');

                $table->timestamps();

                $table->index('part_category');
                $table->index('is_major_component');
                $table->index('legal_trace_required');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('part_type_rules');
    }
};
