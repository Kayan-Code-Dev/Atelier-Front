<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Policy;

use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationContext;

/**
 * Resolves which policy types are in scope for the current request (tenant/channel markers).
 */
final class PolicyResolver
{
    public function __construct(
        private readonly PolicyRegistryInterface $registry,
    ) {}

    /**
     * @return list<PolicyDefinition>
     */
    public function resolveApplicable(AuthorizationContext $context): array
    {
        $all = $this->registry->all();
        $tenantId = $context->request()->tenantId();

        // Sprint 5: all enabled policies are candidates; tenant attribute is metadata only.
        $context->markStage('policy_resolver:'.count($all).($tenantId ? ':tenant' : ''));

        return $all;
    }
}
