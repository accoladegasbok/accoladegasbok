<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NEW: AI Knowledge Layer — persists every AI-generated interchange
 * suggestion, not just the ones staff act on. Previously,
 * InterchangeAiController generated live suggestions with real
 * reasoning/confidence, but nothing was ever saved — once a
 * suggestion was confirmed into a real interchange group, the AI's
 * original reasoning was gone, and rejected/ignored suggestions left
 * no trace at all. This gives a real audit trail: "why did AutoMatch
 * AI recommend this," queryable after the fact, for both confirmed
 * AND pending/rejected suggestions.
 *
 * Covers BOTH existing AI suggestion pathways:
 *   - Per-part (InterchangeAiController::suggest() — part edit page)
 *   - Per-vehicle (InterchangeAiController::suggestForVehicle() —
 *     Compatibility Checker page)
 * `part_id` is nullable specifically because the per-vehicle pathway
 * has no single originating part.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_suggestions', function (Blueprint $table) {
            $table->id();
            // Nullable — only set for the per-part suggestion pathway.
            $table->unsignedBigInteger('part_id')->nullable();
            // Set once a staff action turns this into a real group —
            // null until then, and stays null forever if never acted on.
            $table->unsignedBigInteger('group_id')->nullable();

            $table->string('suggested_make', 60);
            $table->string('suggested_model', 80);
            $table->unsignedSmallInteger('suggested_year_from');
            $table->unsignedSmallInteger('suggested_year_to');
            $table->string('engine_code', 30)->nullable();
            $table->string('transmission_code', 30)->nullable();

            $table->enum('confidence', ['high', 'medium', 'low']);
            $table->text('reason');
            // Fixed value today ('openai_gpt4o') but kept as a real
            // column rather than assumed, so a future different model/
            // source doesn't require a schema change.
            $table->string('evidence_source', 60)->default('openai_gpt4o');

            $table->enum('review_status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->unsignedBigInteger('reviewed_by_staff_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index('part_id');
            $table->index('group_id');
            $table->index('review_status');

            $table->foreign('part_id')->references('id')->on('parts_inventory')->nullOnDelete();
            $table->foreign('group_id')->references('id')->on('part_interchange_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_suggestions');
    }
};
