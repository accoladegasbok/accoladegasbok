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
use Illuminate\Support\Facades\DB;

class NotificationService
{
    // =========================================================
    // UNSUBSCRIBE / MUTE — deterministic, stateless token generation.
    // No database row needs to exist ahead of time for a link to
    // work: the token is a signed hash of the phone number, verified
    // the same way on the receiving end. A notification_preferences
    // row only gets CREATED the first time someone actually opts out
    // of something — see the migration's design note.
    // =========================================================
    public static function unsubscribeToken(string $phone): string
    {
        $normalized = preg_replace('/\D/', '', $phone);
        return hash_hmac('sha256', $normalized, config('app.key'));
    }

    public static function verifyUnsubscribeToken(string $phone, string $token): bool
    {
        return hash_equals(self::unsubscribeToken($phone), $token);
    }

    private static function isOptedOut(string $phone, string $channel): bool
    {
        $normalized = preg_replace('/\D/', '', $phone);
        if ($normalized === '') return false;
        $column = $channel . '_opt_out'; // 'email_opt_out', 'sms_opt_out', 'whatsapp_opt_out'
        return (bool) DB::table('notification_preferences')
            ->where('phone', $normalized)
            ->value($column);
    }

    // Public entry point for the same check — for code that sends
    // through a DIFFERENT mechanism than this service (e.g. a
    // Mailable class) but still needs to respect an opt-out choice.
    public static function isOptedOutPublic(string $phone, string $channel): bool
    {
        return self::isOptedOut($phone, $channel);
    }

    // =========================================================
    // EMAIL — live, via your cPanel SMTP mailbox
    // $attachments: array of file PATHS on disk (existing behavior)
    // $rawAttachments: array of ['data'=>binary,'filename'=>...,
    //   'mime'=>...] — for content generated in-memory (e.g. a PDF)
    //   that was never written to a file, avoiding temp-file cleanup.
    // $customerPhone: optional — if provided, an unsubscribe link is
    //   automatically appended to the email footer, AND this checks
    //   whether the customer has already opted out of email before
    //   sending at all.
    // =========================================================
    public static function sendEmail(string $toEmail, string $toName, string $subject, string $bodyHtml, array $attachments = [], array $rawAttachments = [], ?string $customerPhone = null): bool
    {
        if (empty($toEmail)) {
            Log::warning('NotificationService::sendEmail — no recipient email, skipped.', ['subject' => $subject]);
            return false;
        }

        if ($customerPhone && self::isOptedOut($customerPhone, 'email')) {
            Log::info('NotificationService::sendEmail — recipient has opted out of email, skipped.', ['phone' => $customerPhone]);
            return false;
        }

        // Append an unsubscribe footer — required for any real
        // marketing/transactional email practice, and was completely
        // absent before. Only added when we have a phone number to
        // build the link from (every notify() call site has one).
        if ($customerPhone) {
            $token = self::unsubscribeToken($customerPhone);
            $unsubUrl = url('/unsubscribe/' . preg_replace('/\D/', '', $customerPhone) . '/' . $token . '/email');
            $bodyHtml .= "<p style='font-size:11px;color:#999;margin-top:24px;border-top:1px solid #eee;padding-top:10px;'>"
                . "Don't want these emails? <a href='{$unsubUrl}' style='color:#999;'>Unsubscribe</a></p>";
        }

        try {
            Mail::html($bodyHtml, function ($message) use ($toEmail, $toName, $subject, $attachments, $rawAttachments) {
                $message->to($toEmail, $toName)->subject($subject);
                foreach ($attachments as $path) {
                    if (file_exists($path)) $message->attach($path);
                }
                foreach ($rawAttachments as $raw) {
                    if (!empty($raw['data']) && !empty($raw['filename'])) {
                        $message->attachData($raw['data'], $raw['filename'], [
                            'mime' => $raw['mime'] ?? 'application/octet-stream',
                        ]);
                    }
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
        if (self::isOptedOut($phone, 'sms')) {
            Log::info('NotificationService::sendSms — recipient has opted out of SMS, skipped.', ['phone' => $phone]);
            return false;
        }

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
                $emailHtml ?? nl2br(e($message)),
                [], [],
                $recipient['phone'] ?? null // enables opt-out check + unsubscribe footer
            );
        }

        if (!empty($recipient['phone'])) {
            // WhatsApp is manual-send-only (staff taps Send), but we
            // still respect an explicit opt-out — no link generated
            // at all if the customer asked to be muted on this channel.
            if (!self::isOptedOut($recipient['phone'], 'whatsapp')) {
                $results['whatsapp_link'] = self::whatsappLink($recipient['phone'], $message);
            }
            $results['sms'] = self::sendSms($recipient['phone'], $message); // currently always false, logged above
        }

        return $results;
    }
}
