<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Module;

use DressnMore\Aos\Core\Module\AbstractModule;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityRegistryInterface;

final class PermissionsModule extends AbstractModule
{
    public function __construct(
        private readonly CapabilityRegistryInterface $capabilityRegistry,
    ) {}

    public function name(): string
    {
        return $this->assertName('aos.permissions');
    }

    public function title(): string
    {
        return 'AOS Permission & Policy Engine';
    }

    public function version(): string
    {
        return '0.5.0';
    }

    public function isHealthy(): bool
    {
        return $this->capabilityRegistry instanceof CapabilityRegistryInterface;
    }
}
