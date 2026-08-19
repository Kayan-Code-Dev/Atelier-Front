<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Task;

final class TaskDefinition
{
    /**
     * @param array<string, scalar|array|null> $config
     */
    public function __construct(
        private readonly string $id,
        private readonly TaskType $type,
        private readonly array $config = [],
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function type(): TaskType
    {
        return $this->type;
    }

    /**
     * @return array<string, scalar|array|null>
     */
    public function config(): array
    {
        return $this->config;
    }
}
