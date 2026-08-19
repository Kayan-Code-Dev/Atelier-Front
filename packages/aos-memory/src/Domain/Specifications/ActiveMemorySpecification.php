<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Specifications;

use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;

final class ActiveMemorySpecification
{
    public function isSatisfiedBy(MemoryRecord $record): bool
    {
        return ! $record->isDiscarded() && ! $record->isExpired();
    }
}
