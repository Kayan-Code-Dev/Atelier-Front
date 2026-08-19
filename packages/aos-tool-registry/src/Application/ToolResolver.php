<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\RegistryEventPublisherInterface;
use DressnMore\Aos\ToolRegistry\Contracts\ToolDiscoveryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\ToolResolverInterface;
use DressnMore\Aos\ToolRegistry\Domain\Events\RegistryDomainEvent;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolDescriptor;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolVersion;
use RuntimeException;

final class ToolResolver implements ToolResolverInterface
{
    public function __construct(
        private readonly ToolDiscoveryInterface $discovery,
        private readonly ?RegistryEventPublisherInterface $events = null,
    ) {}

    public function resolve(string $toolName, ?ToolVersion $minimumVersion = null): ToolDescriptor
    {
        $descriptor = $this->discovery->find($toolName);
        if ($descriptor === null) {
            $this->events?->publish(RegistryDomainEvent::discoveryRejected([
                'tool' => $toolName,
                'reason' => 'not_registered_or_not_discoverable',
            ]));
            throw new RuntimeException('Tool not registered or not discoverable: '.$toolName);
        }

        if ($minimumVersion !== null && ! $descriptor->version()->isCompatibleWith($minimumVersion)) {
            $this->events?->publish(RegistryDomainEvent::versionIncompatible([
                'tool' => $toolName,
                'actual' => $descriptor->version()->toString(),
                'required' => $minimumVersion->toString(),
            ]));
            throw new RuntimeException(sprintf(
                'Tool version incompatible for %s: have %s, need %s',
                $toolName,
                $descriptor->version()->toString(),
                $minimumVersion->toString(),
            ));
        }

        return $descriptor;
    }
}
