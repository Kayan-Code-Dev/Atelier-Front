<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Metadata;

use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeDocument;

final class KnowledgeMetadataManager
{
    /**
     * @param  array<string, scalar|null>  $attributes
     */
    public function merge(KnowledgeDocument $document, array $attributes): KnowledgeDocument
    {
        return $document->withMetadata($document->metadata()->merge($attributes));
    }

    /**
     * @return array<string, scalar|null>
     */
    public function export(KnowledgeDocument $document): array
    {
        return $document->metadata()->all();
    }
}
