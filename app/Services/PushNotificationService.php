<?php
// FILE: app/Services/PushNotificationService.php
//
// Sends push notifications to staff devices via Firebase Cloud
// Messaging (FCM), which handles both Android and iOS delivery.
// Uses the modern FCM HTTP v1 API (OAuth2 service-account auth, not
// the older/deprecated legacy server-key API).

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PushNotificationService
{
    /**
     * Send a notification to ALL registered staff devices, or a
     * specific subset by staff_id.
     *
     * @param string $title
     * @param string $body
     * @param array  $data        Optional extra payload (e.g. ['url' => '/admin/orders/5'])
     * @param array|null $staffIds Limit to specific staff (null = everyone with a token)
     */
    public function send(string $title, string $body, array $data = [], ?array $staffIds = null): void
    {
        $query = DB::table('device_tokens');
        if ($staffIds) {
            $query->whereIn('staff_id', $staffIds);
        }
        $tokens = $query->pluck('token');

        if ($tokens->isEmpty()) return;

        $accessToken = $this->getAccessToken();
        if (!$accessToken) return;

        $projectId = env('FIREBASE_PROJECT_ID');

        foreach ($tokens as $token) {
            Http::withToken($accessToken)->post(
                "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                [
                    'message' => [
                        'token'        => $token,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data'         => array_map('strval', $data),
                    ],
                ]
            );
        }
    }

    /**
     * Exchanges the Firebase service-account JSON (stored on the
     * server, path set via FIREBASE_CREDENTIALS_PATH in .env) for a
     * short-lived OAuth2 access token, cached to avoid re-fetching on
     * every single notification sent.
     */
    private function getAccessToken(): ?string
    {
        return Cache::remember('fcm_access_token', 3300, function () {
            $credentialsPath = env('FIREBASE_CREDENTIALS_PATH');
            if (!$credentialsPath || !file_exists($credentialsPath)) {
                return null;
            }

            $credentials = json_decode(file_get_contents($credentialsPath), true);

            $now = time();
            $header = $this->base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64url(json_encode([
                'iss'   => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'exp'   => $now + 3600,
                'iat'   => $now,
            ]));

            $signatureInput = "{$header}.{$claims}";
            openssl_sign($signatureInput, $signature, $credentials['private_key'], 'SHA256');
            $jwt = "{$signatureInput}." . $this->base64url($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            return $response->json('access_token');
        });
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
