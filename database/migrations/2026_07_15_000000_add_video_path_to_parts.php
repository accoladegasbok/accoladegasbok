<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->string('video_path', 255)->nullable()->after('photos');
        });
    }

    public function down(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->dropColumn('video_path');
        });
    }
};
