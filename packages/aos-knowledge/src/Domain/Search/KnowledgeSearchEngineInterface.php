<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Search;

use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;

/**
 * Swappable search port — lexical in Sprint 9; vector adapters later without Domain changes.
 */
interface KnowledgeSearchEngineInterface
{
    /**
     * @param  list<KnowledgeDocument>  $corpus
     * @return list<KnowledgeSearchHit>
     */
    public function search(string $query, array $corpus, int $limit = 50): array;
}
