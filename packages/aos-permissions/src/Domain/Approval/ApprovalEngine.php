<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Approval;

use DateInterval;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityCode;
use DressnMore\Aos\Permissions\Domain\Risk\RiskLevel;

final class ApprovalEngine
{
    public function __construct(
        private readonly ApprovalRepositoryInterface $repository,
    ) {}

    public function requestApproval(
        string $correlationId,
        CapabilityCode $capability,
        RiskLevel $riskLevel,
        ?ApprovalChain $chain = null,
        ?DateInterval $timeout = null,
    ): ApprovalRequest {
        $request = ApprovalRequest::open(
            $correlationId,
            $capability,
            $riskLevel,
            ($chain ?? ApprovalChain::defaultSupervisor())->steps(),
            $timeout,
        );
        $this->repository->save($request);

        return $request;
    }

    public function grant(ApprovalRequestId $id, string $decidedBy, string $reason = ''): ApprovalRequest
    {
        $request = $this->require($id);
        $request->grant($decidedBy, $reason);
        $this->repository->save($request);

        return $request;
    }

    public function reject(ApprovalRequestId $id, string $decidedBy, string $reason = ''): ApprovalRequest
    {
        $request = $this->require($id);
        $request->reject($decidedBy, $reason);
        $this->repository->save($request);

        return $request;
    }

    public function expireIfNeeded(ApprovalRequest $request): ApprovalRequest
    {
        if ($request->status() === ApprovalStatus::Pending && $request->isExpired()) {
            $request->markExpired();
            $this->repository->save($request);
        }

        return $request;
    }

    private function require(ApprovalRequestId $id): ApprovalRequest
    {
        $request = $this->repository->findById($id);
        if ($request === null) {
            throw new \RuntimeException(sprintf('Approval request [%s] not found.', $id->toString()));
        }

        return $this->expireIfNeeded($request);
    }
}
