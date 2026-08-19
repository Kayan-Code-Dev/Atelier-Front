<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Memory;

use DateTimeImmutable;

enum ExpirationPolicy: string
{
    case Session = 'session';
    case ShortLived = 'short_lived';
    case Rolling = 'rolling';
    case LongLived = 'long_lived';
    case Permanent = 'permanent';

    public function expiresAt(DateTimeImmutable $from): ?DateTimeImmutable
    {
        return match ($this) {
            self::Session => $from->modify('+2 hours'),
            self::ShortLived => $from->modify('+7 days'),
            self::Rolling => $from->modify('+30 days'),
            self::LongLived => $from->modify('+365 days'),
            self::Permanent => null,
        };
    }
}
