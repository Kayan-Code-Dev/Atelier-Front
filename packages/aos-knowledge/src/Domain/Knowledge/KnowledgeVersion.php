<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Knowledge;

use DateTimeImmutable;
use InvalidArgumentException;

final class KnowledgeVersion
{
    public function __construct(
        private readonly string $version,
        private readonly DateTimeImmutable $createdAt,
        private readonly string $createdBy = 'aos.knowledge',
    ) {
        if ($this->version === '') {
            throw new InvalidArgumentException('Knowledge version cannot be empty.');
        }
    }

    public static function initial(string $createdBy = 'aos.knowledge'): self
    {
        return new self('1.0.0', new DateTimeImmutable(), $createdBy);
    }

    public function bumpMinor(string $createdBy = 'aos.knowledge'): self
    {
        $parts = explode('.', $this->version);
        $major = (int) ($parts[0] ?? 1);
        $minor = (int) ($parts[1] ?? 0);

        return new self($major.'.'.($minor + 1).'.0', new DateTimeImmutable(), $createdBy);
    }

    public function version(): string
    {
        return $this->version;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function createdBy(): string
    {
        return $this->createdBy;
    }
}
