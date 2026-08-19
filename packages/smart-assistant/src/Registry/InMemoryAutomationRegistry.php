<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Registry;

use DressnMore\SmartAssistant\Contracts\Automation\AutomationInterface;
use DressnMore\SmartAssistant\Contracts\Registry\AutomationRegistryInterface;

final class InMemoryAutomationRegistry implements AutomationRegistryInterface
{
    /** @var array<string, AutomationInterface> */
    private array $items = [];

    public function register(AutomationInterface $automation): void
    {
        $this->items[$automation->identity()->id()] = $automation;
    }

    public function get(string $automationId): ?AutomationInterface
    {
        return $this->items[$automationId] ?? null;
    }
}
