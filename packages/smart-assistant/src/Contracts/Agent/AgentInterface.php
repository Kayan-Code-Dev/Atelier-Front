<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Agent;

use DressnMore\SmartAssistant\Domain\Agent\Agent;

interface AgentInterface
{
    public function identity(): Agent;

    /**
     * @return list<string>
     */
    public function capabilities(): array;
}
