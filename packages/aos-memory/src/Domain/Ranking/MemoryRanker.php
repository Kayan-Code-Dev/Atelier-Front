<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Ranking;

use DateTimeImmutable;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;

/**
 * Scores memories by recency, importance, relevance, confidence, frequency.
 */
final class MemoryRanker
{
    /**
     * @param  list<MemoryRecord>  $records
     * @return list<MemoryRecord>
     */
    public function rank(array $records, string $query = '', ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $tokens = $this->tokens($query);

        $scored = [];
        foreach ($records as $record) {
            $scored[] = [
                'record' => $record,
                'score' => $this->score($record, $tokens, $now),
            ];
        }

        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_map(static fn (array $row): MemoryRecord => $row['record'], $scored);
    }

    /**
     * @param  list<string>  $queryTokens
     */
    public function score(MemoryRecord $record, array $queryTokens, DateTimeImmutable $now): float
    {
        $recency = $this->recency($record, $now);
        $importance = $record->importance()->value();
        $confidence = $record->confidence()->value();
        $frequency = min(1.0, $record->accessCount() / 10);
        $relevance = $this->relevance($record, $queryTokens);

        return (0.25 * $recency)
            + (0.30 * $importance)
            + (0.20 * $relevance)
            + (0.15 * $confidence)
            + (0.10 * $frequency);
    }

    private function recency(MemoryRecord $record, DateTimeImmutable $now): float
    {
        $ageSeconds = max(0, $now->getTimestamp() - $record->createdAt()->getTimestamp());
        $day = 86400;

        return max(0.0, 1.0 - ($ageSeconds / (30 * $day)));
    }

    /**
     * @param  list<string>  $queryTokens
     */
    private function relevance(MemoryRecord $record, array $queryTokens): float
    {
        if ($queryTokens === []) {
            return 0.5;
        }

        $haystack = mb_strtolower($record->content().' '.implode(' ', $record->tags()));
        $hits = 0;
        foreach ($queryTokens as $token) {
            if ($token !== '' && mb_strpos($haystack, $token) !== false) {
                $hits++;
            }
        }

        return min(1.0, $hits / count($queryTokens));
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
