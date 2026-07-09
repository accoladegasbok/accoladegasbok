<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 2 of 6 — Powerlink Adoption Phase 1
 * Create vehicle_revenue_projections (Powerlink: PROJECTED_PART_GROUP_REVENUE)
 * and part_group_revenue (Powerlink: PART_GROUP_REVENUE)
 *
 * Together these power the ROI engine:
 * - At harvest: staff enters projected revenue per category
 * - As parts sell: actual revenue accumulates automatically
 * - Dashboard shows recovery % per vehicle
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Projected revenue per vehicle (set at harvest time) ───────────
        if (!Schema::hasTable('vehicle_revenue_projections')) {
            Schema::create('vehicle_revenue_projections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('donor_vehicle_id');
                $table->foreign('donor_vehicle_id')
                    ->references('id')->on('donor_vehicles')
                    ->onDelete('cascade');

                $table->string('location', 60)->nullable();
                $table->string('currency_code', 3)->default('NGN');

                // ── Projected revenue by part category ──
                // Staff fills these in at harvest time to set ROI targets.
                // Powerlink uses group 0-9; we use named categories instead
                // to match AutoZenith's existing part_category system.
                $table->decimal('proj_engine', 12, 2)->default(0);
                $table->decimal('proj_transmission', 12, 2)->default(0);
                $table->decimal('proj_body', 12, 2)->default(0);
                $table->decimal('proj_electrical', 12, 2)->default(0);
                $table->decimal('proj_suspension', 12, 2)->default(0);
                $table->decimal('proj_brakes', 12, 2)->default(0);
                $table->decimal('proj_interior', 12, 2)->default(0);
                $table->decimal('proj_cooling', 12, 2)->default(0);
                $table->decimal('proj_fuel_exhaust', 12, 2)->default(0);
                $table->decimal('proj_wheels', 12, 2)->default(0);
                $table->decimal('proj_other', 12, 2)->default(0);
                $table->decimal('proj_scrap', 12, 2)->default(0);
                $table->decimal('proj_total', 12, 2)->default(0)
                    ->comment('Sum of all proj_ columns — updated by app');

                // ── Actual revenue as parts sell ──
                $table->decimal('actual_total', 12, 2)->default(0)
                    ->comment('Running total updated each time a part from this vehicle sells');

                // ── Break-even tracking ──
                $table->timestamp('break_even_reached_at')->nullable()
                    ->comment('Set when actual_total >= donor_vehicles.total_cost');

                $table->timestamps();
            });
        }

        // ── Actual part-level revenue log (Powerlink: PART_GROUP_REVENUE) ─
        if (!Schema::hasTable('part_group_revenue')) {
            Schema::create('part_group_revenue', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('donor_vehicle_id');
                $table->foreign('donor_vehicle_id')
                    ->references('id')->on('donor_vehicles')
                    ->onDelete('cascade');

                $table->unsignedBigInteger('parts_inventory_id')->nullable();
                $table->foreign('parts_inventory_id')
                    ->references('id')->on('parts_inventory')
                    ->onDelete('set null');

                // Invoice line item that generated this revenue
                $table->unsignedBigInteger('invoice_id')->nullable();

                $table->string('part_category', 60)->nullable();
                $table->string('part_name', 191)->nullable();
                $table->decimal('revenue_amount', 12, 2)->default(0);
                $table->string('currency_code', 3)->default('NGN');
                $table->date('sale_date')->nullable();

                $table->timestamps();

                $table->index(['donor_vehicle_id', 'part_category']);
                $table->index('sale_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('part_group_revenue');
        Schema::dropIfExists('vehicle_revenue_projections');
    }
};
