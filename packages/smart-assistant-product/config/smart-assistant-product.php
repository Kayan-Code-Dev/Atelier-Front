<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Smart Assistant — المساعد الذكي (Sprint 22)
    |--------------------------------------------------------------------------
    | Multi-channel social automation product surface.
    | Architecture foundation lives in dressnmore/smart-assistant (frozen).
    | This package activates WhatsApp / Facebook / Instagram as a sellable module.
    */
    'module_key' => 'platform.smart-assistant',
    'display_name' => 'Smart Assistant',
    'display_name_ar' => 'المساعد الذكي',
    'icon' => 'bot',
    'category' => 'automation',
    'version' => '0.25.0',

    'enabled_globally' => env('DRESSNMORE_SMART_ASSISTANT_ENABLED', true),

    'plan_feature' => 'smart_assistant.enabled',
    'plan_feature_auto_reply' => 'smart_assistant.auto_reply',

    'tenant_disabled' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('DRESSNMORE_SMART_ASSISTANT_TENANT_DISABLED', ''))
    ))),

    'routes_prefix' => 'smart-assistant',

    'channels' => [
        'whatsapp' => [
            'enabled' => (bool) env('SMART_ASSISTANT_WHATSAPP', true),
            'label' => 'WhatsApp',
            'label_ar' => 'واتساب',
            'supports' => ['messages', 'media'],
        ],
        'facebook' => [
            'enabled' => (bool) env('SMART_ASSISTANT_FACEBOOK', true),
            'label' => 'Facebook',
            'label_ar' => 'فيسبوك',
            'supports' => ['messages', 'comments', 'media'],
        ],
        'instagram' => [
            'enabled' => (bool) env('SMART_ASSISTANT_INSTAGRAM', true),
            'label' => 'Instagram',
            'label_ar' => 'إنستغرام',
            'supports' => ['messages', 'comments', 'media'],
        ],
    ],

    'permissions' => [
        'smart_assistant.access',
        'smart_assistant.channels',
        'smart_assistant.messages',
        'smart_assistant.comments',
        'smart_assistant.automations',
        'smart_assistant.settings',
    ],

    'webhook_verify_token' => env('SMART_ASSISTANT_WEBHOOK_VERIFY_TOKEN', 'dressnmore-sa'),

    // Existing prod workers listen on "intelligence"; override via SMART_ASSISTANT_QUEUE.
    'queue' => env('SMART_ASSISTANT_QUEUE', 'intelligence'),

    'whatsapp' => [
        'api_base' => env('META_WHATSAPP_API_BASE', 'https://graph.facebook.com'),
        'graph_version' => env('META_WHATSAPP_GRAPH_VERSION', 'v21.0'),
        'app_secret' => env('META_WHATSAPP_APP_SECRET', ''),
        'require_signature' => (bool) env('META_WHATSAPP_REQUIRE_SIGNATURE', false),
        'timeout' => (int) env('META_WHATSAPP_TIMEOUT', 20),
        'auto_reply_mode' => env('SMART_ASSISTANT_WA_AUTO_REPLY_MODE', 'template'), // template|planner|off
        'auto_reply_template' => env(
            'SMART_ASSISTANT_WA_AUTO_REPLY_TEMPLATE',
            "مرحباً 👋\nتم استلام رسالتك عبر المساعد الذكي لـ DressnMore.\nسنعاود الرد عليك في أقرب وقت."
        ),
        'embedded' => [
            'enabled' => (bool) env('META_WHATSAPP_EMBEDDED_ENABLED', true),
            'app_id' => env('META_WHATSAPP_APP_ID', '1057619943621467'),
            'config_id' => env('META_WHATSAPP_CONFIG_ID', '1405928088119674'),
            'feature_type' => env('META_WHATSAPP_FEATURE_TYPE', 'whatsapp_business_app_onboarding'),
            'redirect_uri' => env(
                'META_WHATSAPP_EMBEDDED_REDIRECT_URI',
                'https://api.dressnmore.it.com/api/smart-assistant/whatsapp/embedded-signup/callback'
            ),
            'frontend_return_url' => env(
                'META_WHATSAPP_FRONTEND_RETURN_URL',
                'https://dressnmore.it.com/smart-assistant'
            ),
            // Full Meta-hosted onboard URL (optional override). Prefer regenerating with correct redirect_uri.
            'onboard_url' => env('META_WHATSAPP_ONBOARD_URL', ''),
        ],
    ],

    'whatsapp_web' => [
        'enabled' => (bool) env('SMART_ASSISTANT_WA_WEB_ENABLED', false),
        'gateway_url' => env('WHATSAPP_GATEWAY_URL', 'http://127.0.0.1:3101'),
        'gateway_secret' => env('WHATSAPP_GATEWAY_SECRET', ''),
        'platform_tenant_id' => env('WHATSAPP_PLATFORM_TENANT_ID', ''),
    ],

    'messenger' => [
        'api_base' => env('META_MESSENGER_API_BASE', env('META_WHATSAPP_API_BASE', 'https://graph.facebook.com')),
        'graph_version' => env('META_MESSENGER_GRAPH_VERSION', env('META_WHATSAPP_GRAPH_VERSION', 'v21.0')),
        'timeout' => (int) env('META_MESSENGER_TIMEOUT', 20),
        'auto_reply_template' => env(
            'SMART_ASSISTANT_MESSENGER_AUTO_REPLY_TEMPLATE',
            "مرحباً 👋\nتم استلام رسالتك عبر المساعد الذكي لـ DressnMore.\nسنعاود الرد عليك في أقرب وقت."
        ),
    ],
];
