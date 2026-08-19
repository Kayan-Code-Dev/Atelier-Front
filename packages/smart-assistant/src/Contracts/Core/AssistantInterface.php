<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Core;

use DressnMore\SmartAssistant\Domain\Core\Assistant;

interface AssistantInterface
{
    public function identity(): Assistant;
}
