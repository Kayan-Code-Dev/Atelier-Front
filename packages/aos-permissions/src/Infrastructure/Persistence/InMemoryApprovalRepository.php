<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Infrastructure\Persistence;

use DressnMore\Aos\Permissions\Domain\Approval\ApprovalRepositoryInterface;
use DressnMore\Aos\Permissions\Domain\Approval\ApprovalRequest;
use DressnMore\Aos\Permissions\Domain\Approval\ApprovalRequestId;

final class InMemoryApprovalRepository implements ApprovalRepositoryInterface
{
    /** @var array<string, ApprovalRequest> */
    private array $byId = [];

    /** @var array<string, string> */
    private array $byCorrelation = [];

    public function save(ApprovalRequest $request): void
    {
        $this->byId[$request->id()->toString()] = $request;
        $this->byCorrelation[$request->correlationId()] = $request->id()->toString();
    }

    public function findById(ApprovalRequestId $id): ?ApprovalRequest
    {
        return $this->byId[$id->toString()] ?? null;
    }

    public function findByCorrelationId(string $correlationId): ?ApprovalRequest
    {
        $id = $this->byCorrelation[$correlationId] ?? null;
        if ($id === null) {
            return null;
        }

        return $this->byId[$id] ?? null;
    }
}
