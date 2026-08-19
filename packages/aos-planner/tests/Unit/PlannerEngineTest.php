<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Tests\Unit;

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Planner\Application\PlannerEngine;
use DressnMore\Aos\Planner\Domain\Context\PlanningContext;
use DressnMore\Aos\Planner\Domain\Intent\IntentKind;
use DressnMore\Aos\Planner\Domain\Plan\PlanningDecision;
use PHPUnit\Framework\TestCase;

final class PlannerEngineTest extends TestCase
{
    private PlannerEngine $engine;

    protected function setUp(): void
    {
        $bus = new class implements EventBusInterface {
            public function publish(object $event): void {}

            public function subscribe(string $event, string|callable $listener): void {}
        };
        $this->engine = PlannerEngine::createDefault($bus);
    }

    public function test_single_intent_generates_executable_plan(): void
    {
        $plan = $this->engine->plan(PlanningContext::fromMessage(
            'أريد معرفة المتبقي عليّ',
        ));

        $this->assertSame(IntentKind::Single, $plan->intentKind());
        $this->assertSame(PlanningDecision::ReadyToExecute, $plan->decision());
        $this->assertNotEmpty($plan->tasks());
        $this->assertContains('GetOutstandingBalance', $plan->toolCandidates());
    }

    public function test_multi_intent_plan(): void
    {
        $plan = $this->engine->plan(PlanningContext::fromMessage(
            'أريد معرفة المتبقي عليّ وأحجز بروفة',
        ));

        $this->assertSame(IntentKind::Multi, $plan->intentKind());
        $this->assertSame(PlanningDecision::ReadyToExecute, $plan->decision());
        $this->assertGreaterThanOrEqual(2, count($plan->goals()));
        $this->assertContains('GetOutstandingBalance', $plan->toolCandidates());
        $this->assertContains('CreateReservation', $plan->toolCandidates());
    }

    public function test_conflicting_intent_requires_clarification(): void
    {
        $plan = $this->engine->plan(PlanningContext::fromMessage(
            'أريد أحجز موعد بروفة وألغي الحجز',
        ));

        $this->assertSame(IntentKind::Conflicting, $plan->intentKind());
        $this->assertSame(PlanningDecision::ClarificationRequired, $plan->decision());
        $this->assertNotNull($plan->clarificationPrompt());
    }

    public function test_unknown_intent_requires_clarification(): void
    {
        $plan = $this->engine->plan(PlanningContext::fromMessage(
            'asdf qwer zxcv',
        ));

        $this->assertSame(IntentKind::Unknown, $plan->intentKind());
        $this->assertSame(PlanningDecision::ClarificationRequired, $plan->decision());
    }

    public function test_clarification_for_ambiguous_weak_signal(): void
    {
        // Very short / weak — may be ambiguous depending on catalog; use unknown-like noise with one weak arabic particle
        $plan = $this->engine->plan(PlanningContext::fromMessage('هل'));

        $this->assertTrue(in_array($plan->decision(), [
            PlanningDecision::ClarificationRequired,
            PlanningDecision::ReadyToExecute,
        ], true));
        // "هل" alone matches ask_knowledge keyword "هل يمكن"? "هل" is substring of "هل يمكن" - mb_strpos('هل', 'هل يمكن') is false; mb_strpos('هل', 'هل') - keyword is 'هل يمكن' so no match → Unknown
        $this->assertSame(IntentKind::Unknown, $plan->intentKind());
    }

    public function test_execution_plan_is_immutable_shape(): void
    {
        $plan = $this->engine->plan(PlanningContext::fromMessage('وين طلبي'));
        $array = $plan->toArray();

        $this->assertArrayHasKey('goals', $array);
        $this->assertArrayHasKey('tasks', $array);
        $this->assertArrayHasKey('tool_candidates', $array);
        $this->assertArrayHasKey('dependencies', $array);
        $this->assertArrayHasKey('confidence', $array);
        $this->assertSame(IntentKind::Single, $plan->intentKind());
        $this->assertTrue($plan->isExecutable());
    }

    public function test_transfer_human_escalates(): void
    {
        $plan = $this->engine->plan(PlanningContext::fromMessage(
            'أريد التحدث مع موظف بشري',
        ));

        $this->assertSame(PlanningDecision::EscalationRequired, $plan->decision());
        $this->assertTrue($plan->humanEscalation());
    }
}
