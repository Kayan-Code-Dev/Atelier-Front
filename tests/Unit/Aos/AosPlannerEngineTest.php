<?php

declare(strict_types=1);

namespace Tests\Unit\Aos;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Planner\Application\PlannerEngine;
use DressnMore\Aos\Planner\Architecture\PlannerScopeDecision;
use DressnMore\Aos\Planner\Domain\Context\PlanningContext;
use DressnMore\Aos\Planner\Domain\Intent\IntentKind;
use DressnMore\Aos\Planner\Domain\Plan\PlanningDecision;
use Tests\TestCase;

final class AosPlannerEngineTest extends TestCase
{
    public function test_planner_module_is_registered(): void
    {
        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        $this->assertTrue($registry->has('aos.planner'));
    }

    public function test_engine_plans_single_intent(): void
    {
        /** @var PlannerEngine $engine */
        $engine = $this->app->make(PlannerEngine::class);
        $plan = $engine->plan(PlanningContext::fromMessage('أريد معرفة الرصيد المتبقي'));

        $this->assertSame(IntentKind::Single, $plan->intentKind());
        $this->assertSame(PlanningDecision::ReadyToExecute, $plan->decision());
    }

    public function test_sprint6_scope_excludes_llm_and_tools(): void
    {
        $excluded = PlannerScopeDecision::excludedConcerns();
        $this->assertContains('openai', $excluded);
        $this->assertContains('tool_execution', $excluded);
        $this->assertContains('whatsapp', $excluded);
        $this->assertSame(['dressnmore/aos-planner'], PlannerScopeDecision::includedPackages());
    }
}
