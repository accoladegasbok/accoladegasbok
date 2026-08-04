<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * NEW: standardized part taxonomy, modeled on the industry's ACES/PIES
 * PartTerminologyID concept (Auto Care Association's public standard —
 * not competitor-specific data). Replaces free-typed part_name with a
 * fixed, curated list so "Alternator", "Alternator Assembly", and "ALT"
 * can never coexist as three different things in search/matching.
 *
 * Scoped to ~150 terms across the categories AutoZenith actually
 * stocks (matches the existing part_category list used in
 * AuditController/InventoryController) — not the full ~13,000-entry
 * industry list, which would be far more than this business needs.
 *
 * `pin_count` and other Nigerian/Ladipo-market gearbox attributes are
 * DELIBERATELY NOT part of this taxonomy — those stay as proprietary
 * fields on parts_inventory itself (transmission/gear category only),
 * per explicit decision to keep that data as AutoZenith's own
 * differentiator rather than folding it into the standardized layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_terminology', function (Blueprint $table) {
            $table->id();
            $table->string('category', 60); // matches existing part_category values
            $table->string('standard_name', 150);
            // Rough internal note on the ACES/PIES concept this maps
            // to — NOT a real external PartTerminologyID (Auto Care
            // Association's actual ID list is licensed); this is our
            // own curated equivalent, informed by the public standard's
            // structure.
            $table->string('aces_pies_note', 100)->nullable();
            $table->timestamps();

            $table->unique(['category', 'standard_name']);
        });

        // ── parts_inventory: link to standardized terminology ──────────
        Schema::table('parts_inventory', function (Blueprint $table) {
            // Nullable — existing rows keep their free-typed part_name
            // untouched. New entries going forward should set this;
            // enforced at the application layer, not a hard DB
            // constraint, so historical data is never broken.
            $table->unsignedBigInteger('part_terminology_id')->nullable()->after('part_name');
        });

        $this->seedTerminology();
    }

    public function down(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->dropColumn('part_terminology_id');
        });
        Schema::dropIfExists('part_terminology');
    }

    private function seedTerminology(): void
    {
        $now = now();
        $terms = [];

        $add = function (string $category, array $names) use (&$terms) {
            foreach ($names as $name) {
                $terms[] = ['category' => $category, 'standard_name' => $name];
            }
        };

        $add('Engine', [
            'Complete Engine Assembly', 'Engine Long Block', 'Engine Short Block',
            'Cylinder Head', 'Engine Block (Bare)', 'Intake Manifold', 'Exhaust Manifold',
            'Oil Pan', 'Valve Cover', 'Timing Cover', 'Timing Chain/Belt Kit',
            'Turbocharger', 'Supercharger', 'Engine Wiring Harness', 'Engine Mount',
            'Oil Pump', 'Crankshaft', 'Camshaft', 'Piston & Rod Assembly',
        ]);

        $add('Transmission', [
            'Automatic Transmission Assembly', 'Manual Transmission Assembly',
            'CVT Transmission Assembly', 'Transfer Case', 'Torque Converter',
            'Transmission Control Module (TCM)', 'Clutch Assembly', 'Flywheel',
            'Transmission Mount', 'Transmission Wiring Harness',
        ]);

        $add('Electrical', [
            'Alternator', 'Starter Motor', 'Engine Control Module (ECU/PCM)',
            'Body Control Module (BCM)', 'Battery Tray', 'Ignition Coil',
            'Main Wiring Harness', 'Fuse Box / Junction Block',
            'Oxygen (O2) Sensor', 'Mass Air Flow (MAF) Sensor',
            'Crankshaft Position Sensor', 'Camshaft Position Sensor',
            'ABS Wheel Speed Sensor', 'Headlight Assembly', 'Taillight Assembly',
            'Fog Light Assembly', 'Power Window Motor', 'Blower Motor',
            'Instrument Cluster', 'Radio / Infotainment Unit', 'Backup Camera',
        ]);

        $add('Body', [
            'Front Bumper Cover', 'Rear Bumper Cover', 'Hood', 'Front Fender',
            'Rear Quarter Panel', 'Front Door', 'Rear Door', 'Trunk Lid',
            'Tailgate', 'Side Mirror Assembly', 'Grille Assembly', 'Windshield',
            'Rear Windshield', 'Door Glass', 'Roof Panel', 'Rocker Panel',
            'Door Handle', 'Fender Liner',
        ]);

        $add('Suspension', [
            'Strut Assembly - Front', 'Strut Assembly - Rear', 'Shock Absorber',
            'Control Arm - Upper', 'Control Arm - Lower', 'Sway Bar',
            'Sway Bar Link', 'Coil Spring', 'Wheel Hub Assembly', 'CV Axle Shaft',
            'Steering Rack', 'Steering Knuckle', 'Ball Joint', 'Tie Rod End',
        ]);

        $add('Brakes', [
            'Brake Caliper', 'Brake Rotor', 'Master Cylinder', 'ABS Module',
            'Brake Booster', 'Parking Brake Assembly', 'Brake Line',
        ]);

        $add('Cooling', [
            'Radiator', 'A/C Condenser', 'Cooling Fan Assembly', 'Water Pump',
            'Radiator Hose', 'Thermostat Housing', 'A/C Compressor',
        ]);

        $add('Fuel', [
            'Fuel Pump Assembly', 'Fuel Tank', 'Fuel Injector', 'Fuel Rail',
            'Fuel Filler Neck',
        ]);

        $add('Exhaust', [
            'Catalytic Converter', 'Muffler', 'Exhaust Pipe', 'Exhaust Manifold Gasket',
        ]);

        $add('Airbag', [
            'Airbag Module - Driver', 'Airbag Module - Passenger',
            'Airbag Module - Side/Curtain', 'Airbag Control Module',
            'Seat Belt Assembly (Pretensioner)',
        ]);

        $add('Interior', [
            'Dashboard / Instrument Panel', 'Center Console', 'Door Panel',
            'Headliner', 'Steering Wheel', 'Glove Box',
        ]);

        $add('Seat', [
            'Front Seat Assembly', 'Rear Seat Assembly', 'Seat Track/Motor',
        ]);

        $add('Wheels', [
            'Alloy Wheel / Rim', 'Wheel Center Cap', 'Spare Wheel',
        ]);

        // Consumable is DELIBERATELY excluded — those are branded
        // retail products (oils, filters, fluids) named by their real
        // product name, not standardized part terminology. Forcing a
        // taxonomy match there would fight against how that category
        // already works (see InventoryController's Generic-brand /
        // custom-name handling for consumables).

        foreach ($terms as &$t) {
            $t['aces_pies_note'] = null;
            $t['created_at'] = $now;
            $t['updated_at'] = $now;
        }
        unset($t);

        DB::table('part_terminology')->insert($terms);
    }
};
