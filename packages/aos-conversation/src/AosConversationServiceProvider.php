<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation;

use DressnMore\Aos\Conversation\Application\ConversationLifecycle;
use DressnMore\Aos\Conversation\Application\ConversationManager;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationFactory;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationRepositoryInterface;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationStateMachine;
use DressnMore\Aos\Conversation\Domain\Conversation\Policies\OwnershipPolicy;
use DressnMore\Aos\Conversation\Infrastructure\Persistence\InMemoryConversationRepository;
use DressnMore\Aos\Conversation\Module\ConversationModule;
use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Registers Conversation Engine contracts and the aos.conversation module.
 */
final class AosConversationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConversationStateMachine::class);
        $this->app->singleton(OwnershipPolicy::class);
        $this->app->singleton(ConversationFactory::class);
        $this->app->singleton(ConversationLifecycle::class);
        $this->app->singleton(InMemoryConversationRepository::class);
        $this->app->singleton(
            ConversationRepositoryInterface::class,
            InMemoryConversationRepository::class
        );
        $this->app->singleton(ConversationManager::class);
        $this->app->singleton(ConversationModule::class);

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
        if (! $registry->has('aos.conversation')) {
            $registry->add($this->app->make(ConversationModule::class));
        }
    }
}
