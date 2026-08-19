<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Memory\Application\MemoryEngine;
use DressnMore\Aos\Memory\Application\MemoryPipelineFactory;
use DressnMore\Aos\Memory\Contracts\MemoryEngineInterface;
use DressnMore\Aos\Memory\Domain\Context\MemoryContextBuilder;
use DressnMore\Aos\Memory\Domain\Factory\MemoryFactory;
use DressnMore\Aos\Memory\Domain\Index\MemoryIndexInterface;
use DressnMore\Aos\Memory\Domain\Memory\MemoryConsolidator;
use DressnMore\Aos\Memory\Domain\Memory\MemoryFactExtractor;
use DressnMore\Aos\Memory\Domain\Memory\MemoryManager;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRetriever;
use DressnMore\Aos\Memory\Domain\Memory\MemoryWriter;
use DressnMore\Aos\Memory\Domain\Policies\MemoryExpirationManager;
use DressnMore\Aos\Memory\Domain\Policies\MemoryPolicy;
use DressnMore\Aos\Memory\Domain\Ranking\MemoryRanker;
use DressnMore\Aos\Memory\Domain\Repository\MemoryStoreInterface;
use DressnMore\Aos\Memory\Domain\Snapshot\MemorySnapshotFactory;
use DressnMore\Aos\Memory\Domain\Summary\MemorySummarizer;
use DressnMore\Aos\Memory\Infrastructure\Persistence\InMemoryMemoryIndex;
use DressnMore\Aos\Memory\Infrastructure\Persistence\InMemoryMemoryStore;
use DressnMore\Aos\Memory\Module\MemoryModule;
use Illuminate\Support\ServiceProvider;

final class AosMemoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InMemoryMemoryStore::class);
        $this->app->singleton(MemoryStoreInterface::class, InMemoryMemoryStore::class);
        $this->app->singleton(InMemoryMemoryIndex::class);
        $this->app->singleton(MemoryIndexInterface::class, InMemoryMemoryIndex::class);

        $this->app->singleton(MemoryFactory::class);
        $this->app->singleton(MemoryPolicy::class);
        $this->app->singleton(MemoryFactExtractor::class);
        $this->app->singleton(MemoryConsolidator::class);
        $this->app->singleton(MemorySummarizer::class);
        $this->app->singleton(MemoryRanker::class);
        $this->app->singleton(MemoryContextBuilder::class);
        $this->app->singleton(MemoryExpirationManager::class);
        $this->app->singleton(MemorySnapshotFactory::class);
        $this->app->singleton(MemoryPipelineFactory::class);

        $this->app->singleton(MemoryWriter::class, static function ($app): MemoryWriter {
            return $app->make(MemoryPipelineFactory::class)->createWriter();
        });
        $this->app->singleton(MemoryRetriever::class, static function ($app): MemoryRetriever {
            return $app->make(MemoryPipelineFactory::class)->createRetriever();
        });

        $this->app->singleton(MemoryManager::class);
        $this->app->singleton(MemoryEngine::class);
        $this->app->singleton(MemoryEngineInterface::class, MemoryEngine::class);
        $this->app->singleton(MemoryModule::class);

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
        if (! $registry->has('aos.memory')) {
            $registry->add($this->app->make(MemoryModule::class));
        }
    }
}
