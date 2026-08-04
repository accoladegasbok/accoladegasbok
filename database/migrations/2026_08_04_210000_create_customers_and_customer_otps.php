<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NEW: real customer accounts. Previously "customers" only existed as
 * a computed grouping of orders/invoices by phone number — there was
 * no actual account, login, or verified identity. This adds one.
 *
 * Deliberately a NEW table, not a retrofit of `customer_profile_overrides`
 * (that table serves a different purpose — staff-side corrections to a
 * customer's display name, editable by admin regardless of whether the
 * customer has ever logged in anywhere). A registered account here is
 * matched to existing order/invoice history by phone number, same way
 * CustomerController::index() already groups them today.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 30)->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            // Which channel they'd like OTP codes sent through by
            // default — email works today; telegram/whatsapp require
            // setup described in CustomerOtpService before they're live.
            $table->enum('preferred_otp_channel', ['email', 'telegram', 'whatsapp'])->default('email');
            // Populated once a customer completes the Telegram bot
            // linking flow (see CustomerAuthController::telegramLink).
            // Null means they haven't linked Telegram — sending will
            // fail gracefully with a clear message until they do.
            $table->string('telegram_chat_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // ── OTP codes — one table for every verification purpose and
        // every channel, rather than a separate table per channel/purpose.
        Schema::create('customer_otps', function (Blueprint $table) {
            $table->id();
            // Nullable — during registration, no customer row exists
            // yet. identifier (email or phone) is the lookup key until
            // the account is created; customer_id backfills for
            // logged-in-flow purposes (e.g. re-verifying a changed email).
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('identifier'); // email or phone, depending on channel
            $table->enum('channel', ['email', 'telegram', 'whatsapp']);
            $table->enum('purpose', ['register', 'login', 'change_email', 'change_phone', 'telegram_link']);
            $table->string('code', 6);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['identifier', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_otps');
        Schema::dropIfExists('customers');
    }
};
