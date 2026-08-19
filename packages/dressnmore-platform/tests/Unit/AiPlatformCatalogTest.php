<?php

declare(strict_types=1);

namespace DressnMore\Platform\Tests\Unit;

use DressnMore\Platform\Domain\AiNavigation;
use DressnMore\Platform\Support\AiPermissionCatalog;
use PHPUnit\Framework\TestCase;

final class AiPlatformCatalogTest extends TestCase
{
    public function test_permissions_cover_sprint_keys(): void
    {
        $keys = AiPermissionCatalog::keys();
        foreach (['ai.access', 'ai.chat', 'ai.history', 'ai.memory', 'ai.integrations', 'ai.settings', 'ai.usage'] as $key) {
            $this->assertContains($key, $keys);
        }
    }

    public function test_navigation_paths_match_tenant_ai_routes(): void
    {
        $paths = array_column(AiNavigation::items(), 'path');
        $this->assertContains('/tenant/ai', $paths);
        $this->assertContains('/tenant/ai/history', $paths);
        $this->assertContains('/tenant/ai/settings', $paths);
        $this->assertContains('/tenant/ai/memory', $paths);
        $this->assertContains('/tenant/ai/integrations', $paths);
        $this->assertContains('/tenant/ai/usage', $paths);
    }

    public function test_navigation_filters_by_permission(): void
    {
        $items = AiNavigation::forPermissions(['ai.chat']);
        $this->assertCount(1, $items);
        $this->assertSame('chat', $items[0]['key']);
    }

    public function test_ai_access_unlocks_all_nav_items(): void
    {
        $items = AiNavigation::forPermissions(['ai.access']);
        $this->assertCount(count(AiNavigation::items()), $items);
    }
}
