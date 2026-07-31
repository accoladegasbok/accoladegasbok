<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NEW: engine displacement (e.g. "2.5L", "3.5L V6") was only ever used
 * transiently during harvest-entry lookups (InventoryController's OEM
 * disambiguation logic) to pick the right engine_code_oem — it was
 * never actually saved. This adds a real, persisted, searchable column
 * so displacement shows on inventory rows, prints on tags, and is
 * searchable when staff or customers look up an engine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->string('engine_displacement', 20)->nullable()->after('engine_code_oem');
        });
    }

    public function down(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->dropColumn('engine_displacement');
        });
    }
};
