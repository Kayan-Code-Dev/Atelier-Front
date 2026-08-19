<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Permission;

/**
 * Dimensions of the Permission Model (orthogonal, composable).
 */
enum PermissionModelDimension: string
{
    case RoleBased = 'role_based';
    case CapabilityBased = 'capability_based';
    case PolicyBased = 'policy_based';
    case ApprovalBased = 'approval_based';
    case RiskBased = 'risk_based';
}
