<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai;

use DressnMore\Aos\Ai\Application\AiEngine;
use DressnMore\Aos\Ai\Application\AiPipelineFactory;
use DressnMore\Aos\Ai\Contracts\AiEngineInterface;
use DressnMore\Aos\Ai\Domain\Capability\CapabilityRegistry;
use DressnMore\Aos\Ai\Domain\Cost\CostManager;
use DressnMore\Aos\Ai\Domain\Factory\ProviderFactory;
use DressnMore\Aos\Ai\Domain\Fallback\FallbackManager;
use DressnMore\Aos\Ai\Domain\Health\ProviderHealthMonitor;
use DressnMore\Aos\Ai\Domain\Metrics\ProviderMetrics;
use DressnMore\Aos\Ai\Domain\Model\ModelCatalog;
use DressnMore\Aos\Ai\Domain\Model\ModelRegistry;
use DressnMore\Aos\Ai\Domain\Model\ModelRegistryInterface;
use DressnMore\Aos\Ai\Domain\Model\ModelResolver;
use DressnMore\Aos\Ai\Domain\Pipeline\AiPipeline;
use DressnMore\Aos\Ai\Domain\Policies\ProviderPolicyEngine;
use DressnMore\Aos\Ai\Domain\Provider\ProviderManager;
use DressnMore\Aos\Ai\Domain\Provider\ProviderRegistry;
use DressnMore\Aos\Ai\Domain\Provider\ProviderRegistryInterface;
use DressnMore\Aos\Ai\Domain\Provider\ProviderResolver;
use DressnMore\Aos\Ai\Domain\Request\CompletionEngine;
use DressnMore\Aos\Ai\Domain\Retry\RetryManager;
use DressnMore\Aos\Ai\Domain\Selection\ProviderSelector;
use DressnMore\Aos\Ai\Domain\Streaming\StreamingEngine;
use DressnMore\Aos\Ai\Domain\Token\TokenManager;
use DressnMore\Aos\Ai\Infrastructure\Bootstrap\BuiltinAiCatalogBootstrap;
use DressnMore\Aos\Ai\Module\AiModule;
use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Registers AI provider platform. Module registration is deferred to boot()
 * so EventBus (aos-events) is available — package name sorts before aos-events.
 */
final class AosAiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProviderRegistry::class);
        $this->app->singleton(ProviderRegistryInterface::class, ProviderRegistry::class);
        $this->app->singleton(ModelRegistry::class);
        $this->app->singleton(ModelRegistryInterface::class, ModelRegistry::class);
        $this->app->singleton(ModelCatalog::class);
        $this->app->singleton(ProviderFactory::class);
        $this->app->singleton(ProviderHealthMonitor::class);
        $this->app->singleton(ProviderMetrics::class);
        $this->app->singleton(ProviderPolicyEngine::class);
        $this->app->singleton(CapabilityRegistry::class);
        $this->app->singleton(ProviderResolver::class);
        $this->app->singleton(ModelResolver::class);
        $this->app->singleton(ProviderSelector::class);
        $this->app->singleton(RetryManager::class);
        $this->app->singleton(FallbackManager::class);
        $this->app->singleton(CostManager::class);
        $this->app->singleton(TokenManager::class);
        $this->app->singleton(ProviderManager::class);
        $this->app->singleton(BuiltinAiCatalogBootstrap::class);
        $this->app->singleton(AiPipelineFactory::class);
        $this->app->singleton(AiPipeline::class, static function ($app): AiPipeline {
            return $app->make(AiPipelineFactory::class)->create();
        });
        $this->app->singleton(CompletionEngine::class);
        $this->app->singleton(StreamingEngine::class);
        $this->app->singleton(AiEngine::class);
        $this->app->singleton(AiEngineInterface::class, AiEngine::class);
        $this->app->singleton(AiModule::class);
    }

    public function boot(): void
    {
        $this->app->make(BuiltinAiCatalogBootstrap::class)->seed();

        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        if (! $registry->has('aos.ai')) {
            $registry->add($this->app->make(AiModule::class));
        }
    }
}
