<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Knowledge\Application\KnowledgeEngine;
use DressnMore\Aos\Knowledge\Application\KnowledgePipelineFactory;
use DressnMore\Aos\Knowledge\Contracts\KnowledgeEngineInterface;
use DressnMore\Aos\Knowledge\Domain\Collection\KnowledgeCollectionManager;
use DressnMore\Aos\Knowledge\Domain\Context\KnowledgeContextBuilder;
use DressnMore\Aos\Knowledge\Domain\Factory\KnowledgeFactory;
use DressnMore\Aos\Knowledge\Domain\Index\KnowledgeIndexInterface;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocumentManager;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeManager;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeRegistry;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeRetriever;
use DressnMore\Aos\Knowledge\Domain\Metadata\KnowledgeMetadataManager;
use DressnMore\Aos\Knowledge\Domain\Policies\KnowledgePolicyEngine;
use DressnMore\Aos\Knowledge\Domain\Publishing\KnowledgePublisher;
use DressnMore\Aos\Knowledge\Domain\Ranking\KnowledgeRanker;
use DressnMore\Aos\Knowledge\Domain\Repository\KnowledgeCollectionRepositoryInterface;
use DressnMore\Aos\Knowledge\Domain\Repository\KnowledgeDocumentRepositoryInterface;
use DressnMore\Aos\Knowledge\Domain\Repository\KnowledgeSourceRepositoryInterface;
use DressnMore\Aos\Knowledge\Domain\Search\KnowledgeSearchEngineInterface;
use DressnMore\Aos\Knowledge\Domain\Search\LexicalKnowledgeSearchEngine;
use DressnMore\Aos\Knowledge\Domain\Source\KnowledgeSourceManager;
use DressnMore\Aos\Knowledge\Domain\Validation\KnowledgeValidator;
use DressnMore\Aos\Knowledge\Infrastructure\Bootstrap\BuiltinKnowledgeCatalogBootstrap;
use DressnMore\Aos\Knowledge\Infrastructure\Persistence\InMemoryKnowledgeCollectionRepository;
use DressnMore\Aos\Knowledge\Infrastructure\Persistence\InMemoryKnowledgeDocumentRepository;
use DressnMore\Aos\Knowledge\Infrastructure\Persistence\InMemoryKnowledgeIndex;
use DressnMore\Aos\Knowledge\Infrastructure\Persistence\InMemoryKnowledgeSourceRepository;
use DressnMore\Aos\Knowledge\Module\KnowledgeModule;
use Illuminate\Support\ServiceProvider;

final class AosKnowledgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InMemoryKnowledgeDocumentRepository::class);
        $this->app->singleton(KnowledgeDocumentRepositoryInterface::class, InMemoryKnowledgeDocumentRepository::class);
        $this->app->singleton(InMemoryKnowledgeCollectionRepository::class);
        $this->app->singleton(KnowledgeCollectionRepositoryInterface::class, InMemoryKnowledgeCollectionRepository::class);
        $this->app->singleton(InMemoryKnowledgeSourceRepository::class);
        $this->app->singleton(KnowledgeSourceRepositoryInterface::class, InMemoryKnowledgeSourceRepository::class);
        $this->app->singleton(InMemoryKnowledgeIndex::class);
        $this->app->singleton(KnowledgeIndexInterface::class, InMemoryKnowledgeIndex::class);

        $this->app->singleton(KnowledgeSearchEngineInterface::class, LexicalKnowledgeSearchEngine::class);
        $this->app->singleton(KnowledgeFactory::class);
        $this->app->singleton(KnowledgePolicyEngine::class);
        $this->app->singleton(KnowledgeValidator::class);
        $this->app->singleton(KnowledgePublisher::class);
        $this->app->singleton(KnowledgeRanker::class);
        $this->app->singleton(KnowledgeContextBuilder::class);
        $this->app->singleton(KnowledgeMetadataManager::class);
        $this->app->singleton(KnowledgeCollectionManager::class);
        $this->app->singleton(KnowledgeSourceManager::class);
        $this->app->singleton(KnowledgeDocumentManager::class);
        $this->app->singleton(KnowledgePipelineFactory::class);

        $this->app->singleton(KnowledgeRegistry::class, static function ($app): KnowledgeRegistry {
            return new KnowledgeRegistry(
                $app->make(KnowledgeCollectionManager::class),
                $app->make(KnowledgeSourceManager::class),
                $app->make(KnowledgeDocumentManager::class),
            );
        });

        $this->app->singleton(KnowledgeRetriever::class, static function ($app): KnowledgeRetriever {
            return $app->make(KnowledgePipelineFactory::class)->createRetriever();
        });

        $this->app->singleton(KnowledgeManager::class);
        $this->app->singleton(KnowledgeEngine::class);
        $this->app->singleton(KnowledgeEngineInterface::class, KnowledgeEngine::class);
        $this->app->singleton(BuiltinKnowledgeCatalogBootstrap::class);
        $this->app->singleton(KnowledgeModule::class);

        $this->app->afterResolving(ModuleRegistryInterface::class, function (ModuleRegistryInterface $registry): void {
            $this->registerModule($registry);
        });

        if ($this->app->resolved(ModuleRegistryInterface::class)) {
            $this->registerModule($this->app->make(ModuleRegistryInterface::class));
        }
    }

    public function boot(): void
    {
        $this->app->make(BuiltinKnowledgeCatalogBootstrap::class)->seed();
        $this->registerModule($this->app->make(ModuleRegistryInterface::class));
    }

    private function registerModule(ModuleRegistryInterface $registry): void
    {
        if (! $registry->has('aos.knowledge')) {
            $registry->add($this->app->make(KnowledgeModule::class));
        }
    }
}
