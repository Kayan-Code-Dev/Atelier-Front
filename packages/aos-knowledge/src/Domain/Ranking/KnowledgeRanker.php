<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Ranking;

use DateTimeImmutable;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;
use DressnMore\Aos\Knowledge\Domain\Search\KnowledgeSearchHit;

/**
 * Ranks by relevance, freshness, confidence, importance, popularity, business priority.
 */
final class KnowledgeRanker
{
    /**
     * @param  list<KnowledgeSearchHit>  $hits
     * @return list<KnowledgeSearchHit>
     */
    public function rank(array $hits, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $scored = [];
        foreach ($hits as $hit) {
            $scored[] = [
                'hit' => new KnowledgeSearchHit($hit->document(), $this->score($hit, $now)),
                'score' => $this->score($hit, $now),
            ];
        }
        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_map(static fn (array $row): KnowledgeSearchHit => $row['hit'], $scored);
    }

    public function score(KnowledgeSearchHit $hit, DateTimeImmutable $now): float
    {
        $doc = $hit->document();
        $freshness = $this->freshness($doc, $now);
        $popularity = min(1.0, $doc->popularity() / 20);

        return (0.30 * $hit->relevance())
            + (0.15 * $freshness)
            + (0.15 * $doc->confidence())
            + (0.15 * $doc->importance())
            + (0.10 * $popularity)
            + (0.15 * $doc->businessPriority());
    }

    private function freshness(KnowledgeDocument $document, DateTimeImmutable $now): float
    {
        $age = max(0, $now->getTimestamp() - $document->updatedAt()->getTimestamp());

        return max(0.0, 1.0 - ($age / (180 * 86400)));
    }
}
