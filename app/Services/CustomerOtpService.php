<?php

namespace App\Services;

use App\Mail\CustomerOtpMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generates and delivers OTP codes across email, Telegram, and
 * WhatsApp. Each channel is isolated behind its own send*() method so
 * adding/fixing one never touches the others.
 *
 * STATUS PER CHANNEL:
 *   - email:    WORKING — uses the app's existing mail configuration.
 *   - telegram: NEEDS SETUP — create a bot via @BotFather on Telegram
 *               (free, ~5 min), then set TELEGRAM_BOT_TOKEN in .env.
 *               Telegram also requires the CUSTOMER to have started a
 *               chat with the bot first (Telegram's own anti-spam
 *               rule — a bot can't message someone who hasn't messaged
 *               it) — see telegramLink() in CustomerAuthController for
 *               that one-time linking flow.
 *   - whatsapp: NOT YET POSSIBLE — Meta's Cloud API requires business
 *               verification and a registered phone number before it
 *               will send anything automatically. There is no free,
 *               instant way to send an automated WhatsApp message
 *               without that setup existing first. sendWhatsapp()
 *               throws clearly rather than pretending to succeed.
 */
class CustomerOtpService
{
    const OTP_LENGTH   = 6;
    const OTP_LIFETIME = 10; // minutes

    /**
     * Generate and send an OTP. Returns the OTP row so the caller can
     * redirect to a verify screen with context (masked identifier etc).
     *
     * $identifier is what the code VERIFIES (e.g. a new phone number
     * being added). $deliverTo is where it's actually SENT — these
     * differ for change_phone, where SMS delivery isn't available yet
     * and the code must go to the customer's already-verified email
     * instead. Defaults to $identifier when they're the same thing
     * (registration, login, change_email).
     *
     * @throws \RuntimeException if the requested channel can't send right now.
     */
    public function send(string $identifier, string $channel, string $purpose, ?int $customerId = null, ?string $deliverTo = null): object
    {
        $code = (string) random_int(100000, 999999);
        $deliverTo = $deliverTo ?? $identifier;

        $otpId = DB::table('customer_otps')->insertGetId([
            'customer_id' => $customerId,
            'identifier'  => $identifier,
            'channel'     => $channel,
            'purpose'     => $purpose,
            'code'        => $code,
            'expires_at'  => now()->addMinutes(self::OTP_LIFETIME),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        match ($channel) {
            'email'    => $this->sendEmail($deliverTo, $code, $purpose),
            'telegram' => $this->sendTelegram($deliverTo, $code, $customerId),
            'whatsapp' => $this->sendWhatsapp($deliverTo, $code),
            default    => throw new \InvalidArgumentException("Unknown OTP channel: {$channel}"),
        };

        return DB::table('customer_otps')->where('id', $otpId)->first();
    }

    /**
     * Verify a submitted code. Returns true/false; increments attempts
     * on every check (including failed) and rejects after 5 tries so
     * a code can't be brute-forced within its 10-minute window.
     */
    public function verify(string $identifier, string $purpose, string $submittedCode): bool
    {
        $otp = DB::table('customer_otps')
            ->where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if (!$otp) return false;

        if ($otp->attempts >= 5) return false;

        DB::table('customer_otps')->where('id', $otp->id)->increment('attempts');

        if (!hash_equals($otp->code, $submittedCode)) return false;

        DB::table('customer_otps')->where('id', $otp->id)->update(['consumed_at' => now()]);
        return true;
    }

    // ── EMAIL — working today ──────────────────────────────────────
    private function sendEmail(string $email, string $code, string $purpose): void
    {
        Mail::to($email)->send(new CustomerOtpMail($code, $purpose));
    }

    // ── TELEGRAM — needs TELEGRAM_BOT_TOKEN + a linked chat_id ──────
    private function sendTelegram(string $identifier, string $code, ?int $customerId): void
    {
        $token = config('services.telegram.bot_token');
        if (!$token) {
            throw new \RuntimeException('Telegram is not configured yet — set TELEGRAM_BOT_TOKEN in .env after creating a bot via @BotFather.');
        }

        $chatId = $customerId
            ? DB::table('customers')->where('id', $customerId)->value('telegram_chat_id')
            : null;

        if (!$chatId) {
            throw new \RuntimeException('This account has not linked Telegram yet — complete the Telegram linking step first.');
        }

        $res = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text'    => "Your Auto Zenith Parts verification code is: {$code}\n\nExpires in " . self::OTP_LIFETIME . " minutes.",
        ]);

        if (!$res->successful()) {
            Log::error('Telegram OTP send failed', ['response' => $res->body()]);
            throw new \RuntimeException('Could not send Telegram message — try email instead.');
        }
    }

    // ── WHATSAPP — not possible without Meta Cloud API setup ────────
    private function sendWhatsapp(string $identifier, string $code): void
    {
        $token      = config('services.whatsapp.access_token');
        $phoneId    = config('services.whatsapp.phone_number_id');

        if (!$token || !$phoneId) {
            throw new \RuntimeException('WhatsApp sending is not available yet — this requires Meta Cloud API business verification and a registered phone number. Use email or Telegram for now.');
        }

        // Left unimplemented beyond this guard clause — build this out
        // once real Meta Cloud API credentials exist. The call shape,
        // once set up, is a POST to
        // https://graph.facebook.com/v19.0/{$phoneId}/messages
        // with a template message (WhatsApp requires pre-approved
        // templates for business-initiated messages, so the OTP text
        // itself needs to be submitted to Meta for template approval
        // before this will actually work).
        throw new \RuntimeException('WhatsApp sending is configured but not yet implemented — contact your developer.');
    }
}
