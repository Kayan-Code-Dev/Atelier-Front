<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Prompt;

use DateTimeImmutable;

/**
 * Version metadata for a generated prompt document.
 */
final class PromptVersion
{
    public function __construct(
        private readonly string $version,
        private readonly DateTimeImmutable $createdAt,
        private readonly string $generatedBy,
        private readonly string $templateVersion,
    ) {}

    public static function create(
        string $version = '1.0.0',
        string $generatedBy = 'aos.prompts',
        string $templateVersion = '1.0.0',
        ?DateTimeImmutable $createdAt = null,
    ): self {
        return new self($version, $createdAt ?? new DateTimeImmutable(), $generatedBy, $templateVersion);
    }

    public function version(): string
    {
        return $this->version;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function generatedBy(): string
    {
        return $this->generatedBy;
    }

    public function templateVersion(): string
    {
        return $this->templateVersion;
    }
}
