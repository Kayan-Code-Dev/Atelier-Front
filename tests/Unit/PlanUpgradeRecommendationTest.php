<?php

namespace Tests\Unit;

use App\Support\PlanFeatureCatalog;
use Tests\TestCase;

class PlanUpgradeRecommendationTest extends TestCase
{
    public function test_lowest_plan_recommendations(): void
    {
        $this->assertSame('starter', PlanFeatureCatalog::minimumPlanFor('website.enabled'));
        $this->assertSame('starter', PlanFeatureCatalog::minimumPlanFor('marketplace.enabled'));
        $this->assertSame('professional', PlanFeatureCatalog::minimumPlanFor('ai_assistant.enabled'));
        $this->assertSame('business', PlanFeatureCatalog::minimumPlanFor('factory.enabled'));
    }
}
