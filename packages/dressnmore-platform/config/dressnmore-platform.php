<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | AI Assistant — Feature Flags (Sprint 18A)
    |--------------------------------------------------------------------------
    | Layers: global → package (subscription) → tenant override.
    | Permission checks remain separate (RBAC).
    */
    'ai' => [
        'module_key' => 'platform.ai-integration',
        'display_name' => 'AI Assistant',
        'display_name_ar' => 'المستشار الذكي',
        'icon' => 'sparkles',
        'category' => 'intelligence',
        'version' => '0.18.5',

        'enabled_globally' => env('DRESSNMORE_AI_ENABLED', true),

        /** Plan feature key that must be true for package enablement */
        'plan_feature' => 'ai_assistant.enabled',
        'plan_feature_advanced' => 'ai_assistant.advanced',

        /** Optional per-tenant denylist (tenant ids / slugs) */
        'tenant_disabled' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('DRESSNMORE_AI_TENANT_DISABLED', ''))
        ))),

        'routes_prefix' => 'ai',
    ],

    'permissions' => [
        'ai.access',
        'ai.chat',
        'ai.history',
        'ai.memory',
        'ai.integrations',
        'ai.settings',
        'ai.usage',
    ],
];
