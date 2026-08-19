<?php

declare(strict_types=1);

namespace DressnMore\Platform\Module;

use DressnMore\Aos\Core\Module\AbstractModule;

final class AiIntegrationModule extends AbstractModule
{
    public function name(): string
    {
        return $this->assertName('platform.ai-integration');
    }

    public function title(): string
    {
        return (string) config('dressnmore-platform.ai.display_name', 'AI Assistant');
    }

    public function version(): string
    {
        return '0.18.5';
    }

    public function icon(): string
    {
        return (string) config('dressnmore-platform.ai.icon', 'sparkles');
    }

    public function category(): string
    {
        return (string) config('dressnmore-platform.ai.category', 'intelligence');
    }

    public function isEnabled(): bool
    {
        $modules = config('aos.enabled_modules', []);
        $moduleOn = is_array($modules)
            ? (bool) ($modules['platform.ai-integration'] ?? true)
            : true;

        return $moduleOn && (bool) config('dressnmore-platform.ai.enabled_globally', true);
    }

    public function isHealthy(): bool
    {
        return $this->isEnabled();
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return [
            'key' => $this->name(),
            'display_name' => $this->title(),
            'display_name_ar' => config('dressnmore-platform.ai.display_name_ar'),
            'icon' => $this->icon(),
            'category' => $this->category(),
            'version' => $this->version(),
            'enabled' => $this->isEnabled(),
        ];
    }
}
