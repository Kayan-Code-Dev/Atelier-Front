<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Automation;

use DressnMore\SmartAssistant\Domain\Automation\Automation;

interface AutomationInterface
{
    public function identity(): Automation;

    public function start(): void;

    public function stop(): void;
}
