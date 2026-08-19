<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Tests\Unit;

use DressnMore\Aos\Workflow\Application\WorkflowEngine;
use DressnMore\Aos\Workflow\Domain\Condition\ConditionEngine;
use DressnMore\Aos\Workflow\Domain\Execution\WorkflowExecutor;
use DressnMore\Aos\Workflow\Domain\Factory\WorkflowFactory;
use DressnMore\Aos\Workflow\Domain\Monitoring\WorkflowMetrics;
use DressnMore\Aos\Workflow\Domain\Monitoring\WorkflowMonitor;
use DressnMore\Aos\Workflow\Domain\Retry\RetryManager;
use DressnMore\Aos\Workflow\Domain\Retry\RetryPolicyType;
use DressnMore\Aos\Workflow\Domain\Task\TaskDefinition;
use DressnMore\Aos\Workflow\Domain\Task\TaskType;
use DressnMore\Aos\Workflow\Domain\Trigger\TriggerEngine;
use DressnMore\Aos\Workflow\Domain\Trigger\TriggerType;
use DressnMore\Aos\Workflow\Domain\Variables\VariableScope;
use DressnMore\Aos\Workflow\Domain\Variables\WorkflowVariables;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowLifecycleStatus;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowRegistry;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowType;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowVersion;
use DressnMore\Aos\Workflow\Infrastructure\InMemory\InMemoryWorkflowRepository;
use DressnMore\Aos\Workflow\Infrastructure\InMemory\StubTaskDispatcher;
use PHPUnit\Framework\TestCase;

final class WorkflowEngineTest extends TestCase
{
    private WorkflowEngine $engine;

    protected function setUp(): void
    {
        $repo = new InMemoryWorkflowRepository();
        $registry = new WorkflowRegistry($repo);
        $factory = new WorkflowFactory();
        $registry->register($factory->create(
            'incoming',
            WorkflowType::Conversation,
            TriggerType::IncomingMessage,
            [
                new TaskDefinition('seq-1', TaskType::SequentialTask),
                new TaskDefinition('par-1', TaskType::ParallelTask),
            ],
        ));

        $this->engine = new WorkflowEngine(
            new TriggerEngine(),
            $registry,
            new WorkflowExecutor(new StubTaskDispatcher(), new RetryManager()),
            new WorkflowMonitor(),
            new WorkflowMetrics(),
        );
    }

    public function test_workflow_creation_and_trigger_resolution(): void
    {
        $result = $this->engine->run(['trigger' => 'incoming_message']);
        $this->assertTrue($result->success());
        $this->assertContains('seq-1', $result->completedTasks());
    }

    public function test_condition_evaluation(): void
    {
        $engine = new ConditionEngine();
        $this->assertTrue($engine->evaluate(['a' => 1], ['a' => 1]));
        $this->assertFalse($engine->evaluate(['a' => 1], ['a' => 2]));
    }

    public function test_sequential_and_parallel_execution_trace(): void
    {
        $result = $this->engine->run(['trigger' => 'incoming_message']);
        $this->assertContains('execute_tasks', $result->trace());
        $this->assertContains('parallel:par-1', $result->trace());
    }

    public function test_retry_policies(): void
    {
        $retry = new RetryManager();
        $this->assertSame(0, $retry->nextDelaySeconds(RetryPolicyType::Immediate, 1));
        $this->assertSame(4, $retry->nextDelaySeconds(RetryPolicyType::ExponentialBackoff, 3));
        $this->assertSame(-1, $retry->nextDelaySeconds(RetryPolicyType::ManualRetry, 2));
        $this->assertSame(-2, $retry->nextDelaySeconds(RetryPolicyType::DeadLetter, 5));
    }

    public function test_workflow_variables_scopes(): void
    {
        $vars = new WorkflowVariables();
        $vars->set(VariableScope::Tenant, 'tier', 'gold');
        $vars->set(VariableScope::Runtime, 'attempt', 1);
        $this->assertSame('gold', $vars->get(VariableScope::Tenant, 'tier'));
        $this->assertSame(1, $vars->get(VariableScope::Runtime, 'attempt'));
    }

    public function test_workflow_lifecycle_and_versioning_objects(): void
    {
        $this->assertSame('published', WorkflowLifecycleStatus::Published->value);
        $this->assertSame(2, WorkflowVersion::initial()->next()->value());
    }
}
