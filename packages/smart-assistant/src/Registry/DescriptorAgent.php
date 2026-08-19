<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Registry;

use DressnMore\SmartAssistant\Contracts\Agent\AgentInterface;
use DressnMore\SmartAssistant\Domain\Agent\Agent;

/** Descriptor stub for registry validation — not an executable agent. */
final class DescriptorAgent implements AgentInterface
{
    public function __construct(private readonly Agent $agent) {}

    public function identity(): Agent
    {
        return $this->agent;
    }

    public function capabilities(): array
    {
        return $this->agent->capabilityIds();
    }
}
