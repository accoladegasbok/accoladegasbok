<?php
// FILE: database/migrations/2026_07_20_000008_create_customer_notes.php
//
// Staff notes on a customer profile — e.g. "prefers WhatsApp over
// email", "always asks for trade pricing", "difficult return history".
// Keyed by normalized phone number, same identity key CustomerController
// already uses to group orders/invoices (customers aren't a
// first-class table).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_notes')) {
            Schema::create('customer_notes', function (Blueprint $table) {
                $table->id();
                $table->string('phone', 30); // normalized, digits only
                $table->text('note');
                $table->unsignedBigInteger('staff_id')->nullable();
                $table->timestamps();
                $table->index('phone');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notes');
    }
};
