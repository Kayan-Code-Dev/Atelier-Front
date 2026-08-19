<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Memory;

use DressnMore\Aos\Memory\Domain\Factory\MemoryFactory;
use DressnMore\Aos\Memory\Domain\Policies\MemoryPolicy;
use DressnMore\Aos\Memory\Domain\Specifications\DuplicateMemorySpecification;

/**
 * Merges / promotes memories (short-term → long-term, etc.).
 */
final class MemoryConsolidator
{
    public function __construct(
        private readonly MemoryFactory $factory = new MemoryFactory(),
        private readonly MemoryPolicy $policy = new MemoryPolicy(),
        private readonly DuplicateMemorySpecification $duplicates = new DuplicateMemorySpecification(),
    ) {}

    /**
     * @param  list<MemoryRecord>  $candidates
     * @param  list<MemoryRecord>  $existing
     * @return list<MemoryRecord> Records to persist (merged / promoted)
     */
    public function consolidate(array $candidates, array $existing): array
    {
        $result = [];
        foreach ($candidates as $candidate) {
            $merged = false;
            foreach ($existing as $current) {
                if ($this->duplicates->isDuplicateOf($candidate, $current)) {
                    if ($this->policy->shouldReplace($current, $candidate)) {
                        $result[] = $current
                            ->withContent($candidate->content())
                            ->withImportance($candidate->importance());
                    }
                    $merged = true;
                    break;
                }
            }

            if (! $merged) {
                $result[] = $this->maybePromote($candidate);
            }
        }

        return $result;
    }

    private function maybePromote(MemoryRecord $record): MemoryRecord
    {
        if ($record->type() === MemoryType::ShortTerm && $record->importance()->value() >= 0.75) {
            return $record->withType(MemoryType::LongTerm);
        }

        if ($record->type() === MemoryType::Conversation && $record->importance()->value() >= 0.85) {
            return $record->withType(MemoryType::Episodic);
        }

        return $record;
    }
}
