<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Registry;

use DressnMore\SmartAssistant\Contracts\Automation\AutomationInterface;

interface AutomationRegistryInterface
{
    public function register(AutomationInterface $automation): void;

    public function get(string $automationId): ?AutomationInterface;
}
