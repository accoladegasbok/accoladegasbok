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
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('subtotal_local', 12, 2)->nullable()->after('subtotal_usd');
            $table->decimal('discount_amount_local', 12, 2)->nullable()->after('discount_amount_usd');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('unit_price_local', 12, 2)->nullable()->after('unit_price_usd');
            $table->decimal('discount_amount_local', 12, 2)->nullable()->after('discount_amount_usd');
        });

        // ── Backfill existing invoices using the currency_code already
        // stored on each invoice (set at creation time) — freezes the
        // local-currency value permanently, no future recompute.
        $invoices = DB::table('invoices')->select('id', 'subtotal_usd', 'discount_amount_usd', 'currency_code', 'location')->get();

        foreach ($invoices as $inv) {
            $currency = $inv->currency_code
                ? collect(['NGN' => 1600, 'GHS' => 15.5, 'GBP' => 0.79, 'USD' => 1])->get($inv->currency_code, 1)
                : InvoiceController::currencyForLocation($inv->location ?? '')['rate'];

            $rate     = is_array($currency) ? $currency['rate'] : $currency;
            $decimals = $inv->currency_code === 'NGN' ? 0 : 2;

            DB::table('invoices')->where('id', $inv->id)->update([
                'subtotal_local'        => round(($inv->subtotal_usd ?? 0) * $rate, $decimals),
                'discount_amount_local' => round(($inv->discount_amount_usd ?? 0) * $rate, $decimals),
            ]);
        }

        $items = DB::table('invoice_items as ii')
            ->join('invoices as inv', 'inv.id', '=', 'ii.invoice_id')
            ->select('ii.id', 'ii.unit_price_usd', 'ii.discount_amount_usd', 'inv.currency_code', 'inv.location')
            ->get();

        foreach ($items as $item) {
            $rateMap  = ['NGN' => 1600, 'GHS' => 15.5, 'GBP' => 0.79, 'USD' => 1];
            $rate     = $rateMap[$item->currency_code] ?? InvoiceController::currencyForLocation($item->location ?? '')['rate'];
            $decimals = $item->currency_code === 'NGN' ? 0 : 2;

            DB::table('invoice_items')->where('id', $item->id)->update([
                'unit_price_local'      => round(($item->unit_price_usd ?? 0) * $rate, $decimals),
                'discount_amount_local' => round(($item->discount_amount_usd ?? 0) * $rate, $decimals),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['subtotal_local', 'discount_amount_local']);
        });
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['unit_price_local', 'discount_amount_local']);
        });
    }
};
