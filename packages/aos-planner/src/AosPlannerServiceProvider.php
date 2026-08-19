<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Planner\Application\PlannerEngine;
use DressnMore\Aos\Planner\Application\PlanningPipelineFactory;
use DressnMore\Aos\Planner\Application\Platform\CapabilityMatcher;
use DressnMore\Aos\Planner\Application\Platform\ExecutionPlanBuilder;
use DressnMore\Aos\Planner\Application\Platform\IntentAnalyzer;
use DressnMore\Aos\Planner\Application\Platform\PermissionValidator;
use DressnMore\Aos\Planner\Application\Platform\PlanningContextProvider;
use DressnMore\Aos\Planner\Application\Platform\PlatformPlannerEngine;
use DressnMore\Aos\Planner\Application\Platform\PolicyEvaluator;
use DressnMore\Aos\Planner\Application\Platform\SubscriptionValidator;
use DressnMore\Aos\Planner\Application\Platform\ToolSelector;
use DressnMore\Aos\Planner\Contracts\CapabilityMatcherInterface;
use DressnMore\Aos\Planner\Contracts\ExecutionPlanRepositoryInterface;
use DressnMore\Aos\Planner\Contracts\IntentAnalyzerInterface;
use DressnMore\Aos\Planner\Contracts\PlanBuilderInterface;
use DressnMore\Aos\Planner\Contracts\PlannerInterface;
use DressnMore\Aos\Planner\Contracts\PlanningContextProviderInterface;
use DressnMore\Aos\Planner\Contracts\PolicyEvaluatorInterface;
use DressnMore\Aos\Planner\Contracts\ToolSelectorInterface;
use DressnMore\Aos\Planner\Domain\Clarification\ClarificationEngine;
use DressnMore\Aos\Planner\Domain\Confidence\ConfidenceEvaluator;
use DressnMore\Aos\Planner\Domain\Escalation\EscalationEvaluator;
use DressnMore\Aos\Planner\Domain\Goal\GoalResolver;
use DressnMore\Aos\Planner\Domain\Intent\IntentCatalog;
use DressnMore\Aos\Planner\Domain\Intent\IntentResolver;
use DressnMore\Aos\Planner\Domain\Plan\ExecutionPlanner;
use DressnMore\Aos\Planner\Domain\Plan\PlanningValidator;
use DressnMore\Aos\Planner\Domain\Pipeline\PlanningPipeline;
use DressnMore\Aos\Planner\Domain\Platform\PlatformIntentCatalog;
use DressnMore\Aos\Planner\Domain\Policy\PlanningPolicy;
use DressnMore\Aos\Planner\Domain\Strategy\PlanningStrategy;
use DressnMore\Aos\Planner\Domain\Task\TaskPlanner;
use DressnMore\Aos\Planner\Infrastructure\InMemoryExecutionPlanRepository;
use DressnMore\Aos\Planner\Module\PlannerModule;
use Illuminate\Support\ServiceProvider;

/**
 * Registers AI Planner contracts and the aos.planner module.
 */
final class AosPlannerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IntentCatalog::class);
        $this->app->singleton(IntentResolver::class);
        $this->app->singleton(GoalResolver::class);
        $this->app->singleton(TaskPlanner::class);
        $this->app->singleton(ExecutionPlanner::class);
        $this->app->singleton(PlanningPolicy::class);
        $this->app->singleton(PlanningValidator::class);
        $this->app->singleton(ClarificationEngine::class);
        $this->app->singleton(EscalationEvaluator::class);
        $this->app->singleton(ConfidenceEvaluator::class);
        $this->app->singleton(PlanningStrategy::class);
        $this->app->singleton(PlanningPipelineFactory::class);
        $this->app->singleton(PlanningPipeline::class, static function ($app): PlanningPipeline {
            return $app->make(PlanningPipelineFactory::class)->create();
        });
        $this->app->singleton(PlannerEngine::class);

        // Sprint 18 — Platform Planner (registry-oriented; no tool execution)
        $this->app->singleton(PlatformIntentCatalog::class);
        $this->app->singleton(IntentAnalyzerInterface::class, IntentAnalyzer::class);
        $this->app->singleton(CapabilityMatcherInterface::class, CapabilityMatcher::class);
        $this->app->singleton(ToolSelectorInterface::class, ToolSelector::class);
        $this->app->singleton(PolicyEvaluatorInterface::class, PolicyEvaluator::class);
        $this->app->singleton(PlanBuilderInterface::class, ExecutionPlanBuilder::class);
        $this->app->singleton(PlanningContextProviderInterface::class, PlanningContextProvider::class);
        $this->app->singleton(ExecutionPlanRepositoryInterface::class, InMemoryExecutionPlanRepository::class);
        $this->app->singleton(PermissionValidator::class);
        $this->app->singleton(SubscriptionValidator::class);
        $this->app->singleton(PlannerInterface::class, PlatformPlannerEngine::class);
        $this->app->singleton(PlatformPlannerEngine::class);

        $this->app->singleton(PlannerModule::class);

        $this->app->afterResolving(ModuleRegistryInterface::class, function (ModuleRegistryInterface $registry): void {
            $this->registerModule($registry);
        });

        if ($this->app->resolved(ModuleRegistryInterface::class)) {
            $this->registerModule($this->app->make(ModuleRegistryInterface::class));
        }
    }

    public function boot(): void
    {
        $this->registerModule($this->app->make(ModuleRegistryInterface::class));
    }

    private function registerModule(ModuleRegistryInterface $registry): void
    {
        if (! $registry->has('aos.planner')) {
            $registry->add($this->app->make(PlannerModule::class));
        }
    }
}
