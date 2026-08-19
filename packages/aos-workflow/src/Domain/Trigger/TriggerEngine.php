<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Trigger;

final class TriggerEngine
{
    /**
     * @param array<string,mixed> $payload
     */
    public function resolve(array $payload): TriggerType
    {
        $value = (string) ($payload['trigger'] ?? TriggerType::ManualTrigger->value);

        return TriggerType::from($value);
    }
}
