<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 1 of 6 — Powerlink Adoption Phase 1
 * Enhance donor_vehicles with:
 * - Vehicle acquisition cost breakdown (Powerlink: VEHICLE_COST_PAYMENT)
 * - Break-even days target
 * - Vehicle status (Parts vs Rebuilder)
 * - Primary/Secondary damage codes
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donor_vehicles', function (Blueprint $table) {

            // ── Acquisition cost breakdown (Powerlink VEHICLE_COST_PAYMENT) ──
            if (!Schema::hasColumn('donor_vehicles', 'salvage_cost')) {
                $table->decimal('salvage_cost', 12, 2)->nullable()->default(0)
                    ->after('notes')
                    ->comment('Purchase / salvage price paid for the vehicle');
            }
            if (!Schema::hasColumn('donor_vehicles', 'towing_cost')) {
                $table->decimal('towing_cost', 12, 2)->nullable()->default(0)
                    ->after('salvage_cost')
                    ->comment('Inbound towing / transport cost');
            }
            if (!Schema::hasColumn('donor_vehicles', 'processing_cost')) {
                $table->decimal('processing_cost', 12, 2)->nullable()->default(0)
                    ->after('towing_cost')
                    ->comment('Processing / dismantling labour cost');
            }
            if (!Schema::hasColumn('donor_vehicles', 'other_cost')) {
                $table->decimal('other_cost', 12, 2)->nullable()->default(0)
                    ->after('processing_cost')
                    ->comment('Any other acquisition costs (storage, fees etc.)');
            }
            if (!Schema::hasColumn('donor_vehicles', 'total_cost')) {
                // Stored computed total — updated by app whenever cost fields change.
                // Avoids summing four columns on every query.
                $table->decimal('total_cost', 12, 2)->nullable()->default(0)
                    ->after('other_cost')
                    ->comment('Total cost = salvage + towing + processing + other');
            }
            if (!Schema::hasColumn('donor_vehicles', 'currency_code')) {
                $table->string('currency_code', 3)->default('NGN')
                    ->after('total_cost')
                    ->comment('Currency for all cost figures on this vehicle');
            }

            // ── Break-even target (Powerlink: VEHICLE.BreakEvenDays) ──────
            if (!Schema::hasColumn('donor_vehicles', 'break_even_days')) {
                $table->unsignedSmallInteger('break_even_days')->nullable()
                    ->after('currency_code')
                    ->comment('Target days to recover vehicle cost from parts sales');
            }

            // ── Vehicle status (Powerlink: P=Parts, R=Rebuilder) ──────────
            if (!Schema::hasColumn('donor_vehicles', 'vehicle_status')) {
                $table->enum('vehicle_status', ['Parts', 'Rebuilder'])->default('Parts')
                    ->after('break_even_days')
                    ->comment('Parts = dismantle for parts; Rebuilder = resell as running');
            }

            // ── Damage codes (Powerlink: ARA standard damage codes) ───────
            if (!Schema::hasColumn('donor_vehicles', 'primary_damage_code')) {
                $table->string('primary_damage_code', 3)->nullable()
                    ->after('vehicle_status')
                    ->comment('Primary ARA damage code e.g. FE=Front End, RR=Rear');
            }
            if (!Schema::hasColumn('donor_vehicles', 'secondary_damage_code')) {
                $table->string('secondary_damage_code', 3)->nullable()
                    ->after('primary_damage_code')
                    ->comment('Secondary ARA damage code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('donor_vehicles', function (Blueprint $table) {
            $cols = [
                'salvage_cost', 'towing_cost', 'processing_cost', 'other_cost',
                'total_cost', 'currency_code', 'break_even_days', 'vehicle_status',
                'primary_damage_code', 'secondary_damage_code',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('donor_vehicles', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
