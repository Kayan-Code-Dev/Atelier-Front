<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Domain\Subscription;

enum SubscriptionPlan: string
{
    case Basic = 'basic';
    case Professional = 'professional';
    case Enterprise = 'enterprise';
}
