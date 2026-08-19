<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Domain\Aggregator;

final class AggregatedToolResults
{
    /**
     * @param list<ToolOutcome> $outcomes
     * @param list<ToolOutcome> $succeeded
     * @param list<ToolOutcome> $failed
     */
    public function __construct(
        private readonly array $outcomes,
        private readonly array $succeeded,
        private readonly array $failed,
    ) {}

    /** @return list<ToolOutcome> */
    public function outcomes(): array { return $this->outcomes; }
    /** @return list<ToolOutcome> */
    public function succeeded(): array { return $this->succeeded; }
    /** @return list<ToolOutcome> */
    public function failed(): array { return $this->failed; }

    public function isEmpty(): bool { return $this->outcomes === []; }
    public function allSucceeded(): bool { return $this->failed === [] && $this->succeeded !== []; }
    public function allFailed(): bool { return $this->succeeded === [] && $this->failed !== []; }
    public function isPartial(): bool { return $this->succeeded !== [] && $this->failed !== []; }
    public function count(): int { return count($this->outcomes); }
}
