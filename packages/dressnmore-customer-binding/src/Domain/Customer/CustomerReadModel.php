<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Domain\Customer;

/**
 * AI-safe customer read model (binding DTO) — not an Eloquent model.
 *
 * @phpstan-type MeasurementArray array<string, scalar|null>
 * @phpstan-type NoteArray array{id?:string,text:string,at?:string}
 */
final class CustomerReadModel
{
    /**
     * @param list<array<string, scalar|null>> $orders
     * @param list<array<string, scalar|null>> $reservations
     * @param list<array<string, scalar|null>> $invoices
     * @param list<NoteArray> $notes
     * @param list<string> $tags
     * @param array<string, scalar|null> $preferences
     * @param MeasurementArray $measurements
     * @param list<array<string, scalar|null>> $timelineSeed
     */
    public function __construct(
        private readonly CustomerId $id,
        private readonly string $tenantId,
        private readonly string $displayName,
        private readonly ?string $phone = null,
        private readonly ?string $preferredLanguage = null,
        private readonly bool $vip = false,
        private readonly array $tags = [],
        private readonly array $preferences = [],
        private readonly array $measurements = [],
        private readonly array $orders = [],
        private readonly array $reservations = [],
        private readonly array $invoices = [],
        private readonly string $paymentStatus = 'unknown',
        private readonly array $notes = [],
        private readonly ?string $lastInteractionAt = null,
        private readonly ?string $aiSummaryPlaceholder = null,
        private readonly array $timelineSeed = [],
    ) {}

    public function id(): CustomerId { return $this->id; }
    public function tenantId(): string { return $this->tenantId; }
    public function displayName(): string { return $this->displayName; }
    public function phone(): ?string { return $this->phone; }
    public function preferredLanguage(): ?string { return $this->preferredLanguage; }
    public function vip(): bool { return $this->vip; }
    /** @return list<string> */
    public function tags(): array { return $this->tags; }
    /** @return array<string, scalar|null> */
    public function preferences(): array { return $this->preferences; }
    /** @return MeasurementArray */
    public function measurements(): array { return $this->measurements; }
    /** @return list<array<string, scalar|null>> */
    public function orders(): array { return $this->orders; }
    /** @return list<array<string, scalar|null>> */
    public function reservations(): array { return $this->reservations; }
    /** @return list<array<string, scalar|null>> */
    public function invoices(): array { return $this->invoices; }
    public function paymentStatus(): string { return $this->paymentStatus; }
    /** @return list<NoteArray> */
    public function notes(): array { return $this->notes; }
    public function lastInteractionAt(): ?string { return $this->lastInteractionAt; }
    public function aiSummaryPlaceholder(): ?string { return $this->aiSummaryPlaceholder; }
    /** @return list<array<string, scalar|null>> */
    public function timelineSeed(): array { return $this->timelineSeed; }
}
