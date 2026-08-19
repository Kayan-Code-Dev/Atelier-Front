<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Domain\Dashboard;

/**
 * Conceptual AI Assistant dashboard IA for DressnMore control panel.
 */
final class AiDashboardMenu
{
    /**
     * @return list<array{key:string,label:string,path:string}>
     */
    public static function items(): array
    {
        return [
            ['key' => 'chat', 'label' => 'Chat', 'path' => '/ai-assistant/chat'],
            ['key' => 'history', 'label' => 'History', 'path' => '/ai-assistant/history'],
            ['key' => 'settings', 'label' => 'Settings', 'path' => '/ai-assistant/settings'],
            ['key' => 'memory', 'label' => 'Memory', 'path' => '/ai-assistant/memory'],
            ['key' => 'integrations', 'label' => 'Integrations', 'path' => '/ai-assistant/integrations'],
            ['key' => 'usage', 'label' => 'Usage', 'path' => '/ai-assistant/usage'],
        ];
    }
}
