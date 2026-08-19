<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Approval;

use DateInterval;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityCode;
use DressnMore\Aos\Permissions\Domain\Risk\RiskLevel;

interface ApprovalRepositoryInterface
{
    public function save(ApprovalRequest $request): void;

    public function findById(ApprovalRequestId $id): ?ApprovalRequest;

    public function findByCorrelationId(string $correlationId): ?ApprovalRequest;
}
