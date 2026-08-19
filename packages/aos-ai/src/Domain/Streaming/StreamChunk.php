<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Streaming;

final class StreamChunk
{
    public function __construct(
        private readonly string $delta,
        private readonly bool $done = false,
        private readonly int $index = 0,
    ) {}

    public function delta(): string
    {
        return $this->delta;
    }

    public function isDone(): bool
    {
        return $this->done;
    }

    public function index(): int
    {
        return $this->index;
    }
}
