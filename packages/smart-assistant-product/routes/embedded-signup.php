<?php

declare(strict_types=1);

use DressnMore\SmartAssistantProduct\Http\Controllers\EmbeddedSignupCallbackController;
use Illuminate\Support\Facades\Route;

/*
| Public Embedded Signup browser callback (NOT the messages webhook).
| Path: /api/smart-assistant/whatsapp/embedded-signup/callback
*/
Route::get('/smart-assistant/whatsapp/embedded-signup/callback', EmbeddedSignupCallbackController::class);
