<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Core;

use DressnMore\SmartAssistant\Domain\Core\Assistant;

interface AssistantManagerInterface
{
    public function initialize(string $tenantId, string $name): Assistant;

    public function find(string $tenantId, string $assistantId): ?Assistant;
}
