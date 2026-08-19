<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Pipeline;

use DressnMore\Aos\Context\Domain\Business\BusinessContext;
use DressnMore\Aos\Context\Domain\Channel\ChannelContext;
use DressnMore\Aos\Context\Domain\Conversation\ConversationContext;
use DressnMore\Aos\Context\Domain\Conversation\ConversationRef;
use DressnMore\Aos\Context\Domain\Customer\CustomerContext;
use DressnMore\Aos\Context\Domain\Identity\ChannelIdentity;
use DressnMore\Aos\Context\Domain\Identity\CustomerIdentity;
use DressnMore\Aos\Context\Domain\Identity\IdentityMatchResult;
use DressnMore\Aos\Context\Domain\Localization\BusinessHours;
use DressnMore\Aos\Context\Domain\Localization\LanguageCode;
use DressnMore\Aos\Context\Domain\Localization\TimezoneId;
use DressnMore\Aos\Context\Domain\Localization\WorkingHours;
use DressnMore\Aos\Context\Domain\Permission\AvailableCapabilities;
use DressnMore\Aos\Context\Domain\Permission\ResolvedPermissions;
use DressnMore\Aos\Context\Domain\Snapshot\ContextSnapshot;
use DressnMore\Aos\Context\Domain\Tenant\BranchContext;
use DressnMore\Aos\Context\Domain\Tenant\BranchId;
use DressnMore\Aos\Context\Domain\Tenant\TenantContext;
use DressnMore\Aos\Context\Domain\Tenant\TenantId;

/**
 * Mutable bag accumulated while the Context Pipeline runs.
 */
final class ResolutionBag
{
    private PipelineStage $stage = PipelineStage::IncomingMessage;

    private ?ChannelIdentity $channelIdentity = null;

    private ?ChannelContext $channelContext = null;

    private ?TenantId $tenantId = null;

    private ?TenantContext $tenantContext = null;

    private ?CustomerIdentity $customerIdentity = null;

    private ?IdentityMatchResult $identityMatch = null;

    private ?CustomerContext $customerContext = null;

    private ?ConversationRef $conversationRef = null;

    private ?ConversationContext $conversationContext = null;

    private ?BranchId $branchId = null;

    private ?BranchContext $branchContext = null;

    private ?LanguageCode $language = null;

    private ?TimezoneId $timezone = null;

    private ?BusinessHours $businessHours = null;

    private ?WorkingHours $workingHours = null;

    private ?BusinessContext $businessContext = null;

    private ?ResolvedPermissions $permissions = null;

    private ?AvailableCapabilities $capabilities = null;

    private ?ContextSnapshot $snapshot = null;

    private ?string $failureReason = null;

    /** @var array<string, scalar|null> */
    private array $metadata = [];

    /** @var list<PipelineStage> */
    private array $completedStages = [];

    public function __construct(
        private readonly IncomingMessage $message,
    ) {}

    public function message(): IncomingMessage
    {
        return $this->message;
    }

    public function stage(): PipelineStage
    {
        return $this->stage;
    }

    public function advance(PipelineStage $stage): void
    {
        $this->stage = $stage;
        $this->completedStages[] = $stage;
    }

    /**
     * @return list<PipelineStage>
     */
    public function completedStages(): array
    {
        return $this->completedStages;
    }

    public function fail(string $reason): void
    {
        $this->failureReason = $reason;
        $this->stage = PipelineStage::Failed;
        $this->completedStages[] = PipelineStage::Failed;
    }

    public function failureReason(): ?string
    {
        return $this->failureReason;
    }

    public function isFailed(): bool
    {
        return $this->stage === PipelineStage::Failed;
    }

    public function setChannelIdentity(ChannelIdentity $identity): void
    {
        $this->channelIdentity = $identity;
    }

    public function channelIdentity(): ?ChannelIdentity
    {
        return $this->channelIdentity;
    }

    public function setChannelContext(ChannelContext $context): void
    {
        $this->channelContext = $context;
    }

    public function channelContext(): ?ChannelContext
    {
        return $this->channelContext;
    }

    public function setTenantId(TenantId $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function tenantId(): ?TenantId
    {
        return $this->tenantId;
    }

    public function setTenantContext(TenantContext $context): void
    {
        $this->tenantContext = $context;
    }

    public function tenantContext(): ?TenantContext
    {
        return $this->tenantContext;
    }

    public function setCustomerIdentity(?CustomerIdentity $identity): void
    {
        $this->customerIdentity = $identity;
    }

    public function customerIdentity(): ?CustomerIdentity
    {
        return $this->customerIdentity;
    }

    public function setIdentityMatch(IdentityMatchResult $result): void
    {
        $this->identityMatch = $result;
    }

    public function identityMatch(): ?IdentityMatchResult
    {
        return $this->identityMatch;
    }

    public function setCustomerContext(CustomerContext $context): void
    {
        $this->customerContext = $context;
    }

    public function customerContext(): ?CustomerContext
    {
        return $this->customerContext;
    }

    public function setConversationRef(?ConversationRef $ref): void
    {
        $this->conversationRef = $ref;
    }

    public function conversationRef(): ?ConversationRef
    {
        return $this->conversationRef;
    }

    public function setConversationContext(ConversationContext $context): void
    {
        $this->conversationContext = $context;
    }

    public function conversationContext(): ?ConversationContext
    {
        return $this->conversationContext;
    }

    public function setBranchId(?BranchId $branchId): void
    {
        $this->branchId = $branchId;
    }

    public function branchId(): ?BranchId
    {
        return $this->branchId;
    }

    public function setBranchContext(BranchContext $context): void
    {
        $this->branchContext = $context;
    }

    public function branchContext(): ?BranchContext
    {
        return $this->branchContext;
    }

    public function setLanguage(LanguageCode $language): void
    {
        $this->language = $language;
    }

    public function language(): ?LanguageCode
    {
        return $this->language;
    }

    public function setTimezone(TimezoneId $timezone): void
    {
        $this->timezone = $timezone;
    }

    public function timezone(): ?TimezoneId
    {
        return $this->timezone;
    }

    public function setBusinessHours(BusinessHours $hours): void
    {
        $this->businessHours = $hours;
    }

    public function businessHours(): ?BusinessHours
    {
        return $this->businessHours;
    }

    public function setWorkingHours(WorkingHours $hours): void
    {
        $this->workingHours = $hours;
    }

    public function workingHours(): ?WorkingHours
    {
        return $this->workingHours;
    }

    public function setBusinessContext(BusinessContext $context): void
    {
        $this->businessContext = $context;
    }

    public function businessContext(): ?BusinessContext
    {
        return $this->businessContext;
    }

    public function setPermissions(ResolvedPermissions $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function permissions(): ?ResolvedPermissions
    {
        return $this->permissions;
    }

    public function setCapabilities(AvailableCapabilities $capabilities): void
    {
        $this->capabilities = $capabilities;
    }

    public function capabilities(): ?AvailableCapabilities
    {
        return $this->capabilities;
    }

    public function setSnapshot(ContextSnapshot $snapshot): void
    {
        $this->snapshot = $snapshot;
    }

    public function snapshot(): ?ContextSnapshot
    {
        return $this->snapshot;
    }

    /**
     * @param  array<string, scalar|null>  $metadata
     */
    public function mergeMetadata(array $metadata): void
    {
        $this->metadata = array_merge($this->metadata, $metadata);
    }

    /**
     * @return array<string, scalar|null>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
