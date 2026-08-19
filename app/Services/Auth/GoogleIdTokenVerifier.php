<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GoogleIdTokenVerifier
{
    public function __construct(
        private readonly ?string $clientId = null,
    ) {}

    /**
     * @return array{email: string, sub: string, name: ?string, picture: ?string, email_verified: bool}
     */
    public function verify(string $idToken): array
    {
        $clientId = trim((string) ($this->clientId ?? config('services.google.client_id')));
        if ($clientId === '') {
            throw ValidationException::withMessages([
                'id_token' => ['تسجيل الدخول عبر Google غير مفعّل حالياً.'],
            ]);
        }

        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw ValidationException::withMessages([
                'id_token' => ['رمز Google غير صالح.'],
            ]);
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;
        $header = $this->decodeJson($headerB64);
        $payload = $this->decodeJson($payloadB64);

        if (($header['alg'] ?? null) !== 'RS256' || empty($header['kid'])) {
            throw ValidationException::withMessages([
                'id_token' => ['رمز Google غير مدعوم.'],
            ]);
        }

        $publicKey = $this->publicKeyForKid((string) $header['kid']);
        $signed = $headerB64.'.'.$payloadB64;
        $signature = $this->base64UrlDecode($signatureB64);

        $ok = openssl_verify($signed, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) {
            throw ValidationException::withMessages([
                'id_token' => ['فشل التحقق من توقيع Google.'],
            ]);
        }

        $iss = (string) ($payload['iss'] ?? '');
        if (! in_array($iss, ['accounts.google.com', 'https://accounts.google.com'], true)) {
            throw ValidationException::withMessages([
                'id_token' => ['مصدر رمز Google غير موثوق.'],
            ]);
        }

        $aud = $payload['aud'] ?? null;
        $audList = is_array($aud) ? $aud : [$aud];
        if (! in_array($clientId, $audList, true)) {
            throw ValidationException::withMessages([
                'id_token' => ['رمز Google لا يطابق تطبيق DressnMore.'],
            ]);
        }

        $exp = (int) ($payload['exp'] ?? 0);
        if ($exp < time()) {
            throw ValidationException::withMessages([
                'id_token' => ['انتهت صلاحية رمز Google.'],
            ]);
        }

        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        if ($email === '') {
            throw ValidationException::withMessages([
                'id_token' => ['حساب Google لا يحتوي على بريد إلكتروني.'],
            ]);
        }

        $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if (! $emailVerified) {
            throw ValidationException::withMessages([
                'id_token' => ['بريد Google غير موثّق.'],
            ]);
        }

        return [
            'email' => $email,
            'sub' => (string) ($payload['sub'] ?? ''),
            'name' => isset($payload['name']) ? (string) $payload['name'] : null,
            'picture' => isset($payload['picture']) ? (string) $payload['picture'] : null,
            'email_verified' => true,
        ];
    }

    private function publicKeyForKid(string $kid): \OpenSSLAsymmetricKey
    {
        $jwks = Cache::remember('google_oauth_jwks_v3', 3600, function (): array {
            $response = Http::timeout(10)->get('https://www.googleapis.com/oauth2/v3/certs');
            if (! $response->successful()) {
                throw new RuntimeException('Unable to fetch Google JWKS.');
            }

            $json = $response->json();
            if (! is_array($json) || ! isset($json['keys']) || ! is_array($json['keys'])) {
                throw new RuntimeException('Invalid Google JWKS payload.');
            }

            return $json;
        });

        foreach ($jwks['keys'] as $key) {
            if (! is_array($key) || ($key['kid'] ?? null) !== $kid) {
                continue;
            }

            $pem = $this->jwkToPem($key);
            $publicKey = openssl_pkey_get_public($pem);
            if ($publicKey === false) {
                throw ValidationException::withMessages([
                    'id_token' => ['تعذر قراءة مفتاح Google العام.'],
                ]);
            }

            return $publicKey;
        }

        Cache::forget('google_oauth_jwks_v3');

        throw ValidationException::withMessages([
            'id_token' => ['مفتاح توقيع Google غير موجود.'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $jwk
     */
    private function jwkToPem(array $jwk): string
    {
        $n = $this->base64UrlDecode((string) ($jwk['n'] ?? ''));
        $e = $this->base64UrlDecode((string) ($jwk['e'] ?? ''));
        if ($n === '' || $e === '') {
            throw new RuntimeException('Incomplete Google JWK.');
        }

        // ASN.1 INTEGERs must be positive — prefix 0x00 when high bit is set.
        if ((ord($n[0]) & 0x80) !== 0) {
            $n = "\x00".$n;
        }
        if ((ord($e[0]) & 0x80) !== 0) {
            $e = "\x00".$e;
        }

        $modulus = $this->encodeLength($n);
        $exponent = $this->encodeLength($e);
        $rsaPublicKey = chr(0x30).$this->encodeLength(
            chr(0x02).$modulus.
            chr(0x02).$exponent
        );

        $algorithmIdentifier = pack('H*', '300d06092a864886f70d0101010500');
        $bitString = chr(0x03).$this->encodeLength(chr(0x00).$rsaPublicKey);
        $sequence = chr(0x30).$this->encodeLength($algorithmIdentifier.$bitString);

        return "-----BEGIN PUBLIC KEY-----\n".
            chunk_split(base64_encode($sequence), 64, "\n").
            "-----END PUBLIC KEY-----\n";
    }

    private function encodeLength(string $data): string
    {
        $length = strlen($data);
        if ($length < 0x80) {
            return chr($length).$data;
        }

        $temp = ltrim(pack('N', $length), "\x00");

        return chr(0x80 | strlen($temp)).$temp.$data;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $b64): array
    {
        $json = $this->base64UrlDecode($b64);
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'id_token' => ['رمز Google تالف.'],
            ]);
        }

        return $decoded;
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if ($decoded === false) {
            throw ValidationException::withMessages([
                'id_token' => ['رمز Google تالف.'],
            ]);
        }

        return $decoded;
    }
}
