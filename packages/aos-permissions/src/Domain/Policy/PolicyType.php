<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Policy;

enum PolicyType: string
{
    case Business = 'business';
    case Security = 'security';
    case Compliance = 'compliance';
    case Operating = 'operating';
    case Channel = 'channel';
    case Tenant = 'tenant';
}
