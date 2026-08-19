<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Http\Controllers;

use DressnMore\SmartAssistantProduct\Application\WhatsAppEmbeddedSignupService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Public browser redirect target after Meta-hosted Embedded Signup.
 * Must NOT be the WhatsApp messages webhook URL.
 */
final class EmbeddedSignupCallbackController
{
    public function __construct(
        private readonly WhatsAppEmbeddedSignupService $embeddedSignup,
    ) {}

    public function __invoke(Request $request): Response
    {
        $info = $this->embeddedSignup->onboardInfo();
        $returnUrl = $info['frontend_return_url'];

        Log::info('whatsapp.embedded_signup.redirect', [
            'query' => $request->query(),
        ]);

        // Preserve useful Meta query params for the tenant FE (if present).
        $params = array_filter([
            'wa' => 'embedded_done',
            'phone_number_id' => $request->query('phone_number_id'),
            'waba_id' => $request->query('waba_id'),
            'code' => $request->query('code'),
        ], static fn ($v) => $v !== null && $v !== '');

        $target = $returnUrl.(str_contains($returnUrl, '?') ? '&' : '?').http_build_query($params);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>تم ربط واتساب</title>
  <style>
    body{font-family:Tahoma,Arial,sans-serif;background:#f5f7fb;margin:0;display:flex;min-height:100vh;align-items:center;justify-content:center}
    .card{background:#fff;border-radius:16px;padding:28px;max-width:520px;box-shadow:0 8px 30px rgba(12,26,62,.08);text-align:center}
    h1{color:#0C1A3E;font-size:22px;margin:0 0 10px}
    p{color:#475569;line-height:1.7;margin:0 0 18px}
    a{display:inline-block;background:linear-gradient(135deg,#0C1A3E,#1E3A7B);color:#fff;text-decoration:none;padding:12px 18px;border-radius:10px;font-weight:700}
  </style>
  <meta http-equiv="refresh" content="2;url="{$target}" />
</head>
<body>
  <div class="card">
    <h1>تم إكمال خطوة Meta</h1>
    <p>سيتم إرجاعك الآن إلى المساعد الذكي في DressnMore لإتمام الربط تلقائياً إن أمكن.</p>
    <a href="{$target}">العودة للمساعد الذكي</a>
  </div>
</body>
</html>
HTML;

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
