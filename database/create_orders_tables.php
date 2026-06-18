<?php
// ============================================================
// FILE: database/migrations/2024_01_03_create_orders_tables.php
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Orders ────────────────────────────────────────────
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_ref', 20)->unique(); // AZ-2024-00001

            // Customer details
            $table->string('customer_name', 100);
            $table->string('customer_email', 150)->nullable();
            $table->string('customer_phone', 30);
            $table->string('customer_whatsapp', 30)->nullable();
            $table->string('customer_city', 80)->nullable();
            $table->enum('customer_country', ['Nigeria','Ghana','USA','Other'])->default('Nigeria');
            $table->text('delivery_address')->nullable();

            // Payment
            $table->enum('payment_method', [
                'bank_transfer',   // Nigerian bank transfer to Moniepoint
                'pos_instore',     // Physical POS at office
            ])->default('bank_transfer');

            $table->enum('payment_status', [
                'pending',         // Awaiting transfer / POS payment
                'transfer_sent',   // Customer claims they sent
                'confirmed',       // Staff confirmed receipt
                'failed',          // Payment not received
                'refunded',
            ])->default('pending')->index();

            $table->decimal('total_amount_ngn', 14, 2); // total in Naira
            $table->decimal('total_amount_usd', 10, 2); // reference USD
            $table->decimal('exchange_rate',    10, 4)->default(1600); // NGN per USD used

            // Bank transfer proof
            $table->string('transfer_reference', 100)->nullable(); // customer's bank ref
            $table->string('transfer_proof_url', 300)->nullable(); // Cloudinary URL of receipt photo
            $table->timestamp('transfer_claimed_at')->nullable();
            $table->timestamp('payment_confirmed_at')->nullable();
            $table->string('confirmed_by', 80)->nullable(); // staff name

            // Order status
            $table->enum('order_status', [
                'draft',
                'awaiting_payment',
                'payment_pending_confirmation',
                'confirmed',
                'processing',
                'ready_for_collection',
                'shipped',
                'completed',
                'cancelled',
            ])->default('awaiting_payment')->index();

            $table->enum('fulfillment_type', ['collection','delivery'])->default('collection');
            $table->text('notes')->nullable();
            $table->text('staff_notes')->nullable();

            $table->timestamps();

            $table->index(['order_status','payment_status']);
            $table->index('customer_phone');
        });

        // ── Order Items ───────────────────────────────────────
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('part_id')->constrained('parts_inventory')->onDelete('restrict');

            $table->string('part_code', 20);
            $table->string('part_name', 150);
            $table->string('brand', 50);
            $table->string('model', 80);
            $table->smallInteger('year_from');
            $table->smallInteger('year_to');
            $table->string('condition_grade', 5);
            $table->string('location', 60);

            $table->integer('quantity')->default(1);
            $table->decimal('unit_price_usd', 10, 2);
            $table->decimal('unit_price_ngn', 14, 2);
            $table->decimal('subtotal_ngn',   14, 2);

            $table->timestamps();
        });

        // ── Cart sessions (guest cart, expires after 7 days) ──
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->string('session_key', 64)->unique()->index();
            $table->json('items')->default('[]'); // [{part_id, qty, snapshot}]
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('carts');
    }
};
