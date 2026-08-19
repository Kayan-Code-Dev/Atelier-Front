<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Domain\Memory;

/**
 * Tenant-scoped conversation memory preferences (not smart memory engine).
 */
final class TenantConversationMemory
{
    /**
     * @param array<string, scalar|null> $businessPreferences
     */
    public function __construct(
        private readonly string $tenantId,
        private readonly string $preferredLanguage = 'ar',
        private readonly ?string $businessInstructions = null,
        private readonly ?string $customAiInstructions = null,
        private readonly array $businessPreferences = [],
    ) {}

    public function tenantId(): string { return $this->tenantId; }
    public function preferredLanguage(): string { return $this->preferredLanguage; }
    public function businessInstructions(): ?string { return $this->businessInstructions; }
    public function customAiInstructions(): ?string { return $this->customAiInstructions; }
    /** @return array<string, scalar|null> */
    public function businessPreferences(): array { return $this->businessPreferences; }
}
