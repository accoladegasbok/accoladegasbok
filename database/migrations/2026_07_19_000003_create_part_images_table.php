<?php
// FILE: database/migrations/2026_07_19_000003_create_part_images_table.php
//
// Multiple TYPED reference photos per physical part — Front, Connector,
// Tag/Label, Damage, etc. — the "pictures like ALLDATA/eBay" request:
// staff and customers can visually confirm a match (connector shape,
// tag text) without physically handling the part first.
//
// Distinct from parts_inventory.photos (existing JSON column, general
// listing photos for the storefront) — this table is specifically for
// IDENTIFICATION photos tied to interchange confidence, with metadata
// JSON can't hold (image_type, who uploaded it, notes). The two can
// coexist; parts_inventory.photos remains the storefront gallery.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('part_images')) {
            Schema::create('part_images', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('part_id'); // FK to parts_inventory.id
                $table->enum('image_type', ['Front', 'Back', 'Side', 'Connector', 'Tag', 'Damage', 'Other'])->default('Front');
                $table->string('filename_or_url', 500);
                $table->boolean('is_primary')->default(false);
                $table->string('notes', 300)->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable(); // staff id
                $table->timestamps();

                $table->index('part_id');
                $table->foreign('part_id')->references('id')->on('parts_inventory')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('part_images');
    }
};
