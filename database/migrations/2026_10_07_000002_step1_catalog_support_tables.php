<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Step 1 support tables/columns:
 *  - part_code_history          old stock numbers after a category change (still searchable)
 *  - parts_inventory.inclusions JSON list of add-ons that come with a "Complete Engine (With Add-ons)"
 *  - part_terminology.harvest_checklist
 *                               1 = this name also appears as a row on the harvest checklist
 *                               (built-in checklist rows always show, regardless of this flag)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('part_code_history')) {
            Schema::create('part_code_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('parts_inventory_id')->index();
                $table->string('old_code', 30)->index();
                $table->string('new_code', 30);
                $table->string('old_category', 50)->nullable();
                $table->string('new_category', 50)->nullable();
                $table->unsignedBigInteger('changed_by_staff_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('parts_inventory', 'inclusions')) {
            Schema::table('parts_inventory', function (Blueprint $table) {
                $table->text('inclusions')->nullable();
            });
        }

        if (!Schema::hasColumn('part_terminology', 'harvest_checklist')) {
            Schema::table('part_terminology', function (Blueprint $table) {
                $table->boolean('harvest_checklist')->default(false);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('part_code_history');
        if (Schema::hasColumn('parts_inventory', 'inclusions')) {
            Schema::table('parts_inventory', fn (Blueprint $t) => $t->dropColumn('inclusions'));
        }
        if (Schema::hasColumn('part_terminology', 'harvest_checklist')) {
            Schema::table('part_terminology', fn (Blueprint $t) => $t->dropColumn('harvest_checklist'));
        }
    }
};
