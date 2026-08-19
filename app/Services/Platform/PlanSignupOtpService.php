<?php

namespace App\Services\Platform;

use App\Services\Mail\PlatformMailService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class PlanSignupOtpService
{
    private const OTP_TTL_SECONDS = 600;

    private const TOKEN_TTL_SECONDS = 1800;

    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly PlatformMailService $platformMailService,
    ) {}

    public function send(string $email, ?string $name = null): array
    {
        $email = $this->normalizeEmail($email);
        $otp = (string) random_int(100000, 999999);

        Cache::put($this->otpKey($email), [
            'hash' => hash('sha256', $otp),
            'attempts' => 0,
        ], self::OTP_TTL_SECONDS);

        $displayName = trim((string) $name);
        $greeting = $displayName !== '' ? $displayName : 'مرحباً';

        $this->platformMailService->sendPlanSignupOtp($email, $greeting, $otp);

        return [
            'email' => $email,
            'expires_in' => self::OTP_TTL_SECONDS,
            'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني.',
        ];
    }

    public function verify(string $email, string $otp): array
    {
        $email = $this->normalizeEmail($email);
        $otp = trim($otp);

        if (! preg_match('/^\d{6}$/', $otp)) {
            throw new RuntimeException('رمز التحقق غير صالح.');
        }

        $payload = Cache::get($this->otpKey($email));
        if (! is_array($payload) || empty($payload['hash'])) {
            throw new RuntimeException('انتهت صلاحية رمز التحقق. أعد الإرسال.');
        }

        $attempts = (int) ($payload['attempts'] ?? 0);
        if ($attempts >= self::MAX_ATTEMPTS) {
            Cache::forget($this->otpKey($email));
            throw new RuntimeException('تم تجاوز عدد المحاولات. أعد إرسال رمز جديد.');
        }

        if (! hash_equals((string) $payload['hash'], hash('sha256', $otp))) {
            $payload['attempts'] = $attempts + 1;
            Cache::put($this->otpKey($email), $payload, self::OTP_TTL_SECONDS);
            throw new RuntimeException('رمز التحقق غير صحيح.');
        }

        Cache::forget($this->otpKey($email));

        $token = Str::random(64);
        Cache::put($this->tokenKey($token), [
            'email' => $email,
            'verified_at' => now()->toIso8601String(),
        ], self::TOKEN_TTL_SECONDS);

        return [
            'email' => $email,
            'email_verification_token' => $token,
            'expires_in' => self::TOKEN_TTL_SECONDS,
            'message' => 'تم التحقق من البريد الإلكتروني بنجاح.',
        ];
    }

    public function assertVerifiedToken(string $email, ?string $token): void
    {
        $email = $this->normalizeEmail($email);
        $token = trim((string) $token);

        if ($token === '') {
            throw new RuntimeException('يجب التحقق من البريد الإلكتروني أولاً.');
        }

        $payload = Cache::get($this->tokenKey($token));
        if (! is_array($payload) || ($payload['email'] ?? null) !== $email) {
            throw new RuntimeException('رمز التحقق من البريد غير صالح أو منتهي. أعد التحقق.');
        }
    }

    public function consumeToken(string $token): void
    {
        Cache::forget($this->tokenKey(trim($token)));
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function otpKey(string $email): string
    {
        return 'plan_signup_otp:'.$email;
    }

    private function tokenKey(string $token): string
    {
        return 'plan_signup_email_token:'.$token;
    }
}
