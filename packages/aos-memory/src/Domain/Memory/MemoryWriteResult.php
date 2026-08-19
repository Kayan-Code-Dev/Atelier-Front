<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Memory;

use DressnMore\Aos\Memory\Domain\Summary\ConversationSummary;

final class MemoryWriteResult
{
    /**
     * @param  list<MemoryRecord>  $persisted
     * @param  list<string>  $discardReasons
     */
    public function __construct(
        private readonly array $persisted,
        private readonly ?ConversationSummary $summary,
        private readonly array $discardReasons = [],
    ) {}

    /**
     * @return list<MemoryRecord>
     */
    public function persisted(): array
    {
        return $this->persisted;
    }

    public function summary(): ?ConversationSummary
    {
        return $this->summary;
    }

    /**
     * @return list<string>
     */
    public function discardReasons(): array
    {
        return $this->discardReasons;
    }

    public function count(): int
    {
        return count($this->persisted);
    }
}
