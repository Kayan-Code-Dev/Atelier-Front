<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Attachment;

final class Attachment
{
    /**
     * @param array<string, scalar|null> $metadata
     */
    public function __construct(
        private readonly AttachmentType $type,
        private readonly string $url,
        private readonly ?string $mimeType = null,
        private readonly ?int $sizeBytes = null,
        private readonly array $metadata = [],
    ) {}

    public function type(): AttachmentType
    {
        return $this->type;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function mimeType(): ?string
    {
        return $this->mimeType;
    }

    public function sizeBytes(): ?int
    {
        return $this->sizeBytes;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
