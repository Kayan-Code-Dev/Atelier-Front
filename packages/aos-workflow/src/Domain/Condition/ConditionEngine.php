<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Condition;

final class ConditionEngine
{
    /**
     * @param array<string, mixed> $context
     * @param array<string, scalar|array|null> $conditions
     */
    public function evaluate(array $context, array $conditions): bool
    {
        foreach ($conditions as $key => $expected) {
            if (! array_key_exists($key, $context)) {
                return false;
            }
            if ($expected !== null && $context[$key] !== $expected) {
                return false;
            }
        }

        return true;
    }
}
