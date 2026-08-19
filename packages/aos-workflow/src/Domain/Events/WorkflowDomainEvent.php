<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Events;

use DateTimeImmutable;

final class WorkflowDomainEvent
{
    /**
     * @param array<string, scalar|null> $payload
     */
    public function __construct(
        private readonly string $name,
        private readonly array $payload = [],
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {}

    public function name(): string { return $this->name; }
    /** @return array<string, scalar|null> */
    public function payload(): array { return $this->payload; }
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }

    public static function workflowStarted(array $payload = []): self { return new self('WorkflowStarted', $payload); }
    public static function workflowCompleted(array $payload = []): self { return new self('WorkflowCompleted', $payload); }
    public static function workflowPaused(array $payload = []): self { return new self('WorkflowPaused', $payload); }
    public static function workflowCancelled(array $payload = []): self { return new self('WorkflowCancelled', $payload); }
    public static function taskStarted(array $payload = []): self { return new self('TaskStarted', $payload); }
    public static function taskCompleted(array $payload = []): self { return new self('TaskCompleted', $payload); }
    public static function taskFailed(array $payload = []): self { return new self('TaskFailed', $payload); }
    public static function approvalRequested(array $payload = []): self { return new self('ApprovalRequested', $payload); }
    public static function approvalCompleted(array $payload = []): self { return new self('ApprovalCompleted', $payload); }
    public static function retryTriggered(array $payload = []): self { return new self('RetryTriggered', $payload); }
    public static function workflowArchived(array $payload = []): self { return new self('WorkflowArchived', $payload); }
}
