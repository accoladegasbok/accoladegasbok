<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Store rooms — one per physical room/warehouse at a location.
        // e.g. Ile-Ife Nigeria has 6 of these.
        Schema::create('storage_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('location', 60);       // matches existing location strings, e.g. "Ile-Ife Nigeria"
            $table->string('name', 80);             // e.g. "Store 1", "Main Warehouse"
            $table->string('code', 30)->unique();   // e.g. "ILE-S1"
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('location');
        });

        // ── Shelves / bins within a store room.
        Schema::create('storage_shelves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storage_room_id')->constrained('storage_rooms')->cascadeOnDelete();
            $table->string('shelf_code', 20);              // e.g. "A", "B1"
            $table->unsignedInteger('column_number')->nullable();
            $table->unsignedInteger('space_number')->nullable();
            $table->string('full_bin_code', 60)->unique(); // e.g. "ILE-S1-A-01-02"
            $table->unsignedInteger('capacity')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Link parts_inventory to a structured bin (optional — the old
        // free-text bin_location column stays for backward compatibility
        // and for locations that haven't been mapped into the new system yet).
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->foreignId('storage_shelf_id')
                ->nullable()
                ->after('bin_location')
                ->constrained('storage_shelves')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->dropConstrainedForeignId('storage_shelf_id');
        });
        Schema::dropIfExists('storage_shelves');
        Schema::dropIfExists('storage_rooms');
    }
};
