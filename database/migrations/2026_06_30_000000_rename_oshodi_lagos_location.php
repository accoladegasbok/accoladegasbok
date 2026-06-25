<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── location was an ENUM that didn't include the new location
        // names (Lagos Nigeria, Abuja Nigeria, Akure Nigeria). Widen it
        // to a plain VARCHAR so new locations never require a schema
        // change again — same approach used for parts_inventory.status
        // earlier (Phase B2).
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->string('location', 60)->change();
        });

        Schema::table('storage_rooms', function (Blueprint $table) {
            $table->string('location', 60)->change();
        });

        // Now safe to rename the existing data.
        DB::table('parts_inventory')->where('location', 'Oshodi Lagos')->update(['location' => 'Lagos Nigeria']);
        DB::table('storage_rooms')->where('location', 'Oshodi Lagos')->update(['location' => 'Lagos Nigeria']);
    }

    public function down(): void
    {
        DB::table('parts_inventory')->where('location', 'Lagos Nigeria')->update(['location' => 'Oshodi Lagos']);
        DB::table('storage_rooms')->where('location', 'Lagos Nigeria')->update(['location' => 'Oshodi Lagos']);
    }
};
