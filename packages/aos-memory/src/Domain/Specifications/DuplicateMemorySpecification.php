<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Specifications;

use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;

final class DuplicateMemorySpecification
{
    public function isDuplicateOf(MemoryRecord $a, MemoryRecord $b): bool
    {
        return $a->fingerprint() === $b->fingerprint();
    }
}
