<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Tests\Unit\Platform;

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Planner\Application\Platform\CapabilityMatcher;
use DressnMore\Aos\Planner\Application\Platform\ExecutionPlanBuilder;
use DressnMore\Aos\Planner\Application\Platform\IntentAnalyzer;
use DressnMore\Aos\Planner\Application\Platform\PermissionValidator;
use DressnMore\Aos\Planner\Application\Platform\PlatformPlannerEngine;
use DressnMore\Aos\Planner\Application\Platform\PolicyEvaluator;
use DressnMore\Aos\Planner\Application\Platform\SubscriptionValidator;
use DressnMore\Aos\Planner\Application\Platform\ToolSelector;
use DressnMore\Aos\Planner\Contracts\IntentAnalyzerInterface;
use DressnMore\Aos\Planner\Contracts\PlanBuilderInterface;
use DressnMore\Aos\Planner\Domain\Platform\AnalyzedIntent;
use DressnMore\Aos\Planner\Domain\Platform\CapabilityMatch;
use DressnMore\Aos\Planner\Domain\Platform\PlatformExecutionPlan;
use DressnMore\Aos\Planner\Domain\Platform\PlatformPlanningContext;
use DressnMore\Aos\Planner\Domain\Platform\PlanningStatus;
use DressnMore\Aos\Planner\Domain\Platform\PolicyEvaluation;
use DressnMore\Aos\Planner\Domain\Platform\ToolSelection;
use DressnMore\Aos\Planner\Infrastructure\InMemoryExecutionPlanRepository;
use PHPUnit\Framework\TestCase;

final class PlatformPlannerEngineTest extends TestCase
{
    private EventBusInterface $bus;

    protected function setUp(): void
    {
        $this->bus = new class implements EventBusInterface {
            /** @var list<object> */
            public array $events = [];

            public function publish(object $event): void
            {
                $this->events[] = $event;
            }

            public function subscribe(string $event, string|callable $listener): void {}
        };
    }

    public function test_book_reservation_builds_plan_requiring_approval(): void
    {
        $engine = PlatformPlannerEngine::createDefault($this->bus);
        $plan = $engine->plan($this->ctx('احجز فستان'));

        $this->assertSame('BookReservation', $plan->intent());
        $this->assertSame('Create Reservation', $plan->goal());
        $this->assertContains('CheckAvailability', $plan->selectedTools());
        $this->assertContains('CreateReservation', $plan->selectedTools());
        $this->assertSame(PlanningStatus::RequiresApproval, $plan->status());
        $this->assertTrue($plan->isReadyForGateway());
        $this->assertNotEmpty($plan->planId());
    }

    public function test_create_customer_intent(): void
    {
        $engine = PlatformPlannerEngine::createDefault($this->bus);
        $plan = $engine->plan($this->ctx('أضف عميل'));

        $this->assertSame('CreateCustomer', $plan->intent());
        $this->assertContains('CreateCustomer', $plan->selectedTools());
        $this->assertSame(PlanningStatus::RequiresApproval, $plan->status());
    }

    public function test_sales_summary_on_professional(): void
    {
        $engine = PlatformPlannerEngine::createDefault($this->bus);
        $plan = $engine->plan($this->ctx('كم مبيعات اليوم', subscription: 'professional'));

        $this->assertSame('SalesSummary', $plan->intent());
        $this->assertSame(['GenerateReport'], $plan->selectedTools());
        $this->assertSame(PlanningStatus::Ready, $plan->status());
    }

    public function test_unknown_intent_rejected(): void
    {
        $engine = PlatformPlannerEngine::createDefault($this->bus);
        $plan = $engine->plan($this->ctx('xyz nonsense 12345'));

        $this->assertSame(PlanningStatus::Rejected, $plan->status());
        $this->assertSame('Unknown', $plan->intent());
    }

    public function test_conflicting_intent_rejected(): void
    {
        $engine = PlatformPlannerEngine::createDefault($this->bus);
        $plan = $engine->plan($this->ctx('احجز موعد وألغي الحجز'));

        $this->assertSame(PlanningStatus::Rejected, $plan->status());
        $this->assertFalse($plan->isReadyForGateway());
    }

    public function test_missing_capability_rejected(): void
    {
        $engine = PlatformPlannerEngine::createDefault($this->bus, ['Customer.Search']);
        $plan = $engine->plan($this->ctx('احجز فستان'));

        $this->assertSame(PlanningStatus::Rejected, $plan->status());
        $this->assertStringContainsString('missing_capabilities', $plan->rejectionReason());
    }

    public function test_unregistered_tool_rejected(): void
    {
        $engine = PlatformPlannerEngine::createDefault($this->bus);
        $plan = $engine->plan($this->ctx('احجز فستان', availableTools: ['SearchCustomer']));

        $this->assertSame(PlanningStatus::Rejected, $plan->status());
        $this->assertTrue(
            str_contains($plan->rejectionReason(), 'tool_unavailable')
            || str_contains($plan->rejectionReason(), 'plan_without_tools')
            || str_contains($plan->rejectionReason(), 'empty_plan')
        );
    }

    public function test_policy_blocked_tool_rejected(): void
    {
        $repo = new InMemoryExecutionPlanRepository();
        $engine = new PlatformPlannerEngine(
            new IntentAnalyzer(),
            new CapabilityMatcher(),
            new ToolSelector(),
            new PolicyEvaluator(['CreateReservation']),
            new PermissionValidator(),
            new SubscriptionValidator(),
            new ExecutionPlanBuilder(),
            $repo,
            $this->bus,
        );
        $plan = $engine->plan($this->ctx('احجز فستان'));

        $this->assertSame(PlanningStatus::Rejected, $plan->status());
        $this->assertStringContainsString('tool_policy_blocked', $plan->rejectionReason());
    }

    public function test_subscription_denied_for_report_on_basic(): void
    {
        $engine = PlatformPlannerEngine::createDefault($this->bus);
        $plan = $engine->plan($this->ctx('كم مبيعات اليوم', subscription: 'basic'));

        $this->assertSame(PlanningStatus::Rejected, $plan->status());
        $this->assertStringContainsString('subscription_denied', $plan->rejectionReason());
    }

    public function test_permission_denied(): void
    {
        $engine = PlatformPlannerEngine::createDefault($this->bus);
        $plan = $engine->plan($this->ctx(
            'احجز فستان',
            grantedTools: ['CheckAvailability'],
        ));

        $this->assertSame(PlanningStatus::Rejected, $plan->status());
        $this->assertStringContainsString('permission_denied', $plan->rejectionReason());
    }

    public function test_plan_without_tools_rejected(): void
    {
        $analyzer = new class implements IntentAnalyzerInterface {
            public function analyze(PlatformPlanningContext $context): AnalyzedIntent
            {
                return new AnalyzedIntent('EmptyPlan', 1.0, ['x'], [], ['Reports.Read'], null, 'none', true);
            }
        };
        $engine = new PlatformPlannerEngine(
            $analyzer,
            new CapabilityMatcher(),
            new ToolSelector(),
            new PolicyEvaluator(),
            new PermissionValidator(),
            new SubscriptionValidator(),
            new ExecutionPlanBuilder(),
            new InMemoryExecutionPlanRepository(),
            $this->bus,
        );
        $plan = $engine->plan($this->ctx('anything'));

        $this->assertSame(PlanningStatus::Rejected, $plan->status());
        $this->assertTrue(
            str_contains($plan->rejectionReason(), 'plan_without_tools')
            || str_contains($plan->rejectionReason(), 'empty_plan')
            || str_contains($plan->rejectionReason(), 'tool_unavailable')
        );
    }

    public function test_conflicting_tools_rejected(): void
    {
        $analyzer = new class implements IntentAnalyzerInterface {
            public function analyze(PlatformPlanningContext $context): AnalyzedIntent
            {
                return new AnalyzedIntent(
                    'ConflictTools',
                    1.0,
                    ['x'],
                    ['CreateReservation', 'CancelReservation'],
                    ['Reservation.Create', 'Reservation.Update'],
                    null,
                    'none',
                    true,
                );
            }
        };
        $engine = new PlatformPlannerEngine(
            $analyzer,
            new CapabilityMatcher(),
            new ToolSelector(),
            new PolicyEvaluator(),
            new PermissionValidator(),
            new SubscriptionValidator(),
            new ExecutionPlanBuilder(),
            new InMemoryExecutionPlanRepository(),
            $this->bus,
        );
        $plan = $engine->plan($this->ctx('conflict'));

        $this->assertSame(PlanningStatus::Rejected, $plan->status());
        $this->assertStringContainsString('conflicting_tools', $plan->rejectionReason());
    }

    public function test_intent_requires_approval(): void
    {
        $engine = PlatformPlannerEngine::createDefault($this->bus);
        $plan = $engine->plan($this->ctx('احجز فستان'));

        $this->assertSame(PlanningStatus::RequiresApproval, $plan->status());
        $this->assertNotEmpty($plan->requiredApprovals());
    }

    public function test_build_failure_emits_failed_status(): void
    {
        $builder = new class implements PlanBuilderInterface {
            public function build(
                PlatformPlanningContext $context,
                AnalyzedIntent $intent,
                CapabilityMatch $capabilities,
                ToolSelection $tools,
                PolicyEvaluation $policy,
            ): PlatformExecutionPlan {
                return new PlatformExecutionPlan(
                    'plan_fail',
                    $context->tenantId(),
                    $context->conversationId(),
                    'x',
                    $intent->intent(),
                    [],
                    [],
                    [],
                    [],
                    0.0,
                    'low',
                    PlanningStatus::Failed,
                    '2026-01-01T00:00:00+00:00',
                    'build_failed',
                );
            }
        };
        $engine = new PlatformPlannerEngine(
            new IntentAnalyzer(),
            new CapabilityMatcher(),
            new ToolSelector(),
            new PolicyEvaluator(),
            new PermissionValidator(),
            new SubscriptionValidator(),
            $builder,
            new InMemoryExecutionPlanRepository(),
            $this->bus,
        );
        $plan = $engine->plan($this->ctx('أضف عميل'));

        $this->assertSame(PlanningStatus::Failed, $plan->status());
    }

    public function test_plan_persisted_in_repository(): void
    {
        $repo = new InMemoryExecutionPlanRepository();
        $engine = new PlatformPlannerEngine(
            new IntentAnalyzer(),
            new CapabilityMatcher(),
            new ToolSelector(),
            new PolicyEvaluator(),
            new PermissionValidator(),
            new SubscriptionValidator(),
            new ExecutionPlanBuilder(),
            $repo,
            $this->bus,
        );
        $plan = $engine->plan($this->ctx('أضف عميل', subscription: 'professional'));
        $found = $repo->find($plan->planId());

        $this->assertNotNull($found);
        $this->assertSame($plan->planId(), $found->planId());
        $this->assertCount(1, $repo->forTenant('tenant_demo'));
    }

    public function test_never_executes_tools_only_identifiers(): void
    {
        $engine = PlatformPlannerEngine::createDefault($this->bus);
        $plan = $engine->plan($this->ctx('احجز فستان'));

        foreach ($plan->selectedTools() as $tool) {
            $this->assertIsString($tool);
            $this->assertDoesNotMatchRegularExpression('/https?:/', $tool);
        }
        $this->assertArrayHasKey('selectedTools', $plan->toArray());
    }

    /**
     * @param list<string> $grantedTools
     * @param list<string> $availableTools
     */
    private function ctx(
        string $message,
        string $subscription = 'professional',
        array $grantedTools = [],
        array $availableTools = [],
    ): PlatformPlanningContext {
        return new PlatformPlanningContext(
            $message,
            'tenant_demo',
            'conv_1',
            'user_1',
            'branch_1',
            'ar',
            $subscription,
            [],
            [],
            $grantedTools,
            $availableTools,
            [],
            'corr_test',
        );
    }
}
