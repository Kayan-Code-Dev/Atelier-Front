<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Tool;

use InvalidArgumentException;
use Stringable;

/**
 * Category code supporting builtins + future custom categories without enum churn.
 */
final class ToolCategoryCode implements Stringable
{
    private function __construct(
        private readonly string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException('ToolCategoryCode cannot be empty.');
        }
    }

    public static function fromEnum(ToolCategory $category): self
    {
        return new self($category->value);
    }

    public static function custom(string $value): self
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            throw new InvalidArgumentException('Custom category cannot be empty.');
        }

        return new self($normalized);
    }

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));
        $builtin = ToolCategory::tryFrom($normalized);
        if ($builtin !== null) {
            return self::fromEnum($builtin);
        }

        return self::custom($normalized);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
