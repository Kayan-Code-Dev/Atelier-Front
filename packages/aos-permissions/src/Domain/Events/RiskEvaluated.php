<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Events;

final class RiskEvaluated extends PermissionDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $riskLevel,
        public readonly bool $requiresApproval,
        public readonly bool $requiresHuman,
    ) {
        parent::__construct($correlationId);
    }
}
