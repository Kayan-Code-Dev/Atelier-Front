<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Authorization\Stages;

use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationContext;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationStage;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationStageInterface;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityEngine;

final class ResolveCapabilitiesStage implements AuthorizationStageInterface
{
    public function __construct(
        private readonly CapabilityEngine $capabilityEngine,
    ) {}

    public function name(): AuthorizationStage
    {
        return AuthorizationStage::ResolveCapabilities;
    }

    public function process(AuthorizationContext $context): void
    {
        $definition = $this->capabilityEngine->resolveDefinition(
            $context->request()->requestedCapability()
        );
        $context->setCapabilityDefinition($definition);
    }
}
