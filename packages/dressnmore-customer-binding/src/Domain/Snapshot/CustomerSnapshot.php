<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Domain\Snapshot;

use DressnMore\CustomerBinding\Domain\Customer\CustomerId;

/**
 * Compact snapshot for Planner, Memory, Knowledge, Workflow, Prompt Engine.
 */
final class CustomerSnapshot
{
    /**
     * @param list<string> $tags
     * @param array<string, scalar|null> $highlights
     */
    public function __construct(
        private readonly CustomerId $customerId,
        private readonly string $tenantId,
        private readonly string $displayName,
        private readonly bool $vip,
        private readonly ?string $preferredLanguage,
        private readonly string $paymentStatus,
        private readonly array $tags,
        private readonly int $openOrders,
        private readonly int $openReservations,
        private readonly int $openInvoices,
        private readonly ?string $lastInteractionAt,
        private readonly ?string $summary,
        private readonly array $highlights = [],
    ) {}

    public function customerId(): CustomerId { return $this->customerId; }
    public function tenantId(): string { return $this->tenantId; }
    public function displayName(): string { return $this->displayName; }
    public function vip(): bool { return $this->vip; }
    public function preferredLanguage(): ?string { return $this->preferredLanguage; }
    public function paymentStatus(): string { return $this->paymentStatus; }
    /** @return list<string> */
    public function tags(): array { return $this->tags; }
    public function openOrders(): int { return $this->openOrders; }
    public function openReservations(): int { return $this->openReservations; }
    public function openInvoices(): int { return $this->openInvoices; }
    public function lastInteractionAt(): ?string { return $this->lastInteractionAt; }
    public function summary(): ?string { return $this->summary; }
    /** @return array<string, scalar|null> */
    public function highlights(): array { return $this->highlights; }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'customerId' => $this->customerId->toString(),
            'tenantId' => $this->tenantId,
            'displayName' => $this->displayName,
            'vip' => $this->vip,
            'preferredLanguage' => $this->preferredLanguage,
            'paymentStatus' => $this->paymentStatus,
            'tags' => $this->tags,
            'openOrders' => $this->openOrders,
            'openReservations' => $this->openReservations,
            'openInvoices' => $this->openInvoices,
            'lastInteractionAt' => $this->lastInteractionAt,
            'summary' => $this->summary,
            'highlights' => $this->highlights,
        ];
    }
}
