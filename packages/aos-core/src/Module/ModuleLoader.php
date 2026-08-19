<?php

declare(strict_types=1);

namespace DressnMore\Aos\Core\Module;

use DressnMore\Aos\Core\Configuration\Contracts\ConfigurationProviderInterface;
use DressnMore\Aos\Core\Module\Contracts\ModuleInterface;
use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;

/**
 * Loads and boots modules that are enabled in configuration.
 */
final class ModuleLoader
{
    public function __construct(
        private readonly ModuleRegistryInterface $registry,
        private readonly ConfigurationProviderInterface $config,
    ) {}

    /**
     * Register enabled modules into the application.
     */
    public function registerEnabled(): void
    {
        foreach ($this->registry->all() as $name => $module) {
            if (! $this->config->isModuleEnabled($name)) {
                continue;
            }

            $module->register();
        }
    }

    /**
     * Boot enabled modules.
     */
    public function bootEnabled(): void
    {
        foreach ($this->registry->all() as $name => $module) {
            if (! $this->config->isModuleEnabled($name)) {
                continue;
            }

            $module->boot();
        }
    }

    /**
     * @return list<ModuleInterface>
     */
    public function enabledModules(): array
    {
        $out = [];
        foreach ($this->registry->all() as $name => $module) {
            if ($this->config->isModuleEnabled($name)) {
                $out[] = $module;
            }
        }

        return $out;
    }
}
