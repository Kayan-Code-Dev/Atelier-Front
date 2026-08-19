<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Approval;

use DateTimeImmutable;
use InvalidArgumentException;
use Stringable;

final class ApprovalRequestId implements Stringable
{
    public function __construct(
        private readonly string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException('ApprovalRequestId cannot be empty.');
        }
    }

    public static function generate(): self
    {
        return new self('apr_'.bin2hex(random_bytes(8)));
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
