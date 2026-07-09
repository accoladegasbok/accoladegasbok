<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * PartTypeRulesSeeder — Phase 2 of Powerlink Adoption
 *
 * Seeds part_type_rules with:
 * - expected_qty per vehicle (NULL = staff enters, 1 = default, 4+ = multi)
 * - legal_trace_required (catalytic converters, airbags, engines)
 * - is_major_component (engines, gearboxes, HV battery — triggers supervisor PIN)
 * - wholesale_margin_pct (default trade discount from retail)
 *
 * Run with: php artisan db:seed --class=PartTypeRulesSeeder
 * Safe to re-run — uses upsert so existing records are updated, not duplicated.
 *
 * ARA Damage Codes reference used in Powerlink (for your reference):
 * FE=Front End, RE=Rear, TP=Top, RS=Rollover, WS=Water/Storm,
 * VD=Vandalism, MH=Mechanical/Hail, BP=Burn/Passngr, BD=Burn/Driver
 */
class PartTypeRulesSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [

            // ── MAJOR COMPONENTS — supervisor PIN always required ──────────
            // is_major_component = 1, wholesale_margin_pct = 20%

            ['part_name' => 'Complete Engine Assembly',
             'part_category' => 'Engine', 'expected_qty' => 1,
             'is_major_component' => 1, 'legal_trace_required' => 1,
             'wholesale_margin_pct' => 20.00,
             'notes' => 'Engine serial number must be recorded. Legal trace in most jurisdictions.'],

            ['part_name' => 'Complete Engine And Gear With Accessories',
             'part_category' => 'Engine', 'expected_qty' => 1,
             'is_major_component' => 1, 'legal_trace_required' => 1,
             'wholesale_margin_pct' => 20.00,
             'notes' => 'Record both engine and gearbox serial numbers.'],

            ['part_name' => 'Engine Long Block',
             'part_category' => 'Engine', 'expected_qty' => 1,
             'is_major_component' => 1, 'legal_trace_required' => 1,
             'wholesale_margin_pct' => 20.00, 'notes' => null],

            ['part_name' => 'Engine Short Block',
             'part_category' => 'Engine', 'expected_qty' => 1,
             'is_major_component' => 1, 'legal_trace_required' => 0,
             'wholesale_margin_pct' => 20.00, 'notes' => null],

            ['part_name' => 'Transmission / Gearbox (Auto)',
             'part_category' => 'Transmission', 'expected_qty' => 1,
             'is_major_component' => 1, 'legal_trace_required' => 0,
             'wholesale_margin_pct' => 20.00,
             'notes' => 'Record pin count and gearbox code.'],

            ['part_name' => 'Transmission / Gearbox (Manual)',
             'part_category' => 'Transmission', 'expected_qty' => 1,
             'is_major_component' => 1, 'legal_trace_required' => 0,
             'wholesale_margin_pct' => 20.00, 'notes' => null],

            ['part_name' => 'CVT Transmission (Complete)',
             'part_category' => 'Transmission', 'expected_qty' => 1,
             'is_major_component' => 1, 'legal_trace_required' => 0,
             'wholesale_margin_pct' => 20.00, 'notes' => null],

            ['part_name' => 'High-Voltage Battery Pack',
             'part_category' => 'Electrical', 'expected_qty' => 1,
             'is_major_component' => 1, 'legal_trace_required' => 0,
             'wholesale_margin_pct' => 15.00,
             'notes' => 'Hybrid/EV only. Requires safe storage and handling protocols.'],

            ['part_name' => 'Inverter / Power Control Unit',
             'part_category' => 'Electrical', 'expected_qty' => 1,
             'is_major_component' => 1, 'legal_trace_required' => 0,
             'wholesale_margin_pct' => 15.00, 'notes' => 'Hybrid/EV only.'],

            // ── LEGAL TRACE REQUIRED — documentation at harvest + sale ────

            ['part_name' => 'Catalytic Converter',
             'part_category' => 'Exhaust', 'expected_qty' => 1,
             'is_major_component' => 0, 'legal_trace_required' => 1,
             'wholesale_margin_pct' => 15.00,
             'notes' => 'High theft risk. Record buyer ID at point of sale. Required in NG and US.'],

            ['part_name' => 'Airbag — Driver (Steering Wheel)',
             'part_category' => 'Airbag', 'expected_qty' => 1,
             'is_major_component' => 0, 'legal_trace_required' => 1,
             'wholesale_margin_pct' => 10.00,
             'notes' => 'Safety-critical. Document condition and deployment status.'],

            ['part_name' => 'Airbag — Passenger (Dashboard)',
             'part_category' => 'Airbag', 'expected_qty' => 1,
             'is_major_component' => 0, 'legal_trace_required' => 1,
             'wholesale_margin_pct' => 10.00, 'notes' => 'Safety-critical.'],

            ['part_name' => 'Airbag — Side Curtain Left',
             'part_category' => 'Airbag', 'expected_qty' => 1,
             'is_major_component' => 0, 'legal_trace_required' => 1,
             'wholesale_margin_pct' => 10.00, 'notes' => null],

            ['part_name' => 'Airbag — Side Curtain Right',
             'part_category' => 'Airbag', 'expected_qty' => 1,
             'is_major_component' => 0, 'legal_trace_required' => 1,
             'wholesale_margin_pct' => 10.00, 'notes' => null],

            ['part_name' => 'Airbag — Knee',
             'part_category' => 'Airbag', 'expected_qty' => 1,
             'is_major_component' => 0, 'legal_trace_required' => 1,
             'wholesale_margin_pct' => 10.00, 'notes' => null],

            ['part_name' => 'Airbag Control Module (ACM)',
             'part_category' => 'Airbag', 'expected_qty' => 1,
             'is_major_component' => 0, 'legal_trace_required' => 1,
             'wholesale_margin_pct' => 10.00, 'notes' => null],

            // ── MULTI-QTY PARTS — staff enters qty based on cylinder/axle count ─

            ['part_name' => 'Ignition Coil',
             'part_category' => 'Engine', 'expected_qty' => null,
             'is_major_component' => 0, 'legal_trace_required' => 0,
             'wholesale_margin_pct' => 15.00,
             'notes' => 'NULL qty = staff enters. Equals cylinder count (4/6/8).'],

            ['part_name' => 'Spark Plug',
             'part_category' => 'Engine', 'expected_qty' => null,
             'is_major_component' => 0, 'legal_trace_required' => 0,
             'wholesale_margin_pct' => 10.00,
             'notes' => 'NULL qty = staff enters. Equals cylinder count (4/6/8).'],

            ['part_name' => 'Fuel Injectors (Set)',
             'part_category' => 'Engine', 'expected_qty' => null,
             'is_major_component' => 0, 'legal_trace_required' => 0,
             'wholesale_margin_pct' => 15.00,
             'notes' => 'NULL qty = staff enters. Equals cylinder count.'],

            // ABS sensors — 4 per vehicle (one per wheel)
            ['part_name' => 'ABS Sensor (Front Left)',
             'part_category' => 'Brakes', 'expected_qty' => 1,
             'is_major_component' => 0, 'legal_trace_required' => 0,
             'wholesale_margin_pct' => 10.00, 'notes' => null],

            // Brake calipers — harvested individually, each is qty 1 per harvest row
            ['part_name' => 'Brake Caliper — Front Left',
             'part_category' => 'Brakes', 'expected_qty' => 1,
             'is_major_component' => 0, 'legal_trace_required' => 0,
             'wholesale_margin_pct' => 10.00, 'notes' => null],

            ['part_name' => 'Brake Caliper — Front Right',
             'part_category' => 'Brakes', 'expected_qty' => 1,
             'is_major_component' => 0, 'legal_trace_required' => 0,
             'wholesale_margin_pct' => 10.00, 'notes' => null],

            // ── STANDARD PARTS — default rules, wholesale margin by category ─

            // Engine parts — 20% wholesale margin
            ['part_name' => 'Alternator', 'part_category' => 'Engine',
             'expected_qty' => 1, 'is_major_component' => 0,
             'legal_trace_required' => 0, 'wholesale_margin_pct' => 20.00, 'notes' => null],

            ['part_name' => 'Starter Motor', 'part_category' => 'Engine',
             'expected_qty' => 1, 'is_major_component' => 0,
             'legal_trace_required' => 0, 'wholesale_margin_pct' => 20.00, 'notes' => null],

            ['part_name' => 'Power Steering Pump', 'part_category' => 'Engine',
             'expected_qty' => 1, 'is_major_component' => 0,
             'legal_trace_required' => 0, 'wholesale_margin_pct' => 20.00, 'notes' => null],

            ['part_name' => 'A/C Compressor', 'part_category' => 'Engine',
             'expected_qty' => 1, 'is_major_component' => 0,
             'legal_trace_required' => 0, 'wholesale_margin_pct' => 15.00, 'notes' => null],

            ['part_name' => 'Turbocharger / Supercharger', 'part_category' => 'Engine',
             'expected_qty' => 1, 'is_major_component' => 1,
             'legal_trace_required' => 0, 'wholesale_margin_pct' => 20.00, 'notes' => null],

            // Body parts — 15% wholesale margin
            ['part_name' => 'Hood / Bonnet', 'part_category' => 'Body',
             'expected_qty' => 1, 'is_major_component' => 0,
             'legal_trace_required' => 0, 'wholesale_margin_pct' => 15.00, 'notes' => null],

            ['part_name' => 'Front Bumper Cover', 'part_category' => 'Body',
             'expected_qty' => 1, 'is_major_component' => 0,
             'legal_trace_required' => 0, 'wholesale_margin_pct' => 15.00, 'notes' => null],

            ['part_name' => 'Rear Bumper Cover', 'part_category' => 'Body',
             'expected_qty' => 1, 'is_major_component' => 0,
             'legal_trace_required' => 0, 'wholesale_margin_pct' => 15.00, 'notes' => null],

            // Electrical — 15% wholesale margin
            ['part_name' => 'Engine Control Module (ECM/PCM)', 'part_category' => 'Electrical',
             'expected_qty' => 1, 'is_major_component' => 0,
             'legal_trace_required' => 0, 'wholesale_margin_pct' => 15.00, 'notes' => null],

            ['part_name' => 'Body Control Module (BCM)', 'part_category' => 'Electrical',
             'expected_qty' => 1, 'is_major_component' => 0,
             'legal_trace_required' => 0, 'wholesale_margin_pct' => 15.00, 'notes' => null],

            ['part_name' => 'Instrument Cluster / Speedometer', 'part_category' => 'Electrical',
             'expected_qty' => 1, 'is_major_component' => 0,
             'legal_trace_required' => 0, 'wholesale_margin_pct' => 15.00, 'notes' => null],

            // Wheels — 15% wholesale margin
            ['part_name' => 'Alloy Wheel Rims (Set of 4)', 'part_category' => 'Wheels',
             'expected_qty' => 1, 'is_major_component' => 0,
             'legal_trace_required' => 0, 'wholesale_margin_pct' => 15.00, 'notes' => null],

            // Transfer case / differentials — major component
            ['part_name' => 'Transfer Case', 'part_category' => 'Transmission',
             'expected_qty' => 1, 'is_major_component' => 1,
             'legal_trace_required' => 0, 'wholesale_margin_pct' => 20.00, 'notes' => null],

            ['part_name' => 'Front Differential', 'part_category' => 'Transmission',
             'expected_qty' => 1, 'is_major_component' => 1,
             'legal_trace_required' => 0, 'wholesale_margin_pct' => 20.00, 'notes' => null],

            ['part_name' => 'Rear Differential', 'part_category' => 'Transmission',
             'expected_qty' => 1, 'is_major_component' => 1,
             'legal_trace_required' => 0, 'wholesale_margin_pct' => 20.00, 'notes' => null],
        ];

        foreach ($rules as $rule) {
            DB::table('part_type_rules')->upsert(
                array_merge($rule, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
                ['part_name'],  // unique key to match on
                [               // columns to update if record exists
                    'part_category', 'expected_qty', 'is_major_component',
                    'legal_trace_required', 'wholesale_margin_pct', 'notes',
                    'updated_at',
                ]
            );
        }

        $count = count($rules);
        $this->command->info("PartTypeRulesSeeder: {$count} part type rules seeded/updated.");
    }
}
