<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Admin\InvoiceController;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            // price_local is the ONLY price anyone edits/trusts going forward.
            // price_usd is kept untouched as a frozen historical snapshot —
            // never recalculated — useful for cross-location $ reporting only.
            $table->decimal('price_local', 12, 2)->nullable()->after('price_usd');
            $table->string('currency_code', 3)->nullable()->after('price_local');
        });

        // ── One-time backfill: convert every existing record's price_usd
        // into its location's native currency, using the SAME rate table
        // already in use at the time of this migration. After this runs,
        // price_local is fixed and will never be recalculated again.
        $parts = DB::table('parts_inventory')->select('id', 'price_usd', 'location')->get();

        foreach ($parts as $part) {
            $currency = InvoiceController::currencyForLocation($part->location ?? '');
            $priceLocal = round(($part->price_usd ?? 0) * $currency['rate'], $currency['decimals']);

            DB::table('parts_inventory')->where('id', $part->id)->update([
                'price_local'   => $priceLocal,
                'currency_code' => $currency['code'],
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('parts_inventory', function (Blueprint $table) {
            $table->dropColumn(['price_local', 'currency_code']);
        });
    }
};
