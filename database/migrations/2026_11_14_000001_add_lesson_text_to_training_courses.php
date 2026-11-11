<?php
// FILE: database/migrations/2026_11_14_000001_add_lesson_text_to_training_courses.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('training_courses') && !Schema::hasColumn('training_courses', 'lesson_text')) {
            Schema::table('training_courses', function (Blueprint $t) {
                $t->longText('lesson_text')->nullable();   // written lesson shown above the quiz (use "## Heading" and "- bullet" lines)
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('training_courses', 'lesson_text')) {
            Schema::table('training_courses', fn (Blueprint $t) => $t->dropColumn('lesson_text'));
        }
    }
};
