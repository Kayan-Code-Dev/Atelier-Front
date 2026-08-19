<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Authorization\Stages;

use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationContext;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationStage;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationStageInterface;
use DressnMore\Aos\Permissions\Domain\Mode\OperatingModeManager;

final class ResolveOperatingModeStage implements AuthorizationStageInterface
{
    public function __construct(
        private readonly OperatingModeManager $modeManager,
    ) {}

    public function name(): AuthorizationStage
    {
        return AuthorizationStage::ResolveOperatingMode;
    }

    public function process(AuthorizationContext $context): void
    {
        $context->setResolvedMode($this->modeManager->resolve($context->request()));
    }
}
