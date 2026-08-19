<?php

declare(strict_types=1);

namespace DressnMore\Aos\Events\Contracts;

/**
 * Declares event subscriptions for the foundation event bus.
 */
interface SubscriberInterface
{
    /**
     * @return array<class-string, list<class-string|callable>>
     */
    public function subscriptions(): array;
}
