<?php

declare(strict_types=1);

namespace DressnMore\Aos\Core\Configuration\Contracts;

/**
 * Typed access to AOS foundation configuration.
 */
interface ConfigurationProviderInterface
{
    public function platformName(): string;

    public function version(): string;

    public function environment(): string;

    /**
     * @return array<string, bool>
     */
    public function enabledModules(): array;

    public function isModuleEnabled(string $moduleName): bool;

    public function isFeatureEnabled(string $feature): bool;

    /**
     * @return array<string, mixed>
     */
    public function all(): array;

    public function get(string $key, mixed $default = null): mixed;
}
