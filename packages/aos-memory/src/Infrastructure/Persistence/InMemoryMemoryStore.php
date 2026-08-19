<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Infrastructure\Persistence;

use DressnMore\Aos\Memory\Domain\Memory\MemoryId;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;
use DressnMore\Aos\Memory\Domain\Memory\MemoryType;
use DressnMore\Aos\Memory\Domain\Repository\MemoryStoreInterface;

/**
 * In-memory adapter — no database / Eloquent.
 */
final class InMemoryMemoryStore implements MemoryStoreInterface
{
    /** @var array<string, MemoryRecord> */
    private array $items = [];

    public function save(MemoryRecord $record): void
    {
        $this->items[$record->id()->toString()] = $record;
    }

    public function find(MemoryId $id): ?MemoryRecord
    {
        return $this->items[$id->toString()] ?? null;
    }

    public function findByScope(
        string $tenantId,
        ?string $customerId = null,
        ?string $conversationId = null,
        array $types = [],
        int $limit = 100,
    ): array {
        $typeValues = array_map(
            static fn (MemoryType $t): string => $t->value,
            $types
        );

        $out = [];
        foreach ($this->items as $record) {
            if ($record->isDiscarded()) {
                continue;
            }
            if ($record->tenantId() !== $tenantId) {
                continue;
            }
            if ($customerId !== null && $record->customerId() !== null && $record->customerId() !== $customerId) {
                continue;
            }
            if ($conversationId !== null && $record->sourceConversationId() !== null && $record->sourceConversationId() !== $conversationId) {
                continue;
            }
            if ($types !== [] && ! in_array($record->type()->value, $typeValues, true)) {
                continue;
            }
            $out[] = $record;
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    public function findByFingerprint(string $tenantId, string $fingerprint): array
    {
        $out = [];
        foreach ($this->items as $record) {
            if ($record->tenantId() === $tenantId && $record->fingerprint() === $fingerprint && ! $record->isDiscarded()) {
                $out[] = $record;
            }
        }

        return $out;
    }

    public function delete(MemoryId $id): void
    {
        unset($this->items[$id->toString()]);
    }

    public function allActiveForTenant(string $tenantId): array
    {
        $out = [];
        foreach ($this->items as $record) {
            if ($record->tenantId() === $tenantId && ! $record->isDiscarded()) {
                $out[] = $record;
            }
        }

        return $out;
    }
}
