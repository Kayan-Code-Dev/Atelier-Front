<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Fallback;

use DressnMore\Aos\Ai\Domain\Provider\ProviderDescriptor;
use DressnMore\Aos\Ai\Domain\Provider\ProviderId;
use DressnMore\Aos\Ai\Domain\Selection\ProviderSelection;

final class FallbackManager
{
    /**
     * @param  list<ProviderSelection>  $ranked
     */
    public function next(array $ranked, ?ProviderId $failedProviderId): ?ProviderSelection
    {
        foreach ($ranked as $selection) {
            if ($failedProviderId !== null && $selection->provider()->id()->equals($failedProviderId)) {
                continue;
            }

            return $selection;
        }

        return null;
    }

    /**
     * @param  list<ProviderDescriptor>  $providers
     * @return list<ProviderDescriptor>
     */
    public function orderByPriority(array $providers): array
    {
        usort($providers, static fn (ProviderDescriptor $a, ProviderDescriptor $b): int => $a->priority() <=> $b->priority());

        return $providers;
    }
}
