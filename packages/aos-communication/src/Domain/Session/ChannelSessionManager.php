<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Session;

use DateTimeImmutable;

final class ChannelSessionManager
{
    /** @var array<string, DateTimeImmutable> */
    private array $sessions = [];

    public function touch(string $sessionKey): void
    {
        $this->sessions[$sessionKey] = new DateTimeImmutable();
    }

    public function has(string $sessionKey): bool
    {
        return isset($this->sessions[$sessionKey]);
    }
}
