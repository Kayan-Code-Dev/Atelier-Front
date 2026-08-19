<?php

declare(strict_types=1);

/**
 * AOS platform configuration (Foundation → AI Tenant Integration).
 */
return [
    'name' => env('AOS_PLATFORM_NAME', 'DressnMore Agent Operating System'),

    'version' => env('AOS_VERSION', '1.0.0-smart-assistant'),

    'environment' => env('AOS_ENV', env('APP_ENV', 'production')),

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
        'business_tools' => true,
        'tool_registry' => true,
        'tenant_ai' => true,
        'conversations' => true,
        'planner' => true,
        'prompts' => true,
        'memory' => true,
        'knowledge' => true,
        'channels_whatsapp' => false,
        'ai_providers' => true,
        'communication_hub' => true,
        'workflow_automation' => true,
        'customer_domain_binding' => true,
        'reservation_domain_binding' => true,
        'ai_platform_integration' => true,
        'response_engine' => true,
        'smart_assistant' => true,
    ],

    'boot' => [
        'fail_on_unhealthy' => (bool) env('AOS_BOOT_FAIL_ON_UNHEALTHY', false),
    ],
];
