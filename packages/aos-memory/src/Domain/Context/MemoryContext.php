<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Context;

use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;
use DressnMore\Aos\Memory\Domain\Summary\ConversationSummary;

/**
 * Provider-agnostic memory context payload for Prompt Engine consumption.
 */
final class MemoryContext
{
    /**
     * @param  list<MemoryRecord>  $memories
     */
    public function __construct(
        private readonly string $tenantId,
        private readonly ?string $customerId,
        private readonly ?string $conversationId,
        private readonly array $memories,
        private readonly ?ConversationSummary $summary = null,
        private readonly string $compressedText = '',
        private readonly bool $compressionApplied = false,
    ) {}

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    public function customerId(): ?string
    {
        return $this->customerId;
    }

    public function conversationId(): ?string
    {
        return $this->conversationId;
    }

    /**
     * @return list<MemoryRecord>
     */
    public function memories(): array
    {
        return $this->memories;
    }

    public function summary(): ?ConversationSummary
    {
        return $this->summary;
    }

    public function compressedText(): string
    {
        return $this->compressedText !== '' ? $this->compressedText : $this->render();
    }

    public function compressionApplied(): bool
    {
        return $this->compressionApplied;
    }

    public function render(): string
    {
        if ($this->compressedText !== '') {
            return $this->compressedText;
        }

        $lines = [];
        if ($this->summary !== null) {
            $lines[] = 'Summary ('.$this->summary->kind()->value.'):';
            $lines[] = $this->summary->text();
        }
        foreach ($this->memories as $memory) {
            $lines[] = '- ['.$memory->type()->value.'] '.$memory->content();
        }

        return $lines === [] ? '[No memory context]' : implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'customer_id' => $this->customerId,
            'conversation_id' => $this->conversationId,
            'memory_count' => count($this->memories),
            'compression_applied' => $this->compressionApplied,
            'rendered' => $this->render(),
            'summary' => $this->summary?->text(),
        ];
    }
}
