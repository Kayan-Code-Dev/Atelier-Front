<?php

declare(strict_types=1);

use DressnMore\SmartAssistantProduct\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
| Public Smart Assistant webhooks (Meta verify + inbound).
| Path: /api/webhooks/smart-assistant/{channel}
*/

Route::prefix('webhooks/smart-assistant')->group(function (): void {
    Route::get('/{channel}', [WebhookController::class, 'verify'])
        ->where('channel', 'whatsapp|facebook|instagram');
    Route::post('/{channel}', [WebhookController::class, 'receive'])
        ->where('channel', 'whatsapp|facebook|instagram');
});
