<?php

declare(strict_types=1);

use DressnMore\SmartAssistantProduct\Http\Controllers\ChannelController;
use DressnMore\SmartAssistantProduct\Http\Controllers\CommentController;
use DressnMore\SmartAssistantProduct\Http\Controllers\EmbeddedSignupController;
use DressnMore\SmartAssistantProduct\Http\Controllers\MessageController;
use DressnMore\SmartAssistantProduct\Http\Controllers\SmartAssistantDashboardController;
use DressnMore\SmartAssistantProduct\Http\Controllers\WhatsAppSessionController;
use Illuminate\Support\Facades\Route;

/*
| Tenant Smart Assistant product surface (Sprint 22).
| Mounted under /api/tenant with tenant auth middleware already applied.
*/

Route::prefix('smart-assistant')->middleware(['smart-assistant.feature'])->group(function (): void {
    $anyAccess = 'tenant.permission:smart_assistant.access|smart_assistant.channels|smart_assistant.messages|smart_assistant.comments|smart_assistant.automations|smart_assistant.settings';

    Route::get('/', [SmartAssistantDashboardController::class, 'index'])
        ->middleware($anyAccess);
    Route::get('/navigation', [SmartAssistantDashboardController::class, 'navigation'])
        ->middleware($anyAccess);
    Route::get('/settings', [SmartAssistantDashboardController::class, 'settings'])
        ->middleware('tenant.permission:smart_assistant.settings');
    Route::put('/settings', [SmartAssistantDashboardController::class, 'updateSettings'])
        ->middleware('tenant.permission:smart_assistant.settings');
    Route::patch('/settings', [SmartAssistantDashboardController::class, 'updateSettings'])
        ->middleware('tenant.permission:smart_assistant.settings');
    Route::get('/channels', [ChannelController::class, 'index'])
        ->middleware($anyAccess);
    Route::post('/channels/{channel}/connect', [ChannelController::class, 'connect'])
        ->middleware('tenant.permission:smart_assistant.channels')
        ->where('channel', 'whatsapp|facebook|instagram');
    Route::post('/channels/{channel}/disconnect', [ChannelController::class, 'disconnect'])
        ->middleware('tenant.permission:smart_assistant.channels')
        ->where('channel', 'whatsapp|facebook|instagram');

    Route::get('/whatsapp/embedded-signup', [EmbeddedSignupController::class, 'onboardUrl'])
        ->middleware('tenant.permission:smart_assistant.channels');
    Route::post('/whatsapp/embedded-signup/complete', [EmbeddedSignupController::class, 'complete'])
        ->middleware('tenant.permission:smart_assistant.channels');

    Route::post('/whatsapp/session', [WhatsAppSessionController::class, 'connect'])
        ->middleware($anyAccess);
    Route::get('/whatsapp/session/qr', [WhatsAppSessionController::class, 'qr'])
        ->middleware($anyAccess);
    Route::get('/whatsapp/session/status', [WhatsAppSessionController::class, 'status'])
        ->middleware($anyAccess);
    Route::post('/whatsapp/session/send', [WhatsAppSessionController::class, 'send'])
        ->middleware($anyAccess);

    Route::get('/whatsapp/numbers', [\DressnMore\SmartAssistantProduct\Http\Controllers\WhatsAppNumberController::class, 'index'])
        ->middleware($anyAccess);
    Route::get('/whatsapp/departments', [\DressnMore\SmartAssistantProduct\Http\Controllers\WhatsAppNumberController::class, 'departments'])
        ->middleware($anyAccess);
    Route::post('/whatsapp/numbers', [\DressnMore\SmartAssistantProduct\Http\Controllers\WhatsAppNumberController::class, 'store'])
        ->middleware($anyAccess);
    Route::put('/whatsapp/numbers/{number}', [\DressnMore\SmartAssistantProduct\Http\Controllers\WhatsAppNumberController::class, 'update'])
        ->middleware($anyAccess)
        ->whereNumber('number');
    Route::patch('/whatsapp/numbers/{number}', [\DressnMore\SmartAssistantProduct\Http\Controllers\WhatsAppNumberController::class, 'update'])
        ->middleware($anyAccess)
        ->whereNumber('number');
    Route::post('/whatsapp/numbers/{number}/session', [\DressnMore\SmartAssistantProduct\Http\Controllers\WhatsAppNumberController::class, 'connect'])
        ->middleware($anyAccess)
        ->whereNumber('number');
    Route::get('/whatsapp/numbers/{number}/qr', [\DressnMore\SmartAssistantProduct\Http\Controllers\WhatsAppNumberController::class, 'qr'])
        ->middleware($anyAccess)
        ->whereNumber('number');
    Route::get('/whatsapp/numbers/{number}/status', [\DressnMore\SmartAssistantProduct\Http\Controllers\WhatsAppNumberController::class, 'status'])
        ->middleware($anyAccess)
        ->whereNumber('number');
    Route::post('/whatsapp/numbers/{number}/disconnect', [\DressnMore\SmartAssistantProduct\Http\Controllers\WhatsAppNumberController::class, 'disconnect'])
        ->middleware($anyAccess)
        ->whereNumber('number');

    Route::get('/agent-settings', [\DressnMore\SmartAssistantProduct\Http\Controllers\AgentSettingsController::class, 'show'])
        ->middleware('tenant.permission:smart_assistant.settings');
    Route::put('/agent-settings', [\DressnMore\SmartAssistantProduct\Http\Controllers\AgentSettingsController::class, 'update'])
        ->middleware('tenant.permission:smart_assistant.settings');
    Route::patch('/agent-settings', [\DressnMore\SmartAssistantProduct\Http\Controllers\AgentSettingsController::class, 'update'])
        ->middleware('tenant.permission:smart_assistant.settings');
    Route::post('/preview', [\DressnMore\SmartAssistantProduct\Http\Controllers\AgentSettingsController::class, 'preview'])
        ->middleware('tenant.permission:smart_assistant.settings');
    Route::post('/preview/reset', [\DressnMore\SmartAssistantProduct\Http\Controllers\AgentSettingsController::class, 'resetPreview'])
        ->middleware('tenant.permission:smart_assistant.settings');

    Route::get('/usage', [\DressnMore\SmartAssistantProduct\Http\Controllers\AssistantUsageController::class, 'show'])
        ->middleware($anyAccess);
    Route::get('/usage/history', [\DressnMore\SmartAssistantProduct\Http\Controllers\AssistantUsageController::class, 'history'])
        ->middleware($anyAccess);

    Route::get('/messages', [MessageController::class, 'index'])
        ->middleware('tenant.permission:smart_assistant.messages');
    Route::post('/messages/reply', [MessageController::class, 'reply'])
        ->middleware('tenant.permission:smart_assistant.messages');
    Route::post('/messages/ingest', [MessageController::class, 'ingest'])
        ->middleware('tenant.permission:smart_assistant.messages');

    Route::get('/comments', [CommentController::class, 'index'])
        ->middleware('tenant.permission:smart_assistant.comments');
    Route::post('/comments/reply', [CommentController::class, 'reply'])
        ->middleware('tenant.permission:smart_assistant.comments');
    Route::post('/comments/ingest', [CommentController::class, 'ingest'])
        ->middleware('tenant.permission:smart_assistant.comments');
});
