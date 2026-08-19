<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Module;

use DressnMore\Aos\Core\Module\AbstractModule;

final class SmartAssistantProductModule extends AbstractModule
{
    public function name(): string
    {
        return $this->assertName('platform.smart-assistant');
    }

    public function title(): string
    {
        return (string) config('smart-assistant-product.display_name', 'Smart Assistant');
    }

    public function version(): string
    {
        return (string) config('smart-assistant-product.version', '0.25.0');
    }

    public function icon(): string
    {
        return (string) config('smart-assistant-product.icon', 'bot');
    }

    public function category(): string
    {
        return (string) config('smart-assistant-product.category', 'automation');
    }

    public function isEnabled(): bool
    {
        $modules = config('aos.enabled_modules', []);
        $moduleOn = is_array($modules)
            ? (bool) ($modules['platform.smart-assistant'] ?? true)
            : true;

        return $moduleOn
            && (bool) config('aos.feature_flags.smart_assistant_product', true)
            && (bool) config('smart-assistant-product.enabled_globally', true);
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
            'display_name_ar' => config('smart-assistant-product.display_name_ar'),
            'icon' => $this->icon(),
            'category' => $this->category(),
            'version' => $this->version(),
            'enabled' => $this->isEnabled(),
            'channels' => array_keys((array) config('smart-assistant-product.channels', [])),
        ];
    }
}
