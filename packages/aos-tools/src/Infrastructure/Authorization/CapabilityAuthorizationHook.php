<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Infrastructure\Authorization;

use DressnMore\Aos\Tools\Domain\Contracts\AuthorizationDecision;
use DressnMore\Aos\Tools\Domain\Contracts\ToolAuthorizationHookInterface;
use DressnMore\Aos\Tools\Domain\Policies\ToolCapabilityPolicy;
use DressnMore\Aos\Tools\Domain\Request\ToolRequest;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;

/**
 * Default authorization: capability + permission intersection; no Permission Engine.
 */
final class CapabilityAuthorizationHook implements ToolAuthorizationHookInterface
{
    public function __construct(
        private readonly ToolCapabilityPolicy $policy = new ToolCapabilityPolicy(),
    ) {}

    public function authorize(ToolRequest $request, ToolManifest $manifest): AuthorizationDecision
    {
        $context = $request->executionContext();

        if (! $this->policy->hasRequiredCapabilities($manifest, $context)) {
            return AuthorizationDecision::Deny;
        }

        if (! $this->policy->hasRequiredPermissions($manifest, $context)) {
            return AuthorizationDecision::Deny;
        }

        return AuthorizationDecision::Allow;
    }
}
