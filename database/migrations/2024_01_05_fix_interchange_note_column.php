<?php
// FILE: database/migrations/2024_01_05_fix_interchange_note_column.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts_compatibility', function (Blueprint $table) {
            $table->text('interchange_note')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('parts_compatibility', function (Blueprint $table) {
            $table->string('interchange_note', 255)->nullable()->change();
        });
    }
};
