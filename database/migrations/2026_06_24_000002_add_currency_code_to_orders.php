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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('currency_code', 3)->nullable()->after('total_amount_usd');
        });

        // Backfill using customer_country (orders don't have a "location"
        // field like invoices/parts do — country is the closest proxy).
        $orders = DB::table('orders')->select('id', 'customer_country')->get();
        foreach ($orders as $order) {
            $code = InvoiceController::currencyForLocation($order->customer_country ?? '')['code'];
            DB::table('orders')->where('id', $order->id)->update(['currency_code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('currency_code');
        });
    }
};
