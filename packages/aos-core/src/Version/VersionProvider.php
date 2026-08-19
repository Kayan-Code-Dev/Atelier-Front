<?php

declare(strict_types=1);

namespace DressnMore\Aos\Core\Version;

use DressnMore\Aos\Core\Configuration\Contracts\ConfigurationProviderInterface;
use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Core\Version\Contracts\VersionProviderInterface;

/**
 * Exposes platform and registered module versions.
 */
final class VersionProvider implements VersionProviderInterface
{
    public function __construct(
        private readonly ConfigurationProviderInterface $config,
        private readonly ModuleRegistryInterface $registry,
    ) {}

    public function platformVersion(): string
    {
        return $this->config->version();
    }

    public function moduleVersions(): array
    {
        $versions = [];
        foreach ($this->registry->all() as $name => $module) {
            $versions[$name] = $module->version();
        }

        return $versions;
    }
}
