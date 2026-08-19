<?php

declare(strict_types=1);

namespace DressnMore\Aos\Core\Configuration;

use DressnMore\Aos\Core\Configuration\Contracts\ConfigurationProviderInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Reads foundation settings from config/aos.php.
 */
final class AosConfigurationProvider implements ConfigurationProviderInterface
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function platformName(): string
    {
        return (string) $this->config->get('aos.name', 'DressnMore Agent Operating System');
    }

    public function version(): string
    {
        return (string) $this->config->get('aos.version', '0.1.0-foundation');
    }

    public function environment(): string
    {
        return (string) $this->config->get('aos.environment', 'production');
    }

    public function enabledModules(): array
    {
        /** @var array<string, bool> $modules */
        $modules = $this->config->get('aos.enabled_modules', []);

        return $modules;
    }

    public function isModuleEnabled(string $moduleName): bool
    {
        return (bool) ($this->enabledModules()[$moduleName] ?? false);
    }

    public function isFeatureEnabled(string $feature): bool
    {
        return (bool) $this->config->get('aos.feature_flags.'.$feature, false);
    }

    public function all(): array
    {
        /** @var array<string, mixed> $all */
        $all = $this->config->get('aos', []);

        return $all;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config->get('aos.'.$key, $default);
    }
}
