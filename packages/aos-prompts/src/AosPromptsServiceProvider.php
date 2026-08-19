<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Prompts\Application\PromptEngine;
use DressnMore\Aos\Prompts\Application\PromptPipelineFactory;
use DressnMore\Aos\Prompts\Contracts\PromptEngineInterface;
use DressnMore\Aos\Prompts\Domain\Builder\PromptBuilder;
use DressnMore\Aos\Prompts\Domain\Composer\PromptComposer;
use DressnMore\Aos\Prompts\Domain\Composer\PromptRenderer;
use DressnMore\Aos\Prompts\Domain\Context\PromptContextInjector;
use DressnMore\Aos\Prompts\Domain\Factory\PromptFactory;
use DressnMore\Aos\Prompts\Domain\Guard\PromptGuard;
use DressnMore\Aos\Prompts\Domain\Optimizer\PromptOptimizer;
use DressnMore\Aos\Prompts\Domain\Persona\PersonaRegistry;
use DressnMore\Aos\Prompts\Domain\Persona\PersonaRegistryInterface;
use DressnMore\Aos\Prompts\Domain\Persona\PersonaResolver;
use DressnMore\Aos\Prompts\Domain\Pipeline\PromptPipeline;
use DressnMore\Aos\Prompts\Domain\Policies\PromptSafetyPolicy;
use DressnMore\Aos\Prompts\Domain\Policy\PromptPolicyResolver;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptRegistry;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptRegistryInterface;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptVersionManager;
use DressnMore\Aos\Prompts\Domain\Sanitizer\PromptSanitizer;
use DressnMore\Aos\Prompts\Domain\Template\PromptTemplateEngine;
use DressnMore\Aos\Prompts\Domain\Template\PromptTemplateRegistry;
use DressnMore\Aos\Prompts\Domain\Template\PromptTemplateRegistryInterface;
use DressnMore\Aos\Prompts\Domain\Validation\PromptValidator;
use DressnMore\Aos\Prompts\Infrastructure\Bootstrap\BuiltinPromptCatalogBootstrap;
use DressnMore\Aos\Prompts\Module\PromptsModule;
use Illuminate\Support\ServiceProvider;

final class AosPromptsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PersonaRegistry::class);
        $this->app->singleton(PersonaRegistryInterface::class, PersonaRegistry::class);
        $this->app->singleton(PromptTemplateRegistry::class);
        $this->app->singleton(PromptTemplateRegistryInterface::class, PromptTemplateRegistry::class);
        $this->app->singleton(PromptRegistry::class);
        $this->app->singleton(PromptRegistryInterface::class, PromptRegistry::class);

        $this->app->singleton(PromptGuard::class);
        $this->app->singleton(PromptSanitizer::class);
        $this->app->singleton(PromptOptimizer::class);
        $this->app->singleton(PromptComposer::class);
        $this->app->singleton(PromptRenderer::class);
        $this->app->singleton(PromptValidator::class);
        $this->app->singleton(PromptPolicyResolver::class);
        $this->app->singleton(PromptSafetyPolicy::class);
        $this->app->singleton(PromptVersionManager::class);
        $this->app->singleton(PersonaResolver::class);
        $this->app->singleton(PromptContextInjector::class);
        $this->app->singleton(PromptTemplateEngine::class);
        $this->app->singleton(PromptBuilder::class);
        $this->app->singleton(PromptFactory::class);
        $this->app->singleton(BuiltinPromptCatalogBootstrap::class);
        $this->app->singleton(PromptPipelineFactory::class);
        $this->app->singleton(PromptPipeline::class, static function ($app): PromptPipeline {
            return $app->make(PromptPipelineFactory::class)->create();
        });
        $this->app->singleton(PromptEngine::class);
        $this->app->singleton(PromptEngineInterface::class, PromptEngine::class);
        $this->app->singleton(PromptsModule::class);

        $this->app->afterResolving(ModuleRegistryInterface::class, function (ModuleRegistryInterface $registry): void {
            $this->registerModule($registry);
        });

        if ($this->app->resolved(ModuleRegistryInterface::class)) {
            $this->registerModule($this->app->make(ModuleRegistryInterface::class));
        }
    }

    public function boot(): void
    {
        $this->app->make(BuiltinPromptCatalogBootstrap::class)->seed();
        $this->registerModule($this->app->make(ModuleRegistryInterface::class));
    }

    private function registerModule(ModuleRegistryInterface $registry): void
    {
        if (! $registry->has('aos.prompts')) {
            $registry->add($this->app->make(PromptsModule::class));
        }
    }
}
