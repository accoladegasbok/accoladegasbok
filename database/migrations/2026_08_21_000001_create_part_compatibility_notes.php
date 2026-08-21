<?php
// FILE: database/migrations/2026_08_21_000001_create_part_compatibility_notes.php
//
// Free-text "Extra Compatibility Note" a confirmed staff member can add
// to any part — for cases that don't fit the structured make/model/
// year_from/year_to schema used by part_interchange_vehicles and
// parts_compatibility (e.g. "Also confirmed on a swapped/modified
// trim", "Bracket differs on early-build units, verify before selling
// as direct fit", etc).
//
// Deliberately its own small table, not a single overwritable column
// on parts_inventory — matches the same attributed, append-only
// pattern already used for part_oem_numbers: multiple notes can
// accumulate over time, each one traceable to exactly who added it
// and when, nothing ever silently overwritten.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_compatibility_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parts_inventory_id');
            $table->text('note');
            $table->unsignedBigInteger('added_by_staff_id')->nullable();
            // Denormalized name + role at time of writing — same
            // pattern as invoice_edit_log.edited_by, so the note stays
            // readable/attributable even if the staff record is later
            // deactivated or renamed.
            $table->string('added_by_name', 120)->nullable();
            $table->string('added_by_role', 30)->nullable();
            $table->timestamps();

            $table->index('parts_inventory_id');
            $table->foreign('parts_inventory_id')->references('id')->on('parts_inventory')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_compatibility_notes');
    }
};
