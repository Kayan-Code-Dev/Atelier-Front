<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Policies;

use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;
use DressnMore\Aos\Memory\Domain\Memory\MemoryType;

/**
 * Central memory policy evaluator — retention, privacy, isolation, replacement.
 */
final class MemoryPolicy
{
    public function __construct(
        private readonly float $minImportanceToPersist = 0.35,
        private readonly float $minConfidenceToPersist = 0.4,
        private readonly int $maxWorkingMemories = 25,
    ) {}

    public function allowsPersist(MemoryRecord $record): bool
    {
        if ($record->type() === MemoryType::Semantic) {
            // Placeholder type — accept but mark as non-operational for Sprint 8.
            return $record->importance()->value() >= $this->minImportanceToPersist;
        }

        if ($record->importance()->value() < $this->minImportanceToPersist) {
            return false;
        }

        if ($record->confidence()->value() < $this->minConfidenceToPersist) {
            return false;
        }

        return true;
    }

    public function enforcesTenantIsolation(): bool
    {
        return true;
    }

    public function enforcesCustomerIsolation(): bool
    {
        return true;
    }

    public function allowsCrossTenantAccess(): bool
    {
        return false;
    }

    public function maxWorkingMemories(): int
    {
        return $this->maxWorkingMemories;
    }

    public function shouldReplace(MemoryRecord $existing, MemoryRecord $incoming): bool
    {
        if ($existing->fingerprint() !== $incoming->fingerprint()) {
            return false;
        }

        return $incoming->importance()->value() >= $existing->importance()->value()
            || $incoming->confidence()->value() > $existing->confidence()->value();
    }

    public function allowsCompression(): bool
    {
        return true;
    }

    public function privacyRedactsPiiHints(): bool
    {
        return true;
    }
}
