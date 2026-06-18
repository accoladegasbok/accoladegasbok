<?php
// FILE: database/migrations/2024_01_01_000004_create_orders_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Carts ─────────────────────────────────────────────────
        if (!Schema::hasTable('carts')) {
            Schema::create('carts', function (Blueprint $table) {
                $table->id();
                $table->string('session_key', 64)->unique()->index();
                $table->json('items')->nullable();
                $table->timestamp('expires_at');
                $table->timestamps();
            });
        }

        // ── Orders ────────────────────────────────────────────────
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_ref', 20)->unique();
                $table->string('customer_name', 100);
                $table->string('customer_email', 150)->nullable();
                $table->string('customer_phone', 30);
                $table->string('customer_whatsapp', 30)->nullable();
                $table->string('customer_city', 80)->nullable();
                $table->enum('customer_country', ['Nigeria','Ghana','USA','Other'])->default('Nigeria');
                $table->text('delivery_address')->nullable();
                $table->enum('payment_method', ['bank_transfer','pos_instore'])->default('bank_transfer');
                $table->enum('payment_status', [
                    'pending','transfer_sent','confirmed','failed','refunded'
                ])->default('pending')->index();
                $table->decimal('total_amount_ngn', 14, 2);
                $table->decimal('total_amount_usd', 10, 2);
                $table->decimal('exchange_rate', 10, 4)->default(1600);
                $table->string('transfer_reference', 100)->nullable();
                $table->string('transfer_proof_url', 300)->nullable();
                $table->timestamp('transfer_claimed_at')->nullable();
                $table->timestamp('payment_confirmed_at')->nullable();
                $table->string('confirmed_by', 80)->nullable();
                $table->enum('order_status', [
                    'draft','awaiting_payment','payment_pending_confirmation',
                    'confirmed','processing','ready_for_collection',
                    'shipped','completed','cancelled'
                ])->default('awaiting_payment')->index();
                $table->enum('fulfillment_type', ['collection','delivery'])->default('collection');
                $table->text('notes')->nullable();
                $table->text('staff_notes')->nullable();
                $table->timestamps();
            });
        }

        // ── Order items ───────────────────────────────────────────
        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->onDelete('cascade');
                $table->unsignedBigInteger('part_id');
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
                $table->decimal('subtotal_ngn', 14, 2);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('carts');
    }
};
