<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Context;

use DressnMore\Aos\Knowledge\Domain\Search\KnowledgeSearchHit;

/**
 * Provider-agnostic knowledge context for Prompt Engine.
 */
final class KnowledgeContext
{
    /**
     * @param  list<KnowledgeSearchHit>  $hits
     * @param  list<string>  $sources
     */
    public function __construct(
        private readonly ?string $tenantId,
        private readonly string $query,
        private readonly array $hits,
        private readonly array $sources,
        private readonly float $averageConfidence,
        private readonly string $compressedText = '',
        private readonly bool $compressionApplied = false,
        private readonly string $summaryPlaceholder = '[Knowledge summary placeholder]',
    ) {}

    public function tenantId(): ?string
    {
        return $this->tenantId;
    }

    public function query(): string
    {
        return $this->query;
    }

    /** @return list<KnowledgeSearchHit> */
    public function hits(): array
    {
        return $this->hits;
    }

    /** @return list<string> */
    public function sources(): array
    {
        return $this->sources;
    }

    public function averageConfidence(): float
    {
        return $this->averageConfidence;
    }

    public function compressionApplied(): bool
    {
        return $this->compressionApplied;
    }

    public function summaryPlaceholder(): string
    {
        return $this->summaryPlaceholder;
    }

    public function render(): string
    {
        if ($this->compressedText !== '') {
            return $this->compressedText;
        }

        if ($this->hits === []) {
            return '[No knowledge context]';
        }

        $lines = [$this->summaryPlaceholder];
        foreach ($this->hits as $hit) {
            $doc = $hit->document();
            $lines[] = sprintf(
                '- [%s|%s|score=%.2f] %s: %s',
                $doc->type()->value,
                $doc->version()->version(),
                $hit->relevance(),
                $doc->title(),
                mb_substr($doc->body(), 0, 180)
            );
        }

        return implode("\n", $lines);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'query' => $this->query,
            'hit_count' => count($this->hits),
            'average_confidence' => $this->averageConfidence,
            'sources' => $this->sources,
            'compression_applied' => $this->compressionApplied,
            'rendered' => $this->render(),
        ];
    }
}
