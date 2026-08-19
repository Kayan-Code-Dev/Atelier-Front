<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Model;

use DressnMore\Aos\Ai\Domain\Capability\ModelCapability;
use DressnMore\Aos\Ai\Domain\Policies\ProviderPolicyEngine;
use DressnMore\Aos\Ai\Domain\Provider\ProviderDescriptor;
use DressnMore\Aos\Ai\Domain\Request\AiRequest;

final class ModelResolver
{
    public function __construct(
        private readonly ModelRegistryInterface $registry,
        private readonly ProviderPolicyEngine $policies,
    ) {}

    /**
     * @param  list<ProviderDescriptor>  $providers
     * @param  list<ModelCapability>  $required
     * @return list<ModelDescriptor>
     */
    public function resolveCandidates(AiRequest $request, array $providers, array $required): array
    {
        $providerIds = array_map(static fn (ProviderDescriptor $p): string => $p->id()->toString(), $providers);
        $out = [];
        foreach ($this->registry->all() as $model) {
            if (! in_array($model->providerId()->toString(), $providerIds, true)) {
                continue;
            }
            if (! $model->supportsAll($required)) {
                continue;
            }
            if (! $this->policies->allowsModel($model, $request)) {
                continue;
            }
            $out[] = $model;
        }

        return $out;
    }

    public function resolve(ModelId $id): ?ModelDescriptor
    {
        return $this->registry->get($id);
    }
}
