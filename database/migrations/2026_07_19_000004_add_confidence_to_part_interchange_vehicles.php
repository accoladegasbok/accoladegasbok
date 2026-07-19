<?php
// FILE: database/migrations/2026_07_19_000004_add_confidence_to_part_interchange_vehicles.php
//
// Extends the EXISTING part_interchange_vehicles table (group_id +
// make/model/year_from/year_to — the group-to-vehicle pairing already
// used throughout CompatibilityController/InterchangeService) with the
// confidence-scoring fields from AutoZenith_Basic_Interchange_Model.xlsx
// "Part Applications" sheet. No new parallel table — this IS that
// sheet's data, attached to the pairing that already exists.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('part_interchange_vehicles')) {
            Schema::table('part_interchange_vehicles', function (Blueprint $table) {
                if (!Schema::hasColumn('part_interchange_vehicles', 'confidence_score')) {
                    $table->unsignedTinyInteger('confidence_score')->nullable()->after('year_to'); // 0-100, computed from summed accepted evidence weights — see ConfidenceScorer
                }
                if (!Schema::hasColumn('part_interchange_vehicles', 'source_count')) {
                    $table->unsignedTinyInteger('source_count')->default(0)->after('confidence_score'); // count of Accepted evidence rows backing this pairing
                }
                if (!Schema::hasColumn('part_interchange_vehicles', 'verification_status')) {
                    // Auto-suggested by ConfidenceScorer from the score, but staff
                    // can manually override — matches the workbook's example data,
                    // where two pairings at the same score (45) were manually set
                    // to different statuses (Probable vs Under Review).
                    $table->enum('verification_status', ['Verified', 'Probable', 'Under Review', 'Rejected'])
                        ->default('Under Review')->after('source_count');
                }
                if (!Schema::hasColumn('part_interchange_vehicles', 'conditions_note')) {
                    $table->string('conditions_note', 500)->nullable()->after('verification_status'); // e.g. "Transmission code U760E; FWD; connector must match"
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('part_interchange_vehicles')) {
            Schema::table('part_interchange_vehicles', function (Blueprint $table) {
                foreach (['confidence_score', 'source_count', 'verification_status', 'conditions_note'] as $col) {
                    if (Schema::hasColumn('part_interchange_vehicles', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
