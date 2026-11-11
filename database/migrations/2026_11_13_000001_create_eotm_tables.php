<?php
// FILE: database/migrations/2026_11_13_000001_create_eotm_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('eotm_cycles')) {
            Schema::create('eotm_cycles', function (Blueprint $t) {
                $t->id();
                $t->string('ym', 7)->unique();                     // 2026-11
                $t->string('status', 10)->default('rating');       // rating -> voting -> closed
                $t->unsignedBigInteger('winner_staff_id')->nullable();
                $t->json('rewards')->nullable();                   // ["cash","voucher","salary","certificate","promotion"]
                $t->decimal('prize_amount', 12, 2)->nullable();
                $t->string('prize_currency', 3)->nullable();
                $t->string('prize_note', 255)->nullable();
                $t->timestamp('paid_at')->nullable();
                $t->timestamp('shortlisted_at')->nullable();
                $t->timestamp('closed_at')->nullable();
                $t->timestamps();
            });
        }
        if (!Schema::hasTable('eotm_ratings')) {
            Schema::create('eotm_ratings', function (Blueprint $t) {
                $t->id();
                $t->string('ym', 7);
                $t->unsignedBigInteger('staff_id');                // the person being rated
                $t->unsignedBigInteger('rater_id');
                $t->unsignedTinyInteger('customer_satisfaction')->nullable();   // 1-5
                $t->unsignedTinyInteger('teamwork')->nullable();
                $t->unsignedTinyInteger('communication')->nullable();
                $t->unsignedTinyInteger('problem_solving')->nullable();
                $t->string('comment', 255)->nullable();
                $t->timestamps();
                $t->unique(['ym', 'staff_id', 'rater_id']);
            });
        }
        if (!Schema::hasTable('eotm_nominees')) {
            Schema::create('eotm_nominees', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('cycle_id');
                $t->unsignedBigInteger('staff_id');
                $t->unsignedTinyInteger('rank')->default(1);
                $t->decimal('performance_score', 5, 1);
                $t->json('breakdown')->nullable();
                $t->unsignedInteger('votes')->default(0);
                $t->decimal('vote_share', 5, 1)->nullable();
                $t->decimal('final_score', 5, 1)->nullable();
                $t->boolean('disqualified')->default(false);
                $t->string('disqualified_reason', 120)->nullable();
                $t->timestamps();
                $t->unique(['cycle_id', 'staff_id']);
            });
        }
        if (!Schema::hasTable('eotm_votes')) {
            Schema::create('eotm_votes', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('cycle_id');
                $t->unsignedBigInteger('voter_staff_id');
                $t->unsignedBigInteger('nominee_staff_id');
                $t->timestamps();
                $t->unique(['cycle_id', 'voter_staff_id']);        // one vote per staff member per month
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('eotm_votes');
        Schema::dropIfExists('eotm_nominees');
        Schema::dropIfExists('eotm_ratings');
        Schema::dropIfExists('eotm_cycles');
    }
};
