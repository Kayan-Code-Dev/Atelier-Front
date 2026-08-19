<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Application;

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Knowledge\Contracts\KnowledgeEngineInterface;
use DressnMore\Aos\Knowledge\Domain\Collection\CollectionId;
use DressnMore\Aos\Knowledge\Domain\Collection\CollectionScope;
use DressnMore\Aos\Knowledge\Domain\Collection\KnowledgeCollection;
use DressnMore\Aos\Knowledge\Domain\Collection\KnowledgeCollectionManager;
use DressnMore\Aos\Knowledge\Domain\Context\KnowledgeContext;
use DressnMore\Aos\Knowledge\Domain\Context\KnowledgeContextBuilder;
use DressnMore\Aos\Knowledge\Domain\Events\KnowledgeArchived;
use DressnMore\Aos\Knowledge\Domain\Events\KnowledgeCreated;
use DressnMore\Aos\Knowledge\Domain\Events\KnowledgePolicyApplied;
use DressnMore\Aos\Knowledge\Domain\Events\KnowledgePublished;
use DressnMore\Aos\Knowledge\Domain\Events\KnowledgeRanked;
use DressnMore\Aos\Knowledge\Domain\Events\KnowledgeRejected;
use DressnMore\Aos\Knowledge\Domain\Events\KnowledgeRetrieved;
use DressnMore\Aos\Knowledge\Domain\Events\KnowledgeSearchCompleted;
use DressnMore\Aos\Knowledge\Domain\Events\KnowledgeUpdated;
use DressnMore\Aos\Knowledge\Domain\Events\KnowledgeVersionCreated;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeManager;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeRetrievalRequest;
use DressnMore\Aos\Knowledge\Domain\Policies\KnowledgePolicyEngine;
use DressnMore\Aos\Knowledge\Domain\Ranking\KnowledgeRanker;
use DressnMore\Aos\Knowledge\Domain\Search\LexicalKnowledgeSearchEngine;
use DressnMore\Aos\Knowledge\Domain\Snapshot\KnowledgeSnapshot;
use DressnMore\Aos\Knowledge\Domain\Source\KnowledgeSource;
use DressnMore\Aos\Knowledge\Domain\Source\KnowledgeSourceManager;
use DressnMore\Aos\Knowledge\Domain\Source\KnowledgeSourceType;
use DressnMore\Aos\Knowledge\Domain\Source\SourceId;
use DressnMore\Aos\Knowledge\Domain\Validation\KnowledgeValidator;
use DressnMore\Aos\Knowledge\Infrastructure\Persistence\InMemoryKnowledgeCollectionRepository;
use DressnMore\Aos\Knowledge\Infrastructure\Persistence\InMemoryKnowledgeDocumentRepository;
use DressnMore\Aos\Knowledge\Infrastructure\Persistence\InMemoryKnowledgeIndex;
use DressnMore\Aos\Knowledge\Infrastructure\Persistence\InMemoryKnowledgeSourceRepository;

/**
 * Knowledge Engine — enterprise knowledge platform; no LLM / embeddings / vector DB.
 */
final class KnowledgeEngine implements KnowledgeEngineInterface
{
    public function __construct(
        private readonly KnowledgeManager $manager,
        private readonly EventBusInterface $eventBus,
    ) {}

    public static function createDefault(EventBusInterface $eventBus): self
    {
        $documents = new InMemoryKnowledgeDocumentRepository();
        $collections = new InMemoryKnowledgeCollectionRepository();
        $sources = new InMemoryKnowledgeSourceRepository();
        $index = new InMemoryKnowledgeIndex();
        $policies = new KnowledgePolicyEngine();
        $factory = new KnowledgePipelineFactory(
            $documents,
            new LexicalKnowledgeSearchEngine(),
            new KnowledgeRanker(),
            $policies,
            new KnowledgeContextBuilder(),
        );

        $collectionManager = new KnowledgeCollectionManager($collections);
        $sourceManager = new KnowledgeSourceManager($sources);
        $registry = $factory->createRegistry($collectionManager, $sourceManager, $index);
        $manager = new KnowledgeManager($registry, $factory->createRetriever(), $documents);

        // Seed default global + manual source for smoke/tests.
        $registry->registerCollection(new KnowledgeCollection(
            CollectionId::fromString('col_global'),
            'Global Collection',
            CollectionScope::Global,
            description: 'Platform-wide knowledge',
        ));
        $registry->registerSource(new KnowledgeSource(
            SourceId::fromString('src_manual'),
            KnowledgeSourceType::ManualEntry,
            'Manual Entry',
        ));

        return new self($manager, $eventBus);
    }

    public function register(KnowledgeDocument $document): KnowledgeDocument
    {
        $validator = new KnowledgeValidator();
        if (! $validator->isValid($document)) {
            $this->eventBus->publish(new KnowledgeRejected(
                bin2hex(random_bytes(8)),
                $document->id()->toString(),
                implode(',', $validator->validate($document)),
            ));
            throw new \RuntimeException('Knowledge registration rejected.');
        }

        $saved = $this->manager->register($document);
        $this->eventBus->publish(new KnowledgeCreated(
            bin2hex(random_bytes(8)),
            $saved->id()->toString(),
            $saved->type()->value,
            $saved->tenantId(),
        ));

        return $saved;
    }

    public function publish(KnowledgeDocument $document): KnowledgeDocument
    {
        $published = $this->manager->publish($document);
        $this->eventBus->publish(new KnowledgePublished(
            bin2hex(random_bytes(8)),
            $published->id()->toString(),
        ));

        return $published;
    }

    public function archive(KnowledgeDocument $document): KnowledgeDocument
    {
        $archived = $this->manager->archive($document);
        $this->eventBus->publish(new KnowledgeArchived(
            bin2hex(random_bytes(8)),
            $archived->id()->toString(),
        ));

        return $archived;
    }

    public function update(KnowledgeDocument $document): KnowledgeDocument
    {
        $updated = $this->manager->update($document);
        $this->eventBus->publish(new KnowledgeUpdated(
            bin2hex(random_bytes(8)),
            $updated->id()->toString(),
            $updated->version()->version(),
        ));
        $this->eventBus->publish(new KnowledgeVersionCreated(
            bin2hex(random_bytes(8)),
            $updated->id()->toString(),
            $updated->version()->version(),
        ));

        return $updated;
    }

    public function retrieve(KnowledgeRetrievalRequest $request): KnowledgeContext
    {
        $bag = $this->manager->retriever()->retrieveBag($request);
        $context = $bag->context();
        if ($context === null) {
            throw new \RuntimeException('Knowledge context missing.');
        }

        $this->eventBus->publish(new KnowledgeSearchCompleted(
            $request->correlationId(),
            $request->query(),
            count($bag->hits()),
        ));
        $this->eventBus->publish(new KnowledgeRanked(
            $request->correlationId(),
            count($bag->ranked()),
        ));
        $this->eventBus->publish(new KnowledgePolicyApplied(
            $request->correlationId(),
            $bag->policyNotes(),
        ));
        $this->eventBus->publish(new KnowledgeRetrieved(
            $request->correlationId(),
            $request->tenantId(),
            count($context->hits()),
        ));

        return $context;
    }

    public function snapshot(?string $tenantId, int $limit = 50): KnowledgeSnapshot
    {
        return $this->manager->snapshot($tenantId, $limit);
    }

    public function manager(): KnowledgeManager
    {
        return $this->manager;
    }
}
