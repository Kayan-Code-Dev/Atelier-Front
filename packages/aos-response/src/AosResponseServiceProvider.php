<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Response\Application\ConversationReplyGenerator;
use DressnMore\Aos\Response\Application\EndToEndAiOrchestrator;
use DressnMore\Aos\Response\Application\ErrorResponseGenerator;
use DressnMore\Aos\Response\Application\LocalizationService;
use DressnMore\Aos\Response\Application\PlanStepExecutor;
use DressnMore\Aos\Response\Application\ResponseBuilder;
use DressnMore\Aos\Response\Application\ResponseEngine;
use DressnMore\Aos\Response\Application\ResultAggregator;
use DressnMore\Aos\Response\Application\ResultFormatter;
use DressnMore\Aos\Response\Application\ToolOutcomeFactory;
use DressnMore\Aos\Response\Contracts\ErrorResponseInterface;
use DressnMore\Aos\Response\Contracts\LocalizationInterface;
use DressnMore\Aos\Response\Contracts\ResponseBuilderInterface;
use DressnMore\Aos\Response\Contracts\ResponseEngineInterface;
use DressnMore\Aos\Response\Contracts\ResultAggregatorInterface;
use DressnMore\Aos\Response\Domain\Policy\ResponsePolicy;
use DressnMore\Aos\Response\Module\ResponseModule;
use Illuminate\Support\ServiceProvider;

final class AosResponseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ResponsePolicy::class);
        $this->app->singleton(LocalizationInterface::class, LocalizationService::class);
        $this->app->singleton(LocalizationService::class);
        $this->app->singleton(ResultFormatter::class);
        $this->app->singleton(ErrorResponseInterface::class, ErrorResponseGenerator::class);
        $this->app->singleton(ErrorResponseGenerator::class);
        $this->app->singleton(ResultAggregatorInterface::class, ResultAggregator::class);
        $this->app->singleton(ResultAggregator::class);
        $this->app->singleton(ResponseBuilderInterface::class, ResponseBuilder::class);
        $this->app->singleton(ResponseBuilder::class);
        $this->app->singleton(ResponseEngineInterface::class, ResponseEngine::class);
        $this->app->singleton(ResponseEngine::class);
        $this->app->singleton(ToolOutcomeFactory::class);
        $this->app->singleton(PlanStepExecutor::class);
        $this->app->singleton(ConversationReplyGenerator::class);
        $this->app->singleton(EndToEndAiOrchestrator::class);
        $this->app->singleton(ResponseModule::class);
    }

    public function boot(): void
    {
        if (! $this->app->bound(ModuleRegistryInterface::class)) {
            return;
        }

        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        if (! $registry->has('aos.response')) {
            $registry->add($this->app->make(ResponseModule::class));
        }
    }
}
