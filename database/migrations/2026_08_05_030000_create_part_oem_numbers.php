<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * NEW: normalized OEM number cross-reference — the one structural gap
 * identified when comparing against the Hollander schema (their
 * Int_ID table: one interchange number, MANY OEM numbers across MANY
 * manufacturers). parts_inventory.oem_part_number was a single free
 * -text field — fine for "this part's main OEM number," but couldn't
 * represent "this alternator matches both a Denso AND an Aisin OEM
 * number," which is a real, common situation.
 *
 * Keyed by parts_inventory_id (not interchange_group_id) — a part
 * doesn't need a confirmed Interchange Group to have multiple known
 * OEM numbers, and most of your inventory isn't grouped yet. This
 * stays useful standalone and composes naturally with the group
 * system whenever a part does get grouped.
 *
 * `oem_part_number` on parts_inventory is NOT dropped or deprecated
 * here — every existing value is backfilled into this new table as
 * the "primary" number, and the old field keeps working exactly as
 * before for anything already reading/writing it. This is purely
 * additive; nothing existing breaks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_oem_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parts_inventory_id');
            $table->string('oem_number', 60);
            // Which manufacturer this specific OEM number belongs to —
            // e.g. "Denso", "Aisin", "Toyota Genuine". Nullable since
            // this often isn't known at entry time.
            $table->string('manufacturer', 60)->nullable();
            // Mirrors Hollander's IsBaseNbrInd concept — flags the
            // main/preferred number when a part has several. The
            // backfilled legacy value is always marked primary.
            $table->boolean('is_primary')->default(false);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index('parts_inventory_id');
            $table->unique(['parts_inventory_id', 'oem_number']);
        });

        // Backfill every existing oem_part_number value as this part's
        // primary entry — no data lost, old field stays authoritative
        // for anything not yet updated to read the new table.
        DB::table('parts_inventory')
            ->whereNotNull('oem_part_number')
            ->where('oem_part_number', '!=', '')
            ->orderBy('id')
            ->chunk(500, function ($parts) {
                $rows = $parts->map(fn($p) => [
                    'parts_inventory_id' => $p->id,
                    'oem_number'         => $p->oem_part_number,
                    'manufacturer'       => null,
                    'is_primary'         => true,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ])->toArray();

                DB::table('part_oem_numbers')->insertOrIgnore($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_oem_numbers');
    }
};
