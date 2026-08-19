<?php

declare(strict_types=1);

/**
 * AOS platform configuration (Foundation → AI Tenant Integration).
 *
 * Channels remain out of scope for product enablement.
 */
return [
    'name' => env('AOS_PLATFORM_NAME', 'DressnMore Agent Operating System'),

    'version' => env('AOS_VERSION', '1.0.0-smart-assistant-product'),

    'environment' => env('AOS_ENV', env('APP_ENV', 'production')),

    /*
    |--------------------------------------------------------------------------
    | Enabled modules
    |--------------------------------------------------------------------------
    | Keys must match ModuleInterface::name() registrations.
    */
    'enabled_modules' => [
        'aos.core' => true,
        'aos.events' => true,
        'aos.observability' => true,
        'aos.conversation' => true,
        'aos.tools' => true,
        'aos.tool-registry' => true,
        'aos.tenant-ai' => true,
        'aos.permissions' => true,
        'aos.planner' => true,
        'aos.response' => true,
        'aos.prompts' => true,
        'aos.memory' => true,
        'aos.knowledge' => true,
        'aos.ai' => true,
        'aos.communication' => true,
        'aos.workflow' => true,
        'dressnmore.customer.binding' => true,
        'dressnmore.reservation.binding' => true,
        'platform.ai-integration' => true,
        'smart.assistant' => true,
        'platform.smart-assistant' => true,
    ],

    'logging' => [
        'channel' => env('AOS_LOG_CHANNEL', env('LOG_CHANNEL', 'stack')),
        'context_key' => 'aos',
    ],

    'tracing' => [
        'enabled' => (bool) env('AOS_TRACING_ENABLED', false),
    ],

    'metrics' => [
        'enabled' => (bool) env('AOS_METRICS_ENABLED', false),
    ],

    'health' => [
        'enabled' => (bool) env('AOS_HEALTH_ENABLED', true),
        'include_module_status' => true,
    ],

    'feature_flags' => [
        // Sprint 12: Workflow engine on (in-memory execution model).
        'business_tools' => true,
        'tool_registry' => true,
        'tenant_ai' => true,
        'conversations' => true,
        'planner' => true,
        'prompts' => true,
        'memory' => true,
        'knowledge' => true,
        'channels_whatsapp' => true,
        'channels_facebook' => true,
        'channels_instagram' => true,
        'ai_providers' => true,
        'communication_hub' => true,
        'workflow_automation' => true,
        'customer_domain_binding' => true,
        'reservation_domain_binding' => true,
        'ai_platform_integration' => true,
        'response_engine' => true,
        'smart_assistant' => true,
        'smart_assistant_product' => true,
    ],

    'boot' => [
        'fail_on_unhealthy' => (bool) env('AOS_BOOT_FAIL_ON_UNHEALTHY', false),
    ],
];
