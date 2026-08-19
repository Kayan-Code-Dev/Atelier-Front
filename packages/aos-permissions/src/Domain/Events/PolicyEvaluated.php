<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Events;

final class PolicyEvaluated extends PermissionDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly ?string $dominantEffect,
        public readonly int $matchedCount,
    ) {
        parent::__construct($correlationId);
    }
}
