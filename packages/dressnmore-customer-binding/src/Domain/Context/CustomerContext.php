<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Domain\Context;

use DressnMore\CustomerBinding\Domain\Customer\CustomerId;

/**
 * Rich customer context for Planner / Prompt / Memory consumers.
 */
final class CustomerContext
{
    /**
     * @param array<string, scalar|null> $basicProfile
     * @param array<string, scalar|null> $measurements
     * @param list<array<string, scalar|null>> $orders
     * @param list<array<string, scalar|null>> $reservations
     * @param list<array<string, scalar|null>> $invoices
     * @param array<string, scalar|null> $preferences
     * @param list<string> $tags
     * @param list<array{id?:string,text:string,at?:string}> $notes
     */
    public function __construct(
        private readonly CustomerId $customerId,
        private readonly string $tenantId,
        private readonly array $basicProfile,
        private readonly array $measurements,
        private readonly array $orders,
        private readonly array $reservations,
        private readonly array $invoices,
        private readonly string $paymentStatus,
        private readonly ?string $preferredLanguage,
        private readonly array $preferences,
        private readonly bool $vipStatus,
        private readonly array $tags,
        private readonly array $notes,
        private readonly ?string $lastInteraction,
        private readonly ?string $aiSummaryPlaceholder,
    ) {}

    public function customerId(): CustomerId { return $this->customerId; }
    public function tenantId(): string { return $this->tenantId; }
    /** @return array<string, scalar|null> */
    public function basicProfile(): array { return $this->basicProfile; }
    /** @return array<string, scalar|null> */
    public function measurements(): array { return $this->measurements; }
    /** @return list<array<string, scalar|null>> */
    public function orders(): array { return $this->orders; }
    /** @return list<array<string, scalar|null>> */
    public function reservations(): array { return $this->reservations; }
    /** @return list<array<string, scalar|null>> */
    public function invoices(): array { return $this->invoices; }
    public function paymentStatus(): string { return $this->paymentStatus; }
    public function preferredLanguage(): ?string { return $this->preferredLanguage; }
    /** @return array<string, scalar|null> */
    public function preferences(): array { return $this->preferences; }
    public function vipStatus(): bool { return $this->vipStatus; }
    /** @return list<string> */
    public function tags(): array { return $this->tags; }
    /** @return list<array{id?:string,text:string,at?:string}> */
    public function notes(): array { return $this->notes; }
    public function lastInteraction(): ?string { return $this->lastInteraction; }
    public function aiSummaryPlaceholder(): ?string { return $this->aiSummaryPlaceholder; }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'customerId' => $this->customerId->toString(),
            'tenantId' => $this->tenantId,
            'basicProfile' => $this->basicProfile,
            'measurements' => $this->measurements,
            'orders' => $this->orders,
            'reservations' => $this->reservations,
            'invoices' => $this->invoices,
            'paymentStatus' => $this->paymentStatus,
            'preferredLanguage' => $this->preferredLanguage,
            'preferences' => $this->preferences,
            'vipStatus' => $this->vipStatus,
            'tags' => $this->tags,
            'notes' => $this->notes,
            'lastInteraction' => $this->lastInteraction,
            'aiSummaryPlaceholder' => $this->aiSummaryPlaceholder,
        ];
    }
}
