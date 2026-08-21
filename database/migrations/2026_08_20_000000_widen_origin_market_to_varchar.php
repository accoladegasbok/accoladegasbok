<?php
// FILE: database/migrations/2026_08_20_000000_widen_origin_market_to_varchar.php
//
// origin_market was created as ENUM('JDM','USDM','EDM','Nigerian Used','N/A').
// The Add/Edit Inventory form's dropdown was later extended with a
// "Tokunbo" option that was never added to the ENUM — so selecting it
// causes MySQL strict mode to reject the insert with:
//   "Data truncated for column 'origin_market' at row 1"
//
// Same root cause as the earlier brand / staff.location / gear_alias /
// customer_otps.purpose truncation bugs. Same permanent fix: widen to
// VARCHAR instead of patching the ENUM list, so the next new dropdown
// value can never recreate this exact crash.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->string('origin_market', 30)->default('N/A')->change();
        });
    }

    public function down(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->enum('origin_market', ['JDM', 'USDM', 'EDM', 'Nigerian Used', 'N/A'])
                  ->default('N/A')->change();
        });
    }
};
