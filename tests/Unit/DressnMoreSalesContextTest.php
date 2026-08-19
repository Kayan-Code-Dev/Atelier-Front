<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Platform\AiSales\DressnMoreSalesContextBuilder;
use App\Support\PlanFeatureCatalog;
use PHPUnit\Framework\TestCase;

final class DressnMoreSalesContextTest extends TestCase
{
    public function test_context_uses_catalog_plans_and_dressnmore_business_type(): void
    {
        $ctx = new DressnMoreSalesContextBuilder;

        $this->assertSame('DressnMore', $ctx->businessType());
        $this->assertSame('DressnMore', $ctx->productIdentity());
        $this->assertSame('subscription_plans', $ctx->salesPolicies()['pricing_source']);
        $this->assertContains('customer_asks_for_human', $ctx->handoffRules()['triggers']);
        $this->assertSame(PlanFeatureCatalog::publicPlanSlugs(), [
            PlanFeatureCatalog::PLAN_FREE,
            PlanFeatureCatalog::PLAN_STARTER,
            PlanFeatureCatalog::PLAN_PROFESSIONAL,
            PlanFeatureCatalog::PLAN_BUSINESS,
        ]);
    }
}
