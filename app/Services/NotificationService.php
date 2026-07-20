<?php
// FILE: app/Services/NotificationService.php
//
// Single, centralized place for every outbound customer/staff
// notification — email, SMS, WhatsApp. Nothing else in the app should
// call Mail::send(), a Termii API, or build a wa.me link directly —
// route it through here instead. This is what lets the whole app
// switch or add providers later (Termii -> Twilio, WhatsApp deep-link
// -> Meta Cloud API) by editing ONE file, not every call site.
//
// Current status per channel:
//   Email    — LIVE. Uses your real cPanel SMTP (accounts@autozenithparts.com).
//   WhatsApp — LIVE, but manual-send only. Generates a wa.me deep-link
//              with the message pre-filled; a staff member must open
//              it and tap Send. True zero-touch automation needs
//              Meta's Business Cloud API (separate project — business
//              verification + template approval).
//   SMS      — NOT YET CONFIGURED. Safely no-ops (logs + returns
//              false) rather than crashing, until a provider (Termii
//              recommended — Nigeria-first, also does WhatsApp
//              automation later if you upgrade that channel too) is
//              wired in. Every SMS call site already goes through
//              here, so turning SMS on later is a one-file change.

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    // =========================================================
    // EMAIL — live, via your cPanel SMTP mailbox
    // =========================================================
    public static function sendEmail(string $toEmail, string $toName, string $subject, string $bodyHtml, array $attachments = []): bool
    {
        if (empty($toEmail)) {
            Log::warning('NotificationService::sendEmail — no recipient email, skipped.', ['subject' => $subject]);
            return false;
        }

        try {
            Mail::html($bodyHtml, function ($message) use ($toEmail, $toName, $subject, $attachments) {
                $message->to($toEmail, $toName)->subject($subject);
                foreach ($attachments as $path) {
                    if (file_exists($path)) $message->attach($path);
                }
            });
            return true;
        } catch (\Exception $e) {
            Log::error('NotificationService::sendEmail failed', ['error' => $e->getMessage(), 'to' => $toEmail]);
            return false;
        }
    }

    // =========================================================
    // WHATSAPP — deep-link generation only (manual-send). Returns
    // the wa.me URL; the CALLER decides whether to show it as a
    // clickable button, redirect to it, etc. This does NOT send
    // anything itself — WhatsApp opens with the message pre-filled,
    // and a person has to tap Send.
    // =========================================================
    public static function whatsappLink(string $phone, string $message): string
    {
        $normalized = ltrim(preg_replace('/[^\d+]/', '', $phone), '+');
        return 'https://wa.me/' . $normalized . '?text=' . urlencode($message);
    }

    // =========================================================
    // SMS — not configured yet. Safe no-op: logs the attempt and
    // returns false rather than crashing every call site that uses
    // it. When a provider is ready (Termii recommended), only this
    // method needs to change — everywhere else stays the same.
    // =========================================================
    public static function sendSms(string $phone, string $message): bool
    {
        Log::info('NotificationService::sendSms — SMS not configured yet, message NOT sent.', [
            'phone' => $phone,
            'message_preview' => mb_substr($message, 0, 50),
        ]);
        return false;

        // ── When ready, replace the two lines above with something like: ──
        // $response = Http::withToken(config('services.termii.key'))
        //     ->post('https://api.ng.termii.com/api/sms/send', [
        //         'to' => $phone, 'from' => config('services.termii.sender_id'),
        //         'sms' => $message, 'type' => 'plain', 'channel' => 'generic',
        //     ]);
        // return $response->successful();
    }

    // =========================================================
    // Convenience: send the SAME event across whichever channels
    // have real contact info + are actually configured. Returns a
    // per-channel result array so callers can show "Email sent,
    // WhatsApp link ready, SMS not configured" style feedback
    // instead of a single misleading yes/no.
    // =========================================================
    public static function notify(array $recipient, string $subject, string $message, string $emailHtml = null): array
    {
        $results = ['email' => false, 'whatsapp_link' => null, 'sms' => false];

        if (!empty($recipient['email'])) {
            $results['email'] = self::sendEmail(
                $recipient['email'],
                $recipient['name'] ?? '',
                $subject,
                $emailHtml ?? nl2br(e($message))
            );
        }

        if (!empty($recipient['phone'])) {
            $results['whatsapp_link'] = self::whatsappLink($recipient['phone'], $message);
            $results['sms'] = self::sendSms($recipient['phone'], $message); // currently always false, logged above
        }

        return $results;
    }
}
