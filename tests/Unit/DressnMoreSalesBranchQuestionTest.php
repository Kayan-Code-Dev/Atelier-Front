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
use App\Services\Platform\AiSales\DressnMoreQualificationGate;
use App\Services\Platform\AiSales\DressnMoreSalesAgent;
use App\Services\Platform\AiSales\DressnMoreSalesPolicy;
use App\Services\Platform\AiSales\DressnMoreSalesResponder;
use App\Services\Platform\AiSales\DressnMoreSalesTools;
use App\Services\Platform\PlanEntitlementService;
use App\Support\AiSales\AiSalesIntent;
use App\Support\PlanFeatureCatalog;
use App\Support\PlanFeatureGate;
use PHPUnit\Framework\TestCase;

final class DressnMoreSalesBranchQuestionTest extends TestCase
{
    private DressnMoreSalesAgent $agent;

    private DressnMoreQualificationGate $gate;

    /** @var list<array{author: string, body: string}> */
    private array $thread = [];

    protected function setUp(): void
    {
        parent::setUp();
        $ctx = $this->fakeContext();
        $this->gate = new DressnMoreQualificationGate;
        $this->agent = new DressnMoreSalesAgent(
            new DressnMoreSalesPolicy,
            new AiSalesStateMachine,
            new DressnMoreProfileExtractor,
            new DressnMorePainExtractor,
            new DressnMoreObjectionDetector,
            new AiSalesIntentDetector,
            new DressnMoreSalesTools($ctx, new DressnMorePlanAdvisor($ctx), new PlanEntitlementService(new PlanFeatureGate)),
            new DressnMoreSalesResponder,
            new AiSalesLeadScorer,
            new DressnMoreLanguageMatcher,
            $this->gate,
        );
    }

    public function test_greeting_does_not_ask_branch_count(): void
    {
        $result = $this->turn('هاي');
        $this->assertSame(AiSalesIntent::Greeting->value, $result['intent']);
        $this->assertFalse($this->asksBranches($result['response']));
        $this->assertStringContainsString('أهلا', $result['response']);
    }

    public function test_pricing_answers_without_asking_branches(): void
    {
        $result = $this->turn('السعر كام؟');
        $this->assertSame(AiSalesIntent::PricingInquiry->value, $result['intent']);
        $this->assertFalse($this->asksBranches($result['response']));
        $this->assertTrue($this->usedTool($result, 'RecommendPlan'));
    }

    public function test_feature_question_does_not_ask_branches(): void
    {
        $result = $this->turn('النظام بيعمل إيه؟');
        $this->assertSame(AiSalesIntent::FeatureInquiry->value, $result['intent']);
        $this->assertFalse($this->asksBranches($result['response']));
        $this->assertNotSame('', trim($result['response']));
    }

    public function test_demo_request_does_not_ask_branches(): void
    {
        $result = $this->turn('عايز أحجز ديمو');
        $this->assertSame('DEMO_REQUESTED', $result['state']);
        $this->assertFalse($this->asksBranches($result['response']));
    }

    public function test_existing_branch_answer_is_stored_and_not_reasked(): void
    {
        $first = $this->turn('عندي فرعين');
        $this->assertSame(2, $first['memory']['known']['branches']);
        $this->assertFalse($this->asksBranches($first['response']));

        $second = $this->continue($first, 'قولي على الأسعار');
        $this->assertSame(2, $second['memory']['known']['branches']);
        $this->assertFalse($this->asksBranches($second['response']));
    }

    public function test_unanswered_branch_question_then_greeting_does_not_repeat(): void
    {
        $asked = $this->agent->handle([
            'conversation_context' => [
                'messages' => [
                    ['author' => 'customer', 'body' => 'رشحلي أنسب باقة'],
                    ['author' => 'ai_agent', 'body' => 'عندك كام فرع حاليًا؟'],
                    ['author' => 'customer', 'body' => 'هاي'],
                ],
                'memory' => [
                    'asked_questions' => ['branches'],
                    'pending_slot' => 'branches',
                    'state' => 'QUALIFICATION',
                ],
            ],
            'lead_context' => [],
            'sales_context' => ['state' => 'QUALIFICATION'],
        ]);
        $this->assertFalse($this->asksBranches($asked['response']));
        $this->assertNull($asked['memory']['pending_slot'] ?? null);
        $this->assertStringContainsString('أهلا', $asked['response']);
    }

    public function test_short_answer_after_branch_question_is_captured(): void
    {
        $asked = $this->agent->handle([
            'conversation_context' => [
                'messages' => [
                    ['author' => 'ai_agent', 'body' => 'ممكن أعرف عندك كام فرع؟'],
                    ['author' => 'customer', 'body' => 'فرع واحد'],
                ],
                'memory' => ['asked_questions' => ['branches'], 'pending_slot' => 'branches', 'state' => 'DISCOVERY'],
            ],
            'lead_context' => [],
            'sales_context' => ['state' => 'DISCOVERY'],
        ]);
        $this->assertSame(1, $asked['memory']['known']['branches']);
        $this->assertFalse($this->asksBranches($asked['response']));
        $this->assertContains('branches', $asked['memory']['asked_questions']);
    }

    public function test_price_objection_does_not_repeat_branch_question(): void
    {
        $result = $this->agent->handle([
            'conversation_context' => [
                'messages' => [
                    ['author' => 'ai_agent', 'body' => 'ممكن أعرف عندك كام فرع؟'],
                    ['author' => 'customer', 'body' => 'الأسعار غالية'],
                ],
                'memory' => ['asked_questions' => ['branches'], 'state' => 'DISCOVERY'],
            ],
            'lead_context' => [],
            'sales_context' => ['state' => 'DISCOVERY'],
        ]);
        $this->assertSame('OBJECTION', $result['state']);
        $this->assertFalse($this->asksBranches($result['response']));
    }

    public function test_unclear_acknowledgement_does_not_default_to_branches(): void
    {
        $hello = $this->turn('هاي');
        $result = $this->continue($hello, 'تمام');
        $this->assertFalse($this->asksBranches($result['response']));
    }

    public function test_support_intent_does_not_ask_branches(): void
    {
        $result = $this->turn('عندي مشكلة في الحساب');
        $this->assertSame(AiSalesIntent::Support->value, $result['intent']);
        $this->assertFalse($this->asksBranches($result['response']));
    }

    public function test_unknown_intent_does_not_default_to_branch_qualification(): void
    {
        $result = $this->turn('ممكن نكمل بعدين');
        $this->assertFalse($this->asksBranches($result['response']));
    }

    public function test_plan_fit_may_ask_branches_once_when_required(): void
    {
        $result = $this->turn('رشحلي أنسب باقة');
        $this->assertTrue($this->asksBranches($result['response']));
        $this->assertContains('branches', $result['memory']['asked_questions']);

        $again = $this->continue($result, 'ماشي');
        $this->assertFalse($this->asksBranches($again['response']));
    }

    public function test_semantic_branch_question_variants_are_treated_as_one_slot(): void
    {
        foreach ([
            'كام فرع عندك؟',
            'عندك كام فرع؟',
            'عدد الفروع كام؟',
            'الأتيليه فيه كام فرع؟',
            'حضرتك عندك كام فرع؟',
        ] as $variant) {
            $this->assertTrue($this->gate->isBranchQuestion($variant), $variant);
        }
    }

    public function test_multi_turn_conversation_never_falls_back_to_branches(): void
    {
        $turn = $this->turn('هاي');
        $this->assertFalse($this->asksBranches($turn['response']));
        foreach ([
            'النظام بيعمل إيه؟',
            'السعر كام؟',
            'عندي فرع واحد',
            'الأسعار غالية',
            'عايز أحجز ديمو',
            'تمام',
            'ممكن حد يكلمني؟',
        ] as $body) {
            $turn = $this->continue($turn, $body);
            $this->assertFalse($this->asksBranches($turn['response']), $body);
        }
        $this->assertSame(1, $turn['memory']['known']['branches']);
    }

    public function test_gate_rejects_unknown_as_reason_to_ask(): void
    {
        $this->assertFalse($this->gate->mayAskSlot('branches', false, false, false, false, false));
        $this->assertTrue($this->gate->mayAskSlot('branches', false, false, true, true, false));
        $this->assertFalse($this->gate->mayAskSlot('branches', true, false, true, true, false));
        $this->assertFalse($this->gate->mayAskSlot('branches', false, true, true, true, false));
    }

    /**
     * @return array<string, mixed>
     */
    private function turn(string $body): array
    {
        $this->thread = [['author' => 'customer', 'body' => $body]];

        return $this->agent->handle([
            'conversation_context' => [
                'messages' => $this->thread,
            ],
            'lead_context' => [],
            'sales_context' => ['state' => 'NEW'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $previous
     * @return array<string, mixed>
     */
    private function continue(array $previous, string $body): array
    {
        $this->thread[] = ['author' => 'ai_agent', 'body' => (string) $previous['response']];
        $this->thread[] = ['author' => 'customer', 'body' => $body];

        return $this->agent->handle([
            'conversation_context' => [
                'messages' => $this->thread,
                'memory' => $previous['memory'] ?? [],
            ],
            'lead_context' => $previous['lead_updates'] ?? [],
            'sales_context' => ['state' => $previous['state'] ?? 'DISCOVERY', 'memory' => $previous['memory'] ?? []],
        ]);
    }

    private function asksBranches(string $text): bool
    {
        return $this->gate->isBranchQuestion($text);
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
