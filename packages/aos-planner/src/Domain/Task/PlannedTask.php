<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Task;

use DressnMore\Aos\Planner\Domain\Goal\GoalCode;

/**
 * One planned task step (tool candidate selection only — no execution).
 */
final class PlannedTask
{
    /**
     * @param  list<string>  $toolCandidates
     * @param  list<string>  $dependsOnTaskIds
     */
    public function __construct(
        private readonly TaskId $id,
        private readonly GoalCode $goalCode,
        private readonly string $description,
        private readonly array $toolCandidates,
        private readonly array $dependsOnTaskIds = [],
        private readonly bool $isWrite = false,
        private readonly bool $requiresApproval = false,
        private readonly int $order = 0,
    ) {}

    public function id(): TaskId
    {
        return $this->id;
    }

    public function goalCode(): GoalCode
    {
        return $this->goalCode;
    }

    public function description(): string
    {
        return $this->description;
    }

    /**
     * @return list<string>
     */
    public function toolCandidates(): array
    {
        return $this->toolCandidates;
    }

    /**
     * @return list<string>
     */
    public function dependsOnTaskIds(): array
    {
        return $this->dependsOnTaskIds;
    }

    public function isWrite(): bool
    {
        return $this->isWrite;
    }

    public function requiresApproval(): bool
    {
        return $this->requiresApproval;
    }

    public function order(): int
    {
        return $this->order;
    }
}
