<?php
// FILE: database/migrations/2026_07_19_000001_create_interchange_evidence_table.php
//
// Weighted, typed evidence supporting a specific interchange group.
// Mirrors AutoZenith_Basic_Interchange_Model.xlsx "Evidence" sheet.
// Each row is one piece of proof (an OEM number match, a physical
// connector match, a VIN-confirmed installation, etc.) with a point
// weight. Summed weights feed ConfidenceScorer to produce a
// suggested verification status — see app/Services/ConfidenceScorer.php.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('interchange_evidence')) {
            Schema::create('interchange_evidence', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('group_id');
                $table->string('evidence_type', 60); // e.g. 'OEM Number Match', 'Transmission Code Match', 'Physical Install Confirmed'
                $table->string('description', 500)->nullable();
                $table->unsignedTinyInteger('weight')->default(0); // 0-100 points toward confidence score
                $table->string('source', 100)->nullable(); // 'Internal reference', 'Physical inspection', 'Customer confirmation', 'ALLDATA/Mitchell lookup', etc.
                $table->enum('status', ['Accepted', 'Pending', 'Rejected'])->default('Pending');
                $table->unsignedBigInteger('recorded_by')->nullable(); // staff id
                $table->timestamps();

                $table->index('group_id');
                $table->foreign('group_id')->references('id')->on('part_interchange_groups')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('interchange_evidence');
    }
};
