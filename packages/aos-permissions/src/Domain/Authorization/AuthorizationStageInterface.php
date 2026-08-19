<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Authorization;

interface AuthorizationStageInterface
{
    public function name(): AuthorizationStage;

    public function process(AuthorizationContext $context): void;
}
