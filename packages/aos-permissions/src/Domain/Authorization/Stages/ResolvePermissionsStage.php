<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Authorization\Stages;

use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationContext;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationStage;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationStageInterface;
use DressnMore\Aos\Permissions\Domain\Decision\AuthorizationOutcome;
use DressnMore\Aos\Permissions\Domain\Permission\PermissionEngine;

final class ResolvePermissionsStage implements AuthorizationStageInterface
{
    public function __construct(
        private readonly PermissionEngine $permissionEngine,
    ) {}

    public function name(): AuthorizationStage
    {
        return AuthorizationStage::ResolvePermissions;
    }

    public function process(AuthorizationContext $context): void
    {
        $ok = $this->permissionEngine->hasAllRequired(
            $context->request(),
            $context->capabilityDefinition()
        );

        if (! $ok && $context->capabilityDefinition() !== null) {
            // Soft marker — DecisionEngine will deny; keep pipeline flowing.
            $context->setOutcome(
                AuthorizationOutcome::Denied,
                'permission resolution incomplete'
            );
        }
    }
}
