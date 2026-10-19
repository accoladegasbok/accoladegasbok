<?php
// FILE: database/migrations/2026_10_19_000002_create_part_approvals.php
//
// Manager approval before new stock goes live.
//  - A new part saved by staff below manager gets the status "Under Review" (so the website, search,
//    carts and invoices — which only ever use "Available" parts — cannot see it) and one row here.
//  - A consumable restock by staff below manager waits here too, until a manager or admin approves it.
// Existing parts are untouched.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The new status needs a plain text status column. Only changed if it is still an ENUM.
        if (Schema::hasTable('parts_inventory') && Schema::hasColumn('parts_inventory', 'status')) {
            $info = DB::selectOne(
                'SELECT DATA_TYPE AS t, IS_NULLABLE AS n, COLUMN_DEFAULT AS d FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                ['parts_inventory', 'status']
            );
            if ($info && strtolower($info->t) === 'enum') {
                $null    = $info->n === 'YES' ? 'NULL' : 'NOT NULL';
                $default = $info->d !== null ? 'DEFAULT ' . DB::getPdo()->quote($info->d) : '';
                DB::statement("ALTER TABLE `parts_inventory` MODIFY COLUMN `status` VARCHAR(30) {$null} {$default}");
            }
        }

        if (!Schema::hasTable('part_approvals')) {
            Schema::create('part_approvals', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('part_id')->index();
                $t->string('type', 20)->default('new_part');          // new_part | restock
                $t->integer('qty_to_add')->nullable();                // restock only
                $t->string('status', 20)->default('pending')->index(); // pending | approved | rejected
                $t->unsignedBigInteger('requested_by_staff_id')->nullable();
                $t->string('requested_by_name', 120)->nullable();
                $t->unsignedBigInteger('reviewed_by_staff_id')->nullable();
                $t->string('reviewed_by_name', 120)->nullable();
                $t->timestamp('reviewed_at')->nullable();
                $t->string('note', 255)->nullable();                  // rejection reason
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('part_approvals');
    }
};
