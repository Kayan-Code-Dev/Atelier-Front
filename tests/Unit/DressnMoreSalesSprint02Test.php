<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\AiSales\DressnMoreSalesContext;
use App\Services\Platform\AiSales\AiSalesIntentDetector;
use App\Services\Platform\AiSales\AiSalesLeadScorer;
use App\Services\Platform\AiSales\DressnMorePlanAdvisor;
use App\Services\Platform\AiSales\DressnMoreSalesContextBuilder;
use App\Services\Platform\AiSales\DressnMoreSalesTools;
use App\Services\Platform\PlanEntitlementService;
use App\Support\AiSales\AiSalesIntent;
use App\Support\PlanFeatureCatalog;
use App\Support\PlanFeatureGate;
use PHPUnit\Framework\TestCase;

final class DressnMoreSalesSprint02Test extends TestCase
{
    public function test_context_never_hardcodes_pricing_source(): void
    {
        $ctx = new DressnMoreSalesContextBuilder;

        $this->assertSame('subscription_plans', $ctx->salesPolicies()['pricing_source']);
        $this->assertSame(['live_business_data', 'subscription_plan_system', 'approved_knowledge_base', 'ai_reasoning'], $ctx->knowledgePriority());
        $this->assertSame(PlanFeatureCatalog::PLAN_STARTER, $ctx->upgradeMapping()['website.enabled']);
        $this->assertSame(PlanFeatureCatalog::PLAN_PROFESSIONAL, $ctx->upgradeMapping()['ai_assistant.enabled']);
        $this->assertSame(PlanFeatureCatalog::PLAN_BUSINESS, $ctx->upgradeMapping()['factory.enabled']);
    }

    public function test_feature_tool_minimum_plan_matches_entitlement_catalog(): void
    {
        $entitlements = new PlanEntitlementService(new PlanFeatureGate);
        $this->assertSame('starter', $entitlements->getRequiredPlan('website.enabled'));
        $this->assertSame('professional', $entitlements->getRequiredPlan('ai_assistant.enabled'));
        $this->assertSame('business', $entitlements->getRequiredPlan('factory.enabled'));
        $this->assertSame($entitlements->getRequiredPlan('website.enabled'), PlanFeatureCatalog::minimumPlanFor('website.enabled'));
    }

    public function test_plans_tool_uses_live_context_prices_not_stale_hardcoded_values(): void
    {
        $context = $this->fakeContext();
        $tools = new DressnMoreSalesTools($context, new DressnMorePlanAdvisor($context), new PlanEntitlementService(new PlanFeatureGate));
        $starter = $tools->getPlanDetails('starter');

        $this->assertNotNull($starter);
        $this->assertSame(20.0, $starter['price']);
        $this->assertNotSame(25.0, $starter['price']);
        $this->assertSame('subscription_system', $tools->recommendPlan(['branch_count' => 1, 'user_count' => 1])['pricing_source']);
    }

    public function test_recommends_free_or_starter_for_small_atelier(): void
    {
        $advisor = new DressnMorePlanAdvisor($this->fakeContext());
        $small = $advisor->recommend(['branch_count' => 1, 'user_count' => 1]);
        $this->assertContains($small['recommended_plan'], ['free', 'starter']);

        $withWebsite = $advisor->recommend([
            'branch_count' => 1,
            'user_count' => 1,
            'desired_features' => ['website'],
        ]);
        $this->assertSame('starter', $withWebsite['recommended_plan']);
        $this->assertSame('starter', $withWebsite['required_plan_for_requested_features']);
        $this->assertNull($this->hardCodedPrice($withWebsite));
    }

    public function test_recommends_professional_for_two_branches_and_seven_users(): void
    {
        $result = (new DressnMorePlanAdvisor($this->fakeContext()))->recommend([
            'branch_count' => 2,
            'user_count' => 7,
            'desired_features' => ['website', 'advanced ai'],
        ]);

        $this->assertSame('professional', $result['recommended_plan']);
    }

    public function test_factory_requirement_recommends_business(): void
    {
        $result = (new DressnMorePlanAdvisor($this->fakeContext()))->recommend([
            'branch_count' => 1,
            'user_count' => 1,
            'desired_features' => ['factory'],
        ]);

        $this->assertSame('business', $result['recommended_plan']);
        $this->assertSame('business', $result['required_plan_for_requested_features']);
    }

    public function test_lead_score_uses_signals_not_message_count_only(): void
    {
        $scorer = new AiSalesLeadScorer;
        $low = $scorer->score(['engagement_count' => 20]);
        $high = $scorer->score([
            'asked_price' => true,
            'asked_plans' => true,
            'asked_payment' => true,
            'asked_demo' => true,
            'requested_trial' => true,
            'branch_count' => 2,
        ]);

        $this->assertTrue($high['value'] > $low['value']);
        $this->assertSame('hot', $high['band']);
        $this->assertNotEmpty($high['reasons']);
    }

    public function test_detects_trial_and_human_intents(): void
    {
        $detector = new AiSalesIntentDetector;
        $trial = $detector->detect('عايز أجرب النظام');
        $human = $detector->detect('I want to speak to a human');

        $this->assertNotNull($trial);
        $this->assertSame(AiSalesIntent::TrialRequest, $trial['intent']);
        $this->assertNotNull($human);
        $this->assertSame(AiSalesIntent::HumanRequest, $human['intent']);
    }

    private function fakeContext(): DressnMoreSalesContext
    {
        return new class implements DressnMoreSalesContext
        {
            public function businessType(): string { return 'DressnMore'; }
            public function productIdentity(): string { return 'DressnMore'; }
            public function productDescription(): string { return 'SaaS'; }
            public function plans(): array
            {
                $out = [];
                foreach (PlanFeatureCatalog::publicPlanSlugs() as $slug) {
                    $out[] = [
                        'slug' => $slug,
                        'name' => ucfirst($slug),
                        'price' => match ($slug) {
                            'free' => 0.0,
                            'starter' => 20.0,
                            'professional' => 40.0,
                            default => 80.0,
                        },
                        'currency' => 'USD',
                        'billing_period' => 'monthly',
                        'description' => null,
                        'limits' => [],
                        'features' => [],
                        'upgrade_to' => null,
                    ];
                }

                return $out;
            }
            public function salesPolicies(): array { return ['pricing_source' => 'subscription_plans']; }
            public function handoffRules(): array { return []; }
            public function toArray(): array { return []; }
            public function trialPolicy(): array { return []; }
            public function demoProcess(): array { return []; }
            public function paymentProcess(): array { return []; }
            public function contactRules(): array { return []; }
            public function markets(): array { return ['EG']; }
            public function languages(): array { return ['ar']; }
            public function upgradeMapping(): array { return []; }
            public function knowledgePriority(): array { return []; }
        };
    }

    private function hardCodedPrice(array $result): mixed
    {
        return str_contains((string) $result['reason'], '$25') ? 25 : null;
    }
}
