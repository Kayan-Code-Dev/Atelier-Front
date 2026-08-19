<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\AiSales\DressnMoreSalesContext;
use App\Services\Platform\AiSales\AiSalesIntentDetector;
use App\Services\Platform\AiSales\AiSalesLeadScorer;
use App\Services\Platform\AiSales\AiSalesStateMachine;
use App\Services\Platform\AiSales\DressnMoreLanguageMatcher;
use App\Services\Platform\AiSales\DressnMoreObjectionDetector;
use App\Services\Platform\AiSales\DressnMorePainExtractor;
use App\Services\Platform\AiSales\DressnMorePlanAdvisor;
use App\Services\Platform\AiSales\DressnMoreProfileExtractor;
use App\Services\Platform\AiSales\DressnMoreSalesAgent;
use App\Services\Platform\AiSales\DressnMoreSalesPolicy;
use App\Services\Platform\AiSales\DressnMoreSalesResponder;
use App\Services\Platform\AiSales\DressnMoreSalesTools;
use App\Services\Platform\PlanEntitlementService;
use App\Support\AiSales\AiSalesConversationState;
use App\Support\AiSales\AiSalesObjection;
use App\Support\PlanFeatureCatalog;
use App\Support\PlanFeatureGate;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DressnMoreSalesSprint03Test extends TestCase
{
    private DressnMoreSalesAgent $agent;

    private DressnMoreSalesTools $tools;

    protected function setUp(): void
    {
        parent::setUp();
        $ctx = $this->fakeContext();
        $this->tools = new DressnMoreSalesTools($ctx, new DressnMorePlanAdvisor($ctx), new PlanEntitlementService(new PlanFeatureGate));
        $this->agent = new DressnMoreSalesAgent(
            new DressnMoreSalesPolicy,
            new AiSalesStateMachine,
            new DressnMoreProfileExtractor,
            new DressnMorePainExtractor,
            new DressnMoreObjectionDetector,
            new AiSalesIntentDetector,
            $this->tools,
            new DressnMoreSalesResponder,
            new AiSalesLeadScorer,
            new DressnMoreLanguageMatcher,
        );
    }

    public function test_small_atelier_recommends_free_or_starter_without_professional_upsell(): void
    {
        $result = $this->turn('عندي أتيليه صغير وفرع واحد وأنا لوحدي.');
        $plan = $this->recommendedPlan($result);
        $this->assertContains($plan, ['free', 'starter']);
        $this->assertNotSame('professional', $plan);
        $this->assertStringNotContainsString('Professional', $result['response']);
        $this->assertContains($result['state'], ['DISCOVERY', 'QUALIFICATION', 'RECOMMENDATION']);
    }

    public function test_multi_branch_recommends_professional(): void
    {
        $result = $this->turn('عندي فرعين و7 موظفين.');
        $this->assertSame('professional', $this->recommendedPlan($result));
        $this->assertTrue($this->usedTool($result, 'RecommendPlan'));
        $this->assertContains($result['state'], ['QUALIFICATION', 'RECOMMENDATION']);
    }

    public function test_website_requirement_uses_catalog_and_lowest_plan(): void
    {
        $availability = $this->tools->getFeatureAvailability('website.enabled', 'free');
        $this->assertFalse($availability['available']);
        $this->assertSame('starter', $availability['required_plan']);

        $result = $this->turn('محتاج موقع كمان.');
        $this->assertTrue($this->usedTool($result, 'CheckFeatureAvailability') || $this->usedTool($result, 'RecommendPlan'));
        $this->assertSame('starter', $this->recommendedPlan($result));
    }

    public function test_price_objection_does_not_invent_discount(): void
    {
        $result = $this->turn('$40 غالي.');
        $this->assertSame(AiSalesObjection::Price->value, $result['objection']);
        $this->assertSame('OBJECTION', $result['state']);
        $this->assertStringNotContainsString('هعملك خصم', $result['response']);
        $this->assertStringNotContainsString('خصم 40', $result['response']);
        $this->assertStringNotContainsStringIgnoringCase('discount', $result['response']);
        $this->assertStringNotContainsString('$25', $result['response']);
    }

    public function test_existing_system_asks_what_works_without_attacking(): void
    {
        $result = $this->turn('أنا عندي برنامج بالفعل.');
        $this->assertSame('OBJECTION', $result['state']);
        $this->assertStringContainsStringIgnoringCase('ناقص', $result['response']);
        $this->assertStringNotContainsStringIgnoringCase('the best', $result['response']);
        $this->assertStringNotContainsString('سيء', $result['response']);
    }

    public function test_ready_to_buy_moves_to_checkout_without_discovery(): void
    {
        $result = $this->turn('تمام، عايز أشترك.');
        $this->assertSame('CHECKOUT', $result['state']);
        $this->assertSame('HIGH_PURCHASE_INTENT', $result['purchase_intent']);
        $this->assertSame('checkout', $result['recommended_next_action']);
        $this->assertStringNotContainsString('عندك كام فرع', $result['response']);
    }

    public function test_human_request_pauses_ai(): void
    {
        $result = $this->turn('ممكن حد من المبيعات يكلمني؟');
        $this->assertSame('HUMAN_HANDOFF', $result['state']);
        $this->assertTrue($result['ai_paused']);
        $this->assertTrue($this->usedTool($result, 'RequestHumanHandoff'));
    }

    public function test_unknown_feature_does_not_hallucinate(): void
    {
        $result = $this->turn('هل عندكم تكامل Shopify؟');
        $this->assertTrue($this->usedTool($result, 'CheckFeatureAvailability'));
        $this->assertTrue($result['confidence'] === 'LOW_CONFIDENCE' || ($result['ai_paused'] ?? false));
        $this->assertStringNotContainsStringIgnoringCase('yes we have shopify', $result['response']);
    }

    public function test_does_not_repeat_known_branch_question(): void
    {
        $result = $this->agent->handle([
            'conversation_context' => [
                'messages' => [
                    ['author' => 'customer', 'body' => 'عندي فرعين.'],
                    ['author' => 'customer', 'body' => 'قولي على الباقات.'],
                ],
                'memory' => [
                    'known' => ['branches' => 2],
                    'asked_questions' => ['branches'],
                ],
            ],
            'lead_context' => ['branch_count' => 2],
            'sales_context' => ['state' => 'DISCOVERY'],
        ]);
        $this->assertStringNotContainsString('عندك كام فرع', $result['response']);
        $this->assertSame(2, $result['memory']['known']['branches']);
    }

    public function test_think_it_over_schedules_follow_up(): void
    {
        $result = $this->turn('خليني أفكر وأرجعلك.');
        $this->assertSame('CONSIDERATION', $result['state']);
        $this->assertSame('follow_up', $result['recommended_next_action']);
        $this->assertTrue($this->usedTool($result, 'ScheduleSalesFollowUp'));
        $this->assertTrue((new DressnMoreSalesPolicy)->mayScheduleFollowUp($result['memory'], 0));
        $this->assertFalse((new DressnMoreSalesPolicy)->mayScheduleFollowUp(['state' => 'WON', 'opted_out' => false], 0));
    }

    public function test_state_machine_rejects_random_jumps(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AiSalesStateMachine)->transition(AiSalesConversationState::Won, AiSalesConversationState::Discovery);
    }

    public function test_compare_plans_uses_subscription_source(): void
    {
        $cmp = $this->tools->comparePlans('starter', 'professional');
        $this->assertSame('subscription_system', $cmp['source']);
        $this->assertSame(20.0, $cmp['price_a']);
        $this->assertSame(40.0, $cmp['price_b']);
    }

    public function test_profile_extraction_from_natural_sentence(): void
    {
        $extracted = (new DressnMoreProfileExtractor)->extract('عندي فرعين و6 موظفين وبنعمل حوالي 100 فاتورة بالشهر وبستخدم Excel.');
        $this->assertSame(2, $extracted['branches']);
        $this->assertSame(6, $extracted['users']);
        $this->assertSame(100, $extracted['invoice_volume']);
        $this->assertSame('Excel', $extracted['current_system']);
    }

    /**
     * @return array<string, mixed>
     */
    private function turn(string $body): array
    {
        return $this->agent->handle([
            'conversation_context' => [
                'messages' => [['author' => 'customer', 'body' => $body]],
            ],
            'lead_context' => [],
            'sales_context' => ['state' => 'NEW'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function recommendedPlan(array $result): ?string
    {
        foreach (array_reverse($result['tool_actions']) as $action) {
            if (($action['tool'] ?? '') === 'RecommendPlan') {
                return $action['result']['recommended_plan'] ?? $action['result_summary']['recommended_plan'] ?? null;
            }
        }

        return $result['lead_updates']['interested_plan'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function usedTool(array $result, string $tool): bool
    {
        foreach ($result['tool_actions'] as $action) {
            if (($action['tool'] ?? '') === $tool) {
                return true;
            }
        }

        return false;
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
                    $matrix = PlanFeatureCatalog::defaultMatrix()[$slug] ?? [];
                    $features = [];
                    foreach (PlanFeatureCatalog::definitions() as $def) {
                        $raw = $matrix[$def['key']] ?? null;
                        $included = PlanFeatureCatalog::isBooleanKey($def['key'])
                            ? PlanFeatureCatalog::isEnabledValue(is_bool($raw) ? ($raw ? 'true' : 'false') : ($raw !== null ? (string) $raw : null))
                            : $raw !== null;
                        $features[] = [
                            'key' => $def['key'],
                            'label' => $def['label'],
                            'included' => $included,
                            'value' => $raw,
                        ];
                    }
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
                        'features' => $features,
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
}
