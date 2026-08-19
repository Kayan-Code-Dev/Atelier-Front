<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Contracts;

use DressnMore\Aos\TenantAi\Domain\Subscription\SubscriptionEntitlement;
use DressnMore\Aos\TenantAi\Domain\Subscription\SubscriptionPlan;

interface SubscriptionProviderInterface
{
    public function resolve(SubscriptionPlan|string $plan): SubscriptionEntitlement;
}
