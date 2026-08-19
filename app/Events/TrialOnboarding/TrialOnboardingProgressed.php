<?php

namespace App\Events\TrialOnboarding;

final class TrialOnboardingProgressed
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $userId,
        public readonly string $eventName,
        public readonly ?string $stepKey,
        public readonly array $metadata,
    ) {}
}
