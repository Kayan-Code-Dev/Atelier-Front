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
use App\Services\Platform\AiSales\Identity\CustomerIdentity;
use App\Services\Platform\AiSales\Identity\CustomerIdentityContextFormatter;
use App\Services\Platform\AiSales\Identity\CustomerNameExtractor;
use App\Services\Platform\AiSales\Identity\DemoAccountIdentityService;
use App\Services\Platform\AiSales\Identity\PhoneIdentity;
use App\Services\Platform\PlanEntitlementService;
use App\Support\PlanFeatureCatalog;
use App\Support\PlanFeatureGate;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\WhatsAppWebhookPayloadParser;
use PHPUnit\Framework\TestCase;

final class CustomerIdentityFoundationTest extends TestCase
{
    private DressnMoreSalesAgent $agent;

    /** @var list<array{author: string, body: string}> */
    private array $thread = [];

    protected function setUp(): void
    {
        parent::setUp();
        $ctx = $this->fakeContext();
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
            new DressnMoreQualificationGate,
        );
    }

    public function test_explicit_name_and_business_extraction(): void
    {
        $extracted = (new CustomerNameExtractor)->extract('أنا أحمد من أتيليه لورين');
        $this->assertSame('أحمد', $extracted['customer_name']);
        $this->assertSame('أتيليه لورين', $extracted['business_name']);
        $this->assertSame(CustomerIdentity::SOURCE_EXPLICIT_USER, $extracted['name_source']);
    }

    public function test_price_sentence_is_not_a_name(): void
    {
        $extracted = (new CustomerNameExtractor)->extract('عايز أعرف السعر');
        $this->assertNull($extracted['customer_name']);
        $extracted = (new CustomerNameExtractor)->extract('أنا من أتيليه XYZ');
        $this->assertNull($extracted['customer_name']);
        $this->assertSame('أتيليه XYZ', $extracted['business_name']);
    }

    public function test_push_name_is_used_when_no_explicit_name(): void
    {
        $result = $this->agent->handle([
            'conversation_context' => [
                'messages' => [['author' => 'customer', 'body' => 'مساء الخير']],
                'push_name' => 'محمد علي',
            ],
            'lead_context' => ['phone' => '201001234567'],
            'sales_context' => ['state' => 'NEW'],
        ]);
        $this->assertSame('محمد علي', $result['customer_identity']['customer_name']);
        $this->assertSame(CustomerIdentity::SOURCE_WHATSAPP_PUSH_NAME, $result['customer_identity']['name_source']);
        $this->assertStringContainsString('محمد', $result['response']);
        $this->assertStringNotContainsString('أنادي حضرتك', $result['response']);
    }

    public function test_no_name_does_not_invent_one(): void
    {
        $result = $this->turn('هاي');
        $this->assertNull($result['customer_identity']['customer_name']);
        $this->assertStringContainsString('أهلا', $result['response']);
        $this->assertStringNotContainsString('يا User', $result['response']);
        $this->assertStringNotContainsString('يا Unknown', $result['response']);
        $this->assertDoesNotMatchRegularExpression('/أهلاً بك يا/u', $result['response']);
        $this->assertStringContainsString('أنادي حضرتك', $result['response']);
    }

    public function test_customer_says_name_during_conversation(): void
    {
        $first = $this->turn('هاي');
        $second = $this->continue($first, 'أنا أحمد من أتيليه لورين');
        $this->assertSame('أحمد', $second['customer_identity']['customer_name']);
        $this->assertSame('أتيليه لورين', $second['customer_identity']['business_name']);
        $this->assertSame(CustomerIdentity::SOURCE_EXPLICIT_USER, $second['customer_identity']['name_source']);
        $this->assertSame('أحمد', $second['lead_updates']['name']);
        $this->assertSame('أتيليه لورين', $second['lead_updates']['business']);
    }

    public function test_returning_customer_keeps_identity(): void
    {
        $first = $this->agent->handle([
            'conversation_context' => [
                'messages' => [['author' => 'customer', 'body' => 'أنا أحمد']],
            ],
            'lead_context' => [],
            'sales_context' => ['state' => 'NEW'],
        ]);
        $later = $this->agent->handle([
            'conversation_context' => [
                'messages' => [
                    ['author' => 'customer', 'body' => 'أنا أحمد'],
                    ['author' => 'ai_agent', 'body' => $first['response']],
                    ['author' => 'customer', 'body' => 'مساء الخير'],
                ],
                'memory' => $first['memory'],
            ],
            'lead_context' => ['name' => 'أحمد', 'identity' => ['name_source' => CustomerIdentity::SOURCE_EXPLICIT_USER]],
            'sales_context' => ['state' => $first['state'], 'memory' => $first['memory']],
        ]);
        $this->assertSame('أحمد', $later['customer_identity']['customer_name']);
        $this->assertStringContainsString('أحمد', $later['response']);
        $this->assertStringNotContainsString('أنادي حضرتك', $later['response']);
    }

    public function test_explicit_name_outranks_push_name(): void
    {
        $result = $this->agent->handle([
            'conversation_context' => [
                'messages' => [['author' => 'customer', 'body' => 'بالمناسبة اسمي محمد أحمد']],
                'push_name' => 'Wa User',
                'memory' => [
                    'identity' => [
                        'customer_name' => 'Wa User',
                        'name_source' => CustomerIdentity::SOURCE_WHATSAPP_PUSH_NAME,
                    ],
                ],
            ],
            'lead_context' => ['name' => 'Wa User'],
            'sales_context' => ['state' => 'DISCOVERY'],
        ]);
        $this->assertSame('محمد أحمد', $result['customer_identity']['customer_name']);
        $this->assertSame(CustomerIdentity::SOURCE_EXPLICIT_USER, $result['customer_identity']['name_source']);
    }

    public function test_does_not_repeatedly_ask_for_name_once_known(): void
    {
        $first = $this->agent->handle([
            'conversation_context' => [
                'messages' => [['author' => 'customer', 'body' => 'هاي']],
            ],
            'lead_context' => ['name' => 'أحمد', 'identity' => ['name_source' => CustomerIdentity::SOURCE_EXPLICIT_USER]],
            'sales_context' => ['state' => 'NEW'],
        ]);
        $this->assertStringNotContainsString('أنادي حضرتك', $first['response']);
        $second = $this->continue($first, 'السعر كام؟');
        $this->assertStringNotContainsString('أنادي حضرتك', $second['response']);
    }

    public function test_session_reset_ignores_old_qualification_questions(): void
    {
        $result = $this->agent->handle([
            'conversation_context' => [
                'messages' => [
                    ['id' => 1, 'author' => 'customer', 'body' => 'رشحلي أنسب باقة', 'at' => '2026-08-01T10:00:00+00:00'],
                    ['id' => 2, 'author' => 'ai_agent', 'body' => 'عندك كام فرع حاليًا؟', 'at' => '2026-08-01T10:00:05+00:00'],
                    ['id' => 3, 'author' => 'customer', 'body' => 'هاي', 'at' => '2026-08-17T20:00:01+00:00'],
                ],
                'memory' => [
                    'asked_questions' => [],
                    'pending_slot' => null,
                    'state' => 'NEW',
                    'session_reset_at' => '2026-08-17T20:00:00+00:00',
                    'session_reset_message_id' => 2,
                    'identity' => [
                        'customer_name' => 'أحمد',
                        'name_source' => CustomerIdentity::SOURCE_EXPLICIT_USER,
                    ],
                    'known' => [
                        'customer_name' => 'أحمد',
                    ],
                ],
            ],
            'lead_context' => [
                'name' => 'أحمد',
                'identity' => ['name_source' => CustomerIdentity::SOURCE_EXPLICIT_USER],
            ],
            'sales_context' => ['state' => 'NEW'],
        ]);
        $this->assertSame('أحمد', $result['customer_identity']['customer_name']);
        $this->assertNotContains('branches', $result['memory']['asked_questions'] ?? []);
        $this->assertNull($result['memory']['pending_slot'] ?? null);
        $this->assertFalse((new DressnMoreQualificationGate)->isBranchQuestion((string) $result['response']));
        $this->assertStringContainsString('أحمد', $result['response']);
    }

    public function test_greeting_after_unanswered_branch_question_does_not_stay_stuck(): void
    {
        $first = $this->turn('رشحلي أنسب باقة');
        $this->assertTrue((new DressnMoreQualificationGate)->isBranchQuestion((string) $first['response']));
        $second = $this->continue($first, 'هاي');
        $this->assertFalse((new DressnMoreQualificationGate)->isBranchQuestion((string) $second['response']));
        $this->assertNull($second['memory']['pending_slot'] ?? null);
        $this->assertStringContainsString('أهلا', $second['response']);
    }

    public function test_openai_context_marks_confirmed_and_unknown(): void
    {
        $identity = CustomerIdentity::fromArray([
            'customer_id' => 12,
            'customer_name' => 'أحمد محمد',
            'phone_number' => '+201001234567',
            'business_name' => 'Atelier Elegance',
            'business_type' => 'atelier',
            'name_source' => CustomerIdentity::SOURCE_EXPLICIT_USER,
            'confirmed' => ['customer_name' => 'أحمد محمد', 'branches' => 2],
            'unknown' => ['city'],
        ]);
        $block = (new CustomerIdentityContextFormatter)->promptBlock($identity);
        $this->assertStringContainsString('Name: أحمد محمد', $block);
        $this->assertStringContainsString('WhatsApp: +201001234567', $block);
        $this->assertStringContainsString('Business: Atelier Elegance', $block);
        $this->assertStringContainsString('customer_name = أحمد محمد', $block);
        $this->assertStringContainsString('- city', $block);
        $this->assertStringContainsString('Unknown field does not mean you must ask', $block);
        $this->assertStringContainsString('Never invent a customer name', $block);
    }

    public function test_demo_email_arabic_english_and_duplicates(): void
    {
        $svc = new DemoAccountIdentityService;
        $arabic = $svc->uniqueEmail('أحمد محمد');
        $this->assertSame('ahmed.mohamed@demo.dressnmore.com', $arabic);
        $english = $svc->uniqueEmail('Ahmed Mohamed');
        $this->assertSame('ahmed.mohamed@demo.dressnmore.com', $english);
        $taken = ['ahmed.mohamed@demo.dressnmore.com' => true];
        $dup = $svc->uniqueEmail('Ahmed Mohamed', static fn (string $email): bool => isset($taken[$email]));
        $this->assertSame('ahmed.mohamed2@demo.dressnmore.com', $dup);
        $business = $svc->propose(CustomerIdentity::fromArray([
            'customer_name' => 'أحمد محمد',
            'business_name' => 'Atelier Elegance',
            'name_source' => CustomerIdentity::SOURCE_EXPLICIT_USER,
            'name_confidence' => CustomerIdentity::CONFIDENCE_HIGH,
        ]));
        $this->assertSame('Atelier Elegance', $business['tenant_name']);
        $this->assertSame('أحمد محمد', $business['admin_name']);
        $missingBusiness = $svc->propose(CustomerIdentity::fromArray([
            'customer_name' => 'أحمد محمد',
            'name_source' => CustomerIdentity::SOURCE_EXPLICIT_USER,
            'name_confidence' => CustomerIdentity::CONFIDENCE_HIGH,
        ]));
        $this->assertSame('أحمد محمد', $missingBusiness['tenant_name']);
        $this->assertNull($svc->emailLocalPart('User123'));
        $this->assertNull($svc->emailLocalPart('201001234567'));
    }

    public function test_phone_match_key_is_stable(): void
    {
        $this->assertTrue(PhoneIdentity::matches('01001234567', '+20 100 123 4567'));
        $this->assertSame('+201001234567', PhoneIdentity::display('01001234567'));
    }

    public function test_whatsapp_parser_extracts_push_name(): void
    {
        $parsed = (new WhatsAppWebhookPayloadParser)->extractInboundMessages([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => '123'],
                        'contacts' => [[
                            'profile' => ['name' => 'Ahmed'],
                            'wa_id' => '201001234567',
                        ]],
                        'messages' => [[
                            'id' => 'wamid.1',
                            'from' => '201001234567',
                            'timestamp' => '1710000000',
                            'type' => 'text',
                            'text' => ['body' => 'السلام عليكم'],
                        ]],
                    ],
                ]],
            ]],
        ]);
        $this->assertSame('Ahmed', $parsed[0]['message']['push_name']);
        $this->assertSame('201001234567', $parsed[0]['message']['from']);
    }

    /**
     * @return array<string, mixed>
     */
    private function turn(string $body): array
    {
        $this->thread = [['author' => 'customer', 'body' => $body]];

        return $this->agent->handle([
            'conversation_context' => ['messages' => $this->thread],
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
            'lead_context' => array_merge($previous['lead_updates'] ?? [], [
                'identity' => $previous['memory']['identity'] ?? [],
            ]),
            'sales_context' => ['state' => $previous['state'] ?? 'DISCOVERY', 'memory' => $previous['memory'] ?? []],
        ]);
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
                        'price' => 20.0,
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
