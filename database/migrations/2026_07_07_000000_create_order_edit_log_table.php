<?php
// FILE: database/migrations/2026_07_07_000000_create_order_edit_log_table.php
//
// Completes improvement #5 for Orders — Manual Invoices/Quick Receipts
// already have editable line items with a full change-log
// (invoice_edit_log). Orders had no equivalent: only status/payment/
// delete could be changed, never the actual line items on an order
// after it was placed. This table gives Orders the same audit trail.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_edit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('edited_by', 80)->nullable();
            $table->text('changes_summary')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_edit_log');
    }
};
