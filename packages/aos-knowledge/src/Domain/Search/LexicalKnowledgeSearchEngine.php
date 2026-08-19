<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Search;

use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;

/**
 * Lexical keyword search — no embeddings / vector DB.
 */
final class LexicalKnowledgeSearchEngine implements KnowledgeSearchEngineInterface
{
    public function search(string $query, array $corpus, int $limit = 50): array
    {
        $tokens = $this->tokens($query);
        $hits = [];

        foreach ($corpus as $document) {
            /** @var KnowledgeDocument $document */
            $relevance = $this->score($document, $tokens);
            if ($relevance <= 0.0 && $tokens !== []) {
                continue;
            }
            if ($tokens === []) {
                $relevance = 0.35;
            }
            $hits[] = new KnowledgeSearchHit($document, $relevance);
        }

        usort($hits, static fn (KnowledgeSearchHit $a, KnowledgeSearchHit $b): int => $b->relevance() <=> $a->relevance());

        return array_slice($hits, 0, max(1, $limit));
    }

    /**
     * @param  list<string>  $tokens
     */
    private function score(KnowledgeDocument $document, array $tokens): float
    {
        if ($tokens === []) {
            return 0.0;
        }

        $haystack = $document->searchableText();
        $hits = 0;
        foreach ($tokens as $token) {
            if (mb_strpos($haystack, $token) !== false) {
                $hits++;
                if (mb_strpos(mb_strtolower($document->title()), $token) !== false) {
                    $hits += 0.5;
                }
            }
        }

        return min(1.0, $hits / count($tokens));
    }

    /**
     * @return list<string>
     */
    private function tokens(string $query): array
    {
        $query = mb_strtolower(trim($query));
        if ($query === '') {
            return [];
        }
        $parts = preg_split('/[\s,.;:!?]+/u', $query) ?: [];

        return array_values(array_filter($parts, static fn (string $p): bool => mb_strlen($p) > 1));
    }
}
