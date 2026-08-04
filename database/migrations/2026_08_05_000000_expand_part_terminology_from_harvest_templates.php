<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FIXED: the initial part_terminology seed (previous migration) was a
 * reasonable ~140-term list, but HarvestController::getPartsList()
 * already had a MUCH more complete, position-aware, already-in-
 * production template list (~150+ terms including Left/Right/Front/
 * Rear variants — e.g. "Brake Caliper — Front Left" as its own
 * distinct term, not just "Brake Caliper" + a separate Side field).
 *
 * That harvest template list is the more authoritative source — it's
 * what staff have actually been selecting from this whole time — so
 * this migration folds it into part_terminology as the canonical
 * taxonomy, rather than maintaining two divergent standardized lists.
 *
 * Uses insertOrIgnore() against the existing unique(['category',
 * 'standard_name']) constraint, so any term already present from the
 * first seed is silently skipped — safe to run regardless of whether
 * that migration has been deployed yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [];

        // Exact mirror of HarvestController::getPartsList() — every
        // 'label' + 'category' pair, flattened. Kept in sync manually;
        // if the harvest template list changes, this list should too.
        $templates = [
            ['Complete Engine Assembly', 'Engine'],
            ['Complete Engine And Gear With Accessories', 'Engine'],
            ['Engine Block', 'Engine'],
            ['Cylinder Head', 'Engine'],
            ['Intake Manifold', 'Engine'],
            ['Throttle Body', 'Engine'],
            ['Fuel Injectors (Set)', 'Engine'],
            ['Alternator', 'Engine'],
            ['Starter Motor', 'Engine'],
            ['Power Steering Pump', 'Engine'],
            ['A/C Compressor', 'Engine'],
            ['Turbocharger / Supercharger', 'Engine'],
            ['Engine Wiring Harness', 'Electrical'],
            ['Engine Control Module (ECM/PCM)', 'Electrical'],
            ['Transmission / Gearbox (Auto)', 'Transmission'],
            ['Transmission / Gearbox (Manual)', 'Transmission'],
            ['Transfer Case', 'Transmission'],
            ['Front Differential', 'Transmission'],
            ['Rear Differential', 'Transmission'],
            ['Driveshaft — Front', 'Transmission'],
            ['Driveshaft — Rear', 'Transmission'],
            ['Axle / CV Shaft — Front Left', 'Transmission'],
            ['Axle / CV Shaft — Front Right', 'Transmission'],
            ['Axle / CV Shaft — Rear Left', 'Transmission'],
            ['Axle / CV Shaft — Rear Right', 'Transmission'],
            ['Oil Pan', 'Engine'],
            ['Valve Cover / Cam Cover', 'Engine'],
            ['Timing Chain / Belt Kit', 'Engine'],
            ['Flywheel / Flexplate', 'Engine'],
            ['Ignition Coil', 'Engine'],
            ['Spark Plug', 'Engine'],
            ['Radiator', 'Cooling'],
            ['Cooling Fan Assembly', 'Cooling'],
            ['Fan Clutch', 'Cooling'],
            ['Intercooler', 'Cooling'],
            ['Coolant Reservoir / Overflow Tank', 'Cooling'],
            ['Thermostat Housing', 'Cooling'],
            ['Water Pump', 'Cooling'],
            ['A/C Condenser', 'Cooling'],
            ['A/C Evaporator', 'Cooling'],
            ['Heater Core', 'Cooling'],
            ['Blower Motor', 'Cooling'],
            ['Steering Rack and Pinion', 'Suspension'],
            ['Steering Column', 'Suspension'],
            ['Steering Wheel', 'Interior'],
            ['Control Arm — Front Left', 'Suspension'],
            ['Control Arm — Front Right', 'Suspension'],
            ['Control Arm — Rear Left', 'Suspension'],
            ['Control Arm — Rear Right', 'Suspension'],
            ['Spindle / Knuckle — Front Left', 'Suspension'],
            ['Spindle / Knuckle — Front Right', 'Suspension'],
            ['Strut Assembly — Front Left', 'Suspension'],
            ['Strut Assembly — Front Right', 'Suspension'],
            ['Strut Assembly — Rear Left', 'Suspension'],
            ['Strut Assembly — Rear Right', 'Suspension'],
            ['Shock Absorber — Front Left', 'Suspension'],
            ['Shock Absorber — Front Right', 'Suspension'],
            ['Shock Absorber — Rear Left', 'Suspension'],
            ['Shock Absorber — Rear Right', 'Suspension'],
            ['Coil Spring — Front', 'Suspension'],
            ['Coil Spring — Rear', 'Suspension'],
            ['Sway Bar — Front', 'Suspension'],
            ['Sway Bar — Rear', 'Suspension'],
            ['Wheel Hub & Bearing — Front Left', 'Suspension'],
            ['Wheel Hub & Bearing — Front Right', 'Suspension'],
            ['Wheel Hub & Bearing — Rear Left', 'Suspension'],
            ['Wheel Hub & Bearing — Rear Right', 'Suspension'],
            ['Subframe — Front', 'Suspension'],
            ['Subframe — Rear', 'Suspension'],
            ['Brake Caliper — Front Left', 'Brakes'],
            ['Brake Caliper — Front Right', 'Brakes'],
            ['Brake Caliper — Rear Left', 'Brakes'],
            ['Brake Caliper — Rear Right', 'Brakes'],
            ['Brake Master Cylinder', 'Brakes'],
            ['ABS Module / Pump', 'Brakes'],
            ['Brake Booster / Servo', 'Brakes'],
            ['Brake Rotor — Front Left', 'Brakes'],
            ['Brake Rotor — Front Right', 'Brakes'],
            ['Brake Rotor — Rear Left', 'Brakes'],
            ['Brake Rotor — Rear Right', 'Brakes'],
            ['Fuse Box — Engine Bay', 'Electrical'],
            ['Fuse Box — Cabin / Interior', 'Electrical'],
            ['Body Control Module (BCM)', 'Electrical'],
            ['Instrument Cluster / Speedometer', 'Electrical'],
            ['Ignition Switch', 'Electrical'],
            ['Window Motor — Front Left', 'Electrical'],
            ['Window Motor — Front Right', 'Electrical'],
            ['Window Motor — Rear Left', 'Electrical'],
            ['Window Motor — Rear Right', 'Electrical'],
            ['Wiper Motor — Front', 'Electrical'],
            ['Mass Air Flow Sensor (MAF)', 'Electrical'],
            ['MAP Sensor', 'Electrical'],
            ['Crankshaft Position Sensor', 'Electrical'],
            ['Camshaft Position Sensor', 'Electrical'],
            ['Oxygen Sensor — Upstream', 'Electrical'],
            ['Oxygen Sensor — Downstream', 'Electrical'],
            ['Radio / Infotainment / Navigation', 'Electrical'],
            ['Climate Control Module', 'Electrical'],
            ['Reverse / Backup Camera', 'Electrical'],
            ['Battery', 'Electrical'],
            ['Hood / Bonnet', 'Body'],
            ['Front Bumper Cover', 'Body'],
            ['Rear Bumper Cover', 'Body'],
            ['Front Fender — Left', 'Body'],
            ['Front Fender — Right', 'Body'],
            ['Door Shell — Front Left', 'Body'],
            ['Door Shell — Front Right', 'Body'],
            ['Door Shell — Rear Left', 'Body'],
            ['Door Shell — Rear Right', 'Body'],
            ['Tailgate', 'Body'],
            ['Trunk Lid / Boot Lid', 'Body'],
            ['Roof Panel', 'Body'],
            ['Quarter Panel — Left', 'Body'],
            ['Quarter Panel — Right', 'Body'],
            ['Grille', 'Body'],
            ['Side Mirror — Left', 'Body'],
            ['Side Mirror — Right', 'Body'],
            ['Headlight Assembly — Left', 'Body'],
            ['Headlight Assembly — Right', 'Body'],
            ['Tail Light Assembly — Left', 'Body'],
            ['Tail Light Assembly — Right', 'Body'],
            ['Fog Light — Left', 'Body'],
            ['Fog Light — Right', 'Body'],
            ['Third Brake Light (CHMSL)', 'Body'],
            ['Windshield / Front Glass', 'Body'],
            ['Door Glass — Front Left', 'Body'],
            ['Door Glass — Front Right', 'Body'],
            ['Door Glass — Rear Left', 'Body'],
            ['Door Glass — Rear Right', 'Body'],
            ['Rear Window Glass', 'Body'],
            ['Sunroof / Moonroof Glass', 'Body'],
            ['Seat — Front Driver', 'Seat'],
            ['Seat — Front Passenger', 'Seat'],
            ['Seat — Rear Left', 'Seat'],
            ['Seat — Rear Right', 'Seat'],
            ['Seat Belt — Front Left', 'Interior'],
            ['Seat Belt — Front Right', 'Interior'],
            ['Seat Belt — Rear', 'Interior'],
            ['Dashboard / Instrument Panel', 'Interior'],
            ['Center Console', 'Interior'],
            ['Door Panel — Front Left', 'Interior'],
            ['Door Panel — Front Right', 'Interior'],
            ['Door Panel — Rear Left', 'Interior'],
            ['Door Panel — Rear Right', 'Interior'],
            ['Carpet / Floor Mat Set', 'Interior'],
            ['Headliner / Roof Lining', 'Interior'],
            ['Glove Box', 'Interior'],
            ['Rearview Mirror (Interior)', 'Interior'],
            ['Gear Shift / Selector Assembly', 'Interior'],
            ['Airbag — Driver (Steering Wheel)', 'Airbag'],
            ['Airbag — Passenger (Dashboard)', 'Airbag'],
            ['Airbag — Side Curtain Left', 'Airbag'],
            ['Airbag — Side Curtain Right', 'Airbag'],
            ['Airbag — Knee', 'Airbag'],
            ['Airbag Control Module (ACM)', 'Airbag'],
            ['Alloy Wheel Rims (Set of 4)', 'Wheels'],
            ['Alloy Wheel Rim (Single)', 'Wheels'],
            ['Spare Wheel / Spare Tyre', 'Wheels'],
            ['Tyres (Set of 4)', 'Wheels'],
            ['Fuel Tank', 'Fuel'],
            ['Fuel Pump', 'Fuel'],
            ['Catalytic Converter', 'Exhaust'],
            ['Exhaust Manifold', 'Exhaust'],
            ['Muffler / Silencer', 'Exhaust'],
            ['High-Voltage Battery Pack', 'Electrical'],
            ['Inverter / Power Control Unit', 'Electrical'],
            ['Electric Motor', 'Engine'],
            ['Charging Port Assembly', 'Electrical'],
        ];

        foreach ($templates as [$label, $category]) {
            $rows[] = [
                'category'       => $category,
                'standard_name'  => $label,
                'aces_pies_note' => null,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        DB::table('part_terminology')->insertOrIgnore($rows);
    }

    public function down(): void
    {
        // Deliberately a no-op — these terms may already be referenced
        // by parts_inventory.part_terminology_id by the time anyone
        // rolls back; safer to leave them than silently break links.
    }
};
