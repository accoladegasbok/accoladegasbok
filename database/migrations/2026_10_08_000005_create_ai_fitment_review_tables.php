<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI fitment review: staff select AI suggestions in a batch, then go through a SECOND review step before
 * anything is added to a part's fitment. Every confirm / reject is logged (who, when, what, why).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_fitment_batches')) {
            Schema::create('ai_fitment_batches', function (Blueprint $t) {
                $t->id();
                $t->string('action', 10);                       // confirm | reject
                $t->unsignedBigInteger('staff_id')->nullable();
                $t->string('staff_name', 80)->nullable();
                $t->unsignedSmallInteger('item_count');
                $t->text('note')->nullable();                   // the reason (required for reject)
                $t->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('ai_fitment_log')) {
            Schema::create('ai_fitment_log', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('batch_id')->index();
                $t->unsignedBigInteger('suggestion_id')->index();
                $t->string('action', 10);                       // confirm | reject
                $t->unsignedBigInteger('part_id')->nullable();
                $t->unsignedBigInteger('group_id')->nullable();
                $t->unsignedBigInteger('vehicle_row_id')->nullable(); // the part_interchange_vehicles row created
                $t->string('make', 60)->nullable();
                $t->string('model', 80)->nullable();
                $t->unsignedSmallInteger('year_from')->nullable();
                $t->unsignedSmallInteger('year_to')->nullable();
                $t->string('confidence', 10)->nullable();       // as it was when reviewed
                $t->text('reason')->nullable();                 // the AI's reasoning, as it was when reviewed
                $t->string('result', 120)->nullable();          // e.g. "Added to new group AI-ENG-15-X4F2"
                $t->unsignedBigInteger('staff_id')->nullable();
                $t->string('staff_name', 80)->nullable();
                $t->timestamp('created_at')->nullable();
            });
        }

        if (Schema::hasTable('ai_suggestions')) {
            Schema::table('ai_suggestions', function (Blueprint $t) {
                if (!Schema::hasColumn('ai_suggestions', 'batch_id'))    $t->unsignedBigInteger('batch_id')->nullable()->index();
                if (!Schema::hasColumn('ai_suggestions', 'review_note')) $t->text('review_note')->nullable();
            });
        }

        // Which AI suggestion a vehicle row came from — lets the report follow it through sales and returns.
        if (Schema::hasTable('part_interchange_vehicles') && !Schema::hasColumn('part_interchange_vehicles', 'ai_suggestion_id')) {
            Schema::table('part_interchange_vehicles', function (Blueprint $t) {
                $t->unsignedBigInteger('ai_suggestion_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_fitment_log');
        Schema::dropIfExists('ai_fitment_batches');
        foreach ([['ai_suggestions', ['batch_id', 'review_note']], ['part_interchange_vehicles', ['ai_suggestion_id']]] as [$table, $cols]) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($table, $cols) {
                    foreach ($cols as $c) if (Schema::hasColumn($table, $c)) $t->dropColumn($c);
                });
            }
        }
    }
};
