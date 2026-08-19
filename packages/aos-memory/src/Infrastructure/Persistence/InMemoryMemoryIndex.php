<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Infrastructure\Persistence;

use DressnMore\Aos\Memory\Domain\Index\MemoryIndexInterface;
use DressnMore\Aos\Memory\Domain\Memory\MemoryId;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;
use DressnMore\Aos\Memory\Domain\Memory\MemoryType;

final class InMemoryMemoryIndex implements MemoryIndexInterface
{
    /** @var array<string, array{tenant: string, customer: ?string, conversation: ?string, type: string}> */
    private array $entries = [];

    public function index(MemoryRecord $record): void
    {
        $this->entries[$record->id()->toString()] = [
            'tenant' => $record->tenantId(),
            'customer' => $record->customerId(),
            'conversation' => $record->sourceConversationId(),
            'type' => $record->type()->value,
        ];
    }

    public function remove(MemoryId $id): void
    {
        unset($this->entries[$id->toString()]);
    }

    public function lookup(
        string $tenantId,
        ?string $customerId = null,
        ?string $conversationId = null,
        array $types = [],
    ): array {
        $typeValues = array_map(static fn (MemoryType $t): string => $t->value, $types);
        $ids = [];
        foreach ($this->entries as $id => $meta) {
            if ($meta['tenant'] !== $tenantId) {
                continue;
            }
            if ($customerId !== null && $meta['customer'] !== null && $meta['customer'] !== $customerId) {
                continue;
            }
            if ($conversationId !== null && $meta['conversation'] !== null && $meta['conversation'] !== $conversationId) {
                continue;
            }
            if ($types !== [] && ! in_array($meta['type'], $typeValues, true)) {
                continue;
            }
            $ids[] = MemoryId::fromString($id);
        }

        return $ids;
    }
}
