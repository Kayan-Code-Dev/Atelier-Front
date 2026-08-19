<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Goal;

use DressnMore\Aos\Planner\Domain\Intent\IntentCode;

/**
 * Planning goal derived from one or more intents.
 */
final class PlanningGoal
{
    /**
     * @param  list<IntentCode>  $sourceIntents
     * @param  list<string>  $toolCandidates  ToolIdentifiers only — never executed here
     */
    public function __construct(
        private readonly GoalCode $code,
        private readonly string $description,
        private readonly array $sourceIntents,
        private readonly array $toolCandidates = [],
        private readonly int $priority = 100,
        private readonly bool $isWrite = false,
    ) {}

    public function code(): GoalCode
    {
        return $this->code;
    }

    public function description(): string
    {
        return $this->description;
    }

    /**
     * @return list<IntentCode>
     */
    public function sourceIntents(): array
    {
        return $this->sourceIntents;
    }

    /**
     * @return list<string>
     */
    public function toolCandidates(): array
    {
        return $this->toolCandidates;
    }

    public function priority(): int
    {
        return $this->priority;
    }

    public function isWrite(): bool
    {
        return $this->isWrite;
    }
}
