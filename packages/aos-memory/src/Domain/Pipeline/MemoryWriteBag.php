<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Pipeline;

use DressnMore\Aos\Memory\Domain\Memory\ConversationMemoryUpdate;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;
use DressnMore\Aos\Memory\Domain\Summary\ConversationSummary;

final class MemoryWriteBag
{
    /** @var list<array{content: string, type: \DressnMore\Aos\Memory\Domain\Memory\MemoryType, importance: float, confidence: float, tags: list<string>}> */
    private array $candidates = [];

    /** @var list<MemoryRecord> */
    private array $classified = [];

    /** @var list<MemoryRecord> */
    private array $accepted = [];

    /** @var list<MemoryRecord> */
    private array $persisted = [];

    private ?ConversationSummary $summary = null;

    /** @var list<string> */
    private array $stages = [];

    /** @var list<string> */
    private array $discardReasons = [];

    public function __construct(
        private readonly ConversationMemoryUpdate $update,
    ) {}

    public function update(): ConversationMemoryUpdate
    {
        return $this->update;
    }

    public function mark(string $stage): void
    {
        $this->stages[] = $stage;
    }

    /**
     * @return list<string>
     */
    public function stages(): array
    {
        return $this->stages;
    }

    /**
     * @param  list<array{content: string, type: \DressnMore\Aos\Memory\Domain\Memory\MemoryType, importance: float, confidence: float, tags: list<string>}>  $candidates
     */
    public function setCandidates(array $candidates): void
    {
        $this->candidates = $candidates;
    }

    /**
     * @return list<array{content: string, type: \DressnMore\Aos\Memory\Domain\Memory\MemoryType, importance: float, confidence: float, tags: list<string>}>
     */
    public function candidates(): array
    {
        return $this->candidates;
    }

    /**
     * @param  list<MemoryRecord>  $records
     */
    public function setClassified(array $records): void
    {
        $this->classified = $records;
    }

    /**
     * @return list<MemoryRecord>
     */
    public function classified(): array
    {
        return $this->classified;
    }

    /**
     * @param  list<MemoryRecord>  $records
     */
    public function setAccepted(array $records): void
    {
        $this->accepted = $records;
    }

    /**
     * @return list<MemoryRecord>
     */
    public function accepted(): array
    {
        return $this->accepted;
    }

    /**
     * @param  list<MemoryRecord>  $records
     */
    public function setPersisted(array $records): void
    {
        $this->persisted = $records;
    }

    /**
     * @return list<MemoryRecord>
     */
    public function persisted(): array
    {
        return $this->persisted;
    }

    public function setSummary(ConversationSummary $summary): void
    {
        $this->summary = $summary;
    }

    public function summary(): ?ConversationSummary
    {
        return $this->summary;
    }

    public function addDiscardReason(string $reason): void
    {
        $this->discardReasons[] = $reason;
    }

    /**
     * @return list<string>
     */
    public function discardReasons(): array
    {
        return $this->discardReasons;
    }
}
