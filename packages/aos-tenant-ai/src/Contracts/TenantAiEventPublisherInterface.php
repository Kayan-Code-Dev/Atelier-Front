<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Contracts;

use DressnMore\Aos\TenantAi\Domain\Events\TenantAiDomainEvent;

interface TenantAiEventPublisherInterface
{
    public function publish(TenantAiDomainEvent $event): void;
}
