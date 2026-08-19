<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Prompt;

use InvalidArgumentException;
use Stringable;

final class PromptId implements Stringable
{
    public function __construct(
        private readonly string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException('PromptId cannot be empty.');
        }
    }

    public static function generate(): self
    {
        return new self('prm_'.bin2hex(random_bytes(8)));
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
