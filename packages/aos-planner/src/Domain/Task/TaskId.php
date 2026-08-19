<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Task;

use InvalidArgumentException;
use Stringable;

final class TaskId implements Stringable
{
    public function __construct(
        private readonly string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException('TaskId cannot be empty.');
        }
    }

    public static function generate(string $prefix = 'task'): self
    {
        return new self($prefix.'_'.bin2hex(random_bytes(4)));
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
