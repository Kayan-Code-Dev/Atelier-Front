<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Discovery;

use DressnMore\Aos\Tools\Domain\Registry\ToolRegistryInterface;
use DressnMore\Aos\Tools\Domain\Tool\ToolCategoryCode;
use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;
use DressnMore\Aos\Tools\Domain\Tool\ToolOperatingMode;

/**
 * Discovery façade — new tools appear via registry registration without Gateway changes.
 */
final class ToolDiscovery
{
    public function __construct(
        private readonly ToolRegistryInterface $registry,
    ) {}

    public function exists(ToolIdentifier $identifier): bool
    {
        return $this->registry->has($identifier);
    }

    public function find(ToolIdentifier $identifier): ?ToolManifest
    {
        return $this->registry->getManifest($identifier);
    }

    /**
     * @return list<ToolManifest>
     */
    public function all(): array
    {
        return $this->registry->allManifests();
    }

    /**
     * @return list<ToolManifest>
     */
    public function byCategory(ToolCategoryCode $category): array
    {
        return $this->registry->discoverByCategory($category);
    }

    /**
     * @return list<ToolManifest>
     */
    public function byCapability(string $capability): array
    {
        return $this->registry->discoverByCapability($capability);
    }

    /**
     * @return list<ToolManifest>
     */
    public function availableForMode(ToolOperatingMode $mode): array
    {
        return array_values(array_filter(
            $this->registry->allManifests(),
            static fn (ToolManifest $m): bool => $m->supportsMode($mode)
        ));
    }
}
