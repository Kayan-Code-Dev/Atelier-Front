<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Summary;

use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;
use DressnMore\Aos\Memory\Domain\Memory\MemoryType;

/**
 * Rule-based summarizer (no LLM). Produces classified summary facts.
 */
final class MemorySummarizer
{
    /**
     * @param  list<MemoryRecord>  $records
     */
    public function summarize(
        string $tenantId,
        string $conversationId,
        ?string $customerId,
        array $records,
        SummaryKind $kind = SummaryKind::Incremental,
    ): ConversationSummary {
        $facts = [];
        foreach ($records as $record) {
            if ($record->isDiscarded() || $record->isExpired()) {
                continue;
            }
            if (in_array($record->type(), [MemoryType::Working, MemoryType::Semantic], true)) {
                continue;
            }
            $facts[] = '['.$record->type()->value.'] '.$record->content();
        }

        $facts = array_values(array_unique($facts));
        $limit = match ($kind) {
            SummaryKind::Incremental => 5,
            SummaryKind::Rolling => 12,
            SummaryKind::Final => 25,
        };
        $facts = array_slice($facts, 0, $limit);

        $text = $facts === []
            ? 'No memorable facts yet for conversation '.$conversationId.'.'
            : implode("\n", array_map(static fn (string $f): string => '- '.$f, $facts));

        return new ConversationSummary(
            $tenantId,
            $conversationId,
            $customerId,
            $kind,
            $text,
            $facts,
        );
    }
}
