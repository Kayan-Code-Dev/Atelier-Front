<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Knowledge;

enum VisibilityPolicy: string
{
    case PublicGlobal = 'public_global';
    case TenantOnly = 'tenant_only';
    case DepartmentOnly = 'department_only';
    case PrivateOwner = 'private_owner';
    case SharedSelected = 'shared_selected';
}

enum RetentionPolicy: string
{
    case Short = 'short';
    case Standard = 'standard';
    case Long = 'long';
    case Permanent = 'permanent';
}
