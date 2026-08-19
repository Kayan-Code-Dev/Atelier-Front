<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Workflow\Application\WorkflowEngine;
use DressnMore\Aos\Workflow\Contracts\WorkflowEngineInterface;
use DressnMore\Aos\Workflow\Domain\Condition\ConditionEngine;
use DressnMore\Aos\Workflow\Domain\Execution\TaskDispatcher;
use DressnMore\Aos\Workflow\Domain\Execution\WorkflowExecutor;
use DressnMore\Aos\Workflow\Domain\Factory\WorkflowFactory;
use DressnMore\Aos\Workflow\Domain\Monitoring\WorkflowMetrics;
use DressnMore\Aos\Workflow\Domain\Monitoring\WorkflowMonitor;
use DressnMore\Aos\Workflow\Domain\Repository\WorkflowRepositoryInterface;
use DressnMore\Aos\Workflow\Domain\Retry\RetryManager;
use DressnMore\Aos\Workflow\Domain\Trigger\TriggerEngine;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowBuilder;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowManager;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowRegistry;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowScheduler;
use DressnMore\Aos\Workflow\Infrastructure\Bootstrap\BuiltinWorkflowCatalogBootstrap;
use DressnMore\Aos\Workflow\Infrastructure\InMemory\InMemoryWorkflowRepository;
use DressnMore\Aos\Workflow\Infrastructure\InMemory\StubTaskDispatcher;
use DressnMore\Aos\Workflow\Module\WorkflowModule;
use Illuminate\Support\ServiceProvider;

final class AosWorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WorkflowRepositoryInterface::class, InMemoryWorkflowRepository::class);
        $this->app->singleton(WorkflowRegistry::class);
        $this->app->singleton(WorkflowFactory::class);
        $this->app->singleton(WorkflowBuilder::class);
        $this->app->singleton(WorkflowManager::class);
        $this->app->singleton(WorkflowScheduler::class);
        $this->app->singleton(TriggerEngine::class);
        $this->app->singleton(ConditionEngine::class);
        $this->app->singleton(RetryManager::class);
        $this->app->singleton(TaskDispatcher::class, StubTaskDispatcher::class);
        $this->app->singleton(WorkflowExecutor::class);
        $this->app->singleton(WorkflowMonitor::class);
        $this->app->singleton(WorkflowMetrics::class);
        $this->app->singleton(BuiltinWorkflowCatalogBootstrap::class);
        $this->app->singleton(WorkflowEngine::class);
        $this->app->singleton(WorkflowEngineInterface::class, WorkflowEngine::class);
        $this->app->singleton(WorkflowModule::class);
    }

    public function boot(): void
    {
        $this->app->make(BuiltinWorkflowCatalogBootstrap::class)->seed();

        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        if (! $registry->has('aos.workflow')) {
            $registry->add($this->app->make(WorkflowModule::class));
        }
    }
}
