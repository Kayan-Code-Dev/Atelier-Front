<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Pipeline;

use DressnMore\Aos\Knowledge\Domain\Context\KnowledgeContext;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeRetrievalRequest;
use DressnMore\Aos\Knowledge\Domain\Search\KnowledgeSearchHit;

final class KnowledgeRetrievalBag
{
    /** @var list<KnowledgeDocument> */
    private array $corpus = [];

    /** @var list<KnowledgeSearchHit> */
    private array $hits = [];

    /** @var list<KnowledgeSearchHit> */
    private array $ranked = [];

    /** @var list<KnowledgeSearchHit> */
    private array $filtered = [];

    private ?KnowledgeContext $context = null;

    /** @var list<string> */
    private array $stages = [];

    /** @var list<string> */
    private array $policyNotes = [];

    public function __construct(
        private readonly KnowledgeRetrievalRequest $request,
    ) {}

    public function request(): KnowledgeRetrievalRequest
    {
        return $this->request;
    }

    public function mark(string $stage): void
    {
        $this->stages[] = $stage;
    }

    /** @return list<string> */
    public function stages(): array
    {
        return $this->stages;
    }

    /** @param  list<KnowledgeDocument>  $corpus */
    public function setCorpus(array $corpus): void
    {
        $this->corpus = $corpus;
    }

    /** @return list<KnowledgeDocument> */
    public function corpus(): array
    {
        return $this->corpus;
    }

    /** @param  list<KnowledgeSearchHit>  $hits */
    public function setHits(array $hits): void
    {
        $this->hits = $hits;
    }

    /** @return list<KnowledgeSearchHit> */
    public function hits(): array
    {
        return $this->hits;
    }

    /** @param  list<KnowledgeSearchHit>  $ranked */
    public function setRanked(array $ranked): void
    {
        $this->ranked = $ranked;
    }

    /** @return list<KnowledgeSearchHit> */
    public function ranked(): array
    {
        return $this->ranked;
    }

    /** @param  list<KnowledgeSearchHit>  $filtered */
    public function setFiltered(array $filtered): void
    {
        $this->filtered = $filtered;
    }

    /** @return list<KnowledgeSearchHit> */
    public function filtered(): array
    {
        return $this->filtered;
    }

    public function setContext(KnowledgeContext $context): void
    {
        $this->context = $context;
    }

    public function context(): ?KnowledgeContext
    {
        return $this->context;
    }

    public function addPolicyNote(string $note): void
    {
        $this->policyNotes[] = $note;
    }

    /** @return list<string> */
    public function policyNotes(): array
    {
        return $this->policyNotes;
    }
}
