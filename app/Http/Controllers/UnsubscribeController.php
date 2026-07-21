<?php
// FILE: app/Http/Controllers/UnsubscribeController.php
//
// PUBLIC route (no admin auth) — a customer clicking the unsubscribe
// link in an email lands here. Verifies the signed token matches
// their phone number (same HMAC the email footer link was built
// with) before honoring the opt-out, so nobody can mute another
// customer's notifications just by guessing a phone number in the URL.

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;

class UnsubscribeController extends Controller
{
    // GET /unsubscribe/{phone}/{token}/{channel}
    public function show(string $phone, string $token, string $channel)
    {
        $normalized = preg_replace('/\D/', '', $phone);
        $validChannel = in_array($channel, ['email', 'sms', 'whatsapp']);
        $validToken = NotificationService::verifyUnsubscribeToken($normalized, $token);

        if (!$validChannel || !$validToken) {
            return view('unsubscribe', ['success' => false, 'error' => 'This unsubscribe link is invalid or has expired.']);
        }

        $column = $channel . '_opt_out';
        $exists = DB::table('notification_preferences')->where('phone', $normalized)->exists();
        if ($exists) {
            DB::table('notification_preferences')->where('phone', $normalized)->update([
                $column => true, 'updated_at' => now(),
            ]);
        } else {
            DB::table('notification_preferences')->insert([
                'phone'      => $normalized,
                'email_opt_out'    => $channel === 'email',
                'sms_opt_out'      => $channel === 'sms',
                'whatsapp_opt_out' => $channel === 'whatsapp',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return view('unsubscribe', ['success' => true, 'channel' => $channel]);
    }
}
