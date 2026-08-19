<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Template;

use InvalidArgumentException;
use Stringable;

final class PromptTemplateId implements Stringable
{
    public function __construct(
        private readonly string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException('PromptTemplateId cannot be empty.');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
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
