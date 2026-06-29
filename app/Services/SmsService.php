<?php
// FILE: app/Services/SmsService.php
//
// Sends SMS alerts via Twilio. REQUIRES a Twilio account — sign up at
// twilio.com, get a phone number, and add these to .env:
//   TWILIO_SID=...
//   TWILIO_AUTH_TOKEN=...
//   TWILIO_FROM_NUMBER=+1...
// Without these set, send() silently does nothing (logged, not thrown)
// rather than breaking the ticket flow if SMS isn't set up yet.

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $toNumber, string $message): void
    {
        $sid       = env('TWILIO_SID');
        $token     = env('TWILIO_AUTH_TOKEN');
        $fromNumber = env('TWILIO_FROM_NUMBER');

        if (!$sid || !$token || !$fromNumber) {
            Log::info("SMS not sent (Twilio not configured): would have sent to {$toNumber}: {$message}");
            return;
        }

        try {
            Http::asForm()->withBasicAuth($sid, $token)->post(
                "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json",
                [
                    'From' => $fromNumber,
                    'To'   => $toNumber,
                    'Body' => $message,
                ]
            );
        } catch (\Exception $e) {
            Log::warning("SMS send failed to {$toNumber}: " . $e->getMessage());
        }
    }

    // Send to every active Admin/Manager with a phone number on file
    public function notifyAdminsAndManagers(string $message): void
    {
        $recipients = \Illuminate\Support\Facades\DB::table('staff')
            ->whereIn('role', ['admin', 'manager'])
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->pluck('phone');

        foreach ($recipients as $phone) {
            $this->send($phone, $message);
        }
    }
}
