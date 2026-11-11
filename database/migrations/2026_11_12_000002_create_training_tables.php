<?php
// FILE: database/migrations/2026_11_12_000002_create_training_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('training_courses')) {
            Schema::create('training_courses', function (Blueprint $t) {
                $t->id();
                $t->string('title', 150);
                $t->string('area', 60)->nullable();               // Stock keeping, Customer service, Auto mechanic ...
                $t->text('description')->nullable();
                $t->string('provider_type', 10)->default('internal');   // internal | external
                $t->string('provider_name', 120)->nullable();     // who runs / delivered it
                $t->string('video_source', 10)->default('upload');      // upload | youtube | none (classroom / external session)
                $t->string('video_path')->nullable();
                $t->string('youtube_id', 20)->nullable();
                $t->unsignedSmallInteger('duration_minutes')->nullable();
                $t->text('policy_text')->nullable();              // company policy the staff member must read and accept
                $t->unsignedTinyInteger('pass_mark')->default(80);
                $t->unsignedInteger('recurrence_days')->nullable();     // null = one-time; else must retrain every N days
                $t->boolean('is_active')->default(true);
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('training_questions')) {
            Schema::create('training_questions', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('course_id')->index();
                $t->text('question');
                $t->json('options');                              // ["A text","B text",...]
                $t->unsignedTinyInteger('correct_index')->default(0);
                $t->unsignedSmallInteger('sort')->default(0);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('training_assignments')) {
            Schema::create('training_assignments', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('course_id');
                $t->unsignedBigInteger('staff_id');
                $t->unsignedBigInteger('assigned_by')->nullable();
                $t->date('due_date');
                $t->string('status', 12)->default('assigned');    // assigned | in_progress | completed
                $t->unsignedInteger('max_position')->default(0);  // furthest second watched
                $t->unsignedInteger('video_seconds')->default(0); // length of the video as reported by the player
                $t->boolean('video_done')->default(false);
                $t->timestamp('last_ping_at')->nullable();
                $t->boolean('policy_accepted')->default(false);
                $t->unsignedTinyInteger('score')->nullable();
                $t->unsignedSmallInteger('attempts')->default(0);
                $t->boolean('is_retraining')->default(false);
                $t->timestamp('completed_at')->nullable();
                $t->timestamps();
                $t->index(['staff_id', 'status', 'due_date']);
                $t->index('course_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('training_assignments');
        Schema::dropIfExists('training_questions');
        Schema::dropIfExists('training_courses');
    }
};
