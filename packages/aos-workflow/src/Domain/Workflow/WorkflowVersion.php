<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Workflow;

use InvalidArgumentException;

final class WorkflowVersion
{
    public function __construct(private readonly int $value)
    {
        if ($value < 1) {
            throw new InvalidArgumentException('WorkflowVersion must be >= 1.');
        }
    }

    public static function initial(): self
    {
        return new self(1);
    }

    public function next(): self
    {
        return new self($this->value + 1);
    }

    public function value(): int
    {
        return $this->value;
    }
}
