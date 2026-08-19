<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Context;

use DressnMore\Aos\Knowledge\Domain\Search\KnowledgeSearchHit;

final class KnowledgeContextBuilder
{
    /**
     * @param  list<KnowledgeSearchHit>  $hits
     */
    public function build(?string $tenantId, string $query, array $hits, bool $compressPlaceholder = true): KnowledgeContext
    {
        $sources = [];
        $confidenceSum = 0.0;
        foreach ($hits as $hit) {
            $doc = $hit->document();
            $sources[] = $doc->sourceType()->value.':'.$doc->id()->toString();
            $confidenceSum += $doc->confidence();
        }
        $avg = $hits === [] ? 0.0 : $confidenceSum / count($hits);

        $compressed = '';
        $applied = false;
        $preview = $this->preview($hits);
        if ($compressPlaceholder && mb_strlen($preview) > 2500) {
            $compressed = mb_substr($preview, 0, 2500)."\n…[knowledge compressed placeholder]";
            $applied = true;
        }

        return new KnowledgeContext(
            $tenantId,
            $query,
            $hits,
            array_values(array_unique($sources)),
            $avg,
            $compressed,
            $applied,
        );
    }

    /**
     * @param  list<KnowledgeSearchHit>  $hits
     */
    private function preview(array $hits): string
    {
        $lines = [];
        foreach ($hits as $hit) {
            $doc = $hit->document();
            $lines[] = $doc->title().': '.$doc->body();
        }

        return implode("\n", $lines);
    }
}
