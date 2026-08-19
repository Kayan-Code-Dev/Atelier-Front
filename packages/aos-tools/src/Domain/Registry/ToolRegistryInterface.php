<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Registry;

use DressnMore\Aos\Tools\Domain\Contracts\BusinessToolHandlerInterface;
use DressnMore\Aos\Tools\Domain\Tool\ToolCategoryCode;
use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;

interface ToolRegistryInterface
{
    public function register(BusinessToolHandlerInterface $handler): void;

    public function unregister(ToolIdentifier $identifier): void;

    public function has(ToolIdentifier $identifier): bool;

    public function get(ToolIdentifier $identifier): ?BusinessToolHandlerInterface;

    public function getManifest(ToolIdentifier $identifier): ?ToolManifest;

    /**
     * @return list<ToolManifest>
     */
    public function allManifests(): array;

    /**
     * @return list<ToolManifest>
     */
    public function discoverByCategory(ToolCategoryCode $category): array;

    /**
     * @return list<ToolManifest>
     */
    public function discoverByCapability(string $capability): array;
}
