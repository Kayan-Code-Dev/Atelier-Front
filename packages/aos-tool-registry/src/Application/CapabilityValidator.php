<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\CapabilityRegistryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\RegistryEventPublisherInterface;
use DressnMore\Aos\ToolRegistry\Domain\Events\RegistryDomainEvent;

final class CapabilityValidator
{
    public function __construct(
        private readonly CapabilityRegistryInterface $registry,
        private readonly ?RegistryEventPublisherInterface $events = null,
    ) {}

    /**
     * @param list<string> $required
     * @param list<string> $granted
     */
    public function assertGranted(array $required, array $granted): void
    {
        foreach ($required as $capability) {
            if (! $this->registry->has($capability)) {
                $this->events?->publish(RegistryDomainEvent::capabilityDenied([
                    'capability' => $capability,
                    'reason' => 'not_registered',
                ]));
                throw new \RuntimeException('Permission Denied: capability not registered: '.$capability);
            }

            if (! in_array($capability, $granted, true)) {
                $this->events?->publish(RegistryDomainEvent::capabilityDenied([
                    'capability' => $capability,
                    'reason' => 'not_granted',
                ]));
                throw new \RuntimeException('Permission Denied: missing capability: '.$capability);
            }
        }
    }
}
