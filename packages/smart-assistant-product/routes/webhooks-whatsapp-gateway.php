<?php

declare(strict_types=1);

use DressnMore\SmartAssistantProduct\Http\Controllers\WhatsAppGatewayWebhookController;
use Illuminate\Support\Facades\Route;

/*
| Internal WhatsApp Gateway callback (QR pairing path — NOT Meta).
| Path: /api/webhooks/whatsapp-gateway
| Authenticated by shared secret (X-Gateway-Secret header).
*/
Route::post('/webhooks/whatsapp-gateway', WhatsAppGatewayWebhookController::class);
