<?php
// FILE: app/Services/WebAuthnLite.php
//
// A small, dependency-free WebAuthn (passkey) verifier - no composer package needed on cPanel.
// Supports ES256 (the algorithm every phone fingerprint / face unlock sensor uses).
// The phone's fingerprint never reaches the website: the phone signs a one-time challenge only after
// its owner unlocks it, and we check that signature against the public key saved at registration.

namespace App\Services;

class WebAuthnLite
{
    public static function b64uEncode(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    public static function b64uDecode(string $s): string
    {
        $s = strtr($s, '-_', '+/');
        return (string) base64_decode($s . str_repeat('=', (4 - strlen($s) % 4) % 4), true);
    }

    /** A fresh challenge is kept in the session and must come back inside clientDataJSON. */
    public static function newChallenge(string $purpose): string
    {
        $c = self::b64uEncode(random_bytes(32));
        session(['webauthn_challenge' => ['c' => $c, 'p' => $purpose, 't' => time()]]);
        return $c;
    }

    private static function takeChallenge(string $purpose): ?string
    {
        $row = session('webauthn_challenge');
        session()->forget('webauthn_challenge');           // one use only
        if (!is_array($row) || ($row['p'] ?? '') !== $purpose || time() - ($row['t'] ?? 0) > 300) return null;
        return $row['c'];
    }

    /** Checks the parts both registration and sign-in share. Returns an error string, or null if fine. */
    private static function checkClientData(string $clientDataJson, string $type, string $purpose, string $origin): ?string
    {
        $cd = json_decode($clientDataJson, true);
        if (!is_array($cd)) return 'Unreadable device response.';
        if (($cd['type'] ?? '') !== $type) return 'Wrong response type.';
        $expected = self::takeChallenge($purpose);
        if (!$expected) return 'Request expired. Please try again.';
        if (!hash_equals($expected, (string) ($cd['challenge'] ?? ''))) return 'Challenge mismatch.';
        if (($cd['origin'] ?? '') !== $origin) return 'This phone response came from another website.';
        return null;
    }

    /**
     * Registration. The browser gives us response.getPublicKey() (a DER SubjectPublicKeyInfo),
     * response.getAuthenticatorData() and clientDataJSON, all base64url.
     * Returns ['ok'=>bool, 'error'=>?, 'pem'=>?, 'sign_count'=>?]
     */
    public static function verifyRegistration(string $clientDataB64, string $authDataB64, string $spkiB64, string $rpId, string $origin): array
    {
        $err = self::checkClientData(self::b64uDecode($clientDataB64), 'webauthn.create', 'register', $origin);
        if ($err) return ['ok' => false, 'error' => $err];

        $auth = self::b64uDecode($authDataB64);
        if (strlen($auth) < 37) return ['ok' => false, 'error' => 'Bad device data.'];
        if (!hash_equals(hash('sha256', $rpId, true), substr($auth, 0, 32))) return ['ok' => false, 'error' => 'Device data is for another website.'];
        $flags = ord($auth[32]);
        if (!($flags & 0x01) || !($flags & 0x04)) return ['ok' => false, 'error' => 'The phone did not confirm fingerprint / face unlock.'];

        $der = self::b64uDecode($spkiB64);
        $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
        $key = @openssl_pkey_get_public($pem);
        if (!$key) return ['ok' => false, 'error' => 'Unsupported key. Use a phone with fingerprint or face unlock.'];
        $d = openssl_pkey_get_details($key);
        if (($d['type'] ?? null) !== OPENSSL_KEYTYPE_EC) return ['ok' => false, 'error' => 'Unsupported key type.'];

        return ['ok' => true, 'pem' => $pem, 'sign_count' => unpack('N', substr($auth, 33, 4))[1]];
    }

    /**
     * Sign-in. Returns ['ok'=>bool, 'error'=>?, 'sign_count'=>int]
     */
    public static function verifyAssertion(string $pem, int $storedCount, string $clientDataB64, string $authDataB64, string $sigB64, string $rpId, string $origin): array
    {
        $clientData = self::b64uDecode($clientDataB64);
        $err = self::checkClientData($clientData, 'webauthn.get', 'assert', $origin);
        if ($err) return ['ok' => false, 'error' => $err];

        $auth = self::b64uDecode($authDataB64);
        if (strlen($auth) < 37) return ['ok' => false, 'error' => 'Bad device data.'];
        if (!hash_equals(hash('sha256', $rpId, true), substr($auth, 0, 32))) return ['ok' => false, 'error' => 'Device data is for another website.'];
        $flags = ord($auth[32]);
        if (!($flags & 0x01)) return ['ok' => false, 'error' => 'No user presence.'];
        if (!($flags & 0x04)) return ['ok' => false, 'error' => 'Fingerprint / face unlock was not confirmed.'];

        $ok = openssl_verify($auth . hash('sha256', $clientData, true), self::b64uDecode($sigB64), $pem, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) return ['ok' => false, 'error' => 'Fingerprint check failed.'];

        $count = unpack('N', substr($auth, 33, 4))[1];
        // Counters of 0 mean "not supported" (common on phones). Otherwise it must go up - a copied key would not.
        if ($count > 0 && $storedCount > 0 && $count <= $storedCount) return ['ok' => false, 'error' => 'This passkey looks cloned. Contact the manager.'];

        return ['ok' => true, 'sign_count' => $count];
    }
}
