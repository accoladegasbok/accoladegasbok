<?php
// FILE: database/migrations/2026_07_19_000002_create_oem_numbers_table.php
//
// OEM and alternate part numbers, with supersession tracking (a
// number that was replaced by a newer one). Mirrors
// AutoZenith_Basic_Interchange_Model.xlsx "OEM Numbers" sheet.
// Attached at the interchange-group level (shared across every
// vehicle/part in that group) since that's how the workbook models
// it — a single OEM number can apply to many physical parts.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('oem_numbers')) {
            Schema::create('oem_numbers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('group_id');
                $table->string('oem_number', 60);
                $table->enum('number_type', ['Original', 'Superseded', 'Aftermarket'])->default('Original');
                $table->unsignedBigInteger('superseded_by_id')->nullable(); // self-reference: which oem_numbers.id replaced this one
                $table->string('notes', 500)->nullable();
                $table->timestamps();

                $table->index('group_id');
                $table->index('oem_number'); // fast lookup when staff type a part number to identify a vehicle
                $table->foreign('group_id')->references('id')->on('part_interchange_groups')->onDelete('cascade');
                $table->foreign('superseded_by_id')->references('id')->on('oem_numbers')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('oem_numbers');
    }
};
