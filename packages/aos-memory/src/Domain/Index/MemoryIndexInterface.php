<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Index;

use DressnMore\Aos\Memory\Domain\Memory\MemoryId;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;
use DressnMore\Aos\Memory\Domain\Memory\MemoryType;

/**
 * Conceptual in-process index for fast scoped lookups.
 */
interface MemoryIndexInterface
{
    public function index(MemoryRecord $record): void;

    public function remove(MemoryId $id): void;

    /**
     * @param  list<MemoryType>  $types
     * @return list<MemoryId>
     */
    public function lookup(
        string $tenantId,
        ?string $customerId = null,
        ?string $conversationId = null,
        array $types = [],
    ): array;
}
