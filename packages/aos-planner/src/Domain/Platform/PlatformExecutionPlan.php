<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Platform;

/**
 * Sprint 18 Execution Plan — planning output for Gateway (no execution).
 */
final class PlatformExecutionPlan
{
    /**
     * @param list<string> $requiredCapabilities
     * @param list<string> $selectedTools
     * @param list<PlanStep> $orderedSteps
     * @param list<string> $requiredApprovals
     */
    public function __construct(
        private readonly string $planId,
        private readonly string $tenantId,
        private readonly ?string $conversationId,
        private readonly string $goal,
        private readonly string $intent,
        private readonly array $requiredCapabilities,
        private readonly array $selectedTools,
        private readonly array $orderedSteps,
        private readonly array $requiredApprovals,
        private readonly float $estimatedCost,
        private readonly string $estimatedComplexity,
        private readonly PlanningStatus $status,
        private readonly string $createdAt,
        private readonly string $rejectionReason = '',
    ) {}

    public function planId(): string { return $this->planId; }
    public function tenantId(): string { return $this->tenantId; }
    public function conversationId(): ?string { return $this->conversationId; }
    public function goal(): string { return $this->goal; }
    public function intent(): string { return $this->intent; }
    /** @return list<string> */
    public function requiredCapabilities(): array { return $this->requiredCapabilities; }
    /** @return list<string> */
    public function selectedTools(): array { return $this->selectedTools; }
    /** @return list<PlanStep> */
    public function orderedSteps(): array { return $this->orderedSteps; }
    /** @return list<string> */
    public function requiredApprovals(): array { return $this->requiredApprovals; }
    public function estimatedCost(): float { return $this->estimatedCost; }
    public function estimatedComplexity(): string { return $this->estimatedComplexity; }
    public function status(): PlanningStatus { return $this->status; }
    public function createdAt(): string { return $this->createdAt; }
    public function rejectionReason(): string { return $this->rejectionReason; }

    public function isReadyForGateway(): bool
    {
        return in_array($this->status, [PlanningStatus::Ready, PlanningStatus::RequiresApproval], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'planId' => $this->planId,
            'tenantId' => $this->tenantId,
            'conversationId' => $this->conversationId,
            'goal' => $this->goal,
            'intent' => $this->intent,
            'requiredCapabilities' => $this->requiredCapabilities,
            'selectedTools' => $this->selectedTools,
            'orderedSteps' => array_map(static fn (PlanStep $s): array => [
                'order' => $s->order(),
                'tool' => $s->toolName(),
                'capability' => $s->capability(),
                'goal' => $s->goal(),
            ], $this->orderedSteps),
            'requiredApprovals' => $this->requiredApprovals,
            'estimatedCost' => $this->estimatedCost,
            'estimatedComplexity' => $this->estimatedComplexity,
            'status' => $this->status->value,
            'createdAt' => $this->createdAt,
            'rejectionReason' => $this->rejectionReason,
        ];
    }
}
