<?php
// FILE: database/migrations/2026_07_13_000000_add_columns_to_invoice_edit_log.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_edit_log', function (Blueprint $table) {
            $table->string('staff_role', 30)->nullable()->after('edited_by');
            $table->string('override_by', 150)->nullable()->after('staff_role');
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_edit_log', function (Blueprint $table) {
            $table->dropColumn(['staff_role', 'override_by', 'updated_at']);
        });
    }
};
