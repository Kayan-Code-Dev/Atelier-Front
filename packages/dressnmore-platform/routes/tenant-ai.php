<?php

declare(strict_types=1);

use DressnMore\Platform\Http\Controllers\AiChatController;
use DressnMore\Platform\Http\Controllers\AiDashboardController;
use DressnMore\Platform\Http\Controllers\AiIntegrationController;
use DressnMore\Platform\Http\Controllers\AiMemoryController;
use DressnMore\Platform\Http\Controllers\AiSettingsController;
use DressnMore\Platform\Http\Controllers\AiUsageController;
use Illuminate\Support\Facades\Route;

/*
| Tenant AI Assistant product surface (Sprint 18A).
| Mounted under /api/tenant with tenant auth middleware already applied by the host.
| No Planner / Gateway / LLM execution.
*/

Route::prefix('ai')->middleware(['ai.feature'])->group(function (): void {
    Route::get('/', [AiDashboardController::class, 'index'])
        ->middleware('tenant.permission:ai.access');
    Route::get('/navigation', [AiDashboardController::class, 'navigation'])
        ->middleware('tenant.permission:ai.access');

    Route::get('/chat', [AiChatController::class, 'index'])
        ->middleware('tenant.permission:ai.chat');
    Route::get('/history', [AiChatController::class, 'history'])
        ->middleware('tenant.permission:ai.history');

    Route::get('/settings', [AiSettingsController::class, 'index'])
        ->middleware('tenant.permission:ai.settings');
    Route::get('/memory', [AiMemoryController::class, 'index'])
        ->middleware('tenant.permission:ai.memory');
    Route::get('/integrations', [AiIntegrationController::class, 'index'])
        ->middleware('tenant.permission:ai.integrations');
    Route::get('/usage', [AiUsageController::class, 'index'])
        ->middleware('tenant.permission:ai.usage');
});
