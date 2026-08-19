<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Context;

use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;
use DressnMore\Aos\Memory\Domain\Summary\ConversationSummary;

final class MemoryContextBuilder
{
    /**
     * @param  list<MemoryRecord>  $memories
     */
    public function build(
        string $tenantId,
        ?string $customerId,
        ?string $conversationId,
        array $memories,
        ?ConversationSummary $summary = null,
        bool $compressPlaceholder = true,
    ): MemoryContext {
        $compressed = '';
        $applied = false;

        if ($compressPlaceholder) {
            // Placeholder compression: truncate long renders to reduce context explosion.
            $full = $this->renderPreview($memories, $summary);
            if (mb_strlen($full) > 2000) {
                $compressed = mb_substr($full, 0, 2000)."\n…[memory compressed placeholder]";
                $applied = true;
            }
        }

        return new MemoryContext(
            $tenantId,
            $customerId,
            $conversationId,
            $memories,
            $summary,
            $compressed,
            $applied,
        );
    }

    /**
     * @param  list<MemoryRecord>  $memories
     */
    private function renderPreview(array $memories, ?ConversationSummary $summary): string
    {
        $lines = [];
        if ($summary !== null) {
            $lines[] = $summary->text();
        }
        foreach ($memories as $memory) {
            $lines[] = '['.$memory->type()->value.'] '.$memory->content();
        }

        return implode("\n", $lines);
    }
}
