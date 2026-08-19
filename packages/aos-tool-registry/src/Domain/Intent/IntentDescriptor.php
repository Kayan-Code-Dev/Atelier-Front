<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Domain\Intent;

/**
 * Maps a business intent to an ordered tool plan + capability/policy/approval.
 *
 * @phpstan-type Step array{tool:string,capability?:string}
 */
final class IntentDescriptor
{
    /**
     * @param list<Step> $toolPlan
     * @param list<string> $requiredCapabilities
     */
    public function __construct(
        private readonly string $intent,
        private readonly array $toolPlan,
        private readonly array $requiredCapabilities,
        private readonly ?string $policy = null,
        private readonly ?string $approval = null,
        private readonly string $ownerDomain = 'platform',
    ) {}

    public function intent(): string { return $this->intent; }
    /** @return list<Step> */
    public function toolPlan(): array { return $this->toolPlan; }
    /** @return list<string> */
    public function requiredCapabilities(): array { return $this->requiredCapabilities; }
    public function policy(): ?string { return $this->policy; }
    public function approval(): ?string { return $this->approval; }
    public function ownerDomain(): string { return $this->ownerDomain; }

    /**
     * @return list<string>
     */
    public function toolNames(): array
    {
        return array_values(array_map(
            static fn (array $step): string => (string) $step['tool'],
            $this->toolPlan,
        ));
    }
}
