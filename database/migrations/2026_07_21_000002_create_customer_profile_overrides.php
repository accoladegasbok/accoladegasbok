<?php
// FILE: database/migrations/2026_07_21_000002_create_customer_profile_overrides.php
//
// Customer records on the Customers & Freelancers page are auto-built
// by aggregating orders/invoices grouped by phone number — there's no
// single editable row for "a customer." This table lets staff correct
// the DISPLAYED name/phone/email/address without touching historical
// order/invoice records (which stay exactly as they were at time of
// sale — correct for accounting/audit purposes). When a row exists
// here for a phone, its values take precedence for display; when it
// doesn't, the auto-derived values from order/invoice history are
// used as before.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_profile_overrides')) {
            Schema::create('customer_profile_overrides', function (Blueprint $table) {
                $table->id();
                $table->string('phone', 30)->unique(); // normalized, digits only - the original phone, used as the lookup key even if a corrected phone is stored below
                $table->string('override_name')->nullable();
                $table->string('override_phone', 30)->nullable(); // a corrected phone number, if the original was wrong
                $table->string('override_email')->nullable();
                $table->string('override_address')->nullable();
                $table->unsignedBigInteger('updated_by_staff_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profile_overrides');
    }
};
