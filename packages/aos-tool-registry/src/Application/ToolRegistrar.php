<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\ApprovalRegistryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\CapabilityRegistryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\IntentRegistryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\PolicyRegistryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\ProviderRegistryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\RegistryEventPublisherInterface;
use DressnMore\Aos\ToolRegistry\Contracts\ToolMetadataRegistryInterface;
use DressnMore\Aos\ToolRegistry\Contracts\ToolRegistryInterface;
use DressnMore\Aos\ToolRegistry\Domain\Approval\ApprovalDescriptor;
use DressnMore\Aos\ToolRegistry\Domain\Capability\CapabilityDescriptor;
use DressnMore\Aos\ToolRegistry\Domain\Events\RegistryDomainEvent;
use DressnMore\Aos\ToolRegistry\Domain\Intent\IntentDescriptor;
use DressnMore\Aos\ToolRegistry\Domain\Policy\PolicyDescriptor;
use DressnMore\Aos\ToolRegistry\Domain\Provider\ProviderDescriptor;
use DressnMore\Aos\ToolRegistry\Domain\Tool\ToolDescriptor;

/**
 * Plugin-facing registrar — domains register without knowing Planner/Gateway internals.
 */
final class ToolRegistrar
{
    public function __construct(
        private readonly ToolRegistryInterface $tools,
        private readonly ToolMetadataRegistryInterface $metadata,
        private readonly CapabilityRegistryInterface $capabilities,
        private readonly IntentRegistryInterface $intents,
        private readonly PolicyRegistryInterface $policies,
        private readonly ApprovalRegistryInterface $approvals,
        private readonly ProviderRegistryInterface $providers,
        private readonly ToolValidator $validator,
        private readonly ?RegistryEventPublisherInterface $events = null,
    ) {}

    public function registerProvider(ProviderDescriptor $provider): void
    {
        $this->providers->register($provider);
    }

    public function registerCapability(CapabilityDescriptor $capability): void
    {
        $this->capabilities->register($capability);
        $this->events?->publish(RegistryDomainEvent::capabilityRegistered([
            'capability' => $capability->name(),
            'ownerDomain' => $capability->ownerDomain(),
        ]));
    }

    public function registerTool(ToolDescriptor $descriptor, bool $validate = true): void
    {
        if ($validate) {
            $this->validator->assertValid($descriptor);
        }
        $this->tools->register($descriptor);
        $this->metadata->syncFromDescriptor($descriptor);
        $this->events?->publish(RegistryDomainEvent::toolRegistered([
            'tool' => $descriptor->name(),
            'ownerDomain' => $descriptor->metadata()->ownerDomain(),
            'version' => $descriptor->version()->toString(),
        ]));
    }

    public function registerIntent(IntentDescriptor $intent): void
    {
        $this->intents->register($intent);
        $this->events?->publish(RegistryDomainEvent::intentRegistered([
            'intent' => $intent->intent(),
        ]));
    }

    public function registerPolicy(PolicyDescriptor $policy): void
    {
        $this->policies->register($policy);
    }

    public function registerApproval(ApprovalDescriptor $approval): void
    {
        $this->approvals->register($approval);
    }
}
