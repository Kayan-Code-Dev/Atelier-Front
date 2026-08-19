<?php

declare(strict_types=1);

namespace Tests\Unit\Aos;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Workflow\Architecture\WorkflowScopeDecision;
use DressnMore\Aos\Workflow\Contracts\WorkflowEngineInterface;
use Tests\TestCase;

final class AosWorkflowEngineTest extends TestCase
{
    public function test_workflow_module_registered(): void
    {
        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        $this->assertTrue($registry->has('aos.workflow'));
    }

    public function test_engine_bound_and_executable(): void
    {
        /** @var WorkflowEngineInterface $engine */
        $engine = $this->app->make(WorkflowEngineInterface::class);
        $result = $engine->run(['trigger' => 'incoming_message']);
        $this->assertTrue($result->success());
    }

    public function test_sprint12_scope_excludes_db_and_models(): void
    {
        $excluded = WorkflowScopeDecision::excludedConcerns();
        $this->assertContains('database', $excluded);
        $this->assertContains('laravel_models', $excluded);
        $this->assertContains('business_logic', $excluded);
    }
}
