<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Domain\Tool;

/**
 * Semantic version for registry compatibility checks.
 */
final class ToolVersion
{
    public function __construct(
        private readonly int $major,
        private readonly int $minor = 0,
        private readonly int $patch = 0,
    ) {
        if ($major < 0 || $minor < 0 || $patch < 0) {
            throw new \InvalidArgumentException('Version segments must be non-negative.');
        }
    }

    public static function parse(string $version): self
    {
        $parts = array_map('intval', explode('.', trim($version)));

        return new self($parts[0] ?? 0, $parts[1] ?? 0, $parts[2] ?? 0);
    }

    public function major(): int { return $this->major; }
    public function minor(): int { return $this->minor; }
    public function patch(): int { return $this->patch; }

    public function toString(): string
    {
        return $this->major.'.'.$this->minor.'.'.$this->patch;
    }

    /**
     * Compatible when major matches and candidate is not older than required minor/patch loosely.
     */
    public function isCompatibleWith(self $required): bool
    {
        if ($this->major !== $required->major) {
            return false;
        }

        if ($this->minor < $required->minor) {
            return false;
        }

        if ($this->minor === $required->minor && $this->patch < $required->patch) {
            return false;
        }

        return true;
    }
}
