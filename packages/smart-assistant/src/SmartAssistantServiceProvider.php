<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\SmartAssistant\Capability\AgentCapabilityCatalog;
use DressnMore\SmartAssistant\Contracts\Registry\AgentRegistryInterface;
use DressnMore\SmartAssistant\Contracts\Registry\AutomationRegistryInterface;
use DressnMore\SmartAssistant\Contracts\Registry\CapabilityRegistryInterface;
use DressnMore\SmartAssistant\Contracts\Registry\ChannelRegistryInterface;
use DressnMore\SmartAssistant\Contracts\Registry\IntegrationRegistryInterface;
use DressnMore\SmartAssistant\Contracts\Registry\KnowledgeRegistryInterface;
use DressnMore\SmartAssistant\Contracts\Registry\PromptRegistryInterface;
use DressnMore\SmartAssistant\Domain\Policy\TenantIsolationPolicy;
use DressnMore\SmartAssistant\Module\SmartAssistantModule;
use DressnMore\SmartAssistant\Registry\InMemoryAgentRegistry;
use DressnMore\SmartAssistant\Registry\InMemoryAutomationRegistry;
use DressnMore\SmartAssistant\Registry\InMemoryCapabilityRegistry;
use DressnMore\SmartAssistant\Registry\InMemoryChannelRegistry;
use DressnMore\SmartAssistant\Registry\InMemoryIntegrationRegistry;
use DressnMore\SmartAssistant\Registry\InMemoryKnowledgeRegistry;
use DressnMore\SmartAssistant\Registry\InMemoryPromptRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Registers Smart Assistant architecture foundation (no UI / DB / execution).
 */
final class SmartAssistantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AgentRegistryInterface::class, InMemoryAgentRegistry::class);
        $this->app->singleton(ChannelRegistryInterface::class, InMemoryChannelRegistry::class);
        $this->app->singleton(CapabilityRegistryInterface::class, InMemoryCapabilityRegistry::class);
        $this->app->singleton(PromptRegistryInterface::class, InMemoryPromptRegistry::class);
        $this->app->singleton(IntegrationRegistryInterface::class, InMemoryIntegrationRegistry::class);
        $this->app->singleton(KnowledgeRegistryInterface::class, InMemoryKnowledgeRegistry::class);
        $this->app->singleton(AutomationRegistryInterface::class, InMemoryAutomationRegistry::class);
        $this->app->singleton(AgentCapabilityCatalog::class);
        $this->app->singleton(TenantIsolationPolicy::class);
        $this->app->singleton(SmartAssistantModule::class);
    }

    public function boot(): void
    {
        if ($this->app->bound(CapabilityRegistryInterface::class)) {
            /** @var InMemoryCapabilityRegistry $caps */
            $caps = $this->app->make(CapabilityRegistryInterface::class);
            if ($caps instanceof InMemoryCapabilityRegistry && $caps->all() === []) {
                $this->app->make(AgentCapabilityCatalog::class)->seed($caps);
            }
        }

        if (! $this->app->bound(ModuleRegistryInterface::class)) {
            return;
        }

        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        if (! $registry->has('smart.assistant')) {
            $registry->add($this->app->make(SmartAssistantModule::class));
        }
    }
}
