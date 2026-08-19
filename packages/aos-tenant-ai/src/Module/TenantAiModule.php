<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Module;

use DressnMore\Aos\Core\Module\AbstractModule;
use DressnMore\Aos\TenantAi\Domain\Dashboard\AiDashboardMenu;

final class TenantAiModule extends AbstractModule
{
    public function name(): string
    {
        return $this->assertName('aos.tenant-ai');
    }

    public function title(): string
    {
        return 'AOS AI Tenant Integration Platform';
    }

    public function version(): string
    {
        return '0.17.0';
    }

    public function isHealthy(): bool
    {
        return count(AiDashboardMenu::items()) === 6;
    }
}
