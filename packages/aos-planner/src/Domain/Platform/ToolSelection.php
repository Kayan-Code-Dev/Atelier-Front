<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Platform;

final class ToolSelection
{
    /**
     * @param list<string> $selectedTools
     * @param list<PlanStep> $orderedSteps
     * @param list<string> $missingTools
     * @param list<string> $blockedTools
     */
    public function __construct(
        private readonly array $selectedTools,
        private readonly array $orderedSteps,
        private readonly array $missingTools = [],
        private readonly array $blockedTools = [],
    ) {}

    /** @return list<string> */
    public function selectedTools(): array { return $this->selectedTools; }
    /** @return list<PlanStep> */
    public function orderedSteps(): array { return $this->orderedSteps; }
    /** @return list<string> */
    public function missingTools(): array { return $this->missingTools; }
    /** @return list<string> */
    public function blockedTools(): array { return $this->blockedTools; }
    public function ok(): bool { return $this->missingTools === [] && $this->selectedTools !== []; }
}
