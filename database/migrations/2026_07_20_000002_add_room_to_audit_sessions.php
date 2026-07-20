<?php
// FILE: database/migrations/2026_07_20_000002_add_room_to_audit_sessions.php
//
// Adds room-level scoping to audit sessions — previously an audit
// could only be scoped to Location + Category, with no way to audit
// just one storage room at a time.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_sessions')) {
            Schema::table('audit_sessions', function (Blueprint $table) {
                if (!Schema::hasColumn('audit_sessions', 'room_id')) {
                    $table->unsignedBigInteger('room_id')->nullable()->after('location');
                }
                if (!Schema::hasColumn('audit_sessions', 'room_name')) {
                    $table->string('room_name', 100)->nullable()->after('room_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('audit_sessions')) {
            Schema::table('audit_sessions', function (Blueprint $table) {
                foreach (['room_id', 'room_name'] as $col) {
                    if (Schema::hasColumn('audit_sessions', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
