<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Interchange groups ─────────────────────────────────────────
        // One row per "this part also fits these other vehicles" cluster.
        // For Engine/Transmission, group_code is usually the OEM code
        // (e.g. "2AR-FE", "U341E"). For other categories, admin assigns
        // a human-readable code (e.g. "COROLLA-E170-HEADLIGHT-L").
        Schema::create('part_interchange_groups', function (Blueprint $table) {
            $table->id();
            $table->string('part_category', 40);      // Engine, Transmission, Body, etc.
            $table->string('part_name', 150);          // canonical name, from PartNames
            $table->string('group_code', 80)->unique();
            $table->string('source', 20)->default('manual'); // 'manual' | 'auto_heuristic'
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by_staff_id')->nullable();
            $table->timestamps();
        });

        // ── Vehicles accepted by each group ────────────────────────────
        Schema::create('part_interchange_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')
                ->constrained('part_interchange_groups')
                ->cascadeOnDelete();
            $table->string('make', 60);
            $table->string('model', 80);
            $table->unsignedSmallInteger('year_from');
            $table->unsignedSmallInteger('year_to');
            $table->string('trim', 60)->nullable(); // null = all trims
            $table->string('body_style', 60)->nullable();
            $table->string('added_via', 20)->default('manual'); // 'manual' | 'auto_heuristic'
            $table->timestamps();

            $table->index(['make', 'model', 'year_from', 'year_to']);
        });

        // ── Link parts_inventory to a group ─────────────────────────────
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->foreignId('interchange_group_id')
                ->nullable()
                ->after('not_compatible_note')
                ->constrained('part_interchange_groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->dropConstrainedForeignId('interchange_group_id');
        });
        Schema::dropIfExists('part_interchange_vehicles');
        Schema::dropIfExists('part_interchange_groups');
    }
};
