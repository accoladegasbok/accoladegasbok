<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NEW: photo/video gallery for the public homepage — brand and staff
 * media, self-managed through admin (not auto-pulled from social
 * accounts, per standing decision — manual upload is the safer,
 * faster build, and keeps content curated rather than automatic).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_gallery', function (Blueprint $table) {
            $table->id();
            $table->enum('media_type', ['photo', 'video']);
            $table->string('title', 150)->nullable();
            // Loose category, not a strict enum — e.g. 'Brand', 'Staff',
            // 'Shop Floor', 'Events'. Free text so new categories don't
            // need a migration every time.
            $table->string('category', 60)->nullable();
            $table->string('file_path', 255);
            // Only relevant for videos — a poster/thumbnail image shown
            // before play, since videos don't auto-generate one.
            $table->string('thumbnail_path', 255)->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('uploaded_by_staff_id')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_gallery');
    }
};
