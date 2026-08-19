<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Contracts;

use DressnMore\Aos\ToolRegistry\Domain\Events\RegistryDomainEvent;

interface RegistryEventPublisherInterface
{
    public function publish(RegistryDomainEvent $event): void;
}
