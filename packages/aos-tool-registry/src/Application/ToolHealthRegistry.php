<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Domain\Health\ToolHealthRecord;

final class ToolHealthRegistry
{
    /** @var array<string, ToolHealthRecord> */
    private array $records = [];

    public function record(ToolHealthRecord $record): void
    {
        $this->records[$record->toolName()] = $record;
    }

    public function get(string $toolName): ?ToolHealthRecord
    {
        return $this->records[$toolName] ?? null;
    }

    public function isHealthy(string $toolName): bool
    {
        return $this->records[$toolName]?->healthy() ?? true;
    }

    /**
     * @return list<ToolHealthRecord>
     */
    public function all(): array
    {
        return array_values($this->records);
    }
}
