<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Provider;

use DressnMore\Aos\Ai\Domain\Capability\ModelCapability;
use DressnMore\Aos\Ai\Domain\Policies\ProviderPolicyEngine;
use DressnMore\Aos\Ai\Domain\Request\AiRequest;

final class ProviderResolver
{
    public function __construct(
        private readonly ProviderRegistryInterface $registry,
        private readonly ProviderPolicyEngine $policies,
    ) {}

    /**
     * @param  list<ModelCapability>  $required
     * @return list<ProviderDescriptor>
     */
    public function resolveCandidates(AiRequest $request, array $required): array
    {
        $out = [];
        foreach ($this->registry->all() as $provider) {
            if (! $this->policies->allowsProvider($provider, $request)) {
                continue;
            }
            $out[] = $provider;
        }

        return $out;
    }

    public function resolve(ProviderId $id): ?ProviderDescriptor
    {
        return $this->registry->get($id);
    }
}
