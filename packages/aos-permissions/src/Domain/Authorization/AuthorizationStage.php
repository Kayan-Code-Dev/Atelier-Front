<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Authorization;

enum AuthorizationStage: string
{
    case Requested = 'authorization_requested';
    case LoadContext = 'load_context';
    case ResolveOperatingMode = 'resolve_operating_mode';
    case ResolveCapabilities = 'resolve_capabilities';
    case ResolvePermissions = 'resolve_permissions';
    case EvaluatePolicies = 'evaluate_policies';
    case EvaluateRisk = 'evaluate_risk';
    case NeedApproval = 'need_approval';
    case Decision = 'decision';
}
