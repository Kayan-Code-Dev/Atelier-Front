<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Snapshot;

use DateTimeImmutable;
use DressnMore\Aos\Context\Domain\Business\BusinessContext;
use DressnMore\Aos\Context\Domain\Channel\ChannelContext;
use DressnMore\Aos\Context\Domain\Conversation\ConversationContext;
use DressnMore\Aos\Context\Domain\Conversation\ConversationOwnerKind;
use DressnMore\Aos\Context\Domain\Conversation\OperatingMode;
use DressnMore\Aos\Context\Domain\Customer\CustomerContext;
use DressnMore\Aos\Context\Domain\Identity\CustomerId;
use DressnMore\Aos\Context\Domain\Localization\BusinessHours;
use DressnMore\Aos\Context\Domain\Localization\LanguageCode;
use DressnMore\Aos\Context\Domain\Localization\TimezoneId;
use DressnMore\Aos\Context\Domain\Localization\WorkingHours;
use DressnMore\Aos\Context\Domain\Permission\AvailableCapabilities;
use DressnMore\Aos\Context\Domain\Permission\ResolvedPermissions;
use DressnMore\Aos\Context\Domain\Tenant\BranchContext;
use DressnMore\Aos\Context\Domain\Tenant\BranchId;
use DressnMore\Aos\Context\Domain\Tenant\TenantContext;
use DressnMore\Aos\Context\Domain\Tenant\TenantId;
use DressnMore\Aos\Context\Domain\Conversation\ConversationRef;

/**
 * Immutable Context Snapshot — single source of assembled context for downstream modules.
 */
final class ContextSnapshot
{
    /**
     * @param  array<string, scalar|null>  $metadata
     */
    public function __construct(
        private readonly ContextSnapshotId $id,
        private readonly string $version,
        private readonly TenantContext $tenant,
        private readonly ChannelContext $channel,
        private readonly CustomerContext $customer,
        private readonly ConversationContext $conversation,
        private readonly BranchContext $branch,
        private readonly BusinessContext $business,
        private readonly LanguageCode $language,
        private readonly TimezoneId $timezone,
        private readonly BusinessHours $businessHours,
        private readonly WorkingHours $workingHours,
        private readonly ResolvedPermissions $permissions,
        private readonly AvailableCapabilities $capabilities,
        private readonly array $metadata,
        private readonly DateTimeImmutable $builtAt,
        private readonly ?DateTimeImmutable $expiresAt = null,
    ) {}

    public function id(): ContextSnapshotId
    {
        return $this->id;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function tenant(): TenantContext
    {
        return $this->tenant;
    }

    public function channel(): ChannelContext
    {
        return $this->channel;
    }

    public function customer(): CustomerContext
    {
        return $this->customer;
    }

    public function conversation(): ConversationContext
    {
        return $this->conversation;
    }

    public function branch(): BranchContext
    {
        return $this->branch;
    }

    public function business(): BusinessContext
    {
        return $this->business;
    }

    public function language(): LanguageCode
    {
        return $this->language;
    }

    public function timezone(): TimezoneId
    {
        return $this->timezone;
    }

    public function businessHours(): BusinessHours
    {
        return $this->businessHours;
    }

    public function workingHours(): WorkingHours
    {
        return $this->workingHours;
    }

    public function permissions(): ResolvedPermissions
    {
        return $this->permissions;
    }

    public function capabilities(): AvailableCapabilities
    {
        return $this->capabilities;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function builtAt(): DateTimeImmutable
    {
        return $this->builtAt;
    }

    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(?DateTimeImmutable $now = null): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return ($now ?? new DateTimeImmutable()) > $this->expiresAt;
    }

    public function tenantId(): TenantId
    {
        return $this->tenant->tenantId();
    }

    public function customerId(): ?CustomerId
    {
        return $this->customer->customerId();
    }

    public function conversationId(): ?ConversationRef
    {
        return $this->conversation->conversationId();
    }

    public function branchId(): ?BranchId
    {
        return $this->branch->branchId();
    }

    public function operatingMode(): OperatingMode
    {
        return $this->conversation->operatingMode();
    }

    public function currentOwner(): ConversationOwnerKind
    {
        return $this->conversation->owner();
    }

    public function conversationState(): ?string
    {
        return $this->conversation->state();
    }

    public function customerSummary(): ?string
    {
        return $this->customer->summary();
    }

    public function recentConversationSummary(): ?string
    {
        return $this->conversation->recentSummary();
    }

    public function currentBusinessState(): ?string
    {
        return $this->business->currentStateSummary();
    }

    /**
     * Content fingerprint for audit / cache keys (immutable).
     */
    public function contentHash(): string
    {
        return hash('sha256', implode('|', [
            $this->id->toString(),
            $this->version,
            $this->tenant->tenantId()->toString(),
            $this->channel->channelType()->value,
            $this->channel->externalIdentifier()->toString(),
            $this->customer->customerId()?->toString() ?? '',
            $this->conversation->conversationId()?->toString() ?? '',
            $this->language->toString(),
            $this->timezone->toString(),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'version' => $this->version,
            'tenant_id' => $this->tenant->tenantId()->toString(),
            'channel' => $this->channel->channelType()->value,
            'customer_id' => $this->customer->customerId()?->toString(),
            'conversation_id' => $this->conversation->conversationId()?->toString(),
            'branch_id' => $this->branch->branchId()?->toString(),
            'language' => $this->language->toString(),
            'timezone' => $this->timezone->toString(),
            'operating_mode' => $this->operatingMode()->value,
            'owner' => $this->currentOwner()->value,
            'conversation_state' => $this->conversationState(),
            'permissions' => $this->permissions->keys(),
            'capabilities' => $this->capabilities->all(),
            'metadata' => $this->metadata,
            'built_at' => $this->builtAt->format(DateTimeImmutable::ATOM),
            'expires_at' => $this->expiresAt?->format(DateTimeImmutable::ATOM),
            'content_hash' => $this->contentHash(),
        ];
    }
}
