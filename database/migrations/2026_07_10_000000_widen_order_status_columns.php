<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Same recurring problem as fulfillment_type, location,
            // customer_country, and staff.role earlier this project —
            // these were ENUM columns that didn't include values our
            // own code now uses ('awaiting_payment', 'confirmed', etc).
            // Widened to VARCHAR so this can never happen again
            // regardless of what new statuses get introduced later.
            $table->string('payment_status', 30)->change();
            $table->string('order_status', 30)->change();
        });
    }

    public function down(): void
    {
        // Intentionally no-op — reverting to a restrictive ENUM would
        // immediately break existing orders using newer status values.
    }
};
