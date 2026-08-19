<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\TenantAi\Application\AiSessionManager;
use DressnMore\Aos\TenantAi\Application\AiWorkspaceManager;
use DressnMore\Aos\TenantAi\Application\ConversationManager;
use DressnMore\Aos\TenantAi\Application\ConversationMemoryService;
use DressnMore\Aos\TenantAi\Application\MessageManager;
use DressnMore\Aos\TenantAi\Application\PermissionResolver;
use DressnMore\Aos\TenantAi\Application\SubscriptionResolver;
use DressnMore\Aos\TenantAi\Application\TenantContextBuilder;
use DressnMore\Aos\TenantAi\Application\TenantIntegrationRegistry;
use DressnMore\Aos\TenantAi\Application\ToolAccessGuard;
use DressnMore\Aos\TenantAi\Contracts\ConversationProviderInterface;
use DressnMore\Aos\TenantAi\Contracts\ContextProviderInterface;
use DressnMore\Aos\TenantAi\Contracts\IntegrationProviderInterface;
use DressnMore\Aos\TenantAi\Contracts\MemoryProviderInterface;
use DressnMore\Aos\TenantAi\Contracts\MessageProviderInterface;
use DressnMore\Aos\TenantAi\Contracts\PermissionProviderInterface;
use DressnMore\Aos\TenantAi\Contracts\SessionProviderInterface;
use DressnMore\Aos\TenantAi\Contracts\SubscriptionProviderInterface;
use DressnMore\Aos\TenantAi\Contracts\TenantAiEventPublisherInterface;
use DressnMore\Aos\TenantAi\Contracts\WorkspaceProviderInterface;
use DressnMore\Aos\TenantAi\Infrastructure\InMemory\InMemoryConversationProvider;
use DressnMore\Aos\TenantAi\Infrastructure\InMemory\InMemoryIntegrationProvider;
use DressnMore\Aos\TenantAi\Infrastructure\InMemory\InMemoryMemoryProvider;
use DressnMore\Aos\TenantAi\Infrastructure\InMemory\InMemoryMessageProvider;
use DressnMore\Aos\TenantAi\Infrastructure\InMemory\InMemorySessionProvider;
use DressnMore\Aos\TenantAi\Infrastructure\InMemory\InMemoryTenantAiEventPublisher;
use DressnMore\Aos\TenantAi\Infrastructure\InMemory\InMemoryWorkspaceProvider;
use DressnMore\Aos\TenantAi\Module\TenantAiModule;
use Illuminate\Support\ServiceProvider;

final class AosTenantAiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InMemoryWorkspaceProvider::class);
        $this->app->singleton(WorkspaceProviderInterface::class, InMemoryWorkspaceProvider::class);
        $this->app->singleton(InMemoryConversationProvider::class);
        $this->app->singleton(ConversationProviderInterface::class, InMemoryConversationProvider::class);
        $this->app->singleton(InMemoryMessageProvider::class);
        $this->app->singleton(MessageProviderInterface::class, InMemoryMessageProvider::class);
        $this->app->singleton(InMemorySessionProvider::class);
        $this->app->singleton(SessionProviderInterface::class, InMemorySessionProvider::class);
        $this->app->singleton(InMemoryMemoryProvider::class);
        $this->app->singleton(MemoryProviderInterface::class, InMemoryMemoryProvider::class);
        $this->app->singleton(InMemoryIntegrationProvider::class);
        $this->app->singleton(IntegrationProviderInterface::class, InMemoryIntegrationProvider::class);
        $this->app->singleton(TenantAiEventPublisherInterface::class, InMemoryTenantAiEventPublisher::class);

        $this->app->singleton(PermissionResolver::class);
        $this->app->singleton(PermissionProviderInterface::class, PermissionResolver::class);
        $this->app->singleton(SubscriptionResolver::class);
        $this->app->singleton(SubscriptionProviderInterface::class, SubscriptionResolver::class);
        $this->app->singleton(TenantContextBuilder::class);
        $this->app->singleton(ContextProviderInterface::class, TenantContextBuilder::class);
        $this->app->singleton(AiWorkspaceManager::class);
        $this->app->singleton(ConversationManager::class);
        $this->app->singleton(MessageManager::class);
        $this->app->singleton(AiSessionManager::class);
        $this->app->singleton(ConversationMemoryService::class);
        $this->app->singleton(TenantIntegrationRegistry::class);
        $this->app->singleton(ToolAccessGuard::class);
        $this->app->singleton(TenantAiModule::class);
    }

    public function boot(): void
    {
        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        if (! $registry->has('aos.tenant-ai')) {
            $registry->add($this->app->make(TenantAiModule::class));
        }
    }
}
